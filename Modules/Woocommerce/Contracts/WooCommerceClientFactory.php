<?php

namespace Modules\Woocommerce\Contracts;

use Modules\Woocommerce\Models\WoocommerceSetting;

interface WooCommerceClientFactory
{
    public function make(WoocommerceSetting $setting): WooCommerceClient;
}
