<?php

namespace App\Modules\Staff\Actions;

use App\Modules\Shared\Actions\Action;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Staff\Locks\LockStaffMember;
use App\Modules\Staff\Models\StaffMember;
use SensitiveParameter;

/**
 * Re-hashes a staff member's password at sign-in when the hashing settings
 * have changed. Laravel's guard would otherwise save the model itself; the
 * staff user provider sends it here instead (AD-1).
 */
final class RehashPassword extends Action
{
    public function handle(StaffMember $member, #[SensitiveParameter] string $password): void
    {
        $this->transaction(function () use ($member, $password): void {
            app(LockStaffMember::class)->handle($member->id);
            $member->forceFill(['password' => $password]);

            $this->saveAndAudit(Actor::staff($member->id), 'staff.password_rehashed', $member);
        });
    }
}
