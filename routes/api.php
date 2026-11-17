<?php

use App\Modules\Shared\Http\HealthController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

/*
 * /api/v1. Each module adds its own endpoints in app/Modules/{Module}/Http/routes.php,
 * which ModuleServiceProvider loads under the same prefix and middleware.
 */
Route::prefix('v1')->name('api.v1.')->group(function () {
    // No session: a health check is not a visit, and must answer the same for the app, the host and scripts.
    Route::get('health', HealthController::class)
        ->withoutMiddleware(EnsureFrontendRequestsAreStateful::class)
        ->name('health');
});
