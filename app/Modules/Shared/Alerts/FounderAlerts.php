<?php

namespace App\Modules\Shared\Alerts;

use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Logging\RequestId;
use App\Modules\Shared\Support\Iso8601;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

/**
 * Alerts to the founder by email (AD-20).
 *
 * - Sent synchronously, never through the queue they may be reporting on.
 * - One alert per kind per throttle window (15 minutes). Occurrences inside
 *   the window are held and counted; once the window has closed, the held
 *   count goes out in one follow-up alert, sent by `alerts:send-held` (run
 *   each minute by the scheduler) or by the next occurrence, whichever is first.
 * - Never throws: a failure to alert is logged as JSON with the request id.
 * - Times are shown in WAT and UTC, read from the Shared Clock.
 */
final class FounderAlerts
{
    public const KIND_FAILED_JOB = 'failed_job';

    private const PREFIX = 'founder-alerts:';

    private const PENDING = self::PREFIX.'pending';

    private const STATE_TTL_SECONDS = 172800;

    public function __construct(private readonly Clock $clock) {}

    /** The JobFailed listener: a queued job has failed permanently. */
    public function jobFailed(JobFailed $event): void
    {
        try {
            $job = $event->job->resolveName();
            $queue = (string) $event->job->getQueue();

            $this->raise(self::KIND_FAILED_JOB, 'Failed job', [
                'What failed' => $job,
                'Job id (for queue:retry)' => (string) ($event->job->uuid() ?? 'none'),
                'Queue' => $queue,
                'Connection' => $event->connectionName,
                'Error' => $event->exception::class,
            ], "{$job} on {$queue}");
        } catch (Throwable $e) {
            $this->logFailure(self::KIND_FAILED_JOB, $e);
        }
    }

    /**
     * Raises one occurrence of an alert. Returns true when an email went out
     * now, false when it was held by the throttle or could not be sent.
     *
     * The throttle decision and the new window are saved under a short lock;
     * the email is sent after the lock is released, so a slow or down channel
     * never holds the lock, and a failed send still throttles the kind.
     *
     * @param  array<string, string>  $facts  label => value, shown in order
     * @param  string|null  $group  what to count held occurrences by, e.g. "Job on queue"
     */
    public function raise(string $kind, string $title, array $facts, ?string $group = null): bool
    {
        try {
            $lines = $this->lock($kind)->block(5, function () use ($kind, $title, $facts, $group) {
                $now = $this->clock->now();
                $state = $this->state($kind);
                $group ??= $title;

                if ($state !== null && $now->lessThan($this->windowEnds($state))) {
                    $state['held']++;
                    $state['held_by'][$group] = ($state['held_by'][$group] ?? 0) + 1;
                    $state['last'] = ['title' => $title, 'facts' => $facts, 'at' => Iso8601::format($now), 'request_id' => RequestId::current()];
                    $this->save($kind, $state);
                    $this->markPending($kind);

                    return null;
                }

                $lines = $this->factLines($facts, $now, RequestId::current());

                if ($state !== null && $state['held'] > 0) {
                    $lines = [...$lines, ...$this->heldLines($state)];
                }

                $this->save($kind, $this->freshWindow($now));

                return $lines;
            });
        } catch (Throwable $e) {
            // The throttle store or its lock is down (the database, say): one
            // unthrottled attempt, so the founder still hears of it.
            $sent = $this->trySend($kind, $title, $this->factLines($facts, $this->clock->now(), RequestId::current()));
            $this->logFailure($kind, $e);

            return $sent;
        }

        return $lines === null ? false : $this->trySend($kind, $title, $lines);
    }

    /**
     * Sends one follow-up alert for each kind whose window has closed with
     * occurrences held back. Returns the number of alerts sent.
     */
    public function sendHeld(): int
    {
        $sent = 0;

        try {
            $pending = $this->store()->get(self::PENDING, []);
        } catch (Throwable $e) {
            $this->logFailure('held', $e);

            return 0;
        }

        foreach (is_array($pending) ? $pending : [] as $kind) {
            try {
                $alert = $this->lock($kind)->block(5, function () use ($kind) {
                    $now = $this->clock->now();
                    $state = $this->state($kind);

                    if ($state === null || $state['held'] === 0) {
                        $this->unmarkPending($kind);

                        return null;
                    }

                    if ($now->lessThan($this->windowEnds($state))) {
                        return null;
                    }

                    $last = $state['last'];
                    $lines = [
                        "This happened {$state['held']} more ".($state['held'] === 1 ? 'time' : 'times').' since the last alert, '
                            .($state['held'] + 1).' in all since '.AlertTime::format($state['window_started_at']).'.',
                        ...$this->heldBreakdown($state),
                        'The most recent one:',
                        ...$this->factLines($last['facts'], CarbonImmutable::parse($last['at']), $last['request_id'] ?? RequestId::current()),
                    ];

                    $this->save($kind, ['window_started_at' => Iso8601::format($now), 'held' => 0, 'held_by' => [], 'last' => $last]);
                    $this->unmarkPending($kind);

                    return ['title' => "{$last['title']}: {$state['held']} more", 'lines' => $lines];
                });
            } catch (Throwable $e) {
                $this->logFailure($kind, $e);

                continue;
            }

            if ($alert !== null && $this->trySend($kind, $alert['title'], $alert['lines'])) {
                $sent++;
            }
        }

        return $sent;
    }

    /** @param list<string> $lines */
    private function trySend(string $kind, string $title, array $lines): bool
    {
        try {
            $this->send($title, $lines);

            return true;
        } catch (Throwable $e) {
            $this->logFailure($kind, $e);

            return false;
        }
    }

    /** @param list<string> $lines */
    private function send(string $title, array $lines): void
    {
        $to = config('alerts.founder_email');

        if (! is_string($to) || $to === '') {
            throw new RuntimeException('No founder address is set (FOUNDER_ALERT_EMAIL).');
        }

        $mailer = config('alerts.mailer');

        Mail::mailer(is_string($mailer) && $mailer !== '' ? $mailer : null)
            ->to($to)
            ->send(new FounderAlert($title, $lines));
    }

    /**
     * @param  array<string, string>  $facts
     * @return list<string>
     */
    private function factLines(array $facts, CarbonImmutable $at, string $requestId): array
    {
        $lines = [];

        foreach ($facts as $label => $value) {
            $lines[] = "{$label}: {$value}";
        }

        $lines[] = 'When: '.AlertTime::format($at);
        $lines[] = 'Request id: '.$requestId;

        return $lines;
    }

    /**
     * @param  array{window_started_at: string, held: int, held_by: array<string, int>}  $state
     * @return list<string>
     */
    private function heldLines(array $state): array
    {
        return [
            "Also held back before this one: {$state['held']} more since ".AlertTime::format($state['window_started_at']).'.',
            ...$this->heldBreakdown($state),
        ];
    }

    /**
     * @param  array{held_by: array<string, int>}  $state
     * @return list<string>
     */
    private function heldBreakdown(array $state): array
    {
        $lines = [];

        foreach ($state['held_by'] as $group => $count) {
            $lines[] = "  {$group}: {$count}";
        }

        return $lines;
    }

    /**
     * @return array{window_started_at: string, held: int, held_by: array<string, int>, last: array{title: string, facts: array<string, string>, at: string, request_id?: string}}|null
     */
    private function state(string $kind): ?array
    {
        $state = $this->store()->get(self::PREFIX.$kind);

        /** @var array{window_started_at: string, held: int, held_by: array<string, int>, last: array{title: string, facts: array<string, string>, at: string, request_id?: string}}|null $state */
        return is_array($state) ? $state : null;
    }

    /** @param array<string, mixed> $state */
    private function save(string $kind, array $state): void
    {
        $this->store()->put(self::PREFIX.$kind, $state, self::STATE_TTL_SECONDS);
    }

    /** @return array<string, mixed> */
    private function freshWindow(CarbonImmutable $now): array
    {
        return ['window_started_at' => Iso8601::format($now), 'held' => 0, 'held_by' => [], 'last' => null];
    }

    /** @param array{window_started_at: string} $state */
    private function windowEnds(array $state): CarbonImmutable
    {
        return CarbonImmutable::parse($state['window_started_at'])->addMinutes((int) config('alerts.throttle_minutes', 15));
    }

    private function markPending(string $kind): void
    {
        $pending = $this->store()->get(self::PENDING, []);
        $pending = is_array($pending) ? $pending : [];

        if (! in_array($kind, $pending, true)) {
            $pending[] = $kind;
            $this->store()->put(self::PENDING, $pending, self::STATE_TTL_SECONDS);
        }
    }

    private function unmarkPending(string $kind): void
    {
        $pending = $this->store()->get(self::PENDING, []);
        $pending = array_values(array_filter(is_array($pending) ? $pending : [], fn ($held) => $held !== $kind));

        $this->store()->put(self::PENDING, $pending, self::STATE_TTL_SECONDS);
    }

    private function lock(string $kind): Lock
    {
        $store = $this->store()->getStore();

        if (! $store instanceof LockProvider) {
            throw new RuntimeException('The alerts cache store cannot take locks.');
        }

        return $store->lock(self::PREFIX.$kind.':lock', 10);
    }

    private function store(): Repository
    {
        return Cache::store((string) config('alerts.cache_store', 'database'));
    }

    private function logFailure(string $kind, Throwable $e): void
    {
        try {
            Log::error('Founder alert not sent', [
                'alert_kind' => $kind,
                'error' => $e::class,
                'error_message' => $e->getMessage(),
            ]);
        } catch (Throwable) {
            // Logging is the last resort; nothing is left to tell.
        }
    }
}
