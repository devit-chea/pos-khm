<?php

namespace Modules\Ecommerce\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Modules\Ecommerce\Entities\EcommercePaymentAttempt;
use Modules\Ecommerce\Services\Payments\Contracts\PaymentProviderAdapter;

class PayPalPaymentAdapter implements PaymentProviderAdapter
{
    public function __construct(private PaymentProviderCredentials $credentials)
    {
    }

    public function provider(): string
    {
        return 'paypal';
    }

    public function initiate(EcommercePaymentAttempt $attempt): array
    {
        [$base, $token, $credentials] = $this->auth();
        $response = Http::withToken($token)
            ->withHeaders(['PayPal-Request-Id' => $attempt->idempotency_key, 'Prefer' => 'return=representation'])
            ->post("{$base}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'custom_id' => $attempt->public_id,
                    'invoice_id' => $attempt->public_id,
                    'amount' => [
                        'currency_code' => $attempt->currency_code,
                        'value' => number_format((float) $attempt->provider_amount, $attempt->minor_unit, '.', ''),
                    ],
                ]],
            ])->throw()->json();

        return [
            'provider_reference' => $response['id'],
            'order_id' => $response['id'],
            'client_id' => $credentials['client_id'],
            'currency' => $attempt->currency_code,
        ];
    }

    public function verify(EcommercePaymentAttempt $attempt, array $payload): array
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        if ($orderId === '' || $attempt->provider_reference !== $orderId) {
            throw ValidationException::withMessages(['payment' => __('db.Invalid PayPal order reference')]);
        }
        [$base, $token] = $this->auth();
        $order = Http::withToken($token)
            ->withHeaders(['PayPal-Request-Id' => $attempt->idempotency_key . ':capture', 'Prefer' => 'return=representation'])
            ->post("{$base}/v2/checkout/orders/{$orderId}/capture", [])
            ->throw()
            ->json();
        $capture = $order['purchase_units'][0]['payments']['captures'][0] ?? null;
        if (($order['status'] ?? null) !== 'COMPLETED' || ($capture['status'] ?? null) !== 'COMPLETED' ||
            ($order['purchase_units'][0]['custom_id'] ?? null) !== $attempt->public_id) {
            throw ValidationException::withMessages(['payment' => __('db.PayPal payment is not completed')]);
        }

        return [
            'transaction_id' => $capture['id'],
            'amount' => (float) $capture['amount']['value'],
            'currency' => strtoupper($capture['amount']['currency_code']),
            'evidence' => ['verified' => true, 'provider' => 'paypal', 'status' => $capture['status'], 'order_id' => $orderId],
        ];
    }

    private function auth(): array
    {
        $credentials = $this->credentials->get('PayPal');
        if (empty($credentials['client_id']) || empty($credentials['client_secret'])) {
            throw ValidationException::withMessages(['payment_mode' => __('db.PayPal credentials are incomplete')]);
        }
        $base = app()->environment('production') ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
        $token = Http::asForm()
            ->withBasicAuth($credentials['client_id'], $credentials['client_secret'])
            ->post("{$base}/v1/oauth2/token", ['grant_type' => 'client_credentials'])
            ->throw()
            ->json('access_token');
        return [$base, $token, $credentials];
    }
}
