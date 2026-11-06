<?php

namespace App\Modules\Shared\Events;

/**
 * Reacts to a relayed outbox event. A listener must be idempotent: the relay
 * delivers at least once, so the same event may arrive again after a failure.
 * A listener that calls a provider records a delivery key (event id plus
 * channel) under a unique constraint before the call. Throwing leaves the
 * event for the next relay.
 */
interface OutboxListener
{
    public function handle(OutboxMessage $message): void;
}
