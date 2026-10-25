<?php

use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Support\FrozenClock;
use App\Modules\Shared\Support\SystemClock;
use Carbon\CarbonImmutable;

afterEach(fn () => app(Clock::class) instanceof FrozenClock && app(Clock::class)->reset());

it('binds the settable FrozenClock in testing', function () {
    expect(app(Clock::class))->toBeInstanceOf(FrozenClock::class)
        ->and(app(Clock::class))->toBe(app(Clock::class));
});

it('returns the set instant, then that instant plus two hours, with no real wait', function () {
    $clock = app(Clock::class);

    $clock->set('2026-10-25T09:00:00Z');
    expect($clock->now()->toIso8601ZuluString())->toBe('2026-10-25T09:00:00Z');

    $started = hrtime(true);
    $clock->advance('2 hours');

    expect($clock->now()->toIso8601ZuluString())->toBe('2026-10-25T11:00:00Z')
        ->and(hrtime(true) - $started)->toBeLessThan(1_000_000_000);
});

it('moves framework time with it, so model timestamps agree', function () {
    app(Clock::class)->set('2026-10-25T09:00:00Z');

    expect(CarbonImmutable::now()->toIso8601ZuluString())->toBe('2026-10-25T09:00:00Z');
});

it('reads the real time again after a reset', function () {
    $clock = app(Clock::class);
    $clock->set('2001-01-01T00:00:00Z');
    $clock->reset();

    expect($clock->isFrozen())->toBeFalse()
        ->and($clock->now()->year)->toBeGreaterThan(2001);
});

it('always reads UTC', function () {
    expect((new SystemClock)->now()->tzName)->toBe('UTC')
        ->and(app(Clock::class)->set(new DateTimeImmutable('2026-10-25 10:00:00', new DateTimeZone('Africa/Lagos')))->toIso8601ZuluString())
        ->toBe('2026-10-25T09:00:00Z');
});
