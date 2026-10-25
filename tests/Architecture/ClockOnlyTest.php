<?php

use Tests\Architecture\Support\ClockScanner;
use Tests\Architecture\Support\RealScans;
use Tests\Architecture\Support\Violation;

it('reads the time only through the Shared Clock (TC-2)', function () {
    $violations = RealScans::clock();

    if (withFixtures()) {
        array_push($violations, ...(new ClockScanner)->scan([archFixture('Clock')], ['/Fixtures/Clock/Modules/Shared/']));
    }

    expect($violations)->toBeEmpty(Violation::report('Clock-only time', $violations));
});

it('reports reads of the real time outside Shared, by file and line', function () {
    $file = 'tests/Architecture/Fixtures/Clock/Modules/Orders/Actions/ExpireOrder.php';

    expect(locations((new ClockScanner)->scan([archFixture('Clock')], ['/Fixtures/Clock/Modules/Shared/'])))->toBe([
        "{$file}:14", // now()
        "{$file}:15", // Carbon::now()
        "{$file}:16", // time()
        "{$file}:17", // date('Y-m-d')
        "{$file}:18", // new DateTimeImmutable
        "{$file}:19", // strtotime() with one argument
        "{$file}:20", // strtotime('+1 day', ...) relative
        "{$file}:21", // mktime()
        "{$file}:22", // microtime()
        "{$file}:23", // new DateTimeImmutable('+2 hours')
        "{$file}:24", // new DateTimeImmutable('next monday')
        "{$file}:25", // Carbon::parse('tomorrow')
        "{$file}:26", // Carbon::make('1 day ago')
        "{$file}:27", // date_create('midnight')
        "{$file}:28", // date_create_immutable('-3 days')
    ]);
});

it('catches a read of the real time planted in a real module', function () {
    $relative = 'app/Modules/Orders/Actions/ArchPlantedTime.php';
    $code = <<<'PHP'
        <?php

        namespace App\Modules\Orders\Actions;

        class ArchPlantedTime
        {
            public function handle(): mixed
            {
                return now();
            }
        }
        PHP;

    expect(RealScans::withPlanted($relative, $code, RealScans::clock(...)))->toBe(["{$relative}:9"]);
});
