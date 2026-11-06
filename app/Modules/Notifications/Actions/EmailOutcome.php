<?php

namespace App\Modules\Notifications\Actions;

/**
 * What {@see SendEmail} did for an event.
 */
enum EmailOutcome: string
{
    /** The provider accepted the email now. */
    case Sent = 'sent';

    /** An earlier attempt for this event already sent it. */
    case AlreadySent = 'already_sent';

    /** It may or may not have gone (a timeout, or a process that died mid-send); it is never sent again. */
    case Uncertain = 'uncertain';
}
