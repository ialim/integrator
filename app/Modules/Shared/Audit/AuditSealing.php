<?php

namespace App\Modules\Shared\Audit;

use App\Modules\Shared\Actions\SealAuditEntries;
use App\Modules\Shared\Alerts\AlertTime;
use App\Modules\Shared\Alerts\FounderAlerts;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Queries\AuditChain;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * One scheduled sealing run (`php artisan audit:seal`), with its alerts:
 *
 * - a run that throws raises a founder alert naming the failure; the next
 *   scheduled run retries;
 * - audit rows unsealed past the grace period (30 minutes) raise a founder
 *   alert, whatever the run did;
 * - each run first checks both insert-only triggers exist and are enabled
 *   for every session, and alerts the founder naming any table that is not
 *   protected;
 * - a run that completes POSTs the chain head (last sealed sequence and hash)
 *   to the external heartbeat check (`AUDIT_SEAL_HEARTBEAT_URL`; unset means
 *   no ping) and logs it as `audit.sealed`, so a later shorter or different
 *   head can be noticed outside the database; the monitor alerts the founder
 *   when the pings stop, say because the scheduler stopped;
 * - a run that throws also GETs the check's `/fail` URL; a busy run (writers
 *   stayed open) logs `audit.seal_busy` and does not ping.
 *
 * Never throws: the scheduler always gets an outcome.
 */
final class AuditSealing
{
    public const SCHEDULE = 'every 5 minutes';

    public function __construct(
        private readonly SealAuditEntries $seal,
        private readonly AuditChain $chain,
        private readonly FounderAlerts $alerts,
        private readonly Clock $clock,
    ) {}

    public function run(): SealingOutcome
    {
        $this->checkProtection();

        $result = null;
        $failure = null;

        try {
            $result = $this->seal->handle();
        } catch (Throwable $e) {
            $failure = $e;
            $this->sealFailed($e);
            $this->ping(fail: true);
        }

        if ($result !== null && $result->busy) {
            $this->log('warning', 'audit.seal_busy', ['last_sealed_sequence' => $result->lastSealedSequence]);
        } elseif ($result !== null) {
            $this->completed($result);
        }

        $overdue = $this->checkOverdue();

        return new SealingOutcome($result, $failure === null ? null : $failure::class, $overdue);
    }

    private function sealFailed(Throwable $e): void
    {
        $this->log('error', 'audit.seal_failed', ['error' => $e::class]);

        $this->alerts->raise(FounderAlerts::KIND_AUDIT_SEAL_FAILED, 'Audit seal failed', [
            'What failed' => 'Sealing the audit trail into its hash chain (php artisan audit:seal)',
            'Error' => $e::class,
            'Next try' => 'The next scheduled run, '.self::SCHEDULE.'; nothing is lost meanwhile, the rows wait unsealed',
        ]);
    }

    /** Raises the overdue alert when the oldest unsealed row is older than the grace period; returns whether it did. */
    private function checkOverdue(): bool
    {
        try {
            $unsealed = $this->chain->unsealed();
            $graceMinutes = (int) config('alerts.audit_seal_grace_minutes', 30);

            if ($unsealed->oldestOccurredAt === null || $unsealed->oldestOccurredAt->greaterThanOrEqualTo($this->clock->now()->subMinutes($graceMinutes))) {
                return false;
            }

            $this->alerts->raise(FounderAlerts::KIND_AUDIT_SEAL_OVERDUE, 'Audit seal overdue', [
                'What' => "Audit rows have waited more than {$graceMinutes} minutes to be sealed into the hash chain",
                'Unsealed rows' => (string) $unsealed->count,
                'Oldest unsealed' => "sequence {$unsealed->oldestSequence}, written ".AlertTime::format($unsealed->oldestOccurredAt),
            ]);

            return true;
        } catch (Throwable $e) {
            $this->alerts->raise(FounderAlerts::KIND_AUDIT_SEAL_FAILED, 'Audit seal failed', [
                'What failed' => 'Checking for audit rows left unsealed (php artisan audit:seal)',
                'Error' => $e::class,
            ]);

            return false;
        }
    }

    /** Alerts the founder naming each audit table whose insert-only trigger is missing or disabled. */
    private function checkProtection(): void
    {
        try {
            $unprotected = $this->chain->unprotectedTables();
        } catch (Throwable $e) {
            $this->alerts->raise(FounderAlerts::KIND_AUDIT_SEAL_FAILED, 'Audit seal failed', [
                'What failed' => 'Checking the audit trail insert-only triggers (php artisan audit:seal)',
                'Error' => $e::class,
            ]);

            return;
        }

        if ($unprotected === []) {
            return;
        }

        $this->log('error', 'audit.protection_missing', ['tables' => $unprotected]);
        $this->alerts->raise(FounderAlerts::KIND_AUDIT_PROTECTION_MISSING, 'Audit trail unprotected', [
            'What' => 'The insert-only trigger is missing or disabled, so rows could be changed or deleted',
            'Tables' => implode(', ', $unprotected),
            'What to do' => 'Run app/Modules/Shared/Database/audit-protection.sql against the database, find out who disabled it, then check the chain on the Audit trail page and compare its head with the heartbeat log',
        ]);
    }

    /** A completed run: the chain head goes to the log and, as the ping's body, to the heartbeat check. */
    private function completed(SealResult $result): void
    {
        try {
            $head = $this->chain->head();
        } catch (Throwable) {
            $head = null;
        }

        $body = ['sealed' => $result->sealed, 'last_sealed_sequence' => $head['sequence'] ?? null, 'head_hash' => $head['hash'] ?? null];

        $this->log('info', 'audit.sealed', $body);
        $this->ping(body: $body);
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function ping(bool $fail = false, ?array $body = null): void
    {
        $url = config('alerts.audit_seal_heartbeat_url');

        if (! is_string($url) || $url === '') {
            return;
        }

        try {
            $request = Http::timeout(10)->retry(2, 1000, throw: false);
            $response = $fail ? $request->get(rtrim($url, '/').'/fail') : $request->post($url, $body ?? []);
            $response->throw();
        } catch (Throwable $e) {
            // A missed ping is what the monitor watches for; log it and carry on.
            $this->log('warning', 'audit.seal_heartbeat_failed', ['error' => $e::class]);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function log(string $level, string $message, array $context): void
    {
        try {
            Log::log($level, $message, $context);
        } catch (Throwable) {
            // Logging is the last resort; nothing is left to tell.
        }
    }
}
