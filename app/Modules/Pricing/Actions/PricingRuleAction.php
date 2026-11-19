<?php

namespace App\Modules\Pricing\Actions;

use App\Modules\Shared\Actions\Action;
use App\Modules\Staff\Locks\LockStaffMember;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

/**
 * What the Admin's Pricing rule Actions share: locking the acting staff
 * member through Staff's public Lock action, first in the fixed order, the
 * capability check on the actor as read under the lock, and the required
 * reason. Each concrete Action declares its capability with
 * #[NeedsCapability].
 */
abstract class PricingRuleAction extends Action
{
    /**
     * @throws AuthorizationException
     */
    protected function lockActor(Model $actor): Model
    {
        $id = (string) $actor->getKey();
        $locked = app(LockStaffMember::class)->handle($id);
        $current = $locked[$id] ?? throw new AuthorizationException('Only an active staff member can change Pricing rules. Nothing was changed.');
        $this->authorizeCapability($current);

        return $current;
    }

    /**
     * @throws InvalidPricingInput
     */
    protected function reason(?string $reason): string
    {
        $reason = trim((string) $reason);

        if ($reason === '') {
            throw InvalidPricingInput::noReason();
        }

        return $reason;
    }
}
