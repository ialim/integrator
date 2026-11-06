<?php

namespace App\Modules\Staff\Filament\Pages;

use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Staff\Capabilities\StaffCapabilities;
use App\Modules\Staff\Filament\ConsoleRail;
use App\Modules\Staff\Filament\GuardedByCapability;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * The first page of the console, first in the rail's Queues group, open to
 * every role. It is empty until the role-aware queues of later epics arrive.
 */
#[NeedsCapability(StaffCapabilities::TODAY)]
final class Today extends Page
{
    use GuardedByCapability;

    protected static ?string $slug = 'today';

    protected static ?string $title = 'Today';

    protected static ?string $navigationLabel = 'Today';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSun;

    protected static string|UnitEnum|null $navigationGroup = ConsoleRail::QUEUES;

    protected static ?int $navigationSort = -100;

    protected string $view = 'staff::today';
}
