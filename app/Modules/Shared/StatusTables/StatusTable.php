<?php

namespace App\Modules\Shared\StatusTables;

/**
 * A declared transition table (AD-4): an aggregate's states, usually a backed
 * enum, and the named moves between them. An Action checks each move against
 * the table before it writes; a move the table does not allow from the current
 * state is refused with {@see TransitionRefused}.
 */
interface StatusTable
{
    /**
     * Each named move, the states it may start from and the state it ends in.
     *
     * @return array<string, array{from: list<static>, to: static}>
     */
    public static function moves(): array;

    /** The state in words, for screens and messages: "Active". */
    public function label(): string;
}
