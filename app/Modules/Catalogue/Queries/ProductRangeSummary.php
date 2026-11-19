<?php

namespace App\Modules\Catalogue\Queries;

/**
 * One Product Range as other modules read it: its words, its state, how many
 * Products it holds, and the audit subject its History is kept under.
 */
final class ProductRangeSummary
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $compliance,
        public readonly string $complianceLabel,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly bool $isRetired,
        public readonly int $productCount,
    ) {}

    /**
     * The range's own audit subject, for its History.
     *
     * @return array{0: string, 1: string}
     */
    public function auditSubject(): array
    {
        return [ProductRanges::AUDIT_SUBJECT, $this->id];
    }
}
