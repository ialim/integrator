<?php

namespace App\Modules\Pricing\Locks;

use App\Modules\Pricing\Models\PricingSetting;
use App\Modules\Shared\Locks\LockAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Locks Pricing settings' rows (the `pricing_setting` aggregate root, such as
 * the margin floor) until the Action's transaction ends.
 */
final class LockPricingSetting extends LockAction
{
    public const ROOT = 'pricing_setting';

    public static function root(): string
    {
        return self::ROOT;
    }

    protected function table(): string
    {
        return (new PricingSetting)->getTable();
    }

    /**
     * @throws ModelNotFoundException when a setting does not exist
     */
    public function handle(string ...$ids): void
    {
        $this->lockRows(array_values($ids));
    }
}
