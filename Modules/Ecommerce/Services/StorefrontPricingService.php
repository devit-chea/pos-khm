<?php

namespace Modules\Ecommerce\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class StorefrontPricingService
{
    public function resolve(object $product, ?object $variant = null, ?CarbonInterface $at = null): array
    {
        $date = ($at ?: CarbonImmutable::today())->toDateString();
        $additionalPrice = (float) ($variant->additional_price ?? 0);
        $regularPrice = (float) $product->price + $additionalPrice;
        $promotionPrice = (float) ($product->promotion_price ?? 0) + $additionalPrice;
        $startsOn = $product->starting_date ?? null;
        $endsOn = $product->last_date ?? null;

        $promotionActive = (bool) ($product->promotion ?? false)
            && ($product->promotion_price ?? null) !== null
            && (!$startsOn || $startsOn <= $date)
            && (!$endsOn || $endsOn >= $date);

        return [
            'regular_price' => $regularPrice,
            'selling_price' => $promotionActive ? $promotionPrice : $regularPrice,
            'promotion_active' => $promotionActive,
        ];
    }

    public function decorate(object $product, ?object $variant = null): object
    {
        $price = $this->resolve($product, $variant);
        $product->storefront_regular_price = $price['regular_price'];
        $product->storefront_price = $price['selling_price'];
        $product->storefront_promotion_active = $price['promotion_active'];

        return $product;
    }
}
