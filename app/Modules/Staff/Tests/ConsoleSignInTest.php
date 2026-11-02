<?php

use App\Modules\Shared\Alerts\FounderAlert;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Queries\AuditTrail;
use App\Modules\Staff\Filament\Pages\SignIn;
use App\Modules\Staff\Http\StaffSession;
use Database\Factories\Staff\FirstAdminFactory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\Support\StaffConsole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(Clock::class)->set('2026-10-26T09:00:00Z');
    StaffConsole::useConsole();
});

afterEach(fn () => app(Clock::class)->reset());

function firstStep(): mixed
{
    return Livewire::test(SignIn::class)
        ->set('data.email', FirstAdminFactory::EMAIL)
        ->set('data.password', FirstAdminFactory::PASSWORD)
        ->call('authenticate');
}

it('sends a signed-out visitor to the sign-in page', function () {
    $this->get('/console')->assertRedirect();
    $this->get('/console/today')->assertRedirect('/console/login');
});

it('renders the sign-in page with one top heading, labelled fields and no "remember me"', function () {
    $html = $this->get('/console/login')->assertOk()->getContent();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('Sign in to the staff console')
        ->and($html)->toContain('Email address')
        ->and($html)->toContain('Password')
        ->and($html)->not->toContain('Remember me')
        ->and($html)->not->toContain('fonts.bunny.net')
        ->and($html)->not->toContain('Access denied');
});

it('keeps a staff member on the second step after a correct first step', function () {
    StaffConsole::admin();

    firstStep()
        ->assertHasNoErrors()
        ->assertNotSet('userUndertakingMultiFactorAuthentication', null)
        ->assertSee('Enter your authenticator code');

    expect(Filament::auth()->check())->toBeFalse();
});

it('refuses a wrong password in words beside the email field', function () {
    StaffConsole::admin();

    Livewire::test(SignIn::class)
        ->set('data.email', FirstAdminFactory::EMAIL)
        ->set('data.password', 'not-the-password')
        ->call('authenticate')
        ->assertHasErrors(['data.email'])
        ->assertSee('The email address or password is not right.');

    expect(Filament::auth()->check())->toBeFalse();
});

it('stays on the second step with an error beside the field when the code is wrong', function () {
    StaffConsole::admin();

    firstStep()
        ->set('data.multiFactor.app.code', StaffConsole::wrongCode())
        ->call('authenticate')
        ->assertHasErrors(['data.multiFactor.app.code'])
        ->assertSee('That code is not right.')
        ->assertNotSet('userUndertakingMultiFactorAuthentication', null);

    expect(Filament::auth()->check())->toBeFalse();
});

it('throttles repeated wrong codes', function () {
    StaffConsole::admin();

    $page = firstStep();

    foreach (range(1, 5) as $attempt) {
        $page->set('data.multiFactor.app.code', StaffConsole::wrongCode())->call('authenticate');
    }

    $page->set('data.multiFactor.app.code', StaffConsole::currentCode())
        ->call('authenticate')
        ->assertHasErrors(['data.multiFactor.app.code'])
        ->assertSee('Too many attempts.');

    expect(Filament::auth()->check())->toBeFalse();
});

it('signs in after both steps and starts the 12 hours', function () {
    $admin = StaffConsole::admin();

    firstStep()
        ->set('data.multiFactor.app.code', StaffConsole::currentCode())
        ->call('authenticate')
        ->assertHasNoErrors()
        ->assertRedirect('/console');

    expect(Filament::auth()->id())->toBe($admin->id)
        ->and(session(StaffSession::KEY))->toBe(app(Clock::class)->now()->getTimestamp());
});

it('signs in with a recovery code, which is then used up and audited', function () {
    $admin = StaffConsole::admin();
    StaffConsole::provider()->saveRecoveryCodes($admin, ['recovery-one', 'recovery-two']);

    firstStep()
        ->call('mountAction', 'useRecoveryCode')
        ->set('data.multiFactor.app.useRecoveryCode', true)
        ->set('data.multiFactor.app.recoveryCode', 'recovery-one')
        ->call('authenticate')
        ->assertHasNoErrors();

    expect(Filament::auth()->id())->toBe($admin->id);

    $codes = $admin->refresh()->getAppAuthenticationRecoveryCodes();

    expect($codes)->toHaveCount(1)
        ->and(Hash::check('recovery-two', $codes[0]))->toBeTrue();

    $actions = array_map(fn ($entry) => $entry->action, app(AuditTrail::class)->forSubject('staff_members', $admin->id));

    expect($actions)->toBe([
        'staff.first_admin_created',
        'staff.authenticator_set',
        'staff.recovery_codes_set',
        'staff.recovery_code_used',
    ]);
});

it('sends a staff member without a second factor to set one up, and nowhere else', function () {
    $admin = StaffConsole::admin(withAuthenticator: false);

    firstStep()->assertHasNoErrors();
    expect(Filament::auth()->id())->toBe($admin->id);

    StaffConsole::signIn($this, $admin);

    $this->get('/console/today')->assertRedirect('/console/multi-factor-authentication/set-up');

    $html = $this->get('/console/multi-factor-authentication/set-up')->assertOk()->getContent();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('Set up your authenticator app');
});

it('saves the second factor Filament sets up through Staff Actions, audited with the member as actor', function () {
    $admin = StaffConsole::admin(withAuthenticator: false);
    $provider = StaffConsole::provider();

    $provider->saveSecret($admin, StaffConsole::SECRET);
    $provider->saveRecoveryCodes($admin, ['code-one']);

    $entries = array_slice(app(AuditTrail::class)->forSubject('staff_members', $admin->id), 1);

    expect(array_map(fn ($entry) => [$entry->actorType, $entry->actorId, $entry->action, $entry->changes], $entries))->toBe([
        ['staff', $admin->id, 'staff.authenticator_set', ['app_authentication_secret' => 'changed']],
        ['staff', $admin->id, 'staff.recovery_codes_set', ['app_authentication_recovery_codes' => 'changed']],
    ]);

    expect($admin->refresh()->getAppAuthenticationSecret())->toBe(StaffConsole::SECRET);
});

it('refuses a deactivated staff member at the first step, with the same words as a wrong password', function () {
    StaffConsole::deactivate(StaffConsole::admin());

    firstStep()
        ->assertHasErrors(['data.email'])
        ->assertSee('The email address or password is not right.')
        ->assertSet('userUndertakingMultiFactorAuthentication', null);

    expect(Filament::auth()->check())->toBeFalse();
});

it('refuses the next request of a signed-in member who has been deactivated', function () {
    $admin = StaffConsole::admin();
    $this->withSession([
        Auth::guard('staff')->getName() => $admin->id,
        StaffSession::KEY => app(Clock::class)->now()->getTimestamp(),
    ]);
    $this->get('/console/today')->assertOk();

    StaffConsole::deactivate($admin);
    Auth::guard('staff')->forgetUser(); // as a fresh request would, the member is read again

    $this->get('/console/today')->assertForbidden()->assertDontSee('Access denied');
});

it('reaches the second step with a mixed-case email', function () {
    StaffConsole::admin();

    Livewire::test(SignIn::class)
        ->set('data.email', 'Founder@EXAMPLE.test')
        ->set('data.password', FirstAdminFactory::PASSWORD)
        ->call('authenticate')
        ->assertHasNoErrors()
        ->assertNotSet('userUndertakingMultiFactorAuthentication', null);
});

it('throttles repeated wrong passwords in words beside the email field', function () {
    StaffConsole::admin();
    $page = Livewire::test(SignIn::class)->set('data.email', FirstAdminFactory::EMAIL);

    foreach (range(1, 6) as $attempt) {
        $page->set('data.password', 'not-the-password')->call('authenticate');
    }

    $page->assertHasErrors(['data.email'])->assertSee('Too many attempts.');
});

it('re-hashes the password through a Staff Action when the hashing cost rises', function () {
    $admin = StaffConsole::admin();
    config(['hashing.bcrypt.rounds' => 5]);
    app('hash')->forgetDrivers();

    firstStep()
        ->set('data.multiFactor.app.code', StaffConsole::currentCode())
        ->call('authenticate')
        ->assertHasNoErrors();

    $rehashes = array_filter(
        app(AuditTrail::class)->forSubject('staff_members', $admin->id),
        fn ($entry) => $entry->action === 'staff.password_rehashed',
    );

    expect($rehashes)->toHaveCount(1)
        ->and(array_values($rehashes)[0]->changes)->toBe(['password' => 'changed'])
        ->and(Hash::check(FirstAdminFactory::PASSWORD, $admin->refresh()->password))->toBeTrue();
});

it('alerts the founder when an Admin sets up an authenticator app, naming whose', function () {
    config(['alerts.founder_email' => 'founder-alerts@example.test']);
    Mail::fake();

    $admin = StaffConsole::admin();

    Mail::assertSent(FounderAlert::class, function (FounderAlert $mail) use ($admin) {
        $text = $mail->render();

        return str_contains($text, 'An Admin set up an authenticator app')
            && str_contains($text, $admin->id)
            && str_contains($text, 'WAT');
    });
});

it('does not audit or alert a second factor save that changes nothing', function () {
    $admin = StaffConsole::admin();
    $before = count(app(AuditTrail::class)->forSubject('staff_members', $admin->id));

    $admin->saveAppAuthenticationSecret(StaffConsole::SECRET);

    expect(app(AuditTrail::class)->forSubject('staff_members', $admin->id))->toHaveCount($before);
});

it('refuses a staff session on an authenticated /api/v1 route with the JSON error envelope', function () {
    Route::middleware(['api', 'auth:sanctum'])->get('/api/v1/_probe/me', fn () => ['id' => auth()->id()]);
    $admin = StaffConsole::admin();

    StaffConsole::signIn($this, $admin);

    $this->get('/console/today')->assertOk();

    $this->withHeader('Referer', 'http://localhost/console/today')
        ->getJson('/api/v1/_probe/me')
        ->assertUnauthorized()
        ->assertJsonPath('code', 'unauthenticated')
        ->assertJsonStructure(['message', 'errors', 'code', 'server_time']);
});

it('never shows a console page to a session that is not a staff session', function () {
    $this->withSession([Auth::guard('web')->getName() => '01890000-0000-7000-8000-000000000000'])
        ->get('/console/today')
        ->assertRedirect('/console/login');

    expect(Filament::auth()->check())->toBeFalse();
});
