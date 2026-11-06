<?php

namespace App\Modules\Staff\Actions;

use RuntimeException;

/**
 * An invitation link that no longer works, said in words that offer the way on.
 */
final class InvitationUnusable extends RuntimeException
{
    public static function expired(): self
    {
        return new self('This invitation link has expired: links work for '.InvitationLinks::VALID_HOURS.' hours. Ask an Admin to send you a new one.');
    }

    public static function used(): self
    {
        return new self('This invitation link has already been used, or a newer one has replaced it. Use the latest link you were sent, or ask an Admin to send you a new one.');
    }
}
