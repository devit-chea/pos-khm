<?php

namespace Modules\Ecommerce\Tests\Feature;

use Modules\Ecommerce\Entities\PageWidgets;
use Tests\TestCase;

class HomepageWidgetSettingsTest extends TestCase
{
    /**
     * @dataProvider settingsProvider
     */
    public function test_settings_are_always_normalized_to_an_array($raw, array $expected): void
    {
        $widget = new PageWidgets();
        $widget->setRawAttributes(['settings' => $raw]);

        $this->assertSame($expected, $widget->settings);
    }

    public static function settingsProvider(): array
    {
        return [
            'valid JSON' => ['{"title":"Video"}', ['title' => 'Video']],
            'array' => [['title' => 'Video'], ['title' => 'Video']],
            'object' => [(object) ['title' => 'Video'], ['title' => 'Video']],
            'null' => [null, []],
            'empty string' => ['', []],
            'invalid JSON' => ['{invalid', []],
        ];
    }

    public function test_widget_contract_casts_scalar_fields(): void
    {
        $widget = new PageWidgets();
        $widget->setRawAttributes([
            'id' => 9,
            'page_id' => '4',
            'name' => 'hero-video-widget',
            'order' => '10',
            'is_active' => 1,
            'settings' => null,
        ]);

        $this->assertIsInt($widget->page_id);
        $this->assertIsInt($widget->order);
        $this->assertIsBool($widget->is_active);
        $this->assertIsArray($widget->settings);
    }

    public function test_advanced_partials_are_safe_with_missing_settings_and_related_data(): void
    {
        $widget = new PageWidgets([
            'page_id' => 4,
            'order' => 1,
            'is_active' => true,
            'settings' => null,
        ]);
        $widget->id = 999;
        $widget->prepared_products = collect();
        $widget->prepared_product = null;
        $widget->prepared_posts = collect();

        foreach ([
            'hero-video-widget',
            'masonry-product-widget',
            'deal-of-the-day-widget',
            'recent-blog-widget',
        ] as $name) {
            $widget->name = $name;
            $html = view("ecommerce::frontend.includes.{$name}", [
                'widget' => $widget,
                'settings' => $widget->settings,
            ])->render();

            $this->assertIsString($html);
        }
    }

    public function test_video_settings_reach_safe_embed_markup(): void
    {
        $widget = new PageWidgets([
            'page_id' => 4,
            'name' => 'hero-video-widget',
            'order' => 1,
            'is_active' => true,
            'settings' => [
                'video_url' => 'https://www.youtube.com/watch?v=abc123',
                'title' => 'Foundation Video',
            ],
        ]);

        $html = view('ecommerce::frontend.includes.hero-video-widget', [
            'widget' => $widget,
            'settings' => $widget->settings,
        ])->render();

        $this->assertStringContainsString('youtube.com/embed/abc123', $html);
        $this->assertStringContainsString('Foundation Video', $html);
    }
}
