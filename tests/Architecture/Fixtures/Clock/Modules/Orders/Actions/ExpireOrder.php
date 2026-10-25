<?php

namespace ArchFixtures\Clock\Modules\Orders\Actions;

use Carbon\Carbon;
use DateTimeImmutable;

// Fixture: the reads of the real time on lines 14 to 28 are reported; the rest are not.
class ExpireOrder
{
    public function handle(int $timestamp): array
    {
        return [
            now(),
            Carbon::now(),
            time(),
            date('Y-m-d'),
            new DateTimeImmutable,
            strtotime('2026-10-25'),
            strtotime('+1 day', $timestamp),
            mktime(),
            microtime(true),
            new DateTimeImmutable('+2 hours'),
            new DateTimeImmutable('next monday'),
            Carbon::parse('tomorrow'),
            Carbon::make('1 day ago'),
            date_create('midnight'),
            date_create_immutable('-3 days'),
            date('Y-m-d', $timestamp),
            new DateTimeImmutable('2026-10-25T09:00:00Z'),
            Carbon::parse('2026-10-25T09:00:00Z'),
            strtotime('2026-10-25', $timestamp),
            mktime(9, 0, 0, 10, 25, 2026),
        ];
    }
}
