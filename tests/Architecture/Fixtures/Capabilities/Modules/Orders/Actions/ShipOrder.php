<?php

namespace ArchFixtures\Capabilities\Modules\Orders\Actions;

use App\Modules\Shared\Actions\Action;
use App\Modules\Shared\Capabilities\NeedsCapability;

// Fixture: an Action that declares a capability but never checks it is reported.
#[NeedsCapability('staff.manage')]
final class ShipOrder extends Action
{
    public function handle(): void {}
}
