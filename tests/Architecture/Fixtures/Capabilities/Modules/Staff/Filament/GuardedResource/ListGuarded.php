<?php

namespace ArchFixtures\Capabilities\Modules\Staff\Filament\GuardedResource;

use App\Modules\Shared\Filament\GuardedByCapability;
use Filament\Resources\Pages\Page;

// Fixture: a resource's page with no declaration of its own that uses the guard is not reported.
final class ListGuarded extends Page
{
    use GuardedByCapability;
}
