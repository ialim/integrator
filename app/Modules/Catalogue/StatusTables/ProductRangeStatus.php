<?php

namespace App\Modules\Catalogue\StatusTables;

use App\Modules\Shared\StatusTables\StatusTable;

/**
 * A Product Range's states and the moves between them (AD-4): Active, then
 * Retired, which is final. A retired range keeps its name, its History and
 * its markup, takes no new Products and no further edits.
 */
enum ProductRangeStatus: string implements StatusTable
{
    case Active = 'active';
    case Retired = 'retired';

    public static function moves(): array
    {
        return [
            'rename' => ['from' => [self::Active], 'to' => self::Active],
            'set_compliance' => ['from' => [self::Active], 'to' => self::Active],
            'retire' => ['from' => [self::Active], 'to' => self::Retired],
        ];
    }

    /** A new range's state. */
    public static function initial(): self
    {
        return self::Active;
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Retired => 'Retired',
        };
    }
}
