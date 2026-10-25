<?php

namespace App\Modules\Shared\Support;

use DateTimeInterface;
use DateTimeZone;

/**
 * Times leave the system as ISO 8601 UTC with a trailing Z, to the second.
 */
final class Iso8601
{
    public static function format(DateTimeInterface $instant): string
    {
        return \DateTimeImmutable::createFromInterface($instant)
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s\Z');
    }
}
