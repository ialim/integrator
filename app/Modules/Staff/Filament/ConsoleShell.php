<?php

namespace App\Modules\Staff\Filament;

use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Support\Iso8601;
use App\Modules\Shared\Support\Wat;
use App\Modules\Staff\Http\StaffSession;
use App\Modules\Staff\Models\StaffMember;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

/**
 * The render hooks that turn Filament's layout into the Ledger console shell:
 * "Staff console" beside the wordmark, the WAT clock with the person's name
 * and role, the phone notice, the session warning and the console script.
 */
final class ConsoleShell
{
    public static function title(): View
    {
        return view('staff::topbar-title');
    }

    public static function identity(): View
    {
        $now = app(Clock::class)->now();
        $member = Filament::auth()->user();

        return view('staff::topbar-identity', [
            'nowIso' => Iso8601::format($now),
            'nowWat' => Wat::time($now),
            'member' => $member instanceof StaffMember ? $member : null,
        ]);
    }

    public static function phoneNotice(): View
    {
        return view('staff::phone-notice');
    }

    public static function session(): ?View
    {
        $member = Filament::auth()->user();

        // No second factor yet means no "Stay signed in"; the set-up page comes first.
        if (! $member instanceof StaffMember || blank($member->getAppAuthenticationSecret())) {
            return null;
        }

        $staffSession = app(StaffSession::class);
        $session = session()->driver();
        $endsAt = $staffSession->endsAt($session);

        if ($endsAt === null) {
            return null;
        }

        $secondsLeft = $staffSession->secondsLeft($session);

        return view('staff::session-banner', [
            'memberId' => $member->id,
            'endsAtIso' => Iso8601::format($endsAt),
            'endsAtWat' => Wat::time($endsAt),
            'nowIso' => Iso8601::format($staffSession->now()),
            'warningSeconds' => StaffSession::WARNING_SECONDS,
            'inWarning' => $staffSession->isInWarning($session),
            'minutesLeft' => max(1, (int) ceil($secondsLeft / 60)),
            'extendUrl' => route('filament.'.StaffMember::PANEL.'.session.extend'),
        ]);
    }

    public static function script(): HtmlString
    {
        return new HtmlString(Blade::render("@vite(['resources/console/session.ts', 'resources/console/table-keys.ts'])"));
    }
}
