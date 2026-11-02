<?php

namespace App\Modules\Shared\Audit;

use Illuminate\Database\Eloquent\Model;

/**
 * The field differences a save is about to make to a model, as the audit trail
 * stores them: `{field: {from, to}}`, or `{field: "changed"}` for a personal
 * or secret column. Timestamps are left out; they are the audit row's own time.
 * Read before `save()`: a save that would change nothing gives no changes.
 */
final class AuditChanges
{
    private const IGNORED = ['created_at', 'updated_at'];

    /**
     * @return array<string, array{from: mixed, to: mixed}|string>
     */
    public static function pending(Model $model, PersonalFields $personal): array
    {
        $table = $model->getTable();
        $original = $model->exists ? $model->getRawOriginal() : [];
        $changes = [];

        foreach ($model->getDirty() as $field => $to) {
            if ($field === $model->getKeyName() || in_array($field, self::IGNORED, true)) {
                continue;
            }

            $changes[$field] = $personal->isPersonal($table, $field)
                ? PersonalFields::CHANGED
                : ['from' => $original[$field] ?? null, 'to' => $to];
        }

        ksort($changes);

        return $changes;
    }
}
