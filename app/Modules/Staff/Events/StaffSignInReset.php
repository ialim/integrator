<?php

namespace App\Modules\Staff\Events;

/** An Admin reset a staff member's sign-in; a new invitation link goes out. */
final class StaffSignInReset extends StaffInvitationIssued
{
    public static function type(): string
    {
        return 'staff.sign_in_reset';
    }
}
