<?php

namespace Tests\Architecture\Support;

use Stringable;

/**
 * One broken rule, located by file (relative to the project root) and line.
 */
final class Violation implements Stringable
{
    public function __construct(
        public readonly string $file,
        public readonly int $line,
        public readonly string $message,
    ) {}

    public function __toString(): string
    {
        return "{$this->file}:{$this->line} {$this->message}";
    }

    /**
     * @param  list<Violation>  $violations
     */
    public static function report(string $rule, array $violations): string
    {
        return $rule.' is broken in '.count($violations)." place(s):\n  ".implode("\n  ", array_map('strval', $violations));
    }
}
