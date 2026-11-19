<?php

namespace App\Modules\Shared\Capabilities;

use LogicException;

/**
 * Every module's capabilities (ADR 0002, point 4): a container singleton each
 * module declares its own into from its service provider, through
 * `callAfterResolving(CapabilityDeclarations::class, ...)`. Each name starts
 * with the declaring module's prefix (`catalogue.`, `pricing.`), and the
 * constant naming it lives in that module.
 *
 * It only lists what was declared. Staff's capability registry reads it and
 * stays the only place that answers who holds a capability.
 */
final class CapabilityDeclarations
{
    /** @var array<string, array{what: string, groups: list<CapabilityGroup>}> */
    private array $declared = [];

    /**
     * Declares $capability, held by $groups and by Admin. $what names it in
     * words for a refusal: "The Support desk".
     */
    public function declare(string $capability, string $what, CapabilityGroup ...$groups): void
    {
        if (isset($this->declared[$capability])) {
            throw new LogicException("The capability {$capability} is declared twice.");
        }

        $holders = array_values(array_filter($groups, fn (CapabilityGroup $group): bool => $group !== CapabilityGroup::Admin));
        $this->declared[$capability] = ['what' => $what, 'groups' => [...$holders, CapabilityGroup::Admin]];
    }

    /**
     * Every declaration, in the order declared; Admin last in each.
     *
     * @return array<string, array{what: string, groups: list<CapabilityGroup>}>
     */
    public function all(): array
    {
        return $this->declared;
    }
}
