<?php

namespace Modules\Woocommerce\Tests\Feature;

use App\Models\{Account, Biller, Category, Currency, Customer, CustomerGroup, JournalEntry, Payment, Product, Product_Sale, Product_Warehouse, Sale, Warehouse};
use App\Services\AccountingResult;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Woocommerce\Contracts\WooCommerceClientFactory;
use Modules\Woocommerce\Models\WoocommerceSetting;
use Modules\Woocommerce\Tests\Fakes\{FakeWooCommerceClient, FakeWooCommerceClientFactory};
use Tests\TestCase;

class WooCommerceOrderImportE2ETest extends TestCase
{
    use DatabaseTransactions;

    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->warehouse = Warehouse::query()->where('is_active', 1)->firstOrFail();
        WoocommerceSetting::query()->delete();
        WoocommerceSetting::query()->create([
            'sync_enabled' => true, 'woocomerce_app_url' => 'https://93.184.216.34/store',
            'woocomerce_consumer_key' => 'synthetic-key', 'woocomerce_consumer_secret' => 'synthetic-secret',
            'customer_group_id' => CustomerGroup::query()->where('is_active', 1)->firstOrFail()->id,
            'warehouse_id' => $this->warehouse->id,
            'biller_id' => Biller::query()->where('is_active', 1)->firstOrFail()->id,
            'order_status_pending' => 1, 'order_status_processing' => 1, 'order_status_on_hold' => 1,
            'order_status_completed' => 1, 'order_status_draft' => 1,
        ]);
        Product::query()->whereNotNull('woocommerce_product_id')->update(['woocommerce_product_id' => null]);
        $this->withoutMiddleware([\App\Http\Middleware\Common::class, \App\Http\Middleware\Active::class]);
        $this->actingAs($this->createIsolatedAdminUser());
    }

    public function test_supported_paid_order_uses_deterministic_references_and_deducts_stock_once(): void
    {
        $product = $this->mappedProduct(7001, 10);
        $order = $this->order(8001, 7001, 2);
        $fake = (new FakeWooCommerceClient)->queue('GET', 'orders', [$order], []);
        app()->instance(WooCommerceClientFactory::class, new FakeWooCommerceClientFactory($fake));

        $this->post(route('woocommerce.syncOrders', absolute: false))->assertOk()->assertJson(['success' => 1]);

        $sale = Sale::query()->where('woocommerce_order_id', 8001)->firstOrFail();
        self::assertSame('woo-8001', $sale->reference_no);
        self::assertSame($this->warehouse->id, (int) $sale->warehouse_id);
        $payment = Payment::query()->where('sale_id', $sale->id)->firstOrFail();
        self::assertSame('woo-pay-8001', $payment->payment_reference);
        self::assertSame('posted', $sale->fresh()->accounting_status);
        self::assertSame('posted', $payment->fresh()->accounting_status);
        self::assertTrue(JournalEntry::query()->where('source_type', Sale::class)->where('source_id', $sale->id)->where('event_type', 'woocommerce_order_created')->exists());
        self::assertTrue(JournalEntry::query()->where('source_type', Payment::class)->where('source_id', $payment->id)->where('event_type', 'woocommerce_payment_created')->exists());
        self::assertSame(1, Product_Sale::query()->where('sale_id', $sale->id)->count());
        self::assertSame(8.0, (float) Product_Warehouse::query()->where('product_id', $product->id)->where('warehouse_id', $this->warehouse->id)->value('qty'));
        self::assertSame([0, 0], $fake->requestTransactionDepths);
    }

    public function test_replay_is_idempotent_and_insufficient_stock_rolls_back_all_local_state(): void
    {
        $product = $this->mappedProduct(7002, 1);
        $before = $this->fingerprint($product);
        $fake = (new FakeWooCommerceClient)->queue('GET', 'orders', [$this->order(8002, 7002, 2)], []);
        app()->instance(WooCommerceClientFactory::class, new FakeWooCommerceClientFactory($fake));
        $this->post(route('woocommerce.syncOrders', absolute: false))->assertOk()->assertJson(['success' => 0]);
        self::assertSame($before, $this->fingerprint($product));
        self::assertSame(0, Sale::query()->where('woocommerce_order_id', 8002)->count());
    }

    public function test_successful_sequential_replay_does_not_duplicate_sale_payment_or_stock_effect(): void
    {
        $product = $this->mappedProduct(7003, 10);
        $order = $this->order(8003, 7003, 2);
        $fake = (new FakeWooCommerceClient)->queue('GET', 'orders', [$order], [], [$order], []);
        app()->instance(WooCommerceClientFactory::class, new FakeWooCommerceClientFactory($fake));

        $this->post(route('woocommerce.syncOrders', absolute: false))->assertOk()->assertJson(['success' => 1]);
        $this->post(route('woocommerce.syncOrders', absolute: false))->assertOk()->assertJson(['success' => 1]);

        $sale = Sale::query()->where('woocommerce_order_id', 8003)->firstOrFail();
        self::assertSame(1, Sale::query()->where('woocommerce_order_id', 8003)->count());
        self::assertSame(1, Payment::query()->where('sale_id', $sale->id)->count());
        self::assertSame(1, Product_Sale::query()->where('sale_id', $sale->id)->count());
        self::assertSame(8.0, (float) Product_Warehouse::query()->where('product_id', $product->id)->where('warehouse_id', $this->warehouse->id)->value('qty'));
    }

    public function test_missing_product_mapping_rolls_back_customer_and_all_order_state(): void
    {
        $before = [Customer::query()->count(), Sale::query()->count(), Product_Sale::query()->count(), Payment::query()->count()];
        $fake = (new FakeWooCommerceClient)->queue('GET', 'orders', [$this->order(8004, 999999, 1)], []);
        app()->instance(WooCommerceClientFactory::class, new FakeWooCommerceClientFactory($fake));
        $this->post(route('woocommerce.syncOrders', absolute: false))->assertOk()->assertJson(['success' => 0]);
        self::assertSame($before, [Customer::query()->count(), Sale::query()->count(), Product_Sale::query()->count(), Payment::query()->count()]);
        self::assertSame(0, Sale::query()->where('woocommerce_order_id', 8004)->count());
    }

    public function test_sale_accounting_failure_rolls_back_the_entire_import(): void
    {
        $product = $this->mappedProduct(7005, 10);
        $before = $this->fingerprint($product);
        $fake = (new FakeWooCommerceClient)->queue('GET', 'orders', [$this->order(8005, 7005, 2)], []);
        app()->instance(WooCommerceClientFactory::class, new FakeWooCommerceClientFactory($fake));

        $accounting = \Mockery::mock(AccountingService::class);
        $accounting->shouldReceive('recordSale')->once()
            ->andReturn(AccountingResult::failed('Controlled A1 Woo sale posting failure.'));
        $this->app->instance(AccountingService::class, $accounting);

        $this->post(route('woocommerce.syncOrders', absolute: false))->assertOk()->assertJson(['success' => 0]);

        self::assertSame($before, $this->fingerprint($product));
        self::assertSame(0, Sale::query()->where('woocommerce_order_id', 8005)->count());
    }

    public function test_payment_accounting_failure_rolls_back_sale_payment_and_stock(): void
    {
        $product = $this->mappedProduct(7006, 10);
        $before = $this->fingerprint($product);
        $fake = (new FakeWooCommerceClient)->queue('GET', 'orders', [$this->order(8006, 7006, 2)], []);
        app()->instance(WooCommerceClientFactory::class, new FakeWooCommerceClientFactory($fake));

        $accounting = \Mockery::mock(AccountingService::class);
        $accounting->shouldReceive('recordSale')->once()->andReturn(AccountingResult::success());
        $accounting->shouldReceive('recordPayment')->once()
            ->andReturn(AccountingResult::failed('Controlled A1 Woo payment posting failure.'));
        $this->app->instance(AccountingService::class, $accounting);

        $this->post(route('woocommerce.syncOrders', absolute: false))->assertOk()->assertJson(['success' => 0]);

        self::assertSame($before, $this->fingerprint($product));
        self::assertSame(0, Sale::query()->where('woocommerce_order_id', 8006)->count());
    }

    public function test_unpaid_and_foreign_currency_imports_retain_transaction_amounts_and_normalize_journals(): void
    {
        $foreign = Currency::query()->where('code', 'A1F')->first() ?? Currency::create([
            'name' => 'A1 Foreign Currency', 'code' => 'A1F', 'symbol' => 'F',
            'exchange_rate' => 2.5, 'is_active' => 1,
        ]);
        $foreign->update(['exchange_rate' => 2.5, 'is_active' => 1]);
        $baseProduct = $this->mappedProduct(7007, 10);
        $foreignUnpaidProduct = $this->mappedProduct(7008, 10);
        $foreignPaidProduct = $this->mappedProduct(7009, 10);
        $fake = (new FakeWooCommerceClient)->queue(
            'GET',
            'orders',
            [$this->order(8007, 7007, 2, null, false)], [],
            [$this->order(8008, 7008, 2, $foreign->code, false)], [],
            [$this->order(8009, 7009, 2, $foreign->code)], []
        );
        app()->instance(WooCommerceClientFactory::class, new FakeWooCommerceClientFactory($fake));

        $this->post(route('woocommerce.syncOrders', absolute: false))->assertOk()->assertJson(['success' => 1]);
        $baseSale = Sale::query()->where('woocommerce_order_id', 8007)->sole();
        self::assertSame(0, Payment::query()->where('sale_id', $baseSale->id)->count());
        self::assertSame(
            app(\App\Services\Accounting\CurrencyNormalizationService::class)->getBaseCurrencyId(),
            (int) $baseSale->currency_id
        );

        $this->post(route('woocommerce.syncOrders', absolute: false))->assertOk()->assertJson(['success' => 1]);
        $foreignUnpaidSale = Sale::query()->where('woocommerce_order_id', 8008)->sole();
        self::assertSame($foreign->id, (int) $foreignUnpaidSale->currency_id);
        self::assertSame(2.5, (float) $foreignUnpaidSale->exchange_rate);
        self::assertSame(20.0, (float) $foreignUnpaidSale->grand_total);
        self::assertSame(0, Payment::query()->where('sale_id', $foreignUnpaidSale->id)->count());

        $this->post(route('woocommerce.syncOrders', absolute: false))->assertOk()->assertJson(['success' => 1]);
        $foreignPaidSale = Sale::query()->where('woocommerce_order_id', 8009)->sole();
        $payment = Payment::query()->where('sale_id', $foreignPaidSale->id)->sole();
        $journal = JournalEntry::query()->where([
            'source_type' => Sale::class,
            'source_id' => $foreignPaidSale->id,
            'event_type' => 'woocommerce_order_created',
        ])->sole();
        $paymentJournal = JournalEntry::query()->where([
            'source_type' => Payment::class,
            'source_id' => $payment->id,
            'event_type' => 'woocommerce_payment_created',
        ])->sole();

        self::assertSame($foreign->id, (int) $foreignPaidSale->currency_id);
        self::assertSame(20.0, (float) $foreignPaidSale->grand_total);
        self::assertSame(2.5, (float) $foreignPaidSale->exchange_rate);
        self::assertSame($foreign->id, (int) $payment->currency_id);
        self::assertSame(2.5, (float) $payment->exchange_rate);
        self::assertSame(8.0, (float) $journal->lines()->sum('debit'));
        self::assertSame(8.0, (float) $journal->lines()->sum('credit'));
        self::assertSame(8.0, (float) $paymentJournal->lines()->sum('debit'));
        self::assertSame(8.0, (float) $paymentJournal->lines()->sum('credit'));
        self::assertSame(8.0, (float) $foreignPaidSale->grand_total / (float) $foreignPaidSale->exchange_rate);
        self::assertSame(8.0, (float) Product_Warehouse::query()->where('product_id', $foreignPaidProduct->id)->where('warehouse_id', $this->warehouse->id)->value('qty'));
        self::assertSame(8.0, (float) Product_Warehouse::query()->where('product_id', $foreignUnpaidProduct->id)->where('warehouse_id', $this->warehouse->id)->value('qty'));
        self::assertSame(8.0, (float) Product_Warehouse::query()->where('product_id', $baseProduct->id)->where('warehouse_id', $this->warehouse->id)->value('qty'));
    }

    private function mappedProduct(int $remoteId, float $stock): Product
    {
        $source = Product::query()->firstOrFail();
        $category = Category::query()->firstOrFail();
        $product = $source->replicate();
        $product->fill(['name' => 'Order product '.bin2hex(random_bytes(3)), 'code' => 'O'.bin2hex(random_bytes(5)),
            'category_id' => $category->id, 'type' => 'standard', 'is_active' => 1, 'qty' => $stock,
            'woocommerce_product_id' => $remoteId, 'is_batch' => 0, 'is_imei' => 0]);
        $product->save();
        Product_Warehouse::query()->create(['product_id' => $product->id, 'warehouse_id' => $this->warehouse->id, 'qty' => $stock]);
        return $product;
    }

    private function order(int $id, int $remoteProductId, float $quantity, ?string $currency = null, bool $paid = true): object
    {
        return json_decode(json_encode([
            'id' => $id, 'status' => 'processing', 'discount_total' => 0, 'cart_tax' => 0,
            'total' => 20, 'shipping_total' => 0, 'shipping_tax' => 0, 'payment_method' => $paid ? 'card' : 'cod',
            'payment_method_title' => $paid ? 'Card' : 'Cash on Delivery',
            'currency' => $currency ?: Currency::query()->whereKey(app(\App\Services\Accounting\CurrencyNormalizationService::class)->getBaseCurrencyId())->value('code'),
            'billing' => ['first_name' => 'Synthetic', 'last_name' => 'Buyer', 'company' => '',
                'email' => "woo{$id}@example.test", 'phone' => "555{$id}", 'address_1' => 'Test',
                'address_2' => '', 'city' => 'Test', 'state' => '', 'postcode' => '1000', 'country' => 'BD'],
            'line_items' => [['product_id' => $remoteProductId, 'quantity' => $quantity,
                'subtotal' => 20, 'taxes' => []]],
        ]));
    }

    private function fingerprint(Product $product): array
    {
        return [
            Sale::query()->count(), Product_Sale::query()->count(), Payment::query()->count(), Customer::query()->count(),
            (float) $product->fresh()->qty,
            (float) Product_Warehouse::query()->where('product_id', $product->id)->where('warehouse_id', $this->warehouse->id)->value('qty'),
        ];
    }
}
