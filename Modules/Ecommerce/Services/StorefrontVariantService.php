<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StorefrontVariantService
{
    public function forProduct(int $productId): Collection
    {
        return DB::table('product_variants')
            ->join('variants', 'product_variants.variant_id', '=', 'variants.id')
            ->where('product_variants.product_id', $productId)
            ->orderBy('product_variants.position')
            ->orderBy('product_variants.id')
            ->get([
                'product_variants.id',
                'product_variants.product_id',
                'product_variants.variant_id as variant_definition_id',
                'product_variants.item_code',
                'product_variants.additional_price',
                'product_variants.qty',
                'variants.name',
            ]);
    }

    public function resolve(int $productId, int $productVariantId): ?object
    {
        return DB::table('product_variants')
            ->join('variants', 'product_variants.variant_id', '=', 'variants.id')
            ->where('product_variants.product_id', $productId)
            ->where('product_variants.id', $productVariantId)
            ->select([
                'product_variants.id',
                'product_variants.product_id',
                'product_variants.variant_id as variant_definition_id',
                'product_variants.item_code',
                'product_variants.additional_price',
                'product_variants.qty',
                'variants.name',
            ])
            ->first();
    }
}
