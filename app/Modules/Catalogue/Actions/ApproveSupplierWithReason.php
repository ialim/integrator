<?php

namespace App\Modules\Catalogue\Actions;

use App\Modules\Catalogue\Capabilities\CatalogueCapabilities;
use App\Modules\Catalogue\Queries\SupplierRecord;
use App\Modules\Catalogue\Queries\Suppliers;
use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Shared\StatusTables\TransitionRefused;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * An Admin approves a Supplier while some required checklist item still
 * needs action, with a written reason: the override (FR-14). The reason is
 * stored on the Supplier and shown there, and the approval is audited with
 * who and when. A Supplier whose required items are all Clear is refused:
 * it is approved the ordinary way, with no reason to give.
 */
#[NeedsCapability(CatalogueCapabilities::APPROVE_SUPPLIERS)]
final class ApproveSupplierWithReason extends SupplierApprovalAction
{
    public const MOVE = 'approve_with_reason';

    public const AUDIT_ACTION = 'catalogue.supplier_approved_with_reason';

    /**
     * @throws InvalidSupplierDetails when the reason is missing
     * @throws TransitionRefused when every required item is Clear, or the Supplier is already approved
     * @throws AuthorizationException when $actor does not hold the capability
     * @throws ModelNotFoundException when the Supplier does not exist
     */
    public function handle(Model $actor, string $supplierId, string $reason): SupplierRecord
    {
        $this->transaction(function () use ($actor, $supplierId, $reason): void {
            [$current, $supplier, $checklist] = $this->lockForApproval($actor, $supplierId, self::MOVE);
            $reason = $this->reason($reason);

            if (self::needingAction($checklist) === 0) {
                throw $this->refuse($supplier, 'status', self::MOVE, "Nothing was changed: every required item for {$supplier->legal_name} is clear, so approve it with \"Approve the supplier\" instead.");
            }

            $this->approve($current, $supplier, self::MOVE, self::AUDIT_ACTION, $reason);
        });

        return app(Suppliers::class)->find($supplierId) ?? throw new ModelNotFoundException;
    }
}
