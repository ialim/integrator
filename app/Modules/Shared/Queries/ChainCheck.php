<?php

namespace App\Modules\Shared\Queries;

/**
 * The result of checking the audit hash chain: intact up to
 * {@see self::$lastSealedSequence} (null when nothing is sealed yet), or
 * broken first at {@see self::$brokenAt} for {@see self::$problem}.
 * {@see self::$unsealed} entries after the last seal wait for the next run.
 */
final class ChainCheck
{
    /** A sealed entry's stored values no longer match its seal, or a seal no longer links to the one before. */
    public const CHANGED = 'changed';

    /** A seal names an entry that is no longer there. */
    public const MISSING = 'missing';

    /** An entry sits inside the sealed part of the chain with no seal of its own. */
    public const EXTRA = 'extra';

    public function __construct(
        public readonly bool $intact,
        public readonly ?int $lastSealedSequence,
        public readonly int $unsealed,
        public readonly ?int $brokenAt = null,
        public readonly ?string $problem = null,
    ) {}
}
