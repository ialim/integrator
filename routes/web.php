<?php

use Illuminate\Support\Facades\Route;

// The single SPA shell. Every non-API path renders it; the React app routes client-side.
Route::view('/{path?}', 'app')
    ->where('path', '^(?!(api|console)(/|$)|up$).*$')
    ->name('app.shell');
