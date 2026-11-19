<?php

namespace App\Modules\Catalogue\Actions;

use App\Modules\Catalogue\Capabilities\CatalogueCapabilities;
use App\Modules\Catalogue\Locks\LockProductRange;
use App\Modules\Catalogue\Models\ProductRange;
use App\Modules\Catalogue\Queries\ProductRanges;
use App\Modules\Catalogue\Queries\ProductRangeSummary;
use App\Modules\Catalogue\StatusTables\ProductRangeStatus;
use App\Modules\Pricing\Actions\SetRangeMarkup;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Capabilities\NeedsCapability;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

/**
 * An Admin creates a Product Range (FR-13): a name no other active range
 * has, and the compliance its Products need. It starts Active, and Pricing's
 * {@see SetRangeMarkup} gives it the starting Platform Markup of 25% in the
 * same transaction, under the range's lock, so a range never exists without
 * its markup. Both are audited.
 */
#[NeedsCapability(CatalogueCapabilities::MANAGE_RANGES)]
final class CreateProductRange extends ProductRangeAction
{
    public const AUDIT_ACTION = 'catalogue.range_created';

    /**
     * @throws InvalidRangeDetails when the name or the compliance flag is refused
     * @throws AuthorizationException when $actor does not hold the capability
     */
    public function handle(Model $actor, string $name, string $compliance): ProductRangeSummary
    {
        $id = $this->guardingName($name, fn (): string => $this->transaction(function () use ($actor, $name, $compliance): string {
            $current = $this->lockActor($actor);
            $name = $this->name($name);
            $flag = $this->compliance($compliance);
            $this->refuseTakenName($name);

            $range = new ProductRange;
            $range->forceFill(['name' => $name, 'compliance' => $flag, 'status' => ProductRangeStatus::initial()]);
            $this->saveAndAudit(Actor::staff((string) $current->getKey()), self::AUDIT_ACTION, $range);

            // The new row is locked before its markup is set, so the nested Pricing Action can assert it.
            app(LockProductRange::class)->handle($range->id);
            app(SetRangeMarkup::class)->handle($current, $range->id, SetRangeMarkup::STARTING_MARKUP);

            return $range->id;
        }));

        return app(ProductRanges::class)->find($id) ?? throw new \LogicException("The range {$id} was not saved.");
    }
}
