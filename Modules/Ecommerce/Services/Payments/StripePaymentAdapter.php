<?php

namespace Modules\Ecommerce\Services\Payments;

use Illuminate\Validation\ValidationException;
use Modules\Ecommerce\Entities\EcommercePaymentAttempt;
use Modules\Ecommerce\Services\Payments\Contracts\PaymentProviderAdapter;
use Stripe\StripeClient;

class StripePaymentAdapter implements PaymentProviderAdapter
{
    public function __construct(private PaymentProviderCredentials $credentials, private mixed $client = null)
    {
    }

    public function provider(): string
    {
        return 'stripe';
    }

    public function initiate(EcommercePaymentAttempt $attempt): array
    {
        $credentials = $this->credentials->get('Stripe');
        $client = $this->client ?? new StripeClient($this->secret($credentials));
        $intent = $client->paymentIntents->create([
            'amount' => $this->minorAmount($attempt),
            'currency' => strtolower($attempt->currency_code),
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => ['ecommerce_payment_attempt' => $attempt->public_id],
        ], ['idempotency_key' => $attempt->idempotency_key]);

        return [
            'provider_reference' => $intent->id,
            'client_secret' => $intent->client_secret,
            'publishable_key' => $credentials['publishable_key'] ?? $credentials['public_key'] ?? null,
        ];
    }

    public function verify(EcommercePaymentAttempt $attempt, array $payload): array
    {
        $id = (string) ($payload['payment_intent_id'] ?? '');
        if ($id === '' || ($attempt->provider_reference && $attempt->provider_reference !== $id)) {
            throw ValidationException::withMessages(['payment' => __('db.Invalid Stripe payment reference')]);
        }
        $credentials = $this->credentials->get('Stripe');
        $client = $this->client ?? new StripeClient($this->secret($credentials));
        $intent = $client->paymentIntents->retrieve($id, []);
        if ($intent->status !== 'succeeded' ||
            (string) ($intent->metadata->ecommerce_payment_attempt ?? '') !== $attempt->public_id) {
            throw ValidationException::withMessages(['payment' => __('db.Stripe payment is not verified')]);
        }

        return [
            'transaction_id' => $intent->id,
            'amount' => ((int) ($intent->amount_received ?: $intent->amount)) / (10 ** $attempt->minor_unit),
            'currency' => strtoupper($intent->currency),
            'evidence' => ['verified' => true, 'provider' => 'stripe', 'status' => $intent->status],
        ];
    }

    private function secret(array $credentials): string
    {
        $secret = $credentials['secret_key'] ?? $credentials['private_key'] ?? $credentials['secret'] ?? null;
        if (!$secret) {
            throw ValidationException::withMessages(['payment_mode' => __('db.Stripe credentials are incomplete')]);
        }
        return $secret;
    }

    private function minorAmount(EcommercePaymentAttempt $attempt): int
    {
        return (int) round((float) $attempt->provider_amount * (10 ** $attempt->minor_unit));
    }
}
