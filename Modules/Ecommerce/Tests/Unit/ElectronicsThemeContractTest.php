<?php

namespace Modules\Ecommerce\Tests\Unit;

use PHPUnit\Framework\TestCase;

class ElectronicsThemeContractTest extends TestCase
{
    private function modulePath(string $path): string
    {
        return dirname(__DIR__, 2) . '/' . ltrim($path, '/');
    }

    public function test_electronics_theme_manifest_is_valid_and_identifies_theme(): void
    {
        $manifestPath = $this->modulePath('Resources/views/frontend/themes/electronics/theme.json');

        $this->assertFileExists($manifestPath);
        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Electronics Theme', $manifest['name']);
        $this->assertSame('electronics', $manifest['industry']);
        $this->assertContains('product-category-widget', $manifest['supported_widgets']);
        $this->assertContains('brand-slider-widget', $manifest['supported_widgets']);
    }

    public function test_electronics_home_uses_explicit_profile_composition(): void
    {
        $view = file_get_contents($this->modulePath('Resources/views/frontend/themes/electronics/home.blade.php'));

        $this->assertStringContainsString("@extends('ecommerce::frontend.layout.main')", $view);
        $this->assertStringContainsString('electronics-department-grid', $view);
        $this->assertStringContainsString('includes.demo-product-grid', $view);
        $this->assertStringContainsString('includes.demo-product-actions', $view);
        $this->assertStringNotContainsString('\$widgets', $view);
    }

    public function test_storefront_layout_can_resolve_theme_specific_css_and_header(): void
    {
        $layout = file_get_contents($this->modulePath('Resources/views/frontend/layout/main.blade.php'));

        $this->assertStringContainsString('$storefront_theme_stylesheet', $layout);
        $this->assertStringContainsString('$storefront_header_view', $layout);
        $this->assertStringContainsString('theme-{{ $active_theme }}', $layout);
    }

    public function test_electronics_css_is_a_dedicated_override_layer(): void
    {
        $css = file_get_contents($this->modulePath('assets/css/style-electronics.css'));

        $this->assertStringNotContainsString("@import url('style.css');", $css);
        $this->assertStringContainsString('.theme-electronics .single-product-wrapper', $css);
        $this->assertStringContainsString('.theme-electronics .electronics-department-grid', $css);
    }
}
