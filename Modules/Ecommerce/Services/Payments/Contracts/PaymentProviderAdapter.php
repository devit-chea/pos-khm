<?php

namespace Modules\Ecommerce\Services\Payments\Contracts;

use Modules\Ecommerce\Entities\EcommercePaymentAttempt;

interface PaymentProviderAdapter
{
    public function provider(): string;

    public function initiate(EcommercePaymentAttempt $attempt): array;

    public function verify(EcommercePaymentAttempt $attempt, array $payload): array;
}
