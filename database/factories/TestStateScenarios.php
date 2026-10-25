<?php

namespace Database\Factories;

/**
 * The named states `php artisan app:seed-test-state` can create. Each entry is
 * an invokable class that builds its state only through {@see ActionFactory}
 * subclasses. Epics add scenarios as their Actions arrive (for example a
 * Level 3 business, an approved Supplier, an active Product with stock).
 */
final class TestStateScenarios
{
    /**
     * @return array<string, class-string> name => invokable scenario class
     */
    public static function all(): array
    {
        return [
            //
        ];
    }
}
