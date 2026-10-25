<?php

use Tests\Support\DatabaseGuard;

it('refuses a connection that is not PostgreSQL, naming it', function (string $driver) {
    expect(DatabaseGuard::refusal('testing_db', ['driver' => $driver]))
        ->toBe("Tests refused: database connection [testing_db] uses the [{$driver}] driver. Tests run only on PostgreSQL 17.");
})->with(['sqlite', 'mysql', 'mariadb', 'sqlsrv']);

it('refuses PostgreSQL servers other than 17, naming the connection', function (int $version, int $major) {
    expect(DatabaseGuard::refusal('pgsql', ['driver' => 'pgsql'], fn () => $version))
        ->toBe("Tests refused: database connection [pgsql] is PostgreSQL {$major}. Tests run only on PostgreSQL 17.");
})->with([[160004, 16], [150008, 15], [180001, 18]]);

it('allows PostgreSQL 17', function () {
    expect(DatabaseGuard::refusal('pgsql', ['driver' => 'pgsql'], fn () => 170006))->toBeNull();
});

it('refuses an undefined or unreachable connection', function () {
    expect(DatabaseGuard::refusal('nope', null))->toContain('[nope] is not defined')
        ->and(DatabaseGuard::refusal('pgsql', ['driver' => 'pgsql'], fn () => throw new RuntimeException('connection refused')))
        ->toContain('could not reach PostgreSQL on database connection [pgsql]: connection refused');
});
