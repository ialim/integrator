<?php

namespace Tests\Architecture\Support;

/**
 * Each architecture rule's real configuration (roots, namespace, exemptions),
 * in one place so the real test and its planted-violation test use the same one.
 */
final class RealScans
{
    public const SCHEMA_PATHS = ['/Database/Migrations/', '/Database/Views/', '/database/migrations/'];

    /** @return list<Violation> */
    public static function modelWrites(): array
    {
        return (new ModelWriteScanner)->scan([app_path(), base_path('routes'), database_path()], self::SCHEMA_PATHS);
    }

    /** @return list<Violation> */
    public static function boundaries(): array
    {
        return (new ModuleBoundaryScanner)->scan(app_path('Modules'), 'App\\Modules', [database_path('migrations')]);
    }

    /** @return list<Violation> */
    public static function foreignKeys(): array
    {
        return (new ForeignKeyScanner)->scan(app_path('Modules'), [database_path('migrations')]);
    }

    /** @return list<Violation> */
    public static function clock(): array
    {
        return (new ClockScanner)->scan(
            [app_path(), base_path('routes'), database_path()],
            [SourceFiles::normalise(app_path('Modules/Shared')).'/'],
        );
    }

    /** @return list<Violation> */
    public static function contractFakes(): array
    {
        return (new ContractFakeScanner)->scan(app_path('Modules'));
    }

    /** @return list<Violation> */
    public static function uuidKeys(): array
    {
        return (new UuidKeyScanner)->scan([app_path()]);
    }

    /**
     * Writes a violating file at a path relative to the project root, runs the
     * scan, and always deletes the file again.
     *
     * @param  callable(): list<Violation>  $scan
     * @return list<string> the `file:line` of each violation found in the planted file
     */
    public static function withPlanted(string $relative, string $code, callable $scan): array
    {
        $path = base_path($relative);
        $created = [];

        for ($directory = dirname($path); ! is_dir($directory); $directory = dirname($directory)) {
            $created[] = $directory; // deepest first
        }

        if ($created !== []) {
            mkdir($created[0], 0777, true);
        }

        file_put_contents($path, $code);

        try {
            $violations = $scan();
        } finally {
            @unlink($path);

            foreach ($created as $directory) {
                @rmdir($directory);
            }
        }

        return array_values(array_filter(
            array_map(fn (Violation $v) => "{$v->file}:{$v->line}", $violations),
            fn (string $location) => str_starts_with($location, $relative.':'),
        ));
    }
}
