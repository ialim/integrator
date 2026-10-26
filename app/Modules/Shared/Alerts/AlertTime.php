<?php

namespace App\Modules\Shared\Alerts;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * An alert's time in WAT and UTC, e.g. "25 October 2026, 10:00 WAT (09:00 UTC)".
 */
final class AlertTime
{
    public static function format(DateTimeInterface|string $instant): string
    {
        $utc = CarbonImmutable::parse($instant)->utc();
        $local = $utc->setTimezone((string) config('alerts.local_timezone', 'Africa/Lagos'));
        $label = (string) config('alerts.local_timezone_label', 'WAT');

        return $local->format('j F Y, H:i')." {$label} (".$utc->format('H:i').' UTC'
            .($local->format('Y-m-d') === $utc->format('Y-m-d') ? '' : ', '.$utc->format('j F Y'))
            .')';
    }
}
