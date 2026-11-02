<?php

namespace App\Modules\Shared\Actions;

use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Audit\PersonalFields;
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
 * Rows carry identifiers and field differences, never personal values
 * ({@see PersonalFields}). The `sequence` column is a
 * bigserial; Story 1.6 adds the hash chain, the sealing job and the trigger
 * that rejects UPDATE and DELETE.
 */
final class WriteAuditEntry
{
    public function __construct(private readonly Clock $clock) {}

    /**
     * @param  array<string, mixed>  $changes  field => {from, to} or "changed"
     */
    public function handle(Actor $actor, string $action, string $subjectType, ?string $subjectId, array $changes): string
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('An audit entry is written only inside the Action\'s transaction.');
        }

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
