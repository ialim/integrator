<?php

use App\Modules\Shared\Alerts\FounderAlerts;
use App\Modules\Shared\Audit\AuditSealing;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Events\RelayOutbox;
use App\Modules\Shared\Support\FrozenClock;
use App\Modules\Shared\Support\Id;
use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('alerts:send-held', function (FounderAlerts $alerts) {
    $this->info('Founder alerts sent for held occurrences: '.$alerts->sendHeld());
})->purpose('Send one follow-up founder alert per kind whose 15-minute window closed with occurrences held back');

Artisan::command('alerts:test', function (FounderAlerts $alerts) {
    // Each run is its own kind, so a second test inside 15 minutes is not throttled.
    $sent = $alerts->raise('test:'.Id::generate(), 'Test alert', ['What' => 'A test of the founder alert channel; nothing has failed']);

    $sent ? $this->info('Test alert sent to '.config('alerts.founder_email').'.')
        : $this->error('Test alert not sent: the channel failed; see the log.');

    return $sent ? 0 : 1;
})->purpose('Send a test founder alert, to check the SMTP settings and the founder address');

Artisan::command('audit:seal', function (AuditSealing $sealing) {
    $outcome = $sealing->run();
    $result = $outcome->result;

    if ($result === null) {
        $this->error("Sealing failed ({$outcome->error}); the founder was alerted. The next run retries.");
    } elseif ($result->busy) {
        $this->warn('Nothing sealed: writers stayed open. The next run tries again.');
    } else {
        $this->info("Sealed {$result->sealed} audit ".($result->sealed === 1 ? 'row' : 'rows').'; the chain now ends at sequence '.($result->lastSealedSequence ?? 'none').'.');
    }

    if ($outcome->overdue) {
        $this->warn('Audit rows have waited too long to be sealed; the founder was alerted.');
    }

    return $outcome->failed() ? 1 : 0;
})->purpose('Seal new audit rows into the hash chain, in sequence order (it never changes or re-seals a row)');

/*
 * The settable Clock in testing and staging (TC-2): set it, move it forward without waiting, or let it read
 * the real time again. In staging the instant is shared by every process until reset. Production's Clock is
 * the real one, so these refuse there.
 */
$frozenClock = function (Clock $clock, Command $command): ?FrozenClock {
    if ($clock instanceof FrozenClock) {
        return $clock;
    }

    $command->error('The Clock can be set only in testing and staging; this environment reads the real time.');

    return null;
};

Artisan::command('clock:set {instant : An ISO 8601 instant, such as 2026-11-20T09:00:00Z}', function (Clock $clock) use ($frozenClock) {
    if (! $frozen = $frozenClock($clock, $this)) {
        return 1;
    }

    $this->info('The Clock now reads '.$frozen->set((string) $this->argument('instant'))->toIso8601ZuluString().'.');

    return 0;
})->purpose('Testing and staging: freeze the Clock at an instant, for every process');

Artisan::command('clock:advance {interval : How far to move, such as "2 hours" or "3 days"}', function (Clock $clock) use ($frozenClock) {
    if (! $frozen = $frozenClock($clock, $this)) {
        return 1;
    }

    $this->info('The Clock now reads '.$frozen->advance((string) $this->argument('interval'))->toIso8601ZuluString().'.');

    return 0;
})->purpose('Testing and staging: move the Clock forward without waiting');

Artisan::command('clock:reset', function (Clock $clock) use ($frozenClock) {
    if (! $frozen = $frozenClock($clock, $this)) {
        return 1;
    }

    $frozen->reset();
    $this->info('The Clock reads the real time again.');

    return 0;
})->purpose('Testing and staging: let the Clock read the real time again');

Artisan::command('clock:show', function (Clock $clock) {
    $state = $clock instanceof FrozenClock && $clock->isFrozen() ? 'frozen' : 'real time';
    $this->info('The Clock reads '.$clock->now()->toIso8601ZuluString()." ({$state}).");
})->purpose('Show the time the Clock reads, and whether it is frozen');

// The scheduler runs this directly, never through the queue the alerts report on.
Schedule::command('alerts:send-held')->everyMinute()->onOneServer()->withoutOverlapping(5);

// The outbox relay (AD-4): a queued job each minute; one runs at a time.
Schedule::job(new RelayOutbox)->everyMinute()->onOneServer()->name('outbox-relay');

// The one writer of the audit hash chain (AD-21): never two at once.
Schedule::command('audit:seal')->everyFiveMinutes()->onOneServer()->withoutOverlapping(10);
