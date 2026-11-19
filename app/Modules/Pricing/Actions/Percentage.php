<?php

namespace App\Modules\Pricing\Actions;

/**
 * A percentage as Pricing stores it: numeric(7,4), from 0 up to 999.9999,
 * kept as a string so no float ever touches it. Reads what a person typed
 * ("25", "27.5", "27.5%") and writes it back in words ("27.5%"). No price
 * arithmetic here.
 */
final class Percentage
{
    /**
     * The typed value as stored, "27.5000".
     *
     * @param  string  $subject  "A markup", "The margin floor": the words for a refusal
     *
     * @throws InvalidPricingInput
     */
    public static function parse(string $typed, string $field, string $subject): string
    {
        // Only the outer whitespace goes: "2 5" is not 25.
        $value = trim(rtrim(trim($typed), '%'));

        // One minus sign before a figure that is not zero is below zero; "--5" is not a percentage.
        if (preg_match('/^-\d*(\.\d*)?$/', $value) === 1 && preg_match('/[1-9]/', $value) === 1) {
            throw new InvalidPricingInput($field, "{$subject} cannot be below zero.");
        }

        // "-0" and "-0.00" are zero.
        if (preg_match('/^-\d+(\.\d+)?$/', $value) === 1) {
            $value = substr($value, 1);
        }

        if (preg_match('/^\d+(\.\d+)?$/', $value) !== 1) {
            throw new InvalidPricingInput($field, 'Enter a percentage, like 25 or 27.5.');
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $whole = ltrim($whole, '0') === '' ? '0' : ltrim($whole, '0');

        if (strlen($whole) > 3) {
            throw new InvalidPricingInput($field, "{$subject} must be under 1,000%.");
        }

        if (strlen(rtrim($fraction, '0')) > 4) {
            throw new InvalidPricingInput($field, 'Use at most four decimal places, like 27.1234.');
        }

        return $whole.'.'.str_pad(substr($fraction, 0, 4), 4, '0');
    }

    /** "27.5000" reads "27.5"; "25.0000" reads "25". */
    public static function plain(string $stored): string
    {
        return str_contains($stored, '.') ? rtrim(rtrim($stored, '0'), '.') : $stored;
    }

    /** "27.5%". */
    public static function words(string $stored): string
    {
        return self::plain($stored).'%';
    }
}
