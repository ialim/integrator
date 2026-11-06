<?php

namespace App\Modules\Staff\Actions;

use App\Modules\Shared\Audit\Actor;
use App\Modules\Staff\Events\StaffAccountCreated;
use App\Modules\Staff\Locks\LockStaffMember;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use App\Modules\Staff\StatusTables\StaffAccountStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

/**
 * An Admin adds a Finance, Operations or Admin account. It starts Invited,
 * with no password: the invitee follows the emailed link (72 hours) to set a
 * password and an authenticator app. The acting Admin is locked and checked,
 * the change audited with the Admin as actor, and `StaffAccountCreated`
 * written to the outbox, all in one transaction (AD-4).
 */
final class CreateStaffAccount extends StaffAccountAction
{
    /** The unique index on email among Invited and Active accounts (owner's decision, 6 November 2026). */
    public const EMAIL_INDEX = 'staff_members_email_current_unique';

    /**
     * @throws InvalidStaffDetails when the name or email is not acceptable or the email is taken
     * @throws AuthorizationException when $admin is not an active Admin
     */
    public function handle(StaffMember $admin, string $name, string $email, StaffRole|string $role): StaffMember
    {
        $name = trim($name);
        $email = Str::lower(trim($email));
        $role = is_string($role) ? (StaffRole::tryFrom($role) ?? throw new InvalidStaffDetails('role', 'Choose Finance, Operations or Admin.')) : $role;

        if ($name === '' || mb_strlen($name) > 255) {
            throw new InvalidStaffDetails('name', 'Enter the person\'s full name, up to 255 characters.');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 255) {
            throw new InvalidStaffDetails('email', 'Enter an email address like name@example.com.');
        }

        try {
            return $this->transaction(function () use ($admin, $name, $email, $role): StaffMember {
                $actor = app(LockStaffMember::class)->handle($admin->id)[$admin->id];
                $this->assertActiveAdmin($actor);

                // Unique only among Invited and Active accounts: a deactivated person who comes back gets a new account.
                if (StaffMember::query()->where('email', $email)->whereIn('status', [StaffAccountStatus::Invited->value, StaffAccountStatus::Active->value])->exists()) {
                    throw InvalidStaffDetails::emailTaken();
                }

                $member = new StaffMember;
                $member->forceFill([
                    'name' => $name,
                    'email' => $email,
                    'role' => $role,
                    'is_founder' => false,
                    'status' => StaffAccountStatus::initial(),
                ]);
                // A fresh id now, so the invitation and the event can name the member.
                $member->setAttribute($member->getKeyName(), $member->newUniqueId());
                [$token, $expiresAt] = $this->issueInvitation($member, $actor);

                $this->saveAndAudit(Actor::staff($actor->id), 'staff.account_created', $member);
                $this->emit(new StaffAccountCreated($member->id, $token, $expiresAt, $actor->id));

                return $member;
            });
        } catch (UniqueConstraintViolationException $e) {
            // Two Admins adding the same address at once: the second is told beside the field.
            // By the constraint's name only, never by the words around it.
            if ($e->index === self::EMAIL_INDEX) {
                throw InvalidStaffDetails::emailTaken();
            }

            throw $e;
        }
    }
}
