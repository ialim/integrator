<?php

namespace App\Modules\Catalogue\Actions;

use InvalidArgumentException;

/**
 * A Supplier detail, a reason or a checklist setting that is refused, with
 * the field it belongs to so the screen can show the words beside that
 * field.
 */
final class InvalidSupplierDetails extends InvalidArgumentException
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
