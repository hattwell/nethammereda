<?php

namespace App\Filament\Resources\Concerns;

use App\Enums\UserRole;

trait HidesFromHostedDemoViewer
{
    public static function canViewAny(): bool
    {
        return ! (
            config('lunch.hosted_demo')
            && auth('web')->user()?->role === UserRole::DemoViewer
        ) && parent::canViewAny();
    }
}
