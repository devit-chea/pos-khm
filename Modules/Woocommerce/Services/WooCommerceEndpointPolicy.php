<?php

namespace Modules\Woocommerce\Services;

use Illuminate\Validation\ValidationException;

class WooCommerceEndpointPolicy
{
    public function validateAndNormalize(string $url): string
    {
        $url = rtrim(trim($url), '/');
        $parts = parse_url($url);

        if (!is_array($parts)
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])) {
            $this->reject();
        }

        $host = strtolower(rtrim($parts['host'], '.'));
        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) {
            $this->reject();
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP)
            ? [$host]
            : array_values(array_unique(array_merge(
                gethostbynamel($host) ?: [],
                $this->ipv6Addresses($host)
            )));

        if ($addresses === []) {
            $this->reject('The WooCommerce host could not be resolved.');
        }

        foreach ($addresses as $address) {
            if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $this->reject('The WooCommerce host must not resolve to a private or reserved network.');
            }
        }

        return $url;
    }

    private function ipv6Addresses(string $host): array
    {
        if (!function_exists('dns_get_record')) {
            return [];
        }

        return array_values(array_filter(array_column(dns_get_record($host, DNS_AAAA) ?: [], 'ipv6')));
    }

    private function reject(string $message = 'Use an HTTPS WooCommerce URL without credentials, query parameters, or fragments.'): never
    {
        throw ValidationException::withMessages(['woocomerce_app_url' => $message]);
    }
}
