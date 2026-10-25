<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Production seeds nothing. Test and local state is created through Actions
 * by `php artisan app:seed-test-state` (TC-4), which exists only in local and testing.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        //
    }
}
