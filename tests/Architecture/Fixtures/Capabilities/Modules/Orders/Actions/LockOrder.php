<?php

namespace ArchFixtures\Capabilities\Modules\Orders\Actions;

use App\Modules\Shared\Locks\LockAction;

// Fixture: a Lock action is not an entry point and is not reported, even in Actions/.
final class LockOrder extends LockAction
{
    public static function root(): string
    {
        return 'order';
    }

    protected function table(): string
    {
        return 'orders';
    }

    public function handle(string ...$ids): void {}
}
