<?php

namespace App\Modules\Catalogue\Providers;

use App\Modules\Catalogue\Capabilities\CatalogueCapabilities;
use App\Modules\Shared\Capabilities\CapabilityDeclarations;
use Illuminate\Support\ServiceProvider;

final class CatalogueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->callAfterResolving(CapabilityDeclarations::class, function (CapabilityDeclarations $declarations): void {
            CatalogueCapabilities::declareIn($declarations);
        });
    }
}
