<?php

namespace Modules\Woocommerce\Exceptions;

use RuntimeException;

class WebhookRejected extends RuntimeException
{
    public function __construct(public readonly string $failureCode, public readonly int $httpStatus = 400)
    {
        parent::__construct('Webhook request rejected.');
    }
}
