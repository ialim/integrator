<?php

namespace ArchFixtures\Boundaries\Modules\Orders\Http;

use ArchFixtures\Boundaries\Modules\Catalogue\Models\Product;
use ArchFixtures\Boundaries\Modules\Catalogue\Queries\ProductQuery;

// Fixture: importing Catalogue's model is reported; using its Query is not.
class ShowOrderController
{
    public function __invoke(ProductQuery $products): mixed
    {
        return [$products->find('x'), Product::query()->count()];
    }
}
