<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockHostedDemoViewerApi
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(
            config('lunch.hosted_demo') && $request->user()?->role === UserRole::DemoViewer,
            403,
        );

        return $next($request);
    }
}
