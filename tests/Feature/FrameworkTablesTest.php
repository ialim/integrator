<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('runs on PostgreSQL 17', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql')
        ->and(intdiv((int) DB::scalar('SHOW server_version_num'), 10000))->toBe(17);
});

it('creates the framework tables and each module\'s own tables when migrated', function () {
    $tables = array_map(fn (string $table) => str_replace('public.', '', $table), Schema::getTableListing());
    sort($tables);

    expect($tables)->toBe([
        'audit_entries', 'audit_seals', // Shared
        'cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs', 'migrations',
        'notification_deliveries', // Notifications
        'outbox_events', // Shared
        'pricing_settings', // Pricing
        'product_ranges', // Catalogue
        'range_markups', // Pricing
        'sessions',
        'staff_members', 'staff_sessions', // Staff
    ]);
});
