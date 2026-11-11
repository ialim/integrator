<?php

namespace App\Modules\Shared\Actions;

use App\Modules\Shared\Audit\AuditSeal;
use App\Modules\Shared\Audit\SealResult;
use App\Modules\Shared\Capabilities\NeedsNoCapability;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Support\Id;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Sleep;

/**
 * The one writer of `audit_seals` (AD-21): seals unsealed audit entries in
 * sequence order, each seal a new insert-only row linked to the previous one
 * ({@see AuditSeal}). It never changes an audit entry, and never seals an
 * entry twice.
 *
 * 1. It tries the writers' lock exclusively, again every 50 ms for up to
 *    2 seconds, never queueing for it, and reads the highest committed sequence.
 *    Every writer holds that lock shared until its transaction ends, so at
 *    that moment no entry with a lower sequence is still open, and every
 *    later entry gets a higher one. If writers stay open past the wait, the
 *    run seals nothing and says so; the next run tries again.
 * 2. It seals up to that sequence, in batches, each under the sealer lock and
 *    re-reading the last seal, so two runs at once never fork the chain.
 */
#[NeedsNoCapability('A scheduled system job with no staff actor; it only appends seals')]
final class SealAuditEntries
{
    /** Step 1 tries the writers' lock this many times, this far apart: a 2-second budget. */
    public const ATTEMPTS = 40;

    public const RETRY_MILLISECONDS = 50;

    private const BATCH = 500;

    public function __construct(private readonly Clock $clock) {}

    public function handle(): SealResult
    {
        $highest = $this->highestCommittedSequence();

        if ($highest === null) {
            return new SealResult(0, $this->lastSealedSequence(), busy: true);
        }

        $sealed = 0;

        do {
            $batch = $this->sealBatch($highest);
            $sealed += $batch;
        } while ($batch === self::BATCH);

        return new SealResult($sealed, $this->lastSealedSequence());
    }

    /**
     * The highest sequence no open transaction can still undercut; null when
     * writers stayed open for the whole budget. The exclusive lock is only
     * ever tried, never waited for: a queued request would make every new
     * writer queue behind it, stalling Actions and risking deadlocks.
     */
    private function highestCommittedSequence(): ?int
    {
        for ($attempt = 0; $attempt < self::ATTEMPTS; $attempt++) {
            $highest = DB::transaction(function (): ?int {
                if (! (bool) DB::scalar('SELECT pg_try_advisory_xact_lock(?)', [AuditSeal::WRITERS_LOCK])) {
                    return null;
                }

                return (int) DB::scalar('SELECT coalesce(max(sequence), 0) FROM audit_entries');
            });

            if ($highest !== null) {
                return $highest;
            }

            Sleep::for(self::RETRY_MILLISECONDS)->milliseconds();
        }

        return null;
    }

    private function sealBatch(int $highest): int
    {
        return DB::transaction(function () use ($highest): int {
            DB::select('SELECT pg_advisory_xact_lock(?)', [AuditSeal::SEALER_LOCK]);

            /** @var object{sequence: int|string, hash: string}|null $last */
            $last = DB::table('audit_seals')->orderByDesc('sequence')->first(['sequence', 'hash']);
            $previousHash = $last === null ? AuditSeal::GENESIS : $last->hash;

            $entries = DB::select(
                'SELECT '.AuditSeal::COLUMNS.' FROM audit_entries e WHERE e.sequence > ? AND e.sequence <= ? ORDER BY e.sequence LIMIT '.self::BATCH,
                [$last === null ? 0 : (int) $last->sequence, $highest],
            );

            $now = $this->clock->now();

            foreach ($entries as $entry) {
                /** @var object{sequence: string} $entry */
                $hash = AuditSeal::hash($previousHash, $entry);

                DB::table('audit_seals')->insert([
                    'id' => Id::generate(),
                    'sequence' => (int) $entry->sequence,
                    'hash' => $hash,
                    'previous_hash' => $previousHash,
                    'sealed_at' => $now,
                ]);

                $previousHash = $hash;
            }

            return count($entries);
        });
    }

    private function lastSealedSequence(): ?int
    {
        $last = DB::table('audit_seals')->max('sequence');

        return $last === null ? null : (int) $last;
    }
}
