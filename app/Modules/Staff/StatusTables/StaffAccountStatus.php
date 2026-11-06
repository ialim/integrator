<?php

namespace App\Modules\Staff\StatusTables;

use App\Modules\Shared\StatusTables\StatusTable;

/**
 * A staff account's states and the moves between them (AD-4):
 * Invited, then Active once the invitee has set a password and an
 * authenticator app, then Deactivated, which is final. Resetting an Active
 * member's sign-in sends them back to Invited with a fresh invitation.
 */
enum StaffAccountStatus: string implements StatusTable
{
    case Invited = 'invited';
    case Active = 'active';
    case Deactivated = 'deactivated';

    public static function moves(): array
    {
        return [
            'accept_invitation' => ['from' => [self::Invited], 'to' => self::Invited],
            'activate' => ['from' => [self::Invited], 'to' => self::Active],
            'resend_invitation' => ['from' => [self::Invited], 'to' => self::Invited],
            'reset_sign_in' => ['from' => [self::Active], 'to' => self::Invited],
            'deactivate' => ['from' => [self::Invited, self::Active], 'to' => self::Deactivated],
        ];
    }

    /** A new account's state. */
    public static function initial(): self
    {
        return self::Invited;
    }

    public function label(): string
    {
        return match ($this) {
            self::Invited => 'Invited',
            self::Active => 'Active',
            self::Deactivated => 'Deactivated',
        };
    }
}
