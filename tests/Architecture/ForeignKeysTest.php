<?php

use Tests\Architecture\Support\ForeignKeyScanner;
use Tests\Architecture\Support\RealScans;
use Tests\Architecture\Support\Violation;

it('makes every cross-module foreign key ON DELETE RESTRICT', function () {
    $violations = RealScans::foreignKeys();

    if (withFixtures()) {
        array_push($violations, ...(new ForeignKeyScanner)->scan(archFixture('Boundaries/Modules')));
    }

    expect($violations)->toBeEmpty(Violation::report('The foreign-key rule', $violations));
});

it('reports a cross-module foreign key without restrictOnDelete() by migration, and leaves same-module keys alone', function () {
    $violations = (new ForeignKeyScanner)->scan(archFixture('Boundaries/Modules'));

    expect(locations($violations))->toBe([
        'tests/Architecture/Fixtures/Boundaries/Modules/Orders/Database/Migrations/2026_01_02_000000_create_orders_tables.php:19',
    ]);

    expect((string) $violations[0])->toContain('foreign key from Orders to products (owned by Catalogue) must use ->restrictOnDelete()');
});

it('catches a cross-module foreign key planted in a real module migration', function () {
    $relative = 'app/Modules/Orders/Database/Migrations/2099_01_01_000000_arch_planted_lines.php';
    $code = <<<'PHP'
        <?php

        use Illuminate\Database\Schema\Blueprint;
        use Illuminate\Support\Facades\Schema;

        return new class extends Illuminate\Database\Migrations\Migration
        {
            public function up(): void
            {
                Schema::create('arch_planted_lines', function (Blueprint $table) {
                    $table->foreignUuid('session_id')->constrained('sessions');
                });
            }
        };
        PHP;

    expect(RealScans::withPlanted($relative, $code, RealScans::foreignKeys(...)))->toBe(["{$relative}:11"]);
});
