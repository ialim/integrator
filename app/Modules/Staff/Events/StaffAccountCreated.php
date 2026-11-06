<?php

namespace App\Modules\Staff\Events;

/** An Admin created a staff account; the invitation goes out. */
final class StaffAccountCreated extends StaffInvitationIssued
{
    public static function type(): string
    {
        return 'staff.account_created';
    }
}
