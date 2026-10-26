<?php

namespace Modules\Ecommerce\Services;

use App\Models\Account;
use App\Models\Currency;
use App\Models\Payment;
use App\Models\Sale;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Ecommerce\Entities\EcommercePaymentAttempt;

class StorefrontPaymentService
{
    public function __construct(private AccountingService $accounting)
    {
    }
    public const PENDING = 'pending';
    public const PAID = 'paid';
    public const FAILED = 'failed';

    /**
     * Phase 3B.4A only certifies the common boundary. A provider must remain
     * unavailable until its adapter performs server-to-server verification.
     */
    private const CAPABILITIES = [
        'paypal' => ['label' => 'PayPal', 'initiation' => true, 'verified_completion' => true, 'currencies' => ['AUD', 'BRL', 'CAD', 'CNY', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'SGD', 'SEK', 'CHF', 'THB', 'USD']],
        'stripe' => ['label' => 'Stripe', 'initiation' => true, 'verified_completion' => true, 'currencies' => []],
        'razorpay' => ['label' => 'Razorpay', 'initiation' => true, 'verified_completion' => true, 'currencies' => ['INR']],
        'paystack' => ['label' => 'Paystack', 'initiation' => true, 'verified_completion' => false, 'currencies' => ['GHS', 'NGN', 'USD', 'ZAR', 'KES']],
        'mollie' => ['label' => 'Mollie', 'initiation' => true, 'verified_completion' => false, 'currencies' => ['EUR']],
        'xendit' => ['label' => 'Xendit', 'initiation' => true, 'verified_completion' => false, 'currencies' => ['IDR', 'PHP']],
        'mpesa' => ['label' => 'Mpesa', 'initiation' => false, 'verified_completion' => false, 'currencies' => ['KES']],
        'mtnmomo' => ['label' => 'MtnMoMo', 'initiation' => false, 'verified_completion' => false, 'currencies' => []],
        'payhere' => ['label' => 'PayHere', 'initiation' => false, 'verified_completion' => false, 'currencies' => ['LKR']],
        'bkash' => ['label' => 'bkash', 'initiation' => false, 'verified_completion' => false, 'currencies' => ['BDT']],
        'sslcommerz' => ['label' => 'sslcommerz', 'initiation' => false, 'verified_completion' => false, 'currencies' => ['BDT']],
        'pesapal' => ['label' => 'Pesapal', 'initiation' => false, 'verified_completion' => false, 'currencies' => []],
        'moneipoint' => ['label' => 'Moneipoint', 'initiation' => false, 'verified_completion' => false, 'currencies' => ['NGN']],
    ];

    public function capabilities(): array
    {
        return self::CAPABILITIES;
    }

    public function exposedProviderNames(): array
    {
        return collect(self::CAPABILITIES)
            ->filter(fn (array $capability) => $capability['initiation'] && $capability['verified_completion'])
            ->pluck('label')
            ->values()
            ->all();
    }

    public function validateProvider(string $provider, ?string $currencyCode = null): void
    {
        $key = $this->providerKey($provider);
        if ($currencyCode !== null) {
            $this->assertCurrencySupported($key, strtoupper($currencyCode));
        }
    }

    public function start(int $saleId, string $provider): EcommercePaymentAttempt
    {
        app(\App\Services\ZatcaIntegrationService::class)->assertStorefrontCheckoutAvailable();
        $key = $this->providerKey($provider);

        return DB::transaction(function () use ($saleId, $key) {
            $sale = Sale::whereKey($saleId)->lockForUpdate()->firstOrFail();
            $this->assertPayable($sale);

            $currency = Currency::findOrFail($sale->currency_id);
            $code = strtoupper((string) $currency->code);
            $this->assertCurrencySupported($key, $code);
            $idempotencyKey = "ecommerce-sale:{$sale->id}:{$key}";

            return EcommercePaymentAttempt::firstOrCreate(
                ['idempotency_key' => $idempotencyKey],
                [
                    'public_id' => (string) Str::uuid(),
                    'sale_id' => $sale->id,
                    'provider' => $key,
                    'expected_amount' => round((float) $sale->grand_total, 4),
                    'currency_id' => $currency->id,
                    'currency_code' => $code,
                    'exchange_rate' => (float) ($sale->exchange_rate ?: 1),
                    'provider_amount' => round((float) $sale->grand_total, 4),
                    'minor_unit' => $this->minorUnit($code),
                    'state' => self::PENDING,
                ]
            );
        }, 3);
    }

    public function read(string $publicId): EcommercePaymentAttempt
    {
        return EcommercePaymentAttempt::where('public_id', $publicId)->firstOrFail();
    }

    public function recordProviderReference(string $publicId, string $reference): EcommercePaymentAttempt
    {
        return DB::transaction(function () use ($publicId, $reference) {
            $attempt = EcommercePaymentAttempt::where('public_id', $publicId)->lockForUpdate()->firstOrFail();
            if ($attempt->state !== self::PENDING) {
                throw ValidationException::withMessages(['payment' => __('db.Payment attempt is not pending')]);
            }
            if ($attempt->provider_reference && !hash_equals($attempt->provider_reference, $reference)) {
                throw ValidationException::withMessages(['payment' => __('db.Payment provider reference cannot be changed')]);
            }
            if (!$attempt->provider_reference) {
                $attempt->update(['provider_reference' => $reference]);
            }
            return $attempt->fresh();
        }, 3);
    }

    public function completeVerified(
        string $publicId,
        string $providerTransactionId,
        float $verifiedAmount,
        string $verifiedCurrency,
        array $verificationData
    ): EcommercePaymentAttempt {
        app(\App\Services\ZatcaIntegrationService::class)->assertStorefrontCheckoutAvailable();
        if (($verificationData['verified'] ?? false) !== true) {
            throw ValidationException::withMessages(['payment' => __('db.Provider verification is required')]);
        }

        return DB::transaction(function () use (
            $publicId, $providerTransactionId, $verifiedAmount, $verifiedCurrency, $verificationData
        ) {
            $attempt = EcommercePaymentAttempt::where('public_id', $publicId)->lockForUpdate()->firstOrFail();
            $sale = Sale::whereKey($attempt->sale_id)->lockForUpdate()->firstOrFail();

            if ($attempt->state === self::PAID) {
                if (hash_equals((string) $attempt->provider_transaction_id, $providerTransactionId)) {
                    return $attempt;
                }
                throw ValidationException::withMessages(['payment' => __('db.Payment attempt is already complete')]);
            }
            if ($attempt->state !== self::PENDING) {
                throw ValidationException::withMessages(['payment' => __('db.Payment attempt is not pending')]);
            }
            $this->assertPayable($sale);
            if (round($verifiedAmount, 4) !== round((float) $attempt->provider_amount, 4)) {
                throw ValidationException::withMessages(['amount' => __('db.Payment amount does not match order')]);
            }
            if (strtoupper($verifiedCurrency) !== $attempt->currency_code) {
                throw ValidationException::withMessages(['currency' => __('db.Payment currency does not match order')]);
            }

            $account = Account::where('is_default', 1)->firstOrFail();
            $payment = Payment::create([
                'payment_reference' => 'ecom-pay-' . $attempt->public_id,
                'user_id' => $sale->user_id,
                'sale_id' => $sale->id,
                'account_id' => $account->id,
                'amount' => $attempt->expected_amount,
                'currency_id' => $attempt->currency_id,
                'exchange_rate' => $attempt->exchange_rate,
                'change' => 0,
                'paying_method' => self::CAPABILITIES[$attempt->provider]['label'],
            ]);

            $accountingResult = $this->accounting->recordPayment($payment, 'ecommerce_payment_created');
            if (!$accountingResult->success) {
                throw ValidationException::withMessages([
                    'accounting' => $accountingResult->error ?: __('db.Accounting posting failed'),
                ]);
            }

            $attempt->update([
                'payment_id' => $payment->id,
                'provider_transaction_id' => $providerTransactionId,
                'state' => self::PAID,
                'verification_data' => $verificationData,
                'verified_at' => now(),
            ]);
            $sale->update(['paid_amount' => $attempt->expected_amount, 'payment_status' => 4]);

            return $attempt->fresh();
        }, 3);
    }

    public function fail(string $publicId, string $reason): EcommercePaymentAttempt
    {
        return DB::transaction(function () use ($publicId, $reason) {
            $attempt = EcommercePaymentAttempt::where('public_id', $publicId)->lockForUpdate()->firstOrFail();
            if ($attempt->state === self::PAID) {
                throw ValidationException::withMessages(['payment' => __('db.Paid payment cannot be failed')]);
            }
            if ($attempt->state === self::PENDING) {
                $attempt->update([
                    'state' => self::FAILED,
                    'failure_reason' => Str::limit($reason, 1000, ''),
                    'failed_at' => now(),
                ]);
            }
            return $attempt->fresh();
        }, 3);
    }

    public function clearCartAfterVerifiedBrowserReturn(string $publicId): bool
    {
        $attempt = $this->read($publicId);
        if ($attempt->state !== self::PAID || (int) session('ecommerce_payment_sale_id') !== (int) $attempt->sale_id) {
            return false;
        }
        session()->forget(['cart', 'total_qty', 'subTotal', 'ecommerce_payment_attempt', 'ecommerce_payment_sale_id']);
        return true;
    }

    private function providerKey(string $provider): string
    {
        $key = strtolower(preg_replace('/[^a-z0-9]/i', '', $provider));
        if (!isset(self::CAPABILITIES[$key]) || !self::CAPABILITIES[$key]['initiation']) {
            throw ValidationException::withMessages(['payment_mode' => __('db.Payment provider is unavailable')]);
        }
        return $key;
    }

    private function assertCurrencySupported(string $provider, string $currency): void
    {
        $supported = self::CAPABILITIES[$provider]['currencies'];
        if ($supported && !in_array($currency, $supported, true)) {
            throw ValidationException::withMessages(['payment_mode' => __('db.Payment provider does not support order currency')]);
        }
    }

    private function assertPayable(Sale $sale): void
    {
        if ($sale->sale_type !== 'online' || (int) $sale->sale_status === 3 ||
            (int) $sale->payment_status === 4 || (float) $sale->paid_amount > 0 ||
            (float) $sale->grand_total <= 0) {
            throw ValidationException::withMessages(['payment' => __('db.Order is not payable')]);
        }
    }

    private function minorUnit(string $currency): int
    {
        if (in_array($currency, ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'], true)) {
            return 0;
        }
        if (in_array($currency, ['BHD', 'JOD', 'KWD', 'OMR', 'TND'], true)) {
            return 3;
        }
        return 2;
    }
}
