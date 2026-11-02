<?php

namespace App\Modules\Shared\Actions;

use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Audit\AuditChanges;
use App\Modules\Shared\Audit\PersonalFields;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Base of every module Action (AD-1, the single write path). An Action does
 * its writes inside {@see self::transaction()} and records what it changed
 * with {@see self::saveAndAudit()} or {@see self::audit()} inside that same
 * transaction, so a change and its audit row commit together or not at all.
 *
 * Later kernel stories add the lock registry, the transition helper and the
 * outbox to this base; Story 1.6 makes the audit rows tamper-proof.
 */
abstract class Action
{
    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $work
     * @return TResult
     */
    protected function transaction(Closure $work): mixed
    {
        return DB::transaction($work);
    }

    /**
     * Saves $model and audits its field differences. A save that would change
     * nothing writes nothing and is not audited; returns whether it saved.
     */
    protected function saveAndAudit(Actor $actor, string $action, Model $model): bool
    {
        $changes = AuditChanges::pending($model, app(PersonalFields::class));

        if ($changes === []) {
            return false;
        }

        $model->save();
        $this->audit($actor, $action, $model, $changes);

        return true;
    }

    /**
     * Records a change to $subject with the given field differences.
     *
     * @param  array<string, mixed>  $changes
     */
    protected function audit(Actor $actor, string $action, Model $subject, array $changes): void
    {
        $key = $subject->getKey();

        app(WriteAuditEntry::class)->handle($actor, $action, $subject->getTable(), is_string($key) ? $key : null, $changes);
    }
}
