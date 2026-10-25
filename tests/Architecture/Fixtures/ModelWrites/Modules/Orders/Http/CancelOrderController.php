<?php

namespace ArchFixtures\ModelWrites\Modules\Orders\Http;

use ArchFixtures\ModelWrites\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\DB;

// Fixture: every write below is outside an Action and must be reported.
class CancelOrderController
{
    public function __construct(private Order $order) {}

    public function cancel(Order $order): void
    {
        $order->save();
    }

    public function open(): void
    {
        Order::create(['status' => 'open']);
    }

    public function close(): void
    {
        $found = Order::query()->where('status', 'open')->first();
        $found->delete();
    }

    public function archive(): void
    {
        Order::where('status', 'closed')->update(['archived' => true]);
        $this->order->forceDelete();
        DB::table('orders')->insert(['id' => 'x']);
        $this->order->lines()->attach('x');
    }

    public function read(): mixed
    {
        // Reads are allowed anywhere.
        return Order::query()->where('status', 'open')->get();
    }
}
