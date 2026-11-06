<?php

namespace App\Modules\Staff\Actions;

use App\Modules\Staff\Models\StaffMember;

/**
 * What {@see SetSupportDuties} did: the member as read under the lock, and
 * whether the switch moved. When it did not, it already had the asked value
 * (say another Admin switched it first) and nothing was written.
 */
final class SupportDutiesSet
{
    public function __construct(
        public readonly StaffMember $member,
        public readonly bool $changed,
    ) {}
}
