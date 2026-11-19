<?php

namespace App\Modules\Pricing\Capabilities;

use App\Modules\Shared\Capabilities\CapabilityDeclarations;
use App\Modules\Shared\Capabilities\CapabilityGroup;

/**
 * The Pricing module's capabilities, declared into the shared
 * {@see CapabilityDeclarations} from its service provider (ADR 0002). Admin
 * holds every capability without being listed.
 */
final class PricingCapabilities
{
    /** Changing a Product Range's Platform Markup and the margin floor: Admins only. */
    public const MANAGE_RULES = 'pricing.rules.manage';

    /** Opening the Pricing and rules page, read-only: Finance and Operations see what Admin set. */
    public const VIEW_RULES = 'pricing.rules.view';

    public static function declareIn(CapabilityDeclarations $declarations): void
    {
        $declarations->declare(self::MANAGE_RULES, 'Changing markups and the margin floor');
        $declarations->declare(self::VIEW_RULES, 'The Pricing and rules page', CapabilityGroup::Finance, CapabilityGroup::Operations);
    }
}
