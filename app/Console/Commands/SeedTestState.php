<?php

namespace App\Console\Commands;

use Database\Factories\ActionFactory;
use Database\Factories\TestStateScenarios;
use Illuminate\Console\Command;

/**
 * Creates named test states through Actions (TC-4). The command is enabled,
 * and so registered with Artisan, only in `local` and `testing`.
 */
final class SeedTestState extends Command
{
    protected $signature = 'app:seed-test-state {scenarios?* : Scenario names; all scenarios when omitted}';

    protected $description = 'Create test state through Actions (local and testing only)';

    public function isEnabled(): bool
    {
        return $this->laravel === null
            ? app()->environment(ActionFactory::ENVIRONMENTS)
            : $this->laravel->environment(ActionFactory::ENVIRONMENTS);
    }

    public function handle(): int
    {
        ActionFactory::assertAvailable();

        $available = TestStateScenarios::all();
        /** @var list<string> $requested */
        $requested = $this->argument('scenarios');
        $names = $requested === [] ? array_keys($available) : array_values(array_unique($requested));

        if ($names === []) {
            $this->components->info('No test scenarios are registered yet.');

            return self::SUCCESS;
        }

        $unknown = array_diff($names, array_keys($available));

        if ($unknown !== []) {
            $this->components->error('Unknown scenario: '.implode(', ', $unknown).'.');

            return self::FAILURE;
        }

        foreach ($names as $name) {
            $this->components->task($name, fn () => app($available[$name])());
        }

        return self::SUCCESS;
    }
}
