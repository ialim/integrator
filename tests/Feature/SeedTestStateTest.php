<?php

use App\Console\Commands\SeedTestState;
use Database\Factories\ActionFactory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

it('registers the seeding command in testing', function () {
    expect(Artisan::all())->toHaveKey('app:seed-test-state');

    $this->artisan('app:seed-test-state')->assertSuccessful();
});

it('refuses an unknown scenario', function () {
    $this->artisan('app:seed-test-state', ['scenarios' => ['no-such-scenario']])->assertFailed();
});

it('does not register the seeding command in production', function () {
    $result = Process::path(base_path())
        ->env(['APP_ENV' => 'production'])
        ->run([PHP_BINARY, 'artisan', 'app:seed-test-state', '--no-interaction']);

    expect($result->failed())->toBeTrue()
        ->and($result->output().$result->errorOutput())->toMatch('/no commands defined in the "app" namespace|"app:seed-test-state" is not defined/');

    $list = Process::path(base_path())->env(['APP_ENV' => 'production'])->run([PHP_BINARY, 'artisan', 'list', '--raw']);

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
