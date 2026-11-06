<?php

namespace App\Modules\Staff\Actions;

use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\StatusTables\TransitionRefused;
use App\Modules\Staff\Events\StaffSignInReset;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * "Reset sign-in" (owner's decision, 3 November 2026), for a member who forgot
 * their password or lost their authenticator: clears both, revokes every
 * session and sends a fresh 72-hour invitation. The account goes back to
 * Invited until they set both up again. The last active Admin cannot reset
 * themselves.
 */
final class ResetSignIn extends StaffAccountAction
{
    public const MOVE = 'reset_sign_in';

    public function __construct(private readonly RevokeStaffSessions $revokeSessions) {}

    /**
     * @throws TransitionRefused when the account is not Active, or is the last active Admin
     * @throws AuthorizationException when $admin is not an active Admin
     */
    public function handle(StaffMember $admin, string $memberId): StaffMember
    {
        return $this->transaction(function () use ($admin, $memberId): StaffMember {
            $isAdmin = StaffMember::query()->whereKey($memberId)->value('role') === StaffRole::Admin->value;
            [$actor, $member, $locked] = $this->lockActorAndMember($admin, $memberId, withActiveAdmins: $isAdmin);

            $this->refuseIfLastActiveAdmin($member, $locked, self::MOVE, "{$member->name} is the only active Admin, so this sign-in cannot be reset: nobody would be left to manage staff. Add another Admin first.");
            $this->move($member, 'status', self::MOVE);

            $member->forceFill([
                'password' => null,
                'app_authentication_secret' => null,
                'app_authentication_recovery_codes' => null,
            ]);
            [$token, $expiresAt] = $this->issueInvitation($member, $actor);

            $this->saveAndAudit(Actor::staff($actor->id), 'staff.sign_in_reset', $member);
            $revoked = $this->revokeSessions->handle($member);
            $this->audit(Actor::staff($actor->id), 'staff.sessions_revoked', $member, ['sessions' => ['from' => $revoked, 'to' => 0]]);
            $this->emit(new StaffSignInReset($member->id, $token, $expiresAt, $actor->id));

            return $member;
        });
    }
}
