<?php

namespace App\Modules\Staff\Actions;

use App\Modules\Shared\Actions\Action;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Support\Id;
use App\Modules\Staff\Locks\LockStaffMember;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Records which staff member a session id belongs to, so the session can be
 * deleted when they are deactivated or their sign-in is reset. Runs at sign-in
 * (after the session id changes), at "Stay signed in", and as a backstop on
 * any console request whose session is not yet recorded; the previous id's
 * record goes with it.
 *
 * Takes the member's lock, the same lock deactivation and reset take before
 * revoking sessions, and records nothing for a member who may no longer sign
 * in, so a session can never be recorded just after its revocation.
 *
 * Not audited: it records a session, not a change to the account.
 */
final class RecordStaffSession extends Action
{
    public function __construct(private readonly Clock $clock) {}

    /** Returns false, recording nothing, when the member may no longer sign in. */
    public function handle(string $staffMemberId, string $sessionId, ?string $previousSessionId = null): bool
    {
        return $this->transaction(function () use ($staffMemberId, $sessionId, $previousSessionId): bool {
            try {
                $member = app(LockStaffMember::class)->handle($staffMemberId)[$staffMemberId] ?? null;
            } catch (ModelNotFoundException) {
                $member = null;
            }

            if ($previousSessionId !== null && $previousSessionId !== $sessionId) {
                DB::table('staff_sessions')->where('session_id', $previousSessionId)->delete();
            }

            if ($member === null || ! $member->canSignIn()) {
                return false;
            }

            DB::table('staff_sessions')->upsert(
                [['id' => Id::generate(), 'staff_member_id' => $staffMemberId, 'session_id' => $sessionId, 'created_at' => $this->clock->now()]],
                ['session_id'],
                ['staff_member_id'],
            );

            return true;
        });
    }
}
