<?php

namespace App\Modules\Shared\Providers;

use App\Modules\Shared\Audit\PersonalFields;
use App\Modules\Shared\Capabilities\CapabilityDeclarations;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Events\OutboxListeners;
use App\Modules\Shared\Locks\LockRegistry;
use App\Modules\Shared\Support\FrozenClock;
use App\Modules\Shared\Support\SystemClock;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Testing and staging replace this with the FrozenClock fake; see ModuleServiceProvider.
        $this->app->singleton(Clock::class, SystemClock::class);

        // In staging the FrozenClock keeps its instant in the shared cache, so web, queue and
        // `php artisan clock:*` agree on the time (TC-2). Tests keep it in memory.
        if ($this->app->environment('staging')) {
            $this->app->afterResolving(Clock::class, function (Clock $clock, Application $app): void {
                if ($clock instanceof FrozenClock) {
                    $clock->persistIn($app->make(CacheFactory::class)->store());
                }
            });
        }

        // Modules declare their personal and secret columns here; the audit writer reads them.
        $this->app->singleton(PersonalFields::class);

        // Each module declares its capabilities here; Staff's capability registry reads them (ADR 0002).
        $this->app->singleton(CapabilityDeclarations::class);

        // The locks the running Action holds, and each outbox event type's listeners (AD-4).
        $this->app->singleton(LockRegistry::class);
        $this->app->singleton(OutboxListeners::class);
    }

    public function boot(): void
    {
        // The console views every module's pages share: History, and the in-shell refusal.
        $this->loadViewsFrom(__DIR__.'/../Filament/views', 'shared');

        if (! $this->app->environment('staging')) {
            return;
        }

        // Read the shared instant when the process starts and before each queued job, so framework
        // time (model timestamps, cache expiry) follows a `clock:set` made in another process.
        $sync = fn () => $this->app->make(Clock::class)->now();
        $this->app->booted($sync);
        Event::listen(JobProcessing::class, $sync);
    }
}
