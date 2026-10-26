<?php

namespace Modules\Ecommerce\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class StorefrontProductService
{
    public const LIVE_SEARCH_LIMIT = 8;
    public const FULL_SEARCH_PER_PAGE = 12;
    public const MAX_CART_QUANTITY = 999999;
    public const GENERIC_CART_TYPES = ['standard', 'combo', 'service'];

    public function __construct(private DemoStorefrontProfile $demoProfile)
    {
    }

    public function query(): Builder
    {
        return $this->demoProfile->scopeProducts($this->baseQuery());
    }

    public function baseQuery(): Builder
    {
        return Product::query()
            ->where('is_active', 1)
            ->where('is_online', 1);
    }

    public function find(int $id): ?Product
    {
        return $this->query()->whereKey($id)->first();
    }

    public function findForCart(int $id): ?Product
    {
        return $this->baseQuery()->whereKey($id)->first();
    }

    public function supportsGenericCart(Product $product): bool
    {
        return in_array($product->type, self::GENERIC_CART_TYPES, true)
            && !(bool) $product->is_imei;
    }

    public function search(string $term): Builder
    {
        $escaped = $this->escapeLike($term);

        return $this->query()->where(function (Builder $query) use ($escaped) {
            $pattern = '%' . $escaped . '%';

            $query->where('name', 'LIKE', $pattern)
                ->orWhere('tags', 'LIKE', $pattern)
                ->orWhere('code', 'LIKE', $pattern);
        });
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
