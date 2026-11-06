<?php

namespace App\Modules\Staff\Capabilities;

use Illuminate\Auth\Access\AuthorizationException;

/**
 * A staff member called an Action whose capability they do not hold. The
 * message is in words for the screen and names who can ("Adding, changing
 * and deactivating staff accounts is for Admins. Ask Ada Founder.").
 */
final class CapabilityRefused extends AuthorizationException
{
    public function __construct(public readonly string $capability, string $message)
    {
        parent::__construct($message);
    }
}
