<?php

namespace App\Modules\Staff\Actions;

use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\StatusTables\TransitionRefused;
use App\Modules\Staff\Events\StaffAccountDeactivated;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;

/**
 * An Admin deactivates a staff account (Invited or Active to Deactivated,
 * which is final). Every session of that person is revoked at once. The last
 * active Admin can never be deactivated. When two Admins deactivate the same
 * account together, the second waits for the first's lock, then is refused
 * with the current state and who changed it (AD-4).
 */
final class DeactivateStaffAccount extends StaffAccountAction
{
    public const MOVE = 'deactivate';

    public function __construct(
        private readonly RevokeStaffSessions $revokeSessions,
        private readonly Clock $clock,
    ) {}

    /**
     * @throws TransitionRefused when the account is already deactivated, or is the last active Admin
     * @throws AuthorizationException when $admin is not an active Admin
     */
    public function handle(StaffMember $admin, string $memberId): StaffMember
    {
        $member = $this->transaction(function () use ($admin, $memberId): StaffMember {
            $isAdmin = StaffMember::query()->whereKey($memberId)->value('role') === StaffRole::Admin->value;
            [$actor, $member, $locked] = $this->lockActorAndMember($admin, $memberId, withActiveAdmins: $isAdmin);

            $this->refuseIfLastActiveAdmin($member, $locked, self::MOVE, "{$member->name} is the only active Admin, so the account cannot be deactivated: nobody would be left to manage staff. Add another Admin first.");
            $this->move($member, 'status', self::MOVE);

            $member->forceFill([
                'deactivated_at' => $this->clock->now(),
                'invitation_token_hash' => null,
                'invitation_expires_at' => null,
            ]);

            $this->saveAndAudit(Actor::staff($actor->id), 'staff.account_deactivated', $member);
            $revoked = $this->revokeSessions->handle($member);
            $this->audit(Actor::staff($actor->id), 'staff.sessions_revoked', $member, ['sessions' => ['from' => $revoked, 'to' => 0]]);
            $this->emit(new StaffAccountDeactivated($member->id, $actor->id));

            return $member;
        });

        Log::info('staff.account_deactivated', ['staff_id' => $member->id, 'by' => $admin->id]);

        return $member;
    }
}
