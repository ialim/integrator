<?php

namespace App\Modules\Notifications\Contracts;

/**
 * One plain-text email to one person.
 */
final class EmailMessage
{
    public function __construct(
        public readonly string $toAddress,
        public readonly string $toName,
        public readonly string $subject,
        public readonly string $text,
    ) {}
}
