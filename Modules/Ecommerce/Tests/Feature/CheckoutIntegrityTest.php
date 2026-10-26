<?php

namespace Modules\Ecommerce\Tests\Feature;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Ecommerce\Http\Controllers\CustomerController;
use Modules\Ecommerce\Services\StorefrontCartService;
use Modules\Ecommerce\Services\StorefrontCheckoutService;
use Tests\TestCase;

class CheckoutIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    private StorefrontCartService $cart;
    private StorefrontCheckoutService $checkout;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        session()->forget(['cart', 'total_qty', 'subTotal', 'currency_code', 'currency_rate']);
        Currency::updateOrCreate(['code' => 'USD'], [
            'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1, 'is_active' => 1,
        ]);
        $this->seedOperationalPrerequisites();
        $this->cart = app(StorefrontCartService::class);
        $this->checkout = app(StorefrontCheckoutService::class);
        $product = Product::where('type', 'standard')
            ->where(fn ($query) => $query->whereNull('is_imei')->orWhere('is_imei', 0))
            ->first();

        if (!$product) {
            $unitId = DB::table('units')->value('id') ?? 1;
            $catId = DB::table('categories')->value('id') ?? 1;
            $product = Product::create([
                'name' => 'Ecommerce Checkout Test Product',
                'code' => 'ECOMM-CHK-' . uniqid(),
                'type' => 'standard',
                'barcode_symbology' => 'C128',
                'category_id' => $catId,
                'unit_id' => $unitId,
                'purchase_unit_id' => $unitId,
                'sale_unit_id' => $unitId,
                'cost' => 50,
                'price' => 100,
                'qty' => 100,
                'is_active' => 1,
                'is_online' => 1,
                'is_imei' => 0,
                'is_variant' => 0,
                'in_stock' => 1,
                'promotion' => 0,
                'tax_id' => null,
                'tax_method' => 1,
            ]);
        } else {
            $product->update([
                'is_active' => 1,
                'is_online' => 1,
                'is_imei' => 0,
                'is_variant' => 0,
                'in_stock' => 1,
                'price' => 100,
                'promotion' => 0,
                'tax_id' => null,
                'tax_method' => 1,
            ]);
        }
        $this->product = $product;
    }

    private function seedOperationalPrerequisites(): void
    {
        $warehouseId = (int) DB::table('warehouses')->value('id');
        $billerId = (int) DB::table('billers')->value('id');

        DB::table('customer_groups')->updateOrInsert(
            ['name' => 'Ecommerce Integrity Customers'],
            ['percentage' => 0, 'is_active' => 1]
        );
        DB::table('ecommerce_settings')->updateOrInsert(
            ['id' => 1],
            [
                'site_title' => 'Ecommerce Integrity Test',
                'warehouse_id' => $warehouseId,
                'biller_id' => $billerId,
                'flat_rate_shipping' => 0,
                'free_shipping_from' => 0,
                'storefront_enabled' => 0,
                'legacy_activation_review_required' => 0,
            ]
        );
    }

    public function test_checkout_refreshes_cart_and_derives_configured_operational_identity(): void
    {
        $this->cart->add($this->product->id, null, 2);
        $this->product->update(['price' => 125]);

        $quote = $this->checkout->quote();
        $settings = DB::table('ecommerce_settings')->latest('id')->first();

        $this->assertSame(250.0, $quote['sub_total']);
        $this->assertSame(2.0, $quote['total_qty']);
        $this->assertSame(1, $quote['item']);
        $this->assertSame((int) $settings->warehouse_id, $quote['warehouse_id']);
        $this->assertSame((int) $settings->biller_id, $quote['biller_id']);
        $this->assertGreaterThan(0, $quote['currency_id']);
    }

    public function test_coupon_tax_shipping_and_grand_total_are_server_calculated(): void
    {
        $taxId = DB::table('taxes')->insertGetId([
            'name' => 'Phase 3B.2 Tax',
            'rate' => 10,
            'is_active' => 1,
        ]);
        $this->product->update(['tax_id' => $taxId, 'tax_method' => 1]);
        $this->cart->add($this->product->id, null, 2);
        DB::table('coupons')->insert([
            'code' => 'PHASE3B2',
            'type' => 'percentage',
            'amount' => 10,
            'minimum_amount' => 100,
            'quantity' => 5,
            'used' => 0,
            'expired_date' => now()->addDay()->toDateString(),
            'user_id' => 1,
            'is_active' => 1,
        ]);
        $areaId = DB::table('delivery_areas')->insertGetId([
            'name' => 'Phase 3B.2 Area',
            'delivery_charge' => 25,
            'is_active' => 1,
        ]);

        $quote = $this->checkout->quote('PHASE3B2', $areaId);

        $this->assertSame(200.0, $quote['sub_total']);
        $this->assertSame(20.0, $quote['coupon_discount']);
        $this->assertSame(18.0, $quote['total_tax']);
        $this->assertSame(25.0, $quote['shipping_cost']);
        $this->assertSame(223.0, $quote['grand_total']);
        $this->assertSame($areaId, $quote['delivery_area_id']);
    }

    public function test_inclusive_tax_is_reported_but_not_added_twice(): void
    {
        $taxId = DB::table('taxes')->insertGetId([
            'name' => 'Phase 3B.2 Inclusive',
            'rate' => 10,
            'is_active' => 1,
        ]);
        $this->product->update(['tax_id' => $taxId, 'tax_method' => 2]);
        DB::table('ecommerce_settings')->update(['flat_rate_shipping' => 0, 'free_shipping_from' => 0]);
        $this->cart->add($this->product->id, null, 1);

        $quote = $this->checkout->quote();

        $this->assertSame(9.0909, $quote['total_tax']);
        $this->assertSame(0.0, $quote['exclusive_tax']);
        $this->assertSame(100.0, $quote['grand_total']);
    }

    public function test_invalid_coupon_and_delivery_area_fail_closed(): void
    {
        $this->cart->add($this->product->id, null, 1);
        DB::table('delivery_areas')->insert([
            'name' => 'Inactive Phase 3B.2 Area',
            'delivery_charge' => 25,
            'is_active' => 0,
        ]);

        try {
            $this->checkout->quote('MISSING');
            $this->fail('Invalid coupon was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('coupon_code', $exception->errors());
        }

        $this->expectException(ValidationException::class);
        $this->checkout->quote(null, 999999);
    }

    public function test_active_delivery_areas_require_an_authoritative_selection(): void
    {
        $this->cart->add($this->product->id, null, 1);
        DB::table('delivery_areas')->insert([
            'name' => 'Required Phase 3B.2 Area',
            'delivery_charge' => 25,
            'is_active' => 1,
        ]);

        $initial = $this->checkout->quote(null, null, false);
        $this->assertTrue($initial['delivery_area_required']);
        $this->assertSame(0.0, $initial['shipping_cost']);

        $this->expectException(ValidationException::class);
        $this->checkout->quote();
    }

    public function test_address_mutations_are_scoped_to_authenticated_customer(): void
    {
        $user = User::create([
            'name' => 'Phase 3B.2 Customer',
            'email' => 'phase3b2-' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
            'phone' => '01000000000',
            'role_id' => 5,
            'is_active' => 1,
            'is_deleted' => 0,
        ]);
        $customer = Customer::create([
            'customer_group_id' => DB::table('customer_groups')->value('id'),
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => '01000000000',
            'address' => 'Owned',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'is_active' => 1,
        ]);
        $this->actingAs($user);
        $controller = app(CustomerController::class);

        $foreignId = DB::table('customer_addresses')->insertGetId([
            'name' => 'Foreign',
            'address' => 'Hidden',
            'city' => 'Hidden',
            'country' => 'Hidden',
            'customer_id' => $customer->id + 100000,
        ]);

        $response = $controller->addressCreate(Request::create('/address/create', 'POST', [
            'name' => 'Owned',
            'address' => 'Safe',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'customer_id' => $customer->id + 100000,
        ]));
        $this->assertTrue($response->isRedirect());
        $this->assertDatabaseHas('customer_addresses', [
            'name' => 'Owned',
            'customer_id' => $customer->id,
        ]);

        try {
            $controller->addressEdit($foreignId);
            $this->fail('Foreign address was exposed.');
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            $this->assertTrue(true);
        }
    }

    public function test_checkout_form_has_no_client_financial_or_operational_authority_fields(): void
    {
        $blade = file_get_contents(module_path('Ecommerce', 'Resources/views/frontend/checkout.blade.php'));

        foreach ([
            'name="sub_total"',
            'name="grand_total"',
            'name="coupon_id"',
            'name="coupon_discount"',
            'name="shipping_cost"',
            'name="total_qty"',
            'name="item"',
            'name="warehouse_id"',
            'name="biller_id"',
            'name="currency"',
        ] as $field) {
            $this->assertStringNotContainsString($field, $blade);
        }
    }
}
