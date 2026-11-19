<?php

namespace App\Modules\Pricing\Actions;

use App\Modules\Pricing\Capabilities\PricingCapabilities;
use App\Modules\Pricing\Locks\LockPricingSetting;
use App\Modules\Pricing\Models\PricingSetting;
use App\Modules\Pricing\Queries\PricingRules;
use App\Modules\Pricing\Queries\RulePercent;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Capabilities\NeedsCapability;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

/**
 * An Admin changes the margin floor (FR-13): the pre-VAT price is never
 * below Supplier Cost × (1 + floor). It starts at 8%, inserted by the
 * migration; this is the only writer after that. Never below zero; a
 * change needs a reason, audited with the old and new values.
 */
#[NeedsCapability(PricingCapabilities::MANAGE_RULES)]
final class SetMarginFloor extends PricingRuleAction
{
    public const AUDIT_ACTION = 'pricing.margin_floor_set';

    /**
     * @throws InvalidPricingInput when the floor or the reason is refused
     * @throws AuthorizationException when $actor does not hold the capability
     */
    public function handle(Model $actor, string $floor, string $reason): RulePercent
    {
        $this->transaction(function () use ($actor, $floor, $reason): void {
            $current = $this->lockActor($actor);
            $row = PricingSetting::query()->where('key', PricingSetting::MARGIN_FLOOR)->firstOrFail();
            app(LockPricingSetting::class)->handle($row->id);
            $row->refresh();

            $percent = Percentage::parse($floor, 'floor', 'The margin floor');
            $reason = $this->reason($reason);
            $was = Percentage::parse((string) $row->percent, 'floor', 'The margin floor');

            if ($was === $percent) {
                throw new InvalidPricingInput('floor', 'The margin floor is already '.Percentage::words($percent).'. Enter a new floor, or cancel.');
            }

            $row->forceFill(['percent' => $percent]);
            $row->save();
            $this->audit(Actor::staff((string) $current->getKey()), self::AUDIT_ACTION, $row, [
                'floor_percent' => ['from' => Percentage::plain($was), 'to' => Percentage::plain($percent)],
                'reason' => $reason,
            ]);
        });

        return app(PricingRules::class)->marginFloor();
    }
}
