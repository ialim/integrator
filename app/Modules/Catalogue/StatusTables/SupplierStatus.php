<?php

namespace App\Modules\Catalogue\StatusTables;

use App\Modules\Shared\StatusTables\StatusTable;

/**
 * A Supplier's states and the moves between them (AD-4): Not approved, then
 * Approved, by an Admin against the onboarding checklist or with a written
 * reason. There is no move back: an edit or a checklist change never
 * un-approves a Supplier (Story 2.16 adds stopping one).
 *
 * An edit is allowed in both states and ends in the state it started from,
 * so it is declared once per state.
 */
enum SupplierStatus: string implements StatusTable
{
    case NotApproved = 'not_approved';
    case Approved = 'approved';

    public static function moves(): array
    {
        return [
            'edit' => ['from' => [self::NotApproved], 'to' => self::NotApproved],
            'edit_approved' => ['from' => [self::Approved], 'to' => self::Approved],
            'approve' => ['from' => [self::NotApproved], 'to' => self::Approved],
            'approve_with_reason' => ['from' => [self::NotApproved], 'to' => self::Approved],
        ];
    }

    /** A new Supplier's state. */
    public static function initial(): self
    {
        return self::NotApproved;
    }

    /** The edit move for this state: an edit never changes it. */
    public function editMove(): string
    {
        return $this === self::Approved ? 'edit_approved' : 'edit';
    }

    public function label(): string
    {
        return match ($this) {
            self::NotApproved => 'Not approved',
            self::Approved => 'Approved',
        };
    }
}
