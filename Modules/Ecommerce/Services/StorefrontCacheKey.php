<?php

namespace Modules\Ecommerce\Services;

final class StorefrontCacheKey
{
    public static function for(string $name, ?string $host = null): string
    {
        $host = strtolower(trim($host ?: request()->getHost()));

        return 'ecommerce.' . ($host ?: 'default') . '.' . ltrim($name, '.');
    }
}
