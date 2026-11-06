<?php

namespace ArchFixtures\Capabilities\Modules\Orders\Actions;

use App\Modules\Shared\Actions\Action;
use App\Modules\Shared\Capabilities\NeedsNoCapability;

// Fixture: "needs none" without a reason is reported.
#[NeedsNoCapability('')]
final class ExpireOrder extends Action
{
    public function handle(): void {}
}
