<?php

namespace App\Modules\Shared\StatusTables;

use App\Modules\Shared\Queries\AuditEntry;
use RuntimeException;

/**
 * A move refused by an Action (AD-4): the transition table does not allow it
 * from the current state (a lost race, usually), or a rule forbids it. It
 * carries the current state and the last recorded change, so a screen can say
 * who changed the record and when instead of showing an error; under /api/v1 it
 * becomes a 409 with the current resource.
 */
final class TransitionRefused extends RuntimeException
{
    private function __construct(
        string $message,
        public readonly string $subjectType,
        public readonly string $subjectId,
        public readonly string $move,
        public readonly StatusTable $current,
        public readonly ?AuditEntry $lastChange,
        public readonly ?string $reason,
    ) {
        parent::__construct($message);
    }

    /** The table does not allow $move from the current state. */
    public static function notAllowed(string $subjectType, string $subjectId, string $move, StatusTable $current, ?AuditEntry $lastChange): self
    {
        return new self(
            "The move [{$move}] is not allowed from {$current->label()} for {$subjectType} {$subjectId}.",
            $subjectType, $subjectId, $move, $current, $lastChange, null,
        );
    }

    /** A rule forbids the move; $reason says why, in words for the screen. */
    public static function because(string $reason, string $subjectType, string $subjectId, string $move, StatusTable $current, ?AuditEntry $lastChange = null): self
    {
        return new self($reason, $subjectType, $subjectId, $move, $current, $lastChange, $reason);
    }
}
