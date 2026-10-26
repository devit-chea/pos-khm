<?php

namespace Modules\Ecommerce\Services;

class DemoMediaResolver
{
    public function __construct(private DemoStorefrontProfile $profiles)
    {
    }

    public function productImageUrl(object $product, string $size = 'large'): ?string
    {
        $filename = $this->firstFilename($product->image ?? null);
        foreach (array_unique([$size, 'large', 'medium']) as $directory) {
            if ($filename && is_file(public_path("images/product/{$directory}/{$filename}"))) {
                return url("images/product/{$directory}/{$filename}");
            }
        }

        $slug = $this->activeSlug();
        if (!$slug) {
            return null;
        }

        return $this->demoAsset("{$slug}/products/fallback.png");
    }

    public function productGallery(object $product): array
    {
        $urls = collect(explode(',', (string) ($product->image ?? '')))
            ->map(fn ($image) => basename(trim($image)))
            ->filter()
            ->map(function (string $image) {
                foreach (['xlarge', 'large'] as $directory) {
                    if (is_file(public_path("images/product/{$directory}/{$image}"))) {
                        return url("images/product/{$directory}/{$image}");
                    }
                }

                return null;
            })->filter()->values()->all();

        return $urls ?: array_values(array_filter([$this->productImageUrl($product)]));
    }

    public function categoryImageUrl(object $category, bool $preferLarge = false): ?string
    {
        $icon = $this->firstFilename($category->icon ?? null);
        $large = $this->firstFilename($category->image ?? null);
        $candidates = $preferLarge
            ? [['large', $large], ['icons', $icon]]
            : [['icons', $icon], ['large', $large]];

        foreach ($candidates as [$directory, $filename]) {
            if ($filename && is_file(public_path("images/category/{$directory}/{$filename}"))) {
                return url("images/category/{$directory}/{$filename}");
            }
        }

        if ($this->activeSlug() !== 'fashion') {
            return null;
        }

        $slug = (string) ($category->slug ?? '');
        $asset = match ($slug) {
            'bags-accessories' => 'fashion/collections/bags-accessories.png',
            'footwear' => 'fashion/collections/footwear.png',
            'mens-apparel', 'womens-apparel' => 'fashion/collections/mens-apparel.png',
            'hair-care', 'personal-care', 'skin-care' => 'fashion/collections/beauty-care.png',
            default => null,
        };

        return $asset ? $this->demoAsset($asset) : null;
    }

    public function categoryIcon(object $category, string $default = 'category'): string
    {
        $icons = [
            'audio' => 'headphones', 'computer-accessories' => 'keyboard',
            'home-appliances' => 'kitchen', 'laptops-computers' => 'laptop_mac',
            'mobile-accessories' => 'cable', 'networking' => 'router', 'smartphones' => 'smartphone',
            'bakery' => 'bakery_dining', 'beverages' => 'local_drink',
            'cooking-essentials' => 'skillet', 'dairy-eggs' => 'egg_alt',
            'fresh-produce' => 'nutrition', 'frozen-food' => 'ac_unit',
            'meat-poultry' => 'set_meal', 'rice-grains-pulses' => 'grain',
            'seafood' => 'phishing', 'snacks' => 'fastfood',
        ];

        return $icons[$category->slug ?? ''] ?? $default;
    }

    public function brandImageUrl(object $brand): ?string
    {
        $filename = $this->firstFilename($brand->image ?? null);

        return $filename && is_file(public_path("images/brand/{$filename}"))
            ? url("images/brand/{$filename}")
            : null;
    }

    private function activeSlug(): ?string
    {
        $profile = $this->profiles->active();

        return $profile && $profile['slug'] !== 'default' ? $profile['slug'] : null;
    }

    private function demoAsset(string $path): ?string
    {
        $relative = 'modules/ecommerce/images/demo/' . ltrim($path, '/');

        if (!is_file(public_path($relative)) && !is_file(base_path('Modules/Ecommerce/assets/images/demo/' . ltrim($path, '/')))) {
            return null;
        }

        return rtrim((string) config('app.url'), '/') . '/' . $relative;
    }

    private function firstFilename(mixed $value): ?string
    {
        $filename = basename(trim(explode(',', (string) $value)[0] ?? ''));

        return $filename !== '' ? $filename : null;
    }
}
