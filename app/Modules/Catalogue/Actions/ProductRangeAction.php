<?php

namespace App\Modules\Catalogue\Actions;

use App\Modules\Catalogue\Locks\LockProductRange;
use App\Modules\Catalogue\Models\ProductRange;
use App\Modules\Catalogue\Models\RangeCompliance;
use App\Modules\Catalogue\StatusTables\ProductRangeStatus;
use App\Modules\Shared\Actions\Action;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Audit\AuditChanges;
use App\Modules\Shared\Audit\PersonalFields;
use App\Modules\Shared\StatusTables\TransitionRefused;
use App\Modules\Staff\Locks\LockStaffMember;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * What the Admin's Product Range Actions share: locking the acting staff
 * member (through Staff's public Lock action) and then the range, in the
 * fixed lock order; the capability check on the actor as read under the
 * lock; the rules for a name, a compliance flag and a reason; and saving a
 * change with its reason in the audit row. Each concrete Action declares
 * its capability with #[NeedsCapability].
 */
abstract class ProductRangeAction extends Action
{
    /** The unique index on the name, ignoring case, among Active ranges. */
    public const NAME_INDEX = 'product_ranges_active_name_unique';

    /** The name as saved: runs of whitespace become one space, and the ends are trimmed. */
    public static function cleanName(string $name): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }

    /**
     * Locks the acting staff member and checks the Action's capability on
     * them as read under the lock.
     *
     * @throws AuthorizationException
     */
    protected function lockActor(Model $actor): Model
    {
        $id = (string) $actor->getKey();
        $locked = app(LockStaffMember::class)->handle($id);
        $current = $locked[$id] ?? throw new AuthorizationException('Only an active staff member can change Product Ranges. Nothing was changed.');
        $this->authorizeCapability($current);

        return $current;
    }

    /**
     * Locks the actor, then the range, and returns both as read under the lock.
     *
     * @return array{0: Model, 1: ProductRange}
     *
     * @throws ModelNotFoundException when the range does not exist
     */
    protected function lockActorAndRange(Model $actor, string $rangeId): array
    {
        $current = $this->lockActor($actor);
        app(LockProductRange::class)->handle($rangeId);

        return [$current, ProductRange::query()->findOrFail($rangeId)];
    }

    /**
     * Checks $move against the range's transition table; a retired range is
     * refused in words, since it takes no further edits.
     *
     * @throws TransitionRefused
     */
    protected function moveRange(ProductRange $range, string $move): void
    {
        if ($range->status === ProductRangeStatus::Retired) {
            throw $this->refuse($range, 'status', $move, "{$range->name} is retired, so it takes no further edits. Nothing was changed.");
        }

        $this->move($range, 'status', $move);
    }

    /**
     * @throws InvalidRangeDetails
     */
    protected function name(string $name): string
    {
        $name = self::cleanName($name);

        if ($name === '') {
            throw new InvalidRangeDetails('name', 'Enter a name for the Product Range.');
        }

        if (mb_strlen($name) > InvalidRangeDetails::NAME_MAX) {
            throw new InvalidRangeDetails('name', 'Keep the name to '.InvalidRangeDetails::NAME_MAX.' characters or fewer.');
        }

        return $name;
    }

    /**
     * Refuses a name another active range already has, whatever its case.
     *
     * @throws InvalidRangeDetails
     */
    protected function refuseTakenName(string $name, ?string $exceptId = null): void
    {
        $taken = ProductRange::query()
            ->where('status', ProductRangeStatus::Active->value)
            ->whereRaw('lower(name) = lower(?)', [$name])
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();

        if ($taken) {
            throw InvalidRangeDetails::nameTaken($name);
        }
    }

    /**
     * @throws InvalidRangeDetails
     */
    protected function compliance(string $compliance): RangeCompliance
    {
        return RangeCompliance::tryFrom($compliance)
            ?? throw new InvalidRangeDetails('compliance', 'Choose what its Products need: None, NCC type approval or SONCAP.');
    }

    /**
     * @throws InvalidRangeDetails
     */
    protected function reason(?string $reason): string
    {
        $reason = trim((string) $reason);

        if ($reason === '') {
            throw InvalidRangeDetails::noReason();
        }

        return $reason;
    }

    /** Saves $range and audits its field differences together with the reason. */
    protected function saveWithReason(Actor $actor, string $action, ProductRange $range, string $reason): void
    {
        $changes = AuditChanges::pending($range, app(PersonalFields::class));
        $range->save();
        $this->audit($actor, $action, $range, [...$changes, 'reason' => $reason]);
    }

    /**
     * Runs $work; a second active range given the same name at the same
     * moment is refused by the database's unique index, in the same words.
     * Any other unique violation is not about the name, and is rethrown.
     *
     * @template TResult
     *
     * @param  string  $name  the name as typed; the refusal words it as saved
     * @param  Closure(): TResult  $work
     * @return TResult
     */
    protected function guardingName(string $name, Closure $work): mixed
    {
        try {
            return $work();
        } catch (UniqueConstraintViolationException $e) {
            // By the index's name only, never by the words around it.
            if ($e->index === self::NAME_INDEX) {
                throw InvalidRangeDetails::nameTaken(self::cleanName($name));
            }

            throw $e;
        }
    }
}
