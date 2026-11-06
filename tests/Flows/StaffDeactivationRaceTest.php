<?php

use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Queries\AuditTrail;
use App\Modules\Shared\StatusTables\TransitionRefused;
use App\Modules\Staff\Actions\DeactivateStaffAccount;
use App\Modules\Staff\Filament\RefusalWords;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use App\Modules\Staff\StatusTables\StaffAccountStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\StaffConsole;

/*
 * Two Admins deactivate the same account at the same moment, in two real
 * processes with their own database connections. No RefreshDatabase: each
 * racer must commit, so this test cleans up after itself.
 */

function raceConnection(): PDO
{
    $config = config('database.connections.pgsql');
    $pdo = new PDO(
        sprintf('pgsql:host=%s;port=%s;dbname=%s', $config['host'], $config['port'], $config['database']),
        (string) $config['username'],
        (string) $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
    );

    return $pdo;
}

function cleanRaceTables(): void
{
    DB::statement('TRUNCATE staff_sessions, notification_deliveries, outbox_events, audit_entries, staff_members CASCADE');
}

/**
 * Forks a racer that deactivates $memberId as $admin and writes what happened to $file.
 */
function racer(StaffMember $admin, string $memberId, string $file): int
{
    $pid = pcntl_fork();

    if ($pid !== 0) {
        return $pid;
    }

    // The child: its own connection, then out without running the test runner's shutdown.
    try {
        DB::reconnect();
        app(DeactivateStaffAccount::class)->handle($admin, $memberId);
        file_put_contents($file, json_encode(['outcome' => 'won', 'by' => $admin->name]));
    } catch (TransitionRefused $e) {
        file_put_contents($file, json_encode(['outcome' => 'refused', 'by' => $admin->name, 'words' => app(RefusalWords::class)->for($e)]));
    } catch (AuthorizationException $e) {
        file_put_contents($file, json_encode(['outcome' => 'not_allowed', 'by' => $admin->name, 'words' => $e->getMessage()]));
    } catch (Throwable $e) {
        file_put_contents($file, json_encode(['outcome' => 'error', 'by' => $admin->name, 'error' => $e::class.': '.$e->getMessage()]));
    }

    posix_kill(posix_getpid(), SIGKILL);

    exit(1);
}

beforeEach(function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('The race needs pcntl, which the Docker image and CI have.');
    }

    if (! RefreshDatabaseState::$migrated) {
        Artisan::call('migrate:fresh');
        RefreshDatabaseState::$migrated = true;
    }

    cleanRaceTables();
    app(Clock::class)->set('2026-11-03T13:05:00Z');
});

afterEach(function () {
    DB::reconnect();
    cleanRaceTables();
    app(Clock::class)->reset();
});

it('lets the first of two racing Admins deactivate the account; the other sees who did it and when', function () {
    $founder = StaffConsole::admin();
    $second = StaffConsole::active($founder, StaffRole::Admin, 'Chidi Admin');
    $member = StaffConsole::active($founder, StaffRole::Operations, 'Bola Ade');

    // Hold the member's row so both racers are inside their Action, waiting on the lock, before either can win.
    $gate = raceConnection();
    $gate->beginTransaction();
    $gate->prepare('SELECT id FROM staff_members WHERE id = ? FOR UPDATE')->execute([$member->id]);

    DB::disconnect();
    $files = [tempnam(sys_get_temp_dir(), 'race-a'), tempnam(sys_get_temp_dir(), 'race-b')];
    $pids = [racer($founder, $member->id, $files[0]), racer($second, $member->id, $files[1])];

    $waiting = 0;

    for ($i = 0; $i < 200 && $waiting < 2; $i++) {
        usleep(100_000);
        $waiting = (int) $gate->query("SELECT count(*) FROM pg_stat_activity WHERE datname = current_database() AND wait_event_type = 'Lock'")->fetchColumn();
    }

    $gate->commit();

    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
    }

    $results = array_map(fn (string $file) => json_decode((string) file_get_contents($file), true), $files);
    array_map('unlink', $files);

    expect($waiting)->toBe(2, 'both racers were waiting on the lock at the same moment');

    $outcomes = array_column($results, 'outcome');
    sort($outcomes);

    expect($outcomes)->toBe(['refused', 'won'], json_encode($results));

    $winner = collect($results)->firstWhere('outcome', 'won')['by'];
    $loser = collect($results)->firstWhere('outcome', 'refused');

    expect($loser['words'])->toBe("Bola Ade was already deactivated by {$winner} at 14:05 WAT on Tuesday 3 November 2026. Nothing else was changed.");

    DB::reconnect();

    expect(StaffMember::query()->findOrFail($member->id)->status)->toBe(StaffAccountStatus::Deactivated)
        ->and(collect(app(AuditTrail::class)->forSubject('staff_members', $member->id))->where('action', 'staff.account_deactivated'))->toHaveCount(1)
        ->and(DB::table('outbox_events')->where('aggregate_id', $member->id)->where('type', 'staff.account_deactivated')->count())->toBe(1);
});

it('never lets two Admins deactivate each other at once and leave nobody', function () {
    $founder = StaffConsole::admin();
    $second = StaffConsole::active($founder, StaffRole::Admin, 'Chidi Admin');

    $gate = raceConnection();
    $gate->beginTransaction();
    $gate->prepare('SELECT id FROM staff_members WHERE id IN (?, ?) FOR UPDATE')->execute([$founder->id, $second->id]);

    DB::disconnect();
    $files = [tempnam(sys_get_temp_dir(), 'race-a'), tempnam(sys_get_temp_dir(), 'race-b')];
    $pids = [racer($founder, $second->id, $files[0]), racer($second, $founder->id, $files[1])];

    $waiting = 0;

    for ($i = 0; $i < 200 && $waiting < 2; $i++) {
        usleep(100_000);
        $waiting = (int) $gate->query("SELECT count(*) FROM pg_stat_activity WHERE datname = current_database() AND wait_event_type = 'Lock'")->fetchColumn();
    }

    $gate->commit();

    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
    }

    $results = array_map(fn (string $file) => json_decode((string) file_get_contents($file), true), $files);
    array_map('unlink', $files);
    $outcomes = array_column($results, 'outcome');
    sort($outcomes);

    // The loser has just been deactivated by the winner, so it may no longer manage staff at all.
    expect($outcomes)->toBe(['not_allowed', 'won'], json_encode($results));

    DB::reconnect();

    expect(StaffMember::query()->where('role', 'admin')->where('status', 'active')->count())->toBe(1);
});
