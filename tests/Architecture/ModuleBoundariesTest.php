<?php

use Tests\Architecture\Support\ModuleBoundaryScanner;
use Tests\Architecture\Support\RealScans;
use Tests\Architecture\Support\Violation;

it('keeps modules to each other\'s Actions, Queries and Lock actions (AD-2)', function () {
    $violations = RealScans::boundaries();

    if (withFixtures()) {
        array_push($violations, ...(new ModuleBoundaryScanner)->scan(archFixture('Boundaries/Modules'), 'ArchFixtures\\Boundaries\\Modules'));
    }

    expect($violations)->toBeEmpty(Violation::report('AD-2, strict module boundaries,', $violations));
});

it('reports an import of another module\'s model and a query of its table, by file', function () {
    $violations = (new ModuleBoundaryScanner)->scan(archFixture('Boundaries/Modules'), 'ArchFixtures\\Boundaries\\Modules');
    $controller = 'tests/Architecture/Fixtures/Boundaries/Modules/Orders/Http/ShowOrderController.php';
    $query = 'tests/Architecture/Fixtures/Boundaries/Modules/Orders/Queries/OrderLinesQuery.php';

    expect(locations($violations))->toBe([
        "{$controller}:5",  // use ...\Catalogue\Models\Product
        "{$controller}:13", // Product::query()
        "{$query}:14",      // select * from products
        "{$query}:19",      // ->join('products', ...)
    ]);

    expect((string) $violations[0])->toContain('which is private to Catalogue');
    expect((string) $violations[2])->toContain('queries table products, which belongs to Catalogue');
});

it('catches an import of another module\'s model planted in a real module', function () {
    $relative = 'app/Modules/Orders/Http/ArchPlantedImport.php';
    $code = <<<'PHP'
        <?php

        namespace App\Modules\Orders\Http;

        use App\Modules\Catalogue\Models\ArchPlantedProduct;

        class ArchPlantedImport {}
        PHP;

    expect(RealScans::withPlanted($relative, $code, RealScans::boundaries(...)))->toBe(["{$relative}:5"]);
});
