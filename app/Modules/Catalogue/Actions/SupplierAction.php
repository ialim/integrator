<?php

namespace App\Modules\Catalogue\Actions;

use App\Modules\Catalogue\Locks\LockSupplier;
use App\Modules\Catalogue\Models\Supplier;
use App\Modules\Shared\Actions\Action;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Audit\AuditChanges;
use App\Modules\Shared\Audit\PersonalFields;
use App\Modules\Staff\Locks\LockStaffMember;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use LogicException;

/**
 * What the Supplier Actions share: locking the acting staff member (through
 * Staff's public Lock action) and then the Supplier, in the fixed lock
 * order; the capability check on the actor as read under the lock; the
 * rules for each detail and for a reason; and saving a change with its
 * reason in the audit row. Each concrete Action declares its capability
 * with #[NeedsCapability].
 */
abstract class SupplierAction extends Action
{
    private const LINE = 'line';

    private const TEXT = 'text';

    private const CURRENCY = 'currency';

    private const EMAIL = 'email';

    private const PHONE = 'phone';

    private const DAYS = 'days';

    /** The longest window a form may give, in days: well past any real term. */
    public const DAYS_MAX = 9999;

    /** The currencies a Supplier can invoice in. */
    public const CURRENCIES = ['USD', 'NGN'];

    /**
     * Each detail staff record, in the order the form asks for them: its
     * name in words for a refusal, its kind, and its longest length.
     *
     * @var array<string, array{0: string, 1: string, 2: int}>
     */
    public const DETAILS = [
        'legal_name' => ['legal name', self::LINE, 200],
        'trade_name' => ['trade name', self::LINE, 200],
        'cac_rc_number' => ['CAC RC number', self::LINE, 32],
        'tin' => ['TIN', self::LINE, 32],
        'address' => ['address', self::LINE, 500],
        'invoice_currency' => ['invoicing currency', self::CURRENCY, 3],
        'payment_terms' => ['payment terms', self::LINE, 200],
        'sales_contact_name' => ['sales contact\'s name', self::LINE, 120],
        'sales_contact_phone' => ['sales contact\'s phone', self::PHONE, 32],
        'sales_contact_email' => ['sales contact\'s email', self::EMAIL, 254],
        'returns_contact_name' => ['returns and RMA contact\'s name', self::LINE, 120],
        'returns_contact_phone' => ['returns and RMA contact\'s phone', self::PHONE, 32],
        'returns_contact_email' => ['returns and RMA contact\'s email', self::EMAIL, 254],
        'returns_terms' => ['returns terms', self::TEXT, 2000],
        'doa_terms' => ['dead-on-arrival terms', self::TEXT, 2000],
        'doa_window_days' => ['dead-on-arrival window', self::DAYS, 4],
        'counterfeit_terms' => ['counterfeit-chargeback terms', self::TEXT, 2000],
    ];

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
        $current = $locked[$id] ?? throw new AuthorizationException('Only an active staff member can change Suppliers. Nothing was changed.');
        $this->authorizeCapability($current);

        return $current;
    }

    /**
     * Locks the actor, then the Supplier, and returns both as read under the lock.
     *
     * @return array{0: Model, 1: Supplier}
     *
     * @throws ModelNotFoundException when the Supplier does not exist
     */
    protected function lockActorAndSupplier(Model $actor, string $supplierId): array
    {
        $current = $this->lockActor($actor);
        app(LockSupplier::class)->handle($supplierId);

        return [$current, Supplier::query()->findOrFail($supplierId)];
    }

    /**
     * The details as saved: each trimmed, a single-line one with its runs of
     * whitespace made one space, and an empty one missing (null). Only the
     * details given are returned.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, string|int|null>
     *
     * @throws InvalidSupplierDetails
     */
    protected function details(array $details): array
    {
        $clean = [];

        foreach ($details as $field => $value) {
            $rule = self::DETAILS[$field] ?? throw new LogicException("A Supplier has no detail [{$field}].");
            $clean[$field] = $this->detail($field, $rule, $value);
        }

        if (array_key_exists('legal_name', $clean) && $clean['legal_name'] === null) {
            throw new InvalidSupplierDetails('legal_name', 'Enter the Supplier\'s legal name.');
        }

        return $clean;
    }

    /**
     * @throws InvalidSupplierDetails
     */
    protected function reason(?string $reason): string
    {
        $reason = self::trimmed((string) $reason);

        if ($reason === '') {
            throw InvalidSupplierDetails::noReason();
        }

        return $reason;
    }

    /** Saves $supplier and audits its field differences together with the reason. */
    protected function saveWithReason(Actor $actor, string $action, Supplier $supplier, string $reason): void
    {
        $changes = AuditChanges::pending($supplier, app(PersonalFields::class));
        $supplier->save();
        $this->audit($actor, $action, $supplier, [...$changes, 'reason' => $reason]);
    }

    /**
     * @param  array{0: string, 1: string, 2: int}  $rule
     *
     * @throws InvalidSupplierDetails
     */
    private function detail(string $field, array $rule, mixed $value): string|int|null
    {
        [$words, $kind, $max] = $rule;

        if ($value === null || (is_string($value) && self::trimmed($value) === '')) {
            return null;
        }

        if ($kind === self::DAYS) {
            return $this->days($field, $value);
        }

        if (! is_string($value)) {
            throw new InvalidSupplierDetails($field, "Enter the {$words} as words.");
        }

        $value = $kind === self::TEXT ? self::trimmed($value) : self::trimmed((string) preg_replace('/[\s\p{Z}]+/u', ' ', $value));

        if (mb_strlen($value) > $max) {
            throw new InvalidSupplierDetails($field, "Keep the {$words} to {$max} characters or fewer.");
        }

        return match ($kind) {
            self::CURRENCY => $this->currency($field, $value),
            self::EMAIL => filter_var($value, FILTER_VALIDATE_EMAIL) !== false
                ? $value
                : throw new InvalidSupplierDetails($field, "Enter the {$words} as an email address, like sales@example.com."),
            self::PHONE => preg_match('/^\+?[0-9][0-9 ()\-]{5,}$/', $value) === 1
                ? $value
                : throw new InvalidSupplierDetails($field, "Enter the {$words} as a phone number, like 0803 000 0000."),
            default => $value,
        };
    }

    /** $value without whitespace at either end, Unicode spaces (such as a no-break space) included. */
    public static function trimmed(string $value): string
    {
        return (string) preg_replace('/^[\s\p{Z}]+|[\s\p{Z}]+$/u', '', $value);
    }

    /**
     * @throws InvalidSupplierDetails
     */
    private function currency(string $field, string $value): string
    {
        $currency = strtoupper($value);

        if (! in_array($currency, self::CURRENCIES, true)) {
            throw new InvalidSupplierDetails($field, 'Choose the currency it invoices in: USD or NGN.');
        }

        return $currency;
    }

    /**
     * A dead-on-arrival window: a whole number of days, 1 or more.
     *
     * @throws InvalidSupplierDetails
     */
    private function days(string $field, mixed $value): int
    {
        $text = is_int($value) ? (string) $value : (is_string($value) ? trim($value) : '');

        if (preg_match('/^-?\d+$/', $text) !== 1 || (int) $text < 1) {
            throw new InvalidSupplierDetails($field, 'Enter the dead-on-arrival window as a whole number of days, 1 or more.');
        }

        if (strlen(ltrim($text, '0')) > 4 || (int) $text > self::DAYS_MAX) {
            throw new InvalidSupplierDetails($field, 'Enter a dead-on-arrival window of '.number_format(self::DAYS_MAX).' days or fewer.');
        }

        return (int) $text;
    }
}
