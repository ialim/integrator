<?php

use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Queries\AuditTrail;
use App\Modules\Staff\Actions\CreateFirstAdmin;
use App\Modules\Staff\Actions\FirstAdminRefused;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(fn () => app(Clock::class)->set('2026-10-26T09:00:00Z'));
afterEach(fn () => app(Clock::class)->reset());

const FIRST_ADMIN_PASSWORD = 'correct-horse-battery';

function runCreateFirstAdmin(object $test, string $password = FIRST_ADMIN_PASSWORD, ?string $confirmation = null): mixed
{
    return $test->artisan('staff:create-first-admin', ['--name' => 'Ada', '--email' => 'Ada@Example.com'])
        ->expectsQuestion('Password (at least 12 characters)', $password)
        ->expectsQuestion('Type the password again', $confirmation ?? $password);
}

it('creates the founder\'s Admin when no staff account exists, and says how to finish setup', function () {
    runCreateFirstAdmin($this)
        ->expectsOutputToContain('Created the Admin account for ada@example.com.')
        ->expectsOutputToContain('/console/login')
        ->expectsOutputToContain('Set up an authenticator app when asked')
        ->doesntExpectOutputToContain(FIRST_ADMIN_PASSWORD)
        ->assertSuccessful();

    $admin = StaffMember::query()->sole();

    expect($admin->name)->toBe('Ada')
        ->and($admin->email)->toBe('ada@example.com')
        ->and($admin->role)->toBe(StaffRole::Admin)
        ->and($admin->is_founder)->toBeTrue()
        ->and($admin->is_active)->toBeTrue()
        ->and($admin->app_authentication_secret)->toBeNull()
        ->and(Hash::check(FIRST_ADMIN_PASSWORD, $admin->password))->toBeTrue();
});

it('writes an audit row naming system as the actor, with personal fields only marked changed', function () {
    runCreateFirstAdmin($this)->assertSuccessful();

    $admin = StaffMember::query()->sole();
    $entries = app(AuditTrail::class)->forSubject('staff_members', $admin->id);

    expect($entries)->toHaveCount(1);

    $entry = $entries[0];

    expect($entry->actorType)->toBe('system')
        ->and($entry->actorId)->toBeNull()
        ->and($entry->action)->toBe('staff.first_admin_created')
        ->and($entry->occurredAt->toIso8601ZuluString())->toBe('2026-10-26T09:00:00Z')
        ->and($entry->changes)->toEqual([
            'email' => 'changed',
            'is_active' => ['from' => null, 'to' => true],
            'is_founder' => ['from' => null, 'to' => true],
            'name' => 'changed',
            'password' => 'changed',
            'role' => ['from' => null, 'to' => 'admin'],
        ]);

    expect(json_encode($entry->changes))->not->toContain('ada@example.com')->not->toContain('Ada');
});

it('refuses a password shorter than 12 characters before anything is created', function () {
    runCreateFirstAdmin($this, 'short-pass')
        ->expectsOutputToContain('at least 12 characters')
        ->assertFailed();

    expect(StaffMember::query()->count())->toBe(0)
        ->and(app(AuditTrail::class)->all())->toBe([]);
});

it('refuses mismatched passwords before anything is created', function () {
    runCreateFirstAdmin($this, FIRST_ADMIN_PASSWORD, FIRST_ADMIN_PASSWORD.'x')
        ->expectsOutputToContain('do not match')
        ->assertFailed();

    expect(StaffMember::query()->count())->toBe(0);
});

it('refuses a missing name or an invalid email', function (array $options, string $message) {
    $this->artisan('staff:create-first-admin', $options)
        ->expectsOutputToContain($message)
        ->assertFailed();

    expect(StaffMember::query()->count())->toBe(0);
})->with([
    'no name' => [['--email' => 'ada@example.com'], '--name'],
    'bad email' => [['--name' => 'Ada', '--email' => 'not-an-email'], 'not valid'],
]);

it('refuses and creates nothing once a staff account exists', function () {
    runCreateFirstAdmin($this)->assertSuccessful();

    $this->artisan('staff:create-first-admin', ['--name' => 'Bola', '--email' => 'bola@example.com'])
        ->expectsOutputToContain('A staff account already exists')
        ->assertExitCode(1);

    expect(StaffMember::query()->count())->toBe(1)
        ->and(app(AuditTrail::class)->all())->toHaveCount(1);
});

it('refuses in the Action itself too, so a race between two runs creates one Admin', function () {
    app(CreateFirstAdmin::class)->handle('Ada', 'ada@example.com', FIRST_ADMIN_PASSWORD);

    expect(fn () => app(CreateFirstAdmin::class)->handle('Bola', 'bola@example.com', FIRST_ADMIN_PASSWORD))
        ->toThrow(FirstAdminRefused::class);

    expect(StaffMember::query()->count())->toBe(1);
});

it('refuses a weak password in the Action, whoever calls it', function () {
    expect(fn () => app(CreateFirstAdmin::class)->handle('Ada', 'ada@example.com', 'too-short'))
        ->toThrow(InvalidArgumentException::class, 'at least 12 characters');
});

it('refuses a weak password, in the command and in the Action alike', function (string $password, string $message) {
    runCreateFirstAdmin($this, $password)
        ->expectsOutputToContain($message)
        ->assertFailed();

    expect(StaffMember::query()->count())->toBe(0)
        ->and(fn () => app(CreateFirstAdmin::class)->handle('Ada', 'Ada@Example.com', $password))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'too short' => ['short-pass', 'at least 12 characters'],
    'one character repeated' => ['aaaaaaaaaaaaaa', 'one character repeated'],
    'contains the email local part' => ['my-ADA-password-2026', 'cannot contain the email address'],
]);

it('refuses a password containing the name, whatever the case', function () {
    expect(fn () => app(CreateFirstAdmin::class)->handle('Bolanle', 'founder@example.com', 'xx-BOLANLE-xx-2026'))
        ->toThrow(InvalidArgumentException::class, 'cannot contain the name');
});
