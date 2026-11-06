<?php

namespace App\Modules\Staff\Actions;

use App\Modules\Shared\Actions\Action;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Staff\Locks\LockStaffMember;
use App\Modules\Staff\Models\StaffMember;
use SensitiveParameter;

/**
 * Stores a staff member's hashed recovery codes: at setup, when they are
 * regenerated, and when one is used up at sign-in. Filament reaches this
 * through {@see StaffMember::saveAppAuthenticationRecoveryCodes()}.
 */
final class SetRecoveryCodes extends Action
{
    /**
     * @param  array<string>|null  $hashedCodes
     */
    public function handle(StaffMember $member, #[SensitiveParameter] ?array $hashedCodes, ?Actor $actor = null): void
    {
        $this->transaction(function () use ($member, $hashedCodes, $actor): void {
            app(LockStaffMember::class)->handle($member->id);
            $member->refresh();

            $before = $member->getAppAuthenticationRecoveryCodes() ?? [];
            $after = $hashedCodes === null ? null : array_values($hashedCodes);

            $member->forceFill(['app_authentication_recovery_codes' => $after]);

            $this->saveAndAudit($actor ?? Actor::staff($member->id), match (true) {
                $after === null => 'staff.recovery_codes_removed',
                self::isOneUsed($before, $after) => 'staff.recovery_code_used',
                default => 'staff.recovery_codes_set',
            }, $member);
        });
    }

    /**
     * @param  array<string>  $before
     * @param  list<string>  $after
     */
    private static function isOneUsed(array $before, array $after): bool
    {
        return count($after) === count($before) - 1 && array_diff($after, $before) === [];
    }
}
