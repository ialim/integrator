<?php

namespace Database\Factories\Staff;

use App\Modules\Staff\Actions\InvitationLinks;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;

/**
 * An Operations account the founder's Admin has invited, whose invitation
 * link uses a known token, so the accessibility check can open the
 * invitation page. Creates the founder's Admin first when there is none.
 */
final class InvitedStaffScenario
{
    /** Test value only: the invitation link is /console/invitation/{TOKEN}. */
    public const TOKEN = 'a11yInvitationToken0000000000000000000000000000000000000000000000';

    public function __invoke(): void
    {
        $admin = StaffMember::query()->where('is_founder', true)->first() ?? FirstAdminFactory::new()->create();

        app()->instance(InvitationLinks::class, new class extends InvitationLinks
        {
            public function newToken(): string
            {
                return InvitedStaffScenario::TOKEN;
            }
        });

        try {
            StaffAccountFactory::new()->create(['admin' => $admin, 'role' => StaffRole::Operations]);
        } finally {
            app()->forgetInstance(InvitationLinks::class);
        }
    }
}
