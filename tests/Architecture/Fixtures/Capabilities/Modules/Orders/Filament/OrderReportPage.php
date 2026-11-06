<?php

namespace ArchFixtures\Capabilities\Modules\Orders\Filament;

use App\Modules\Shared\Capabilities\NeedsCapability;
use Filament\Pages\Page;

// Fixture: a console page that declares a capability but does not use GuardedByCapability is reported.
#[NeedsCapability('staff.manage')]
final class OrderReportPage extends Page {}
