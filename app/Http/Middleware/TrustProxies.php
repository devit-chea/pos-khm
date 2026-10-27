<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array|string
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Trusted proxies can also come from TRUSTED_PROXIES ("*" or comma-separated IPs),
     * e.g. behind the Caddy reverse proxy in docker-compose.prod.yml so https:// URLs are generated.
     *
     * @return array|string|null
     */
    protected function proxies()
    {
        $fromEnv = env('TRUSTED_PROXIES');

        if (! empty($fromEnv)) {
            return $fromEnv === '*' ? '*' : array_map('trim', explode(',', $fromEnv));
        }

        return parent::proxies();
    }
}
