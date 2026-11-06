<?php

namespace App\Modules\Shared\Providers;

use App\Modules\Shared\Audit\PersonalFields;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Events\OutboxListeners;
use App\Modules\Shared\Locks\LockRegistry;
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

        // The locks the running Action holds, and each outbox event type's listeners (AD-4).
        $this->app->singleton(LockRegistry::class);
        $this->app->singleton(OutboxListeners::class);
    }
}
