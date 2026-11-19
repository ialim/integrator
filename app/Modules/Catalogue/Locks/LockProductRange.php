<?php

namespace App\Modules\Catalogue\Locks;

use App\Modules\Catalogue\Models\ProductRange;
use App\Modules\Shared\Locks\LockAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Locks Product Ranges' rows (the `product_range` aggregate root, which also
 * covers the range's Platform Markup in Pricing) until the Action's
 * transaction ends, in ascending id order. Pricing locks a range through this
 * before it changes the range's markup, so a range and its markup are never
 * written apart. Story 2.3's Product writes must take this lock on the range
 * a Product is added to, and on both ranges a Product moves between, so a
 * range cannot be retired while a Product is joining it.
 */
final class LockProductRange extends LockAction
{
    public const ROOT = 'product_range';

    public static function root(): string
    {
        return self::ROOT;
    }

    protected function table(): string
    {
        return (new ProductRange)->getTable();
    }

    /**
     * @throws ModelNotFoundException when a range does not exist
     */
    public function handle(string ...$ids): void
    {
        $this->lockRows(array_values($ids));
    }
}
