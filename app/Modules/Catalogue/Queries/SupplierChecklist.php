<?php

namespace App\Modules\Catalogue\Queries;

use App\Modules\Catalogue\Actions\SupplierAction;
use App\Modules\Catalogue\Models\OnboardingChecklistItem;
use App\Modules\Catalogue\Models\Supplier;
use LogicException;

/**
 * Reads a Supplier against the onboarding checklist (FR-81): each item
 * Clear or Needs action, in words. The items are rows, so later stories add
 * their own keys here (documents in 2.6, bank details in 2.9); nothing
 * assumes there are five.
 *
 * - Business details are Clear when the legal name, CAC RC number, TIN,
 *   address, invoicing currency and payment terms are all present. The
 *   trade name is optional.
 * - Contacts are Clear when both contacts have a name, a phone and an email.
 * - A term is Clear when its text is stated; dead on arrival also needs its
 *   window in days.
 */
final class SupplierChecklist
{
    /** Each item's name in words. */
    public const LABELS = [
        OnboardingChecklistItem::BUSINESS_DETAILS => 'Business details',
        OnboardingChecklistItem::CONTACTS => 'Contacts: sales, and returns and RMA',
        OnboardingChecklistItem::RETURNS_TERMS => 'Returns terms',
        OnboardingChecklistItem::DOA_TERMS => 'Dead-on-arrival terms',
        OnboardingChecklistItem::COUNTERFEIT_CHARGEBACK => 'Counterfeit chargeback',
    ];

    private const BUSINESS = [
        'legal_name' => 'legal name',
        'cac_rc_number' => 'CAC RC number',
        'tin' => 'TIN',
        'address' => 'address',
        'invoice_currency' => 'invoicing currency',
        'payment_terms' => 'payment terms',
    ];

    private const CONTACTS = [
        'sales_contact_name' => 'sales name',
        'sales_contact_phone' => 'sales phone',
        'sales_contact_email' => 'sales email',
        'returns_contact_name' => 'returns and RMA name',
        'returns_contact_phone' => 'returns and RMA phone',
        'returns_contact_email' => 'returns and RMA email',
    ];

    /**
     * The checklist items in their order, as rows.
     *
     * @return list<OnboardingChecklistItem>
     */
    public static function items(): array
    {
        return OnboardingChecklistItem::query()->orderBy('position')->orderBy('key')->get()->values()->all();
    }

    public static function label(string $key): string
    {
        return self::LABELS[$key] ?? throw new LogicException("The onboarding checklist has no item [{$key}].");
    }

    /**
     * @param  list<OnboardingChecklistItem>  $items
     * @return list<ChecklistItemState>
     */
    public static function evaluate(Supplier $supplier, array $items): array
    {
        return array_map(fn (OnboardingChecklistItem $item): ChecklistItemState => self::state($supplier, $item), $items);
    }

    /**
     * The required items that need action, by name: "Business details and Counterfeit chargeback".
     *
     * @param  list<ChecklistItemState>  $states
     */
    public static function namesNeedingAction(array $states): string
    {
        $names = array_values(array_map(fn (ChecklistItemState $state): string => $state->label, array_filter($states, fn (ChecklistItemState $state): bool => $state->holdsBackApproval())));

        return match (count($names)) {
            0 => '',
            1 => $names[0],
            default => implode(', ', array_slice($names, 0, -1)).' and '.$names[count($names) - 1],
        };
    }

    private static function state(Supplier $supplier, OnboardingChecklistItem $item): ChecklistItemState
    {
        [$clear, $detail] = match ($item->key) {
            OnboardingChecklistItem::BUSINESS_DETAILS => self::allStated($supplier, self::BUSINESS),
            OnboardingChecklistItem::CONTACTS => self::allStated($supplier, self::CONTACTS),
            OnboardingChecklistItem::RETURNS_TERMS => self::term($supplier->returns_terms),
            OnboardingChecklistItem::DOA_TERMS => self::deadOnArrival($supplier),
            OnboardingChecklistItem::COUNTERFEIT_CHARGEBACK => self::term($supplier->counterfeit_terms),
            default => throw new LogicException("The onboarding checklist has no rule for [{$item->key}]."),
        };

        return new ChecklistItemState($item->key, self::label($item->key), $item->required, $clear, $detail);
    }

    /**
     * @param  array<string, string>  $fields
     * @return array{0: bool, 1: string}
     */
    private static function allStated(Supplier $supplier, array $fields): array
    {
        $missing = [];

        foreach ($fields as $field => $words) {
            if (! self::stated($supplier->getAttribute($field))) {
                $missing[] = $words;
            }
        }

        return $missing === [] ? [true, 'Clear'] : [false, 'Needs action: missing '.implode(', ', $missing)];
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private static function term(?string $text): array
    {
        return self::stated($text) ? [true, 'Clear'] : [false, 'Needs action: not stated'];
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private static function deadOnArrival(Supplier $supplier): array
    {
        $days = $supplier->doa_window_days;
        $hasWindow = $days !== null && $days >= 1;
        $hasTerms = self::stated($supplier->doa_terms);

        return match (true) {
            $hasTerms && $hasWindow => [true, 'Clear · '.$days.' '.($days === 1 ? 'day' : 'days')],
            $hasTerms => [false, 'Needs action: missing the window in days'],
            $hasWindow => [false, 'Needs action: missing the terms'],
            default => [false, 'Needs action: not stated'],
        };
    }

    private static function stated(mixed $value): bool
    {
        return $value !== null && SupplierAction::trimmed((string) $value) !== '';
    }
}
