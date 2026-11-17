<?php

namespace App\Modules\Shared\Support;

use App\Modules\Shared\Contracts\Clock;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository;

/**
 * The settable Clock for testing and staging. Until it is set it reads the
 * real time; once set it stays at that instant until advanced or reset.
 *
 * Setting it also moves Carbon's test time, so framework-written times
 * (model timestamps, cache expiry) agree with the Clock.
 *
 * In staging the instant is kept in the shared cache ({@see self::persistIn()}),
 * so one `php artisan clock:set` reaches every web request, queue worker and
 * command, and survives between them. In tests it stays in memory.
 */
final class FrozenClock implements Clock
{
    public const STORE_KEY = 'shared.clock.frozen_at';

    private ?CarbonImmutable $frozenAt = null;

    private ?Repository $store = null;

    /** Keeps the instant in $store from now on, and takes up any instant already there. */
    public function persistIn(Repository $store): void
    {
        $this->store = $store;
        $this->sync();
    }

    public function now(): CarbonImmutable
    {
        $this->sync();

        return $this->frozenAt ?? CarbonImmutable::now('UTC');
    }

    public function set(DateTimeInterface|string $instant): CarbonImmutable
    {
        $this->freezeAt(CarbonImmutable::parse($instant)->utc());
        $this->store?->forever(self::STORE_KEY, $this->frozenAt?->format('Y-m-d\TH:i:s.u\Z'));

        return $this->now();
    }

    /**
     * Moves the Clock forward without any real wait, for example
     * `advance('2 hours')` or `advance(CarbonInterval::minutes(5))`.
     */
    public function advance(DateInterval|string $interval): CarbonImmutable
    {
        $interval = is_string($interval) ? CarbonInterval::fromString($interval) : $interval;

        return $this->set($this->now()->add($interval));
    }

    public function isFrozen(): bool
    {
        $this->sync();

        return $this->frozenAt !== null;
    }

    public function reset(): void
    {
        $this->store?->forget(self::STORE_KEY);
        $this->freezeAt(null);
    }

    /** Another process may have set, advanced or reset the stored instant: follow it. */
    private function sync(): void
    {
        if ($this->store === null) {
            return;
        }

        $stored = $this->store->get(self::STORE_KEY);
        $instant = is_string($stored) ? CarbonImmutable::parse($stored)->utc() : null;

        if ($instant?->format('Y-m-d\TH:i:s.u') !== $this->frozenAt?->format('Y-m-d\TH:i:s.u')) {
            $this->freezeAt($instant);
        }
    }

    private function freezeAt(?CarbonImmutable $instant): void
    {
        $this->frozenAt = $instant;

        Carbon::setTestNow($instant);
        CarbonImmutable::setTestNow($instant);
    }
}
