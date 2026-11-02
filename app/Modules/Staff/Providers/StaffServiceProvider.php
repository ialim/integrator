<?php

namespace App\Modules\Staff\Providers;

use App\Modules\Shared\Audit\PersonalFields;
use App\Modules\Staff\Auth\StaffUserProvider;
use App\Modules\Staff\Console\CreateFirstAdminCommand;
use App\Modules\Staff\Filament\ConsolePanelProvider;
use App\Modules\Staff\Filament\SystemFontsOnly;
use App\Modules\Staff\Http\StaffSession;
use App\Modules\Staff\Models\StaffMember;
use Filament\Support\Assets\AssetManager;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

final class StaffServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(ConsolePanelProvider::class);

        // System fonts only: no bundled Inter font file on any console page.
        $this->app->singleton(AssetManager::class, SystemFontsOnly::class);

        $this->callAfterResolving(PersonalFields::class, function (PersonalFields $fields): void {
            $fields->declare((new StaffMember)->getTable(), StaffMember::PERSONAL_FIELDS);
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Filament/views', 'staff');

        Auth::provider('staff', function (Application $app, array $config): StaffUserProvider {
            /** @var array{model: class-string<StaffMember>} $config */
            return new StaffUserProvider($app->make('hash'), $config['model']);
        });

        // The 12 hours start when both steps have passed and the guard signs the member in.
        Event::listen(Login::class, function (Login $event): void {
            if ($event->guard === ConsolePanelProvider::GUARD) {
                $this->app->make(StaffSession::class)->start($this->app->make('session.store'));
                Log::info('staff.signed_in', ['staff_id' => $event->user->getAuthIdentifier()]);
            }
        });

        if ($this->app->runningInConsole()) {
            $this->commands([CreateFirstAdminCommand::class]);
        }
    }
}
