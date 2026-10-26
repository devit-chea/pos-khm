<?php

namespace Modules\Ecommerce\Tests\Unit;

use PHPUnit\Framework\TestCase;

class LegacyHomepageViewContractTest extends TestCase
{
    private function view(string $name): string
    {
        return file_get_contents(__DIR__ . '/../../Resources/views/frontend/includes/' . $name);
    }

    public function test_image_sliders_have_widget_scoped_ids_and_ignore_empty_filenames(): void
    {
        $view = $this->view('image-slider-widget.blade.php');

        $this->assertStringContainsString("array_filter(explode(',', (string) \$widget->slider_images))", $view);
        $this->assertStringContainsString("'widget-slider-' . \$widget->id", $view);
        $this->assertStringNotContainsString('id="widget-slider"', $view);
    }

    public function test_product_category_uses_its_own_slider_settings(): void
    {
        $view = $this->view('product-category-widget.blade.php');

        $this->assertStringContainsString('data-loop="{{$widget->product_category_slider_loop}}"', $view);
        $this->assertStringContainsString('data-autoplay="{{$widget->product_category_slider_autoplay}}"', $view);
    }

    public function test_tabbed_collections_namespace_tab_ids_by_widget_and_collection(): void
    {
        $view = $this->view('tab-product-collection-widget.blade.php');

        $this->assertStringContainsString("'widget-' . \$widget->id . '-collection-' . \$collection->id", $view);
        $this->assertStringNotContainsString('id="myTab"', $view);
        $this->assertStringNotContainsString('id="myTabContent"', $view);
    }

    public function test_carousel_runtime_initializes_each_component_independently(): void
    {
        $script = file_get_contents(__DIR__ . '/../../../../public/modules/ecommerce/js/homepage-widgets.js');

        $this->assertStringContainsString("querySelectorAll('.category-slider-wrapper').forEach", $script);
        $this->assertStringContainsString("querySelectorAll('.product-slider-wrapper').forEach", $script);
        $this->assertStringContainsString("element.closest('section')", $script);
    }

    public function test_carousel_runtime_honors_saved_loop_and_autoplay_values(): void
    {
        $script = file_get_contents(__DIR__ . '/../../../../public/modules/ecommerce/js/homepage-widgets.js');

        $this->assertStringContainsString('loop: enabled(element.dataset.loop)', $script);
        $this->assertStringContainsString('enabled(element.dataset.autoplay)', $script);
    }
}
