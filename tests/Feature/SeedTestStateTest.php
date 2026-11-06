<?php

use App\Console\Commands\SeedTestState;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use App\Modules\Staff\StatusTables\StaffAccountStatus;
use Database\Factories\ActionFactory;
use Database\Factories\Staff\ActiveOperationsScenario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Process;

uses(RefreshDatabase::class);

it('registers the seeding command in testing', function () {
    expect(Artisan::all())->toHaveKey('app:seed-test-state');

    $this->artisan('app:seed-test-state')->assertSuccessful();
});

it('refuses an unknown scenario', function () {
    $this->artisan('app:seed-test-state', ['scenarios' => ['no-such-scenario']])->assertFailed();
});

it('does not register the seeding command in production', function () {
    $result = Process::path(base_path())->timeout(180)
        ->env(['APP_ENV' => 'production'])
        ->run([PHP_BINARY, 'artisan', 'app:seed-test-state', '--no-interaction']);

    expect($result->failed())->toBeTrue()
        ->and($result->output().$result->errorOutput())->toMatch('/no commands defined in the "app" namespace|"app:seed-test-state" is not defined/');

    $list = Process::path(base_path())->timeout(180)->env(['APP_ENV' => 'production'])->run([PHP_BINARY, 'artisan', 'list', '--raw']);

    expect($list->successful())->toBeTrue()
        ->and($list->output())->not->toContain('app:seed-test-state');
});

it('is enabled only in local and testing', function (string $environment, bool $enabled) {
    app()->detectEnvironment(fn () => $environment);

    $command = new SeedTestState;
    $command->setLaravel(app());

    expect($command->isEnabled())->toBe($enabled);
})->with([
    ['local', true],
    ['testing', true],
    ['staging', false],
    ['production', false],
]);

it('refuses to build factories outside local and testing', function () {
    app()->detectEnvironment(fn () => 'production');

    ActionFactory::assertAvailable();
})->throws(LogicException::class, 'Test factories exist only in local and testing');

it('seeds the accessibility run\'s state: the founder, an invitee and an active Operations member with Support duties Off', function () {
    $this->artisan('app:seed-test-state', ['scenarios' => ['first-admin', 'invited-staff', 'active-operations']])->assertSuccessful();

    $operations = StaffMember::query()->where('email', ActiveOperationsScenario::EMAIL)->firstOrFail();

    expect(StaffMember::query()->count())->toBe(3)
        ->and($operations->name)->toBe(ActiveOperationsScenario::NAME)
        ->and($operations->role)->toBe(StaffRole::Operations)
        ->and($operations->status)->toBe(StaffAccountStatus::Active)
        ->and($operations->support_duties)->toBeFalse()
        ->and($operations->getAppAuthenticationSecret())->toBe(ActiveOperationsScenario::SECRET)
        ->and(Hash::check(ActiveOperationsScenario::PASSWORD, (string) $operations->password))->toBeTrue();
});
