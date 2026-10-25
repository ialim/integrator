<?php

namespace ArchFixtures\Boundaries\Modules\Orders\Queries;

use Illuminate\Support\Facades\DB;

// Fixture: naming Catalogue's products table is reported; Orders' own tables are not.
class OrderLinesQuery
{
    public function lines(string $orderId): mixed
    {
        DB::table('order_lines')->where('order_id', $orderId)->get();

        return DB::select('select * from products where id in (select product_id from order_lines)');
    }

    public function withProducts(): mixed
    {
        return DB::table('order_lines')->join('products', 'products.id', '=', 'order_lines.product_id')->get();
    }
}
