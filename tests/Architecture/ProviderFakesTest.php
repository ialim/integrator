<?php

use Tests\Architecture\Support\ContractFakeScanner;
use Tests\Architecture\Support\RealScans;
use Tests\Architecture\Support\Violation;

it('gives every Contracts/ provider interface a fake that implements it (AD-15)', function () {
    $violations = RealScans::contractFakes();

    if (withFixtures()) {
        array_push($violations, ...(new ContractFakeScanner)->scan(archFixture('Contracts/Modules')));
    }

    expect($violations)->toBeEmpty(Violation::report('AD-15, providers behind module interfaces,', $violations));
});

it('reports a Contracts/ interface that declares no fake, by file', function () {
    $violations = (new ContractFakeScanner)->scan(archFixture('Contracts/Modules'));

    expect(locations($violations))->toBe([
        'tests/Architecture/Fixtures/Contracts/Modules/Notifications/Contracts/EmailProvider.php:6',
    ]);

    expect((string) $violations[0])->toContain('declares no fake');
});

it('catches a provider interface without a fake planted in a real module', function () {
    $relative = 'app/Modules/Orders/Contracts/ArchPlantedProvider.php';
    $code = <<<'PHP'
        <?php

        namespace App\Modules\Orders\Contracts;

        interface ArchPlantedProvider
        {
            public function call(): void;
        }
        PHP;

    expect(RealScans::withPlanted($relative, $code, RealScans::contractFakes(...)))->toBe(["{$relative}:5"]);
});
