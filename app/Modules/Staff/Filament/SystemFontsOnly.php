<?php

namespace App\Modules\Staff\Filament;

use Filament\Support\Assets\AssetManager;
use Filament\Support\Assets\Font;

/**
 * Filament's asset manager without its bundled font files: Filament registers
 * the Inter web font for every page, and the Ledger look uses system fonts only.
 */
final class SystemFontsOnly extends AssetManager
{
    /**
     * @param  array<string> | null  $packages
     * @return array<Font>
     */
    public function getFonts(?array $packages = null): array
    {
        return [];
    }
}
