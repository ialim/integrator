<?php

namespace App\Modules\Catalogue\Models;

use App\Modules\Catalogue\StatusTables\ProductRangeStatus;
use App\Modules\Shared\Support\HasUuidV7;
use Illuminate\Database\Eloquent\Model;

/**
 * A Product Range (FR-13): a name, the compliance its Products need, and
 * whether it is Active or Retired. Its Platform Markup belongs to Pricing
 * (AD-7). Written only by the Catalogue range Actions.
 *
 * @property string $id
 * @property string $name
 * @property RangeCompliance $compliance
 * @property ProductRangeStatus $status
 */
final class ProductRange extends Model
{
    use HasUuidV7;

    protected $table = 'product_ranges';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'compliance' => RangeCompliance::class,
            'status' => ProductRangeStatus::class,
        ];
    }
}
