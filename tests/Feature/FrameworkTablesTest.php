<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('runs on PostgreSQL 17', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql')
        ->and(intdiv((int) DB::scalar('SHOW server_version_num'), 10000))->toBe(17);
});

it('creates only Laravel framework tables when migrated', function () {
    $tables = array_map(fn (string $table) => str_replace('public.', '', $table), Schema::getTableListing());
    sort($tables);

    expect($tables)->toBe(['cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs', 'migrations', 'sessions']);
});
