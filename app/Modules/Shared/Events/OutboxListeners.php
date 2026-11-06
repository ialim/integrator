<?php

namespace App\Modules\Shared\Events;

/**
 * Which listeners each outbox event type has. Modules register theirs from
 * their service provider; the registry is a container singleton.
 */
final class OutboxListeners
{
    /** @var array<string, list<class-string<OutboxListener>>> */
    private array $listeners = [];

    /**
     * @param  class-string<OutboxEvent>  $event
     * @param  class-string<OutboxListener>  $listener
     */
    public function listen(string $event, string $listener): void
    {
        $type = $event::type();

        if (! in_array($listener, $this->listeners[$type] ?? [], true)) {
            $this->listeners[$type][] = $listener;
        }
    }

    /**
     * @return list<class-string<OutboxListener>>
     */
    public function for(string $type): array
    {
        return $this->listeners[$type] ?? [];
    }
}
