<?php

namespace Modules\Woocommerce\Services\Legacy;

use Automattic\WooCommerce\Client;
use Modules\Woocommerce\Contracts\WooCommerceClient;
use Modules\Woocommerce\Exceptions\WooCommerceError;
use Throwable;

final class AutomatticWooCommerceClient implements WooCommerceClient
{
    public function __construct(private readonly Client $client) {}

    public function get(string $resource, array $parameters = []): mixed
    {
        try { return $this->client->get($resource, $parameters); }
        catch (Throwable) { throw new WooCommerceError('WooCommerce request failed.'); }
    }

    public function post(string $resource, array $payload): mixed
    {
        try { return $this->client->post($resource, $payload); }
        catch (Throwable) { throw new WooCommerceError('WooCommerce request failed.'); }
    }
}
