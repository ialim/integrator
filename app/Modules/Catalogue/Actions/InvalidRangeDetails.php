<?php

namespace App\Modules\Catalogue\Actions;

use InvalidArgumentException;

/**
 * A Product Range detail that is refused, with the field it belongs to so
 * the screen can show the words beside that field.
 */
final class InvalidRangeDetails extends InvalidArgumentException
{
    public const NAME_MAX = 120;

    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }

    public static function nameTaken(string $name): self
    {
        return new self('name', "An active Product Range is already called {$name}. Give this one another name.");
    }

    public static function noReason(): self
    {
        return new self('reason', 'Give a reason for the change. It is kept on record with the change.');
    }
}
