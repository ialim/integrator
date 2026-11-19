<?php

namespace App\Modules\Shared\Filament;

use App\Modules\Shared\Capabilities\NeedsCapability;

/**
 * The console's page check behind {@see GuardedByCapability}, so any module's
 * page or resource can be guarded by its #[NeedsCapability] declaration
 * without importing `Staff` (AD-2). Staff binds it to the capability
 * registry; unbound, resolving it fails, so no guard means no access.
 *
 * A page also asks it whether the signed-in member may use a second
 * capability, to leave out the buttons they cannot press.
 */
interface ConsolePageGuard
{
    /** Whether the signed-in staff member may use $capability now. */
    public function allows(string $capability): bool;

    /** "The Staff page is for Admins. Ask Ada Founder.": who can, and an active person who does. */
    public function refusal(string $pageTitle, string $capability): string;

    /** The page renders its refusal inside the console shell; the response goes out as 403. */
    public function markRefused(): void;
}
