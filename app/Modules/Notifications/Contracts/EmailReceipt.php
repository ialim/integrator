<?php

namespace App\Modules\Notifications\Contracts;

/**
 * The provider's answer to a sent email: its own message id, when it gives one.
 */
final class EmailReceipt
{
    public function __construct(public readonly ?string $providerMessageId) {}
}
