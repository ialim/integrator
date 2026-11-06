<?php

namespace App\Modules\Shared\Locks;

/**
 * The fixed global order in which Actions lock aggregate roots (AD-4). An
 * Action locks every root it changes up front, earlier roots in this list
 * before later ones and, within one root, rows in ascending id order, so two
 * Actions can never wait on each other's locks. Each new root takes its place
 * here when its module's `Lock{Root}` action arrives.
 */
final class LockOrder
{
    public const ROOTS = [
        'staff_member',
    ];

    public static function rank(string $root): int
    {
        $rank = array_search($root, self::ROOTS, true);

        if ($rank === false) {
            throw new \LogicException("The lock root [{$root}] is not in the fixed lock order; add it to ".self::class.'::ROOTS.');
        }

        return $rank;
    }
}
