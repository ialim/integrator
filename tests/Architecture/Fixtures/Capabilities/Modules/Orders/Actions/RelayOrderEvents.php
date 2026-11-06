<?php

namespace ArchFixtures\Capabilities\Modules\Orders\Actions;

use App\Modules\Shared\Actions\Action;
use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Shared\Capabilities\NeedsNoCapability;
use Illuminate\Database\Eloquent\Model;

// Fixtures: an Action with no staff actor that says why, and one that needs a
// known capability (named by string: Orders cannot import Staff) and checks
// it, are not reported.
#[NeedsNoCapability('Run by the outbox relay; there is no staff actor')]
final class RelayOrderEvents extends Action
{
    public function handle(): void {}
}

#[NeedsCapability('staff.manage')]
final class ReassignOrder extends Action
{
    public function handle(Model $actor): void
    {
        $this->authorizeCapability($actor);
    }
}
