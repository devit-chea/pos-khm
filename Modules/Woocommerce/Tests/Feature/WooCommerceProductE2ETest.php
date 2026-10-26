<?php

namespace Modules\Woocommerce\Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Product_Warehouse;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Woocommerce\Contracts\WooCommerceClientFactory;
use Modules\Woocommerce\Models\WoocommerceSetting;
use Modules\Woocommerce\Tests\Fakes\FakeWooCommerceClient;
use Modules\Woocommerce\Tests\Fakes\FakeWooCommerceClientFactory;
use RuntimeException;
use Tests\TestCase;

class WooCommerceProductE2ETest extends TestCase
{
    use DatabaseTransactions;

    private Warehouse $warehouse;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        Product::query()->update(['is_sync_disable' => 1]);
        Category::query()->update(['is_sync_disable' => 1]);
        $this->warehouse = Warehouse::query()->where('is_active', 1)->firstOrFail();
        $this->category = Category::query()->create([
            'name' => 'Woo category '.bin2hex(random_bytes(3)), 'is_active' => 1,
            'woocommerce_category_id' => 501,
        ]);
        $this->setting();
        $this->withoutMiddleware([\App\Http\Middleware\Common::class, \App\Http\Middleware\Active::class]);
        $this->actingAs($this->createIsolatedAdminUser());
    }

    public function test_standard_export_uses_only_configured_warehouse_decimal_stock(): void
    {
        $product = $this->product(['qty' => 999]);
        Product_Warehouse::query()->create(['product_id' => $product->id, 'warehouse_id' => $this->warehouse->id, 'qty' => 2.5]);
        $other = Warehouse::query()->whereKeyNot($this->warehouse->id)->where('is_active', 1)->first();
        if ($other) Product_Warehouse::query()->create(['product_id' => $product->id, 'warehouse_id' => $other->id, 'qty' => 77]);
        $fake = (new FakeWooCommerceClient)->queue('POST', 'products/batch', (object) ['create' => [(object) ['id' => 901, 'images' => []]]]);
        $this->fake($fake);

        $this->post(route('woocommerce.syncProducts', absolute: false))->assertOk()->assertJson(['success' => 1]);

        self::assertSame(901, (int) $product->fresh()->woocommerce_product_id);
        self::assertSame('2.5', $fake->requests[0]['parameters']['create'][0]['stock_quantity']);
        self::assertSame([0], $fake->requestTransactionDepths);
        self::assertSame(2.5, (float) Product_Warehouse::query()->where('product_id', $product->id)->where('warehouse_id', $this->warehouse->id)->value('qty'));
    }

    public function test_zero_stock_and_existing_mapping_replay_are_preserved(): void
    {
        $product = $this->product(['woocommerce_product_id' => 902]);
        Product_Warehouse::query()->create(['product_id' => $product->id, 'warehouse_id' => $this->warehouse->id, 'qty' => 0]);
        $fake = (new FakeWooCommerceClient)->queue('POST', 'products/batch', (object) ['update' => [(object) ['id' => 902]]]);
        $this->fake($fake);

        $this->post(route('woocommerce.syncProducts', absolute: false))->assertOk()->assertJson(['success' => 1]);
        self::assertSame('0', $fake->requests[0]['parameters']['update'][0]['stock_quantity']);
        self::assertSame(902, (int) $product->fresh()->woocommerce_product_id);
        self::assertSame(1, Product::query()->where('woocommerce_product_id', 902)->count());
    }

    public function test_missing_warehouse_row_category_mapping_batch_and_imei_fail_before_remote_request(): void
    {
        foreach ([
            ['label' => 'missing stock', 'attributes' => []],
            ['label' => 'missing category', 'attributes' => ['category_id' => $this->unmappedCategory()->id]],
            ['label' => 'batch', 'attributes' => ['is_batch' => 1]],
            ['label' => 'imei', 'attributes' => ['is_imei' => 1]],
        ] as $case) {
            Product::query()->whereNull('is_sync_disable')->update(['is_sync_disable' => 1]);
            $product = $this->product($case['attributes']);
            if ($case['label'] !== 'missing stock') {
                Product_Warehouse::query()->create(['product_id' => $product->id, 'warehouse_id' => $this->warehouse->id, 'qty' => 3]);
            }
            $fake = new FakeWooCommerceClient;
            $this->fake($fake);
            $this->post(route('woocommerce.syncProducts', absolute: false))->assertOk()->assertJson(['success' => 0]);
            self::assertSame([], $fake->requests, $case['label']);
            self::assertNull($product->fresh()->woocommerce_product_id, $case['label']);
        }
    }

    public function test_remote_failure_or_malformed_response_does_not_persist_mapping(): void
    {
        foreach ([new RuntimeException('secret remote body'), (object) ['create' => []]] as $remoteResult) {
            Product::query()->whereNull('is_sync_disable')->update(['is_sync_disable' => 1]);
            $product = $this->product();
            Product_Warehouse::query()->create(['product_id' => $product->id, 'warehouse_id' => $this->warehouse->id, 'qty' => 3]);
            $fake = (new FakeWooCommerceClient)->queue('POST', 'products/batch', $remoteResult);
            $this->fake($fake);
            $response = $this->post(route('woocommerce.syncProducts', absolute: false))->assertOk()->assertJson(['success' => 0]);
            self::assertNull($product->fresh()->woocommerce_product_id);
            self::assertStringNotContainsString('secret remote body', $response->getContent());
        }
    }

    public function test_missing_configured_warehouse_and_disabled_sync_block_before_remote(): void
    {
        $fake = new FakeWooCommerceClient;
        $this->fake($fake);
        $setting = WoocommerceSetting::query()->latest()->first();
        $setting->update(['warehouse_id' => null]);
        $this->post(route('woocommerce.syncProducts', absolute: false))->assertOk()->assertJson(['success' => 0]);
        $setting->update(['warehouse_id' => $this->warehouse->id, 'sync_enabled' => false]);
        $this->post(route('woocommerce.syncProducts', absolute: false))->assertOk()->assertJson(['success' => 0]);
        self::assertSame([], $fake->requests);
    }

    private function product(array $attributes = []): Product
    {
        $source = Product::query()->firstOrFail();
        $product = $source->replicate();
        $product->fill(array_merge([
            'name' => 'Woo product '.bin2hex(random_bytes(3)), 'code' => 'W'.bin2hex(random_bytes(5)),
            'type' => 'standard', 'category_id' => $this->category->id, 'qty' => 99,
            'is_active' => 1, 'is_sync_disable' => null, 'woocommerce_product_id' => null,
            'woocommerce_media_id' => null, 'is_batch' => 0, 'is_imei' => 0,
        ], $attributes));
        $product->save();
        return $product;
    }

    private function unmappedCategory(): Category
    {
        return Category::query()->create(['name' => 'Unmapped '.bin2hex(random_bytes(3)), 'is_active' => 1]);
    }

    private function setting(): void
    {
        WoocommerceSetting::query()->delete();
        WoocommerceSetting::query()->create([
            'sync_enabled' => true, 'warehouse_id' => $this->warehouse->id,
            'woocomerce_app_url' => 'https://93.184.216.34/store',
            'woocomerce_consumer_key' => 'synthetic-key', 'woocomerce_consumer_secret' => 'synthetic-secret',
            'product_tax_type' => 'exc', 'manage_stock' => 'true', 'stock_status' => 'instock',
        ]);
    }

    private function fake(FakeWooCommerceClient $fake): void
    {
        app()->instance(WooCommerceClientFactory::class, new FakeWooCommerceClientFactory($fake));
    }
}
