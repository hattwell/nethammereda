<?php

namespace App\Filament\Resources\Concerns;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;

trait ForbidsHostedDemoViewerWrites
{
    private static function isHostedDemoViewer(): bool
    {
        return config('lunch.hosted_demo') && auth('web')->user()?->role === UserRole::DemoViewer;
    }

    public static function canCreate(): bool
    {
        return ! static::isHostedDemoViewer() && parent::canCreate();
    }

    public static function canEdit(Model $record): bool
    {
        return ! static::isHostedDemoViewer() && parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        return ! static::isHostedDemoViewer() && parent::canDelete($record);
    }

    public static function canDeleteAny(): bool
    {
        return ! static::isHostedDemoViewer() && parent::canDeleteAny();
    }
}
