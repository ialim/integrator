<?php

namespace Database\Factories\Staff;

use App\Modules\Staff\Actions\AcceptInvitation;
use App\Modules\Staff\Actions\InvitationLinks;
use App\Modules\Staff\Actions\SetAuthenticatorSecret;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use Database\Factories\ActionFactory;

/**
 * An Active Operations account with Support duties Off, so the accessibility
 * check can sign in as Operations, see that role's rail and be refused the
 * Staff page. The founder's Admin invites it, the invitee sets a known
 * password and a known authenticator secret, all through the Staff Actions.
 * Creates the founder's Admin first when there is none. Test values only.
 */
final class ActiveOperationsScenario
{
    public const NAME = 'Chika Operations';

    public const EMAIL = 'chika@example.test';

    /** Test value only. */
    public const PASSWORD = 'operations-test-passphrase';

    /** Test value only: a known authenticator secret, so the check can work out the codes. */
    public const SECRET = 'KRUGS4ZANFZSAYJAORSXG5BAONSWG4TF';

    /** Test value only: the invitation token the scenario accepts. */
    public const TOKEN = 'a11yOperationsToken0000000000000000000000000000000000000000000000';

    public function __invoke(): void
    {
        ActionFactory::assertAvailable();

        $admin = StaffMember::query()->where('is_founder', true)->first() ?? FirstAdminFactory::new()->create();

        app()->instance(InvitationLinks::class, new class extends InvitationLinks
        {
            public function newToken(): string
            {
                return ActiveOperationsScenario::TOKEN;
            }
        });

        try {
            StaffAccountFactory::new()->create(['admin' => $admin, 'name' => self::NAME, 'email' => self::EMAIL, 'role' => StaffRole::Operations]);
        } finally {
            app()->forgetInstance(InvitationLinks::class);
        }

        $member = app(AcceptInvitation::class)->handle(self::TOKEN, self::PASSWORD);
        app(SetAuthenticatorSecret::class)->handle($member, self::SECRET);
    }
}
