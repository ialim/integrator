<?php

use App\Modules\Shared\Alerts\FounderAlert;
use App\Modules\Shared\Alerts\FounderAlerts;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Logging\RequestId;
use App\Modules\Shared\Support\Id;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Mailer\Exception\TransportException;

uses(RefreshDatabase::class);

final class FounderAlertsProbeJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        throw new RuntimeException('the probe job failed');
    }
}

beforeEach(function () {
    config(['alerts.founder_email' => 'founder@example.test', 'alerts.cache_store' => 'database']);
    app(Clock::class)->set('2026-10-25T09:00:00Z');

    $this->logFile = storage_path('logs/test-alerts-'.Id::generate().'.log');
    config(['logging.channels.json.path' => $this->logFile, 'logging.default' => 'json']);
    Log::forgetChannel('json');
});

afterEach(function () {
    app(Clock::class)->reset();
    @unlink($this->logFile);
});

/** @return list<string> the plain text of every founder alert sent so far */
function sentAlerts(): array
{
    return Mail::sent(FounderAlert::class)->map(fn (FounderAlert $alert) => $alert->plainText())->values()->all();
}

/** @return list<array<string, mixed>> */
function alertLogLines(string $file): array
{
    if (! is_file($file)) {
        return [];
    }

    return array_map(fn (string $line) => json_decode($line, true, flags: JSON_THROW_ON_ERROR), file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
}

/** Queues failing probe jobs on the database queue and lets a worker fail them permanently. */
function failProbeJobs(int $count = 1): void
{
    for ($i = 0; $i < $count; $i++) {
        Queue::connection('database')->pushOn('payments', new FounderAlertsProbeJob);
    }

    test()->artisan('queue:work', ['connection' => 'database', '--queue' => 'payments', '--stop-when-empty' => true, '--tries' => 1, '--sleep' => 0])
        ->assertSuccessful();
}

function raiseBackupFailed(): bool
{
    return app(FounderAlerts::class)->raise('backup_failed', 'Backup failed', ['Step' => 'upload']);
}

it('sends at once, not queued, naming what failed and when in WAT and UTC', function () {
    Mail::fake();

    expect(raiseBackupFailed())->toBeTrue();

    Mail::assertSentCount(1);
    Mail::assertNothingQueued();
    Mail::assertSent(FounderAlert::class, fn (FounderAlert $m) => $m->hasTo('founder@example.test')
        && $m->envelope()->subject === 'integrator alert: Backup failed');

    expect(sentAlerts()[0])
        ->toContain('Step: upload')
        ->toContain('When: 25 October 2026, 10:00 WAT (09:00 UTC)')
        ->toContain('Request id: ');
    expect(is_subclass_of(FounderAlert::class, ShouldQueue::class))->toBeFalse();
});

it('shows the UTC date too when it differs from the WAT date', function () {
    Mail::fake();
    app(Clock::class)->set('2026-10-25T23:30:00Z');

    raiseBackupFailed();

    expect(sentAlerts()[0])->toContain('When: 26 October 2026, 00:30 WAT (23:30 UTC, 25 October 2026)');
});

it('sends one alert per kind per 15 minutes', function () {
    Mail::fake();
    $clock = app(Clock::class);

    expect(raiseBackupFailed())->toBeTrue();
    $clock->advance('14 minutes 59 seconds');
    expect(raiseBackupFailed())->toBeFalse();
    Mail::assertSentCount(1);

    // A different kind is not held back by the first.
    expect(app(FounderAlerts::class)->raise('other_kind', 'Something else', []))->toBeTrue();
    Mail::assertSentCount(2);

    $clock->advance('1 second');
    expect(raiseBackupFailed())->toBeTrue();
    Mail::assertSentCount(3);
    expect(sentAlerts()[2])->toContain('Also held back before this one: 1 more since 25 October 2026, 10:00 WAT (09:00 UTC).');
});

it('turns a storm of 50 failed jobs in one minute into one alert, then one follow-up stating the count', function () {
    Mail::fake();
    $clock = app(Clock::class);

    failProbeJobs(50);

    expect(DB::table('failed_jobs')->count())->toBe(50);
    Mail::assertSentCount(1);
    expect(sentAlerts()[0])->toContain('What failed: FounderAlertsProbeJob')->toContain('Queue: payments');

    // Before the window closes, the scheduled command holds them still.
    $this->artisan('alerts:send-held')->assertSuccessful();
    Mail::assertSentCount(1);

    $clock->set('2026-10-25T09:15:00Z');
    $this->artisan('alerts:send-held')->expectsOutputToContain('1')->assertSuccessful();

    Mail::assertSentCount(2);
    expect(sentAlerts()[1])
        ->toContain('This happened 49 more times since the last alert, 50 in all since 25 October 2026, 10:00 WAT (09:00 UTC).')
        ->toContain('FounderAlertsProbeJob on payments: 49')
        ->toContain('When: 25 October 2026, 10:00 WAT (09:00 UTC)');
    expect(Mail::sent(FounderAlert::class)->last()->title)->toBe('Failed job: 49 more');

    // Nothing more to send, and the follow-up started a fresh window.
    $this->artisan('alerts:send-held')->assertSuccessful();
    Mail::assertSentCount(2);
});

it('alerts on a permanently failed job, naming the job class, queue and time, and leaves the failure itself alone', function () {
    Mail::fake();

    failProbeJobs();

    Mail::assertSentCount(1);
    expect(sentAlerts()[0])
        ->toStartWith('Failed job')
        ->toContain('What failed: FounderAlertsProbeJob')
        ->toContain('Queue: payments')
        ->toContain('Connection: database')
        ->toContain('Error: RuntimeException')
        ->toContain('When: 25 October 2026, 10:00 WAT (09:00 UTC)')
        ->not->toContain('the probe job failed');
});

it('logs, and never throws, when the alert channel is down', function () {
    Mail::shouldReceive('mailer')->andThrow(new TransportException('SMTP connection refused'));

    expect(raiseBackupFailed())->toBeFalse();

    // The job's own failure is unaffected: it is still recorded as failed, with its own exception.
    failProbeJobs();
    expect(DB::table('failed_jobs')->count())->toBe(1)
        ->and(DB::table('failed_jobs')->value('exception'))->toContain('the probe job failed');

    // The worker also logs the job's own exception; pick out the alert lines.
    $lines = array_values(array_filter(alertLogLines($this->logFile), fn (array $line) => $line['message'] === 'Founder alert not sent'));

    expect($lines)->toHaveCount(2)
        ->and($lines[0]['message'])->toBe('Founder alert not sent')
        ->and($lines[0]['context'])->toMatchArray(['alert_kind' => 'backup_failed', 'error' => TransportException::class])
        ->and(Id::isValid($lines[0]['extra']['request_id']))->toBeTrue()
        ->and($lines[1]['context']['alert_kind'])->toBe('failed_job');
});

it('logs an alert it cannot address', function () {
    Mail::fake();
    config(['alerts.founder_email' => null]);

    expect(raiseBackupFailed())->toBeFalse();

    Mail::assertNothingSent();
    expect(alertLogLines($this->logFile)[0]['context']['error_message'])->toContain('FOUNDER_ALERT_EMAIL');
});

it('schedules the held-alert command every minute', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'alerts:send-held'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('* * * * *');
});

it('sends a test alert on demand, and fails the command when it cannot', function () {
    Mail::fake();

    $this->artisan('alerts:test')->expectsOutputToContain('Test alert sent to founder@example.test')->assertSuccessful();
    expect(sentAlerts()[0])->toContain('What: A test of the founder alert channel');

    // A second run inside 15 minutes is its own kind, so it is not throttled.
    $this->artisan('alerts:test')->assertSuccessful();
    Mail::assertSentCount(2);

    config(['alerts.founder_email' => '']);
    $this->artisan('alerts:test')->assertFailed();
});

it('gives the follow-up the request id of the last held occurrence, not the scheduler run', function () {
    Mail::fake();

    RequestId::start('req-first-000001');
    raiseBackupFailed();
    RequestId::start('req-held-0000002');
    raiseBackupFailed();

    app(Clock::class)->advance('15 minutes');
    RequestId::start('req-scheduler-03');
    app(FounderAlerts::class)->sendHeld();

    expect(sentAlerts()[1])->toContain('Request id: req-held-0000002')->not->toContain('req-scheduler-03');
});

it('ships with the throttle on the database cache store', function () {
    expect((require config_path('alerts.php'))['cache_store'])->toBe('database');
});

it('sends with the lock released, and a failed send still throttles the kind', function () {
    $lockWasFree = null;

    Mail::shouldReceive('mailer')->once()->andReturnUsing(function () use (&$lockWasFree) {
        $probe = Cache::store('database')->lock('founder-alerts:backup_failed:lock', 1);
        $lockWasFree = $probe->get();
        $probe->release();

        throw new TransportException('SMTP connection refused');
    });

    expect(raiseBackupFailed())->toBeFalse()
        ->and($lockWasFree)->toBeTrue();

    // Inside the window the second occurrence is held without touching SMTP (mailer is expected once).
    app(Clock::class)->advance('5 minutes');
    expect(raiseBackupFailed())->toBeFalse();
});

it('still sends one unthrottled alert when the throttle store is down, and logs the store failure', function () {
    Mail::fake();
    config(['cache.stores.broken' => ['driver' => 'database', 'connection' => 'no_such_connection', 'table' => 'cache', 'lock_table' => 'cache_locks'],
        'alerts.cache_store' => 'broken']);

    expect(raiseBackupFailed())->toBeTrue()
        ->and(raiseBackupFailed())->toBeTrue();

    Mail::assertSentCount(2);
    expect(sentAlerts()[0])->toContain('Step: upload')->toContain('When: 25 October 2026, 10:00 WAT (09:00 UTC)');
    expect(alertLogLines($this->logFile)[0])
        ->toMatchArray(['message' => 'Founder alert not sent'])
        ->and(alertLogLines($this->logFile)[0]['context']['alert_kind'])->toBe('backup_failed');
});

it('sends a plain-text part beside the HTML', function () {
    config(['alerts.mailer' => 'array']);

    raiseBackupFailed();

    $message = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->first()->getOriginalMessage();

    expect($message->getTextBody())->toStartWith('Backup failed

Step: upload')
        ->and($message->getHtmlBody())->toContain('<p>Step: upload</p>');
});

it('names the failed job by uuid, for queue:retry', function () {
    Mail::fake();

    failProbeJobs();

    $uuid = DB::table('failed_jobs')->value('uuid');
    expect(sentAlerts()[0])->toContain("Job id (for queue:retry): {$uuid}");
});
