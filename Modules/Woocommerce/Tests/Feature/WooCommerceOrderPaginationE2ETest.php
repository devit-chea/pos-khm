<?php

namespace Modules\Woocommerce\Tests\Feature;

use App\Models\Biller;
use App\Models\CustomerGroup;
use App\Models\Sale;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Woocommerce\Contracts\WooCommerceClientFactory;
use Modules\Woocommerce\Models\WoocommerceSetting;
use Modules\Woocommerce\Tests\Fakes\FakeWooCommerceClient;
use Modules\Woocommerce\Tests\Fakes\FakeWooCommerceClientFactory;
use RuntimeException;
use Tests\TestCase;

class WooCommerceOrderPaginationE2ETest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        WoocommerceSetting::query()->delete();
        WoocommerceSetting::query()->create([
            'sync_enabled' => true, 'woocomerce_app_url' => 'https://93.184.216.34/store',
            'woocomerce_consumer_key' => 'synthetic-key', 'woocomerce_consumer_secret' => 'synthetic-secret',
            'customer_group_id' => CustomerGroup::query()->where('is_active', 1)->firstOrFail()->id,
            'warehouse_id' => Warehouse::query()->where('is_active', 1)->firstOrFail()->id,
            'biller_id' => Biller::query()->where('is_active', 1)->firstOrFail()->id,
            'order_status_pending' => 1, 'order_status_processing' => 1, 'order_status_on_hold' => 1,
            'order_status_completed' => 1, 'order_status_draft' => 1,
        ]);
        $this->withoutMiddleware([\App\Http\Middleware\Common::class, \App\Http\Middleware\Active::class]);
        $this->actingAs($this->createIsolatedAdminUser());
    }

    public function test_empty_one_page_and_multiple_pages_complete_before_local_transaction(): void
    {
        $scenarios = [
            [[]],
            [[$this->cancelled(1001)], []],
            [[$this->cancelled(1002)], [$this->cancelled(1003)], []],
        ];
        $fake = new FakeWooCommerceClient;
        foreach ($scenarios as $pages) {
            $fake->queue('GET', 'orders', ...$pages);
        }
        $this->fake($fake);
        $expectedRequests = 0;
        foreach ($scenarios as $pages) {
            $this->post(route('woocommerce.syncOrders', absolute: false))->assertOk()->assertJson(['success' => 1]);
            $expectedRequests += count($pages);
        }
        self::assertCount($expectedRequests, $fake->requests);
        self::assertSame(array_fill(0, $expectedRequests, 0), $fake->requestTransactionDepths);
    }

    public function test_exactly_one_hundred_data_pages_succeeds_with_bounded_terminal_probe(): void
    {
        $pages = [];
        for ($page = 1; $page <= 100; $page++) $pages[] = [$this->cancelled(2000 + $page)];
        $pages[] = [];
        $fake = (new FakeWooCommerceClient)->queue('GET', 'orders', ...$pages);
        $this->fake($fake);
        $this->post(route('woocommerce.syncOrders', absolute: false))->assertOk()->assertJson(['success' => 1]);
        self::assertCount(101, $fake->requests);
        self::assertSame(101, $fake->requests[100]['parameters']['page']);
        self::assertSame([0], array_values(array_unique($fake->requestTransactionDepths)));
    }

    public function test_more_than_one_hundred_pages_fails_without_partial_import(): void
    {
        $remoteId = 3100;
        $pages = [];
        for ($page = 1; $page <= 101; $page++) $pages[] = [$this->cancelled($remoteId + $page)];
        $before = Sale::query()->count();
        $fake = (new FakeWooCommerceClient)->queue('GET', 'orders', ...$pages);
        $this->fake($fake);
        $this->post(route('woocommerce.syncOrders', absolute: false))->assertOk()->assertJson(['success' => 0]);
        self::assertSame($before, Sale::query()->count());
        self::assertCount(101, $fake->requests);
    }

    public function test_intermediate_transport_authentication_and_malformed_page_fail_without_stale_data(): void
    {
        foreach ([new RuntimeException('authentication secret response'), (object) ['not' => 'a page']] as $failure) {
            $before = Sale::query()->count();
            $fake = (new FakeWooCommerceClient)->queue('GET', 'orders', [$this->cancelled(4001)], $failure);
            $this->fake($fake);
            $response = $this->post(route('woocommerce.syncOrders', absolute: false))->assertOk()->assertJson(['success' => 0]);
            self::assertSame($before, Sale::query()->count());
            self::assertStringNotContainsString('authentication secret response', $response->getContent());
        }
    }

    private function cancelled(int $id): object
    {
        return (object) ['id' => $id, 'status' => 'cancelled'];
    }

    private function fake(FakeWooCommerceClient $fake): void
    {
        app()->instance(WooCommerceClientFactory::class, new FakeWooCommerceClientFactory($fake));
    }
}
