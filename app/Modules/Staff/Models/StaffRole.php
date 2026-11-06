<?php

namespace App\Modules\Staff\Models;

enum StaffRole: string
{
    case Admin = 'admin';
    case Finance = 'finance';
    case Operations = 'operations';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Finance => 'Finance',
            self::Operations => 'Operations',
        };
    }
}
