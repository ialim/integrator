<?php

use App\Modules\Shared\Actions\WriteAuditEntry;
use App\Modules\Shared\Alerts\FounderAlert;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Audit\AuditSeal;
use App\Modules\Shared\Audit\AuditSealing;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Queries\AuditChain;
use App\Modules\Shared\Support\Id;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Sleep;

/*
 * Scheduled sealing, its founder alerts and heartbeat. One case plants a seal
 * on purpose, so the file lives outside app/Modules, where the
 * single-write-path rule allows only Actions to write.
 */

uses(RefreshDatabase::class);

const SEAL_HEARTBEAT = 'https://hc-ping.example.test/audit-sealing';

beforeEach(function () {
    config(['alerts.founder_email' => 'founder@example.test', 'alerts.cache_store' => 'database', 'alerts.audit_seal_heartbeat_url' => SEAL_HEARTBEAT]);
    app(Clock::class)->set('2026-11-09T09:00:00Z');
    Mail::fake();
    Http::preventStrayRequests();
});

afterEach(fn () => app(Clock::class)->reset());

/** @return list<string> */
function sealAlerts(): array
{
    return Mail::sent(FounderAlert::class)->map(fn (FounderAlert $alert) => $alert->plainText())->values()->all();
}

/** The heartbeat check answers every ping (and its /fail URL) with $response. */
function fakeHeartbeat(mixed $response = null): void
{
    Http::fake([SEAL_HEARTBEAT.'*' => $response ?? Http::response('OK')]);
}

function sealingEntry(): void
{
    DB::transaction(fn () => app(WriteAuditEntry::class)->handle(Actor::system(), 'probe.done', 'probes', Id::generate(), []));
}

/** A second connection holding the writers' lock, as an Action whose transaction is still open. */
function openWriter(): PDO
{
    $config = config('database.connections.pgsql');
    $pdo = new PDO(
        sprintf('pgsql:host=%s;port=%s;dbname=%s', $config['host'], $config['port'], $config['database']),
        (string) $config['username'],
        (string) $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
    );
    $pdo->beginTransaction();
    $pdo->prepare('SELECT pg_advisory_xact_lock_shared(?)')->execute([AuditSeal::WRITERS_LOCK]);

    return $pdo;
}

it('seals, pings the heartbeat and raises no alert when all is well', function () {
    fakeHeartbeat();
    sealingEntry();

    $this->artisan('audit:seal')->expectsOutputToContain('Sealed 1 audit row;')->assertSuccessful();

    expect(DB::table('audit_seals')->count())->toBe(1)
        ->and(sealAlerts())->toBe([]);
    Http::assertSent(fn ($request) => $request->url() === SEAL_HEARTBEAT);
});

it('pings nothing when no heartbeat URL is set', function () {
    fakeHeartbeat();
    config(['alerts.audit_seal_heartbeat_url' => null]);

    $this->artisan('audit:seal')->assertSuccessful();

    Http::assertNothingSent();
});

it('alerts the founder naming the failure when a run throws, and tells the heartbeat it failed', function () {
    fakeHeartbeat();
    sealingEntry();
    // A planted seal whose hash is also its own previous hash: the next seal would link to it and collide.
    $planted = str_repeat('b', 64);
    DB::table('audit_seals')->insert(['id' => Id::generate(), 'sequence' => 0, 'hash' => $planted, 'previous_hash' => $planted, 'sealed_at' => app(Clock::class)->now()]);

    $this->artisan('audit:seal')->expectsOutputToContain('Sealing failed (Illuminate\Database\UniqueConstraintViolationException)')->assertFailed();

    expect(sealAlerts())->toHaveCount(1)
        ->and(sealAlerts()[0])->toContain('Audit seal failed')
        ->and(sealAlerts()[0])->toContain('What failed: Sealing the audit trail into its hash chain (php artisan audit:seal)')
        ->and(sealAlerts()[0])->toContain('Error: Illuminate\Database\UniqueConstraintViolationException')
        ->and(sealAlerts()[0])->toContain('Next try: The next scheduled run')
        ->and(sealAlerts()[0])->not->toContain('previous_hash');
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->method() === 'GET' && $request->url() === SEAL_HEARTBEAT.'/fail');
});

it('alerts the founder when a row stays unsealed past 30 minutes, and logs each busy run', function () {
    fakeHeartbeat();
    Log::spy();
    // An Action elsewhere keeps its transaction open, so no run can seal.
    $writer = openWriter();

    sealingEntry();
    app(Clock::class)->set('2026-11-09T09:30:00Z');
    expect(app(AuditSealing::class)->run()->overdue)->toBeFalse()
        ->and(sealAlerts())->toBe([]);

    app(Clock::class)->set('2026-11-09T09:31:00Z');
    $outcome = app(AuditSealing::class)->run();

    expect($outcome->overdue)->toBeTrue()
        ->and(sealAlerts())->toHaveCount(1)
        ->and(sealAlerts()[0])->toContain('Audit seal overdue')
        ->and(sealAlerts()[0])->toContain('Unsealed rows: 1')
        ->and(sealAlerts()[0])->toContain('9 November 2026, 10:00 WAT (09:00 UTC)');
    // A run that sealed nothing because writers stayed open is not a completed run.
    Http::assertNothingSent();
    expect(DB::table('audit_seals')->count())->toBe(0);
    Log::shouldHaveReceived('log')->withArgs(fn (string $level, string $message) => $level === 'warning' && $message === 'audit.seal_busy')->twice();

    $writer->rollBack();
});

it('schedules sealing every five minutes, on one server, never two at once', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'audit:seal'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('*/5 * * * *')
        ->and($event->onOneServer)->toBeTrue()
        ->and($event->withoutOverlapping)->toBeTrue();
});

it('sends the chain head as the ping body and logs it', function () {
    fakeHeartbeat();
    Log::spy();
    sealingEntry();
    sealingEntry();

    app(AuditSealing::class)->run();

    $head = DB::table('audit_seals')->orderByDesc('sequence')->first();
    $expected = ['sealed' => 2, 'last_sealed_sequence' => (int) $head->sequence, 'head_hash' => $head->hash];

    Http::assertSent(fn ($request) => $request->method() === 'POST' && $request->url() === SEAL_HEARTBEAT && $request->data() === $expected);
    Log::shouldHaveReceived('log')->withArgs(fn (string $level, string $message, array $context) => $level === 'info' && $message === 'audit.sealed' && $context === $expected)->once();
});

it('still seals and succeeds, with no alert, when the heartbeat fails', function (Closure $answer) {
    Sleep::fake();
    fakeHeartbeat($answer);
    sealingEntry();

    $this->artisan('audit:seal')->expectsOutputToContain('Sealed 1 audit row;')->assertSuccessful();

    expect(DB::table('audit_seals')->count())->toBe(1)
        ->and(sealAlerts())->toBe([]);
})->with([
    'an error answer' => fn () => fn () => Http::response('down', 500),
    'no connection' => fn () => fn () => throw new ConnectionException('Could not resolve host'),
]);

it('alerts the founder, and still finishes the run, when checking for unsealed rows throws', function () {
    fakeHeartbeat();
    $chain = Mockery::mock(AuditChain::class)->makePartial();
    $chain->shouldReceive('unsealed')->andThrow(new RuntimeException('the check failed'));
    app()->instance(AuditChain::class, $chain);
    sealingEntry();

    $this->artisan('audit:seal')->assertSuccessful();

    expect(DB::table('audit_seals')->count())->toBe(1)
        ->and(sealAlerts())->toHaveCount(1)
        ->and(sealAlerts()[0])->toContain('What failed: Checking for audit rows left unsealed (php artisan audit:seal)')
        ->and(sealAlerts()[0])->toContain('Error: RuntimeException');
});

it('alerts the founder naming the table whose trigger is missing or disabled', function (string $tamper, string $table) {
    fakeHeartbeat();
    DB::statement($tamper);

    app(AuditSealing::class)->run();

    expect(sealAlerts())->toHaveCount(1)
        ->and(sealAlerts()[0])->toContain('Audit trail unprotected')
        ->and(sealAlerts()[0])->toContain("Tables: {$table}");
})->with([
    'disabled' => ['ALTER TABLE audit_seals DISABLE TRIGGER audit_seals_insert_only', 'audit_seals'],
    'enabled only for ordinary sessions' => ['ALTER TABLE audit_entries ENABLE TRIGGER audit_entries_insert_only', 'audit_entries'],
    'dropped' => ['DROP TRIGGER audit_entries_insert_only ON audit_entries', 'audit_entries'],
]);

it('raises no protection alert while both triggers are in place', function () {
    fakeHeartbeat();

    app(AuditSealing::class)->run();

    expect(sealAlerts())->toBe([]);
});
