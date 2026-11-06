<?php

namespace App\Modules\Shared\Events;

use Carbon\CarbonImmutable;

/**
 * One outbox event as a listener receives it. `id` is the event id that
 * listeners use for their delivery keys; `sequence` counts the aggregate's
 * events from 1.
 */
final class OutboxMessage
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $aggregateType,
        public readonly string $aggregateId,
        public readonly int $sequence,
        public readonly array $payload,
        public readonly CarbonImmutable $occurredAt,
        public readonly int $attempts,
    ) {}
}
