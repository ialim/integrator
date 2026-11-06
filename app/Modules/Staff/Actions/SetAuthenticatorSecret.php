<?php

namespace App\Modules\Staff\Actions;

use App\Modules\Shared\Actions\Action;
use App\Modules\Shared\Alerts\FounderAlerts;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\StatusTables\TransitionRefused;
use App\Modules\Staff\Events\StaffAccountActivated;
use App\Modules\Staff\Locks\LockStaffMember;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use App\Modules\Staff\StatusTables\StaffAccountStatus;
use Illuminate\Support\Facades\DB;
use SensitiveParameter;
use Throwable;

/**
 * Sets or clears a staff member's authenticator-app secret. Filament's
 * second-factor setup reaches this through
 * {@see StaffMember::saveAppAuthenticationSecret()}; the actor is the member.
 *
 * An invitee who has set their password becomes Active once they set up the
 * authenticator: the `activate` move, audited, with `StaffAccountActivated`
 * in the outbox.
 *
 * Setting or removing an Admin's authenticator also alerts the founder, so an
 * enrolment by someone who learnt the password is noticed.
 */
final class SetAuthenticatorSecret extends Action
{
    public function __construct(private readonly FounderAlerts $alerts) {}

    public function handle(StaffMember $member, #[SensitiveParameter] ?string $secret, ?Actor $actor = null): void
    {
        $this->transaction(function () use ($member, $secret, $actor): void {
            app(LockStaffMember::class)->handle($member->id);
            $member->refresh();

            $actor ??= Actor::staff($member->id);

            if ($secret !== null) {
                $this->refuseUnlessMaySetUp($member);
            }

            $member->forceFill(['app_authentication_secret' => $secret]);
            $action = $secret === null ? 'staff.authenticator_removed' : 'staff.authenticator_set';

            if (! $this->saveAndAudit($actor, $action, $member)) {
                return;
            }

            if ($secret !== null && AcceptInvitation::isAwaitingAuthenticator($member)) {
                $this->move($member, 'status', 'activate');
                $member->forceFill(['invitation_accepted_at' => null, 'invitation_expires_at' => null]);
                $this->saveAndAudit($actor, 'staff.account_activated', $member);
                $this->emit(new StaffAccountActivated($member->id, $member->id));
            }

            if ($member->role === StaffRole::Admin) {
                DB::afterCommit(fn () => $this->alertFounder($member, $secret !== null));
            }
        });
    }

    /**
     * Read under the lock: only an Active member, or an invitee who has set
     * their password within the invitation's 72 hours, sets up an
     * authenticator. An Admin's resend, reset or deactivation that landed
     * first wins, so no Invited account is left holding a second factor.
     *
     * @throws TransitionRefused
     */
    private function refuseUnlessMaySetUp(StaffMember $member): void
    {
        if ($member->status === StaffAccountStatus::Active) {
            return;
        }

        if (! AcceptInvitation::isAwaitingAuthenticator($member) || ! $member->canSignIn()) {
            throw $this->refuse($member, 'status', 'activate', 'This sign-in can no longer be set up: an Admin has sent a new invitation, reset it or deactivated the account, or the invitation\'s '.InvitationLinks::VALID_HOURS.' hours ran out. Ask an Admin to send a new invitation.');
        }
    }

    private function alertFounder(StaffMember $member, bool $set): void
    {
        try {
            $this->alerts->raise(
                'staff_admin_authenticator',
                $set ? 'An Admin set up an authenticator app' : 'An Admin\'s authenticator app was removed',
                [
                    'Whose' => "{$member->name} (staff id {$member->id})",
                    'What' => $set
                        ? 'A new authenticator app can now sign in to this Admin account. If this was not them, deactivate the account.'
                        : 'This Admin must set up an authenticator app again before any console page opens.',
                ],
            );
        } catch (Throwable) {
            // FounderAlerts logs its own failures; the change itself stands.
        }
    }
}
