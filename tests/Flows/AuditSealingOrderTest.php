<?php

use App\Modules\Shared\Actions\SealAuditEntries;
use App\Modules\Shared\Actions\WriteAuditEntry;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Queries\AuditChain;
use App\Modules\Shared\Support\Id;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/*
 * A lower audit sequence commits after a higher one, in two real processes
 * with their own database connections. The sealer must seal neither until
 * both are committed, then seal both in order, so the late commit never
 * slips behind the chain. No RefreshDatabase: each writer must commit. The
 * audit trail refuses TRUNCATE, so the database is marked for a fresh
 * migration afterwards instead.
 */

beforeEach(function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('The commit-order case needs pcntl, which the Docker image and CI have.');
    }

    if (! RefreshDatabaseState::$migrated) {
        Artisan::call('migrate:fresh');
        RefreshDatabaseState::$migrated = true;
    }

    app(Clock::class)->set('2026-11-09T09:00:00Z');
});

afterEach(function () {
    DB::reconnect();
    RefreshDatabaseState::$migrated = false;
    app(Clock::class)->reset();
});

function waitForFile(string $file, int $tenthsOfASecond = 100): bool
{
    for ($i = 0; $i < $tenthsOfASecond; $i++) {
        clearstatcache();

        if (is_file($file) && filesize($file) > 0) {
            return true;
        }

        usleep(100_000);
    }

    return false;
}

it('seals nothing while a lower sequence is still open, then seals both in sequence order', function () {
    $dir = sys_get_temp_dir().'/audit-order-'.Id::generate();
    mkdir($dir);
    $written = "{$dir}/written";
    $release = "{$dir}/release";
    $committed = "{$dir}/committed";

    DB::disconnect();
    $pid = pcntl_fork();

    if ($pid === 0) {
        // The child: an Action that writes its audit row first, then keeps its transaction open until released.
        try {
            DB::reconnect();
            DB::transaction(function () use ($written, $release) {
                $id = app(WriteAuditEntry::class)->handle(Actor::system(), 'probe.slow', 'probes', Id::generate(), []);
                file_put_contents($written, (string) DB::table('audit_entries')->where('id', $id)->value('sequence'));

                for ($i = 0; $i < 300 && ! is_file($release); $i++) {
                    usleep(100_000);
                }
            });
            file_put_contents($committed, 'committed');
        } catch (Throwable $e) {
            file_put_contents($committed, 'error: '.$e::class.': '.$e->getMessage());
        }

        posix_kill(posix_getpid(), SIGKILL);

        exit(1);
    }

    expect(waitForFile($written))->toBeTrue('the slow writer wrote its row');
    $lower = (int) file_get_contents($written);

    // A fast Action commits a higher sequence while the slow one is still open.
    DB::reconnect();
    $id = DB::transaction(fn () => app(WriteAuditEntry::class)->handle(Actor::system(), 'probe.fast', 'probes', Id::generate(), []));
    $higher = (int) DB::table('audit_entries')->where('id', $id)->value('sequence');

    expect($higher)->toBeGreaterThan($lower);

    // The sealer waits for the open writer, gives up for this run and seals nothing, not even the committed higher row.
    $busy = app(SealAuditEntries::class)->handle();

    expect($busy->busy)->toBeTrue()
        ->and($busy->sealed)->toBe(0)
        ->and(DB::table('audit_seals')->count())->toBe(0);

    touch($release);
    pcntl_waitpid($pid, $status);
    expect(file_get_contents($committed))->toBe('committed');

    $sealed = app(SealAuditEntries::class)->handle();

    expect($sealed->busy)->toBeFalse()
        ->and($sealed->sealed)->toBe(2)
        ->and(DB::table('audit_seals')->orderBy('sequence')->pluck('sequence')->map(fn ($sequence) => (int) $sequence)->all())->toBe([$lower, $higher])
        ->and(app(AuditChain::class)->check()->intact)->toBeTrue()
        ->and(app(AuditChain::class)->check()->lastSealedSequence)->toBe($higher);

    array_map('unlink', glob("{$dir}/*") ?: []);
    rmdir($dir);
});
