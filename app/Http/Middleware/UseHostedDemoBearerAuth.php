<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UseHostedDemoBearerAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('lunch.hosted_demo') && $request->is('api/*')) {
            // Sanctum normally tries the web session before the bearer token.
            // The public Filament viewer shares the same origin, so API auth
            // must use only per-visitor tokens in the hosted demo.
            config(['sanctum.guard' => []]);
            Auth::forgetGuards();
        }

        return $next($request);
    }
}
