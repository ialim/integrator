<?php

namespace App\Modules\Catalogue\Queries;

/**
 * One onboarding checklist item as it stands for one Supplier: Clear or
 * Needs action, in words, and whether approval needs it.
 */
final class ChecklistItemState
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly bool $required,
        public readonly bool $clear,
        public readonly string $detail,
    ) {}

    /** "Clear", "Clear · 14 days", "Needs action: missing TIN, address". */
    public function words(): string
    {
        return $this->detail;
    }

    /** A required item that is not Clear: it holds back "Approve the supplier". */
    public function holdsBackApproval(): bool
    {
        return $this->required && ! $this->clear;
    }
}
