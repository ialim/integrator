<?php

namespace App\Modules\Catalogue\Locks;

use App\Modules\Catalogue\Models\OnboardingChecklistItem;
use App\Modules\Shared\Locks\LockAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Locks the onboarding checklist's rows (the `onboarding_setting` aggregate
 * root) until the Action's transaction ends. An approval locks every item,
 * after the Supplier, so it reads the checklist as it stands: an Admin's
 * change to an item waits for the approval, or the approval for the change.
 */
final class LockOnboardingSetting extends LockAction
{
    public const ROOT = 'onboarding_setting';

    public static function root(): string
    {
        return self::ROOT;
    }

    protected function table(): string
    {
        return (new OnboardingChecklistItem)->getTable();
    }

    /**
     * @throws ModelNotFoundException when an item does not exist
     */
    public function handle(string ...$ids): void
    {
        $this->lockRows(array_values($ids));
    }

    /** Locks every checklist item. */
    public function all(): void
    {
        $ids = OnboardingChecklistItem::query()->orderBy('id')->pluck('id')->map(fn (mixed $id): string => (string) $id)->values()->all();
        $this->lockRows($ids);
    }
}
