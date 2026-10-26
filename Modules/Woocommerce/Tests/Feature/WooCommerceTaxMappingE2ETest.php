<?php

namespace Modules\Woocommerce\Tests\Feature;

use App\Models\Tax;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Woocommerce\Contracts\WooCommerceClientFactory;
use Modules\Woocommerce\Models\WoocommerceSetting;
use Modules\Woocommerce\Tests\Fakes\FakeWooCommerceClient;
use Modules\Woocommerce\Tests\Fakes\FakeWooCommerceClientFactory;
use RuntimeException;
use Tests\TestCase;

class WooCommerceTaxMappingE2ETest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        WoocommerceSetting::query()->delete();
        WoocommerceSetting::query()->create([
            'sync_enabled' => true, 'woocomerce_app_url' => 'https://93.184.216.34/store',
            'woocomerce_consumer_key' => 'synthetic-key', 'woocomerce_consumer_secret' => 'synthetic-secret',
        ]);
        $this->withoutMiddleware([\App\Http\Middleware\Common::class, \App\Http\Middleware\Active::class]);
        $this->actingAs($this->createIsolatedAdminUser());
    }

    public function test_valid_mapping_and_replay_validate_remote_and_local_ids(): void
    {
        $tax = Tax::query()->create(['name' => 'Woo tax '.bin2hex(random_bytes(3)), 'rate' => 17.5, 'is_active' => 1]);
        $fake = (new FakeWooCommerceClient)->queue(
            'GET', 'taxes',
            [(object) ['id' => 601, 'name' => 'Remote']], [],
            [(object) ['id' => 601, 'name' => 'Remote']], []
        );
        $this->fake($fake);
        foreach ([1, 2] as $run) {
            $this->post(route('woocommerce.mapTaxRates', absolute: false), ['taxes' => [$tax->id => 601]])
                ->assertOk()->assertJson(['success' => 1]);
            self::assertSame(601, (int) $tax->fresh()->woocommerce_tax_id);
        }
        self::assertSame([0, 0, 0, 0], $fake->requestTransactionDepths);
    }

    public function test_missing_remote_or_local_tax_fails_without_mutation(): void
    {
        $tax = Tax::query()->create(['name' => 'Stale tax '.bin2hex(random_bytes(3)), 'rate' => 5, 'is_active' => 1]);
        foreach ([[$tax->id => 999], [2147483647 => 601]] as $mapping) {
            $tax->update(['woocommerce_tax_id' => null]);
            $fake = $this->pagedTaxes(601);
            $this->fake($fake);
            $this->post(route('woocommerce.mapTaxRates', absolute: false), ['taxes' => $mapping])->assertOk()->assertJson(['success' => 0]);
            self::assertNull($tax->fresh()->woocommerce_tax_id);
        }
    }

    public function test_remote_failure_is_sanitized_and_does_not_change_mapping(): void
    {
        $tax = Tax::query()->create(['name' => 'Failure tax '.bin2hex(random_bytes(3)), 'rate' => 9, 'is_active' => 1, 'woocommerce_tax_id' => 55]);
        $fake = (new FakeWooCommerceClient)->queue('GET', 'taxes', new RuntimeException('authentication body synthetic-secret'));
        $this->fake($fake);
        $response = $this->post(route('woocommerce.mapTaxRates', absolute: false), ['taxes' => [$tax->id => 601]])->assertOk()->assertJson(['success' => 0]);
        self::assertSame(55, (int) $tax->fresh()->woocommerce_tax_id);
        self::assertStringNotContainsString('authentication body', $response->getContent());
        self::assertStringNotContainsString('synthetic-secret', $response->getContent());
    }

    public function test_sync_disabled_blocks_remote_lookup(): void
    {
        WoocommerceSetting::query()->latest()->first()->update(['sync_enabled' => false]);
        $fake = new FakeWooCommerceClient;
        $this->fake($fake);
        $this->post(route('woocommerce.mapTaxRates', absolute: false), ['taxes' => []])
            ->assertOk()->assertJson(['success' => 0, 'msg' => 'WooCommerce synchronization is disabled.']);
        self::assertSame([], $fake->requests);
    }

    private function pagedTaxes(int $id): FakeWooCommerceClient
    {
        return (new FakeWooCommerceClient)->queue('GET', 'taxes', [(object) ['id' => $id, 'name' => 'Remote']], []);
    }

    private function fake(FakeWooCommerceClient $fake): void
    {
        app()->instance(WooCommerceClientFactory::class, new FakeWooCommerceClientFactory($fake));
    }
}
