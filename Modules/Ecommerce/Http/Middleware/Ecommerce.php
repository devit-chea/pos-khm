<?php

namespace Modules\Ecommerce\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use DB;
use Cache;
use Auth;
use Modules\Ecommerce\Services\StorefrontCartService;
use Modules\Ecommerce\Services\DemoStorefrontProfile;
use Modules\Ecommerce\Services\ThemeRegistry;
use Modules\Ecommerce\Services\EffectiveStorefrontTheme;
use Modules\Ecommerce\Services\StorefrontCacheKey;
use App\Services\ModuleAccessService;

class Ecommerce
{
    public function __construct(
        private StorefrontCartService $storefrontCart,
        private ModuleAccessService $moduleAccess,
        private DemoStorefrontProfile $demoProfile,
        private ThemeRegistry $themes,
        private EffectiveStorefrontTheme $effectiveTheme
    )
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $storeCachePrefix = 'ecommerce.' . strtolower($request->getHost());
        $general_setting =  Cache::remember('general_setting', 60*60*24*365, function () {
            return DB::table('general_settings')->select('site_logo','expiry_date','developed_by', 'currency_position', 'decimal')->latest()->first();
        });

        if($this->moduleAccess->isOperational('ecommerce')) {
            if (session()->has('cart')) {
                $this->storefrontCart->current();
            }

            if(auth()->user() && auth()->user()->role_id == 5){
                $customer = DB::table('customers')->select('id','user_id','wishlist')->where('user_id', Auth::id())->first();
                if(isset($customer->wishlist)){
                    $wishlist = $customer->wishlist;
                    $wishlist_count = (count(explode(',',$customer->wishlist)) - 1);
                }else {
                    $wishlist = 0;
                    $wishlist_count = 0;
                }

            }else{
                $wishlist = 0;
                $wishlist_count = 0;
            }

            View::share('wishlist', $wishlist);
            View::share('wishlist_count', $wishlist_count);

            View::share('general_setting', $general_setting);

            $ecommerce_setting = Cache::remember(StorefrontCacheKey::for('setting'), 60*60*24*365, function () {
                return DB::table('ecommerce_settings')->latest()->first();
            });

            // A demo profile is an in-memory/session override only. Never write it
            // back to the company's ecommerce_settings row.
            $themeContext = $this->effectiveTheme->resolve($ecommerce_setting->theme ?? 'default');
            $this->effectiveTheme->remember($themeContext);

            $effectiveEcommerceSetting = clone $ecommerce_setting;
            $effectiveEcommerceSetting->theme = $themeContext['theme'];
            View::share('ecommerce_setting', $effectiveEcommerceSetting);
            View::share('storefront_theme_context', $themeContext);
            View::share('storefront_theme', $themeContext['theme']);
            View::share('storefront_theme_stylesheet', $themeContext['stylesheet']);
            View::share('storefront_header_view', $themeContext['header_view']);
            View::share('demo_storefront_profile', $this->demoProfile->active());
            View::share('demo_storefront_profiles', $this->demoProfile->profiles());

            $social_links = Cache::remember(StorefrontCacheKey::for('social_links'), 60*60*24*365, function () {
                return DB::table('social_links')->orderBy('order','ASC')->get();
            });

            View::share('social_links', $social_links);


            //setting language
            if(isset($_COOKIE['language'])) {
                \App::setLocale($_COOKIE['language']);
            }
            else {
                \App::setLocale('en');
            }

            $currencyCode = session()->get('currency_code');
            $currency = Cache::remember($storeCachePrefix . '.currency.' . ($currencyCode ?: 'default'), 60*60*24, function () use ($currencyCode) {
                if ($currencyCode) {
                    return \App\Models\Currency::where('code', $currencyCode)->first();
                }else {
                    $settingData = DB::table('general_settings')->select('currency')->latest()->first();
                    return \App\Models\Currency::find($settingData->currency);
                }
            });


            View::share('currency', $currency);
            $currencies = Cache::remember($storeCachePrefix . '.currencies.active', 60*60*24, function () {
                return \App\Models\Currency::where('is_active', 1)->get();
            });
            View::share('currencies', $currencies);


            $categories_list = Cache::remember(StorefrontCacheKey::for('categories'), 60*60*24*365, function () {
                return DB::table('categories')->where('is_active', true)->get();
            });
            $categories_list = $this->demoProfile->filterCategories(collect($categories_list));
            View::share('categories_list', $categories_list);

            $navigation = Cache::remember($storeCachePrefix . '.navigation', 60*60, function () {
                return [
                    'topNav' => DB::table('menus')->where('location', 1)->first(),
                    'menu_items' => DB::table('menu_items')->get(),
                ];
            });
            $topNav = $navigation['topNav'];
            $menu_items = $navigation['menu_items'];
            if($topNav && isset($topNav->content)){
                $topNavItems = json_decode($topNav->content);
                $topNavItems = $topNavItems[0];
                foreach ($topNavItems as $menu) {
                    $menu_item = $menu_items->where('id',$menu->id)->first();
                    $menu->title = $menu_item->title;
                    $menu->name = $menu_item->name;
                    $menu->slug = $menu_item->slug;
                    $menu->target = $menu_item->target;
                    $menu->type = $menu_item->type;
                    if (!empty($menu->children[0])) {
                        foreach ($menu->children[0] as $child) {
                            $child_item = $menu_items->where('id',$child->id)->first();
                            $child->title = $child_item->title;
                            $child->name = $child_item->name;
                            $child->slug = $child_item->slug;
                            $child->target = $child_item->target;
                            $child->type = $child_item->type;
                        }
                    }
                }

                $profile = $this->demoProfile->active();
                if ($profile && $profile['slug'] !== 'default') {
                    $allowedSlugs = $categories_list->pluck('slug')->filter()->all();
                    $topNavItems = array_values(array_filter($topNavItems, function ($menu) use ($allowedSlugs) {
                        if (!empty($menu->children[0])) {
                            $menu->children[0] = array_values(array_filter(
                                $menu->children[0],
                                fn ($child) => ($child->type ?? null) !== 'category'
                                    || in_array($child->slug ?? '', $allowedSlugs, true)
                            ));
                        }

                        return ($menu->type ?? null) !== 'category'
                            || in_array($menu->slug ?? '', $allowedSlugs, true);
                    }));
                }
            }else{
                $topNavItems = '';
            }

            $widgets = Cache::remember($storeCachePrefix . '.footer.widgets', 60*60, function () {
                return DB::table('widgets')->whereIn('location', ['footer_top', 'footer'])
                    ->orderBy('order', 'ASC')->get();
            });

            $footer_top_widgets = $widgets->where('location', 'footer_top');

            $footer_widgets = $widgets->where('location', 'footer');

            view()->share([
                'topNavItems' => $topNavItems,
                'menu_items' => $menu_items,
                'footer_widgets' => $footer_widgets,
                'footer_top_widgets' => $footer_top_widgets
            ]);

            return $next($request);
        }
        else {
            if ($request->isMethod('GET') && $request->getPathInfo() === '/') {
                return redirect('/login');
            }

            abort(503, 'The public eCommerce storefront is disabled. Enable it in Ecommerce Settings.');
        }
    }
}
