<?php

namespace Database\Factories\Catalogue;

use App\Modules\Catalogue\Queries\ProductRanges;
use App\Modules\Staff\Models\StaffMember;
use Database\Factories\ActionFactory;
use Database\Factories\Staff\FirstAdminFactory;

/**
 * Two Product Ranges the founder's Admin created, each at the starting 25%
 * markup, so the Pricing and rules page has rows to check. Creates the
 * founder's Admin first when there is none; adds nothing when the ranges are
 * already there. Test values only.
 */
final class ProductRangesScenario
{
    public const RANGES = [
        'IP cameras' => 'ncc_type_approval',
        'Recorders' => 'none',
    ];

    public function __invoke(): void
    {
        ActionFactory::assertAvailable();

        $admin = StaffMember::query()->where('is_founder', true)->first() ?? FirstAdminFactory::new()->create();
        $existing = array_map(fn ($range) => $range->name, app(ProductRanges::class)->all());

        foreach (self::RANGES as $name => $compliance) {
            if (! in_array($name, $existing, true)) {
                ProductRangeFactory::new()->create(['actor' => $admin, 'name' => $name, 'compliance' => $compliance]);
            }
        }
    }
}
