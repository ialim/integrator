<?php

use App\Modules\Shared\Actions\SealAuditEntries;
use App\Modules\Shared\Actions\WriteAuditEntry;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Audit\AuditSeal;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Queries\AuditChain;
use App\Modules\Shared\Queries\ChainCheck;
use App\Modules\Shared\Support\Id;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/*
 * The hash chain. These tests tamper with the trail on purpose (disabling the
 * trigger, rewriting rows), so they live outside app/Modules, where the
 * single-write-path rule allows only Actions to write.
 */

uses(RefreshDatabase::class);

beforeEach(fn () => app(Clock::class)->set('2026-11-09T09:00:00Z'));
afterEach(fn () => app(Clock::class)->reset());

/** Writes one committed audit entry; returns its sequence. */
function chainEntry(string $action = 'probe.done'): int
{
    $id = DB::transaction(fn () => app(WriteAuditEntry::class)->handle(Actor::system(), $action, 'probes', Id::generate(), ['status' => ['from' => 'open', 'to' => 'closed']]));

    return (int) DB::table('audit_entries')->where('id', $id)->value('sequence');
}

/** A sequence number used by a transaction that rolled back: a gap in the trail. */
function rolledBackSequence(): int
{
    $sequence = 0;

    try {
        DB::transaction(function () use (&$sequence) {
            $id = app(WriteAuditEntry::class)->handle(Actor::system(), 'probe.undone', 'probes', Id::generate(), []);
            $sequence = (int) DB::table('audit_entries')->where('id', $id)->value('sequence');

            throw new RuntimeException('the change failed');
        });
    } catch (RuntimeException) {
    }

    return $sequence;
}

function withoutTrigger(Closure $tamper): void
{
    DB::statement('ALTER TABLE audit_entries DISABLE TRIGGER audit_entries_insert_only');
    DB::statement('ALTER TABLE audit_seals DISABLE TRIGGER audit_seals_insert_only');
    $tamper();
    DB::statement('ALTER TABLE audit_entries ENABLE ALWAYS TRIGGER audit_entries_insert_only');
    DB::statement('ALTER TABLE audit_seals ENABLE ALWAYS TRIGGER audit_seals_insert_only');
}

it('seals unsealed rows in sequence order, each linked to the previous, without changing a row', function () {
    $first = chainEntry('probe.one');
    $gap = rolledBackSequence();
    $second = chainEntry('probe.two');
    $third = chainEntry('probe.three');
    $entriesBefore = DB::table('audit_entries')->orderBy('sequence')->get()->all();

    $result = app(SealAuditEntries::class)->handle();

    $seals = DB::table('audit_seals')->orderBy('sequence')->get()->all();

    expect($result->sealed)->toBe(3)
        ->and($result->busy)->toBeFalse()
        ->and($result->lastSealedSequence)->toBe($third)
        ->and(array_map(fn ($seal) => (int) $seal->sequence, $seals))->toBe([$first, $second, $third])
        ->and($gap)->toBeGreaterThan($first)->toBeLessThan($second)
        ->and($seals[0]->previous_hash)->toBe(AuditSeal::GENESIS)
        ->and($seals[1]->previous_hash)->toBe($seals[0]->hash)
        ->and($seals[2]->previous_hash)->toBe($seals[1]->hash)
        ->and(DB::table('audit_entries')->orderBy('sequence')->get()->all())->toEqual($entriesBefore);

    // The next run seals only what is new, linked to the last seal; nothing is sealed twice.
    expect(app(SealAuditEntries::class)->handle()->sealed)->toBe(0);
    $fourth = chainEntry('probe.four');
    expect(app(SealAuditEntries::class)->handle()->sealed)->toBe(1)
        ->and(DB::table('audit_seals')->where('sequence', $fourth)->value('previous_hash'))->toBe($seals[2]->hash);
});

it('reports an intact chain with the last sealed sequence; gaps from rolled-back writes are normal', function () {
    expect(app(AuditChain::class)->check())->toEqual(new ChainCheck(true, null, 0));

    chainEntry();
    rolledBackSequence();
    $last = chainEntry();
    app(SealAuditEntries::class)->handle();
    chainEntry();

    expect(app(AuditChain::class)->check())->toEqual(new ChainCheck(true, $last, 1));
});

it('names the first sequence where a changed row breaks the chain', function () {
    chainEntry();
    $changed = chainEntry();
    chainEntry();
    app(SealAuditEntries::class)->handle();

    withoutTrigger(fn () => DB::table('audit_entries')->where('sequence', $changed)->update(['changes' => json_encode(['status' => ['from' => 'open', 'to' => 'reopened']])]));

    $check = app(AuditChain::class)->check();

    expect([$check->intact, $check->brokenAt, $check->problem])->toBe([false, $changed, ChainCheck::CHANGED]);
});

it('names a missing row', function () {
    chainEntry();
    $missing = chainEntry();
    chainEntry();
    app(SealAuditEntries::class)->handle();

    withoutTrigger(fn () => DB::table('audit_entries')->where('sequence', $missing)->delete());

    $check = app(AuditChain::class)->check();

    expect([$check->intact, $check->brokenAt, $check->problem])->toBe([false, $missing, ChainCheck::MISSING]);
});

it('names an extra row slipped in behind the chain', function () {
    chainEntry();
    $gap = rolledBackSequence();
    chainEntry();
    app(SealAuditEntries::class)->handle();

    // An insert is allowed, so no trigger needs disabling: a row written straight into the gap.
    DB::table('audit_entries')->insert([
        'id' => Id::generate(), 'sequence' => $gap, 'occurred_at' => app(Clock::class)->now(), 'actor_type' => 'system',
        'action' => 'probe.slipped_in', 'subject_type' => 'probes', 'changes' => '{}',
    ]);

    $check = app(AuditChain::class)->check();

    expect([$check->intact, $check->brokenAt, $check->problem])->toBe([false, $gap, ChainCheck::EXTRA]);
});

it('names a seal whose hash was changed', function () {
    $first = chainEntry();
    chainEntry();
    app(SealAuditEntries::class)->handle();

    withoutTrigger(fn () => DB::table('audit_seals')->where('sequence', $first)->update(['hash' => str_repeat('a', 64)]));

    $check = app(AuditChain::class)->check();

    expect([$check->intact, $check->brokenAt, $check->problem])->toBe([false, $first, ChainCheck::CHANGED]);
});

it('hashes the stored values whatever the session time zone', function () {
    chainEntry();
    app(SealAuditEntries::class)->handle();

    DB::statement("SET LOCAL TIME ZONE 'Africa/Lagos'");

    expect(app(AuditChain::class)->check()->intact)->toBeTrue();
});

it('seals more than one batch in a single run, with the chain intact', function () {
    DB::transaction(function () {
        for ($i = 0; $i < 501; $i++) {
            app(WriteAuditEntry::class)->handle(Actor::system(), 'probe.done', 'probes', Id::generate(), []);
        }
    });

    $result = app(SealAuditEntries::class)->handle();

    expect($result->sealed)->toBe(501)
        ->and(DB::table('audit_seals')->count())->toBe(501)
        ->and(app(AuditChain::class)->check())->toEqual(new ChainCheck(true, $result->lastSealedSequence, 0));
});
