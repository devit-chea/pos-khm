<?php

namespace Modules\Woocommerce\Services\Webhooks;

use Illuminate\Support\Facades\DB;
use Modules\Woocommerce\Models\WoocommerceSetting;

class WebhookSecretLifecycle
{
    public function replace(WoocommerceSetting $setting, string $secret, int $overlapSeconds = 3600): void
    {
        if ($secret === '') {
            return;
        }

        DB::transaction(function () use ($setting, $secret, $overlapSeconds): void {
            $setting->refresh();
            $setting->webhook_secret_previous = $overlapSeconds > 0 ? $setting->webhook_secret_current : null;
            $setting->webhook_previous_expires_at = $setting->webhook_secret_previous ? now()->addSeconds(min($overlapSeconds, 86400)) : null;
            $setting->webhook_secret_current = $secret;
            $setting->webhook_key_version = max(1, (int) $setting->webhook_key_version + 1);
            $setting->webhook_revoked_at = null;
            $setting->webhook_configuration_error = null;
            $setting->save();
            DB::table('woocommerce_webhook_security_events')->insert(['woocommerce_setting_id' => $setting->id, 'actor_id' => auth()->id(), 'event_type' => $setting->webhook_secret_previous ? 'secret_rotated' : 'secret_configured', 'metadata' => json_encode(['key_version' => $setting->webhook_key_version, 'overlap_seconds' => $setting->webhook_secret_previous ? min($overlapSeconds, 86400) : 0]), 'created_at' => now()]);
        });
    }

    public function revoke(WoocommerceSetting $setting): void
    {
        $setting->forceFill([
            'webhook_secret_current' => null,
            'webhook_secret_previous' => null,
            'webhook_previous_expires_at' => null,
            'webhook_revoked_at' => now(),
            'webhook_configuration_error' => null,
        ])->save();
        DB::table('woocommerce_webhook_security_events')->insert(['woocommerce_setting_id' => $setting->id, 'actor_id' => auth()->id(), 'event_type' => 'secret_revoked', 'metadata' => json_encode(['key_version' => (int) $setting->webhook_key_version]), 'created_at' => now()]);
    }
}
