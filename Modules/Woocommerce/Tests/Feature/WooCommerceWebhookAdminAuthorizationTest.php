<?php

namespace Modules\Woocommerce\Tests\Feature;

use App\Models\Roles;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Woocommerce\Models\WoocommerceSetting;
use Tests\TestCase;

class WooCommerceWebhookAdminAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_owner_admin_can_rotate_without_secret_disclosure_and_disconnect(): void
    {
        $user = $this->createIsolatedAdminUser();
        $setting = WoocommerceSetting::query()->create(['sync_enabled' => true]);
        $secret = 'synthetic-admin-secret-12345';
        $this->actingAs($user)->post(route('woocommerce.webhook-secret'), ['action' => 'replace', 'webhook_secret' => $secret, 'rotation_overlap_seconds' => 3600])->assertRedirect();
        $setting->refresh();
        self::assertSame($secret, $setting->webhook_secret_current);
        self::assertNotSame($secret, DB::table('woocommerce_settings')->where('id', $setting->id)->value('webhook_secret_current'));
        self::assertStringNotContainsString($secret, DB::table('woocommerce_webhook_security_events')->where('woocommerce_setting_id', $setting->id)->value('metadata'));
        $html = view('woocommerce::includes.api_settings.webhook-settings', ['woocommerce_setting' => $setting])->render();
        self::assertStringNotContainsString($secret, $html);
        $this->actingAs($user)->post(route('woocommerce.webhook-secret'), ['action' => 'disconnect'])->assertRedirect();
        self::assertFalse($setting->fresh()->sync_enabled);
        self::assertNull($setting->fresh()->webhook_secret_current);
    }

    public function test_guest_portal_and_custom_role_are_denied_sensitive_action(): void
    {
        WoocommerceSetting::query()->create(['sync_enabled' => false]);
        $this->post('/woocommerce/webhook-secret', ['action' => 'revoke'])->assertRedirect();
        $this->withoutMiddleware([\App\Http\Middleware\Common::class, \App\Http\Middleware\Active::class]);
        foreach ([5, $this->customRole()->id] as $roleId) {
            $user = $this->userForRole($roleId);
            $this->actingAs($user)->post('/woocommerce/webhook-secret', ['action' => 'revoke'])->assertForbidden();
        }
    }

    public function test_validation_rejects_blank_and_unbounded_rotation_without_flashing_secret(): void
    {
        $user = $this->createIsolatedAdminUser();
        WoocommerceSetting::query()->create(['sync_enabled' => false]);
        $secret = 'synthetic-invalid-secret-12345';
        $response = $this->from('/woocommerce/settings')->actingAs($user)->post('/woocommerce/webhook-secret', ['action' => 'replace', 'webhook_secret' => $secret, 'rotation_overlap_seconds' => 86401]);
        $response->assertRedirect('/woocommerce/settings')->assertSessionHasErrors('rotation_overlap_seconds');
        self::assertArrayNotHasKey('webhook_secret', session()->getOldInput());
        $this->from('/woocommerce/settings')->actingAs($user)->post('/woocommerce/webhook-secret', ['action' => 'replace', 'webhook_secret' => '', 'rotation_overlap_seconds' => 0])->assertSessionHasErrors('webhook_secret');
    }

    private function customRole(): Roles
    {
        return Roles::create(['name' => 'Webhook custom '.bin2hex(random_bytes(3)), 'description' => 'test', 'guard_name' => 'web', 'is_active' => true]);
    }

    private function userForRole(int $roleId): User
    {
        return User::create(['name' => 'Webhook user', 'email' => bin2hex(random_bytes(4)).'@example.test', 'password' => bcrypt('password'), 'phone' => '555'.random_int(10000, 99999), 'role_id' => $roleId, 'is_active' => true, 'is_deleted' => false]);
    }
}
