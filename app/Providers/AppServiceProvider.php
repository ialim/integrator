<?php

namespace App\Providers;

use App\Modules\Shared\Alerts\FounderAlerts;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // A job that has failed permanently alerts the founder, synchronously;
        // FounderAlerts never throws, so the job's own failure is unaffected.
        Event::listen(JobFailed::class, fn (JobFailed $event) => $this->app->make(FounderAlerts::class)->jobFailed($event));
    }
}
