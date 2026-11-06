<?php

namespace App\Modules\Shared\Capabilities;

use Attribute;

/**
 * Declares that an Action or console page has no staff actor to check, and
 * why: sign-in, accepting an invitation, an outbox listener, a system job, or
 * a step nested inside an Action that has already checked. The reason is
 * required; an empty one fails the architecture test like a missing
 * declaration.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class NeedsNoCapability
{
    public function __construct(public readonly string $reason) {}
}
