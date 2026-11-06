<?php

namespace App\Modules\Staff\Actions;

use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\StatusTables\TransitionRefused;
use App\Modules\Staff\Events\StaffInvitationResent;
use App\Modules\Staff\Models\StaffMember;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * An Admin sends an Invited member a new invitation link, valid for 72 hours;
 * the old link stops working at once (owner's decision, 3 November 2026).
 *
 * An invitee part-way through the set-up (password set, authenticator not
 * yet) starts again: their password and any second factor are cleared and
 * their sessions revoked, so the new link is the only way in.
 */
final class ResendInvitation extends StaffAccountAction
{
    public const MOVE = 'resend_invitation';

    public function __construct(private readonly RevokeStaffSessions $revokeSessions) {}

    /**
     * @throws TransitionRefused when the account is no longer Invited
     * @throws AuthorizationException when $admin is not an active Admin
     */
    public function handle(StaffMember $admin, string $memberId): StaffMember
    {
        return $this->transaction(function () use ($admin, $memberId): StaffMember {
            [$actor, $member] = $this->lockActorAndMember($admin, $memberId);

            $this->move($member, 'status', self::MOVE);
            $wasPartWay = $member->invitation_accepted_at !== null || filled($member->password) || filled($member->app_authentication_secret);
            [$token, $expiresAt] = $this->issueInvitation($member, $actor);

            if ($wasPartWay) {
                $member->forceFill([
                    'password' => null,
                    'app_authentication_secret' => null,
                    'app_authentication_recovery_codes' => null,
                ]);
            }

            $this->saveAndAudit(Actor::staff($actor->id), 'staff.invitation_resent', $member);

            if ($wasPartWay) {
                $revoked = $this->revokeSessions->handle($member);
                $this->audit(Actor::staff($actor->id), 'staff.sessions_revoked', $member, ['sessions' => ['from' => $revoked, 'to' => 0]]);
            }

            $this->emit(new StaffInvitationResent($member->id, $token, $expiresAt, $actor->id));

            return $member;
        });
    }
}
