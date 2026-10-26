<?php

namespace Modules\SocialCommerce\Tests\Feature;

use App\Models\Category;
use App\Models\Currency;
use App\Models\GeneralSetting;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Modules\SocialCommerce\Entities\SocialCommerceSetting;
use Modules\SocialCommerce\Entities\SocialProductSetting;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SocialCommerceAjaxPublishTest extends TestCase
{
    use DatabaseTransactions;

    private Currency $currency;
    private Unit $unit;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        View::share('alert_product', 0);
        View::share('dso_alert_product_no', 0);
        View::share('expire_alert_products', 0);

        $this->currency = Currency::create([
            'name' => 'Ajax Test Dollar',
            'code' => 'ATD',
            'symbol' => '$',
            'exchange_rate' => 1,
            'is_active' => true,
        ]);

        GeneralSetting::create([
            'site_title' => 'Social Commerce AJAX Test',
            'currency' => $this->currency->id,
            'currency_position' => 'prefix',
            'staff_access' => 'all',
            'without_stock' => 'no',
            'date_format' => 'd-m-Y',
            'theme' => 'default.css',
            'modules' => '',
            'decimal' => 2,
            'timezone' => 'UTC',
        ]);

        $this->unit = Unit::create([
            'unit_name' => 'Ajax Test Unit',
            'unit_code' => 'ATU-' . uniqid(),
            'operator' => '*',
            'operation_value' => 1,
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Ajax Test Category ' . uniqid(),
            'slug' => 'ajax-test-category-' . uniqid(),
            'is_active' => true,
        ]);

        SocialCommerceSetting::query()->delete();
        SocialCommerceSetting::create([
            'is_active' => true,
            'allow_fallback' => true,
            'whatsapp_number' => '15555550100',
        ]);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        parent::tearDown();
    }

    public function test_ajax_publish_returns_200_json_with_expected_payload(): void
    {
        $product = $this->createProduct();
        $user = $this->createAuthorizedUser();

        $response = $this->actingAs($user)
            ->postJson(route('socialcommerce.togglePublish', $product->id), []);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'product_id' => $product->id,
            'published' => true,
        ]);

        $json = $response->json();
        $this->assertNotEmpty($json['message']);
        $this->assertNotEmpty($json['public_url']);
        $this->assertNotEmpty($json['qr_url']);
        $this->assertNotEmpty($json['whatsapp_url']);
        $this->assertNotEmpty($json['facebook_url']);
        $this->assertNotEmpty($json['telegram_url']);

        $this->assertDatabaseHas('social_product_settings', [
            'product_id' => $product->id,
            'is_published' => 1,
        ]);
    }

    public function test_ajax_unpublish_returns_200_json(): void
    {
        $product = $this->createProduct();
        $this->publish($product);
        $user = $this->createAuthorizedUser();

        $response = $this->actingAs($user)
            ->postJson(route('socialcommerce.togglePublish', $product->id), [
                'unpublish' => 1,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'product_id' => $product->id,
            'published' => false,
            'public_url' => null,
        ]);

        $this->assertDatabaseHas('social_product_settings', [
            'product_id' => $product->id,
            'is_published' => 0,
        ]);
    }

    public function test_ajax_publish_validation_failure_returns_422_json(): void
    {
        $product = $this->createProduct(['image' => null]);
        $user = $this->createAuthorizedUser();

        $response = $this->actingAs($user)
            ->postJson(route('socialcommerce.togglePublish', $product->id), []);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'product_id' => $product->id,
            'published' => false,
        ]);
        $this->assertNotEmpty($response->json('message'));
    }

    public function test_ajax_publish_not_found_product_returns_404_json(): void
    {
        $user = $this->createAuthorizedUser();

        $response = $this->actingAs($user)
            ->postJson(route('socialcommerce.togglePublish', 999999999), []);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_unauthorized_user_cannot_toggle_publish(): void
    {
        $product = $this->createProduct();
        $unauthorizedUser = $this->createUnauthorizedUser();

        $response = $this->actingAs($unauthorizedUser)
            ->postJson(route('socialcommerce.togglePublish', $product->id), []);

        $response->assertStatus(403);
    }

    public function test_publish_unpublish_publish_cycle_is_consistent(): void
    {
        $product = $this->createProduct();
        $user = $this->createAuthorizedUser();

        // 1. Publish
        $res1 = $this->actingAs($user)->postJson(route('socialcommerce.togglePublish', $product->id), []);
        $res1->assertOk()->assertJson(['published' => true]);

        // 2. Unpublish
        $res2 = $this->actingAs($user)->postJson(route('socialcommerce.togglePublish', $product->id), ['unpublish' => 1]);
        $res2->assertOk()->assertJson(['published' => false]);

        // 3. Publish again
        $res3 = $this->actingAs($user)->postJson(route('socialcommerce.togglePublish', $product->id), []);
        $res3->assertOk()->assertJson(['published' => true]);
    }

    public function test_non_ajax_publish_fallback_redirects_with_session(): void
    {
        $product = $this->createProduct();
        $user = $this->createAuthorizedUser();

        $response = $this->actingAs($user)
            ->post(route('socialcommerce.togglePublish', $product->id), []);

        $response->assertRedirect();
        $response->assertSessionHas('message');

        $this->assertDatabaseHas('social_product_settings', [
            'product_id' => $product->id,
            'is_published' => 1,
        ]);
    }

    public function test_catalog_view_contains_ajax_data_attributes(): void
    {
        $product = $this->createProduct();
        $this->publish($product);
        $user = $this->createAuthorizedUser();

        $response = $this->actingAs($user)->get(route('socialcommerce.catalog'));

        $response->assertOk();
        $response->assertSee('data-product-id="' . $product->id . '"', false);
        $response->assertSee('class="sc-toggle-form"', false);
        $response->assertSee('sc-status-badge', false);
    }

    public function test_catalog_filtered_by_published_and_unpublished(): void
    {
        $p1 = $this->createProduct();
        $p2 = $this->createProduct();
        $this->publish($p1);
        $user = $this->createAuthorizedUser();

        // Published filter
        $pubRes = $this->actingAs($user)->get(route('socialcommerce.catalog', ['filter' => 'published']));
        $pubRes->assertOk();
        $pubRes->assertSee($p1->name);
        $pubRes->assertDontSee($p2->name);

        // Unpublished filter
        $unpubRes = $this->actingAs($user)->get(route('socialcommerce.catalog', ['filter' => 'unpublished']));
        $unpubRes->assertOk();
        $unpubRes->assertSee($p2->name);
        $unpubRes->assertDontSee($p1->name);
    }

    public function test_rapid_multiple_product_publishes(): void
    {
        $products = [
            $this->createProduct(),
            $this->createProduct(),
            $this->createProduct(),
        ];
        $user = $this->createAuthorizedUser();

        foreach ($products as $p) {
            $res = $this->actingAs($user)->postJson(route('socialcommerce.togglePublish', $p->id), []);
            $res->assertOk();
            $res->assertJson(['published' => true, 'product_id' => $p->id]);
        }

        foreach ($products as $p) {
            $this->assertDatabaseHas('social_product_settings', [
                'product_id' => $p->id,
                'is_published' => 1,
            ]);
        }
    }

    private function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Ajax Product ' . uniqid(),
            'code' => 'AP-' . strtoupper(uniqid()),
            'type' => 'standard',
            'slug' => 'ajax-product-' . uniqid(),
            'barcode_symbology' => 'C128',
            'unit_id' => $this->unit->id,
            'purchase_unit_id' => $this->unit->id,
            'sale_unit_id' => $this->unit->id,
            'category_id' => $this->category->id,
            'cost' => 10,
            'price' => 20,
            'qty' => 10,
            'image' => 'ajax-product.png',
            'product_details' => 'Ajax test product details',
            'is_active' => true,
            'is_online' => false,
        ], $overrides));
    }

    private function publish(Product $product, bool $published = true): SocialProductSetting
    {
        return SocialProductSetting::create([
            'product_id' => $product->id,
            'is_published' => $published,
            'social_title' => $product->name,
            'social_description' => $product->product_details,
        ]);
    }

    private function createAuthorizedUser(): User
    {
        $permissions = [
            Permission::findOrCreate('module.socialcommerce.access', 'web'),
            Permission::findOrCreate('manage social catalogue', 'web'),
        ];
        $role = Role::create(['name' => 'ajax-role-' . uniqid(), 'guard_name' => 'web', 'is_active' => true]);
        $role->givePermissionTo($permissions);

        $user = User::create([
            'name' => 'Ajax Authorized Admin',
            'email' => 'ajax-admin-' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
            'phone' => '1555' . random_int(1000000, 9999999),
            'role_id' => $role->id,
            'is_active' => true,
            'is_deleted' => false,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function createUnauthorizedUser(): User
    {
        $role = Role::create(['name' => 'unauth-role-' . uniqid(), 'guard_name' => 'web', 'is_active' => true]);

        $user = User::create([
            'name' => 'Unauthorized User',
            'email' => 'unauth-' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
            'phone' => '1555' . random_int(1000000, 9999999),
            'role_id' => $role->id,
            'is_active' => true,
            'is_deleted' => false,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
