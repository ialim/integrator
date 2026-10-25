<?php

namespace App\Modules\Shared\Providers;

use Illuminate\Contracts\Foundation\Application;
use ReflectionClass;

/**
 * Finds every interface in `app/Modules/{Module}/Contracts/` and its declared
 * fake, and binds the fake as a singleton in the environments that must never
 * reach a real provider (AD-15).
 */
final class ProviderFakes
{
    /** Environments that bind fakes instead of real providers. */
    public const ENVIRONMENTS = ['testing', 'staging'];

    /**
     * @return array<class-string, class-string> interface => fake
     */
    public static function declared(string $modulesPath): array
    {
        $fakes = [];

        foreach (glob($modulesPath.'/*/Contracts/*.php') ?: [] as $file) {
            $module = basename(dirname($file, 2));
            $interface = 'App\\Modules\\'.$module.'\\Contracts\\'.basename($file, '.php');

            if (! interface_exists($interface)) {
                continue;
            }

            foreach ((new ReflectionClass($interface))->getAttributes(HasFake::class) as $attribute) {
                $fakes[$interface] = $attribute->newInstance()->fake;
            }
        }

        ksort($fakes);

        return $fakes;
    }

    public static function bind(Application $app): void
    {
        if (! $app->environment(self::ENVIRONMENTS)) {
            return;
        }

        foreach (self::declared($app->path('Modules')) as $interface => $fake) {
            $app->singleton($interface, $fake);
        }
    }
}
