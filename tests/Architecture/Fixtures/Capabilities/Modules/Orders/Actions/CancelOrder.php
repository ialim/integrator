<?php

namespace ArchFixtures\Capabilities\Modules\Orders\Actions;

use App\Modules\Shared\Actions\Action;
use App\Modules\Shared\Capabilities\NeedsCapability;

// Fixture: an Action naming a capability the registry does not know is reported.
#[NeedsCapability('orders.cancel')]
final class CancelOrder extends Action
{
    public function handle(): void {}
}
