<?php

namespace ArchFixtures\Boundaries\Modules\Catalogue\Queries;

use ArchFixtures\Boundaries\Modules\Catalogue\Models\Product;
use Illuminate\Support\Facades\DB;

// Fixture: a module may use its own models and tables.
class ProductQuery
{
    public function find(string $id): mixed
    {
        Product::query()->find($id);

        return DB::table('products')->where('id', $id)->first();
    }
}
