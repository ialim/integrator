<?php

namespace App\Modules\Shared\Capabilities;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

/**
 * The server's capability check behind {@see NeedsCapability}, so the kernel
 * can enforce a declaration without importing `Staff`. Staff binds it to the
 * capability registry. Unbound, resolving it fails: no check means no access.
 */
interface CapabilityCheck
{
    /**
     * Refuses $actor unless their account is active and holds $capability.
     *
     * @throws AuthorizationException in words for the screen, naming who can
     */
    public function authorize(Model $actor, string $capability): void;
}
