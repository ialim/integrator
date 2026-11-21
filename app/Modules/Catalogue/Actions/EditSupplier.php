<?php

namespace App\Modules\Catalogue\Actions;

use App\Modules\Catalogue\Capabilities\CatalogueCapabilities;
use App\Modules\Catalogue\Queries\SupplierRecord;
use App\Modules\Catalogue\Queries\Suppliers;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Shared\StatusTables\TransitionRefused;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Operations or an Admin edits a Supplier's details, with a required reason.
 * Only the details given change. An edit never un-approves a Supplier: an
 * approved one stays approved, and its checklist simply shows any item that
 * now needs action. Audited with the reason; contacts only as "changed".
 */
#[NeedsCapability(CatalogueCapabilities::RECORD_SUPPLIERS)]
final class EditSupplier extends SupplierAction
{
    public const AUDIT_ACTION = 'catalogue.supplier_edited';

    /**
     * @param  array<string, mixed>  $details  detail => value, only the details to change
     *
     * @throws InvalidSupplierDetails when a detail or the reason is refused, or nothing would change
     * @throws TransitionRefused when the Supplier takes no edits in its state
     * @throws AuthorizationException when $actor does not hold the capability
     * @throws ModelNotFoundException when the Supplier does not exist
     */
    public function handle(Model $actor, string $supplierId, array $details, string $reason): SupplierRecord
    {
        $this->transaction(function () use ($actor, $supplierId, $details, $reason): void {
            [$current, $supplier] = $this->lockActorAndSupplier($actor, $supplierId);
            $this->move($supplier, 'status', $supplier->status->editMove());
            $clean = $this->details($details);
            $reason = $this->reason($reason);

            $supplier->forceFill($clean);

            if (! $supplier->isDirty()) {
                throw new InvalidSupplierDetails('reason', 'Nothing was changed: every detail is as it was. Change a detail, or cancel.');
            }

            $this->saveWithReason(Actor::staff((string) $current->getKey()), self::AUDIT_ACTION, $supplier, $reason);
        });

        return app(Suppliers::class)->find($supplierId) ?? throw new ModelNotFoundException;
    }
}
