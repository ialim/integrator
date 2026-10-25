<?php

namespace App\Modules\Shared\Support;

use Illuminate\Support\Str;

/**
 * The one source of primary keys (AD-11): time-ordered UUIDv7 strings.
 * Human-facing references are separate columns, never these ids.
 */
final class Id
{
    public static function generate(): string
    {
        return (string) Str::uuid7();
    }

    public static function isValid(string $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value) === 1;
    }
}
