<?php

namespace App\Modules\Shared\Queries;

use Carbon\CarbonImmutable;

/**
 * One audit row, as other modules read it.
 */
final class AuditEntry
{
    /**
     * @param  array<string, mixed>  $changes
     */
    public function __construct(
        public readonly string $id,
        public readonly int $sequence,
        public readonly CarbonImmutable $occurredAt,
        public readonly string $actorType,
        public readonly ?string $actorId,
        public readonly ?string $actingAs,
        public readonly ?string $actingFor,
        public readonly string $action,
        public readonly string $subjectType,
        public readonly ?string $subjectId,
        public readonly array $changes,
        public readonly ?string $requestId,
    ) {}
}
