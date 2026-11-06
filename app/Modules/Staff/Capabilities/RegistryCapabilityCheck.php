<?php

namespace App\Modules\Staff\Capabilities;

use App\Modules\Shared\Capabilities\CapabilityCheck;
use App\Modules\Staff\Models\StaffMember;
use Illuminate\Database\Eloquent\Model;

/**
 * The kernel's {@see CapabilityCheck}, answered by the capability registry.
 * Only a staff member can hold a capability.
 */
final class RegistryCapabilityCheck implements CapabilityCheck
{
    public function __construct(private readonly CapabilityRegistry $registry) {}

    public function authorize(Model $actor, string $capability): void
    {
        if (! $actor instanceof StaffMember) {
            throw new CapabilityRefused($capability, 'Only a staff member can do this. Nothing was changed.');
        }

        $this->registry->authorize($actor, $capability);
    }
}
