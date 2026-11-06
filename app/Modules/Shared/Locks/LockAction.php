<?php

namespace App\Modules\Shared\Locks;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Base of every module's `Lock{Root}` action (AD-4): takes row locks on one
 * aggregate root's table with SELECT ... FOR UPDATE, in ascending id order,
 * through the {@see LockRegistry}, so the order is checked and nested Actions
 * can assert the lock is held. Only the outermost Action locks, directly in
 * its own transaction. Locks last until the Action's transaction ends.
 */
abstract class LockAction
{
    /** The root's name in {@see LockOrder::ROOTS}. */
    abstract public static function root(): string;

    /** The root's table. */
    abstract protected function table(): string;

    /**
     * @param  list<string>  $ids
     *
     * @throws ModelNotFoundException when a row does not exist
     */
    protected function lockRows(array $ids): void
    {
        $registry = app(LockRegistry::class);
        $toLock = $registry->pending(static::root(), $ids);

        if ($toLock === []) {
            return;
        }

        $found = array_map(
            fn (mixed $id): string => strtolower((string) $id),
            DB::table($this->table())->whereIn('id', $toLock)->orderBy('id')->lockForUpdate()->pluck('id')->all(),
        );

        if (count($found) !== count($toLock)) {
            throw new ModelNotFoundException('No ['.static::root().'] row '.implode(', ', array_diff($toLock, $found)).'.');
        }

        // Held only now that the rows are locked.
        $registry->record(static::root(), $found);
    }
}
