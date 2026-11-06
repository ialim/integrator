<?php

use App\Modules\Shared\Actions\WriteOutboxEvent;
use App\Modules\Shared\Events\OutboxEvent;
use App\Modules\Shared\Support\Id;

// No RefreshDatabase here: it would wrap the test in a transaction.
it('writes an outbox event only inside the Action\'s transaction', function () {
    app(WriteOutboxEvent::class)->handle(new class implements OutboxEvent
    {
        public static function type(): string
        {
            return 'probe.outside';
        }

        public function aggregateType(): string
        {
            return 'probe';
        }

        public function aggregateId(): string
        {
            return Id::generate();
        }

        public function payload(): array
        {
            return [];
        }
    });
})->throws(LogicException::class, 'only inside the Action\'s transaction');
