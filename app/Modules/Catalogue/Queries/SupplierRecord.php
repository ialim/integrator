<?php

namespace App\Modules\Catalogue\Queries;

use Carbon\CarbonImmutable;

/**
 * One Supplier as the console reads it: every detail (null when not stated),
 * its approval, how many Products it supplies, and its onboarding checklist.
 * Staff-only: never on an Integrator surface or API.
 */
final class SupplierRecord
{
    /**
     * @param  array<string, string|int|null>  $details  detail => value, every detail staff record
     * @param  list<ChecklistItemState>  $checklist
     */
    public function __construct(
        public readonly string $id,
        public readonly string $legalName,
        public readonly array $details,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly bool $isApproved,
        public readonly ?string $approvedBy,
        public readonly ?CarbonImmutable $approvedAt,
        public readonly ?string $approvalReason,
        public readonly int $productCount,
        public readonly array $checklist,
    ) {}

    /** A detail as stated, or null when it is missing. */
    public function detail(string $field): string|int|null
    {
        return $this->details[$field] ?? null;
    }

    public function approvedWithReason(): bool
    {
        return $this->isApproved && $this->approvalReason !== null;
    }

    /**
     * The required items that need action.
     *
     * @return list<ChecklistItemState>
     */
    public function needingAction(): array
    {
        return array_values(array_filter($this->checklist, fn (ChecklistItemState $item): bool => $item->holdsBackApproval()));
    }

    public function isClear(): bool
    {
        return $this->needingAction() === [];
    }

    public function requiredCount(): int
    {
        return count(array_filter($this->checklist, fn (ChecklistItemState $item): bool => $item->required));
    }

    public function clearCount(): int
    {
        return $this->requiredCount() - count($this->needingAction());
    }

    /** "3 of 5 clear": the required items only. */
    public function progressWords(): string
    {
        return "{$this->clearCount()} of {$this->requiredCount()} clear";
    }

    /** "Off because 2 required items need action: Business details and Counterfeit chargeback." */
    public function offBecause(): string
    {
        $count = count($this->needingAction());
        $noun = $count === 1 ? 'required item needs' : 'required items need';

        return "Off because {$count} {$noun} action: ".SupplierChecklist::namesNeedingAction($this->checklist).'.';
    }

    /**
     * The Supplier's own audit subject, for its History.
     *
     * @return array{0: string, 1: string}
     */
    public function auditSubject(): array
    {
        return [Suppliers::AUDIT_SUBJECT, $this->id];
    }
}
