<?php

namespace Modules\Woocommerce\Services\Legacy;

use Automattic\WooCommerce\Client;
use Modules\Woocommerce\Contracts\WooCommerceClient;
use Modules\Woocommerce\Contracts\WooCommerceClientFactory;
use Modules\Woocommerce\Models\WoocommerceSetting;
use Modules\Woocommerce\Services\WooCommerceEndpointPolicy;

final class AutomatticWooCommerceClientFactory implements WooCommerceClientFactory
{
    public function __construct(private readonly WooCommerceEndpointPolicy $endpoints) {}

    public function make(WoocommerceSetting $setting): WooCommerceClient
    {
        $endpoint = $this->endpoints->validateAndNormalize((string) $setting->woocomerce_app_url);
        return new AutomatticWooCommerceClient(new Client($endpoint, $setting->woocomerce_consumer_key, $setting->woocomerce_consumer_secret, [
            'wp_api'=>true, 'version'=>'wc/v3', 'timeout'=>15, 'verify_ssl'=>true,
        ]));
    }
}
