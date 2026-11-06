<?php

namespace App\Modules\Staff\Http\Middleware;

use App\Modules\Staff\Models\StaffMember;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * The console's authentication middleware: Filament's, except that a signed-in
 * member who may no longer sign in (deactivated, their sign-in reset or their
 * invitation resent, or their set-up time run out) is signed out on their
 * next request and sent to the sign-in page, which says why, instead of being
 * shown a refusal. A Livewire or JSON request gets a 401 that the console
 * script turns into the same. Deactivation also deletes the member's stored
 * sessions; this covers any session not recorded.
 */
final class SignOutInactiveStaff extends Authenticate
{
    public const MESSAGE = 'You were signed out because your staff account is no longer active, your sign-in was reset, or the time to finish setting it up ran out. Ask an Admin if you think this is wrong.';

    /**
     * @param  array<string>  $guards
     */
    protected function authenticate($request, array $guards): void
    {
        $member = Filament::auth()->user();

        if ($member instanceof StaffMember && ! $member->canSignIn()) {
            throw new HttpResponseException(self::signOut($request, $member->id, 'account_'.$member->status->value));
        }

        parent::authenticate($request, $guards);
    }

    /**
     * Signs the member out of this session and answers with the sign-in page
     * (or, for Livewire and JSON, the 401 the console script handles).
     */
    public static function signOut(Request $request, string $memberId, string $reason): Response
    {
        Filament::auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->flash('staff_session.ended', self::MESSAGE);
        Log::info('staff.session_ended', ['staff_id' => $memberId, 'reason' => $reason]);

        return $request->hasHeader('X-Livewire') || $request->expectsJson()
            ? new JsonResponse(['message' => self::MESSAGE, 'login_url' => Filament::getLoginUrl()], 401, [EndStaffSessionAfter12Hours::ENDED_HEADER => 'ended'])
            : redirect()->guest(Filament::getLoginUrl());
    }
}
