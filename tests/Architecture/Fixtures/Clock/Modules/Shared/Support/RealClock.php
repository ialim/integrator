<?php

namespace ArchFixtures\Clock\Modules\Shared\Support;

// Fixture: the Shared kernel is where the Clock reads the real time, so nothing here is reported.
class RealClock
{
    public function now(): mixed
    {
        return now();
    }
}
