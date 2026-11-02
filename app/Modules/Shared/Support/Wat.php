<?php

namespace App\Modules\Shared\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Times are stored in UTC and shown in West Africa Time (UTC+1, no daylight saving).
 */
final class Wat
{
    public const TIMEZONE = 'Africa/Lagos';

    public const LABEL = 'WAT';

    public static function of(DateTimeInterface $instant): CarbonImmutable
    {
        return CarbonImmutable::instance($instant)->setTimezone(self::TIMEZONE);
    }

    /** "14:05 WAT" */
    public static function time(DateTimeInterface $instant): string
    {
        return self::of($instant)->format('H:i').' '.self::LABEL;
    }

    /** "Monday 26 October 2026, 14:05 WAT" */
    public static function dateTime(DateTimeInterface $instant): string
    {
        return self::of($instant)->format('l j F Y, H:i').' '.self::LABEL;
    }
}
