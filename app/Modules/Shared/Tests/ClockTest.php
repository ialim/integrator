<?php

use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Support\FrozenClock;
use App\Modules\Shared\Support\SystemClock;
use Carbon\CarbonImmutable;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;

// Reset the Clock and Carbon's test time, even when a test swapped in its own Clock.
afterEach(fn () => (new FrozenClock)->reset());

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

it('shares a stored instant between Clocks, as staging does across processes', function () {
    $store = new Repository(new ArrayStore);
    $web = new FrozenClock;
    $worker = new FrozenClock;
    $web->persistIn($store);
    $worker->persistIn($store);

    $web->set('2026-11-20T09:00:00Z');
    expect($worker->now()->toIso8601ZuluString())->toBe('2026-11-20T09:00:00Z');

    $worker->advance('2 days');
    expect($web->now()->toIso8601ZuluString())->toBe('2026-11-22T09:00:00Z');

    $web->reset();
    expect($worker->isFrozen())->toBeFalse()
        ->and($store->has(FrozenClock::STORE_KEY))->toBeFalse();
});

it('takes up an instant already stored, as a new process does', function () {
    $store = new Repository(new ArrayStore);
    $store->forever(FrozenClock::STORE_KEY, '2026-11-20T09:00:00.000000Z');

    $later = new FrozenClock;
    $later->persistIn($store);

    expect($later->isFrozen())->toBeTrue()
        ->and($later->now()->toIso8601ZuluString())->toBe('2026-11-20T09:00:00Z')
        ->and(CarbonImmutable::now()->toIso8601ZuluString())->toBe('2026-11-20T09:00:00Z');
});

it('sets, advances, shows and resets the Clock from the command line', function () {
    $this->artisan('clock:set', ['instant' => '2026-11-20T09:00:00Z'])
        ->expectsOutput('The Clock now reads 2026-11-20T09:00:00Z.')->assertSuccessful();
    $this->artisan('clock:advance', ['interval' => '3 hours'])
        ->expectsOutput('The Clock now reads 2026-11-20T12:00:00Z.')->assertSuccessful();
    $this->artisan('clock:show')->expectsOutput('The Clock reads 2026-11-20T12:00:00Z (frozen).')->assertSuccessful();
    $this->artisan('clock:reset')->expectsOutput('The Clock reads the real time again.')->assertSuccessful();

    expect(app(Clock::class)->isFrozen())->toBeFalse();
});

it('refuses to set the Clock where it reads the real time, as in production', function () {
    app()->instance(Clock::class, new SystemClock);

    $this->artisan('clock:set', ['instant' => '2026-11-20T09:00:00Z'])
        ->expectsOutput('The Clock can be set only in testing and staging; this environment reads the real time.')
        ->assertFailed();
    $this->artisan('clock:advance', ['interval' => '1 day'])->assertFailed();
    $this->artisan('clock:reset')->assertFailed();
});
