<?php

namespace App\Modules\Pricing\Actions;

use InvalidArgumentException;

/**
 * A Pricing rule value that is refused, with the field it belongs to so the
 * screen can show the words beside that field: "A markup cannot be below
 * zero." (edge-case row 20).
 */
final class InvalidPricingInput extends InvalidArgumentException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }

    public static function noReason(): self
    {
        return new self('reason', 'Give a reason for the change. It is kept on record with the change.');
    }
}
