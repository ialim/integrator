<?php

namespace App\Modules\Pricing\Filament;

use App\Modules\Catalogue\Actions\CreateProductRange;
use App\Modules\Catalogue\Actions\InvalidRangeDetails;
use App\Modules\Catalogue\Actions\RenameProductRange;
use App\Modules\Catalogue\Actions\RetireProductRange;
use App\Modules\Catalogue\Actions\SetRangeCompliance;
use App\Modules\Catalogue\Queries\ProductRanges;
use App\Modules\Pricing\Actions\InvalidPricingInput;
use App\Modules\Pricing\Actions\SetMarginFloor;
use App\Modules\Pricing\Actions\SetRangeMarkup;
use App\Modules\Pricing\Queries\PricingRules;
use App\Modules\Shared\Filament\AuditHistory;
use App\Modules\Shared\StatusTables\TransitionRefused;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

/**
 * The Pricing and rules page's dialogs. Each change is one Action call with
 * a required reason; a refused value is shown beside its field, a refused
 * change above the table in words. Every change button is visible only to a
 * member who may press it; the Actions refuse anyone else on the server.
 *
 * @mixin PricingAndRules
 */
trait ManagesPricingRules
{
    protected function getHeaderActions(): array
    {
        return [$this->createAction()];
    }

    public function createAction(): Action
    {
        return Action::make('create')
            ->label('Add a Product Range')
            ->visible(fn (): bool => $this->canManageRanges())
            ->modalHeading('Add a Product Range')
            ->modalDescription($this->willHappen([
                'The range is Active and starts with a Platform Markup of '.SetRangeMarkup::STARTING_MARKUP.'%. Change it afterwards if it needs another.',
                'It is recorded in the audit trail with your name.',
            ]))
            ->modalWidth(Width::Large)
            ->schema([
                TextInput::make('name')->label('Name')->required()->maxLength(InvalidRangeDetails::NAME_MAX)->autocomplete('off')
                    ->validationMessages(['required' => 'Enter a name for the Product Range.']),
                $this->complianceField(),
            ])
            ->modalSubmitActionLabel('Add the range')
            ->action(function (array $data): void {
                $this->attempt(function () use ($data): void {
                    $range = app(CreateProductRange::class)->handle($this->actor(), (string) ($data['name'] ?? ''), (string) ($data['compliance'] ?? ''));
                    $this->say("Added {$range->name}, Active, at a ".SetRangeMarkup::STARTING_MARKUP.'% markup.');
                });
            });
    }

    public function markupAction(): Action
    {
        return $this->rangeChange('markup', 'Change markup')
            ->visible(fn (): bool => $this->canManageRules())
            ->modalHeading(fn (array $arguments): string => 'Change the markup for '.$this->nameOf($arguments).'?')
            ->modalDescription(fn (array $arguments): Htmlable => $this->willHappen([
                'Its markup is '.$this->markupWords($arguments).' now. The new one applies to its Products at once.',
                'Orders already made are not changed.',
            ]))
            ->fillForm(fn (array $arguments): array => ['markup' => app(PricingRules::class)->markupFor($this->rangeId($arguments))?->plain()])
            ->schema([
                TextInput::make('markup')->label('New markup, as a percentage over Supplier Cost')->suffix('%')->inputMode('decimal')->required()->autocomplete('off')
                    ->validationMessages(['required' => 'Enter the markup as a percentage, like 25 or 27.5.']),
                $this->reasonField(),
            ])
            ->modalSubmitActionLabel('Save the markup')
            ->action(function (array $data, array $arguments): void {
                $this->attempt(function () use ($data, $arguments): void {
                    $markup = app(SetRangeMarkup::class)->handle($this->actor(), $this->rangeId($arguments), (string) ($data['markup'] ?? ''), (string) ($data['reason'] ?? ''));
                    $this->say("The markup for {$this->nameOf($arguments)} is now {$markup->words()}.");
                });
            });
    }

    public function renameAction(): Action
    {
        return $this->rangeChange('rename', 'Rename')
            ->visible(fn (): bool => $this->canManageRanges())
            ->modalHeading(fn (array $arguments): string => 'Rename '.$this->nameOf($arguments).'?')
            ->fillForm(fn (array $arguments): array => ['name' => $this->rangeOf($arguments)?->name])
            ->schema([
                TextInput::make('name')->label('New name')->required()->maxLength(InvalidRangeDetails::NAME_MAX)->autocomplete('off')
                    ->validationMessages(['required' => 'Enter a name for the Product Range.']),
                $this->reasonField(),
            ])
            ->modalSubmitActionLabel('Save the name')
            ->action(function (array $data, array $arguments): void {
                $this->attempt(function () use ($data, $arguments): void {
                    $was = $this->nameOf($arguments);
                    $range = app(RenameProductRange::class)->handle($this->actor(), $this->rangeId($arguments), (string) ($data['name'] ?? ''), (string) ($data['reason'] ?? ''));
                    $this->say("Renamed {$was} to {$range->name}.");
                });
            });
    }

    public function complianceAction(): Action
    {
        return $this->rangeChange('compliance', 'Change compliance')
            ->visible(fn (): bool => $this->canManageRanges())
            ->modalHeading(fn (array $arguments): string => 'Change what '.$this->nameOf($arguments).' needs?')
            ->fillForm(fn (array $arguments): array => ['compliance' => $this->rangeOf($arguments)?->compliance])
            ->schema([$this->complianceField(), $this->reasonField()])
            ->modalSubmitActionLabel('Save the compliance')
            ->action(function (array $data, array $arguments): void {
                $this->attempt(function () use ($data, $arguments): void {
                    $range = app(SetRangeCompliance::class)->handle($this->actor(), $this->rangeId($arguments), (string) ($data['compliance'] ?? ''), (string) ($data['reason'] ?? ''));
                    $this->say("{$range->name} now needs: {$range->complianceLabel}.");
                });
            });
    }

    public function retireAction(): Action
    {
        return $this->rangeChange('retire', 'Retire')
            ->visible(fn (): bool => $this->canManageRanges())
            ->modalHeading(fn (array $arguments): string => 'Retire '.$this->nameOf($arguments).'?')
            ->modalDescription(fn (array $arguments): Htmlable => $this->willHappen([
                'It stays in the table, labelled Retired, with its History.',
                'It takes no new Products and no further edits.',
                'This cannot be undone: a retired range is never made active again. Add a new range if it is needed.',
            ]))
            ->schema([$this->reasonField()])
            ->modalSubmitAction(fn (Action $action, array $arguments) => $action->color('danger')->label('Retire '.$this->nameOf($arguments)))
            ->modalCancelActionLabel('Keep the range')
            ->action(function (array $data, array $arguments): void {
                $this->attempt(function () use ($data, $arguments): void {
                    $range = app(RetireProductRange::class)->handle($this->actor(), $this->rangeId($arguments), (string) ($data['reason'] ?? ''));
                    $this->say("Retired {$range->name}. It stays in the table with its History.");
                });
            });
    }

    public function floorAction(): Action
    {
        return Action::make('floor')
            ->label('Change the floor')
            ->color('gray')
            ->visible(fn (): bool => $this->canManageRules())
            ->modalWidth(Width::Large)
            ->modalHeading('Change the margin floor?')
            ->modalDescription(fn (): Htmlable => $this->willHappen([
                'The floor is '.$this->getFloor()->words().' over Supplier Cost now. No price before VAT may fall below it.',
                'Orders already made are not changed.',
            ]))
            ->fillForm(fn (): array => ['floor' => $this->getFloor()->plain()])
            ->schema([
                TextInput::make('floor')->label('New margin floor, as a percentage over Supplier Cost')->suffix('%')->inputMode('decimal')->required()->autocomplete('off')
                    ->validationMessages(['required' => 'Enter the floor as a percentage, like 8 or 8.5.']),
                $this->reasonField(),
            ])
            ->modalSubmitActionLabel('Save the floor')
            ->action(function (array $data): void {
                $this->attempt(function () use ($data): void {
                    $floor = app(SetMarginFloor::class)->handle($this->actor(), (string) ($data['floor'] ?? ''), (string) ($data['reason'] ?? ''));
                    $this->say("The margin floor is now {$floor->words()} over Supplier Cost.");
                });
            });
    }

    /** A range's History, read-only: the range and its markup together, newest first. */
    public function historyAction(): Action
    {
        return $this->history('history')
            ->modalHeading(fn (array $arguments): string => 'History of '.$this->nameOf($arguments))
            ->modalContent(function (array $arguments): View {
                $range = $this->rangeOf($arguments) ?? abort(404, self::NOT_FOUND);
                $markup = app(PricingRules::class)->markupFor($range->id);
                $subjects = $markup === null ? [$range->auditSubject()] : [$range->auditSubject(), $markup->auditSubject()];

                return app(AuditHistory::class)->view($subjects, 'Nothing has been recorded for this range yet.');
            });
    }

    /** The margin floor's History, read-only. */
    public function floorHistoryAction(): Action
    {
        return $this->history('floorHistory')
            ->label('History of the floor')
            ->modalHeading('History of the margin floor')
            ->modalContent(fn (): View => app(AuditHistory::class)->view([$this->getFloor()->auditSubject()], 'The floor has not been changed since it was set at 8%.'));
    }

    private function history(string $name): Action
    {
        return Action::make($name)
            ->label('History')
            ->color('gray')
            ->modal()
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalDescription('Who changed what and when, newest first, with the reason given, from the audit trail. Read-only: it cannot be edited.')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }

    private function rangeChange(string $name, string $label): Action
    {
        return Action::make($name)->label($label)->color('gray')->modal()->modalWidth(Width::Large)->modalCancelActionLabel('Cancel');
    }

    private function complianceField(): Select
    {
        return Select::make('compliance')
            ->label('What its Products need before sale')
            ->options(app(ProductRanges::class)->complianceOptions())
            ->default('none')
            ->required()
            ->native()
            ->validationMessages(['required' => 'Choose what its Products need: None, NCC type approval or SONCAP.']);
    }

    private function reasonField(): Textarea
    {
        return Textarea::make('reason')
            ->label('Reason')
            ->helperText('Required. Kept on record with the change, and shown in History to every staff member who opens this page.')
            ->rows(3)
            ->required()
            ->validationMessages(['required' => 'Give a reason for the change. It is kept on record with the change.']);
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function markupWords(array $arguments): string
    {
        $id = self::rangeIdFrom($arguments);

        return ($id === null ? null : app(PricingRules::class)->markupFor($id)?->words()) ?? 'not set';
    }

    /**
     * Runs a change. A refused value is shown beside its field; a refused
     * change (a retired range, Products still in it, a role that cannot) in
     * words above the table, never as an error page.
     */
    private function attempt(Closure $change): void
    {
        try {
            $change();
        } catch (InvalidRangeDetails|InvalidPricingInput $e) {
            throw ValidationException::withMessages(['mountedActions.0.data.'.$e->field => $e->getMessage()]);
        } catch (TransitionRefused $e) {
            $this->say($e->reason ?? $e->getMessage(), refusal: true);
        } catch (AuthorizationException $e) {
            $this->say($e->getMessage(), refusal: true);
        } catch (ModelNotFoundException) {
            abort(404, self::NOT_FOUND);
        }
    }

    /**
     * @param  list<string>  $lines
     */
    private function willHappen(array $lines): Htmlable
    {
        $items = implode('', array_map(fn (string $line) => '<li>'.e($line).'</li>', $lines));

        return new HtmlString('<p>What will happen:</p><ul class="console-dialog-list">'.$items.'</ul>');
    }
}
