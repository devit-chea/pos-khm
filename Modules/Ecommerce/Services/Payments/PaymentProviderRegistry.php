<?php

namespace Modules\Ecommerce\Services\Payments;

use Illuminate\Validation\ValidationException;
use Modules\Ecommerce\Services\Payments\Contracts\PaymentProviderAdapter;

class PaymentProviderRegistry
{
    public function adapter(string $provider): PaymentProviderAdapter
    {
        return match (strtolower($provider)) {
            'stripe' => app(StripePaymentAdapter::class),
            'paypal' => app(PayPalPaymentAdapter::class),
            'razorpay' => app(RazorpayPaymentAdapter::class),
            default => throw ValidationException::withMessages(['payment_mode' => __('db.Payment provider is unavailable')]),
        };
    }
}
