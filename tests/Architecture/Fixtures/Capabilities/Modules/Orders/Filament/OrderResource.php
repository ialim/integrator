<?php

namespace ArchFixtures\Capabilities\Modules\Orders\Filament;

use App\Modules\Shared\Capabilities\NeedsCapability;
use Filament\Resources\Resource;

// Fixture: a console resource that declares a capability but does not use GuardedByCapability is reported.
#[NeedsCapability('staff.manage')]
final class OrderResource extends Resource {}
