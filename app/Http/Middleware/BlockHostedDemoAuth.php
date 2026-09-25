<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockHostedDemoAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(config('lunch.hosted_demo'), 404);

        return $next($request);
    }
}
