<?php

namespace App\Modules\Shared\Contracts;

use App\Modules\Shared\Providers\HasFake;
use App\Modules\Shared\Support\FrozenClock;
use App\Modules\Shared\Support\SystemClock;
use Carbon\CarbonImmutable;

/**
 * The only way code reads the current time (TC-2). Always UTC.
 * Production binds {@see SystemClock};
 * testing and staging bind {@see FrozenClock}, which can be set and advanced.
 */
#[HasFake(FrozenClock::class)]
interface Clock
{
    public function now(): CarbonImmutable;
}
