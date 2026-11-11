<?php

namespace App\Modules\Shared\Queries;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Reads the audit trail: oldest first for a subject, newest first for the
 * History panels, the Audit trail page and its Look up. Read-only; the trail
 * cannot be edited.
 */
final class AuditTrail
{
    /**
     * @return list<AuditEntry>
     */
    public function forSubject(string $subjectType, string $subjectId): array
    {
        return $this->map(DB::table('audit_entries')
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->orderBy('sequence')
            ->get()
            ->all());
    }

    /** The most recent entry for a subject: who last changed it, and when. */
    public function latestFor(string $subjectType, string $subjectId): ?AuditEntry
    {
        return $this->map(DB::table('audit_entries')
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->orderByDesc('sequence')
            ->limit(1)
            ->get()
            ->all())[0] ?? null;
    }

    /**
     * A subject's entries, newest first: its History panel. Callers ask
     * for one more than they show, to know whether older ones were left out.
     *
     * @return list<AuditEntry>
     */
    public function historyOf(string $subjectType, string $subjectId, int $limit = 200): array
    {
        return $this->map(DB::table('audit_entries')
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->orderByDesc('sequence')
            ->limit($limit)
            ->get()
            ->all());
    }

    /**
     * The latest entries across the trail, newest first.
     *
     * @return list<AuditEntry>
     */
    public function recent(int $limit = 50): array
    {
        return $this->map(DB::table('audit_entries')->orderByDesc('sequence')->limit($limit)->get()->all());
    }

    /**
     * "Look up": every entry naming $id as its subject, its actor or the
     * person it was done for, newest first.
     *
     * @return list<AuditEntry>
     */
    public function lookUp(string $id, int $limit = 200): array
    {
        return $this->map(DB::table('audit_entries')
            ->where(fn ($query) => $query->where('subject_id', $id)->orWhere('actor_id', $id)->orWhere('acting_for', $id))
            ->orderByDesc('sequence')
            ->limit($limit)
            ->get()
            ->all());
    }

    /**
     * @return list<AuditEntry>
     */
    public function all(): array
    {
        return $this->map(DB::table('audit_entries')->orderBy('sequence')->get()->all());
    }

    /**
     * @param  array<int, object>  $rows
     * @return list<AuditEntry>
     */
    private function map(array $rows): array
    {
        return array_values(array_map(function (object $row): AuditEntry {
            /** @var object{id: string, sequence: int|string, occurred_at: string, actor_type: string, actor_id: ?string, acting_as: ?string, acting_for: ?string, request_id: ?string, action: string, subject_type: string, subject_id: ?string, changes: string} $row */
            $changes = json_decode($row->changes, true, flags: JSON_THROW_ON_ERROR);

            return new AuditEntry(
                $row->id,
                (int) $row->sequence,
                CarbonImmutable::parse($row->occurred_at)->utc(),
                $row->actor_type,
                $row->actor_id,
                $row->acting_as,
                $row->acting_for,
                $row->action,
                $row->subject_type,
                $row->subject_id,
                is_array($changes) ? $changes : [],
                $row->request_id,
            );
        }, $rows));
    }
}
