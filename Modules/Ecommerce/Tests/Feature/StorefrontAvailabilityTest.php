<?php

namespace Modules\Ecommerce\Tests\Feature;

use App\Models\Currency;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Ecommerce\Services\StorefrontAvailabilityService;
use Modules\Ecommerce\Services\StorefrontCartService;
use Tests\TestCase;

class StorefrontAvailabilityTest extends TestCase
{
    use DatabaseTransactions;

    private int $warehouseId;
    private StorefrontAvailabilityService $availability;
    private StorefrontCartService $cart;

    protected function setUp(): void
    {
        parent::setUp();
        session()->forget(['cart', 'total_qty', 'subTotal', 'currency_code', 'currency_rate']);
        Currency::updateOrCreate(['code' => 'USD'], [
            'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1, 'is_active' => 1,
        ]);
        $this->warehouseId = (int) DB::table('warehouses')->value('id');
        $settingsId = (int) DB::table('ecommerce_settings')->latest('id')->value('id');
        DB::table('ecommerce_settings')->where('id', $settingsId)->update([
            'warehouse_id' => $this->warehouseId,
        ]);
        $this->availability = app(StorefrontAvailabilityService::class);
        $this->cart = app(StorefrontCartService::class);
    }

    public function test_in_stock_standard_product_uses_configured_warehouse_and_exposes_cart_action(): void
    {
        $product = $this->standardProduct(10);
        $this->stock($product->id, null, 3);

        $availability = $this->availability->forProduct($product);
        $html = $this->renderActions($product);

        $this->assertTrue($availability['available']);
        $this->assertSame(3.0, $availability['available_qty']);
        $this->assertStringContainsString('add-to-cart', $html);
        $this->assertStringContainsString('max="3"', $html);
        $this->assertSame(1.0, $this->cart->add($product->id, null, 1)['total_qty']);
    }

    public function test_zero_stock_standard_product_has_no_quantity_or_ajax_cart_control(): void
    {
        $product = $this->standardProduct(10);
        $this->stock($product->id, null, 0);

        $availability = $this->availability->forProduct($product);
        $html = $this->renderActions($product);

        $this->assertFalse($availability['available']);
        $this->assertSame(StorefrontAvailabilityService::REASON_OUT_OF_STOCK, $availability['reason']);
        $this->assertStringContainsString(__('db.Out of stock'), $html);
        $this->assertStringNotContainsString('add-to-cart', $html);
        $this->assertStringNotContainsString('name="qty"', $html);
        $this->expectException(InvalidArgumentException::class);
        $this->cart->add($product->id, null, 1);
    }

    public function test_variant_without_storefront_warehouse_stock_is_out_of_stock(): void
    {
        $product = $this->standardProduct(10, true);
        $definitionId = DB::table('variants')->insertGetId(['name' => 'Availability/Zero']);
        $productVariantId = DB::table('product_variants')->insertGetId([
            'product_id' => $product->id,
            'variant_id' => $definitionId,
            'item_code' => 'AVAIL-ZERO-' . uniqid(),
            'additional_price' => 0,
            'qty' => 8,
            'position' => 1,
        ]);
        $this->stock($product->id, $definitionId, 0);

        $selected = $this->availability->forProduct($product, $productVariantId);
        $aggregate = $this->availability->forProduct($product);
        $html = $this->renderActions($product);

        $this->assertFalse($selected['available']);
        $this->assertFalse($aggregate['available']);
        $this->assertSame(StorefrontAvailabilityService::REASON_OUT_OF_STOCK, $aggregate['reason']);
        $this->assertStringContainsString(__('db.Out of stock'), $html);
        $this->assertStringNotContainsString('add-to-cart', $html);
    }

    public function test_imei_restriction_remains_distinct_from_zero_stock(): void
    {
        $product = $this->standardProduct(10);
        $product->update(['is_imei' => 1]);
        $product = $product->fresh();
        $this->stock($product->id, null, 10);

        $availability = $this->availability->forProduct($product);
        $html = $this->renderActions($product);

        $this->assertFalse($availability['available']);
        $this->assertSame(StorefrontAvailabilityService::REASON_IMEI_UNSUPPORTED, $availability['reason']);
        $this->assertStringContainsString(__('db.Ecommerce product unavailable'), $html);
        $this->assertStringNotContainsString(__('db.Out of stock'), $html);
        $this->assertStringNotContainsString('add-to-cart', $html);
    }

    public function test_all_interactive_card_surfaces_use_the_shared_availability_contract(): void
    {
        foreach (['product-template.blade.php', 'product-template-search.blade.php'] as $template) {
            $source = file_get_contents(module_path('Ecommerce', 'Resources/views/frontend/includes/' . $template));
            $this->assertStringContainsString('includes.product-purchase-actions', $source);
            $this->assertStringNotContainsString('$product->qty > 0', $source);
        }

        $recent = file_get_contents(module_path('Ecommerce', 'Resources/views/frontend/includes/recently-viewed-products.blade.php'));
        $wishlist = file_get_contents(module_path('Ecommerce', 'Resources/views/frontend/wishlist.blade.php'));
        $layout = file_get_contents(module_path('Ecommerce', 'Resources/views/frontend/layout/main.blade.php'));

        $this->assertStringContainsString('includes.product-template', $recent);
        $this->assertStringContainsString('StorefrontAvailabilityService', $recent);
        $this->assertSame(2, substr_count($wishlist, 'includes.product-purchase-actions'));
        $this->assertStringNotContainsString('value.in_stock', $layout);
        $this->assertStringNotContainsString('response.in_stock', $layout);
        $this->assertStringContainsString('storefront_availability', $layout);
    }

    private function standardProduct(float $quantity, bool $variant = false): Product
    {
        $product = Product::query()
            ->where('type', 'standard')
            ->where(fn ($query) => $query->whereNull('is_imei')->orWhere('is_imei', 0))
            ->firstOrFail();
        $product->update([
            'is_active' => 1,
            'is_online' => 1,
            'is_imei' => 0,
            'is_variant' => $variant ? 1 : 0,
            'in_stock' => 0,
            'qty' => $quantity,
            'price' => 10,
            'promotion' => 0,
        ]);

        return $product->fresh();
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

    private function renderActions(Product $product): string
    {
        $this->availability->decorate($product);

        return view('ecommerce::frontend.includes.product-purchase-actions', compact('product'))->render();
    }
}
