<?php

use Tests\Architecture\Support\ModelWriteScanner;
use Tests\Architecture\Support\RealScans;
use Tests\Architecture\Support\Violation;

it('keeps every model write inside an Action (AD-1)', function () {
    $violations = RealScans::modelWrites();

    if (withFixtures()) {
        array_push($violations, ...(new ModelWriteScanner)->scan([archFixture('ModelWrites')]));
    }

    expect($violations)->toBeEmpty(Violation::report('AD-1, Actions are the only writers,', $violations));
});

it('reports each model write outside an Action by file and line', function () {
    $file = 'tests/Architecture/Fixtures/ModelWrites/Modules/Orders/Http/CancelOrderController.php';

    expect(locations((new ModelWriteScanner)->scan([archFixture('ModelWrites')])))->toBe([
        "{$file}:15", // $order->save()
        "{$file}:20", // Order::create()
        "{$file}:26", // $found->delete() on a queried model
        "{$file}:31", // Order::where()->update()
        "{$file}:32", // $this->order->forceDelete()
        "{$file}:33", // DB::table()->insert()
        "{$file}:34", // $this->order->lines()->attach(), a pivot write
    ]);
});

it('catches a model write planted in a real module, including under a nested Filament Actions/ folder', function (string $relative) {
    $code = <<<'PHP'
        <?php

        namespace App\Modules\Orders\Planted;

        class ArchPlantedOrder extends \Illuminate\Database\Eloquent\Model {}

        class ArchPlantedWriter
        {
            public function run(): void
            {
                ArchPlantedOrder::create([]);
            }
        }
        PHP;

    expect(RealScans::withPlanted($relative, $code, RealScans::modelWrites(...)))->toBe(["{$relative}:11"]);
})->with([
    'app/Modules/Orders/Http/ArchPlantedWriter.php',
    'app/Modules/Orders/Filament/Resources/Actions/ArchPlantedWriter.php',
]);
