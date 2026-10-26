<?php

use App\Modules\Shared\Alerts\FounderAlerts;
use App\Modules\Shared\Support\Id;
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

// The scheduler runs this directly, never through the queue the alerts report on.
Schedule::command('alerts:send-held')->everyMinute()->onOneServer()->withoutOverlapping(5);
