<?php

namespace App\Modules\Catalogue\Actions;

use App\Modules\Catalogue\Locks\LockOnboardingSetting;
use App\Modules\Catalogue\Models\Supplier;
use App\Modules\Catalogue\Queries\ChecklistItemState;
use App\Modules\Catalogue\Queries\SupplierChecklist;
use App\Modules\Catalogue\StatusTables\SupplierStatus;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\StatusTables\TransitionRefused;
use Illuminate\Database\Eloquent\Model;

/**
 * What the two ways an Admin approves a Supplier share: the actor, the
 * Supplier and then every checklist item locked, in the fixed order, so the
 * checklist and its settings are read as they stand at the moment of
 * approval; an approved Supplier refused in words; and the approval saved
 * with who and when, audited.
 */
abstract class SupplierApprovalAction extends SupplierAction
{
    public function __construct(private readonly Clock $clock) {}

    /**
     * Locks what an approval reads, and returns the actor, the Supplier and
     * its checklist, all as read under the locks.
     *
     * @return array{0: Model, 1: Supplier, 2: list<ChecklistItemState>}
     *
     * @throws TransitionRefused when the Supplier is already approved
     */
    protected function lockForApproval(Model $actor, string $supplierId, string $move): array
    {
        [$current, $supplier] = $this->lockActorAndSupplier($actor, $supplierId);
        app(LockOnboardingSetting::class)->all();

        if ($supplier->status === SupplierStatus::Approved) {
            throw $this->refuse($supplier, 'status', $move, "{$supplier->legal_name} is already approved. Nothing was changed.");
        }

        return [$current, $supplier, SupplierChecklist::evaluate($supplier, SupplierChecklist::items())];
    }

    /** Moves the Supplier to Approved, names who and when, and audits it. */
    protected function approve(Model $current, Supplier $supplier, string $move, string $action, ?string $reason): void
    {
        $this->move($supplier, 'status', $move);
        $supplier->forceFill([
            'approved_by' => (string) $current->getKey(),
            'approved_at' => $this->clock->now(),
            'approval_reason' => $reason,
        ]);
        $this->saveAndAudit(Actor::staff((string) $current->getKey()), $action, $supplier);
    }

    /**
     * @param  list<ChecklistItemState>  $checklist
     */
    protected static function needingAction(array $checklist): int
    {
        return count(array_filter($checklist, fn (ChecklistItemState $item): bool => $item->holdsBackApproval()));
    }
}
