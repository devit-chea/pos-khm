<?php

namespace Modules\Ecommerce\Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Ecommerce\Entities\PageWidgets;
use Modules\Ecommerce\Services\HomePageDataService;
use Modules\Ecommerce\Services\HomepageCacheService;
use Modules\Ecommerce\Services\StorefrontProductService;
use Tests\TestCase;

class HomepageCategoryDescendantWidgetTest extends TestCase
{
    use DatabaseTransactions;

    private array $categoryIds;
    private array $productIds;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryIds['parent'] = $this->category('Widget Parent');
        $this->categoryIds['child'] = $this->category('Widget Child', $this->categoryIds['parent']);
        $this->categoryIds['grandchild'] = $this->category('Widget Grandchild', $this->categoryIds['child']);
        $this->categoryIds['unrelated'] = $this->category('Widget Unrelated');

        $this->productIds = [
            'parent' => $this->createProduct($this->categoryIds['parent']),
            'child' => $this->createProduct($this->categoryIds['child']),
            'grandchild' => $this->createProduct($this->categoryIds['grandchild']),
            'unrelated' => $this->createProduct($this->categoryIds['unrelated']),
            'inactive' => $this->createProduct($this->categoryIds['child'], false, true),
            'offline' => $this->createProduct($this->categoryIds['grandchild'], true, false),
        ];
    }

    public function test_category_widgets_include_parent_child_and_grandchild_products_only(): void
    {
        $widget = $this->widget('tab-product-category-widget');
        $ids = $this->hydrate($widget)->first()->prepared_products->pluck('id')->all();

        $this->assertContains($this->productIds['parent'], $ids);
        $this->assertContains($this->productIds['child'], $ids);
        $this->assertContains($this->productIds['grandchild'], $ids);
        $this->assertNotContains($this->productIds['unrelated'], $ids);
    }

    public function test_category_widgets_preserve_storefront_visibility_rules(): void
    {
        $widget = $this->widget('tab-product-category-widget');
        $ids = $this->hydrate($widget)->first()->prepared_products->pluck('id')->all();

        $this->assertNotContains($this->productIds['inactive'], $ids);
        $this->assertNotContains($this->productIds['offline'], $ids);
    }

    public function test_category_widget_preserves_existing_product_order_and_limit(): void
    {
        $widget = $this->widget('product-category-widget', 2);
        $actualIds = $this->hydrate($widget)->first()->prepared_products->pluck('id')->all();
        $expectedIds = app(StorefrontProductService::class)->query()
            ->whereIn('category_id', [
                $this->categoryIds['parent'],
                $this->categoryIds['child'],
                $this->categoryIds['grandchild'],
            ])
            ->limit(2)
            ->pluck('id')
            ->all();

        $this->assertSame($expectedIds, $actualIds);
        $this->assertCount(2, $actualIds);
    }

    public function test_versioned_cache_key_ignores_stale_pre_descendant_widget_data(): void
    {
        $widget = $this->widget('tab-product-category-widget');
        $cache = app(HomepageCacheService::class);
        $host = request()->getHost();
        $oldKey = sprintf('ecommerce.%s.home.widgets.data.v2.%d', $host, $widget->page_id);
        $newKey = $cache->widgetDataKey($widget->page_id, $host) . '.saved';
        Cache::put($oldKey, 'stale-v2');
        Cache::forget($newKey);

        $result = app(HomePageDataService::class)->hydrate(collect([$widget]), $widget->page_id);

        $this->assertSame(sprintf('ecommerce.%s.home.widgets.data.v3.%d.saved', $host, $widget->page_id), $newKey);
        $this->assertNotSame('stale-v2', $result);
        $this->assertTrue(Cache::has($newKey));
    }

    private function hydrate(PageWidgets $widget)
    {
        Cache::forget(app(HomepageCacheService::class)->widgetDataKey($widget->page_id, request()->getHost()));

        return app(HomePageDataService::class)->hydrate(collect([$widget]), $widget->page_id);
    }

    private function category(string $name, ?int $parentId = null): int
    {
        return DB::table('categories')->insertGetId([
            'name' => $name.' '.Str::random(8),
            'parent_id' => $parentId,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createProduct(
        int $categoryId,
        bool $active = true,
        bool $online = true
    ): int {
        $code = 'WIDGET-'.Str::upper(Str::random(10));
        $product = Product::create([
            'name' => 'Widget Product '.Str::random(8),
            'code' => $code,
            'type' => 'standard',
            'barcode_symbology' => 'C128',
            'category_id' => $categoryId,
            'unit_id' => 1,
            'purchase_unit_id' => 1,
            'sale_unit_id' => 1,
            'cost' => 10,
            'price' => 20,
            'is_active' => $active ? 1 : 0,
            'is_online' => $online ? 1 : 0,
            'in_stock' => 1,
            'is_variant' => 0,
            'is_imei' => 0,
        ]);

        return $product->id;
    }

    private function widget(string $name, int $limit = 12): PageWidgets
    {
        $widget = new PageWidgets([
            'page_id' => random_int(700000, 799999),
            'name' => $name,
            'product_category_id' => $this->categoryIds['parent'],
            'product_category_limit' => $limit,
            'settings' => [],
        ]);
        $widget->id = random_int(100000, 199999);

        return $widget;
    }
}
