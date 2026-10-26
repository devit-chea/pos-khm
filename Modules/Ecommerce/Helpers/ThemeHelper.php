<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Services\DemoStorefrontProfile;
use Modules\Ecommerce\Services\EffectiveStorefrontTheme;
use Modules\Ecommerce\Services\ThemeRegistry;
use Modules\Ecommerce\Services\StorefrontCacheKey;

if (!function_exists('theme_view')) {
    /**
     * Resolves the view from the active theme, falling back to the default frontend view.
     *
     * @param string $viewName The base name of the view (e.g., 'home', 'product-details', 'shop')
     * @param array $data Data to pass to the view
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    function theme_view($viewName, $data = [])
    {
        $ecommerce_setting = Cache::rememberForever(StorefrontCacheKey::for('setting'), function () {
            return DB::table('ecommerce_settings')->first();
        });

        $effectiveTheme = app(EffectiveStorefrontTheme::class);
        $context = $effectiveTheme->current();
        if (!$context) {
            $context = $effectiveTheme->resolve($ecommerce_setting->theme ?? 'default');
            $effectiveTheme->remember($context);
        }

        $view = app(ThemeRegistry::class)->view($context['theme'], $viewName);

        return view($view, $data);
    }
}

if (!function_exists('is_salepro_demo')) {
    function is_salepro_demo(): bool
    {
        return app(DemoStorefrontProfile::class)->enabled();
    }
}
