<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Ecommerce\Entities\Page;
use Modules\Ecommerce\Entities\PageWidgets;
use Modules\Ecommerce\Http\Controllers\PageWidgetController;
use Modules\Ecommerce\Services\HomepageCacheService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class HomepageWidgetMutationTest extends TestCase
{
    use DatabaseTransactions;

    private Page $page;
    private HomepageCacheService $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->page = new Page([
            'page_name' => 'Phase 2A Test Home',
            'slug' => 'phase-2a-test-' . uniqid(),
            'template' => 'home',
            'status' => 1,
        ]);
        $this->page->id = random_int(800000, 899999);
        $this->page->save();
        $this->cache = app(HomepageCacheService::class);
    }

    public function test_activation_preserves_widget_settings_and_order_and_invalidates_cache(): void
    {
        $widget = $this->widget('hero-video-widget', 10, ['title' => 'Keep me']);
        Cache::put($this->cache->widgetListKey($this->page->id), 'stale');
        Cache::put($this->cache->widgetDataKey($this->page->id, 'salepro.test'), 'stale');

        $request = Request::create('/activation', 'POST', [
            'page_id' => $this->page->id,
            'is_active' => false,
        ], [], [], ['HTTP_HOST' => 'salepro.test']);

        app(PageWidgetController::class)->activation($request, $widget, $this->cache);
        $widget->refresh();

        $this->assertFalse($widget->is_active);
        $this->assertSame(10, $widget->order);
        $this->assertSame('Keep me', $widget->settings['title']);
        $this->assertFalse(Cache::has($this->cache->widgetListKey($this->page->id)));
        $this->assertFalse(Cache::has($this->cache->widgetDataKey($this->page->id, 'salepro.test')));
    }

    public function test_order_endpoint_writes_unique_sequential_positions(): void
    {
        $first = $this->widget('text-widget', 10);
        $second = $this->widget('hero-video-widget', 2);
        $third = $this->widget('recent-blog-widget', 2);

        $request = Request::create('/order', 'POST', [
            'page_id' => $this->page->id,
            'widget_ids' => [$third->id, $first->id, $second->id],
        ]);

        app(PageWidgetController::class)->order($request, $this->cache);

        $this->assertSame(1, $third->fresh()->order);
        $this->assertSame(2, $first->fresh()->order);
        $this->assertSame(3, $second->fresh()->order);
    }

    public function test_order_endpoint_rejects_cross_page_widgets(): void
    {
        $widget = $this->widget('text-widget', 1);
        $otherPage = Page::create([
            'page_name' => 'Other',
            'slug' => 'other-' . uniqid(),
            'template' => 'home',
        ]);
        $foreign = PageWidgets::create([
            'page_id' => $otherPage->id,
            'name' => 'text-widget',
            'order' => 1,
        ]);

        $request = Request::create('/order', 'POST', [
            'page_id' => $this->page->id,
            'widget_ids' => [$widget->id, $foreign->id],
        ]);

        $this->expectException(HttpException::class);
        app(PageWidgetController::class)->order($request, $this->cache);
    }

    public function test_numeric_and_duplicate_ordering_is_deterministic(): void
    {
        $ten = $this->widget('text-widget', 10);
        $twoA = $this->widget('hero-video-widget', 2);
        $twoB = $this->widget('recent-blog-widget', 2);

        $ids = PageWidgets::where('page_id', $this->page->id)
            ->orderByRaw('CAST(`order` AS UNSIGNED) ASC')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([$twoA->id, $twoB->id, $ten->id], $ids);
    }

    public function test_create_update_and_delete_each_invalidate_the_widget_cache(): void
    {
        $key = $this->cache->widgetListKey($this->page->id);
        Cache::put($key, 'stale');

        $storeRequest = Request::create('/widgets', 'POST', [
            'page_id' => $this->page->id,
            'widget_name' => 'text-widget',
            'order' => 1,
            'text_title' => 'Created',
        ], [], [], ['HTTP_HOST' => request()->getHost()]);
        $widgetId = app(PageWidgetController::class)->store($storeRequest, $this->cache);
        $this->assertFalse(Cache::has($key));

        Cache::put($key, 'stale');
        $updateRequest = Request::create('/widgets', 'POST', [
            'id' => $widgetId,
            'page_id' => $this->page->id,
            'widget_name' => 'text-widget',
            'order' => 1,
            'text_title' => 'Updated',
        ], [], [], ['HTTP_HOST' => request()->getHost()]);
        app(PageWidgetController::class)->update($updateRequest, $this->cache);
        $this->assertFalse(Cache::has($key));
        $this->assertSame('Updated', PageWidgets::findOrFail($widgetId)->text_title);

        Cache::put($key, 'stale');
        config(['app.user_verified' => true]);
        $deleteRequest = Request::create('/widgets', 'DELETE', [
            'page_id' => $this->page->id,
        ], [], [], ['HTTP_HOST' => request()->getHost()]);
        app(PageWidgetController::class)->destroy(
            $deleteRequest,
            PageWidgets::findOrFail($widgetId),
            $this->cache
        );
        $this->assertFalse(Cache::has($key));
        $this->assertDatabaseMissing('page_widgets', ['id' => $widgetId]);
    }

    public function test_inactive_advanced_widgets_are_excluded_from_frontend_query(): void
    {
        $active = $this->widget('hero-video-widget', 1, ['title' => 'Active']);
        $inactive = $this->widget('recent-blog-widget', 2, ['title' => 'Inactive']);
        $inactive->update(['is_active' => false]);

        $ids = PageWidgets::where('page_id', $this->page->id)
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        $this->assertContains($active->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
        $this->assertSame(2, $inactive->fresh()->order);
    }

    private function widget(string $name, int $order, array $settings = []): PageWidgets
    {
        return PageWidgets::create([
            'page_id' => $this->page->id,
            'name' => $name,
            'order' => $order,
            'is_active' => true,
            'settings' => $settings,
        ]);
    }
}
