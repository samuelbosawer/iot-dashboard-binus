<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Support = 'support';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Support => 'Support',
        };
    }
}
