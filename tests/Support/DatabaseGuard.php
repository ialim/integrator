<?php

namespace Tests\Support;

use PDO;
use Throwable;

/**
 * TC-1 / R-11: the suite runs only on PostgreSQL 17. Locking, partial unique
 * indexes and triggers behave differently elsewhere, so a green run on any
 * other database would prove nothing.
 */
final class DatabaseGuard
{
    public const REQUIRED_MAJOR = 17;

    /**
     * @param  array<string, mixed>|null  $config  the connection's config, null if it is not defined
     * @param  (callable(array<string, mixed>): int)|null  $serverVersion  returns server_version_num
     * @return string|null why the suite must not run, or null when it may
     */
    public static function refusal(string $connection, ?array $config, ?callable $serverVersion = null): ?string
    {
        if ($config === null) {
            return "Tests refused: database connection [{$connection}] is not defined. Tests run only on PostgreSQL ".self::REQUIRED_MAJOR.'.';
        }

        $driver = (string) ($config['driver'] ?? 'unknown');

        if ($driver !== 'pgsql') {
            return "Tests refused: database connection [{$connection}] uses the [{$driver}] driver. Tests run only on PostgreSQL ".self::REQUIRED_MAJOR.'.';
        }

        $serverVersion ??= self::serverVersion(...);

        try {
            $version = $serverVersion($config);
        } catch (Throwable $e) {
            return "Tests refused: could not reach PostgreSQL on database connection [{$connection}]: ".$e->getMessage();
        }

        $major = intdiv($version, 10000);

        if ($major !== self::REQUIRED_MAJOR) {
            return "Tests refused: database connection [{$connection}] is PostgreSQL {$major}. Tests run only on PostgreSQL ".self::REQUIRED_MAJOR.'.';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function serverVersion(array $config): int
    {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? '5432',
            $config['database'] ?? '',
        );

        $pdo = new PDO($dsn, (string) ($config['username'] ?? ''), (string) ($config['password'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);

        return (int) $pdo->query('SHOW server_version_num')->fetchColumn();
    }
}
