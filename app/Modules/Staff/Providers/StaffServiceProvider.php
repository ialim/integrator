<?php

namespace App\Modules\Staff\Providers;

use App\Modules\Shared\Audit\PersonalFields;
use App\Modules\Shared\Events\OutboxListeners;
use App\Modules\Staff\Actions\ForgetStaffSession;
use App\Modules\Staff\Auth\StaffUserProvider;
use App\Modules\Staff\Console\CreateFirstAdminCommand;
use App\Modules\Staff\Events\SendStaffInvitation;
use App\Modules\Staff\Events\StaffAccountCreated;
use App\Modules\Staff\Events\StaffInvitationResent;
use App\Modules\Staff\Events\StaffSignInReset;
use App\Modules\Staff\Filament\ConsolePanelProvider;
use App\Modules\Staff\Filament\SystemFontsOnly;
use App\Modules\Staff\Http\StaffSession;
use App\Modules\Staff\Models\StaffMember;
use Filament\Support\Assets\AssetManager;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
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

        // Every invitation link issued is emailed by one idempotent outbox listener.
        $this->callAfterResolving(OutboxListeners::class, function (OutboxListeners $listeners): void {
            foreach ([StaffAccountCreated::class, StaffInvitationResent::class, StaffSignInReset::class] as $event) {
                $listeners->listen($event, SendStaffInvitation::class);
            }
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

        // Signing out (or being signed out) removes the session's staff record.
        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->guard === ConsolePanelProvider::GUARD && $this->app->bound('session.store')) {
                $this->app->make(ForgetStaffSession::class)->handle($this->app->make('session.store')->getId());
            }
        });

        if ($this->app->runningInConsole()) {
            $this->commands([CreateFirstAdminCommand::class]);
        }
    }
}
