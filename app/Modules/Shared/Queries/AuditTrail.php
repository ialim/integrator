<?php

namespace App\Modules\Shared\Queries;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Reads the audit trail, oldest first. Later stories build each record's
 * read-only History panel on this.
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
