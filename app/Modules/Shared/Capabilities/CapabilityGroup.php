<?php

namespace App\Modules\Shared\Capabilities;

/**
 * Who can hold a capability (FR-52): the three staff roles, and the Support
 * duties, a group of their own. At launch an Operations account holds the
 * Support duties only while an Admin has its switch On; a later Support role
 * can take the group over without changing any capability.
 *
 * In `Shared` (ADR 0002, point 4): its cases are the PRD's role names, so any
 * module can name the groups that hold its capabilities. Which group a staff
 * member is in is answered by Staff's capability registry alone.
 */
enum CapabilityGroup: string
{
    case Finance = 'finance';
    case Operations = 'operations';
    case SupportDuties = 'support_duties';
    case Admin = 'admin';

    /** The group in words: "Finance", "Support duties". */
    public function label(): string
    {
        return match ($this) {
            self::Finance => 'Finance',
            self::Operations => 'Operations',
            self::SupportDuties => 'Support duties',
            self::Admin => 'Admin',
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
