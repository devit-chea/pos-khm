<?php

namespace Modules\Woocommerce\Services\Webhooks;

use Illuminate\Contracts\Encryption\DecryptException;
use Modules\Woocommerce\Exceptions\WebhookRejected;
use Modules\Woocommerce\Models\WoocommerceSetting;

class WooCommerceSignatureVerifier
{
    /** @return array{version:int,source:string} */
    public function verify(WoocommerceSetting $setting, string $rawBody, ?string $supplied): array
    {
        if ($setting->webhook_revoked_at) {
            throw new WebhookRejected('connection_unavailable', 404);
        }

        if (!is_string($supplied) || $supplied === '') {
            throw new WebhookRejected('signature_missing', 401);
        }

        $decoded = base64_decode($supplied, true);
        if ($decoded === false || strlen($decoded) !== 32 || base64_encode($decoded) !== $supplied) {
            throw new WebhookRejected('signature_malformed', 401);
        }

        $candidates = [];
        $configurationError = null;
        try {
            if (is_string($setting->webhook_secret_current) && $setting->webhook_secret_current !== '') {
                $candidates[] = [$setting->webhook_secret_current, (int) $setting->webhook_key_version, 'current'];
            }
        } catch (DecryptException) {
            $configurationError = 'webhook_secret_decryption_failed';
        }
        try {
            $previousEligible = $setting->webhook_previous_expires_at?->isFuture() === true;
        } catch (\Throwable) {
            $previousEligible = false;
            $configurationError = 'webhook_rotation_metadata_invalid';
        }
        if ($previousEligible) {
            try {
                if (is_string($setting->webhook_secret_previous) && $setting->webhook_secret_previous !== '') {
                    $candidates[] = [$setting->webhook_secret_previous, max(1, (int) $setting->webhook_key_version - 1), 'previous'];
                }
            } catch (DecryptException) {
                $configurationError = 'webhook_secret_decryption_failed';
            }
        }
        if ($configurationError) {
            $setting->newQuery()->whereKey($setting->id)->update(['webhook_configuration_error' => $configurationError]);
        }

        if ($candidates === []) {
            throw new WebhookRejected('connection_unavailable', 404);
        }

        $match = null;
        foreach ($candidates as [$secret, $version, $source]) {
            $valid = hash_equals(hash_hmac('sha256', $rawBody, $secret, true), $decoded);
            if ($valid && $match === null) {
                $match = ['version' => $version, 'source' => $source];
            }
        }

        if ($match === null) {
            throw new WebhookRejected('signature_invalid', 401);
        }

        if (!$configurationError && $setting->webhook_configuration_error) {
            $setting->newQuery()->whereKey($setting->id)->update(['webhook_configuration_error' => null]);
        }

        return $match;
    }
}
