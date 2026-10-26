<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Http\Controllers\DemoStorefrontController;
use Modules\Ecommerce\Services\DemoStorefrontProfile;
use Modules\Ecommerce\Services\DemoHomepageComposer;
use Modules\Ecommerce\Services\DemoMediaResolver;
use Modules\Ecommerce\Services\EffectiveStorefrontTheme;
use Modules\Ecommerce\Services\StorefrontProductService;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Modules\Ecommerce\Database\Seeders\DemoStorefrontProfileSeeder;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class EcommerceDemoStorefrontProfileTest extends TestCase
{
    use DatabaseTransactions;

    private DemoStorefrontProfile $profiles;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ecommerce.demo_mode' => true]);
        session()->forget('ecommerce_demo_profile');
        $this->profiles = app(DemoStorefrontProfile::class);

        $this->catalogFixture(810001, 811001, 820001, 'Electronics', 'Smartphones', 'Demo Electronics Phone');
        $this->catalogFixture(810002, 811002, 820002, 'Fashion', 'Women Clothing', 'Demo Fashion Dress');
        $this->catalogFixture(810003, 811003, 820003, 'Food & Grocery', 'Fruits & Vegetables', 'Demo Grocery Apples');
    }

    public function test_session_profile_overrides_theme_without_updating_saved_company_theme(): void
    {
        DB::connection()->flushQueryLog();
        DB::connection()->enableQueryLog();

        $this->assertTrue($this->profiles->select('electronics'));
        $this->assertSame('electronics', $this->profiles->effectiveTheme('fashion'));

        $persistentWrites = collect(DB::connection()->getQueryLog())->filter(function (array $entry) {
            return preg_match('/\b(insert|update|delete)\b.*\becommerce_settings\b/i', $entry['query']) === 1;
        });
        $this->assertCount(0, $persistentWrites);
    }

    public function test_demo_enabled_without_session_profile_uses_saved_company_theme(): void
    {
        session()->forget('ecommerce_demo_profile');

        $context = app(EffectiveStorefrontTheme::class)->resolve('fashion');

        $this->assertNull($context['demo_profile']);
        $this->assertSame('fashion', $context['theme']);
        $this->assertSame('ecommerce::frontend.themes.fashion.home', $context['home_view']);
        $this->assertSame('ecommerce::frontend.layout.header.header-fashion', $context['header_view']);
        $this->assertSame('style-fashion.css', $context['stylesheet']);
    }

    #[DataProvider('themeContexts')]
    public function test_profile_resolves_one_consistent_theme_context(
        string $profile,
        string $homeView,
        string $headerView,
        string $stylesheet
    ): void {
        $this->profiles->select($profile);

        $context = app(EffectiveStorefrontTheme::class)->resolve('default');

        $this->assertSame($profile, $context['demo_profile']);
        $this->assertSame($profile, $context['theme']);
        $this->assertSame($homeView, $context['home_view']);
        $this->assertSame($headerView, $context['header_view']);
        $this->assertSame($stylesheet, $context['stylesheet']);
    }

    #[DataProvider('catalogProfiles')]
    public function test_profile_returns_only_its_catalog(string $profile, string $included, string $excluded): void
    {
        $this->profiles->select($profile);
        $names = app(StorefrontProductService::class)->query()->pluck('name');

        $this->assertTrue($names->contains($included));
        $this->assertFalse($names->contains($excluded));
    }

    public function test_invalid_profile_is_rejected_without_replacing_the_session_selection(): void
    {
        $this->profiles->select('grocery');

        $this->assertFalse($this->profiles->select('invalid-profile'));
        $this->assertSame('grocery', $this->profiles->selected());
    }

    public function test_switch_endpoint_is_not_available_on_verified_installations(): void
    {
        config(['ecommerce.demo_mode' => false]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        app(DemoStorefrontController::class)->switch('electronics', $this->profiles);
    }

    public function test_customer_installation_does_not_render_demo_switcher_guard(): void
    {
        config(['ecommerce.demo_mode' => false]);

        $markup = Blade::render('@if(is_salepro_demo()) demo-switcher @endif');

        $this->assertStringNotContainsString('demo-switcher', $markup);
    }

    public function test_demo_installation_renders_demo_switcher_guard(): void
    {
        config(['ecommerce.demo_mode' => true]);

        $markup = Blade::render('@if(is_salepro_demo()) demo-switcher @endif');

        $this->assertStringContainsString('demo-switcher', $markup);
    }

    public function test_demo_seeder_makes_only_profile_catalog_discoverable_through_normal_online_rule(): void
    {
        DB::table('products')->where('id', 820002)->update(['is_online' => null]);

        app(DemoStorefrontProfileSeeder::class)->run();

        $this->assertSame(1, (int) DB::table('products')->where('id', 820002)->value('is_online'));
    }

    public function test_demo_seeder_is_idempotent_for_catalog_visibility(): void
    {
        $seeder = app(DemoStorefrontProfileSeeder::class);
        $seeder->run();
        $first = DB::table('products')->whereIn('id', [820001, 820002, 820003])->pluck('is_online', 'id')->all();

        $seeder->run();

        $this->assertSame($first, DB::table('products')->whereIn('id', [820001, 820002, 820003])->pluck('is_online', 'id')->all());
    }

    public function test_missing_demo_product_image_resolves_to_existing_profile_media(): void
    {
        DB::table('products')->where('id', 820001)->update(['image' => 'missing-demo-image.png']);
        $this->profiles->select('electronics');
        $product = DB::table('products')->where('id', 820001)->first();

        $url = app(DemoMediaResolver::class)->productImageUrl($product);

        $this->assertStringContainsString('/modules/ecommerce/images/demo/electronics/products/fallback.png', $url);
        $this->assertFileExists(public_path(ltrim(parse_url($url, PHP_URL_PATH), '/')));
    }

    public function test_missing_category_image_uses_existing_fashion_collection_media(): void
    {
        $this->profiles->select('fashion');
        $category = (object) ['slug' => 'bags-accessories', 'image' => null, 'icon' => null];

        $url = app(DemoMediaResolver::class)->categoryImageUrl($category, true);

        $this->assertStringContainsString('/fashion/collections/bags-accessories.png', $url);
        $this->assertFileExists(public_path(ltrim(parse_url($url, PHP_URL_PATH), '/')));
    }

    public function test_production_installation_does_not_inject_demo_media(): void
    {
        DB::table('products')->where('id', 820001)->update(['image' => 'missing-demo-image.png']);
        $this->profiles->select('electronics');
        config(['ecommerce.demo_mode' => false]);

        $this->assertNull(app(DemoMediaResolver::class)->productImageUrl(DB::table('products')->where('id', 820001)->first()));
    }

    public function test_composed_brands_are_isolated_to_profile_products(): void
    {
        DB::table('brands')->insert([
            ['id' => 830001, 'title' => 'Demo Tech Brand', 'is_active' => 1],
            ['id' => 830002, 'title' => 'Demo Fashion Label', 'is_active' => 1],
        ]);
        DB::table('products')->where('id', 820001)->update(['brand_id' => 830001]);
        DB::table('products')->where('id', 820002)->update(['brand_id' => 830002]);
        $this->profiles->select('fashion');

        $brands = app(DemoHomepageComposer::class)->compose()['content']['brands']->pluck('title');

        $this->assertTrue($brands->contains('Demo Fashion Label'));
        $this->assertFalse($brands->contains('Demo Tech Brand'));
    }

    public function test_homepage_composer_omits_sections_that_have_no_content(): void
    {
        $this->profiles->select('fashion');

        $home = app(DemoHomepageComposer::class)->compose();

        $this->assertSame('fashion', $home['profile']['slug']);
        $this->assertContains('collections', $home['sections']);
        $this->assertNotContains('hero', $home['sections']);
        $this->assertNotContains('promo', $home['sections']);
        $this->assertNotContains('new_arrivals', $home['sections']);
        $this->assertSame(['Demo Fashion Dress'], $home['content']['featured']->pluck('name')->all());
    }

    public function test_customer_installation_never_composes_a_demo_homepage(): void
    {
        $this->profiles->select('electronics');
        config(['ecommerce.demo_mode' => false]);

        $this->assertNull(app(DemoHomepageComposer::class)->compose());
    }

    public function test_profile_configuration_uses_explicit_roots_and_keyed_banners(): void
    {
        $profiles = config('ecommerce.demo_profiles');

        $this->assertSame(['electronics'], $profiles['electronics']['category_roots']);
        $this->assertSame(['fashion', 'beauty-personal-care'], $profiles['fashion']['category_roots']);
        $this->assertSame(['food-grocery'], $profiles['grocery']['category_roots']);
        $this->assertSame('demo:fashion:hero', $profiles['fashion']['banner_keys']['hero']);
        $this->assertArrayNotHasKey('category_terms', $profiles['fashion']);
        $this->assertArrayNotHasKey('slider_ids', $profiles['fashion']);
    }

    public function test_switch_redirects_home_and_keeps_selection_in_the_current_session(): void
    {
        $response = app(DemoStorefrontController::class)->switch('fashion', $this->profiles);

        $this->assertSame(url('/'), $response->getTargetUrl());
        $this->assertSame('fashion', session('ecommerce_demo_profile'));
    }

    public function test_default_switch_clears_override_and_restores_saved_theme(): void
    {
        $this->profiles->select('grocery');

        $response = app(DemoStorefrontController::class)->switch('default', $this->profiles);
        $context = app(EffectiveStorefrontTheme::class)->resolve('fashion');

        $this->assertSame(url('/'), $response->getTargetUrl());
        $this->assertFalse(session()->has('ecommerce_demo_profile'));
        $this->assertNull($context['demo_profile']);
        $this->assertSame('fashion', $context['theme']);
    }

    public function test_cart_lookup_remains_on_normal_business_logic_after_profile_switch(): void
    {
        $this->profiles->select('fashion');
        $products = app(StorefrontProductService::class);

        $this->assertNull($products->find(820001));
        $this->assertSame('Demo Electronics Phone', $products->findForCart(820001)?->name);
    }

    public function test_two_visitors_can_hold_different_demo_profiles(): void
    {
        $originalRequest = app('request');
        $visitorA = new Store('visitor-a', new ArraySessionHandler(120));
        $visitorB = new Store('visitor-b', new ArraySessionHandler(120));

        try {
            $requestA = Request::create('/demo/grocery');
            $requestA->setLaravelSession($visitorA);
            app()->instance('request', $requestA);
            $this->assertTrue($this->profiles->select('grocery'));

            $requestB = Request::create('/demo/fashion');
            $requestB->setLaravelSession($visitorB);
            app()->instance('request', $requestB);
            $this->assertTrue($this->profiles->select('fashion'));

            app()->instance('request', $requestA);
            $this->assertSame('grocery', $this->profiles->selected());
            app()->instance('request', $requestB);
            $this->assertSame('fashion', $this->profiles->selected());
        } finally {
            app()->instance('request', $originalRequest);
        }
    }

    public static function catalogProfiles(): array
    {
        return [
            'electronics' => ['electronics', 'Demo Electronics Phone', 'Demo Fashion Dress'],
            'fashion' => ['fashion', 'Demo Fashion Dress', 'Demo Grocery Apples'],
            'grocery' => ['grocery', 'Demo Grocery Apples', 'Demo Electronics Phone'],
        ];
    }

    public static function themeContexts(): array
    {
        return [
            'electronics' => [
                'electronics',
                'ecommerce::frontend.themes.electronics.home',
                'ecommerce::frontend.layout.header.header-electronics',
                'style-electronics.css',
            ],
            'fashion' => [
                'fashion',
                'ecommerce::frontend.themes.fashion.home',
                'ecommerce::frontend.layout.header.header-fashion',
                'style-fashion.css',
            ],
            'grocery' => [
                'grocery',
                'ecommerce::frontend.themes.grocery.home',
                'ecommerce::frontend.layout.header.header-grocery',
                'style-grocery.css',
            ],
        ];
    }

    private function catalogFixture(int $rootId, int $categoryId, int $productId, string $root, string $category, string $product): void
    {
        DB::table('categories')->insert([
            'id' => $rootId,
            'name' => $root,
            'slug' => str($root)->slug(),
            'is_active' => 1,
            'featured' => 1,
        ]);

        DB::table('categories')->insert([
            'id' => $categoryId,
            'name' => $category,
            'slug' => str($category)->slug(),
            'parent_id' => $rootId,
            'is_active' => 1,
            'featured' => 1,
        ]);

        DB::table('products')->insert([
            'id' => $productId,
            'name' => $product,
            'slug' => str($product)->slug(),
            'code' => 'DEMO-' . $productId,
            'type' => 'standard',
            'barcode_symbology' => 'C128',
            'category_id' => $categoryId,
            'unit_id' => 10,
            'purchase_unit_id' => 10,
            'sale_unit_id' => 10,
            'cost' => 5,
            'price' => 10,
            'qty' => 100,
            'is_active' => 1,
            'is_online' => 1,
            'in_stock' => 1,
            'is_imei' => 0,
            'is_variant' => 0,
            'promotion' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
