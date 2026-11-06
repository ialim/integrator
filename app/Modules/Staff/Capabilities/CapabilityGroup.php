<?php

namespace App\Modules\Staff\Capabilities;

use App\Modules\Staff\Models\StaffRole;

/**
 * Who can hold a capability (FR-52): the three staff roles, and the Support
 * duties, a group of their own. At launch an Operations account holds the
 * Support duties only while an Admin has its switch On; a later Support role
 * can take the group over without changing any capability.
 */
enum CapabilityGroup: string
{
    case Finance = 'finance';
    case Operations = 'operations';
    case SupportDuties = 'support_duties';
    case Admin = 'admin';

    /** The group a role holds by itself, before any switch. */
    public static function ofRole(StaffRole $role): self
    {
        return match ($role) {
            StaffRole::Finance => self::Finance,
            StaffRole::Operations => self::Operations,
            StaffRole::Admin => self::Admin,
        };
    }

    /** The word the Staff page's Duties column uses for the group. */
    public function duty(): string
    {
        return match ($this) {
            self::Finance => 'Money',
            self::Operations => 'Goods',
            self::SupportDuties => 'Support',
            self::Admin => 'Everything',
        };
    }

    /** Who holds the group, in words, for "... is for Finance staff and Admins". */
    public function holders(): string
    {
        return match ($this) {
            self::Finance => 'Finance staff',
            self::Operations => 'Operations staff',
            self::SupportDuties => 'Operations staff with Support duties',
            self::Admin => 'Admins',
        };
    }
}
