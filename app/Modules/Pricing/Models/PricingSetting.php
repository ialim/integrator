<?php

namespace App\Modules\Pricing\Models;

use App\Modules\Shared\Support\HasUuidV7;
use Illuminate\Database\Eloquent\Model;

/**
 * A staff-tunable Pricing setting, one row per key: today only the margin
 * floor, a percentage over Supplier Cost. Changed only through an Action,
 * never an `.env` entry.
 *
 * @property string $id
 * @property string $key
 * @property string $percent
 */
final class PricingSetting extends Model
{
    use HasUuidV7;

    public const MARGIN_FLOOR = 'margin_floor';

    protected $table = 'pricing_settings';
}
