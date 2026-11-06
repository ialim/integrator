<?php

namespace ArchFixtures\Capabilities\Modules\Orders\Filament\OrderResource;

use App\Modules\Shared\Capabilities\NeedsCapability;
use Filament\Resources\Pages\Page;

// Fixture: a resource's page that declares a capability of its own is reported; it needs its resource's.
#[NeedsCapability('staff.manage')]
final class ListOrders extends Page {}
