<?php

namespace ArchFixtures\Capabilities\Modules\Orders\Actions;

// Fixture: a concrete Action that declares no capability is reported.
final class ApproveOrder extends OrderAction
{
    public function handle(): void {}
}
