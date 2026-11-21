<?php

namespace App\Modules\Catalogue\Models;

use App\Modules\Catalogue\StatusTables\SupplierStatus;
use App\Modules\Shared\Support\HasUuidV7;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * A Supplier (FR-14, FR-81): its business details, two contacts, the three
 * agreement terms, and whether an Admin has approved it, by whom, when and,
 * for an approval against gaps, why. Suppliers never sign in; staff keep the
 * record. Written only by the Catalogue Supplier Actions, and never shown on
 * an Integrator surface.
 *
 * @property string $id
 * @property string $legal_name
 * @property ?string $trade_name
 * @property ?string $cac_rc_number
 * @property ?string $tin
 * @property ?string $address
 * @property ?string $invoice_currency
 * @property ?string $payment_terms
 * @property ?string $sales_contact_name
 * @property ?string $sales_contact_phone
 * @property ?string $sales_contact_email
 * @property ?string $returns_contact_name
 * @property ?string $returns_contact_phone
 * @property ?string $returns_contact_email
 * @property ?string $returns_terms
 * @property ?string $doa_terms
 * @property ?int $doa_window_days
 * @property ?string $counterfeit_terms
 * @property SupplierStatus $status
 * @property ?string $approved_by
 * @property ?CarbonImmutable $approved_at
 * @property ?string $approval_reason
 * @property ?CarbonImmutable $created_at
 */
final class Supplier extends Model
{
    use HasUuidV7;

    /** The contacts' names, phones and emails: personal data, audited only as "changed" (AD-21). */
    public const PERSONAL_FIELDS = [
        'sales_contact_name', 'sales_contact_phone', 'sales_contact_email',
        'returns_contact_name', 'returns_contact_phone', 'returns_contact_email',
    ];

    protected $table = 'suppliers';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SupplierStatus::class,
            'doa_window_days' => 'integer',
            'approved_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
