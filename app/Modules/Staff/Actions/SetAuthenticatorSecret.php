<?php

namespace App\Modules\Staff\Actions;

use App\Modules\Shared\Actions\Action;
use App\Modules\Shared\Alerts\FounderAlerts;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use Illuminate\Support\Facades\DB;
use SensitiveParameter;
use Throwable;

/**
 * Sets or clears a staff member's authenticator-app secret. Filament's
 * second-factor setup reaches this through
 * {@see StaffMember::saveAppAuthenticationSecret()}; the actor is the member.
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
            $member->forceFill(['app_authentication_secret' => $secret]);
            $action = $secret === null ? 'staff.authenticator_removed' : 'staff.authenticator_set';

            if (! $this->saveAndAudit($actor ?? Actor::staff($member->id), $action, $member)) {
                return;
            }

            if ($member->role === StaffRole::Admin) {
                DB::afterCommit(fn () => $this->alertFounder($member, $secret !== null));
            }
        });
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
