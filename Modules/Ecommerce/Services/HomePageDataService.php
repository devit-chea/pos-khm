<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HomePageDataService
{
    public function __construct(
        private HomepageCacheService $homepageCache,
        private StorefrontProductService $storefrontProducts,
        private StorefrontPricingService $storefrontPricing,
        private StorefrontAvailabilityService $storefrontAvailability
    )
    {
    }

    public function hydrate(Collection $widgets, int $pageId): Collection
    {
        $store = request()->getHost();
        $profile = app(DemoStorefrontProfile::class)->selected() ?: 'saved';

        $widgets = Cache::remember($this->homepageCache->widgetDataKey($pageId, $store) . '.' . $profile, 3600, function () use ($widgets) {
            $childrenByParent = null;

            return $widgets->map(function ($widget) use (&$childrenByParent) {
                $settings = is_array($widget->settings) ? $widget->settings : [];
                $widget->settings = $settings;

                $widget->prepared_products = collect();
                $widget->prepared_collections = collect();
                $widget->prepared_brands = collect();
                $widget->prepared_posts = collect();
                $widget->prepared_product = null;

                if ($widget->name === 'product-category-widget') {
                    $childrenByParent ??= $this->categoryChildrenByParent();
                    $widget->prepared_products = $this->products()
                        ->whereIn('category_id', $this->categoryAndDescendantIds(
                            $widget->product_category_id,
                            $childrenByParent
                        ))
                        ->limit((int) $widget->product_category_limit)->get();
                } elseif ($widget->name === 'tab-product-category-widget') {
                    $childrenByParent ??= $this->categoryChildrenByParent();
                    $widget->prepared_products = $this->products()
                        ->whereIn('category_id', $this->categoryAndDescendantIds(
                            $widget->product_category_id,
                            $childrenByParent
                        ))->get();
                } elseif ($widget->name === 'category-slider-widget') {
                    $widget->prepared_categories = DB::table('categories')->where('is_active', true)
                        ->whereIn('id', $this->ids($widget->category_slider_ids))->get();
                    $widget->prepared_categories = app(DemoStorefrontProfile::class)
                        ->filterCategories(collect($widget->prepared_categories));
                } elseif ($widget->name === 'product-collection-widget') {
                    $collection = DB::table('collections')->find($widget->product_collection_id);
                    $widget->prepared_products = $collection
                        ? $this->products()->whereIn('id', $this->ids($collection->products))
                            ->limit((int) $widget->product_collection_limit)->get()
                        : collect();
                } elseif ($widget->name === 'tab-product-collection-widget') {
                    $widget->prepared_collections = DB::table('collections')
                        ->whereIn('id', $this->ids($widget->tab_product_collection_id))->get();
                    $productIds = $widget->prepared_collections
                        ->flatMap(fn ($collection) => $this->ids($collection->products))->unique()->values();
                    $products = $this->products()->whereIn('id', $productIds)->get()->keyBy('id');
                    foreach ($widget->prepared_collections as $collection) {
                        $limit = (int) $widget->tab_product_collection_limit;
                        $collection->prepared_products = collect($this->ids($collection->products))
                            ->map(fn ($id) => $products->get($id))->filter()->take($limit)->values();
                    }
                } elseif ($widget->name === 'brand-slider-widget') {
                    $widget->prepared_brands = DB::table('brands')->where('is_active', true)
                        ->whereIn('id', $this->ids($widget->brand_slider_ids))->get();
                } elseif ($widget->name === 'deal-of-the-day-widget' && !empty($settings['product_id'])) {
                    $widget->prepared_product = $this->products()->find($settings['product_id']);
                } elseif ($widget->name === 'masonry-product-widget') {
                    $query = $this->products();
                    if (!empty($settings['category_id'])) {
                        $query->where('category_id', $settings['category_id']);
                    }
                    $widget->prepared_products = $query->limit((int) ($settings['limit'] ?? 6))->get();
                } elseif ($widget->name === 'recent-blog-widget') {
                    $table = Schema::hasTable('posts') ? 'posts' : (Schema::hasTable('blogs') ? 'blogs' : null);
                    if ($table) {
                        $widget->prepared_posts = DB::table($table)
                            ->latest('id')->limit((int) ($settings['limit'] ?? 3))->get();
                    }
                }

                $widget->prepared_products->each(fn ($product) => $this->storefrontPricing->decorate($product));
                if ($widget->prepared_product) {
                    $this->storefrontPricing->decorate($widget->prepared_product);
                }
                $widget->prepared_collections->each(function ($collection) {
                    collect($collection->prepared_products ?? [])->each(
                        fn ($product) => $this->storefrontPricing->decorate($product)
                    );
                });

                return $widget;
            });
        });

        foreach ($widgets as $widget) {
            $this->storefrontAvailability->decorateMany($widget->prepared_products ?? []);
            if ($widget->prepared_product instanceof \App\Models\Product) {
                $this->storefrontAvailability->decorate($widget->prepared_product);
            }
            foreach ($widget->prepared_collections ?? [] as $collection) {
                $this->storefrontAvailability->decorateMany($collection->prepared_products ?? []);
            }
        }

        return $widgets;
    }

    private function products()
    {
        return $this->storefrontProducts->query();
    }

    private function categoryChildrenByParent(): Collection
    {
        return DB::table('categories')
            ->select('id', 'parent_id')
            ->get()
            ->groupBy('parent_id');
    }

    private function categoryAndDescendantIds($categoryId, Collection $childrenByParent): array
    {
        $rootId = (int) $categoryId;
        if ($rootId <= 0) {
            return [];
        }

        $ids = [$rootId];
        $seen = [$rootId => true];

        for ($index = 0; $index < count($ids); $index++) {
            foreach ($childrenByParent->get($ids[$index], collect()) as $child) {
                $childId = (int) $child->id;
                if (!isset($seen[$childId])) {
                    $seen[$childId] = true;
                    $ids[] = $childId;
                }
            }
        }

        return $ids;
    }

    private function ids(?string $ids): array
    {
        return array_values(array_filter(array_map('intval', explode(',', (string) $ids))));
    }
}
