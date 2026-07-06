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

        if ($user && ! $user->isSuperAdmin() && $user->granjas()->count() === 0) {
            if ($request->routeIs('sin-acceso')) {
                return $next($request);
            }

            return redirect()->route('sin-acceso');
        }

        return $next($request);
    }
}
