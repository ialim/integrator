<?php

use App\Modules\Staff\Capabilities\CapabilityRegistry;
use Tests\Architecture\Support\CapabilityDeclarationScanner;
use Tests\Architecture\Support\RealScans;
use Tests\Architecture\Support\Violation;

it('has every Action, console page and console resource declare the capability it needs, and check it (FR-52)', function () {
    $violations = RealScans::capabilities();

    if (withFixtures()) {
        array_push($violations, ...(new CapabilityDeclarationScanner)->scan([archFixture('Capabilities')], app(CapabilityRegistry::class)->names()));
    }

    expect($violations)->toBeEmpty(Violation::report('FR-52, every console page and Action declares its capability,', $violations));
});

it('reports a missing, unknown, reasonless or unchecked declaration by file', function () {
    $violations = (new CapabilityDeclarationScanner)->scan([archFixture('Capabilities')], app(CapabilityRegistry::class)->names());
    $root = 'tests/Architecture/Fixtures/Capabilities/Modules/Orders';

    expect(locations($violations))->toBe([
        "{$root}/Actions/ApproveOrder.php:6",
        "{$root}/Actions/CancelOrder.php:9",
        "{$root}/Actions/ExpireOrder.php:9",
        "{$root}/Actions/ShipOrder.php:9",
        "{$root}/Actions/WriteOrderLog.php:6",
        "{$root}/Filament/OrderReportPage.php:9",
        "{$root}/Filament/OrderResource.php:9",
        "{$root}/Filament/OrderResource/ListOrders.php:9",
        "{$root}/Filament/OrderResource/ShowOrder.php:8",
        "{$root}/Filament/OrdersPage.php:8",
        "{$root}/Filament/ShipmentResource.php:8",
    ]);

    $messages = array_map('strval', $violations);

    expect($messages[0])->toContain('Action ArchFixtures\Capabilities\Modules\Orders\Actions\ApproveOrder declares no capability')
        ->and($messages[1])->toContain('needs capability orders.cancel, which the capability registry does not know')
        ->and($messages[2])->toContain('without a reason')
        ->and($messages[3])->toContain('ShipOrder declares capability staff.manage but never checks it')
        ->and($messages[4])->toContain('Action ArchFixtures\Capabilities\Modules\Orders\Actions\WriteOrderLog declares no capability')
        ->and($messages[5])->toContain('console page ArchFixtures\Capabilities\Modules\Orders\Filament\OrderReportPage declares capability staff.manage but does not use GuardedByCapability')
        ->and($messages[6])->toContain('console resource ArchFixtures\Capabilities\Modules\Orders\Filament\OrderResource declares capability staff.manage but does not use GuardedByCapability')
        ->and($messages[7])->toContain('declares a capability of its own')
        ->and($messages[8])->toContain('ShowOrder does not use GuardedByCapability')
        ->and($messages[9])->toContain('console page ArchFixtures\Capabilities\Modules\Orders\Filament\OrdersPage declares no capability')
        ->and($messages[10])->toContain('console resource ArchFixtures\Capabilities\Modules\Orders\Filament\ShipmentResource declares no capability');
});

it('catches an undeclared or unchecked Action, page or resource planted in a real module', function (string $relative, string $code, int $line) {
    expect(RealScans::withPlanted($relative, $code, RealScans::capabilities(...)))->toBe(["{$relative}:{$line}"]);
})->with([
    'an undeclared Action' => ['app/Modules/Orders/Actions/ArchPlantedAction.php', <<<'PHP'
        <?php

        namespace App\Modules\Orders\Actions;

        use App\Modules\Shared\Actions\Action;

        final class ArchPlantedAction extends Action {}
        PHP, 7],
    'a declared Action that never checks' => ['app/Modules/Orders/Actions/ArchPlantedUnchecked.php', <<<'PHP'
        <?php

        namespace App\Modules\Orders\Actions;

        use App\Modules\Shared\Actions\Action;
        use App\Modules\Shared\Capabilities\NeedsCapability;

        #[NeedsCapability('staff.manage')]
        final class ArchPlantedUnchecked extends Action
        {
            public function handle(): void {}
        }
        PHP, 8],
    'an undeclared console page' => ['app/Modules/Orders/Filament/ArchPlantedPage.php', <<<'PHP'
        <?php

        namespace App\Modules\Orders\Filament;

        use Filament\Pages\Page;

        final class ArchPlantedPage extends Page {}
        PHP, 7],
    'a declared console page without the guard' => ['app/Modules/Orders/Filament/ArchPlantedUnguarded.php', <<<'PHP'
        <?php

        namespace App\Modules\Orders\Filament;

        use App\Modules\Shared\Capabilities\NeedsCapability;
        use Filament\Pages\Page;

        #[NeedsCapability('staff.manage')]
        final class ArchPlantedUnguarded extends Page {}
        PHP, 8],
    'an undeclared console resource' => ['app/Modules/Orders/Filament/ArchPlantedResource.php', <<<'PHP'
        <?php

        namespace App\Modules\Orders\Filament;

        use Filament\Resources\Resource;

        final class ArchPlantedResource extends Resource {}
        PHP, 7],
]);
