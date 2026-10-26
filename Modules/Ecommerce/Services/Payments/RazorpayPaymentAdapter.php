<?php

namespace Modules\Ecommerce\Services\Payments;

use Illuminate\Validation\ValidationException;
use Modules\Ecommerce\Entities\EcommercePaymentAttempt;
use Modules\Ecommerce\Services\Payments\Contracts\PaymentProviderAdapter;
use Razorpay\Api\Api;

class RazorpayPaymentAdapter implements PaymentProviderAdapter
{
    public function __construct(private PaymentProviderCredentials $credentials, private mixed $client = null)
    {
    }

    public function provider(): string
    {
        return 'razorpay';
    }

    public function initiate(EcommercePaymentAttempt $attempt): array
    {
        [$client, $credentials] = $this->client();
        $order = $client->order->create([
            'receipt' => $attempt->public_id,
            'amount' => $this->minorAmount($attempt),
            'currency' => $attempt->currency_code,
            'notes' => ['ecommerce_payment_attempt' => $attempt->public_id],
        ]);

        return [
            'provider_reference' => $order['id'],
            'order_id' => $order['id'],
            'key' => $credentials['key'] ?? $credentials['key_id'] ?? $credentials['public_key'] ?? null,
            'amount' => $this->minorAmount($attempt),
            'currency' => $attempt->currency_code,
        ];
    }

    public function verify(EcommercePaymentAttempt $attempt, array $payload): array
    {
        foreach (['razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature'] as $key) {
            if (empty($payload[$key])) {
                throw ValidationException::withMessages(['payment' => __('db.Razorpay verification data is incomplete')]);
            }
        }
        if ($attempt->provider_reference !== $payload['razorpay_order_id']) {
            throw ValidationException::withMessages(['payment' => __('db.Invalid Razorpay order reference')]);
        }
        [$client] = $this->client();
        try {
            $client->utility->verifyPaymentSignature([
                'razorpay_order_id' => $payload['razorpay_order_id'],
                'razorpay_payment_id' => $payload['razorpay_payment_id'],
                'razorpay_signature' => $payload['razorpay_signature'],
            ]);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['payment' => __('db.Invalid Razorpay signature')]);
        }
        $payment = $client->payment->fetch($payload['razorpay_payment_id']);
        if ($payment['status'] !== 'captured' || $payment['order_id'] !== $attempt->provider_reference) {
            throw ValidationException::withMessages(['payment' => __('db.Razorpay payment is not captured')]);
        }

        return [
            'transaction_id' => $payment['id'],
            'amount' => ((int) $payment['amount']) / (10 ** $attempt->minor_unit),
            'currency' => strtoupper($payment['currency']),
            'evidence' => ['verified' => true, 'provider' => 'razorpay', 'status' => $payment['status']],
        ];
    }

    private function client(): array
    {
        $credentials = $this->credentials->get('Razorpay');
        $key = $credentials['key'] ?? $credentials['key_id'] ?? $credentials['public_key'] ?? null;
        $secret = $credentials['secret'] ?? $credentials['secret_key'] ?? null;
        if (!$key || !$secret) {
            throw ValidationException::withMessages(['payment_mode' => __('db.Razorpay credentials are incomplete')]);
        }
        return [$this->client ?? new Api($key, $secret), $credentials];
    }

    private function minorAmount(EcommercePaymentAttempt $attempt): int
    {
        return (int) round((float) $attempt->provider_amount * (10 ** $attempt->minor_unit));
    }
}
