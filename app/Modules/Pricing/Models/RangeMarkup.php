<?php

namespace App\Modules\Pricing\Models;

use App\Modules\Shared\Support\HasUuidV7;
use Illuminate\Database\Eloquent\Model;

/**
 * A Product Range's Platform Markup (AD-7): a percentage over Supplier Cost,
 * numeric(7,4), never below zero. Written only by SetRangeMarkup, under the
 * range's lock.
 *
 * @property string $id
 * @property string $product_range_id
 * @property string $markup_percent
 */
final class RangeMarkup extends Model
{
    use HasUuidV7;

    protected $table = 'range_markups';
}
