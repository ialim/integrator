<?php

namespace Tests\Support;

use App\Modules\Shared\Contracts\Clock;
use App\Modules\Staff\Actions\AcceptInvitation;
use App\Modules\Staff\Actions\CreateStaffAccount;
use App\Modules\Staff\Actions\DeactivateStaffAccount;
use App\Modules\Staff\Actions\SetAuthenticatorSecret;
use App\Modules\Staff\Http\StaffSession;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use Database\Factories\Staff\FirstAdminFactory;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
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

    /** A test value only: the password every invitee in these tests sets. */
    public const INVITEE_PASSWORD = 'invitee-test-passphrase';

    /**
     * A staff account an Admin invited, through {@see CreateStaffAccount}.
     */
    public static function invite(StaffMember $admin, StaffRole $role = StaffRole::Operations, string $name = 'Bola Ade', ?string $email = null): StaffMember
    {
        $email ??= strtolower(str_replace(' ', '.', $name)).'@example.test';

        return app(CreateStaffAccount::class)->handle($admin, $name, $email, $role);
    }

    /**
     * The latest invitation token issued to $member, read from the outbox
     * event that carries it (the staff table keeps only its hash).
     */
    public static function invitationToken(StaffMember $member): string
    {
        $payload = DB::table('outbox_events')
            ->where('aggregate_id', $member->id)
            ->whereIn('type', ['staff.account_created', 'staff.invitation_resent', 'staff.sign_in_reset'])
            ->orderByDesc('sequence')
            ->value('payload');
        $token = json_decode((string) $payload, true)['token'] ?? null;

        assert(is_string($token), "No invitation was issued to {$member->id}.");

        return Crypt::decryptString($token);
    }

    /**
     * An Active staff member: invited by $admin, invitation accepted and the
     * authenticator set up, all through the Staff Actions.
     */
    public static function active(StaffMember $admin, StaffRole $role = StaffRole::Operations, string $name = 'Bola Ade', ?string $email = null): StaffMember
    {
        $member = self::invite($admin, $role, $name, $email);
        $member = app(AcceptInvitation::class)->handle(self::invitationToken($member), self::INVITEE_PASSWORD);
        app(SetAuthenticatorSecret::class)->handle($member, self::SECRET);

        return $member->refresh();
    }

    /**
     * Deactivates $member through {@see DeactivateStaffAccount}, by another
     * active Admin (one is added when $member is the only one).
     */
    public static function deactivate(StaffMember $member): void
    {
        $by = StaffMember::query()
            ->where('role', StaffRole::Admin->value)
            ->where('status', 'active')
            ->whereKeyNot($member->id)
            ->first();

        if (! $by instanceof StaffMember) {
            $founder = StaffMember::query()->where('is_founder', true)->firstOrFail();
            $by = self::active($founder, StaffRole::Admin, 'Second Admin', 'second.admin@example.test');
        }

        app(DeactivateStaffAccount::class)->handle($by, $member->id);
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
