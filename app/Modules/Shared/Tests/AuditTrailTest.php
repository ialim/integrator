<?php

use App\Modules\Shared\Actions\WriteAuditEntry;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Audit\PersonalFields;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Queries\AuditTrail;
use App\Modules\Shared\Support\Id;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(fn () => app(Clock::class)->set('2026-10-26T09:00:00Z'));
afterEach(fn () => app(Clock::class)->reset());

it('rolls the audit row back with the change it describes', function () {
    try {
        DB::transaction(function () {
            app(WriteAuditEntry::class)->handle(Actor::system(), 'probe.done', 'probes', Id::generate(), []);

            throw new RuntimeException('the change failed');
        });
    } catch (RuntimeException) {
    }

    expect(app(AuditTrail::class)->all())->toBe([]);
});

it('numbers rows in order and records the actor, time and changes', function () {
    $staffId = Id::generate();
    $clientId = Id::generate();
    $subject = Id::generate();

    DB::transaction(function () use ($staffId, $clientId, $subject) {
        app(WriteAuditEntry::class)->handle(Actor::system(), 'probe.created', 'probes', $subject, ['status' => ['from' => null, 'to' => 'open']]);
        app(WriteAuditEntry::class)->handle(Actor::staff($staffId)->actingFor($clientId), 'probe.closed', 'probes', $subject, ['status' => ['from' => 'open', 'to' => 'closed']]);
    });

    $entries = app(AuditTrail::class)->forSubject('probes', $subject);

    expect($entries)->toHaveCount(2)
        ->and($entries[0]->sequence)->toBeLessThan($entries[1]->sequence)
        ->and([$entries[0]->actorType, $entries[0]->actorId])->toBe(['system', null])
        ->and([$entries[1]->actorType, $entries[1]->actorId])->toBe(['staff', $staffId])
        ->and([$entries[1]->actingAs, $entries[1]->actingFor])->toBe([null, $clientId])
        ->and($entries[1]->requestId)->not->toBeNull()
        ->and($entries[1]->changes)->toEqual(['status' => ['from' => 'open', 'to' => 'closed']])
        ->and($entries[1]->occurredAt->toIso8601ZuluString())->toBe('2026-10-26T09:00:00Z');
});

it('records a personal column only as changed', function () {
    $fields = app(PersonalFields::class);
    $fields->declare('probes', ['name']);

    expect($fields->isPersonal('probes', 'name'))->toBeTrue()
        ->and($fields->isPersonal('probes', 'status'))->toBeFalse()
        ->and($fields->of('no_such_table'))->toBe([]);
});
