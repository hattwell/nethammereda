<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleHostedDemoOrders
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('lunch.hosted_demo') && ! $request->isMethod('GET')) {
            $key = 'hosted-demo:orders:'.($request->user()?->id ?? $request->ip());
            abort_unless(RateLimiter::attempt($key, 30, fn (): bool => true, 60), 429);
        }

        return $next($request);
    }
}
