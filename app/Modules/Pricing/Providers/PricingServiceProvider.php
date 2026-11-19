<?php

namespace App\Modules\Pricing\Providers;

use App\Modules\Pricing\Capabilities\PricingCapabilities;
use App\Modules\Pricing\Filament\PricingAndRules;
use App\Modules\Shared\Capabilities\CapabilityDeclarations;
use App\Modules\Shared\Filament\ConsoleRail;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

final class PricingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->callAfterResolving(CapabilityDeclarations::class, function (CapabilityDeclarations $declarations): void {
            PricingCapabilities::declareIn($declarations);
        });

        // The Pricing and rules page joins the staff console only; Filament appends it to Staff's own pages.
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === ConsoleRail::PANEL) {
                $panel->pages([PricingAndRules::class]);
            }
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Filament/views', 'pricing');
    }
}
