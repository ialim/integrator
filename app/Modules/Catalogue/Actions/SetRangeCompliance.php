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
 * An Admin changes what an active Product Range's Products need (NCC type
 * approval, SONCAP or none), with a required reason; audited.
 */
#[NeedsCapability(CatalogueCapabilities::MANAGE_RANGES)]
final class SetRangeCompliance extends ProductRangeAction
{
    public const MOVE = 'set_compliance';

    public const AUDIT_ACTION = 'catalogue.range_compliance_set';

    /**
     * @throws InvalidRangeDetails when the flag or the reason is refused
     * @throws TransitionRefused when the range is retired
     * @throws AuthorizationException when $actor does not hold the capability
     * @throws ModelNotFoundException when the range does not exist
     */
    public function handle(Model $actor, string $rangeId, string $compliance, string $reason): ProductRangeSummary
    {
        $this->transaction(function () use ($actor, $rangeId, $compliance, $reason): void {
            [$current, $range] = $this->lockActorAndRange($actor, $rangeId);
            $this->moveRange($range, self::MOVE);
            $flag = $this->compliance($compliance);
            $reason = $this->reason($reason);

            if ($flag === $range->compliance) {
                throw new InvalidRangeDetails('compliance', "Its Products already need {$flag->label()}. Choose another, or cancel.");
            }

            $range->forceFill(['compliance' => $flag]);
            $this->saveWithReason(Actor::staff((string) $current->getKey()), self::AUDIT_ACTION, $range, $reason);
        });

        return app(ProductRanges::class)->find($rangeId) ?? throw new ModelNotFoundException;
    }
}
