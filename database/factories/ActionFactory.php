<?php

namespace Database\Factories;

use LogicException;

/**
 * Base for test-only factories (TC-4). A factory never writes a model: it
 * builds the input of one Action and calls that Action, so seeded state
 * passes through the same locks, transition checks and audit as real use.
 *
 * Factories exist only in `local` and `testing`; anywhere else they refuse.
 *
 * @template TResult
 */
abstract class ActionFactory
{
    public const ENVIRONMENTS = ['local', 'testing'];

    /**
     * The Action class this factory drives. It is resolved from the container
     * and its `handle()` method is called with the definition as named arguments.
     *
     * @return class-string
     */
    abstract protected function action(): string;

    /**
     * Default named arguments for the Action.
     *
     * @return array<string, mixed>
     */
    abstract protected function definition(): array;

    final public function __construct()
    {
        self::assertAvailable();
    }

    public static function new(): static
    {
        return new static;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return TResult
     */
    public function create(array $overrides = []): mixed
    {
        $action = app($this->action());

        if (! method_exists($action, 'handle')) {
            throw new LogicException($this->action().' has no handle() method to call.');
        }

        return $action->handle(...array_merge($this->definition(), $overrides));
    }

    public static function assertAvailable(): void
    {
        if (! app()->environment(self::ENVIRONMENTS)) {
            throw new LogicException('Test factories exist only in local and testing, not in '.app()->environment().'.');
        }
    }
}
