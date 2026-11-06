<?php

namespace App\Modules\Shared\Providers;

/**
 * The scripting every provider fake offers (TC-3): a test lists what each
 * coming call does, in order, and the fake plays them back one per call.
 * Once the script runs out, every call succeeds.
 *
 *     app(EmailProvider::class)->script(
 *         ScriptedOutcome::Refused, ScriptedOutcome::Timeout, ScriptedOutcome::Error, ScriptedOutcome::Success,
 *     );
 *
 * A fake calls {@see self::play()} at the start of each provider method.
 */
trait ScriptsOutcomes
{
    /** @var list<ScriptedOutcome> */
    private array $script = [];

    /** @var list<array{operation: string, outcome: ScriptedOutcome}> */
    private array $calls = [];

    public function script(ScriptedOutcome ...$outcomes): static
    {
        array_push($this->script, ...array_values($outcomes));

        return $this;
    }

    /**
     * Every call so far, with what it did.
     *
     * @return list<array{operation: string, outcome: ScriptedOutcome}>
     */
    public function calls(): array
    {
        return $this->calls;
    }

    /**
     * Outcomes scripted but not yet played.
     *
     * @return list<ScriptedOutcome>
     */
    public function remainingScript(): array
    {
        return $this->script;
    }

    /**
     * Plays the next scripted outcome: returns on success, throws the matching
     * {@see ProviderFailure} otherwise.
     */
    protected function play(string $operation): void
    {
        $outcome = array_shift($this->script) ?? ScriptedOutcome::Success;
        $this->calls[] = ['operation' => $operation, 'outcome' => $outcome];
        $fake = (new \ReflectionClass($this))->getShortName();

        match ($outcome) {
            ScriptedOutcome::Success => null,
            ScriptedOutcome::Refused => throw new ProviderRefused("{$fake} refused {$operation}, as scripted."),
            ScriptedOutcome::Timeout => throw new ProviderTimedOut("{$fake} timed out on {$operation}, as scripted."),
            ScriptedOutcome::Error => throw new ProviderErrored("{$fake} failed {$operation} with an error, as scripted."),
        };
    }
}
