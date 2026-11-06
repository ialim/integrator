<?php

namespace App\Modules\Shared\StatusTables;

use LogicException;

/**
 * Reads a {@see StatusTable}: whether a move is allowed from a state, and where it ends.
 */
final class Transitions
{
    /**
     * The state the move ends in, or null when the table does not allow it from $current.
     */
    public static function target(StatusTable $current, string $move): ?StatusTable
    {
        $moves = $current::moves();

        if (! isset($moves[$move])) {
            throw new LogicException('The transition table '.$current::class." declares no move [{$move}].");
        }

        return in_array($current, $moves[$move]['from'], true) ? $moves[$move]['to'] : null;
    }
}
