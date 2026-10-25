<?php

use Tests\Architecture\Support\RealScans;
use Tests\Architecture\Support\UuidKeyScanner;
use Tests\Architecture\Support\Violation;

it('gives every model a UUIDv7 primary key from the Shared helper (AD-11)', function () {
    $violations = RealScans::uuidKeys();

    if (withFixtures()) {
        array_push($violations, ...(new UuidKeyScanner)->scan([archFixture('Uuid')]));
    }

    expect($violations)->toBeEmpty(Violation::report('AD-11, UUIDv7 keys everywhere,', $violations));
});

it('reports a model without HasUuidV7, by file and line', function () {
    expect(locations((new UuidKeyScanner)->scan([archFixture('Uuid')])))->toBe([
        'tests/Architecture/Fixtures/Uuid/Modules/Catalogue/Models/Supplier.php:8',
    ]);
});

it('catches a model without HasUuidV7 planted in a real module', function () {
    $relative = 'app/Modules/Orders/Models/ArchPlantedModel.php';
    $code = <<<'PHP'
        <?php

        namespace App\Modules\Orders\Models;

        use Illuminate\Database\Eloquent\Model;

        class ArchPlantedModel extends Model {}
        PHP;

    expect(RealScans::withPlanted($relative, $code, RealScans::uuidKeys(...)))->toBe(["{$relative}:7"]);
});
