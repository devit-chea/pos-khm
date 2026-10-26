<?php

namespace Modules\Woocommerce\Tests\Unit;

use Modules\Woocommerce\Contracts\WooCommerceClientFactory;
use Modules\Woocommerce\Models\WoocommerceSetting;
use Modules\Woocommerce\Services\Legacy\AutomatticWooCommerceClientFactory;
use Modules\Woocommerce\Tests\Fakes\FakeWooCommerceClient;
use Modules\Woocommerce\Tests\Fakes\FakeWooCommerceClientFactory;
use RuntimeException;
use Tests\TestCase;

class WooCommerceClientBoundaryTest extends TestCase
{
    public function test_production_factory_is_bound_and_controller_does_not_construct_vendor_client(): void
    {
        self::assertInstanceOf(AutomatticWooCommerceClientFactory::class, app(WooCommerceClientFactory::class));
        $source = file_get_contents(module_path('Woocommerce', 'Http/Controllers/WoocommerceController.php'));
        self::assertStringNotContainsString('Automattic\\WooCommerce\\Client', $source);
        self::assertStringNotContainsString('new Client(', $source);
    }

    public function test_fake_returns_deterministic_pages_remote_ids_and_records_requests(): void
    {
        $client = (new FakeWooCommerceClient)->queue('GET', 'products', [(object) ['id' => 41]], []);
        self::assertSame(41, $client->get('products', ['page' => 1])[0]->id);
        self::assertSame([], $client->get('products', ['page' => 2]));
        self::assertSame([1, 2], array_column(array_column($client->requests, 'parameters'), 'page'));
    }

    public function test_fake_propagates_transport_auth_validation_and_rate_limit_failures(): void
    {
        foreach (['transport', 'authentication', 'validation', 'rate-limit'] as $kind) {
            $client = (new FakeWooCommerceClient)->queue('POST', 'products/batch', new RuntimeException($kind));
            try {
                $client->post('products/batch', ['create' => []]);
                self::fail('The queued failure was silently converted to success.');
            } catch (RuntimeException $error) {
                self::assertSame($kind, $error->getMessage());
            }
        }
    }

    public function test_factory_is_replaceable_without_credentials_or_network(): void
    {
        $fake = new FakeWooCommerceClientFactory(new FakeWooCommerceClient);
        app()->instance(WooCommerceClientFactory::class, $fake);
        $setting = new WoocommerceSetting;
        $setting->id = 91;
        self::assertSame($fake->client, app(WooCommerceClientFactory::class)->make($setting));
        self::assertSame([91], $fake->settingIds);
    }
}
