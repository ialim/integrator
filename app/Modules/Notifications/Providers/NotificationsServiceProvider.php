<?php

namespace App\Modules\Notifications\Providers;

use App\Modules\Notifications\Contracts\EmailProvider;
use Illuminate\Support\ServiceProvider;

final class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Testing and staging replace this with the fake; see ModuleServiceProvider.
        $this->app->singleton(EmailProvider::class, SmtpEmailProvider::class);
    }
}
