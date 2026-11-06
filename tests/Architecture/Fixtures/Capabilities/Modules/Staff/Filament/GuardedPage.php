<?php

namespace ArchFixtures\Capabilities\Modules\Staff\Filament;

use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Staff\Filament\GuardedByCapability;
use Filament\Pages\Page;

// Fixture: a console page that declares a known capability and uses the guard is not reported.
#[NeedsCapability('staff.manage')]
final class GuardedPage extends Page
{
    use GuardedByCapability;
}
