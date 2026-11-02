<?php

namespace Tests\Support;

use App\Modules\Shared\Contracts\Clock;
use App\Modules\Staff\Actions\SetAuthenticatorSecret;
use App\Modules\Staff\Http\StaffSession;
use App\Modules\Staff\Models\StaffMember;
use Database\Factories\Staff\FirstAdminFactory;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase;
use PragmaRX\Google2FA\Google2FA;

/**
 * Staff console test helpers. Staff state is made through Staff Actions only.
 */
final class StaffConsole
{
    /** A known authenticator secret, test value only. */
    public const SECRET = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';

    public static function useConsole(): void
    {
        Filament::setCurrentPanel(Filament::getPanel(StaffMember::PANEL));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function admin(bool $withAuthenticator = true, array $overrides = []): StaffMember
    {
        /** @var StaffMember $admin */
        $admin = FirstAdminFactory::new()->create($overrides);

        if ($withAuthenticator) {
            app(SetAuthenticatorSecret::class)->handle($admin, self::SECRET);
        }

        return $admin->refresh();
    }

    /**
     * Stands in for Story 1.4's deactivation Action, which does not exist yet:
     * a direct row update, so tests can prove a deactivated member is refused.
     */
    public static function deactivate(StaffMember $member): void
    {
        StaffMember::query()->whereKey($member->id)->update(['is_active' => false]);
    }

    public static function provider(): AppAuthentication
    {
        self::useConsole();

        $provider = Filament::getMultiFactorAuthenticationProviders()['app'];
        assert($provider instanceof AppAuthentication);

        return $provider;
    }

    /** The code the authenticator app shows now. */
    public static function currentCode(): string
    {
        return app(Google2FA::class)->getCurrentOtp(self::SECRET);
    }

    /** A 6-digit code that is not the current one, nor in the accepted window. */
    public static function wrongCode(): string
    {
        $google2fa = app(Google2FA::class);

        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while ($google2fa->verifyKey(self::SECRET, $code, 8));

        return $code;
    }

    /**
     * Both steps passed, as the sign-in would leave the session.
     */
    public static function signIn(TestCase $test, StaffMember $member): TestCase
    {
        return $test->actingAs($member, 'staff')
            ->withSession([StaffSession::KEY => app(Clock::class)->now()->getTimestamp()]);
    }
}
