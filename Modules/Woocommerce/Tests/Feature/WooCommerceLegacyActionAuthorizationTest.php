<?php

namespace Modules\Woocommerce\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Modules\Woocommerce\Models\WoocommerceSetting;
use Tests\TestCase;

class WooCommerceLegacyActionAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    private const MUTATIONS = [
        'woocommerce.store' => 'woocommerce.manage',
        'woocommerce.webhook-secret' => 'woocommerce.manage',
        'woocommerce.syncCategories' => 'woocommerce.sync',
        'woocommerce.resetSyncedCategory' => 'woocommerce.manage',
        'woocommerce.syncProducts' => 'woocommerce.sync',
        'woocommerce.resetSyncedProduct' => 'woocommerce.manage',
        'woocommerce.mapTaxRates' => 'woocommerce.manage',
        'woocommerce.syncOrders' => 'woocommerce.sync',
    ];

    public function test_every_legacy_mutation_is_post_only_and_has_module_and_granular_guards(): void
    {
        foreach (self::MUTATIONS as $name => $permission) {
            $route = Route::getRoutes()->getByName($name);
            self::assertNotNull($route, $name);
            self::assertSame(['POST'], $route->methods(), $name);
            $middleware = $route->gatherMiddleware();
            self::assertContains('module.access:woocommerce', $middleware, $name);
            self::assertContains("woocommerce.permission:{$permission}", $middleware, $name);
            self::assertContains('web', $middleware, $name);
        }
    }

    public function test_guest_is_denied_and_get_cannot_mutate_any_legacy_action(): void
    {
        foreach (array_keys(self::MUTATIONS) as $name) {
            $uri = route($name, absolute: false);
            $this->post($uri)->assertRedirect();
            $this->get($uri)->assertStatus(405);
        }
    }

    public function test_owner_can_reach_sync_actions_but_disabled_state_blocks_before_remote_io(): void
    {
        WoocommerceSetting::query()->create(['sync_enabled' => false]);
        $this->withoutMiddleware([\App\Http\Middleware\Common::class, \App\Http\Middleware\Active::class]);
        $this->actingAs($this->createIsolatedAdminUser());

        foreach (['woocommerce.syncCategories', 'woocommerce.syncProducts', 'woocommerce.syncOrders'] as $name) {
            $this->post(route($name, absolute: false))
                ->assertOk()
                ->assertJson(['success' => 0, 'msg' => 'WooCommerce synchronization is disabled.']);
        }
    }

    public function test_public_webhook_has_no_staff_or_module_permission_middleware(): void
    {
        $route = Route::getRoutes()->getByName('woocommerce.webhooks.receive');
        self::assertNotNull($route);
        $middleware = implode('|', $route->gatherMiddleware());
        self::assertStringNotContainsString('auth', $middleware);
        self::assertStringNotContainsString('module.access', $middleware);
        self::assertStringNotContainsString('woocommerce.permission', $middleware);
    }
}
