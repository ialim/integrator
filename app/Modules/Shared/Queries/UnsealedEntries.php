<?php

namespace App\Modules\Shared\Queries;

use Carbon\CarbonImmutable;

/**
 * The audit entries after the last seal: how many, and the oldest of them.
 */
final class UnsealedEntries
{
    public function __construct(
        public readonly int $count,
        public readonly ?int $oldestSequence,
        public readonly ?CarbonImmutable $oldestOccurredAt,
    ) {}
}
