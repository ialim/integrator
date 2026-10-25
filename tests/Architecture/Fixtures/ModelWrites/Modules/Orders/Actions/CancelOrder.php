<?php

namespace ArchFixtures\ModelWrites\Modules\Orders\Actions;

use ArchFixtures\ModelWrites\Modules\Orders\Models\Order;

// Fixture: Actions are the single write path, so nothing here is reported.
class CancelOrder
{
    public function handle(Order $order): void
    {
        $order->save();
        Order::create(['status' => 'cancelled']);
    }
}
