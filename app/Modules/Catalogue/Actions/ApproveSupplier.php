<?php

namespace App\Modules\Catalogue\Actions;

use App\Modules\Catalogue\Capabilities\CatalogueCapabilities;
use App\Modules\Catalogue\Queries\SupplierChecklist;
use App\Modules\Catalogue\Queries\SupplierRecord;
use App\Modules\Catalogue\Queries\Suppliers;
use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Shared\StatusTables\TransitionRefused;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * An Admin approves a Supplier whose required checklist items are all Clear
 * (FR-14, FR-81). The checklist and its settings are re-read under lock; a
 * Supplier with any required item at Needs action is refused in words,
 * naming the items. Audited with who and when.
 */
#[NeedsCapability(CatalogueCapabilities::APPROVE_SUPPLIERS)]
final class ApproveSupplier extends SupplierApprovalAction
{
    public const MOVE = 'approve';

    public const AUDIT_ACTION = 'catalogue.supplier_approved';

    /**
     * @throws TransitionRefused when a required item needs action, or the Supplier is already approved
     * @throws AuthorizationException when $actor does not hold the capability
     * @throws ModelNotFoundException when the Supplier does not exist
     */
    public function handle(Model $actor, string $supplierId): SupplierRecord
    {
        $this->transaction(function () use ($actor, $supplierId): void {
            [$current, $supplier, $checklist] = $this->lockForApproval($actor, $supplierId, self::MOVE);
            $count = self::needingAction($checklist);

            if ($count > 0) {
                $noun = $count === 1 ? 'required item needs' : 'required items need';

                throw $this->refuse($supplier, 'status', self::MOVE, "Nothing was changed: {$count} {$noun} action: ".SupplierChecklist::namesNeedingAction($checklist).'. Approve it with a reason instead, or complete the details first.');
            }

            $this->approve($current, $supplier, self::MOVE, self::AUDIT_ACTION, null);
        });

        return app(Suppliers::class)->find($supplierId) ?? throw new ModelNotFoundException;
    }
}
