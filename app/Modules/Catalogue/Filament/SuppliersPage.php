<?php

namespace App\Modules\Catalogue\Filament;

use App\Modules\Catalogue\Actions\AddSupplier;
use App\Modules\Catalogue\Actions\SetChecklistItemRequired;
use App\Modules\Catalogue\Capabilities\CatalogueCapabilities;
use App\Modules\Catalogue\Queries\ChecklistSetting;
use App\Modules\Catalogue\Queries\SupplierRecord;
use App\Modules\Catalogue\Queries\Suppliers;
use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Shared\Filament\AuditHistory;
use App\Modules\Shared\Filament\ConsoleRail;
use App\Modules\Shared\Filament\GuardedByCapability;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Pages\Page;
use Filament\Schemas\Components\Text;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * The Suppliers list, in the rail's Catalogue group (FR-14, FR-81): who the
 * goods come from, whether each is approved, its Product count and how far
 * its onboarding checklist has got, filtered by the chips All, Needs
 * attention, Approved and Not approved.
 *
 * Operations and Admins add a Supplier ("Add a supplier…"); only an Admin
 * changes the checklist ("Onboarding settings…"). Finance and Operations
 * see the line "Only an Admin approves Suppliers or changes the checklist."
 * in place of the settings button; nothing is greyed out. Every Action
 * refuses an out-of-role member on the server whatever the screen shows.
 */
#[NeedsCapability(CatalogueCapabilities::VIEW_SUPPLIERS)]
final class SuppliersPage extends Page
{
    use GuardedByCapability;
    use ManagesSuppliers;

    public const ADMIN_ONLY = 'Only an Admin approves Suppliers or changes the checklist.';

    public const NOT_FOUND = 'That Supplier was not found. Reload the Suppliers page and try again.';

    protected static ?string $slug = 'suppliers';

    protected static ?string $title = 'Suppliers';

    protected static ?string $navigationLabel = 'Suppliers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = ConsoleRail::CATALOGUE;

    protected static ?int $navigationSort = 20;

    protected string $view = 'catalogue::suppliers';

    /**
     * The settings as the open dialog showed them. A change is what the Admin
     * changed from these, so an item another Admin changed meanwhile is
     * refused beside it rather than silently skipped.
     *
     * @var array<string, bool>
     */
    #[Locked]
    public array $settingsShown = [];

    /** The filter chip in use. */
    #[Url(as: 'show')]
    public string $chip = Suppliers::ALL;

    /**
     * The rail marks Suppliers as the current place on a Supplier's own page too.
     *
     * @return array<string>
     */
    public static function getNavigationItemActiveRoutePattern(): array
    {
        return [self::getRouteName(), SupplierPage::getRouteName()];
    }

    public function getSubheading(): string
    {
        $counts = app(Suppliers::class)->chipCounts();

        return "{$counts[Suppliers::APPROVED]} approved, {$counts[Suppliers::NOT_APPROVED]} not approved · {$counts[Suppliers::NEEDS_ATTENTION]} need attention";
    }

    /**
     * @return list<SupplierRecord>
     */
    public function getSuppliers(): array
    {
        return app(Suppliers::class)->list($this->currentChip());
    }

    /**
     * The chips for the shared filter-chips component, each with its count.
     *
     * @return list<array{key: string, label: string, count: int}>
     */
    public function getChips(): array
    {
        $counts = app(Suppliers::class)->chipCounts();
        $chips = [];

        foreach (Suppliers::CHIPS as $key => $label) {
            $chips[] = ['key' => $key, 'label' => $label, 'count' => $counts[$key]];
        }

        return $chips;
    }

    public function currentChip(): string
    {
        return array_key_exists($this->chip, Suppliers::CHIPS) ? $this->chip : Suppliers::ALL;
    }

    /** What the table says when the chip lets no Supplier through. */
    public function emptyWords(): string
    {
        return match ($this->currentChip()) {
            Suppliers::NEEDS_ATTENTION => 'No Supplier needs attention. Choose All to see every Supplier.',
            Suppliers::APPROVED => 'No Supplier is approved yet. Choose All to see every Supplier.',
            Suppliers::NOT_APPROVED => 'Every Supplier is approved. Choose All to see them.',
            default => 'No Supplier yet.'.($this->canRecord() ? ' Add one with "Add a supplier…".' : ''),
        };
    }

    public function supplierUrl(string $id): string
    {
        return SupplierPage::getUrl(['supplier' => $id]);
    }

    protected function getHeaderActions(): array
    {
        return [$this->settingsAction(), $this->addAction()];
    }

    public function addAction(): Action
    {
        return Action::make('add')
            ->label('Add a supplier…')
            ->visible(fn (): bool => $this->canRecord())
            ->modalHeading('Add a supplier')
            ->modalDescription($this->willHappen([
                'Only the legal name is needed now. Anything left blank shows "Not stated" until someone adds it.',
                'The Supplier is saved Not approved. An Admin approves it against the onboarding checklist.',
                'It is recorded in the audit trail with your name.',
            ]))
            ->modalWidth(Width::TwoExtraLarge)
            ->schema(fn (): array => $this->supplierFields())
            ->modalSubmitActionLabel('Add the supplier')
            ->action(function (array $data): void {
                $this->attempt(function () use ($data): void {
                    $supplier = app(AddSupplier::class)->handle($this->actor(), self::detailsFrom($data));
                    $this->say("Added {$supplier->legalName}, Not approved. Its checklist is {$supplier->progressWords()}.");
                });
            });
    }

    public function settingsAction(): Action
    {
        return Action::make('settings')
            ->label('Onboarding settings…')
            ->color('gray')
            ->visible(fn (): bool => $this->canConfigure())
            ->modalHeading('Supplier onboarding settings')
            ->modalDescription($this->willHappen([
                'Choose which items "Approve the supplier" needs. The three agreement terms are always required.',
                'A change applies to approvals from now on. No Supplier already approved loses its approval.',
            ]))
            ->modalWidth(Width::Large)
            ->fillForm(fn (): array => $this->settingsShown = $this->settingsValues())
            ->schema(fn (): array => [
                ...array_map(
                    fn (ChecklistSetting $setting): Checkbox|Text => $setting->alwaysRequired
                        ? Text::make("{$setting->label}: always required.")
                        : Checkbox::make('required_'.$setting->key)->label($setting->label.' is required'),
                    $this->settings(),
                ),
                Text::make(SetChecklistItemRequired::TERMS_ALWAYS_REQUIRED.' They cannot be made optional.'),
                $this->reasonField('every Admin who opens these settings'),
            ])
            ->modalSubmitActionLabel('Save the settings')
            ->action(function (array $data): void {
                $this->attempt(fn () => $this->saveSettings($data));
            });
    }

    /** The checklist settings' History, read-only. */
    public function settingsHistoryAction(): Action
    {
        return Action::make('settingsHistory')
            ->label('History of the settings')
            ->color('gray')
            ->modal()
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalHeading('History of the onboarding settings')
            ->modalDescription('Who changed which items are required, when and why, newest first, from the audit trail. Read-only.')
            ->modalContent(fn (): View => app(AuditHistory::class)->view(
                array_map(fn (ChecklistSetting $setting): array => $setting->auditSubject(), $this->settings()),
                'The settings have not been changed since every item started as required.',
            ))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }

    /**
     * @return list<ChecklistSetting>
     */
    public function settings(): array
    {
        return app(Suppliers::class)->checklistSettings();
    }

    /**
     * @return array<string, bool>
     */
    private function settingsValues(): array
    {
        $values = [];

        foreach ($this->settings() as $setting) {
            $values['required_'.$setting->key] = $setting->required;
        }

        return $values;
    }

    /**
     * Saves each item whose setting changed, each audited with the reason.
     *
     * @param  array<string, mixed>  $data
     */
    private function saveSettings(array $data): void
    {
        $shown = $this->settingsShown === [] ? $this->settingsValues() : $this->settingsShown;
        $changed = array_values(array_filter(
            $this->settings(),
            fn (ChecklistSetting $setting): bool => ! $setting->alwaysRequired
                && array_key_exists('required_'.$setting->key, $data)
                && (bool) $data['required_'.$setting->key] !== ($shown['required_'.$setting->key] ?? $setting->required),
        ));

        if ($changed === []) {
            throw ValidationException::withMessages(['mountedActions.0.data.reason' => 'Nothing was changed: every item is as it was. Tick or clear an item, or cancel.']);
        }

        $words = [];

        foreach ($changed as $setting) {
            $saved = app(SetChecklistItemRequired::class)->handle($this->actor(), $setting->key, (bool) $data['required_'.$setting->key], (string) ($data['reason'] ?? ''));
            $words[] = "{$saved->label} is now ".strtolower($saved->words());
        }

        $this->say(implode('. ', $words).'. Approvals from now on follow the new settings.');
    }
}
