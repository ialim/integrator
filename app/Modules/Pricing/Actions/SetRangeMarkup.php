<?php

namespace App\Modules\Pricing\Actions;

use App\Modules\Catalogue\Locks\LockProductRange;
use App\Modules\Catalogue\Queries\ProductRanges;
use App\Modules\Pricing\Capabilities\PricingCapabilities;
use App\Modules\Pricing\Models\RangeMarkup;
use App\Modules\Pricing\Queries\PricingRules;
use App\Modules\Pricing\Queries\RulePercent;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Shared\Locks\LockRegistry;
use App\Modules\Staff\Locks\LockStaffMember;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Sets a Product Range's Platform Markup (FR-13, AD-7): a percentage over
 * Supplier Cost, never below zero (edge-case row 20; a database check
 * refuses it too). The range's lock covers its markup, so a range and its
 * markup are never written apart.
 *
 * Called on its own by an Admin, it locks the actor and then the range, and
 * a change needs a reason, which is audited with the old and new values.
 * Catalogue's CreateProductRange calls it inside its own transaction, with
 * the range already locked, to give a new range its starting 25%; then it
 * asserts those locks instead of taking them, and needs no reason, since it
 * is part of the creation. A retired range's markup never changes.
 */
#[NeedsCapability(PricingCapabilities::MANAGE_RULES)]
final class SetRangeMarkup extends PricingRuleAction
{
    /** A new range starts at 25% (FR-13). */
    public const STARTING_MARKUP = '25';

    public const AUDIT_ACTION = 'pricing.markup_set';

    /**
     * @param  string|null  $reason  required for every change after the range's first markup
     *
     * @throws InvalidPricingInput when the markup or the reason is refused, or the range is retired
     * @throws AuthorizationException when $actor does not hold the capability
     * @throws ModelNotFoundException when the range does not exist
     */
    public function handle(Model $actor, string $rangeId, string $markup, ?string $reason = null): RulePercent
    {
        $this->transaction(function () use ($actor, $rangeId, $markup, $reason): void {
            $current = $this->lockActorAndRange($actor, $rangeId);
            $range = app(ProductRanges::class)->find($rangeId) ?? throw new ModelNotFoundException("No Product Range {$rangeId}.");

            if ($range->isRetired) {
                throw new InvalidPricingInput('markup', "{$range->name} is retired, so its markup cannot change. Nothing was changed.");
            }

            $percent = Percentage::parse($markup, 'markup', 'A markup');
            $row = RangeMarkup::query()->where('product_range_id', $rangeId)->first();
            $changes = ['markup_percent' => ['from' => $row === null ? null : Percentage::plain((string) $row->markup_percent), 'to' => Percentage::plain($percent)]];

            if ($row === null) {
                $row = new RangeMarkup;
                $row->forceFill(['product_range_id' => $rangeId]);
            } else {
                $reason = $this->reason($reason);

                if (Percentage::parse((string) $row->markup_percent, 'markup', 'A markup') === $percent) {
                    throw new InvalidPricingInput('markup', "The markup for {$range->name} is already ".Percentage::words($percent).'. Enter a new markup, or cancel.');
                }

                $changes['reason'] = $reason;
            }

            $row->forceFill(['markup_percent' => $percent]);
            $row->save();
            $this->audit(Actor::staff((string) $current->getKey()), self::AUDIT_ACTION, $row, $changes);
        });

        return app(PricingRules::class)->markupFor($rangeId) ?? throw new ModelNotFoundException;
    }

    /**
     * The acting member as read under the lock. Nested inside the Action that
     * already locked the range (CreateProductRange), it asserts the locks it
     * needs; on its own, it takes them in the fixed order.
     */
    private function lockActorAndRange(Model $actor, string $rangeId): Model
    {
        if (app(LockRegistry::class)->isHeld(LockProductRange::ROOT, $rangeId)) {
            $this->assertLocked(LockStaffMember::ROOT, (string) $actor->getKey());
            $this->authorizeCapability($actor);

            return $actor;
        }

        $current = $this->lockActor($actor);
        app(LockProductRange::class)->handle($rangeId);

        return $current;
    }
}
