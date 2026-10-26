<?php

namespace Modules\Woocommerce\Tests\Fakes;

use Modules\Woocommerce\Contracts\WooCommerceClient;
use Modules\Woocommerce\Contracts\WooCommerceClientFactory;
use Modules\Woocommerce\Models\WoocommerceSetting;

final class FakeWooCommerceClientFactory implements WooCommerceClientFactory
{
    /** @var list<int|null> */
    public array $settingIds = [];

    public function __construct(public readonly FakeWooCommerceClient $client) {}

    public function make(WoocommerceSetting $setting): WooCommerceClient
    {
        $this->settingIds[] = $setting->getKey();

        return $this->client;
    }
}
