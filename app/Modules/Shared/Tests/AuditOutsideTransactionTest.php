<?php

use App\Modules\Shared\Actions\WriteAuditEntry;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Support\Id;

// No RefreshDatabase here: it would wrap the test in a transaction.
it('writes an audit row only inside the Action\'s transaction', function () {
    app(WriteAuditEntry::class)->handle(Actor::system(), 'probe.done', 'probes', Id::generate(), []);
})->throws(LogicException::class, 'only inside the Action\'s transaction');
