<?php

use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Providers\ProviderFakes;
use App\Modules\Shared\Support\FrozenClock;
use App\Modules\Shared\Support\SystemClock;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Bootstrap\RegisterFacades;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Support\Facades\Facade;

/**
 * Boots a fresh application as if APP_ENV were $environment, then restores the test application.
 */
function bootAs(string $environment): Application
{
    $testApp = app();

    /** @var Application $app */
    $app = require base_path('bootstrap/app.php');
    $app->afterBootstrapping(LoadConfiguration::class, function (Application $app) use ($environment) {
        $app['config']->set('app.env', $environment);
        $app->detectEnvironment(fn () => $environment);
    });

    try {
        $app->bootstrapWith([
            LoadEnvironmentVariables::class,
            LoadConfiguration::class,
            RegisterFacades::class,
            RegisterProviders::class,
            BootProviders::class,
        ]);
    } finally {
        Container::setInstance($testApp);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($testApp);
    }

    return $app;
}

it('binds a settable Clock and every declared provider fake when APP_ENV is staging', function () {
    $app = bootAs('staging');

    expect($app->environment())->toBe('staging')
        ->and($app->make(Clock::class))->toBeInstanceOf(FrozenClock::class);

    $declared = ProviderFakes::declared($app->path('Modules'));

    expect($declared)->not->toBeEmpty();

    foreach ($declared as $interface => $fake) {
        expect($app->make($interface))->toBeInstanceOf($fake);
    }

    $clock = $app->make(Clock::class);
    $clock->set('2026-10-25T09:00:00Z');
    $clock->advance('2 hours');
    expect($clock->now()->toIso8601ZuluString())->toBe('2026-10-25T11:00:00Z');
    $clock->reset();
});

it('binds the real system Clock in production', function () {
    expect(bootAs('production')->make(Clock::class))->toBeInstanceOf(SystemClock::class);
});
