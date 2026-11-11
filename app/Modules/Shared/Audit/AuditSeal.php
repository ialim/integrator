<?php

namespace App\Modules\Shared\Audit;

use JsonException;

/**
 * The hash chain over the audit trail (AD-21). Each seal's hash is SHA-256
 * over the previous seal's hash and a canonical encoding of the entry's
 * stored columns; the first seal links to {@see self::GENESIS}. The chain
 * links seals, not sequence numbers, so gaps left by rolled-back
 * transactions are normal.
 *
 * The sealer and the chain check both read entries with {@see self::COLUMNS},
 * so the encoding comes from the stored values alone, whatever the session's
 * time zone.
 *
 * Commit-order safety uses two transaction-level advisory locks (transaction
 * level, so they work through a transaction pooler):
 * - {@see self::WRITERS_LOCK}: the audit writer takes it shared before each
 *   insert; the sealer takes it exclusively for a moment to read the highest
 *   committed sequence, so no entry with a lower sequence is still open.
 * - {@see self::SEALER_LOCK}: one sealing transaction at a time.
 */
final class AuditSeal
{
    public const GENESIS = '0000000000000000000000000000000000000000000000000000000000000000';

    /** Fixed advisory lock keys, used for nothing else in this app. */
    public const WRITERS_LOCK = 6_176_917_062_741_000_001;

    public const SEALER_LOCK = 6_176_917_062_741_000_002;

    /** The stored columns of an entry, as the canonical encoding reads them. */
    public const COLUMNS = <<<'SQL'
        e.id::text AS id,
        e.sequence::text AS sequence,
        to_char(e.occurred_at AT TIME ZONE 'UTC', 'YYYY-MM-DD"T"HH24:MI:SS.US"Z"') AS occurred_at,
        e.actor_type,
        e.actor_id::text AS actor_id,
        e.acting_as,
        e.acting_for::text AS acting_for,
        e.action,
        e.subject_type,
        e.subject_id::text AS subject_id,
        e.changes::text AS changes,
        e.request_id
        SQL;

    private const FIELDS = ['id', 'sequence', 'occurred_at', 'actor_type', 'actor_id', 'acting_as', 'acting_for', 'action', 'subject_type', 'subject_id', 'changes', 'request_id'];

    /**
     * The seal hash of $entry (a row read with {@see self::COLUMNS}) after $previousHash.
     *
     * @throws JsonException
     */
    public static function hash(string $previousHash, object $entry): string
    {
        $values = [];

        foreach (self::FIELDS as $field) {
            $value = $entry->{$field} ?? null;
            $values[] = $value === null ? null : (string) $value;
        }

        return hash('sha256', $previousHash."\n".json_encode($values, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
