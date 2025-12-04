<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreIsSetup
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip check for setup wizard route
        if ($request->routeIs('store.setup-wizard') || $request->routeIs('store.setup-step')) {
            return $next($request);
        }

        // Skip check for logout and settings routes
        if ($request->routeIs('logout') || $request->routeIs('settings.*')) {
            return $next($request);
        }

        // Redirect to setup if store not configured
        if ($user && (! $user->store || ! $user->store->is_setup_complete)) {
            return redirect()->route('store.setup-wizard');
        }

        return $next($request);
    }
}
