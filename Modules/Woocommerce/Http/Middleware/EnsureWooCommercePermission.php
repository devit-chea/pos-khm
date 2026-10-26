<?php

namespace Modules\Woocommerce\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureWooCommercePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        abort_unless($user && (in_array((int)$user->role_id,[1,2],true) || $user->can($permission)), 403);
        return $next($request);
    }
}
