<?php

use Tests\Architecture\Support\SourceFiles;
use Tests\Architecture\Support\Violation;
use Tests\TestCase;

/*
 * Every suite boots the application. tests/bootstrap.php has already refused
 * to start unless the test connection is PostgreSQL 17 (TC-1).
 */
pest()->extend(TestCase::class)->in('Unit', 'Feature', 'Architecture', 'Flows', '../app/Modules');

/** Absolute path of an architecture-rule fixture. */
function archFixture(string $path = ''): string
{
    return SourceFiles::normalise(base_path('tests/Architecture/Fixtures/'.$path));
}

/**
 * When ARCH_FIXTURES=1, the real architecture tests also scan the failing
 * fixtures, so each rule can be seen to fail and name the fixture file.
 */
function withFixtures(): bool
{
    return getenv('ARCH_FIXTURES') === '1';
}

/**
 * The `file:line` of each violation, for comparing against a fixture.
 *
 * @param  list<Violation>  $violations
 * @return list<string>
 */
function locations(array $violations): array
{
    return array_map(fn (Violation $v) => "{$v->file}:{$v->line}", $violations);
}
