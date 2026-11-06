<?php

namespace App\Modules\Shared\Locks;

use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Which aggregate roots the running Action holds locked (AD-4). A container
 * singleton: the Action base opens and closes the scope around its
 * transaction, `Lock{Root}` actions lock rows through it in the fixed global
 * order, and nested Actions assert that the root they change is already held
 * instead of locking it themselves.
 *
 * Locks are taken only by the outermost Action, directly in its own
 * transaction: never in a nested Action and never inside a savepoint, whose
 * rollback would release the row locks while the registry still counted them
 * as held. A row counts as held only once its SELECT ... FOR UPDATE has
 * returned it.
 */
final class LockRegistry
{
    /** @var array<string, list<string>> root => ids held, in the order taken */
    private array $held = [];

    private int $depth = 0;

    /** The connection's transaction level when the outermost Action began. */
    private int $baseLevel = 0;

    public function enter(): void
    {
        if ($this->depth === 0) {
            $this->baseLevel = DB::transactionLevel();
        }

        $this->depth++;
    }

    public function leave(): void
    {
        $this->depth = max(0, $this->depth - 1);

        if ($this->depth === 0) {
            $this->held = [];
        }
    }

    /**
     * Checks a lock about to be taken and returns the ids not already held, in
     * ascending order. Refuses a lock outside the outermost Action's own
     * transaction, or one taken out of the fixed order. Records nothing: call
     * {@see self::record()} once the rows are locked.
     *
     * @param  list<string>  $ids
     * @return list<string>
     */
    public function pending(string $root, array $ids): array
    {
        if ($this->depth === 0 || DB::transactionLevel() === 0) {
            throw new LogicException("A [{$root}] lock is taken only inside an Action's transaction.");
        }

        if ($this->depth > 1) {
            throw new LogicException("A nested Action never takes a [{$root}] lock: the outermost Action locks every root up front, and the nested one asserts it.");
        }

        if (DB::transactionLevel() !== $this->baseLevel + 1) {
            throw new LogicException("A [{$root}] lock is taken directly in the Action's transaction, never inside a savepoint, whose rollback would release it.");
        }

        $rank = LockOrder::rank($root);

        foreach (array_keys($this->held) as $heldRoot) {
            if (LockOrder::rank($heldRoot) > $rank) {
                throw new LogicException("Locks are taken in the fixed order: [{$root}] must be locked before [{$heldRoot}].");
            }
        }

        $held = $this->held[$root] ?? [];
        $new = array_values(array_diff(array_unique(array_map('strtolower', $ids)), $held));
        sort($new, SORT_STRING);

        if ($new !== [] && $held !== [] && strcmp($new[0], max($held)) < 0) {
            throw new LogicException("[{$root}] rows are locked in ascending id order, all in one call where possible; {$new[0]} comes before a row already held.");
        }

        return $new;
    }

    /**
     * Records rows the Action's SELECT ... FOR UPDATE has just locked.
     *
     * @param  list<string>  $ids
     */
    public function record(string $root, array $ids): void
    {
        $this->held[$root] = array_values(array_unique([...($this->held[$root] ?? []), ...array_map('strtolower', $ids)]));
    }

    public function isHeld(string $root, string $id): bool
    {
        return in_array(strtolower($id), $this->held[$root] ?? [], true);
    }

    /**
     * A nested Action never locks: it asserts the calling Action already holds the lock.
     */
    public function assertHeld(string $root, string $id): void
    {
        if (! $this->isHeld($root, $id)) {
            throw new LogicException("The [{$root}] row {$id} must be locked by the calling Action first.");
        }
    }
}
