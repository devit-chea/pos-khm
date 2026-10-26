<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Ecommerce\Services\DemoStorefrontProfile;

class DemoStorefrontProfileSeeder extends Seeder
{
    public function run(): void
    {
        if (!config('ecommerce.demo_mode', false)) {
            return;
        }

        $profiles = app(DemoStorefrontProfile::class);
        foreach (['electronics', 'fashion', 'grocery'] as $slug) {
            $categoryIds = $profiles->categoryIdsFor($slug);
            if (!$categoryIds) {
                continue;
            }

            $limit = (int) ($profiles->profiles()[$slug]['catalog_limit'] ?? 40);
            $productIds = DB::table('products')
                ->where('is_active', 1)
                ->whereIn('category_id', $categoryIds)
                ->orderByDesc('featured')
                ->orderByDesc('id')
                ->limit($limit)
                ->pluck('id');

            // In demo mode the exact profile tree is deliberately capped. The
            // same records are reused on every run, so no duplicates are made.
            DB::table('products')
                ->where('is_active', 1)
                ->whereIn('category_id', $categoryIds)
                ->update(['is_online' => 0]);
            DB::table('products')->whereIn('id', $productIds)->update(['is_online' => 1]);

            if ($slug === 'grocery') {
                DB::table('products')->whereIn('id', $productIds)->update([
                    'price' => DB::raw('ROUND(price, 2)'),
                ]);
                DB::table('products')->where('name', 'Potato Washed')->update(['price' => 1.20]);
                DB::table('products')->where('name', 'Orange Valencia')->update(['price' => 2.60]);
            }
        }

        if (!Schema::hasTable('sliders')) {
            return;
        }

        $now = now();
        foreach ($this->banners() as $banner) {
            DB::table('sliders')->updateOrInsert(
                ['title' => $banner['title']],
                $banner + ['status' => 1, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    private function banners(): array
    {
        return [
            ['title' => 'demo:electronics:hero', 'link' => url('shop'), 'image1' => 'electronics-hero.png', 'image2' => null, 'image3' => null, 'order' => 0],
            ['title' => 'demo:electronics:promo', 'link' => url('shop'), 'image1' => 'electronics-promo.png', 'image2' => null, 'image3' => null, 'order' => 1],
            ['title' => 'demo:fashion:hero', 'link' => url('shop'), 'image1' => 'fashion-hero.png', 'image2' => null, 'image3' => null, 'order' => 0],
            ['title' => 'demo:fashion:promo', 'link' => url('shop'), 'image1' => 'fashion-promo.png', 'image2' => null, 'image3' => null, 'order' => 1],
            ['title' => 'demo:grocery:hero', 'link' => url('shop'), 'image1' => 'grocery-hero.png', 'image2' => null, 'image3' => null, 'order' => 0],
            ['title' => 'demo:grocery:promo', 'link' => url('shop'), 'image1' => 'grocery-promo.png', 'image2' => null, 'image3' => null, 'order' => 1],
        ];
    }
}
