<?php

namespace Modules\Woocommerce\Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Woocommerce\Contracts\WooCommerceClientFactory;
use Modules\Woocommerce\Models\WoocommerceSetting;
use Modules\Woocommerce\Tests\Fakes\FakeWooCommerceClient;
use Modules\Woocommerce\Tests\Fakes\FakeWooCommerceClientFactory;
use RuntimeException;
use Tests\TestCase;

class WooCommerceCategoryE2ETest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('USER_VERIFIED=1');
        $_ENV['USER_VERIFIED'] = '1';
        Category::query()->update(['is_sync_disable' => 1]);
        WoocommerceSetting::query()->delete();
        WoocommerceSetting::query()->create([
            'sync_enabled' => true,
            'woocomerce_app_url' => 'https://93.184.216.34/store',
            'woocomerce_consumer_key' => 'synthetic-key',
            'woocomerce_consumer_secret' => 'synthetic-secret',
        ]);
        $this->withoutMiddleware([\App\Http\Middleware\Common::class, \App\Http\Middleware\Active::class]);
        $this->actingAs($this->createIsolatedAdminUser());
    }

    public function test_new_parent_and_child_export_persists_exact_mappings_after_all_remote_calls(): void
    {
        $parent = Category::query()->create(['name' => 'Parent '.bin2hex(random_bytes(3)), 'is_active' => 1]);
        $child = Category::query()->create(['name' => 'Child '.bin2hex(random_bytes(3)), 'parent_id' => $parent->id, 'is_active' => 1]);
        $fake = (new FakeWooCommerceClient)
            ->queue('POST', 'products/categories/batch', (object) ['create' => [(object) ['id' => 701], (object) ['id' => 702]]])
            ->queue('POST', 'products/categories/batch', (object) ['update' => [(object) ['id' => 702]]]);
        app()->instance(WooCommerceClientFactory::class, new FakeWooCommerceClientFactory($fake));

        $this->post(route('woocommerce.syncCategories', absolute: false))->assertOk()->assertJson(['success' => 1]);

        self::assertSame(701, (int) $parent->fresh()->woocommerce_category_id);
        self::assertSame(702, (int) $child->fresh()->woocommerce_category_id);
        self::assertSame([0, 0], $fake->requestTransactionDepths);
        self::assertSame(701, $fake->requests[1]['parameters']['update'][0]['parent']);
    }

    public function test_replay_updates_existing_mapping_without_duplication(): void
    {
        $category = Category::query()->create(['name' => 'Mapped '.bin2hex(random_bytes(3)), 'is_active' => 1, 'woocommerce_category_id' => 811]);
        $fake = (new FakeWooCommerceClient)->queue('POST', 'products/categories/batch', (object) ['update' => [(object) ['id' => 811]]]);
        app()->instance(WooCommerceClientFactory::class, new FakeWooCommerceClientFactory($fake));

        $this->post(route('woocommerce.syncCategories', absolute: false))->assertOk()->assertJson(['success' => 1]);
        self::assertSame(811, (int) $category->fresh()->woocommerce_category_id);
        self::assertSame(1, Category::query()->where('woocommerce_category_id', 811)->count());
        self::assertSame([0], $fake->requestTransactionDepths);
    }

    public function test_malformed_remote_response_does_not_persist_mapping_or_report_success(): void
    {
        $category = Category::query()->create(['name' => 'Malformed '.bin2hex(random_bytes(3)), 'is_active' => 1]);
        $fake = (new FakeWooCommerceClient)->queue('POST', 'products/categories/batch', (object) ['create' => []]);
        app()->instance(WooCommerceClientFactory::class, new FakeWooCommerceClientFactory($fake));

        $response = $this->post(route('woocommerce.syncCategories', absolute: false))->assertOk()->assertJson(['success' => 0]);
        self::assertNull($category->fresh()->woocommerce_category_id);
        self::assertStringNotContainsString('synthetic-secret', $response->getContent());
    }

    public function test_transport_or_authentication_failure_leaves_every_mapping_unchanged(): void
    {
        $category = Category::query()->create(['name' => 'Failure '.bin2hex(random_bytes(3)), 'is_active' => 1]);
        $fake = (new FakeWooCommerceClient)->queue('POST', 'products/categories/batch', new RuntimeException('remote body synthetic-secret'));
        app()->instance(WooCommerceClientFactory::class, new FakeWooCommerceClientFactory($fake));

        $response = $this->post(route('woocommerce.syncCategories', absolute: false))->assertOk()->assertJson(['success' => 0]);
        self::assertNull($category->fresh()->woocommerce_category_id);
        self::assertStringNotContainsString('remote body', $response->getContent());
        self::assertStringNotContainsString('synthetic-secret', $response->getContent());
    }

    public function test_sync_disabled_blocks_before_client_creation(): void
    {
        WoocommerceSetting::query()->latest()->first()->update(['sync_enabled' => false]);
        $fake = new FakeWooCommerceClient;
        app()->instance(WooCommerceClientFactory::class, new FakeWooCommerceClientFactory($fake));

        $this->post(route('woocommerce.syncCategories', absolute: false))
            ->assertOk()->assertJson(['success' => 0, 'msg' => 'WooCommerce synchronization is disabled.']);
        self::assertSame([], $fake->requests);
    }
}
