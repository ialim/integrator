<?php

namespace App\Modules\Catalogue\Actions;

use App\Modules\Catalogue\Capabilities\CatalogueCapabilities;
use App\Modules\Catalogue\Queries\ProductRanges;
use App\Modules\Catalogue\Queries\ProductRangeSummary;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Shared\StatusTables\TransitionRefused;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * An Admin renames an active Product Range, with a required reason. The new
 * name must not be another active range's; the old name, the new name and
 * the reason are audited together.
 */
#[NeedsCapability(CatalogueCapabilities::MANAGE_RANGES)]
final class RenameProductRange extends ProductRangeAction
{
    public const MOVE = 'rename';

    public const AUDIT_ACTION = 'catalogue.range_renamed';

    /**
     * @throws InvalidRangeDetails when the name or the reason is refused
     * @throws TransitionRefused when the range is retired
     * @throws AuthorizationException when $actor does not hold the capability
     * @throws ModelNotFoundException when the range does not exist
     */
    public function handle(Model $actor, string $rangeId, string $name, string $reason): ProductRangeSummary
    {
        $this->guardingName($name, fn () => $this->transaction(function () use ($actor, $rangeId, $name, $reason): void {
            [$current, $range] = $this->lockActorAndRange($actor, $rangeId);
            $this->moveRange($range, self::MOVE);
            $name = $this->name($name);
            $reason = $this->reason($reason);

            if ($name === $range->name) {
                throw new InvalidRangeDetails('name', "It is already called {$name}. Enter a new name, or cancel.");
            }

            $this->refuseTakenName($name, $range->id);
            $range->forceFill(['name' => $name]);
            $this->saveWithReason(Actor::staff((string) $current->getKey()), self::AUDIT_ACTION, $range, $reason);
        }));

        return app(ProductRanges::class)->find($rangeId) ?? throw new ModelNotFoundException;
    }
}
