<?php

namespace App\Modules\Shared\Actions;

use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Events\OutboxEvent;
use App\Modules\Shared\Support\Id;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * The outbox writer (AD-4): inserts one event inside the calling Action's
 * transaction, so the event commits or rolls back with the change. Each event
 * gets an id (listeners' delivery keys use it) and the next number in its
 * aggregate's sequence; the Action holds the aggregate's lock, and a unique
 * constraint backs the sequence up.
 */
final class WriteOutboxEvent
{
    public function __construct(private readonly Clock $clock) {}

    public function handle(OutboxEvent $event): string
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('An outbox event is written only inside the Action\'s transaction.');
        }

        $id = Id::generate();
        $now = $this->clock->now();

        $sequence = 1 + (int) DB::table('outbox_events')
            ->where('aggregate_type', $event->aggregateType())
            ->where('aggregate_id', $event->aggregateId())
            ->max('sequence');

        DB::table('outbox_events')->insert([
            'id' => $id,
            'type' => $event::type(),
            'aggregate_type' => $event->aggregateType(),
            'aggregate_id' => $event->aggregateId(),
            'sequence' => $sequence,
            'payload' => json_encode((object) $event->payload(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            'occurred_at' => $now,
            'available_at' => $now,
        ]);

        return $id;
    }
}
