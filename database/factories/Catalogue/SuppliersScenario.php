<?php

namespace Database\Factories\Catalogue;

use App\Modules\Catalogue\Actions\ApproveSupplier;
use App\Modules\Catalogue\Actions\ApproveSupplierWithReason;
use App\Modules\Catalogue\Queries\Suppliers;
use App\Modules\Staff\Models\StaffMember;
use Database\Factories\ActionFactory;
use Database\Factories\Staff\FirstAdminFactory;

/**
 * Three Suppliers, all through the Catalogue Actions by the founder's Admin:
 * one approved against a clear checklist, one approved with a reason while
 * its counterfeit term is missing, and one not approved with gaps, so the
 * Suppliers pages have each state to check. Creates the founder's Admin
 * first when there is none; adds nothing when the Suppliers are already
 * there. Test values only.
 */
final class SuppliersScenario
{
    public const APPROVED = 'Northgate Distribution Ltd';

    public const APPROVED_WITH_REASON = 'Sentinel Imports Nigeria Ltd';

    public const WITH_GAPS = 'Brightwave Wholesale Ltd';

    public const REASON = 'Counterfeit term agreed by phone; the written term is promised by 31 December.';

    public function __invoke(): void
    {
        ActionFactory::assertAvailable();

        $admin = StaffMember::query()->where('is_founder', true)->first() ?? FirstAdminFactory::new()->create();
        $existing = array_map(fn ($supplier) => $supplier->legalName, app(Suppliers::class)->list());

        if (! in_array(self::APPROVED, $existing, true)) {
            $supplier = SupplierFactory::new()->create(['actor' => $admin]);
            app(ApproveSupplier::class)->handle($admin, $supplier->id);
        }

        if (! in_array(self::APPROVED_WITH_REASON, $existing, true)) {
            $supplier = SupplierFactory::new()->create(['actor' => $admin, 'details' => [
                ...SupplierFactory::COMPLETE,
                'legal_name' => self::APPROVED_WITH_REASON,
                'trade_name' => 'Sentinel Imports',
                'counterfeit_terms' => null,
            ]]);
            app(ApproveSupplierWithReason::class)->handle($admin, $supplier->id, self::REASON);
        }

        if (! in_array(self::WITH_GAPS, $existing, true)) {
            SupplierFactory::new()->create(['actor' => $admin, 'details' => [
                'legal_name' => self::WITH_GAPS,
                'returns_terms' => 'Unopened goods within 14 days.',
                'doa_terms' => 'Replaced on proof.',
                'doa_window_days' => '14',
            ]]);
        }
    }
}
