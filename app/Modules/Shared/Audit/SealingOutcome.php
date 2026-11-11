<?php

namespace App\Modules\Shared\Audit;

/**
 * What a scheduled sealing run came to: the seal result, or the class of the
 * error that stopped it, and whether the overdue alert was raised.
 */
final class SealingOutcome
{
    public function __construct(
        public readonly ?SealResult $result,
        public readonly ?string $error,
        public readonly bool $overdue,
    ) {}

    public function failed(): bool
    {
        return $this->error !== null;
    }
}
