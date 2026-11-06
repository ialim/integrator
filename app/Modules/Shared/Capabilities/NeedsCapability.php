<?php

namespace App\Modules\Shared\Capabilities;

use Attribute;

/**
 * Declares the one capability a staff member needs to open a console page or
 * resource, or to call an Action (FR-52). The capability is a name in the
 * Staff capability registry, such as `staff.manage`; the server checks it on
 * every page load and every Action call. An architecture test fails, naming
 * the file, for any Action, page or resource that declares neither this nor
 * {@see NeedsNoCapability}, or that names a capability the registry does not
 * know.
 *
 * In `Shared`, so any module can declare its own pages and Actions without
 * importing `Staff`.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class NeedsCapability
{
    public function __construct(public readonly string $capability) {}
}
