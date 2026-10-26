<?php

namespace Modules\Ecommerce\Tests\Feature;

use App\Models\Currency;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Modules\Ecommerce\Http\Controllers\CartController;
use Modules\Ecommerce\Http\Middleware\Ecommerce;
use Modules\Ecommerce\Services\StorefrontCartService;
use Tests\TestCase;

class CartManagementTest extends TestCase
{
    use DatabaseTransactions;

    private StorefrontCartService $cart;
    private int $warehouseId;

    protected function setUp(): void
    {
        parent::setUp();
        session()->forget(['cart', 'total_qty', 'subTotal']);
        Currency::create([
            'name' => 'Cart Management Test Currency',
            'code' => 'CMT-' . uniqid(),
            'symbol' => '$',
            'exchange_rate' => 1,
            'is_active' => true,
        ]);
        $this->warehouseId = (int) DB::table('warehouses')->value('id');
        DB::table('ecommerce_settings')->where('id', DB::table('ecommerce_settings')->max('id'))
            ->update(['warehouse_id' => $this->warehouseId]);
        $this->cart = app(StorefrontCartService::class);
    }

    public function test_cart_read_removes_products_that_become_ineligible(): void
    {
        $product = $this->standardProduct();
        $this->cart->add($product->id, null, 1);
        $product->update(['is_online' => 0]);

        $snapshot = $this->cart->current();

        $this->assertSame([], $snapshot['cart']);
        $this->assertSame(0.0, $snapshot['total_qty']);
        $this->assertSame(0.0, $snapshot['subTotal']);
        $this->assertArrayHasKey((string) $product->id, $snapshot['removed']);
    }

    public function test_cart_read_refreshes_current_regular_and_promotion_prices(): void
    {
        $product = $this->standardProduct();
        $product->update([
            'price' => 100,
            'promotion' => 1,
            'promotion_price' => 80,
            'starting_date' => now()->subDay()->toDateString(),
            'last_date' => now()->addDay()->toDateString(),
        ]);
        $this->cart->add($product->id, null, 2);
        $this->assertSame(160.0, session('subTotal'));

        $product->update(['promotion_price' => 70]);
        $refreshed = $this->cart->current();
        $this->assertSame(70.0, $refreshed['cart'][(string) $product->id]['unit_price']);
        $this->assertSame(140.0, $refreshed['subTotal']);

        $product->update(['last_date' => now()->subDay()->toDateString(), 'price' => 110]);
        $expired = $this->cart->current();
        $this->assertSame(110.0, $expired['cart'][(string) $product->id]['unit_price']);
        $this->assertSame(220.0, $expired['subTotal']);
    }

    public function test_named_add_to_cart_route_mutates_the_storefront_session(): void
    {
        $product = $this->standardProduct();
        $route = Route::getRoutes()->getByName('addToCart');

        $this->assertNotNull($route);
        $this->assertSame('add-to-cart', $route->uri());

        $response = $this->withoutMiddleware(Ecommerce::class)
            ->postJson(route('addToCart'), ['product_id' => $product->id, 'qty' => 2]);

        $response->assertOk()
            ->assertJsonPath('total_qty', 2)
            ->assertJsonPath('line.id', $product->id);
        $this->assertArrayHasKey((string) $product->id, session('cart'));
    }

    public function test_add_fails_cleanly_without_an_active_currency_and_does_not_mutate_cart(): void
    {
        Currency::query()->update(['is_active' => 0]);
        session()->forget(['cart', 'currency_code', 'currency_rate']);
        $product = $this->standardProduct();

        $response = app(CartController::class)->addToCart(
            Request::create('/add-to-cart', 'POST', ['product_id' => $product->id, 'qty' => 1]),
            $this->cart
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([], session('cart', []));
        $this->assertStringContainsString('currency', strtolower((string) $response->getContent()));
    }

    public function test_update_rejects_invalid_quantity_and_stock_reduction(): void
    {
        $product = $this->standardProduct();
        $product->update(['in_stock' => 0, 'qty' => 5]);
        $this->stock($product->id, null, 5);
        $this->cart->add($product->id, null, 2);
        $controller = app(CartController::class);

        foreach ([0, -1, 'bad', 1000000] as $quantity) {
            $response = $controller->updateCart(
                Request::create('/update-cart', 'POST', [
                    'product_id' => $product->id,
                    'product_qty' => $quantity,
                ]),
                $this->cart
            );
            $this->assertSame(422, $response->getStatusCode());
        }

        $product->update(['qty' => 1]);
        $response = $controller->updateCart(
            Request::create('/update-cart', 'POST', [
                'product_id' => $product->id,
                'product_qty' => 2,
            ]),
            $this->cart
        );
        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_repeated_addition_recalculates_and_enforces_cumulative_stock(): void
    {
        $product = $this->standardProduct();
        $product->update(['in_stock' => 0, 'qty' => 3, 'price' => 12.3456, 'promotion' => 0]);
        $this->stock($product->id, null, 3);

        $first = $this->cart->add($product->id, null, 1.5);
        $second = $this->cart->add($product->id, null, 1.5);

        $this->assertCount(1, $second['cart']);
        $this->assertSame(3.0, $second['total_qty']);
        $this->assertSame(37.0368, $second['subTotal']);

        $this->expectException(\InvalidArgumentException::class);
        $this->cart->add($product->id, null, 0.1);
    }

    public function test_variant_lines_remain_distinct_and_stale_variant_is_removed(): void
    {
        $product = $this->standardProduct();
        $product->update(['is_variant' => 1, 'in_stock' => 0, 'qty' => 10, 'price' => 50]);
        [$firstId, $secondId] = $this->createVariants($product);

        $snapshot = $this->cart->add($product->id, $firstId, 1);
        $snapshot = $this->cart->add($product->id, $secondId, 1);

        $this->assertArrayHasKey($product->id . ',' . $firstId, $snapshot['cart']);
        $this->assertArrayHasKey($product->id . ',' . $secondId, $snapshot['cart']);
        $this->assertSame(105.0, $snapshot['subTotal']);

        DB::table('product_variants')->where('id', $firstId)->delete();
        $refreshed = $this->cart->current();
        $this->assertArrayNotHasKey($product->id . ',' . $firstId, $refreshed['cart']);
        $this->assertArrayHasKey($product->id . ',' . $secondId, $refreshed['cart']);
        $this->assertSame(55.0, $refreshed['subTotal']);
    }

    public function test_remove_uses_exact_opaque_variant_identity(): void
    {
        $product = $this->standardProduct();
        $product->update(['is_variant' => 1, 'in_stock' => 0, 'qty' => 10]);
        [$firstId, $secondId] = $this->createVariants($product);
        $this->cart->add($product->id, $firstId, 1);
        $this->cart->add($product->id, $secondId, 1);

        $snapshot = $this->cart->remove($product->id, $firstId);

        $this->assertArrayNotHasKey($product->id . ',' . $firstId, $snapshot['cart']);
        $this->assertArrayHasKey($product->id . ',' . $secondId, $snapshot['cart']);
    }

    private function standardProduct(): Product
    {
        $product = Product::query()
            ->where('type', 'standard')
            ->where(fn ($query) => $query->whereNull('is_imei')->orWhere('is_imei', 0))
            ->first();

        if (!$product) {
            $unitId = DB::table('units')->value('id') ?? 1;
            $catId = DB::table('categories')->value('id') ?? 1;
            $product = Product::create([
                'name' => 'Ecommerce Cart Test Product',
                'code' => 'ECOMM-CART-' . uniqid(),
                'type' => 'standard',
                'barcode_symbology' => 'C128',
                'category_id' => $catId,
                'unit_id' => $unitId,
                'purchase_unit_id' => $unitId,
                'sale_unit_id' => $unitId,
                'cost' => 50,
                'price' => 100,
                'qty' => 100,
                'is_active' => 1,
                'is_online' => 1,
                'is_imei' => 0,
                'is_variant' => 0,
                'in_stock' => 1,
                'promotion' => 0,
            ]);
        } else {
            $product->update([
                'is_active' => 1,
                'is_online' => 1,
                'is_imei' => 0,
                'is_variant' => 0,
                'in_stock' => 1,
                'promotion' => 0,
            ]);
        }

        return $product->fresh();
    }

    private function createVariants(Product $product): array
    {
        $variantA = DB::table('variants')->insertGetId(['name' => 'Phase3B1/A']);
        $variantB = DB::table('variants')->insertGetId(['name' => 'Phase3B1/B']);
        $firstId = DB::table('product_variants')->insertGetId([
            'product_id' => $product->id,
            'variant_id' => $variantA,
            'item_code' => 'P3B1-A',
            'additional_price' => 0,
            'qty' => 5,
            'position' => 1,
        ]);
        $secondId = DB::table('product_variants')->insertGetId([
            'product_id' => $product->id,
            'variant_id' => $variantB,
            'item_code' => 'P3B1-B',
            'additional_price' => 5,
            'qty' => 5,
            'position' => 2,
        ]);
        $this->stock($product->id, $variantA, 5);
        $this->stock($product->id, $variantB, 5);

        return [$firstId, $secondId];
    }

    private function stock(int $productId, ?int $variantDefinitionId, float $quantity): void
    {
        DB::table('product_warehouse')->where([
            'product_id' => $productId,
            'warehouse_id' => $this->warehouseId,
        ])->where('variant_id', $variantDefinitionId)->whereNull('product_batch_id')->delete();
        DB::table('product_warehouse')->insert([
            'product_id' => $productId,
            'warehouse_id' => $this->warehouseId,
            'variant_id' => $variantDefinitionId,
            'product_batch_id' => null,
            'qty' => $quantity,
        ]);
    }
}
