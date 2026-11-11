<?php

namespace App\Modules\Shared\Audit;

/**
 * What one sealing run did: how many entries it sealed and the last sealed
 * sequence, or that it sealed nothing because writers were still open
 * ({@see self::$busy}); the next run tries again.
 */
final class SealResult
{
    public function __construct(
        public readonly int $sealed,
        public readonly ?int $lastSealedSequence,
        public readonly bool $busy = false,
    ) {}
}
