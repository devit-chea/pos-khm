<?php

namespace Modules\Ecommerce\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ThemeAssetPublishingTest extends TestCase
{
    private function root(string $path): string
    {
        return dirname(__DIR__, 4) . '/' . ltrim($path, '/');
    }

    #[DataProvider('themedStylesheets')]
    public function test_theme_stylesheet_source_and_published_asset_match(string $slug): void
    {
        $source = $this->root("Modules/Ecommerce/assets/css/style-{$slug}.css");
        $public = $this->root("public/modules/ecommerce/css/style-{$slug}.css");

        $this->assertFileExists($source);
        $this->assertFileExists($public);
        $this->assertSame(hash_file('sha256', $source), hash_file('sha256', $public));
    }

    public function test_layout_always_loads_base_before_optional_theme_stylesheet(): void
    {
        $layout = file_get_contents($this->root('Modules/Ecommerce/Resources/views/frontend/layout/main.blade.php'));
        $basePosition = strpos($layout, "modules/ecommerce/css/style.css");
        $themePosition = strpos($layout, "modules/ecommerce/css/' . \$theme_css");

        $this->assertNotFalse($basePosition);
        $this->assertNotFalse($themePosition);
        $this->assertLessThan($themePosition, $basePosition);
    }

    public function test_module_provider_publishes_the_canonical_asset_directory(): void
    {
        $provider = file_get_contents($this->root('Modules/Ecommerce/Providers/EcommerceServiceProvider.php'));

        $this->assertStringContainsString("public_path('modules/' . \$this->moduleNameLower)", $provider);
        $this->assertStringContainsString("\$this->moduleNameLower . '-assets'", $provider);
    }

    public function test_demo_switcher_uses_published_standalone_javascript_only_in_demo_guard(): void
    {
        $source = $this->root('Modules/Ecommerce/assets/js/demo-switcher.js');
        $public = $this->root('public/modules/ecommerce/js/demo-switcher.js');
        $layout = file_get_contents($this->root('Modules/Ecommerce/Resources/views/frontend/layout/main.blade.php'));
        $partial = file_get_contents($this->root('Modules/Ecommerce/Resources/views/frontend/includes/demo-profile-switcher.blade.php'));
        $script = file_get_contents($source);

        $this->assertFileExists($source);
        $this->assertFileExists($public);
        $this->assertSame(hash_file('sha256', $source), hash_file('sha256', $public));
        $this->assertStringContainsString("@if(is_salepro_demo())", $layout);
        $this->assertStringContainsString('modules/ecommerce/js/demo-switcher.js', $layout);
        $this->assertStringNotContainsString('<script>', $partial);
        $this->assertStringContainsString("event.key === 'Escape'", $script);
        $this->assertStringContainsString('demoSwitcherInitialised', $script);
        $this->assertStringContainsString('!switcher.contains(event.target)', $script);
    }

    public function test_category_navigation_does_not_render_missing_icon_alt_text(): void
    {
        $partial = file_get_contents($this->root('Modules/Ecommerce/Resources/views/frontend/layout/header/category-icon.blade.php'));
        $media = file_get_contents($this->root('Modules/Ecommerce/Resources/views/frontend/includes/category-media.blade.php'));
        $header = file_get_contents($this->root('Modules/Ecommerce/Resources/views/frontend/layout/header/header.blade.php'));

        $this->assertStringContainsString("is_file(\$iconPath)", $partial);
        $this->assertStringContainsString('alt=""', $partial);
        $this->assertStringContainsString('category-nav-icon', $partial);
        $this->assertStringContainsString('layout.header.category-icon', $header);
        $this->assertStringNotContainsString('alt="{{ $category->name }}"', $header);
        $this->assertStringContainsString('DemoMediaResolver', $media);
        $this->assertStringContainsString('category-image-fallback', $media);
        $this->assertStringContainsString('alt=""', $media);
    }

    public static function themedStylesheets(): array
    {
        return [
            'electronics' => ['electronics'],
            'grocery' => ['grocery'],
            'fashion' => ['fashion'],
        ];
    }
}
