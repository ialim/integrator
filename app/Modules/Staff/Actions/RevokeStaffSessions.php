<?php

namespace App\Modules\Staff\Actions;

use App\Modules\Shared\Actions\Action;
use App\Modules\Staff\Locks\LockStaffMember;
use App\Modules\Staff\Models\StaffMember;
use Illuminate\Support\Facades\DB;

/**
 * Deletes every stored session of a staff member at once, so each browser
 * they were signed in on is signed out on its next request. A nested Action:
 * the calling Action holds the member's lock and does the auditing.
 *
 * The console also refuses a member who may no longer sign in on every
 * request, so a session store without rows to delete is covered too.
 */
final class RevokeStaffSessions extends Action
{
    /** Returns how many sessions were revoked. */
    public function handle(StaffMember $member): int
    {
        return $this->transaction(function () use ($member): int {
            $this->assertLocked(LockStaffMember::ROOT, $member->id);

            $sessionIds = DB::table('staff_sessions')->where('staff_member_id', $member->id)->pluck('session_id')->all();

            if ($sessionIds !== []) {
                DB::table('sessions')->whereIn('id', $sessionIds)->delete();
            }

            DB::table('staff_sessions')->where('staff_member_id', $member->id)->delete();

            return count($sessionIds);
        });
    }
}
