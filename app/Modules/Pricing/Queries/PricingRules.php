<?php

namespace App\Modules\Pricing\Queries;

use App\Modules\Pricing\Models\PricingSetting;
use App\Modules\Pricing\Models\RangeMarkup;
use LogicException;

/**
 * Reads the rules Admin sets in Pricing: each Product Range's Platform
 * Markup and the margin floor. Read-only.
 */
final class PricingRules
{
    /**
     * Every range's markup, by range id.
     *
     * @return array<string, RulePercent>
     */
    public function markups(): array
    {
        $markups = [];

        foreach (RangeMarkup::query()->get() as $markup) {
            $markups[$markup->product_range_id] = $this->markup($markup);
        }

        return $markups;
    }

    public function markupFor(string $rangeId): ?RulePercent
    {
        $markup = RangeMarkup::query()->where('product_range_id', $rangeId)->first();

        return $markup instanceof RangeMarkup ? $this->markup($markup) : null;
    }

    /** The margin floor: the pre-VAT price is never below Supplier Cost × (1 + floor). */
    public function marginFloor(): RulePercent
    {
        $floor = PricingSetting::query()->where('key', PricingSetting::MARGIN_FLOOR)->first()
            ?? throw new LogicException('The margin floor row is missing; it is inserted by the migration that creates pricing_settings.');

        return new RulePercent($floor->id, $floor->getTable(), (string) $floor->percent);
    }

    private function markup(RangeMarkup $markup): RulePercent
    {
        return new RulePercent($markup->id, $markup->getTable(), (string) $markup->markup_percent);
    }
}
