<?php

namespace App\Modules\Catalogue\Filament;

use App\Modules\Catalogue\Actions\AddSupplier;
use App\Modules\Catalogue\Actions\ApproveSupplier;
use App\Modules\Catalogue\Actions\InvalidSupplierDetails;
use App\Modules\Catalogue\Actions\SetChecklistItemRequired;
use App\Modules\Catalogue\Actions\SupplierAction;
use App\Modules\Catalogue\Queries\SupplierRecord;
use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Shared\Filament\ConsolePageGuard;
use App\Modules\Shared\StatusTables\TransitionRefused;
use Closure;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use ReflectionClass;

/**
 * What the Suppliers list and a Supplier's page share: who may press which
 * button (each Action says which capability it needs), the Supplier form's
 * fields, the required reason, and running a change so that a refused
 * value is shown beside its field and a refused change above the page in
 * words, never as an error page. The Actions refuse anyone out of role on
 * the server, whatever the screen shows.
 */
trait ManagesSuppliers
{
    /** What the last change did, or why it was refused, in words. */
    #[Locked]
    public ?string $notice = null;

    #[Locked]
    public bool $noticeIsRefusal = false;

    /** Whether the signed-in member may add Suppliers and edit their details. */
    public function canRecord(): bool
    {
        return self::mayCall(AddSupplier::class);
    }

    /** Whether the signed-in member may approve Suppliers. */
    public function canApprove(): bool
    {
        return self::mayCall(ApproveSupplier::class);
    }

    /** Whether the signed-in member may change the onboarding checklist. */
    public function canConfigure(): bool
    {
        return self::mayCall(SetChecklistItemRequired::class);
    }

    /**
     * Whether the signed-in member holds the capability $action declares.
     *
     * @param  class-string  $action
     */
    private static function mayCall(string $action): bool
    {
        $attributes = (new ReflectionClass($action))->getAttributes(NeedsCapability::class);

        return $attributes !== [] && app(ConsolePageGuard::class)->allows($attributes[0]->newInstance()->capability);
    }

    private function actor(): Model
    {
        $member = Filament::auth()->user();
        abort_unless($member instanceof Model, 403, self::refusalWords());

        return $member;
    }

    /**
     * The Supplier form: Business details, Contacts and Agreement terms. Only
     * the legal name is required.
     *
     * @return list<Fieldset>
     */
    private function supplierFields(): array
    {
        $line = fn (string $name, string $label): TextInput => TextInput::make($name)->label($label)
            ->maxLength(SupplierAction::DETAILS[$name][2])->autocomplete('off');

        return [
            Fieldset::make('Business details')->columns(1)->schema([
                $line('legal_name', 'Legal name')->required()
                    ->validationMessages(['required' => 'Enter the Supplier\'s legal name.']),
                $line('trade_name', 'Trade name (optional)'),
                $line('cac_rc_number', 'CAC RC number'),
                $line('tin', 'TIN'),
                $line('address', 'Address'),
                Select::make('invoice_currency')->label('Currency it invoices in')
                    ->options(array_combine(SupplierAction::CURRENCIES, SupplierAction::CURRENCIES))
                    ->placeholder('Not stated')
                    ->native(),
                $line('payment_terms', 'Payment terms')->helperText('For example, 30 days from invoice.'),
            ]),
            Fieldset::make('Sales contact')->columns(1)->schema([
                $line('sales_contact_name', 'Sales contact\'s name'),
                $line('sales_contact_phone', 'Sales contact\'s phone')->type('tel'),
                $line('sales_contact_email', 'Sales contact\'s email')->type('email'),
            ]),
            Fieldset::make('Returns and RMA contact')->columns(1)->schema([
                $line('returns_contact_name', 'Returns and RMA contact\'s name'),
                $line('returns_contact_phone', 'Returns and RMA contact\'s phone')->type('tel'),
                $line('returns_contact_email', 'Returns and RMA contact\'s email')->type('email'),
            ]),
            Fieldset::make('Agreement terms')->columns(1)->schema([
                Textarea::make('returns_terms')->label('Returns terms')->rows(3)->maxLength(2000),
                Textarea::make('doa_terms')->label('Dead-on-arrival terms')->rows(3)->maxLength(2000),
                TextInput::make('doa_window_days')->label('Dead-on-arrival window, in days')->inputMode('numeric')->autocomplete('off')
                    ->helperText('A whole number, 1 or more. A window shorter than 30 days shortens the one businesses get for these Products.'),
                Textarea::make('counterfeit_terms')->label('Counterfeit-chargeback terms')->rows(3)->maxLength(2000),
            ]),
        ];
    }

    /**
     * The form's values, as the Action takes them: every detail, each blank one missing.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function detailsFrom(array $data): array
    {
        $details = [];

        foreach (array_keys(SupplierAction::DETAILS) as $field) {
            $value = $data[$field] ?? null;
            $details[$field] = is_int($value) ? (string) $value : $value;
        }

        return $details;
    }

    /**
     * A Supplier's details as the form shows them.
     *
     * @return array<string, mixed>
     */
    private static function formValues(SupplierRecord $supplier): array
    {
        $values = [];

        foreach (array_keys(SupplierAction::DETAILS) as $field) {
            $value = $supplier->detail($field);
            $values[$field] = $value === null ? null : (string) $value;
        }

        return $values;
    }

    private function reasonField(string $who = 'every staff member who opens this Supplier'): Textarea
    {
        return Textarea::make('reason')
            ->label('Reason')
            ->helperText("Required. Kept on record with the change, and shown in History to {$who}.")
            ->rows(3)
            ->required()
            ->validationMessages(['required' => 'Give a reason for the change. It is kept on record with the change.']);
    }

    /**
     * Runs a change. A refused value is shown beside its field; a refused
     * change (an approval the checklist holds back, a role that cannot) in
     * words above the page, never as an error page.
     */
    private function attempt(Closure $change): void
    {
        try {
            $change();
        } catch (InvalidSupplierDetails $e) {
            throw ValidationException::withMessages(['mountedActions.0.data.'.$e->field => $e->getMessage()]);
        } catch (TransitionRefused $e) {
            $this->say($e->reason ?? $e->getMessage(), refusal: true);
        } catch (AuthorizationException $e) {
            $this->say($e->getMessage(), refusal: true);
        } catch (ModelNotFoundException) {
            abort(404, self::NOT_FOUND);
        }
    }

    private function say(string $notice, bool $refusal = false): void
    {
        $this->notice = $notice;
        $this->noticeIsRefusal = $refusal;
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
