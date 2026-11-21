<?php

namespace App\Modules\Catalogue\Actions;

use App\Modules\Catalogue\Capabilities\CatalogueCapabilities;
use App\Modules\Catalogue\Models\Supplier;
use App\Modules\Catalogue\Queries\SupplierRecord;
use App\Modules\Catalogue\Queries\Suppliers;
use App\Modules\Catalogue\StatusTables\SupplierStatus;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Capabilities\NeedsCapability;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

/**
 * Operations or an Admin adds a Supplier (FR-14, FR-81). Only the legal name
 * is required; every other detail may be missing and shows "Not stated". It
 * is saved Not approved and audited, its contacts only as "changed" (AD-21).
 */
#[NeedsCapability(CatalogueCapabilities::RECORD_SUPPLIERS)]
final class AddSupplier extends SupplierAction
{
    public const AUDIT_ACTION = 'catalogue.supplier_added';

    /**
     * @param  array<string, mixed>  $details  detail => value, as {@see SupplierAction::DETAILS} names them
     *
     * @throws InvalidSupplierDetails when a detail is refused
     * @throws AuthorizationException when $actor does not hold the capability
     */
    public function handle(Model $actor, array $details): SupplierRecord
    {
        $id = $this->transaction(function () use ($actor, $details): string {
            $current = $this->lockActor($actor);
            $clean = $this->details(['legal_name' => $details['legal_name'] ?? null, ...$details]);

            $supplier = new Supplier;
            $supplier->forceFill([...$clean, 'status' => SupplierStatus::initial()]);
            $this->saveAndAudit(Actor::staff((string) $current->getKey()), self::AUDIT_ACTION, $supplier);

            return $supplier->id;
        });

        return app(Suppliers::class)->find($id) ?? throw new \LogicException("The Supplier {$id} was not saved.");
    }
}
