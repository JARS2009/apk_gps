<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminHasGranja
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isSuperAdmin()) {
            // Cache the count on the request so HandleInertiaRequests can reuse it
            $count = $user->granjas()->count();
            $request->attributes->set('_granjas_count', $count);

            if ($count === 0 && ! $request->routeIs('sin-acceso')) {
                return redirect()->route('sin-acceso');
            }
        }

        return $next($request);
    }
}
