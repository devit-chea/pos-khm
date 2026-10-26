<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoHomepageComposer
{
    public function __construct(
        private DemoStorefrontProfile $profiles,
        private StorefrontProductService $products,
        private StorefrontPricingService $pricing
    ) {
    }

    public function compose(): ?array
    {
        $profile = $this->profiles->active();
        if (!$profile || $profile['slug'] === 'default') {
            return null;
        }

        $categoryIds = $this->profiles->categoryIdsFor($profile['slug']);
        $categories = $this->profileCategories($profile, $categoryIds);
        $products = $this->profileProducts($categories);
        $products->each(fn ($product) => $this->pricing->decorate($product));

        $banners = $this->banners((array) ($profile['banner_keys'] ?? []));
        $brands = $this->brands($products);

        $content = [
            'hero' => $banners->get('hero'),
            'departments' => $categories,
            'collections' => $categories,
            'categories' => $categories,
            'services' => true,
            'featured' => $products->take(10)->values(),
            'new_arrivals' => $products->skip(10)->take(10)->values(),
            'popular' => $products->take(10)->values(),
            'daily_essentials' => $products->skip(10)->take(10)->values(),
            'shop_the_edit' => $products->skip(10)->take(10)->values(),
            'promo' => $banners->get('promo'),
            'brands' => $brands,
        ];

        $sections = collect((array) ($profile['sections'] ?? []))
            ->filter(fn (string $section) => $this->hasContent($content[$section] ?? null))
            ->values()
            ->all();

        return [
            'profile' => $profile,
            'sections' => $sections,
            'content' => $content,
        ];
    }

    private function profileCategories(array $profile, array $categoryIds): Collection
    {
        if (!$categoryIds) {
            return collect();
        }

        $rootSlugs = array_map('strval', (array) ($profile['category_roots'] ?? []));
        $rootIds = DB::table('categories')
            ->where('is_active', 1)
            ->whereIn('slug', $rootSlugs)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $query = DB::table('categories')
            ->where('is_active', 1)
            ->whereIn('id', $categoryIds)
            ->whereNotIn('id', $rootIds)
            ->orderByDesc('featured')
            ->orderBy('name');

        $categories = $query->limit(10)->get();
        $showcase = array_values((array) ($profile['category_showcase'] ?? []));
        if ($showcase) {
            $position = array_flip($showcase);
            $categories = $categories
                ->filter(fn ($category) => isset($position[$category->slug]))
                ->sortBy(fn ($category) => $position[$category->slug])
                ->values();
        }

        // A legacy dataset may use a single exact root without children.
        return $categories->isNotEmpty()
            ? $categories
            : DB::table('categories')->whereIn('id', $rootIds)->limit(10)->get();
    }

    private function banners(array $keys): Collection
    {
        if (!$keys || !Schema::hasTable('sliders')) {
            return collect();
        }

        $rows = DB::table('sliders')
            ->where('status', 1)
            ->whereIn('title', array_values($keys))
            ->get()
            ->keyBy('title');

        return collect($keys)->map(function (string $title) use ($rows) {
            $banner = $rows->get($title);
            if (!$banner || !$banner->image1) {
                return null;
            }

            $banner->image_url = asset('modules/ecommerce/images/demo/' . basename($banner->image1));

            return $banner;
        })->filter();
    }

    private function profileProducts(Collection $categories): Collection
    {
        if ($categories->isEmpty()) {
            return collect();
        }

        $limit = 20;
        $perCategory = max(2, (int) ceil($limit / $categories->count()));
        $products = collect();

        foreach ($categories as $category) {
            $products = $products->concat(
                $this->products->query()
                    ->with('unit')
                    ->where('category_id', $category->id)
                    ->orderByDesc('featured')
                    ->orderByDesc('id')
                    ->limit($perCategory)
                    ->get()
            );
        }

        $products = $products->unique('id')->take($limit)->values();
        if ($products->count() < $limit) {
            $products = $products->concat(
                $this->products->query()
                    ->with('unit')
                    ->whereNotIn('id', $products->pluck('id'))
                    ->orderByDesc('featured')
                    ->orderByDesc('id')
                    ->limit($limit - $products->count())
                    ->get()
            )->values();
        }

        return $products;
    }

    private function brands(Collection $products): Collection
    {
        $brandIds = $products->pluck('brand_id')->filter()->unique()->values();
        if ($brandIds->isEmpty()) {
            return collect();
        }

        return DB::table('brands')
            ->where('is_active', 1)
            ->whereIn('id', $brandIds)
            ->orderBy('title')
            ->limit(12)
            ->get();
    }

    private function hasContent(mixed $content): bool
    {
        if ($content instanceof Collection) {
            return $content->isNotEmpty();
        }

        return !empty($content);
    }
}
