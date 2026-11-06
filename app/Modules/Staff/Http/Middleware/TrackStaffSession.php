<?php

namespace App\Modules\Staff\Http\Middleware;

use App\Modules\Staff\Http\StaffSessionRecords;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The backstop for session records: sign-in and "Stay signed in" record the
 * new session id themselves, and this records any signed-in member's session
 * id that is still unrecorded, so deactivating them can delete it. (The
 * session handler itself records only the default guard's user.) A member
 * who may no longer sign in is signed out ({@see SignOutInactiveStaff}).
 */
final class TrackStaffSession
{
    public const KEY = StaffSessionRecords::KEY;

    public function __construct(private readonly StaffSessionRecords $records) {}

    public function handle(Request $request, Closure $next): Response
    {
        $memberId = Filament::auth()->id();

        // Recording takes the member's lock: a deactivation or reset that landed meanwhile signs them out here.
        if (is_string($memberId) && ! $this->records->record($request->session(), $memberId)) {
            return SignOutInactiveStaff::signOut($request, $memberId, 'session_revoked');
        }

        return $next($request);
    }
}
