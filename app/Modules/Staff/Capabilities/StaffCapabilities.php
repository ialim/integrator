<?php

namespace App\Modules\Staff\Capabilities;

use App\Modules\Shared\Capabilities\CapabilityDeclarations;
use App\Modules\Shared\Capabilities\CapabilityGroup;

/**
 * The Staff module's own capabilities. Every module declares its own into
 * the same {@see CapabilityDeclarations} from its service provider. Admin
 * holds every capability without being listed.
 */
final class StaffCapabilities
{
    /** The Today page, the first page of the console: every role, and the Support duties group, so a later Support role has it too. */
    public const TODAY = 'console.today';

    /** The Staff page and its Actions: add, invite again, reset, deactivate, switch Support duties. */
    public const MANAGE_STAFF = 'staff.manage';

    /** The Support desk (Epics 13 and 16 build its screens): the Support duties group. */
    public const SUPPORT_DESK = 'support.desk';

    /** The Audit trail page, its chain check and "Look up": Admins only. */
    public const VIEW_AUDIT = 'audit.view';

    public static function declareIn(CapabilityDeclarations $declarations): void
    {
        $declarations->declare(self::TODAY, 'The Today page', CapabilityGroup::Finance, CapabilityGroup::Operations, CapabilityGroup::SupportDuties);
        $declarations->declare(self::MANAGE_STAFF, 'Managing staff accounts and their Support duties');
        $declarations->declare(self::SUPPORT_DESK, 'The Support desk', CapabilityGroup::SupportDuties);
        $declarations->declare(self::VIEW_AUDIT, 'Reading the audit trail and looking up an identifier in it');
    }
}
