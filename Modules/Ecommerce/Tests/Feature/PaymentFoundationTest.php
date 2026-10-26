<?php

namespace Modules\Ecommerce\Tests\Feature;

use App\Models\Currency;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Sale;
use App\Services\AccountingResult;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Modules\Ecommerce\Services\Payments\PaymentProviderCredentials;
use Modules\Ecommerce\Services\Payments\PayPalPaymentAdapter;
use Modules\Ecommerce\Services\Payments\RazorpayPaymentAdapter;
use Modules\Ecommerce\Services\Payments\StripePaymentAdapter;
use Modules\Ecommerce\Services\StorefrontPaymentService;
use Tests\TestCase;

class PaymentFoundationTest extends TestCase
{
    use DatabaseTransactions;

    private StorefrontPaymentService $payments;
    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();
        session()->forget(['cart', 'total_qty', 'subTotal', 'ecommerce_payment_attempt', 'ecommerce_payment_sale_id']);
        $this->payments = app(StorefrontPaymentService::class);
        $this->currency = Currency::updateOrCreate(['code' => 'USD'], [
            'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1, 'is_active' => 1,
        ]);
    }

    public function test_attempt_uses_persisted_sale_amount_currency_and_is_idempotent(): void
    {
        $sale = $this->sale(123.4567);
        session(['grand_total' => 1, 'currency' => 'ZZZ']);

        $first = $this->payments->start($sale->id, 'Stripe');
        $second = $this->payments->start($sale->id, 'stripe');

        $this->assertSame($first->id, $second->id);
        $this->assertSame('123.4567', $first->expected_amount);
        $this->assertSame(strtoupper($this->currency->code), $first->currency_code);
        $this->assertSame(1, DB::table('ecommerce_payment_attempts')->where('sale_id', $sale->id)->count());
        $this->assertSame(0, Payment::where('sale_id', $sale->id)->count());
    }

    public function test_reading_an_attempt_has_no_financial_side_effect(): void
    {
        $sale = $this->sale();
        $attempt = $this->payments->start($sale->id, 'PayPal');

        $read = $this->payments->read($attempt->public_id);

        $this->assertSame($attempt->id, $read->id);
        $this->assertSame(0, Payment::where('sale_id', $sale->id)->count());
        $this->assertSame(1, (int) $sale->fresh()->payment_status);
    }

    public function test_unsupported_provider_and_unsupported_currency_are_rejected(): void
    {
        $sale = $this->sale();
        $this->expectValidationError(fn () => $this->payments->start($sale->id, 'Moneipoint'), 'payment_mode');

        $sale->update(['currency_id' => $this->currencyWithCode('USD')->id]);
        $this->expectValidationError(fn () => $this->payments->start($sale->id, 'Mollie'), 'payment_mode');
    }

    public function test_unverified_or_mismatched_completion_cannot_change_financial_state(): void
    {
        $sale = $this->sale(75);
        $attempt = $this->payments->start($sale->id, 'Stripe');

        $this->expectValidationError(
            fn () => $this->payments->completeVerified($attempt->public_id, 'txn-a', 75, $attempt->currency_code, []),
            'payment'
        );
        $this->expectValidationError(
            fn () => $this->payments->completeVerified($attempt->public_id, 'txn-a', 74, $attempt->currency_code, ['verified' => true]),
            'amount'
        );
        $this->expectValidationError(
            fn () => $this->payments->completeVerified($attempt->public_id, 'txn-a', 75, 'ZZZ', ['verified' => true]),
            'currency'
        );

        $this->assertSame(0, Payment::where('sale_id', $sale->id)->count());
        $this->assertSame(1, (int) $sale->fresh()->payment_status);
        $this->assertSame('pending', $attempt->fresh()->state);
    }

    public function test_verified_completion_is_atomic_and_duplicate_delivery_is_idempotent(): void
    {
        $sale = $this->sale(75);
        $attempt = $this->payments->start($sale->id, 'Stripe');

        $first = $this->payments->completeVerified(
            $attempt->public_id,
            'stripe-transaction-1',
            75,
            $attempt->currency_code,
            ['verified' => true, 'source' => 'test-adapter']
        );
        $second = $this->payments->completeVerified(
            $attempt->public_id,
            'stripe-transaction-1',
            75,
            $attempt->currency_code,
            ['verified' => true, 'source' => 'test-adapter']
        );

        $this->assertSame($first->payment_id, $second->payment_id);
        $this->assertSame(1, Payment::where('sale_id', $sale->id)->count());
        $this->assertSame(4, (int) $sale->fresh()->payment_status);
        $this->assertSame(75.0, (float) $sale->fresh()->paid_amount);
        $this->assertSame('paid', $second->state);
        $payment = Payment::where('sale_id', $sale->id)->firstOrFail();
        $this->assertSame('posted', $payment->fresh()->accounting_status);
        $this->assertTrue(JournalEntry::query()
            ->where('source_type', Payment::class)
            ->where('source_id', $payment->id)
            ->where('event_type', 'ecommerce_payment_created')
            ->exists());
    }

    public function test_accounting_failure_rolls_back_payment_sale_state_and_attempt_completion(): void
    {
        $sale = $this->sale(75);
        $attempt = $this->payments->start($sale->id, 'Stripe');
        $journalCount = JournalEntry::query()->count();

        $accounting = \Mockery::mock(AccountingService::class);
        $accounting->shouldReceive('recordPayment')->once()
            ->andReturn(AccountingResult::failed('Controlled A1 payment posting failure.'));
        $this->app->instance(AccountingService::class, $accounting);
        $payments = app(StorefrontPaymentService::class);

        $this->expectValidationError(
            fn () => $payments->completeVerified(
                $attempt->public_id,
                'failed-accounting-txn',
                75,
                $attempt->currency_code,
                ['verified' => true]
            ),
            'accounting'
        );

        $this->assertSame(0, Payment::where('sale_id', $sale->id)->count());
        $this->assertSame(0.0, (float) $sale->fresh()->paid_amount);
        $this->assertSame(1, (int) $sale->fresh()->payment_status);
        $this->assertSame('pending', $attempt->fresh()->state);
        $this->assertNull($attempt->fresh()->payment_id);
        $this->assertSame($journalCount, JournalEntry::query()->count());
    }

    public function test_provider_transaction_cannot_be_replayed_for_another_sale(): void
    {
        $first = $this->payments->start($this->sale(10)->id, 'Stripe');
        $secondSale = $this->sale(10);
        $second = $this->payments->start($secondSale->id, 'Stripe');
        $this->payments->completeVerified($first->public_id, 'replayed-txn', 10, $first->currency_code, ['verified' => true]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->payments->completeVerified($second->public_id, 'replayed-txn', 10, $second->currency_code, ['verified' => true]);
    }

    public function test_failure_is_idempotent_and_paid_attempt_cannot_fail(): void
    {
        $failed = $this->payments->start($this->sale()->id, 'Stripe');
        $this->assertSame('failed', $this->payments->fail($failed->public_id, 'declined')->state);
        $this->assertSame('failed', $this->payments->fail($failed->public_id, 'duplicate notice')->state);

        $paid = $this->payments->start($this->sale()->id, 'Stripe');
        $this->payments->completeVerified($paid->public_id, 'paid-txn', 100, $paid->currency_code, ['verified' => true]);
        $this->expectValidationError(fn () => $this->payments->fail($paid->public_id, 'late failure'), 'payment');
    }

    public function test_cart_clears_only_for_matching_verified_browser_attempt(): void
    {
        $sale = $this->sale();
        $attempt = $this->payments->start($sale->id, 'Stripe');
        session(['cart' => ['line'], 'ecommerce_payment_sale_id' => $sale->id + 1]);
        $this->assertFalse($this->payments->clearCartAfterVerifiedBrowserReturn($attempt->public_id));
        $this->assertNotEmpty(session('cart'));

        $this->payments->completeVerified($attempt->public_id, 'cart-txn', 100, $attempt->currency_code, ['verified' => true]);
        $this->assertFalse($this->payments->clearCartAfterVerifiedBrowserReturn($attempt->public_id));
        session(['ecommerce_payment_sale_id' => $sale->id]);
        $this->assertTrue($this->payments->clearCartAfterVerifiedBrowserReturn($attempt->public_id));
        $this->assertNull(session('cart'));
    }

    public function test_stripe_adapter_uses_attempt_values_and_verifies_server_retrieval(): void
    {
        DB::table('external_services')->where('name', 'Stripe')
            ->update(['active' => 1, 'details' => 'Public Key,Private Key;pk_test_phase,sk_test_phase']);
        $attempt = $this->payments->start($this->sale(12.34)->id, 'Stripe');
        $intents = new class($attempt) {
            public array $created = [];
            public function __construct(private $attempt) {}
            public function create(array $data, array $options) {
                $this->created = compact('data', 'options');
                return (object) ['id' => 'pi_phase', 'client_secret' => 'secret_phase'];
            }
            public function retrieve() {
                return (object) [
                    'id' => 'pi_phase', 'status' => 'succeeded', 'amount' => 1234,
                    'amount_received' => 1234, 'currency' => strtolower($this->attempt->currency_code),
                    'metadata' => (object) ['ecommerce_payment_attempt' => $this->attempt->public_id],
                ];
            }
        };
        $adapter = new StripePaymentAdapter(app(PaymentProviderCredentials::class), (object) ['paymentIntents' => $intents]);

        $started = $adapter->initiate($attempt);
        $attempt = $this->payments->recordProviderReference($attempt->public_id, $started['provider_reference']);
        $verified = $adapter->verify($attempt, ['payment_intent_id' => 'pi_phase']);

        $this->assertSame(1234, $intents->created['data']['amount']);
        $this->assertSame($attempt->public_id, $intents->created['data']['metadata']['ecommerce_payment_attempt']);
        $this->assertSame($attempt->idempotency_key, $intents->created['options']['idempotency_key']);
        $this->assertSame(12.34, $verified['amount']);
        $this->assertTrue($verified['evidence']['verified']);
    }

    public function test_paypal_adapter_creates_and_captures_server_side_order(): void
    {
        DB::table('external_services')->where('name', 'PayPal')
            ->update(['active' => 1, 'details' => 'Client ID,Client Secret;client_phase,secret_phase']);
        $attempt = $this->payments->start($this->saleWithCurrency(20, 'USD')->id, 'PayPal');
        Http::fake(function ($request) use ($attempt) {
            if (str_contains($request->url(), '/v1/oauth2/token')) {
                return Http::response(['access_token' => 'token'], 200);
            }
            if (str_ends_with($request->url(), '/capture')) {
                return Http::response([
                    'status' => 'COMPLETED',
                    'purchase_units' => [[
                        'custom_id' => $attempt->public_id,
                        'payments' => ['captures' => [[
                            'id' => 'capture_phase', 'status' => 'COMPLETED',
                            'amount' => ['value' => '20.00', 'currency_code' => $attempt->currency_code],
                        ]]],
                    ]],
                ], 201);
            }
            return Http::response(['id' => 'paypal_order_phase'], 201);
        });
        $adapter = app(PayPalPaymentAdapter::class);

        $started = $adapter->initiate($attempt);
        $attempt = $this->payments->recordProviderReference($attempt->public_id, $started['provider_reference']);
        $verified = $adapter->verify($attempt, ['order_id' => 'paypal_order_phase']);

        $this->assertSame('paypal_order_phase', $started['order_id']);
        $this->assertSame('capture_phase', $verified['transaction_id']);
        $this->assertSame(20.0, $verified['amount']);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/capture'));
    }

    public function test_razorpay_adapter_verifies_signature_and_captured_payment(): void
    {
        DB::table('external_services')->where('name', 'Razorpay')
            ->update(['active' => 1, 'details' => 'Key,Secret;rzp_phase,secret_phase']);
        $attempt = $this->payments->start($this->saleWithCurrency(30, 'INR')->id, 'Razorpay');
        $orderApi = new class {
            public array $created = [];
            public function create(array $data) { $this->created = $data; return ['id' => 'order_phase']; }
        };
        $utility = new class {
            public bool $verified = false;
            public function verifyPaymentSignature(array $data) { $this->verified = true; }
        };
        $paymentApi = new class {
            public function fetch() {
                return ['id' => 'pay_phase', 'status' => 'captured', 'order_id' => 'order_phase', 'amount' => 3000, 'currency' => 'INR'];
            }
        };
        $adapter = new RazorpayPaymentAdapter(
            app(PaymentProviderCredentials::class),
            (object) ['order' => $orderApi, 'utility' => $utility, 'payment' => $paymentApi]
        );

        $started = $adapter->initiate($attempt);
        $attempt = $this->payments->recordProviderReference($attempt->public_id, $started['provider_reference']);
        $verified = $adapter->verify($attempt, [
            'razorpay_order_id' => 'order_phase',
            'razorpay_payment_id' => 'pay_phase',
            'razorpay_signature' => 'signature_phase',
        ]);

        $this->assertTrue($utility->verified);
        $this->assertSame($attempt->public_id, $orderApi->created['receipt']);
        $this->assertEquals(30.0, $verified['amount']);
    }

    public function test_only_certified_provider_names_are_exposed(): void
    {
        $this->assertEqualsCanonicalizing(
            ['PayPal', 'Stripe', 'Razorpay'],
            $this->payments->exposedProviderNames()
        );
    }

    private function sale(float $total = 100): Sale
    {
        return Sale::create([
            'reference_no' => 'P3B4A-' . uniqid(),
            'user_id' => (int) DB::table('users')->where('email', 'ecommerce-user@example.test')->value('id'),
            'customer_id' => (int) DB::table('customers')->where('email', 'ecommerce-customer@example.test')->value('id'),
            'warehouse_id' => (int) DB::table('warehouses')->where('name', 'Ecommerce Test Warehouse')->value('id'),
            'biller_id' => (int) DB::table('billers')->where('email', 'ecommerce-biller@example.test')->value('id'),
            'item' => 1,
            'total_qty' => 1,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_price' => $total,
            'grand_total' => $total,
            'sale_status' => 2,
            'payment_status' => 1,
            'paid_amount' => 0,
            'sale_type' => 'online',
            'currency_id' => $this->currency->id,
            'exchange_rate' => 1,
        ]);
    }

    private function currencyWithCode(string $code): Currency
    {
        return Currency::create([
            'name' => 'Phase 3B.4A ' . $code . ' ' . uniqid(),
            'code' => $code,
            'symbol' => '$',
            'exchange_rate' => 1,
            'is_active' => 1,
        ]);
    }

    private function saleWithCurrency(float $total, string $code): Sale
    {
        $original = $this->currency;
        $this->currency = Currency::where('code', $code)->first() ?? $this->currencyWithCode($code);
        $sale = $this->sale($total);
        $this->currency = $original;
        return $sale;
    }

    private function expectValidationError(callable $callback, string $key): void
    {
        try {
            $callback();
            $this->fail("Expected validation error for {$key}.");
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($key, $exception->errors());
        }
    }
}
