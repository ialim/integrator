<?php

namespace App\Modules\Catalogue\Locks;

use App\Modules\Catalogue\Models\Supplier;
use App\Modules\Shared\Locks\LockAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Locks Suppliers' rows (the `supplier` aggregate root) until the Action's
 * transaction ends, in ascending id order. Every Supplier write takes it, so
 * an edit and an approval of the same Supplier never interleave.
 */
final class LockSupplier extends LockAction
{
    public const ROOT = 'supplier';

    public static function root(): string
    {
        return self::ROOT;
    }

    protected function table(): string
    {
        return (new Supplier)->getTable();
    }

    /**
     * @throws ModelNotFoundException when a Supplier does not exist
     */
    public function handle(string ...$ids): void
    {
        $this->lockRows(array_values($ids));
    }
}
