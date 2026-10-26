<?php

namespace Modules\Ecommerce\Tests\Feature;

use App\Models\Currency;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Http\Controllers\CartController;
use Modules\Ecommerce\Services\StorefrontPricingService;
use Modules\Ecommerce\Services\StorefrontProductService;
use Modules\Ecommerce\Services\StorefrontVariantService;
use Modules\Ecommerce\Services\StorefrontCartService;
use Tests\TestCase;

class StorefrontPricingVariantTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        session()->forget(['currency_code', 'currency_rate']);
        Currency::updateOrCreate(['code' => 'USD'], [
            'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1, 'is_active' => 1,
        ]);
    }

    public function test_promotion_dates_are_inclusive_and_expired_or_future_promotions_do_not_apply(): void
    {
        $service = app(StorefrontPricingService::class);
        $product = (object) [
            'price' => 100,
            'promotion' => 1,
            'promotion_price' => 80,
            'starting_date' => '2026-07-25',
            'last_date' => '2026-07-25',
        ];

        $this->assertSame(80.0, $service->resolve($product, null, CarbonImmutable::parse('2026-07-25'))['selling_price']);
        $this->assertSame(100.0, $service->resolve($product, null, CarbonImmutable::parse('2026-07-24'))['selling_price']);
        $this->assertSame(100.0, $service->resolve($product, null, CarbonImmutable::parse('2026-07-26'))['selling_price']);
    }

    public function test_variant_price_identity_ownership_and_stock_are_authoritative(): void
    {
        $products = app(StorefrontProductService::class);
        $pricing = app(StorefrontPricingService::class);
        $variants = app(StorefrontVariantService::class);
        $controller = app(CartController::class);
        $product = $this->variantProduct();
        $other = $this->variantProduct($product->id);

        $variantId = DB::table('variants')->insertGetId(['name' => 'Phase3A2/Blue']);
        $ownedId = DB::table('product_variants')->insertGetId([
            'product_id' => $product->id,
            'variant_id' => $variantId,
            'item_code' => 'P3A2-BLUE',
            'additional_price' => 12,
            'qty' => 2,
            'position' => 1,
        ]);
        $foreignId = DB::table('product_variants')->insertGetId([
            'product_id' => $other->id,
            'variant_id' => $variantId,
            'item_code' => 'P3A2-FOREIGN',
            'additional_price' => 99,
            'qty' => 20,
            'position' => 1,
        ]);
        $warehouseId = (int) DB::table('warehouses')->value('id');
        DB::table('ecommerce_settings')->where('id', DB::table('ecommerce_settings')->max('id'))
            ->update(['warehouse_id' => $warehouseId]);
        DB::table('product_warehouse')->insert([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseId,
            'variant_id' => $variantId,
            'product_batch_id' => null,
            'qty' => 2,
        ]);

        $foreign = $controller->addToCart(
            Request::create('/add-to-cart', 'POST', ['product_id' => $product->id, 'variant_id' => $foreignId, 'qty' => 1]),
            app(StorefrontCartService::class)
        );
        $this->assertSame(422, $foreign->getStatusCode());

        $arbitraryLabel = $controller->addToCart(
            Request::create('/add-to-cart', 'POST', ['product_id' => $product->id, 'variant' => 'Phase3A2/Blue', 'qty' => 1]),
            app(StorefrontCartService::class)
        );
        $this->assertSame(422, $arbitraryLabel->getStatusCode());

        $response = $controller->addToCart(
            Request::create('/add-to-cart', 'POST', ['product_id' => $product->id, 'variant_id' => $ownedId, 'qty' => 2]),
            app(StorefrontCartService::class)
        );
        $this->assertSame(200, $response->getStatusCode());
        $line = session('cart')[$product->id . ',' . $ownedId];
        $this->assertSame((float) $product->promotion_price + 12.0, $line['unit_price']);
        $this->assertSame($ownedId, $line['variant_id']);
        $this->assertSame('P3A2-BLUE', $line['item_code']);

        $overStock = $controller->addToCart(
            Request::create('/add-to-cart', 'POST', ['product_id' => $product->id, 'variant_id' => $ownedId, 'qty' => 1]),
            app(StorefrontCartService::class)
        );
        $this->assertSame(422, $overStock->getStatusCode());
    }

    public function test_imei_product_is_rejected_by_generic_cart_boundary(): void
    {
        $product = Product::query()->where('type', 'standard')->firstOrFail();
        $product->update(['is_active' => 1, 'is_online' => 1, 'is_imei' => 1, 'is_variant' => 0]);

        $response = app(CartController::class)->addToCart(
            Request::create('/add-to-cart', 'POST', ['product_id' => $product->id, 'qty' => 1]),
            app(StorefrontCartService::class)
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    private function variantProduct(?int $exclude = null): Product
    {
        $query = Product::query()->where('type', 'standard');
        if ($exclude) {
            $query->whereKeyNot($exclude);
        }
        $product = $query->firstOrFail();
        $product->update([
            'is_active' => 1,
            'is_online' => 1,
            'is_imei' => 0,
            'is_variant' => 1,
            'in_stock' => 0,
            'qty' => 2,
            'price' => 100,
            'promotion' => 1,
            'promotion_price' => 75,
            'starting_date' => now()->subDay()->toDateString(),
            'last_date' => now()->addDay()->toDateString(),
        ]);

        return $product->fresh();
    }
}
