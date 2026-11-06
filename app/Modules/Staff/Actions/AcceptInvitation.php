<?php

namespace App\Modules\Staff\Actions;

use App\Modules\Shared\Actions\Action;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\StatusTables\Transitions;
use App\Modules\Staff\Locks\LockStaffMember;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\StatusTables\StaffAccountStatus;
use SensitiveParameter;

/**
 * The invitee follows their link and sets a password (the one staff password
 * rule). The link is used up. The account stays Invited until they also set
 * up an authenticator app, which makes it Active ({@see SetAuthenticatorSecret});
 * no console page but the set-up opens before that, and only until the
 * invitation's 72 hours run out. After that the Admin sends a new one.
 */
final class AcceptInvitation extends Action
{
    public const MOVE = 'accept_invitation';

    public function __construct(private readonly Clock $clock) {}

    /**
     * The member a link belongs to while it still works.
     *
     * @throws InvitationUnusable when the link has expired, been used or been replaced
     */
    public function memberFor(#[SensitiveParameter] string $token): StaffMember
    {
        $member = StaffMember::query()->where('invitation_token_hash', InvitationLinks::hash($token))->first();

        if (! $member instanceof StaffMember || Transitions::target($member->status, self::MOVE) === null) {
            throw InvitationUnusable::used();
        }

        if (! $this->isUnexpired($member)) {
            throw InvitationUnusable::expired();
        }

        return $member;
    }

    private function isUnexpired(StaffMember $member): bool
    {
        return $member->invitation_expires_at !== null && $member->invitation_expires_at->greaterThan($this->clock->now());
    }

    /**
     * @throws InvitationUnusable when the link has expired, been used or been replaced
     * @throws InvalidStaffDetails when the password breaks the staff password rule
     */
    public function handle(#[SensitiveParameter] string $token, #[SensitiveParameter] string $password): StaffMember
    {
        $found = $this->memberFor($token);

        return $this->transaction(function () use ($found, $token, $password): StaffMember {
            $member = app(LockStaffMember::class)->handle($found->id)[$found->id];

            // Read again under the lock: an Admin may have resent, reset or deactivated meanwhile.
            if ($member->invitation_token_hash === null || ! hash_equals($member->invitation_token_hash, InvitationLinks::hash($token))) {
                throw InvitationUnusable::used();
            }

            if (! $this->isUnexpired($member)) {
                throw InvitationUnusable::expired();
            }

            $problem = StaffPassword::problem($password, $member->name, $member->email);

            if ($problem !== null) {
                throw new InvalidStaffDetails('password', $problem);
            }

            $this->move($member, 'status', self::MOVE);
            // The link is used up, but its expiry stays: it bounds the whole set-up, authenticator included.
            // Any second factor left from before is cleared, so the set-up starts afresh.
            $member->forceFill([
                'password' => $password,
                'invitation_token_hash' => null,
                'invitation_accepted_at' => $this->clock->now(),
                'app_authentication_secret' => null,
                'app_authentication_recovery_codes' => null,
            ]);

            $this->saveAndAudit(Actor::staff($member->id), 'staff.invitation_accepted', $member);

            return $member;
        });
    }

    public static function isAwaitingAuthenticator(StaffMember $member): bool
    {
        return $member->status === StaffAccountStatus::Invited && $member->invitation_accepted_at !== null;
    }
}
