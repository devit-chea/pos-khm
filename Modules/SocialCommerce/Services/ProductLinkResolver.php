<?php

namespace Modules\SocialCommerce\Services;

use App\Models\Product;
use Modules\SocialCommerce\Entities\SocialCommerceSetting;

class ProductLinkResolver
{
    /**
     * Resolve the best available public URL for a product.
     *
     * @param Product $product
     * @return string
     */
    public function resolve(Product $product): string
    {
        $settings = SocialCommerceSetting::firstOrCreate([]);

        if ($settings->link_target === 'ecommerce') {
            $ecommerceUrl = app(SocialCommerceStorefrontCapability::class)->getEcommerceUrl($product);

            if ($ecommerceUrl) {
                return $ecommerceUrl;
            }
        }

        return route('socialcommerce.public.show', $product->id);
    }
}
