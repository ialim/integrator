<?php

use App\Providers\ModuleServiceProvider;

const MODULE_DIRECTORIES = [
    'Actions', 'Queries', 'Locks', 'Models', 'Events', 'Http', 'Filament', 'Contracts',
    'StatusTables', 'Database/Migrations', 'Database/Views', 'Tests',
];

it('holds exactly the 13 modules', function () {
    $modules = array_map('basename', glob(app_path('Modules/*'), GLOB_ONLYDIR) ?: []);
    sort($modules);

    $expected = ModuleServiceProvider::MODULES;
    sort($expected);

    expect($modules)->toBe($expected)->toHaveCount(13);
});

it('gives each module every directory of the layout convention', function () {
    $missing = [];

    foreach (ModuleServiceProvider::MODULES as $module) {
        foreach (MODULE_DIRECTORIES as $directory) {
            if (! is_dir(app_path("Modules/{$module}/{$directory}"))) {
                $missing[] = "app/Modules/{$module}/{$directory}";
            }
        }
    }

    expect($missing)->toBeEmpty("Missing module directories:\n  ".implode("\n  ", $missing));
});
