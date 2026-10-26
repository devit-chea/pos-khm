<?php

namespace Modules\SocialCommerce\Services;

use App\Models\Product;
use Modules\SocialCommerce\Entities\SocialProductSetting;

class SocialProductImageResolver
{
    public function resolve(Product $product, ?SocialProductSetting $publication = null): string
    {
        $socialImage = $this->filename($publication?->social_image);
        if ($socialImage && is_file(public_path('images/product/social/' . $socialImage))) {
            return asset('images/product/social/' . $socialImage);
        }

        $productImage = $this->filename($product->image);
        foreach (['large', '', 'medium', 'small'] as $directory) {
            $relativePath = 'images/product/' . ($directory ? $directory . '/' : '') . $productImage;
            if ($productImage && is_file(public_path($relativePath))) {
                return asset($relativePath);
            }
        }

        return asset('images/zummXD2dvAtI.png');
    }

    private function filename(mixed $value): ?string
    {
        $filename = basename(trim(explode(',', (string) $value)[0] ?? ''));

        return $filename !== '' ? $filename : null;
    }
}
