<?php

namespace Database\Factories\Catalogue;

use App\Modules\Catalogue\Actions\AddSupplier;
use App\Modules\Catalogue\Queries\SupplierRecord;
use Database\Factories\ActionFactory;

/**
 * A Supplier added through {@see AddSupplier}, Not approved, with every
 * detail stated so its checklist is clear; pass the acting staff member as
 * `actor`, and `details` to replace them. Test values only.
 *
 * @extends ActionFactory<SupplierRecord>
 */
final class SupplierFactory extends ActionFactory
{
    /** Every detail stated: the checklist is 5 of 5 clear. Test values only. */
    public const COMPLETE = [
        'legal_name' => 'Northgate Distribution Ltd',
        'trade_name' => 'Northgate',
        'cac_rc_number' => 'RC 1234567',
        'tin' => '12345678-0001',
        'address' => '22 Warehouse Road, Apapa, Lagos',
        'invoice_currency' => 'USD',
        'payment_terms' => '30 days from invoice',
        'sales_contact_name' => 'Ada Sales',
        'sales_contact_phone' => '0803 000 0000',
        'sales_contact_email' => 'sales@northgate.example',
        'returns_contact_name' => 'Musa Returns',
        'returns_contact_phone' => '0805 000 0000',
        'returns_contact_email' => 'rma@northgate.example',
        'returns_terms' => 'Unopened goods within 30 days.',
        'doa_terms' => 'Replaced or credited on proof.',
        'doa_window_days' => '30',
        'counterfeit_terms' => 'Full chargeback of cost.',
    ];

    protected function action(): string
    {
        return AddSupplier::class;
    }

    protected function definition(): array
    {
        return ['details' => self::COMPLETE];
    }
}
