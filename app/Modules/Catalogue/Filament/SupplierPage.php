<?php

namespace App\Modules\Catalogue\Filament;

use App\Modules\Catalogue\Actions\ApproveSupplier;
use App\Modules\Catalogue\Actions\ApproveSupplierWithReason;
use App\Modules\Catalogue\Actions\EditSupplier;
use App\Modules\Catalogue\Capabilities\CatalogueCapabilities;
use App\Modules\Catalogue\Queries\SupplierRecord;
use App\Modules\Catalogue\Queries\Suppliers;
use App\Modules\Shared\Audit\ActorNames;
use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Filament\AuditHistory;
use App\Modules\Shared\Filament\GuardedByCapability;
use App\Modules\Shared\Formats\Format;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;

/**
 * One Supplier's page (FR-14, FR-81): Business details, Contacts and the
 * Agreement terms, each detail "Not stated" when missing; the Onboarding
 * checklist, each item Clear or Needs action in words; and History.
 *
 * Operations and Admins edit the details, with a reason. Only an Admin
 * approves: "Approve the supplier" when every required item is Clear, and
 * otherwise the same button dashed and off with its reason beside it, plus
 * "Approve with a reason…". Finance and Operations see the line "Only an
 * Admin approves Suppliers or changes the checklist." instead. Reached from
 * the Suppliers list; not in the rail of its own.
 */
#[NeedsCapability(CatalogueCapabilities::VIEW_SUPPLIERS)]
final class SupplierPage extends Page
{
    use GuardedByCapability;
    use ManagesSuppliers;

    public const NOT_FOUND = SuppliersPage::NOT_FOUND;

    protected static ?string $slug = 'suppliers/{supplier}';

    protected static ?string $navigationLabel = 'Suppliers';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'catalogue::supplier';

    /** The Supplier's id, from the address. */
    #[Locked]
    public string $supplier = '';

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'suppliers.record';
    }

    public function mount(string $supplier): void
    {
        abort_unless(Str::isUuid($supplier), 404, self::NOT_FOUND);
        $this->supplier = Str::lower($supplier);

        // A member without the capability sees the refusal, not whether the Supplier exists.
        if (self::canAccess()) {
            abort_if(app(Suppliers::class)->find($this->supplier) === null, 404, self::NOT_FOUND);
        }
    }

    public function getTitle(): string
    {
        return $this->refusal === null ? ($this->record()->legalName ?? 'Supplier') : 'Supplier';
    }

    public function getSubheading(): string
    {
        $supplier = $this->record();

        if ($supplier === null) {
            return '';
        }

        $products = $supplier->productCount === 1 ? '1 Product' : "{$supplier->productCount} Products";

        return "{$this->approvalWords($supplier)} · {$products}";
    }

    /** "Not approved", "Approved by Ada Founder on Thu 19 Nov, 9:00 am", or "Approved with a reason by …". */
    public function approvalWords(SupplierRecord $supplier): string
    {
        if (! $supplier->isApproved || $supplier->approvedBy === null || $supplier->approvedAt === null) {
            return $supplier->statusLabel;
        }

        $who = app(ActorNames::class)->namesOf([$supplier->approvedBy])[$supplier->approvedBy] ?? 'a staff member';
        $when = Format::dateTime($supplier->approvedAt, app(Clock::class)->now());
        $how = $supplier->approvedWithReason() ? 'Approved with a reason' : 'Approved';

        return "{$how} by {$who} on {$when}";
    }

    public function record(): ?SupplierRecord
    {
        return $this->supplier === '' ? null : app(Suppliers::class)->find($this->supplier);
    }

    /**
     * The checklist for the shared checklist component.
     *
     * @return list<array{label: string, clear: bool, words: string, optional: bool}>
     */
    public function checklistItems(SupplierRecord $supplier): array
    {
        $items = [];

        foreach ($supplier->checklist as $item) {
            $items[] = ['label' => $item->label, 'clear' => $item->clear, 'words' => $item->words(), 'optional' => ! $item->required];
        }

        return $items;
    }

    protected function getHeaderActions(): array
    {
        return [$this->historyAction(), $this->editAction()];
    }

    public function editAction(): Action
    {
        return Action::make('edit')
            ->label('Edit details…')
            ->color('gray')
            ->visible(fn (): bool => $this->canRecord())
            ->modalHeading(fn (): string => 'Edit '.($this->record()->legalName ?? 'the Supplier'))
            ->modalDescription($this->willHappen([
                'Only the details you change are saved, with your reason, in the audit trail.',
                'An approved Supplier stays approved. Its checklist shows any item that now needs action.',
            ]))
            ->modalWidth(Width::TwoExtraLarge)
            ->fillForm(fn (): array => ($supplier = $this->record()) === null ? [] : self::formValues($supplier))
            ->schema(fn (): array => [...$this->supplierFields(), $this->reasonField()])
            ->modalSubmitActionLabel('Save the details')
            ->modalCancelActionLabel('Cancel')
            ->action(function (array $data): void {
                $this->attempt(function () use ($data): void {
                    $supplier = app(EditSupplier::class)->handle($this->actor(), $this->supplier, self::detailsFrom($data), (string) ($data['reason'] ?? ''));
                    $this->say("Saved the details of {$supplier->legalName}. Its checklist is {$supplier->progressWords()}.");
                });
            });
    }

    public function approveAction(): Action
    {
        return Action::make('approve')
            ->label('Approve the supplier')
            ->visible(fn (): bool => $this->canApprove() && ($this->record()?->isApproved === false))
            ->requiresConfirmation()
            ->modalHeading(fn (): string => 'Approve '.($this->record()->legalName ?? 'the Supplier').'?')
            ->modalDescription($this->willHappen([
                'Its Products can then be bought from it.',
                'Your name and the time are recorded with the approval.',
            ]))
            ->modalSubmitActionLabel('Approve the supplier')
            ->modalCancelActionLabel('Cancel')
            ->action(function (): void {
                $this->attempt(function (): void {
                    $supplier = app(ApproveSupplier::class)->handle($this->actor(), $this->supplier);
                    $this->say("Approved {$supplier->legalName}.");
                });
            });
    }

    public function approveWithReasonAction(): Action
    {
        return Action::make('approveWithReason')
            ->label('Approve with a reason…')
            ->color('gray')
            ->visible(fn (): bool => $this->canApprove() && ($supplier = $this->record()) !== null && ! $supplier->isApproved && ! $supplier->isClear())
            ->modalHeading(fn (): string => 'Approve '.($this->record()->legalName ?? 'the Supplier').' with a reason?')
            ->modalDescription(fn (): Htmlable => $this->willHappen([
                ($this->record()?->offBecause() ?? '').' Approving now overrides that.',
                'Your reason is stored and shown on the Supplier, with your name and the time.',
            ]))
            ->modalWidth(Width::Large)
            ->schema([$this->reasonField('every staff member who opens this Supplier, beside its approval')])
            ->modalSubmitActionLabel('Approve with this reason')
            ->modalCancelActionLabel('Cancel')
            ->action(function (array $data): void {
                $this->attempt(function () use ($data): void {
                    $supplier = app(ApproveSupplierWithReason::class)->handle($this->actor(), $this->supplier, (string) ($data['reason'] ?? ''));
                    $this->say("Approved {$supplier->legalName} with a reason.");
                });
            });
    }

    /** The Supplier's History, read-only. */
    public function historyAction(): Action
    {
        return Action::make('history')
            ->label('History')
            ->color('gray')
            ->modal()
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalHeading(fn (): string => 'History of '.($this->record()->legalName ?? 'the Supplier'))
            ->modalDescription('Who changed what and when, newest first, with the reason given, from the audit trail. Contact details read "changed". Read-only: it cannot be edited.')
            ->modalContent(function (): View {
                $supplier = $this->record() ?? abort(404, self::NOT_FOUND);

                return app(AuditHistory::class)->view([$supplier->auditSubject()], 'Nothing has been recorded for this Supplier yet.');
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }
}
