<?php

namespace App\Modules\Staff\Filament;

use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Audit\PersonalFields;
use App\Modules\Shared\Queries\AuditEntry;
use App\Modules\Shared\Queries\ChainCheck;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use Illuminate\Support\Str;

/**
 * Audit rows in words, for the Audit trail page and each record's History
 * panel: who did what and when, names shown for staff actors, and values
 * only where the row holds them (a personal field reads "changed").
 */
final class AuditWords
{
    /** Subject tables in words. */
    private const RECORDS = [
        'staff_members' => 'Staff account',
    ];

    /**
     * Names of the staff members the entries mention, by id.
     *
     * @param  list<AuditEntry>  $entries
     * @return array<string, string>
     */
    public function staffNames(array $entries): array
    {
        $ids = [];

        foreach ($entries as $entry) {
            if ($entry->actorType === Actor::STAFF && $entry->actorId !== null) {
                $ids[] = $entry->actorId;
            }

            if ($entry->actingFor !== null) {
                $ids[] = $entry->actingFor;
            }

            if ($entry->subjectType === (new StaffMember)->getTable() && $entry->subjectId !== null) {
                $ids[] = $entry->subjectId;
            }
        }

        if ($ids === []) {
            return [];
        }

        /** @var array<string, string> */
        return StaffMember::query()->whereIn('id', array_values(array_unique($ids)))->pluck('name', 'id')->all();
    }

    /**
     * "Ada Founder", "Ada Founder, acting as Finance", "The system".
     *
     * @param  array<string, string>  $names
     */
    public function who(AuditEntry $entry, array $names): string
    {
        $who = match ($entry->actorType) {
            Actor::SYSTEM => 'The system',
            Actor::STAFF => $names[(string) $entry->actorId] ?? 'Staff member '.$entry->actorId,
            default => 'User '.$entry->actorId,
        };

        if ($entry->actingAs !== null) {
            $who .= ', acting as '.(StaffRole::tryFrom($entry->actingAs)?->label() ?? $entry->actingAs);
        }

        if ($entry->actingFor !== null) {
            $who .= ', for '.($names[$entry->actingFor] ?? $entry->actingFor);
        }

        return $who;
    }

    /** "staff.account_created" reads "Account created". */
    public function what(AuditEntry $entry): string
    {
        return Str::ucfirst(str_replace('_', ' ', Str::afterLast($entry->action, '.')));
    }

    /**
     * "Staff account: Bola Ade", or the table and identifier.
     *
     * @param  array<string, string>  $names
     */
    public function record(AuditEntry $entry, array $names): string
    {
        $kind = self::RECORDS[$entry->subjectType] ?? $entry->subjectType;

        if ($entry->subjectId === null) {
            return $kind;
        }

        return $kind.': '.($names[$entry->subjectId] ?? $entry->subjectId);
    }

    /**
     * Each change in words: "Status: from invited to active", "Email: changed".
     *
     * @return list<string>
     */
    public function changes(AuditEntry $entry): array
    {
        $lines = [];

        foreach ($entry->changes as $field => $change) {
            $label = Str::ucfirst(str_replace('_', ' ', (string) $field));

            if (is_array($change) && array_key_exists('from', $change) && array_key_exists('to', $change)) {
                $lines[] = "{$label}: from {$this->value($change['from'])} to {$this->value($change['to'])}";
            } elseif ($change === PersonalFields::CHANGED) {
                $lines[] = "{$label}: changed";
            } else {
                $lines[] = "{$label}: {$this->value($change)}";
            }
        }

        return $lines;
    }

    /** The chain check's result in words. */
    public function chain(ChainCheck $check): string
    {
        $waiting = match ($check->unsealed) {
            0 => '',
            1 => ' 1 newer row is waiting to be sealed.',
            default => " {$check->unsealed} newer rows are waiting to be sealed.",
        };

        if (! $check->intact) {
            $why = match ($check->problem) {
                ChainCheck::MISSING => 'the sealed row is missing',
                ChainCheck::EXTRA => 'a row with no seal sits inside the sealed chain',
                default => 'the row\'s stored values no longer match its seal',
            };

            return "The chain is broken at sequence {$check->brokenAt}: {$why}. Rows from there on cannot be trusted. Tell the founder.";
        }

        if ($check->lastSealedSequence === null) {
            return 'The chain is intact: nothing is sealed yet.'.$waiting;
        }

        return "The chain is intact up to sequence {$check->lastSealedSequence}.".$waiting;
    }

    private function value(mixed $value): string
    {
        return match (true) {
            $value === null => 'none',
            $value === true => 'yes',
            $value === false => 'no',
            is_scalar($value) => (string) $value,
            default => (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        };
    }
}
