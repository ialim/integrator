<?php

namespace App\Modules\Staff\Actions;

use App\Modules\Shared\Actions\Action;
use App\Modules\Shared\Capabilities\NeedsNoCapability;
use Illuminate\Support\Facades\DB;

/**
 * Removes a session's staff record when the member signs out (or is signed
 * out), so `staff_sessions` holds only sessions that may still be signed in.
 * Not audited: it records a session, not a change to the account.
 */
#[NeedsNoCapability('Signing out removes the record of the member\'s own session')]
final class ForgetStaffSession extends Action
{
    public function handle(string $sessionId): void
    {
        $this->transaction(fn () => DB::table('staff_sessions')->where('session_id', $sessionId)->delete());
    }
}
