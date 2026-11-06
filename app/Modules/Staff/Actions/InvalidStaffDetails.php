<?php

namespace App\Modules\Staff\Actions;

use InvalidArgumentException;

/**
 * A staff account detail that is refused, with the field it belongs to so the
 * screen can show the words beside that field.
 */
final class InvalidStaffDetails extends InvalidArgumentException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }

    public static function emailTaken(): self
    {
        return new self('email', 'A staff account already uses this email address. Each person needs their own address.');
    }
}
