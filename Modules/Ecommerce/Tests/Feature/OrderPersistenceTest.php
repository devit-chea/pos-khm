<?php

namespace Modules\Ecommerce\Tests\Feature;

use App\Models\Currency;
use App\Models\Coupon;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Services\AccountingResult;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Ecommerce\Services\StorefrontCartService;
use Modules\Ecommerce\Services\StorefrontCheckoutService;
use Modules\Ecommerce\Services\StorefrontOrderService;
use Tests\TestCase;

class OrderPersistenceTest extends TestCase
{
    use DatabaseTransactions;

    private StorefrontCartService $cart;
    private StorefrontCheckoutService $checkout;
    private StorefrontOrderService $orders;
    private int $warehouseId;
    private int $unitId;

    protected function setUp(): void
    {
        parent::setUp();
        session()->forget(['cart', 'total_qty', 'subTotal', 'currency_code', 'currency_rate']);
        Currency::updateOrCreate(['code' => 'USD'], [
            'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1, 'is_active' => 1,
        ]);
        $this->cart = app(StorefrontCartService::class);
        $this->checkout = app(StorefrontCheckoutService::class);
        $this->orders = app(StorefrontOrderService::class);
        DB::table('ecommerce_settings')->updateOrInsert(
            ['id' => 1],
            [
                'site_title' => 'Ecommerce Order Test',
                'warehouse_id' => (int) DB::table('warehouses')->value('id'),
                'biller_id' => (int) DB::table('billers')->value('id'),
                'flat_rate_shipping' => 0,
                'free_shipping_from' => 0,
                'storefront_enabled' => 0,
                'legacy_activation_review_required' => 0,
            ]
        );
        $settings = DB::table('ecommerce_settings')->latest('id')->first();
        $this->warehouseId = (int) $settings->warehouse_id;
        $this->unitId = DB::table('units')->insertGetId([
            'unit_code' => 'P3B3',
            'unit_name' => 'Phase 3B.3 Unit ' . uniqid(),
            'operator' => '*',
            'operation_value' => 1,
            'is_active' => 1,
        ]);
    }

    public function test_standard_order_atomically_persists_financial_lines_and_inventory(): void
    {
        $product = $this->standardProduct();
        $this->stock($product->id, null, 10);
        $this->cart->add($product->id, null, 2);
        $quote = $this->checkout->quote();

        $result = $this->orders->create($quote, $this->header());
        $sale = $result['sale'];
        $line = DB::table('product_sales')->where('sale_id', $sale->id)->first();

        $this->assertStringStartsWith('ecomm-', $sale->reference_no);
        $this->assertSame($quote['display']['grand_total'], (float) $sale->grand_total);
        $this->assertSame($quote['display']['total_tax'], (float) $sale->total_tax);
        $this->assertNotNull($sale->ecommerce_inventory_deducted_at);
        $this->assertSame($product->id, (int) $line->product_id);
        $this->assertNull($line->product_variant_id);
        $this->assertSame(8.0, (float) $product->fresh()->qty);
        $this->assertSame(8.0, $this->warehouseQty($product->id));
        $this->assertSame('posted', $sale->fresh()->accounting_status);
        $this->assertTrue(JournalEntry::query()
            ->where('source_type', Sale::class)
            ->where('source_id', $sale->id)
            ->where('event_type', 'ecommerce_order_created')
            ->exists());
    }

    public function test_variant_order_persists_both_core_and_opaque_variant_identity(): void
    {
        $product = $this->standardProduct();
        $product->update(['is_variant' => 1]);
        $definitionId = DB::table('variants')->insertGetId(['name' => 'Phase3B3/Blue']);
        $productVariantId = DB::table('product_variants')->insertGetId([
            'product_id' => $product->id,
            'variant_id' => $definitionId,
            'item_code' => 'P3B3-BLUE',
            'additional_price' => 5,
            'qty' => 10,
            'position' => 1,
        ]);
        $this->stock($product->id, $definitionId, 10);
        $this->cart->add($product->id, $productVariantId, 3);

        $result = $this->orders->create($this->checkout->quote(), $this->header());
        $line = DB::table('product_sales')->where('sale_id', $result['sale']->id)->first();

        $this->assertSame($definitionId, (int) $line->variant_id);
        $this->assertSame($productVariantId, (int) $line->product_variant_id);
        $this->assertSame(7.0, (float) ProductVariant::find($productVariantId)->qty);
        $this->assertSame(7.0, $this->warehouseQty($product->id, $definitionId));
        $this->assertSame(7.0, (float) $product->fresh()->qty);
    }

    public function test_coupon_tax_shipping_and_line_allocations_are_persisted_from_quote(): void
    {
        $product = $this->standardProduct();
        $taxId = DB::table('taxes')->insertGetId([
            'name' => 'Phase 3B.3 Tax',
            'rate' => 10,
            'is_active' => 1,
        ]);
        $product->update(['tax_id' => $taxId, 'tax_method' => 1]);
        $this->stock($product->id, null, 10);
        $couponId = DB::table('coupons')->insertGetId([
            'code' => 'ORDER-P3B3',
            'type' => 'fixed',
            'amount' => 10,
            'minimum_amount' => 0,
            'quantity' => 1,
            'used' => 0,
            'expired_date' => now()->addDay()->toDateString(),
            'user_id' => 1,
            'is_active' => 1,
        ]);
        $this->cart->add($product->id, null, 2);
        $quote = $this->checkout->quote('ORDER-P3B3');

        $result = $this->orders->create($quote, $this->header());
        $sale = $result['sale'];
        $line = DB::table('product_sales')->where('sale_id', $sale->id)->first();

        $this->assertSame($couponId, (int) $sale->coupon_id);
        $rate = (float) $quote['exchange_rate'];
        $this->assertSame($quote['display']['coupon_discount'], (float) $sale->coupon_discount);
        $this->assertSame($quote['display']['shipping_cost'], (float) $sale->shipping_cost);
        $this->assertSame($quote['display']['grand_total'], (float) $sale->grand_total);
        $this->assertSame(round((float) $quote['lines'][(string) $product->id]['tax'] * $rate, 4), (float) $line->tax);
        $this->assertSame(round((float) $quote['lines'][(string) $product->id]['discount'] * $rate, 4), (float) $line->discount);
        $this->assertSame(1, (int) Coupon::find($couponId)->used);
    }

    public function test_insufficient_locked_warehouse_stock_rolls_back_everything(): void
    {
        $product = $this->standardProduct();
        $this->stock($product->id, null, 2);
        $this->cart->add($product->id, null, 2);
        $quote = $this->checkout->quote();
        DB::table('product_warehouse')->where([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouseId,
        ])->whereNull('variant_id')->whereNull('product_batch_id')->update(['qty' => 1]);
        $saleCount = Sale::count();

        try {
            $this->orders->create($quote, $this->header());
            $this->fail('Order was created above locked warehouse stock.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('cart', $exception->errors());
        }

        $this->assertSame($saleCount, Sale::count());
        $this->assertSame(10.0, (float) $product->fresh()->qty);
        $this->assertSame(1.0, $this->warehouseQty($product->id));
    }

    public function test_accounting_failure_rolls_back_order_stock_lines_coupon_and_activity(): void
    {
        $product = $this->standardProduct();
        $this->stock($product->id, null, 10);
        $coupon = Coupon::create([
            'code' => 'A1-POST-FAIL-' . uniqid(),
            'type' => 'fixed',
            'amount' => 10,
            'minimum_amount' => 0,
            'quantity' => 1,
            'used' => 0,
            'expired_date' => now()->addDay()->toDateString(),
            'user_id' => 1,
            'is_active' => 1,
        ]);
        $this->cart->add($product->id, null, 2);
        $quote = $this->checkout->quote($coupon->code);
        $before = [
            'sales' => Sale::count(),
            'lines' => DB::table('product_sales')->count(),
            'activities' => DB::table('activity_logs')->count(),
            'journals' => JournalEntry::query()->count(),
        ];

        $accounting = \Mockery::mock(AccountingService::class);
        $accounting->shouldReceive('recordSale')->once()
            ->andReturn(AccountingResult::failed('Controlled A1 order posting failure.'));
        $this->app->instance(AccountingService::class, $accounting);
        $orders = app(StorefrontOrderService::class);

        try {
            $orders->create($quote, $this->header());
            $this->fail('The order committed despite an accounting posting failure.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('accounting', $exception->errors());
        }

        $this->assertSame($before['sales'], Sale::count());
        $this->assertSame($before['lines'], DB::table('product_sales')->count());
        $this->assertSame($before['activities'], DB::table('activity_logs')->count());
        $this->assertSame($before['journals'], JournalEntry::query()->count());
        $this->assertSame(10.0, (float) $product->fresh()->qty);
        $this->assertSame(10.0, $this->warehouseQty($product->id));
        $this->assertSame(0, (int) $coupon->fresh()->used);
    }

    public function test_combo_children_are_deducted_and_service_products_are_not(): void
    {
        $child = $this->standardProduct();
        $this->stock($child->id, null, 10);
        $combo = Product::where('type', 'combo')->firstOrFail();
        $combo->update([
            'is_active' => 1,
            'is_online' => 1,
            'is_imei' => 0,
            'in_stock' => 1,
            'price' => 25,
            'promotion' => 0,
            'sale_unit_id' => $this->unitId,
            'product_list' => (string) $child->id,
            'qty_list' => '2',
            'variant_list' => null,
            'combo_unit_id' => (string) $this->unitId,
        ]);
        $this->cart->add($combo->id, null, 2);
        $this->orders->create($this->checkout->quote(), $this->header());
        $this->assertSame(6.0, (float) $child->fresh()->qty);
        $this->assertSame(6.0, $this->warehouseQty($child->id));

        session()->forget(['cart', 'total_qty', 'subTotal']);
        $service = Product::where('type', 'service')->firstOrFail();
        $service->update([
            'is_active' => 1,
            'is_online' => 1,
            'is_imei' => 0,
            'is_variant' => 0,
            'in_stock' => 0,
            'qty' => 5,
            'price' => 20,
            'promotion' => 0,
            'sale_unit_id' => $this->unitId,
        ]);
        $this->cart->add($service->id, null, 2);
        $this->orders->create($this->checkout->quote(), $this->header());
        $this->assertSame(5.0, (float) $service->fresh()->qty);
    }

    public function test_customer_cancellation_restores_stock_and_coupon_once(): void
    {
        $product = $this->standardProduct();
        $this->stock($product->id, null, 10);
        $this->cart->add($product->id, null, 2);
        $result = $this->orders->create($this->checkout->quote(), $this->header());
        $sale = $result['sale'];

        $cancelled = $this->orders->cancelForCustomer($sale->id, (int) $sale->customer_id);

        $this->assertSame(3, (int) $cancelled->sale_status);
        $this->assertNotNull($cancelled->ecommerce_inventory_restored_at);
        $this->assertSame(10.0, (float) $product->fresh()->qty);
        $this->assertSame(10.0, $this->warehouseQty($product->id));
        $this->assertSame('reversed', $cancelled->fresh()->accounting_status);
        $this->assertTrue(JournalEntry::query()
            ->where('source_type', Sale::class)
            ->where('source_id', $sale->id)
            ->whereNotNull('related_journal_entry_id')
            ->exists());

        $this->expectException(ValidationException::class);
        $this->orders->cancelForCustomer($sale->id, (int) $sale->customer_id);
    }

    private function standardProduct(): Product
    {
        $product = Product::where('type', 'standard')
            ->where(fn ($query) => $query->whereNull('is_imei')->orWhere('is_imei', 0))
            ->firstOrFail();
        $product->update([
            'is_active' => 1,
            'is_online' => 1,
            'is_imei' => 0,
            'is_variant' => 0,
            'in_stock' => 0,
            'qty' => 10,
            'price' => 100,
            'promotion' => 0,
            'tax_id' => null,
            'tax_method' => 1,
            'sale_unit_id' => $this->unitId,
        ]);

        return $product->fresh();
    }

    private function stock(int $productId, ?int $variantDefinitionId, float $quantity): void
    {
        DB::table('product_warehouse')->where([
            'product_id' => $productId,
            'warehouse_id' => $this->warehouseId,
        ])->where('variant_id', $variantDefinitionId)->whereNull('product_batch_id')->delete();
        DB::table('product_warehouse')->insert([
            'product_id' => $productId,
            'warehouse_id' => $this->warehouseId,
            'variant_id' => $variantDefinitionId,
            'product_batch_id' => null,
            'qty' => $quantity,
        ]);
    }

    private function warehouseQty(int $productId, ?int $variantDefinitionId = null): float
    {
        return (float) DB::table('product_warehouse')->where([
            'product_id' => $productId,
            'warehouse_id' => $this->warehouseId,
        ])->where('variant_id', $variantDefinitionId)->whereNull('product_batch_id')->value('qty');
    }

    private function header(): array
    {
        return [
            'user_id' => (int) DB::table('users')->value('id'),
            'customer_id' => (int) DB::table('customers')->value('id'),
            'billing_name' => 'Phase 3B.3',
            'billing_email' => 'phase3b3@example.test',
            'billing_phone' => '01000000000',
            'billing_address' => 'Test',
            'billing_city' => 'Dhaka',
            'billing_state' => '',
            'billing_zip' => '',
            'billing_country' => 'Bangladesh',
            'shipping_name' => 'Phase 3B.3',
            'shipping_email' => 'phase3b3@example.test',
            'shipping_phone' => '01000000000',
            'shipping_address' => 'Test',
            'shipping_city' => 'Dhaka',
            'shipping_state' => '',
            'shipping_zip' => '',
            'shipping_country' => 'Bangladesh',
            'sale_note' => '',
            'payment_mode' => 'Cash on Delivery',
        ];
    }
}
