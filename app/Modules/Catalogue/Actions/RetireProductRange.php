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
 * An Admin retires a Product Range, with a required reason. A range that
 * still holds Products is refused, saying how many must be moved first
 * (PRD section 8.3, question 18). A retired range stays in the table with its
 * History, takes no new Products and no further edits, and is never made
 * active again.
 */
#[NeedsCapability(CatalogueCapabilities::MANAGE_RANGES)]
final class RetireProductRange extends ProductRangeAction
{
    public const MOVE = 'retire';

    public const AUDIT_ACTION = 'catalogue.range_retired';

    /**
     * @throws InvalidRangeDetails when the reason is missing
     * @throws TransitionRefused when the range holds Products, or is already retired
     * @throws AuthorizationException when $actor does not hold the capability
     * @throws ModelNotFoundException when the range does not exist
     */
    public function handle(Model $actor, string $rangeId, string $reason): ProductRangeSummary
    {
        $this->transaction(function () use ($actor, $rangeId, $reason): void {
            [$current, $range] = $this->lockActorAndRange($actor, $rangeId);
            $reason = $this->reason($reason);
            $products = app(ProductRanges::class)->productCount($range->id);

            if ($products > 0) {
                throw $this->refuse($range, 'status', self::MOVE, self::holdsProducts($range->name, $products));
            }

            $this->moveRange($range, self::MOVE);
            $this->saveWithReason(Actor::staff((string) $current->getKey()), self::AUDIT_ACTION, $range, $reason);
        });

        return app(ProductRanges::class)->find($rangeId) ?? throw new ModelNotFoundException;
    }

    /** "Nothing was changed: Phones still holds 3 Products. Move its 3 Products to another range first." */
    public static function holdsProducts(string $name, int $products): string
    {
        $noun = $products === 1 ? 'Product' : 'Products';

        return "Nothing was changed: {$name} still holds {$products} {$noun}. Move its {$products} {$noun} to another range first.";
    }
}
