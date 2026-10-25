<?php

namespace App\Providers;

use App\Modules\Shared\Providers\ProviderFakes;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Wires every module under app/Modules: its own service provider (if any),
 * its Database/Migrations, and its Http/routes.php under /api/v1.
 */
final class ModuleServiceProvider extends ServiceProvider
{
    public const MODULES = [
        'Shared', 'Identity', 'Catalogue', 'Pricing', 'Projects', 'Orders', 'CreditNotes',
        'Fulfilment', 'Documentation', 'Notifications', 'Returns', 'Staff', 'Reporting',
    ];

    public function register(): void
    {
        foreach (self::MODULES as $module) {
            $provider = 'App\\Modules\\'.$module.'\\Providers\\'.$module.'ServiceProvider';

            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }

        // Last, so in testing and staging every declared fake (the Clock included)
        // wins over any real adapter a module provider bound above.
        ProviderFakes::bind($this->app);
    }

    public function boot(): void
    {
        foreach (self::MODULES as $module) {
            $path = $this->app->path("Modules/{$module}");

            $this->loadMigrationsFrom("{$path}/Database/Migrations");

            if (is_file("{$path}/Http/routes.php") && ! $this->app->routesAreCached()) {
                Route::middleware('api')->prefix('api/v1')->group("{$path}/Http/routes.php");
            }
        }
    }
}
