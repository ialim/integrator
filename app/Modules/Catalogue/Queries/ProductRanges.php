<?php

namespace App\Modules\Catalogue\Queries;

use App\Modules\Catalogue\Models\ProductRange;
use App\Modules\Catalogue\Models\RangeCompliance;
use App\Modules\Catalogue\StatusTables\ProductRangeStatus;

/**
 * Reads Product Ranges for screens and other modules: active ranges first by
 * name, then retired ones, each with how many Products it holds. Read-only.
 */
final class ProductRanges
{
    /** The audit trail's subject type for a range. */
    public const AUDIT_SUBJECT = 'product_ranges';

    /**
     * @return list<ProductRangeSummary>
     */
    public function all(): array
    {
        return ProductRange::query()
            ->orderByRaw("CASE status WHEN 'active' THEN 0 ELSE 1 END")
            ->orderByRaw('lower(name)')
            ->orderBy('id')
            ->get()
            ->map(fn (ProductRange $range): ProductRangeSummary => $this->summary($range))
            ->values()
            ->all();
    }

    public function find(string $id): ?ProductRangeSummary
    {
        $range = ProductRange::query()->find($id);

        return $range instanceof ProductRange ? $this->summary($range) : null;
    }

    /**
     * How many Products the range holds. Always 0 until Products arrive
     * (Story 2.3), which must then read the real count here, with its Product
     * writes taking LockProductRange on the range they add to or move between,
     * or the retire refusal no longer holds.
     */
    public function productCount(string $id): int
    {
        return 0;
    }

    /**
     * The compliance a range can need, value => words.
     *
     * @return array<string, string>
     */
    public function complianceOptions(): array
    {
        return RangeCompliance::options();
    }

    private function summary(ProductRange $range): ProductRangeSummary
    {
        return new ProductRangeSummary(
            $range->id,
            $range->name,
            $range->compliance->value,
            $range->compliance->label(),
            $range->status->value,
            $range->status->label(),
            $range->status === ProductRangeStatus::Retired,
            $this->productCount($range->id),
        );
    }
}
