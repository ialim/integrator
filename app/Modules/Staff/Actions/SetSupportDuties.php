<?php

namespace App\Modules\Staff\Actions;

use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Shared\StatusTables\TransitionRefused;
use App\Modules\Staff\Capabilities\StaffCapabilities;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use App\Modules\Staff\StatusTables\StaffAccountStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;

/**
 * An Admin switches the Support duties of an Operations account On or Off
 * (FR-52). The member holds the Support duties group in the capability
 * registry while it is On; the change takes effect on their next request,
 * with no release. The acting Admin and the member are locked first, the
 * Admin's capability checked, and the change audited with its from and to
 * value in the same transaction (AD-4).
 *
 * Only an Operations account that is not deactivated has the switch; any
 * other is refused in words. Asking for the value the account already has
 * changes nothing, writes no audit row and logs nothing, so two Admins
 * switching the same way at once leave one row; the result says so.
 */
#[NeedsCapability(StaffCapabilities::MANAGE_STAFF)]
final class SetSupportDuties extends StaffAccountAction
{
    public const MOVE = 'set_support_duties';

    public const AUDIT_ACTION = 'staff.support_duties_set';

    /**
     * @throws TransitionRefused when the account is not Operations, or is deactivated
     * @throws AuthorizationException when $admin does not hold the capability
     */
    public function handle(StaffMember $admin, string $memberId, bool $on): SupportDutiesSet
    {
        $result = $this->transaction(function () use ($admin, $memberId, $on): SupportDutiesSet {
            [$actor, $member] = $this->lockActorAndMember($admin, $memberId);

            if ($member->role !== StaffRole::Operations) {
                $role = $member->role->label();
                $article = in_array($role[0], ['A', 'E', 'I', 'O', 'U'], true) ? 'an' : 'a';

                throw $this->refuse($member, 'status', self::MOVE, "Support duties are only for Operations accounts. {$member->name} has {$article} {$role} account, so nothing was changed.");
            }

            if ($member->status === StaffAccountStatus::Deactivated) {
                throw $this->refuse($member, 'status', self::MOVE, "{$member->name}'s account is deactivated, so their Support duties cannot be switched. Nothing was changed.");
            }

            $member->forceFill(['support_duties' => $on]);

            return new SupportDutiesSet($member, $this->saveAndAudit(Actor::staff($actor->id), self::AUDIT_ACTION, $member));
        });

        if ($result->changed) {
            Log::info(self::AUDIT_ACTION, ['staff_id' => $result->member->id, 'on' => $on, 'by' => $admin->id]);
        }

        return $result;
    }
}
