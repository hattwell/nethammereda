<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case User = 'user';
    case DemoViewer = 'demo_viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Администратор',
            self::User => 'Пользователь',
            self::DemoViewer => 'Просмотр демо',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];

        foreach (self::cases() as $role) {
            $labels[$role->value] = $role->label();
        }

        return $labels;
    }
}
