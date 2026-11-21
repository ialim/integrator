<?php

namespace App\Modules\Catalogue\Queries;

use App\Modules\Catalogue\Actions\SupplierAction;
use App\Modules\Catalogue\Models\OnboardingChecklistItem;
use App\Modules\Catalogue\Models\Supplier;
use App\Modules\Catalogue\StatusTables\SupplierStatus;
use LogicException;

/**
 * Reads Suppliers for the console and for other modules: the list, with
 * each Supplier's checklist progress and the filter chips' counts; one
 * Supplier's record and checklist; the checklist settings; how many Products
 * a Supplier supplies; and the dead-on-arrival window its Products give
 * businesses. Read-only. Staff-only: no Integrator surface or API reads it.
 */
final class Suppliers
{
    /** The audit trail's subject type for a Supplier. */
    public const AUDIT_SUBJECT = 'suppliers';

    /** The audit trail's subject type for a checklist item's setting. */
    public const SETTINGS_AUDIT_SUBJECT = 'onboarding_checklist_items';

    /** The platform's own dead-on-arrival window (FR-59); a Supplier's shorter one wins. */
    public const PLATFORM_DOA_DAYS = 30;

    public const ALL = 'all';

    public const NEEDS_ATTENTION = 'needs_attention';

    public const APPROVED = 'approved';

    public const NOT_APPROVED = 'not_approved';

    /** The list's filter chips, in order, with their words. */
    public const CHIPS = [
        self::ALL => 'All',
        self::NEEDS_ATTENTION => 'Needs attention',
        self::APPROVED => 'Approved',
        self::NOT_APPROVED => 'Not approved',
    ];

    /**
     * Every Supplier the chip lets through, by legal name. Until Story 2.20,
     * "Needs attention" is a Supplier with any required item at Needs action.
     *
     * @return list<SupplierRecord>
     */
    public function list(string $chip = self::ALL): array
    {
        if (! array_key_exists($chip, self::CHIPS)) {
            throw new LogicException("The Suppliers list has no chip [{$chip}].");
        }

        return array_values(array_filter($this->all(), fn (SupplierRecord $supplier): bool => self::passes($supplier, $chip)));
    }

    /**
     * How many Suppliers each chip lets through.
     *
     * @return array<string, int> chip => count, in the chips' order
     */
    public function chipCounts(): array
    {
        $all = $this->all();
        $counts = [];

        foreach (array_keys(self::CHIPS) as $chip) {
            $counts[$chip] = count(array_filter($all, fn (SupplierRecord $supplier): bool => self::passes($supplier, $chip)));
        }

        return $counts;
    }

    public function find(string $id): ?SupplierRecord
    {
        $supplier = Supplier::query()->find($id);

        return $supplier instanceof Supplier ? $this->record($supplier, SupplierChecklist::items()) : null;
    }

    /**
     * The checklist items as Admin configures them, in order.
     *
     * @return list<ChecklistSetting>
     */
    public function checklistSettings(): array
    {
        return array_map(fn (OnboardingChecklistItem $item): ChecklistSetting => new ChecklistSetting(
            $item->id,
            $item->key,
            SupplierChecklist::label($item->key),
            $item->required,
            in_array($item->key, OnboardingChecklistItem::ALWAYS_REQUIRED, true),
        ), SupplierChecklist::items());
    }

    public function checklistSetting(string $key): ?ChecklistSetting
    {
        foreach ($this->checklistSettings() as $setting) {
            if ($setting->key === $key) {
                return $setting;
            }
        }

        return null;
    }

    /**
     * How many Products the Supplier supplies. Always 0 until Products
     * arrive (Story 2.3), which must then read the real count here.
     */
    public function productCount(string $id): int
    {
        return 0;
    }

    /**
     * The dead-on-arrival window, in days, the Supplier's Products give
     * businesses: the platform's 30 days, or the Supplier's shorter window.
     */
    public function doaWindowDays(string $id): int
    {
        $days = Supplier::query()->whereKey($id)->value('doa_window_days');

        return $days === null ? self::PLATFORM_DOA_DAYS : min((int) $days, self::PLATFORM_DOA_DAYS);
    }

    /**
     * The details staff record, in the order the form asks for them.
     *
     * @return list<string>
     */
    public static function detailFields(): array
    {
        return array_keys(SupplierAction::DETAILS);
    }

    /**
     * @return list<SupplierRecord>
     */
    private function all(): array
    {
        $items = SupplierChecklist::items();

        return Supplier::query()
            ->orderByRaw('lower(legal_name)')
            ->orderBy('id')
            ->get()
            ->map(fn (Supplier $supplier): SupplierRecord => $this->record($supplier, $items))
            ->values()
            ->all();
    }

    private static function passes(SupplierRecord $supplier, string $chip): bool
    {
        return match ($chip) {
            self::NEEDS_ATTENTION => ! $supplier->isClear(),
            self::APPROVED => $supplier->isApproved,
            self::NOT_APPROVED => ! $supplier->isApproved,
            default => true,
        };
    }

    /**
     * @param  list<OnboardingChecklistItem>  $items
     */
    private function record(Supplier $supplier, array $items): SupplierRecord
    {
        $details = [];

        foreach (self::detailFields() as $field) {
            $details[$field] = $supplier->getAttribute($field);
        }

        return new SupplierRecord(
            $supplier->id,
            $supplier->legal_name,
            $details,
            $supplier->status->value,
            $supplier->status->label(),
            $supplier->status === SupplierStatus::Approved,
            $supplier->approved_by,
            $supplier->approved_at,
            $supplier->approval_reason,
            $this->productCount($supplier->id),
            SupplierChecklist::evaluate($supplier, $items),
        );
    }
}
