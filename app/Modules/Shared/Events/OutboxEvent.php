<?php

namespace App\Modules\Shared\Events;

/**
 * Something that happened to an aggregate, written to the outbox in the same
 * transaction as the change (AD-4) and relayed to its listeners afterwards.
 * The payload holds identifiers and facts, never personal values in clear.
 */
interface OutboxEvent
{
    /** A stable name, stored with the event: "staff.account_created". */
    public static function type(): string;

    public function aggregateType(): string;

    public function aggregateId(): string;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;
}
