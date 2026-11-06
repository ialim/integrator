<?php

namespace App\Modules\Shared\Actions;

use App\Modules\Shared\Alerts\FounderAlerts;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Events\OutboxListeners;
use App\Modules\Shared\Events\OutboxMessage;
use App\Modules\Shared\Events\RelayOutbox;
use App\Modules\Shared\Providers\ProviderFailure;
use App\Modules\Shared\Providers\ProviderRefused;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;
use Throwable;

/**
 * The outbox relay (AD-4). Takes due events oldest first, one at a time:
 *
 * 1. Claims the event in a short transaction of its own: the row is picked
 *    with SKIP LOCKED (still unrelayed, not given up, due by `available_at`
 *    and not under another relay's unexpired lease), its attempt is counted
 *    and a lease of {@see self::LEASE_MINUTES} minutes set in
 *    `claimed_until`; then that transaction commits. No outbox row stays
 *    locked while listeners run.
 * 2. Runs each listener outside any relay transaction, so a listener that
 *    calls a provider commits its delivery key before the call. A listener's
 *    failure is always rolled back explicitly, deadlocks and serialization
 *    failures included: any transaction level it left open is rolled back,
 *    and when the relay itself runs inside a transaction each listener gets
 *    a savepoint that is rolled back. The connection is then clean for
 *    recording the failure.
 * 3. Marks the result: relayed, or failed with the attempt already counted
 *    and the next try after 1, 2, 4 ... minutes, at most an hour.
 *
 * Delivery to listeners is at least once: an event whose relay died mid-way
 * is claimed again when its lease runs out, so listeners are idempotent (see
 * the delivery keys of `SendEmail`). An event is given up on, with a founder
 * alert, after {@see self::MAX_ATTEMPTS} attempts, or at once when a provider
 * definitively refused it.
 *
 * A run claims no new event after {@see self::TIME_BUDGET_SECONDS} seconds,
 * below the queued job's timeout, so the worker does not kill a run between
 * a provider call and marking its result.
 */
final class RelayOutboxEvents
{
    public const MAX_ATTEMPTS = 10;

    public const BATCH = 100;

    /** How long a claimed event stays with one relay: longer than a whole run. */
    public const LEASE_MINUTES = 10;

    /** A run claims no new event after this long; below {@see RelayOutbox::$timeout}. */
    public const TIME_BUDGET_SECONDS = 90;

    private const SAVEPOINT = 'outbox_listener';

    public function __construct(
        private readonly Clock $clock,
        private readonly OutboxListeners $listeners,
        private readonly FounderAlerts $alerts,
    ) {}

    /** Relays what is due now, within the time budget; returns how many events were relayed. */
    public function handle(int $limit = self::BATCH, int $budgetSeconds = self::TIME_BUDGET_SECONDS): int
    {
        $started = hrtime(true);
        $relayed = 0;

        for ($taken = 0; $taken < $limit; $taken++) {
            if ((hrtime(true) - $started) / 1e9 >= $budgetSeconds) {
                break;
            }

            $message = $this->claimNext();

            if ($message === null) {
                break;
            }

            if ($message !== false && $this->deliver($message)) {
                $relayed++;
            }
        }

        return $relayed;
    }

    /**
     * Claims the oldest due event under a lease and commits the claim.
     * Returns null when nothing is due, and false when the event had already
     * used every attempt (a relay died while running it) and is now given up.
     */
    private function claimNext(): OutboxMessage|false|null
    {
        return DB::transaction(function (): OutboxMessage|false|null {
            $now = $this->clock->now();

            $row = DB::table('outbox_events')
                ->whereNull('relayed_at')
                ->whereNull('given_up_at')
                ->where('available_at', '<=', $now)
                ->where(fn ($query) => $query->whereNull('claimed_until')->orWhere('claimed_until', '<=', $now))
                ->orderBy('position')
                ->limit(1)
                ->lock('for update skip locked')
                ->first();

            if ($row === null) {
                return null;
            }

            $message = $this->message($row);

            if ($message->attempts >= self::MAX_ATTEMPTS) {
                $this->giveUp($message, $message->attempts, 'The relay stopped before finishing this event on its last attempt.', $now);

                return false;
            }

            DB::table('outbox_events')->where('id', $message->id)->update([
                'attempts' => $message->attempts + 1,
                'claimed_until' => $now->addMinutes(self::LEASE_MINUTES),
            ]);

            return $message;
        });
    }

    private function deliver(OutboxMessage $message): bool
    {
        foreach ($this->listeners->for($message->type) as $listener) {
            try {
                $this->isolated(fn () => app($listener)->handle($message));
            } catch (Throwable $e) {
                $this->recordFailure($message, $e);

                return false;
            }
        }

        DB::table('outbox_events')->where('id', $message->id)->update([
            'relayed_at' => $this->clock->now(),
            'claimed_until' => null,
            'last_error' => null,
        ]);

        return true;
    }

    /**
     * Runs one listener so that whatever it leaves behind on failure is rolled
     * back explicitly: every transaction level it opened and, inside an
     * enclosing transaction, its own savepoint. (Laravel's nested transactions
     * do not roll their savepoint back on a deadlock or serialization failure,
     * which would leave the connection aborted.)
     */
    private function isolated(Closure $listener): void
    {
        $level = DB::transactionLevel();
        $savepoint = $level > 0;

        if ($savepoint) {
            DB::statement('SAVEPOINT '.self::SAVEPOINT);
        }

        try {
            $listener();
        } catch (Throwable $e) {
            $this->rollBackTo($level, $savepoint);

            throw $e;
        }

        if (DB::transactionLevel() !== $level) {
            $this->rollBackTo($level, $savepoint);

            throw new LogicException('An outbox listener left a database transaction open.');
        }

        if ($savepoint) {
            DB::statement('RELEASE SAVEPOINT '.self::SAVEPOINT);
        }
    }

    private function rollBackTo(int $level, bool $savepoint): void
    {
        if (DB::transactionLevel() > $level) {
            DB::rollBack($level);
        }

        if ($savepoint) {
            DB::statement('ROLLBACK TO SAVEPOINT '.self::SAVEPOINT);
            DB::statement('RELEASE SAVEPOINT '.self::SAVEPOINT);
        }
    }

    private function recordFailure(OutboxMessage $message, Throwable $e): void
    {
        $now = $this->clock->now();
        $attempts = $message->attempts + 1;
        // The class, and a provider's own words (which never hold personal values); never other messages.
        $error = $e::class.($e instanceof ProviderFailure ? ': '.mb_substr($e->getMessage(), 0, 500) : '');

        if ($attempts >= self::MAX_ATTEMPTS || $e instanceof ProviderRefused) {
            $this->giveUp($message, $attempts, $error, $now);

            return;
        }

        DB::table('outbox_events')->where('id', $message->id)->update([
            'attempts' => $attempts,
            'last_error' => $error,
            // 1, 2, 4 ... minutes, at most an hour.
            'available_at' => $now->addMinutes(min(60, 2 ** ($attempts - 1))),
            'claimed_until' => null,
        ]);

        Log::warning('outbox.relay_failed', [
            'event_id' => $message->id,
            'type' => $message->type,
            'attempts' => $attempts,
            'error' => $e::class,
            'given_up' => false,
        ]);
    }

    private function giveUp(OutboxMessage $message, int $attempts, string $error, CarbonImmutable $now): void
    {
        DB::table('outbox_events')->where('id', $message->id)->update([
            'attempts' => $attempts,
            'last_error' => mb_substr($error, 0, 700),
            'claimed_until' => null,
            'given_up_at' => $now,
        ]);

        Log::warning('outbox.relay_failed', [
            'event_id' => $message->id,
            'type' => $message->type,
            'attempts' => $attempts,
            'error' => strtok($error, ':'),
            'given_up' => true,
        ]);

        DB::afterCommit(fn () => $this->alerts->raise('outbox_given_up', 'An outbox event was given up on', [
            'Event' => "{$message->type} (event id {$message->id})",
            'What' => "It is no longer retried, after {$attempts} attempt".($attempts === 1 ? '' : 's').'. Last error: '.$error,
        ]));
    }

    private function message(object $row): OutboxMessage
    {
        /** @var object{id: string, type: string, aggregate_type: string, aggregate_id: string, sequence: int|string, payload: string, occurred_at: string, attempts: int|string} $row */
        $payload = json_decode($row->payload, true, flags: JSON_THROW_ON_ERROR);

        return new OutboxMessage(
            $row->id,
            $row->type,
            $row->aggregate_type,
            $row->aggregate_id,
            (int) $row->sequence,
            is_array($payload) ? $payload : [],
            CarbonImmutable::parse($row->occurred_at)->utc(),
            (int) $row->attempts,
        );
    }
}
