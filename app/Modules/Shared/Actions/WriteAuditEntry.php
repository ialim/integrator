<?php

namespace App\Modules\Shared\Actions;

use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Audit\AuditSeal;
use App\Modules\Shared\Audit\PersonalFields;
use App\Modules\Shared\Capabilities\NeedsNoCapability;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Logging\RequestId;
use App\Modules\Shared\Support\Id;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * The audit writer: inserts one row into `audit_entries`, inside the calling
 * Action's transaction, so the row commits or rolls back with the change it
 * describes. It is the only code that writes the table, and it only inserts.
 *
 * Rows carry identifiers and field differences, never personal values: any
 * change to a column on the subject table's personal-data list
 * ({@see PersonalFields}) is recorded as "changed", whoever built the
 * changes. `acting_as` (a role) and `acting_for` (a person's id) are written
 * as given.
 *
 * The `sequence` column is a bigserial. Before inserting, the writer takes
 * the shared writers' lock ({@see AuditSeal::WRITERS_LOCK}) until its
 * transaction ends, so the sealer never seals past an entry whose transaction
 * is still open. A trigger rejects UPDATE, DELETE and TRUNCATE on the table.
 */
#[NeedsNoCapability('Nested inside an Action, which has already checked its actor; it only appends that Action\'s audit row')]
final class WriteAuditEntry
{
    public function __construct(private readonly Clock $clock, private readonly PersonalFields $personal) {}

    /**
     * @param  array<string, mixed>  $changes  field => {from, to} or "changed"
     */
    public function handle(Actor $actor, string $action, string $subjectType, ?string $subjectId, array $changes): string
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('An audit entry is written only inside the Action\'s transaction.');
        }

        foreach (array_keys($changes) as $field) {
            if ($this->personal->isPersonal($subjectType, (string) $field)) {
                $changes[$field] = PersonalFields::CHANGED;
            }
        }

        DB::select('SELECT pg_advisory_xact_lock_shared(?)', [AuditSeal::WRITERS_LOCK]);

        $id = Id::generate();

        DB::table('audit_entries')->insert([
            'id' => $id,
            'occurred_at' => $this->clock->now(),
            'actor_type' => $actor->type,
            'actor_id' => $actor->id,
            'acting_as' => $actor->actingAs,
            'acting_for' => $actor->actingFor,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'changes' => json_encode((object) $changes, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            'request_id' => RequestId::current(),
        ]);

        return $id;
    }
}
