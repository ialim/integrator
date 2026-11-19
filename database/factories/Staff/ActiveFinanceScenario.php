<?php

namespace Database\Factories\Staff;

use App\Modules\Staff\Actions\AcceptInvitation;
use App\Modules\Staff\Actions\InvitationLinks;
use App\Modules\Staff\Actions\SetAuthenticatorSecret;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use Database\Factories\ActionFactory;

/**
 * An Active Finance account, so a check can sign in as Finance and see the
 * Pricing and rules page read-only. The founder's Admin invites it; the
 * invitee sets a known password and authenticator secret, all through the
 * Staff Actions. Creates the founder's Admin first when there is none. Test
 * values only.
 */
final class ActiveFinanceScenario
{
    public const NAME = 'Dayo Finance';

    public const EMAIL = 'dayo@example.test';

    /** Test value only. */
    public const PASSWORD = 'finance-test-passphrase';

    /** Test value only: a known authenticator secret, so a check can work out the codes. */
    public const SECRET = 'MZXW6YTBOI4TEMBQGEYDAMBQGAYDAMBQ';

    /** Test value only: the invitation token the scenario accepts. */
    public const TOKEN = 'a11yFinanceToken000000000000000000000000000000000000000000000000';

    public function __invoke(): void
    {
        ActionFactory::assertAvailable();

        $admin = StaffMember::query()->where('is_founder', true)->first() ?? FirstAdminFactory::new()->create();

        app()->instance(InvitationLinks::class, new class extends InvitationLinks
        {
            public function newToken(): string
            {
                return ActiveFinanceScenario::TOKEN;
            }
        });

        try {
            StaffAccountFactory::new()->create(['admin' => $admin, 'name' => self::NAME, 'email' => self::EMAIL, 'role' => StaffRole::Finance]);
        } finally {
            app()->forgetInstance(InvitationLinks::class);
        }

        $member = app(AcceptInvitation::class)->handle(self::TOKEN, self::PASSWORD);
        app(SetAuthenticatorSecret::class)->handle($member, self::SECRET);
    }
}
