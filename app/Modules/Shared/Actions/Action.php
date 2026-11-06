<?php

namespace App\Modules\Shared\Actions;

use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Audit\AuditChanges;
use App\Modules\Shared\Audit\PersonalFields;
use App\Modules\Shared\Events\OutboxEvent;
use App\Modules\Shared\Locks\LockRegistry;
use App\Modules\Shared\Queries\AuditEntry;
use App\Modules\Shared\Queries\AuditTrail;
use App\Modules\Shared\StatusTables\StatusTable;
use App\Modules\Shared\StatusTables\TransitionRefused;
use App\Modules\Shared\StatusTables\Transitions;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Base of every module Action (AD-1, the single write path; AD-4, the Action
 * contract). Inside {@see self::transaction()} an Action:
 *
 * 1. locks its aggregate roots up front through `Lock{Root}` actions, in the
 *    fixed global order; a nested Action asserts the lock with
 *    {@see self::assertLocked()} and never locks itself;
 * 2. checks each move against the declared transition table with
 *    {@see self::move()}, which refuses with {@see TransitionRefused};
 * 3. records what it changed with {@see self::saveAndAudit()} or
 *    {@see self::audit()}, naming the actor;
 * 4. writes its events to the outbox with {@see self::emit()}.
 *
 * All of it commits together or not at all. Story 1.6 makes the audit rows
 * tamper-proof.
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
        $locks = app(LockRegistry::class);
        $locks->enter();

        try {
            return DB::transaction($work);
        } finally {
            $locks->leave();
        }
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

    /**
     * Checks $move against the transition table of $subject's $attribute and
     * sets the state it ends in (unsaved). Refuses with the current state and
     * the last recorded change when the table does not allow it.
     *
     * @throws TransitionRefused
     */
    protected function move(Model $subject, string $attribute, string $move): StatusTable
    {
        $current = $subject->getAttribute($attribute);

        if (! $current instanceof StatusTable) {
            throw new LogicException($subject::class.'::$'.$attribute.' is not cast to a declared transition table.');
        }

        $target = Transitions::target($current, $move);

        if ($target === null) {
            throw TransitionRefused::notAllowed($subject->getTable(), (string) $subject->getKey(), $move, $current, $this->lastChange($subject));
        }

        $subject->setAttribute($attribute, $target);

        return $target;
    }

    /**
     * Refuses $move on $subject for a rule other than the transition table,
     * with $reason in words for the screen.
     */
    protected function refuse(Model $subject, string $attribute, string $move, string $reason): TransitionRefused
    {
        $current = $subject->getAttribute($attribute);

        if (! $current instanceof StatusTable) {
            throw new LogicException($subject::class.'::$'.$attribute.' is not cast to a declared transition table.');
        }

        return TransitionRefused::because($reason, $subject->getTable(), (string) $subject->getKey(), $move, $current, $this->lastChange($subject));
    }

    /** Writes $event to the outbox in this transaction; returns the event id. */
    protected function emit(OutboxEvent $event): string
    {
        return app(WriteOutboxEvent::class)->handle($event);
    }

    /** A nested Action's check that the calling Action holds the root's lock. */
    protected function assertLocked(string $root, string $id): void
    {
        app(LockRegistry::class)->assertHeld($root, $id);
    }

    private function lastChange(Model $subject): ?AuditEntry
    {
        $key = $subject->getKey();

        return is_string($key) ? app(AuditTrail::class)->latestFor($subject->getTable(), $key) : null;
    }
}
