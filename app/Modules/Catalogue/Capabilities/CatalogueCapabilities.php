<?php

namespace App\Modules\Catalogue\Capabilities;

use App\Modules\Shared\Capabilities\CapabilityDeclarations;
use App\Modules\Shared\Capabilities\CapabilityGroup;

/**
 * The Catalogue module's capabilities, declared into the shared
 * {@see CapabilityDeclarations} from its service provider (ADR 0002). Admin
 * holds every capability without being listed.
 */
final class CatalogueCapabilities
{
    /** Creating, renaming, flagging and retiring Product Ranges: Admins only. */
    public const MANAGE_RANGES = 'catalogue.ranges.manage';

    /** Opening the Suppliers list and a Supplier's page: Finance and Operations, read-only for Finance. */
    public const VIEW_SUPPLIERS = 'catalogue.suppliers.view';

    /** Adding a Supplier and editing its details: Operations. */
    public const RECORD_SUPPLIERS = 'catalogue.suppliers.record';

    /** Approving a Supplier, against the checklist or with a reason: Admins only. */
    public const APPROVE_SUPPLIERS = 'catalogue.suppliers.approve';

    /** Choosing which onboarding checklist items approval needs: Admins only. */
    public const CONFIGURE_ONBOARDING = 'catalogue.onboarding.configure';

    public static function declareIn(CapabilityDeclarations $declarations): void
    {
        $declarations->declare(self::MANAGE_RANGES, 'Setting up Product Ranges');
        $declarations->declare(self::VIEW_SUPPLIERS, 'The Suppliers pages', CapabilityGroup::Finance, CapabilityGroup::Operations);
        $declarations->declare(self::RECORD_SUPPLIERS, 'Recording and editing Suppliers', CapabilityGroup::Operations);
        $declarations->declare(self::APPROVE_SUPPLIERS, 'Approving Suppliers');
        $declarations->declare(self::CONFIGURE_ONBOARDING, 'Changing the Supplier onboarding checklist');
    }
}
