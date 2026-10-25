<?php

namespace App\Modules\Shared\Providers;

use Attribute;

/**
 * Declares the fake of a provider interface in a module's `Contracts/` (AD-15).
 * Testing and staging bind the fake; an architecture test fails any
 * `Contracts/` interface without this attribute.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class HasFake
{
    /**
     * @param  class-string  $fake
     */
    public function __construct(public readonly string $fake) {}
}
