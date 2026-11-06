<?php

namespace App\Modules\Staff\Actions;

use App\Modules\Shared\Actions\Action;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\StatusTables\TransitionRefused;
use App\Modules\Staff\Locks\LockStaffMember;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use App\Modules\Staff\StatusTables\StaffAccountStatus;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * What the Admin's staff-account Actions share: locking the acting Admin and
 * the member together (with every active Admin when the last-Admin rule could
 * apply, so two Admins can never each remove the other), the server-side
 * capability check against the registry, the last-Admin rule and fresh
 * invitation links. Each concrete Action declares its capability with
 * #[NeedsCapability].
 */
abstract class StaffAccountAction extends Action
{
    /**
     * Locks the acting Admin and the member, and when $withActiveAdmins every
     * active Admin too, in one ordered call. Returns the acting Admin and the
     * member as read under the lock, and every locked row.
     *
     * @return array{0: StaffMember, 1: StaffMember, 2: array<string, StaffMember>}
     *
     * @throws AuthorizationException when the actor does not hold the Action's capability
     * @throws ModelNotFoundException when the member does not exist
     */
    protected function lockActorAndMember(StaffMember $admin, string $memberId, bool $withActiveAdmins = false): array
    {
        $ids = [$admin->id, $memberId];

        if ($withActiveAdmins) {
            array_push($ids, ...$this->activeAdminIds());
        }

        $locked = app(LockStaffMember::class)->handle(...$ids);
        $actor = $locked[$admin->id] ?? throw new AuthorizationException('Only an active Admin can manage staff accounts.');
        // The capability the concrete Action declares, on the actor as read under the lock.
        $this->authorizeCapability($actor);

        $member = $locked[$memberId] ?? throw (new ModelNotFoundException)->setModel(StaffMember::class, [$memberId]);

        return [$actor, $member, $locked];
    }

    /**
     * The last active Admin can never be removed: refuses when $member is an
     * active Admin and no other locked Admin is still active.
     *
     * @param  array<string, StaffMember>  $locked
     *
     * @throws TransitionRefused
     */
    protected function refuseIfLastActiveAdmin(StaffMember $member, array $locked, string $move, string $reason): void
    {
        if (! $member->isActiveAdmin()) {
            return;
        }

        foreach ($locked as $id => $other) {
            if ($id !== $member->id && $other->isActiveAdmin()) {
                return;
            }
        }

        throw $this->refuse($member, 'status', $move, $reason);
    }

    /**
     * Gives $member a fresh invitation link, valid for 72 hours from now;
     * any earlier link stops working. Returns the token, for the outbox event.
     *
     * @return array{0: string, 1: CarbonImmutable}
     */
    protected function issueInvitation(StaffMember $member, StaffMember $issuedBy): array
    {
        $token = app(InvitationLinks::class)->newToken();
        $expiresAt = app(Clock::class)->now()->addHours(InvitationLinks::VALID_HOURS);

        $member->forceFill([
            'invitation_token_hash' => InvitationLinks::hash($token),
            'invitation_expires_at' => $expiresAt,
            'invitation_accepted_at' => null,
            'invited_by' => $issuedBy->id,
        ]);

        return [$token, $expiresAt];
    }

    /**
     * @return list<string>
     */
    private function activeAdminIds(): array
    {
        return StaffMember::query()
            ->where('role', StaffRole::Admin->value)
            ->where('status', StaffAccountStatus::Active->value)
            ->pluck('id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all();
    }
}
