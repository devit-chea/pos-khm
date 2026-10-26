<?php

namespace Modules\Ecommerce\Services\Payments;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentProviderCredentials
{
    public function get(string $provider): array
    {
        $service = DB::table('external_services')
            ->whereRaw('LOWER(name) = ?', [strtolower($provider)])
            ->where('type', 'payment')
            ->where('active', 1)
            ->first();

        if (!$service) {
            throw ValidationException::withMessages(['payment_mode' => __('db.Payment provider is unavailable')]);
        }

        [$keys, $values] = array_pad(explode(';', (string) $service->details, 2), 2, '');
        $keys = array_map(fn ($key) => strtolower(str_replace(' ', '_', trim($key))), explode(',', $keys));
        $values = array_map('trim', explode(',', $values));

        return array_filter(array_combine($keys, array_pad($values, count($keys), '')) ?: []);
    }
}
