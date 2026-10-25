<?php

use App\Modules\Shared\Http\HealthController;
use Illuminate\Support\Facades\Route;

/*
 * /api/v1. Each module adds its own endpoints in app/Modules/{Module}/Http/routes.php,
 * which ModuleServiceProvider loads under the same prefix and middleware.
 */
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('health', HealthController::class)->name('health');
});
