<?php

namespace App\Modules\Staff\Actions;

use SensitiveParameter;

/**
 * The one rule for a staff password: at least 12 characters, not one character
 * repeated, and not containing the person's name or the part of their email
 * before the @ (case-insensitive). No external lookup.
 */
final class StaffPassword
{
    public const MIN_LENGTH = 12;

    /** Why the password is refused, in words, or null when it is acceptable. */
    public static function problem(#[SensitiveParameter] string $password, string $name, string $email): ?string
    {
        if (mb_strlen($password) < self::MIN_LENGTH) {
            return 'The password must be at least '.self::MIN_LENGTH.' characters.';
        }

        if (preg_match('/^(.)\1*$/su', $password) === 1) {
            return 'The password cannot be one character repeated.';
        }

        $lower = mb_strtolower($password);
        $localPart = mb_strtolower(trim(explode('@', $email)[0]));
        $name = mb_strtolower(trim($name));

        if ($localPart !== '' && str_contains($lower, $localPart)) {
            return 'The password cannot contain the email address.';
        }

        if ($name !== '' && str_contains($lower, $name)) {
            return 'The password cannot contain the name.';
        }

        return null;
    }
}
