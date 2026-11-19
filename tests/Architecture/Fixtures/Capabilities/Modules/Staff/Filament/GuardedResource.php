<?php

namespace ArchFixtures\Capabilities\Modules\Staff\Filament;

use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Shared\Filament\GuardedByCapability;
use Filament\Resources\Resource;

// Fixture: a console resource that declares a known capability and uses the guard is not reported.
#[NeedsCapability('staff.manage')]
final class GuardedResource extends Resource
{
    use GuardedByCapability;
}
