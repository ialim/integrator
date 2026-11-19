<?php

namespace App\Modules\Catalogue\Models;

/**
 * The compliance a Product Range's Products need before sale in Nigeria: NCC
 * type approval, SONCAP, or none. A Product in a flagged range carries a
 * compliance reference (Story 2.3).
 */
enum RangeCompliance: string
{
    case None = 'none';
    case NccTypeApproval = 'ncc_type_approval';
    case Soncap = 'soncap';

    public function label(): string
    {
        return match ($this) {
            self::None => 'None',
            self::NccTypeApproval => 'NCC type approval',
            self::Soncap => 'SONCAP',
        };
    }

    /**
     * @return array<string, string> value => words, in the order the form offers them
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
