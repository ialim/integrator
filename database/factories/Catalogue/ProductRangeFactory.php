<?php

namespace Database\Factories\Catalogue;

use App\Modules\Catalogue\Actions\CreateProductRange;
use App\Modules\Catalogue\Queries\ProductRangeSummary;
use Database\Factories\ActionFactory;

/**
 * A Product Range created by an Admin through {@see CreateProductRange}, at
 * the starting 25% markup; pass the acting Admin as `actor`. Test values only.
 *
 * @extends ActionFactory<ProductRangeSummary>
 */
final class ProductRangeFactory extends ActionFactory
{
    protected function action(): string
    {
        return CreateProductRange::class;
    }

    protected function definition(): array
    {
        return [
            'name' => 'IP cameras',
            'compliance' => 'ncc_type_approval',
        ];
    }
}
