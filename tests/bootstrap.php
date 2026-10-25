<?php

/*
 * PHPUnit bootstrap. Before any test runs, refuse to start unless the test
 * connection is PostgreSQL 17 (TC-1 / R-11).
 */

use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Tests\Support\DatabaseGuard;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->bootstrapWith([LoadEnvironmentVariables::class, LoadConfiguration::class]);

$config = $app->make('config');
$connection = (string) $config->get('database.default');
$refusal = DatabaseGuard::refusal($connection, $config->get("database.connections.{$connection}"));

if ($refusal !== null) {
    fwrite(STDERR, PHP_EOL.$refusal.PHP_EOL.PHP_EOL);

    exit(1);
}

unset($app, $config, $connection, $refusal);
