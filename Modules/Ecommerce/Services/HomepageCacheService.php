<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomepageCacheService
{
    public function widgetListKey(int $pageId, ?string $host = null): string
    {
        return StorefrontCacheKey::for('home.widgets.list.v2.' . $pageId, $host);
    }

    public function widgetDataKey(int $pageId, ?string $host = null): string
    {
        return StorefrontCacheKey::for('home.widgets.data.v3.' . $pageId, $host);
    }

    public function forgetPage(int $pageId, ?string $host = null): void
    {
        Cache::forget($this->widgetListKey($pageId, $host));
        Cache::forget('home_page_widgets_v2_' . $pageId);
        Cache::forget($this->widgetDataKey($pageId, $host));
        Cache::forget('home_page_widgets_' . $pageId);
        Cache::forget(sprintf(
            'ecommerce.%s.home.widgets.data.%d',
            $host ?: request()->getHost(),
            $pageId
        ));
        Cache::forget(StorefrontCacheKey::for('home.page.' . $pageId, $host));
        Cache::forget('home_page_data_' . $pageId);
    }

    public function forgetConfiguredHomepage(?int $additionalPageId = null, ?string $host = null): void
    {
        $pageIds = array_filter([
            $additionalPageId,
            (int) (DB::table('ecommerce_settings')->value('home_page') ?: 0),
        ]);

        foreach (array_unique($pageIds) as $pageId) {
            $this->forgetPage((int) $pageId, $host);
        }

        $this->forgetSettings($host);
    }

    public function forgetSettings(?string $host = null): void
    {
        Cache::forget(StorefrontCacheKey::for('setting', $host));
        Cache::forget('ecommerce_setting');
    }
}
