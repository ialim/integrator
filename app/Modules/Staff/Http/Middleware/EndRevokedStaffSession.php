<?php

namespace App\Modules\Staff\Http\Middleware;

use App\Modules\Staff\Http\RevokedStaffSessions;
use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends a request on a session an Admin has just revoked (by deactivating,
 * resetting or re-inviting its member) the way {@see SignOutInactiveStaff}
 * does: the message is flashed, a Livewire or JSON request gets the 401 the
 * console script turns into "save the draft, reload", a page load goes to
 * sign-in. Without this, the emptied session would reach the forgery check
 * and the sign-in page with no reason given. It runs before
 * PreventRequestForgery on console routes, and as persistent middleware on
 * Livewire updates; it throws, because Livewire ignores a response that
 * persistent middleware returns.
 */
final class EndRevokedStaffSession
{
    public function __construct(private readonly RevokedStaffSessions $revoked) {}

    public function handle(Request $request, Closure $next): Response
    {
        $memberId = $request->hasSession() ? $this->revoked->pull($request->session()->getId()) : null;

        if ($memberId !== null) {
            throw new HttpResponseException(SignOutInactiveStaff::signOut($request, $memberId, 'session_revoked'));
        }

        return $next($request);
    }
}
