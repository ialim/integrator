<?php

namespace App\Modules\Shared\Providers;

use App\Modules\Shared\Audit\PersonalFields;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Support\SystemClock;
use Illuminate\Support\ServiceProvider;

final class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Testing and staging replace this with the FrozenClock fake; see ModuleServiceProvider.
        $this->app->singleton(Clock::class, SystemClock::class);

        // Modules declare their personal and secret columns here; the audit writer reads them.
        $this->app->singleton(PersonalFields::class);
    }
}
