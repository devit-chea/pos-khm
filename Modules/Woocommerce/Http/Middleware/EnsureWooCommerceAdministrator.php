<?php

namespace Modules\Woocommerce\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWooCommerceAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() && (int) $request->user()->role_id === 1, 403);

        return $next($request);
    }
}
