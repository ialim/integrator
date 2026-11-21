<?php

namespace App\Modules\Catalogue\Actions;

use App\Modules\Catalogue\Capabilities\CatalogueCapabilities;
use App\Modules\Catalogue\Locks\LockOnboardingSetting;
use App\Modules\Catalogue\Models\OnboardingChecklistItem;
use App\Modules\Catalogue\Queries\ChecklistSetting;
use App\Modules\Catalogue\Queries\SupplierChecklist;
use App\Modules\Catalogue\Queries\Suppliers;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Audit\AuditChanges;
use App\Modules\Shared\Audit\PersonalFields;
use App\Modules\Shared\Capabilities\NeedsCapability;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * An Admin makes an onboarding checklist item required or optional, with a
 * required reason (FR-81). The three agreement terms are always required:
 * making one optional is refused here, and by a database CHECK. The change
 * applies to approvals from then on and never un-approves a Supplier.
 * Audited with the reason.
 */
#[NeedsCapability(CatalogueCapabilities::CONFIGURE_ONBOARDING)]
final class SetChecklistItemRequired extends SupplierAction
{
    public const AUDIT_ACTION = 'catalogue.checklist_item_set';

    public const TERMS_ALWAYS_REQUIRED = 'The three agreement terms are always required.';

    /**
     * @throws InvalidSupplierDetails when the item is a term made optional, is already so, or the reason is missing
     * @throws AuthorizationException when $actor does not hold the capability
     * @throws ModelNotFoundException when the item does not exist
     */
    public function handle(Model $actor, string $key, bool $required, string $reason): ChecklistSetting
    {
        $this->transaction(function () use ($actor, $key, $required, $reason): void {
            $current = $this->lockActor($actor);
            $item = OnboardingChecklistItem::query()->where('key', $key)->firstOrFail();
            app(LockOnboardingSetting::class)->handle($item->id);
            $item->refresh();

            if (! $required && in_array($item->key, OnboardingChecklistItem::ALWAYS_REQUIRED, true)) {
                throw new InvalidSupplierDetails('required_'.$item->key, self::TERMS_ALWAYS_REQUIRED);
            }

            $reason = $this->reason($reason);

            if ($item->required === $required) {
                $label = SupplierChecklist::label($item->key);

                throw new InvalidSupplierDetails('required_'.$item->key, "{$label} is already ".($required ? 'required' : 'optional').'. Nothing was changed.');
            }

            $item->forceFill(['required' => $required]);
            $changes = AuditChanges::pending($item, app(PersonalFields::class));
            $item->save();
            $this->audit(Actor::staff((string) $current->getKey()), self::AUDIT_ACTION, $item, [...$changes, 'item' => $item->key, 'reason' => $reason]);
        });

        return app(Suppliers::class)->checklistSetting($key) ?? throw new ModelNotFoundException;
    }
}
