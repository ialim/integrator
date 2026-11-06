<?php

namespace App\Modules\Shared\Events;

use App\Modules\Shared\Actions\RelayOutboxEvents;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * The outbox relay, queued by the scheduler every minute: hands each due
 * outbox event to its listeners. One relay runs at a time; each event is also
 * claimed under a lease with SKIP LOCKED, so an overlap never runs an event's
 * listeners twice at once.
 */
final class RelayOutbox implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /** The unique lock lapses if a worker dies mid-run. */
    public int $uniqueFor = 300;

    public int $tries = 1;

    /**
     * The worker stops a run after this many seconds. A run claims no new
     * event after {@see RelayOutboxEvents::TIME_BUDGET_SECONDS}, well before.
     */
    public int $timeout = 120;

    public bool $failOnTimeout = true;

    public function handle(RelayOutboxEvents $relay): void
    {
        $relay->handle();
    }
}
