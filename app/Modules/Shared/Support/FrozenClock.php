<?php

namespace App\Modules\Shared\Support;

use App\Modules\Shared\Contracts\Clock;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use DateInterval;
use DateTimeInterface;

/**
 * The settable Clock for testing and staging. Until it is set it reads the
 * real time; once set it stays at that instant until advanced or reset.
 *
 * Setting it also moves Carbon's test time, so framework-written times
 * (model timestamps, cache expiry) agree with the Clock.
 */
final class FrozenClock implements Clock
{
    private ?CarbonImmutable $frozenAt = null;

    public function now(): CarbonImmutable
    {
        return $this->frozenAt ?? CarbonImmutable::now('UTC');
    }

    public function set(DateTimeInterface|string $instant): CarbonImmutable
    {
        $this->frozenAt = CarbonImmutable::parse($instant)->utc();

        Carbon::setTestNow($this->frozenAt);
        CarbonImmutable::setTestNow($this->frozenAt);

        return $this->frozenAt;
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
        return $this->frozenAt !== null;
    }

    public function reset(): void
    {
        $this->frozenAt = null;

        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
    }
}
