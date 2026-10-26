<?php

namespace Modules\Ecommerce\Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class IndustryThemesContractTest extends TestCase
{
    public function test_every_registered_theme_has_a_functional_cart_entry_contract(): void
    {
        $layout = file_get_contents(__DIR__ . '/../../Resources/views/frontend/layout/main.blade.php');
        $this->assertStringContainsString("'X-CSRF-TOKEN'", $layout);

        foreach (['default', 'electronics', 'fashion', 'grocery'] as $theme) {
            $home = file_get_contents(__DIR__ . "/../../Resources/views/frontend/themes/{$theme}/home.blade.php");
            $this->assertStringContainsString(
                $theme === 'default' ? "route('addToCart')" : 'demo-product-actions',
                $home,
                "{$theme} must bind product-card cart actions"
            );
        }

        $minimalProduct = file_get_contents(__DIR__ . '/../../Resources/views/frontend/themes/minimal/product-details.blade.php');
        $minimalCart = file_get_contents(__DIR__ . '/../../Resources/views/frontend/themes/minimal/cart.blade.php');
        $this->assertStringContainsString("route('addToCart')", $minimalProduct);
        $this->assertStringContainsString("route('updateCart')", $minimalCart);
        $this->assertStringContainsString("route('removeFromCart')", $minimalCart);
        $this->assertStringNotContainsString("url('cart/add')", $minimalProduct);
        $this->assertStringNotContainsString("url('cart/update')", $minimalCart);
    }

    public function test_shared_logo_and_category_icon_contracts_preserve_uploaded_media(): void
    {
        $category = new \App\Models\Category([
            'name' => 'Child category',
            'icon' => 'child-icon.png',
        ]);

        $this->assertSame('child-icon.png', $category->icon);

        $logoPartial = file_get_contents(__DIR__ . '/../../Resources/views/frontend/layout/header/storefront-logo.blade.php');
        $css = file_get_contents(__DIR__ . '/../../assets/css/style.css');
        $this->assertStringContainsString('$ecommerce_setting->logo', $logoPartial);
        $this->assertStringContainsString('$general_setting->site_logo', $logoPartial);
        $this->assertStringContainsString('object-fit: contain', $css);
        $this->assertStringContainsString('max-width: 100%', $css);
        $this->assertStringContainsString('overflow-wrap: anywhere', $css);
        $this->assertStringContainsString('white-space: normal', $css);
    }

    private function modulePath(string $path): string
    {
        return dirname(__DIR__, 2) . '/' . ltrim($path, '/');
    }

    #[DataProvider('industryThemeProvider')]
    public function test_industry_theme_manifest_and_assets_are_wired(
        string $slug,
        string $name,
        string $industry,
        string $homeMarker,
        string $cssMarker
    ): void {
        $manifestPath = $this->modulePath("Resources/views/frontend/themes/{$slug}/theme.json");
        $homePath = $this->modulePath("Resources/views/frontend/themes/{$slug}/home.blade.php");
        $cssPath = $this->modulePath("assets/css/style-{$slug}.css");

        $this->assertFileExists($manifestPath);
        $this->assertFileExists($homePath);
        $this->assertFileExists($cssPath);

        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($name, $manifest['name']);
        $this->assertSame($industry, $manifest['industry']);
        $this->assertNotEmpty($manifest['description']);
        $this->assertContains('product-category-widget', $manifest['supported_widgets']);
        $this->assertContains('product-collection-widget', $manifest['supported_widgets']);

        $home = (string) file_get_contents($homePath);
        $this->assertStringContainsString("@extends('ecommerce::frontend.layout.main')", $home);
        $this->assertStringContainsString($homeMarker, $home);
        $this->assertStringContainsString('includes.demo-product-grid', $home);
        $this->assertStringContainsString('includes.demo-product-actions', $home);
        $this->assertStringNotContainsString('\$widgets', $home);

        $css = (string) file_get_contents($cssPath);
        $this->assertStringNotContainsString("@import url('style.css');", $css);
        $this->assertStringContainsString($cssMarker, $css);
    }

    public static function industryThemeProvider(): array
    {
        return [
            'grocery' => ['grocery', 'Grocery Theme', 'grocery', 'grocery-category-grid', '.theme-grocery .single-product-wrapper'],
            'fashion' => ['fashion', 'Fashion Theme', 'fashion', 'fashion-category-grid', '.theme-fashion .single-product-wrapper'],
        ];
    }

    public function test_grocery_uses_theme_specific_header_and_fashion_retains_its_header(): void
    {
        $this->assertFileExists($this->modulePath('Resources/views/frontend/layout/header/header-grocery.blade.php'));
        $this->assertFileExists($this->modulePath('Resources/views/frontend/layout/header/header-fashion.blade.php'));

        $groceryHeader = (string) file_get_contents($this->modulePath('Resources/views/frontend/layout/header/header-grocery.blade.php'));
        $fashionHeader = (string) file_get_contents($this->modulePath('Resources/views/frontend/layout/header/header-fashion.blade.php'));

        $this->assertStringContainsString('grocery-utility-bar', $groceryHeader);
        $this->assertStringContainsString("'storefrontHeaderVariant' => 'grocery'", $groceryHeader);
        $this->assertStringContainsString('fashion-announcement', $fashionHeader);
        $this->assertStringContainsString('My Account', $fashionHeader);
    }

    public function test_non_default_category_menu_is_click_controlled_and_logo_is_centered(): void
    {
        $header = (string) file_get_contents($this->modulePath('Resources/views/frontend/layout/header/header.blade.php'));
        $css = (string) file_get_contents($this->modulePath('assets/css/style.css'));
        $javascript = (string) file_get_contents($this->modulePath('assets/js/main.js'));

        $this->assertStringContainsString('($active_theme ?? \'default\') === \'default\'', $header);
        $this->assertStringContainsString('$categorySidebarOpen ? \'show\' : \'\'', $header);
        $this->assertStringContainsString('body:not(.theme-default) .category-list>ul>.has-dropdown>.category-button', $javascript);
        $this->assertStringContainsString("dropdown.toggleClass('show')", $javascript);
        $this->assertStringContainsString('body:not(.theme-default) .category-list>ul>.has-dropdown:hover>.dropdown.sidebar:not(.show)', $css);
        $this->assertMatchesRegularExpression('/\.logo\s*\{[^}]*align-self:\s*center;[^}]*display:\s*flex;[^}]*align-items:\s*center;/s', $css);
        $this->assertMatchesRegularExpression('/\.logo img\s*\{[^}]*object-fit:\s*contain;/s', $css);
    }

    public function test_industry_theme_asset_prefix_is_valid_compiled_blade(): void
    {
        $compiler = new BladeCompiler(new Filesystem(), sys_get_temp_dir());

        foreach (['electronics', 'fashion', 'grocery'] as $theme) {
            $view = (string) file_get_contents($this->modulePath("Resources/views/frontend/themes/{$theme}/home.blade.php"));
            $compiled = $compiler->compileString($view);

            $this->assertStringNotContainsString(
                '@php $asset_prefix',
                $compiled,
                "{$theme} must compile its asset prefix assignment instead of emitting a literal Blade directive"
            );
            $this->assertStringContainsString('$asset_prefix = !config(', $compiled);
            $this->assertStringContainsString("asset(\$asset_prefix . 'modules/ecommerce/js/homepage-widgets.js')", $compiled);
        }
    }

    public function test_demo_brand_tiles_never_render_an_unvalidated_blank_image(): void
    {
        $view = (string) file_get_contents($this->modulePath('Resources/views/frontend/includes/demo-brand-row.blade.php'));

        $this->assertStringContainsString('brandImageUrl', $view);
        $this->assertStringContainsString('demo-brand-monogram', $view);
        $this->assertStringContainsString('<strong>{{ $brand->title }}</strong>', $view);
    }
}
