<?php

namespace App\Modules\Staff\Events;

/** An Admin sent a staff member a new invitation link; the old one no longer works. */
final class StaffInvitationResent extends StaffInvitationIssued
{
    public static function type(): string
    {
        return 'staff.invitation_resent';
    }
}
