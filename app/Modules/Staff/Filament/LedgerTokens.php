<?php

namespace App\Modules\Staff\Filament;

use RuntimeException;

/**
 * Reads colours from the one Ledger token file, resources/tokens/ledger.css,
 * so the console's Filament palette is built from the same values as the
 * theme and the app rather than from a second copy.
 */
final class LedgerTokens
{
    /** @var array<string, string>|null */
    private static ?array $tokens = null;

    public static function color(string $name): string
    {
        $tokens = self::$tokens ??= self::read();
        $key = 'ledger-'.$name;

        if (! isset($tokens[$key])) {
            throw new RuntimeException("The Ledger token --{$key} is not in resources/tokens/ledger.css.");
        }

        return $tokens[$key];
    }

    /**
     * @return array<string, string>
     */
    private static function read(): array
    {
        $css = file_get_contents(resource_path('tokens/ledger.css'));

        if ($css === false) {
            throw new RuntimeException('Cannot read resources/tokens/ledger.css.');
        }

        preg_match_all('/--(ledger-[a-z0-9-]+):\s*(#[0-9a-f]{3,8})\s*;/i', $css, $matches, PREG_SET_ORDER);

        $tokens = [];

        foreach ($matches as [, $name, $value]) {
            $tokens[strtolower($name)] = strtolower($value);
        }

        return $tokens;
    }
}
