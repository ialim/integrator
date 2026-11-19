<?php

namespace App\Modules\Catalogue\Capabilities;

use App\Modules\Shared\Capabilities\CapabilityDeclarations;

/**
 * The Catalogue module's capabilities, declared into the shared
 * {@see CapabilityDeclarations} from its service provider (ADR 0002). Admin
 * holds every capability without being listed.
 */
final class CatalogueCapabilities
{
    /** Creating, renaming, flagging and retiring Product Ranges: Admins only. */
    public const MANAGE_RANGES = 'catalogue.ranges.manage';

    public static function declareIn(CapabilityDeclarations $declarations): void
    {
        $declarations->declare(self::MANAGE_RANGES, 'Setting up Product Ranges');
    }
}
