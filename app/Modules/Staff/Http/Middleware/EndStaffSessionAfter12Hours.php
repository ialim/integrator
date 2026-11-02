<?php

namespace App\Modules\Staff\Http\Middleware;

use App\Modules\Staff\Http\StaffSession;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs a staff member out 12 hours after sign-in. A session with no recorded
 * sign-in time is treated as ended. The console's own script saves unsaved
 * form values as a draft before this happens and offers them back after the
 * next sign-in; a Livewire request is answered with a 401 the script handles.
 */
final class EndStaffSessionAfter12Hours
{
    public const ENDED_HEADER = 'X-Staff-Session';

    public function __construct(private readonly StaffSession $staffSession) {}

    public function handle(Request $request, Closure $next): Response
    {
        $guard = Filament::auth();

        if (! $guard->check() || ! $this->staffSession->hasEnded($request->session())) {
            return $next($request);
        }

        $memberId = $guard->id();
        $guard->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = 'You were signed out because 12 hours had passed since you signed in. Sign in again to carry on.';
        $request->session()->flash('staff_session.ended', $message);
        Log::info('staff.session_ended', ['staff_id' => $memberId, 'reason' => '12_hours']);

        if ($request->hasHeader('X-Livewire') || $request->expectsJson()) {
            return new JsonResponse(['message' => $message, 'login_url' => Filament::getLoginUrl()], 401, [self::ENDED_HEADER => 'ended']);
        }

        return redirect()->guest(Filament::getLoginUrl());
    }
}
