<?php

namespace App\Modules\Staff\Http;

use App\Modules\Shared\Contracts\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Session\Session;

/**
 * The staff session's fixed length: it ends 12 hours after sign-in, whatever
 * the activity, with a warning 5 minutes before. "Stay signed in" asks for the
 * authenticator code again and starts a fresh 12 hours.
 */
final class StaffSession
{
    public const KEY = 'staff_session.started_at';

    public const LENGTH_SECONDS = 12 * 60 * 60;

    public const WARNING_SECONDS = 5 * 60;

    public function __construct(private readonly Clock $clock) {}

    public function start(Session $session): CarbonImmutable
    {
        $now = $this->clock->now();
        $session->put(self::KEY, $now->getTimestamp());

        return $now->addSeconds(self::LENGTH_SECONDS);
    }

    public function startedAt(Session $session): ?CarbonImmutable
    {
        $started = $session->get(self::KEY);

        return is_int($started) ? CarbonImmutable::createFromTimestampUTC($started) : null;
    }

    public function endsAt(Session $session): ?CarbonImmutable
    {
        return $this->startedAt($session)?->addSeconds(self::LENGTH_SECONDS);
    }

    public function secondsLeft(Session $session): int
    {
        $endsAt = $this->endsAt($session);

        return $endsAt === null ? 0 : max(0, $endsAt->getTimestamp() - $this->clock->now()->getTimestamp());
    }

    public function hasEnded(Session $session): bool
    {
        return $this->secondsLeft($session) === 0;
    }

    public function isInWarning(Session $session): bool
    {
        $left = $this->secondsLeft($session);

        return $left > 0 && $left <= self::WARNING_SECONDS;
    }

    public function now(): CarbonImmutable
    {
        return $this->clock->now();
    }
}
