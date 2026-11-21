<?php

namespace App\Modules\Catalogue\Providers;

use App\Modules\Catalogue\Capabilities\CatalogueCapabilities;
use App\Modules\Catalogue\Filament\SupplierPage;
use App\Modules\Catalogue\Filament\SuppliersPage;
use App\Modules\Catalogue\Models\Supplier;
use App\Modules\Shared\Audit\PersonalFields;
use App\Modules\Shared\Capabilities\CapabilityDeclarations;
use App\Modules\Shared\Filament\ConsoleRail;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

final class CatalogueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->callAfterResolving(CapabilityDeclarations::class, function (CapabilityDeclarations $declarations): void {
            CatalogueCapabilities::declareIn($declarations);
        });

        // A Supplier's contact names, phones and emails are audited only as "changed" (AD-21).
        $this->callAfterResolving(PersonalFields::class, function (PersonalFields $fields): void {
            $fields->declare((new Supplier)->getTable(), Supplier::PERSONAL_FIELDS);
        });

        // The Suppliers pages join the staff console only; Filament appends them to Staff's own pages.
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === ConsoleRail::PANEL) {
                $panel->pages([SuppliersPage::class, SupplierPage::class]);
            }
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Filament/views', 'catalogue');
    }
}
