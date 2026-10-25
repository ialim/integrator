<?php

namespace App\Modules\Shared\Support;

use App\Modules\Shared\Contracts\Clock;
use Carbon\CarbonImmutable;

final class SystemClock implements Clock
{
    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now('UTC');
    }
}
