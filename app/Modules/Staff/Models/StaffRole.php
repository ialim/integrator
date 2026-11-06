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

    /**
     * What the role looks after, in words (PRD FR-52). Story 1.5 adds the
     * capability registry and the Support duties switch on Operations accounts.
     */
    public function duties(): string
    {
        return match ($this) {
            self::Admin => 'Everything, including staff accounts and settings',
            self::Finance => 'Money: payments, credit notes and refunds',
            self::Operations => 'Goods, content and customer cases',
        };
    }
}
