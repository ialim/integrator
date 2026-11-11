<?php

use App\Modules\Shared\Actions\SealAuditEntries;
use App\Modules\Shared\Actions\WriteAuditEntry;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Audit\AuditProtection;
use App\Modules\Shared\Audit\PersonalFields;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Queries\AuditTrail;
use App\Modules\Shared\Support\Id;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/*
 * The insert-only triggers. These tests try UPDATE, DELETE and TRUNCATE on
 * purpose, so they live outside app/Modules, where the single-write-path rule
 * allows only Actions to write.
 */

uses(RefreshDatabase::class);

beforeEach(fn () => app(Clock::class)->set('2026-11-09T09:00:00Z'));
afterEach(fn () => app(Clock::class)->reset());

/** Runs $sql in a savepoint and returns the database's refusal, or null when it was accepted. */
function auditRefusal(string $sql): ?string
{
    try {
        DB::transaction(fn () => DB::statement($sql));
    } catch (QueryException $e) {
        return $e->getMessage();
    }

    return null;
}

function writeProbeEntry(string $action = 'probe.done'): string
{
    return DB::transaction(fn () => app(WriteAuditEntry::class)->handle(Actor::system(), $action, 'probes', Id::generate(), ['status' => ['from' => 'open', 'to' => 'closed']]));
}

it('rejects UPDATE, DELETE and TRUNCATE on the audit rows and the seals, for the table owner too', function (string $table, string $statement) {
    writeProbeEntry();
    app(SealAuditEntries::class)->handle();
    $before = DB::table($table)->orderBy('sequence')->get()->all();

    // The test connection owns the tables.
    expect(DB::scalar('SELECT tableowner FROM pg_tables WHERE tablename = ?', [$table]))->toBe(DB::scalar('SELECT current_user'));

    $refusal = auditRefusal(str_replace('{table}', $table, $statement));

    expect($refusal)->toContain('The audit trail is insert-only')
        ->and($refusal)->toContain($table)
        ->and(DB::table($table)->orderBy('sequence')->get()->all())->toEqual($before);
})->with(['audit_entries', 'audit_seals'])->with([
    'UPDATE' => 'UPDATE {table} SET sequence = sequence',
    'UPDATE of no row' => 'UPDATE {table} SET sequence = sequence WHERE false',
    'DELETE' => 'DELETE FROM {table}',
    'TRUNCATE' => 'TRUNCATE {table}',
    'TRUNCATE CASCADE' => 'TRUNCATE {table} CASCADE',
]);

it('keeps rejecting changes under the replica session role, which skips ordinary triggers', function () {
    writeProbeEntry();

    try {
        DB::transaction(fn () => DB::statement('SET LOCAL session_replication_role = replica'));
    } catch (QueryException) {
        $this->markTestSkipped('Setting session_replication_role needs a superuser test connection.');
    }

    expect(auditRefusal('UPDATE audit_entries SET action = action'))->toContain('insert-only');
});

it('still inserts, and the protection SQL can run again without loosening anything', function () {
    DB::unprepared(AuditProtection::sql());
    DB::unprepared(AuditProtection::sql());

    $id = writeProbeEntry();

    expect(DB::table('audit_entries')->where('id', $id)->exists())->toBeTrue()
        ->and(auditRefusal('DELETE FROM audit_entries'))->toContain('insert-only')
        ->and(DB::scalar("SELECT count(*) FROM pg_trigger WHERE tgname IN ('audit_entries_insert_only', 'audit_seals_insert_only') AND tgenabled = 'A'"))->toBe(2);
});

it('records a personal field only as "changed", whoever built the changes', function () {
    app(PersonalFields::class)->declare('staff_members', ['email', 'name']);
    $member = Id::generate();

    DB::transaction(fn () => app(WriteAuditEntry::class)->handle(
        Actor::staff(Id::generate()),
        'probe.email_changed',
        'staff_members',
        $member,
        ['email' => ['from' => 'old@example.test', 'to' => 'new@example.test'], 'name' => 'Bola Ade', 'role' => ['from' => 'finance', 'to' => 'admin']],
    ));

    $stored = (string) DB::table('audit_entries')->where('subject_id', $member)->value('changes');

    expect(app(AuditTrail::class)->forSubject('staff_members', $member)[0]->changes)->toEqual([
        'email' => 'changed',
        'name' => 'changed',
        'role' => ['from' => 'finance', 'to' => 'admin'],
    ])->and($stored)->not->toContain('example.test')
        ->and($stored)->not->toContain('Bola');
});

it('writes acting_as as a role and acting_for as a person', function () {
    $admin = Id::generate();
    $client = Id::generate();
    $subject = Id::generate();

    DB::transaction(fn () => app(WriteAuditEntry::class)->handle(Actor::staff($admin)->actingAs('finance')->actingFor($client), 'probe.confirmed', 'probes', $subject, []));

    $entry = app(AuditTrail::class)->forSubject('probes', $subject)[0];

    expect([$entry->actorId, $entry->actingAs, $entry->actingFor])->toBe([$admin, 'finance', $client]);
});
