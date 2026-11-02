<?php

namespace App\Modules\Staff\Http;

use App\Modules\Shared\Support\Iso8601;
use App\Modules\Shared\Support\Wat;
use App\Modules\Staff\Models\StaffMember;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * "Stay signed in": in the last 5 minutes of the session, checks the
 * authenticator code again and, if it is right, starts a fresh 12 hours
 * without leaving the page.
 */
final class ExtendStaffSessionController
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 15 * 60;

    public function __invoke(Request $request, StaffSession $staffSession): JsonResponse
    {
        /** @var StaffMember $member */
        $member = Filament::auth()->user();
        $secret = $member->getAppAuthenticationSecret();
        $limiterKey = 'staff:extend-session:'.$member->id;

        if (blank($secret)) {
            return $this->refused('Set up an authenticator app before you can stay signed in.', 409);
        }

        if (! $staffSession->isInWarning($request->session())) {
            return $this->refused('You can stay signed in only in the last 5 minutes of your session.', 409);
        }

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_ATTEMPTS)) {
            Log::warning('staff.stay_signed_in_throttled', ['staff_id' => $member->id]);

            return $this->refused('Too many wrong codes. Wait '.RateLimiter::availableIn($limiterKey).' seconds, then try again.', 429);
        }

        $input = $request->input('code');
        $code = is_string($input) ? preg_replace('/\s+/', '', $input) : null;

        if ($code === null || preg_match('/^\d{6}$/', $code) !== 1) {
            return $this->refused('Enter the 6-digit code from your authenticator app.');
        }

        RateLimiter::hit($limiterKey, self::DECAY_SECONDS);

        $provider = Filament::getMultiFactorAuthenticationProviders()['app'] ?? null;

        if (! $provider instanceof AppAuthentication || ! $provider->verifyCode($code, $secret, shouldPreventCodeReuse: true)) {
            Log::notice('staff.stay_signed_in_failed', ['staff_id' => $member->id]);

            return $this->refused('That code is not right. Check your authenticator app and enter the code it shows now.');
        }

        RateLimiter::clear($limiterKey);
        $request->session()->regenerate();
        $endsAt = $staffSession->start($request->session());

        Log::info('staff.stayed_signed_in', ['staff_id' => $member->id, 'ends_at' => Iso8601::format($endsAt)]);

        return new JsonResponse([
            'ends_at' => Iso8601::format($endsAt),
            'ends_at_wat' => Wat::time($endsAt),
            'server_time' => Iso8601::format($staffSession->now()),
        ]);
    }

    private function refused(string $message, int $status = 422): JsonResponse
    {
        return new JsonResponse(['message' => $message, 'errors' => ['code' => [$message]]], $status);
    }
}
