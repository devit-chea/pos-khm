<?php

namespace Modules\Woocommerce\Contracts;

interface WooCommerceClient
{
    public function get(string $resource, array $parameters = []): mixed;
    public function post(string $resource, array $payload): mixed;
}
