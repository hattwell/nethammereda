<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictHostedDemoViewer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        if (
            config('lunch.hosted_demo')
            && $user instanceof User
            && $user->role === UserRole::DemoViewer
        ) {
            // Filament actions run through Livewire POST, not the resource URL.
            // Blocking all viewer writes here also covers custom actions and
            // prevents accidental future write actions from becoming public.
            if ($request->is('admin') || $request->is('admin/*')) {
                abort_unless(
                    $request->isMethod('GET')
                    && in_array($request->path(), ['admin', 'admin/menu-items', 'admin/menu-categories'], true),
                    403,
                );
            }

            abort_if($request->is('livewire/update') || $request->is('livewire/*') || $request->is('livewire-*/update'), 403);
        }

        return $next($request);
    }
}
