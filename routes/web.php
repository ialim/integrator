<?php

use Illuminate\Support\Facades\Route;

// The single SPA shell. Every path that is not the server's own renders it; the React app routes
// client-side. The server's own paths match the service worker's NEVER_ANSWERED list in
// vite.config.ts. Some vendor routes (the local disk's /storage) register after this one, so
// this catch-all must leave them alone itself.
Route::view('/{path?}', 'app')
    ->where('path', '^(?!(api|console|filament|livewire(-[0-9a-f]+)?|storage|sanctum)(/|$)|up$).*$')
    ->name('app.shell');
