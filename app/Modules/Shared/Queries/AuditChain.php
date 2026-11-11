<?php

namespace App\Modules\Shared\Queries;

use App\Modules\Shared\Audit\AuditProtection;
use App\Modules\Shared\Audit\AuditSeal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Reads the audit hash chain (AD-21). Changes nothing. Not final, so tests
 * can make a read fail and prove a sealing run still never crashes.
 */
class AuditChain
{
    /**
     * Recomputes every seal from the stored rows, in sequence order: intact
     * with the last sealed sequence, or the first sequence where the chain
     * breaks, for a changed, missing or extra row.
     *
     * It cannot by itself see a removed tail (the newest seals deleted with
     * their entries): compare {@see self::head()} with the head each sealing
     * run logs and sends to the heartbeat, or with the offsite copy.
     */
    public function check(): ChainCheck
    {
        $lastSealed = $this->lastSealedSequence();
        $unsealed = $this->unsealed()->count;

        if ($lastSealed === null) {
            return new ChainCheck(true, null, $unsealed);
        }

        $rows = DB::cursor(
            'SELECT coalesce(s.sequence, e.sequence) AS position, s.sequence AS seal_sequence, s.hash AS seal_hash,
                s.previous_hash AS seal_previous_hash, (e.id IS NOT NULL)::int AS has_entry, '.AuditSeal::COLUMNS.'
            FROM audit_seals s FULL OUTER JOIN audit_entries e ON e.sequence = s.sequence
            WHERE coalesce(s.sequence, e.sequence) <= ?
            ORDER BY coalesce(s.sequence, e.sequence), s.sequence NULLS FIRST',
            [$lastSealed],
        );

        $previousHash = AuditSeal::GENESIS;

        foreach ($rows as $row) {
            /** @var object{position: int|string, seal_sequence: int|string|null, seal_hash: string|null, seal_previous_hash: string|null, has_entry: int|string} $row */
            $position = (int) $row->position;

            $problem = match (true) {
                $row->seal_sequence === null => ChainCheck::EXTRA,
                (int) $row->has_entry === 0 => ChainCheck::MISSING,
                $row->seal_previous_hash !== $previousHash, AuditSeal::hash($previousHash, $row) !== $row->seal_hash => ChainCheck::CHANGED,
                default => null,
            };

            if ($problem !== null) {
                return new ChainCheck(false, $lastSealed, $unsealed, $position, $problem);
            }

            $previousHash = (string) $row->seal_hash;
        }

        return new ChainCheck(true, $lastSealed, $unsealed);
    }

    /** The entries after the last seal, and the oldest of them. */
    public function unsealed(): UnsealedEntries
    {
        $row = DB::selectOne(
            'SELECT count(*) AS unsealed, min(sequence) AS oldest_sequence, min(occurred_at) AS oldest_at
            FROM audit_entries WHERE sequence > (SELECT coalesce(max(sequence), 0) FROM audit_seals)',
        );

        /** @var object{unsealed: int|string, oldest_sequence: int|string|null, oldest_at: string|null} $row */
        return new UnsealedEntries(
            (int) $row->unsealed,
            $row->oldest_sequence === null ? null : (int) $row->oldest_sequence,
            $row->oldest_at === null ? null : CarbonImmutable::parse($row->oldest_at)->utc(),
        );
    }

    /** The highest sealed sequence; null when nothing is sealed yet. */
    public function lastSealedSequence(): ?int
    {
        $last = DB::table('audit_seals')->max('sequence');

        return $last === null ? null : (int) $last;
    }

    /**
     * Which of $sequences have a seal row of their own.
     *
     * @param  list<int>  $sequences
     * @return array<int, true>
     */
    public function sealedAmong(array $sequences): array
    {
        if ($sequences === []) {
            return [];
        }

        $sealed = [];

        foreach (DB::table('audit_seals')->whereIn('sequence', $sequences)->pluck('sequence') as $sequence) {
            $sealed[(int) $sequence] = true;
        }

        return $sealed;
    }

    /**
     * Where the chain ends: the last seal's sequence and hash; null when
     * nothing is sealed yet.
     *
     * @return array{sequence: int, hash: string}|null
     */
    public function head(): ?array
    {
        /** @var object{sequence: int|string, hash: string}|null $last */
        $last = DB::table('audit_seals')->orderByDesc('sequence')->first(['sequence', 'hash']);

        return $last === null ? null : ['sequence' => (int) $last->sequence, 'hash' => (string) $last->hash];
    }

    /**
     * The audit tables whose insert-only trigger is missing or not enabled
     * for every session (`tgenabled = 'A'`).
     *
     * @return list<string>
     */
    public function unprotectedTables(): array
    {
        $unprotected = [];

        foreach (AuditProtection::TRIGGERS as $table => $trigger) {
            $enabled = DB::scalar(
                "SELECT count(*) FROM pg_trigger t JOIN pg_class c ON c.oid = t.tgrelid
                WHERE c.relname = ? AND t.tgname = ? AND t.tgenabled = 'A' AND pg_table_is_visible(c.oid)",
                [$table, $trigger],
            );

            if ((int) $enabled === 0) {
                $unprotected[] = $table;
            }
        }

        return $unprotected;
    }
}
