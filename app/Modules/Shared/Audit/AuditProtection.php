<?php

namespace App\Modules\Shared\Audit;

use RuntimeException;

/**
 * The triggers that make `audit_entries` and `audit_seals` insert-only, kept
 * in one SQL file so the migration and the restore script
 * (scripts/ops/restore.sh) apply the very same statements.
 */
final class AuditProtection
{
    /** The trigger on each audit table. */
    public const TRIGGERS = [
        'audit_entries' => 'audit_entries_insert_only',
        'audit_seals' => 'audit_seals_insert_only',
    ];

    public static function path(): string
    {
        return dirname(__DIR__).'/Database/audit-protection.sql';
    }

    public static function sql(): string
    {
        $sql = @file_get_contents(self::path());

        if (! is_string($sql) || $sql === '') {
            throw new RuntimeException('The audit protection SQL is missing: '.self::path());
        }

        return $sql;
    }
}
