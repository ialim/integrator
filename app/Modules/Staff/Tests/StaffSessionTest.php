<?php

use App\Modules\Shared\Contracts\Clock;
use App\Modules\Staff\Actions\SetAuthenticatorSecret;
use App\Modules\Staff\Http\Middleware\EndStaffSessionAfter12Hours;
use App\Modules\Staff\Http\StaffSession;
use App\Modules\Staff\Http\StaffSessionRecords;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Support\StaffConsole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(Clock::class)->set('2026-10-26T08:00:00Z');
    StaffConsole::useConsole();
    $this->admin = StaffConsole::admin();
    StaffConsole::signIn($this, $this->admin);
});

afterEach(fn () => app(Clock::class)->reset());

function warningBanner(string $html): ?string
{
    return preg_match('/<div[^>]*data-session-warning[^>]*>/', $html, $match) === 1 ? $match[0] : null;
}

it('ends the session 12 hours after sign-in, at 21:00 WAT for a 09:00 WAT sign-in', function () {
    $html = $this->get('/console/today')->assertOk()->getContent();

    expect($html)->toContain('data-ends-at="2026-10-26T20:00:00Z"')
        ->and($html)->toContain('21:00 WAT');
});

it('keeps the warning hidden until 5 minutes are left', function () {
    app(Clock::class)->advance('11 hours 54 minutes');

    expect(warningBanner($this->get('/console/today')->assertOk()->getContent()))->toContain('hidden');
});

it('warns at 11 hours 55 minutes that the session ends in 5 minutes, with "Stay signed in"', function () {
    app(Clock::class)->advance('11 hours 55 minutes');

    $html = $this->get('/console/today')->assertOk()->getContent();

    expect(warningBanner($html))->not->toBeNull()->not->toContain('hidden')
        ->and($html)->toContain('Your session ends in <span data-session-minutes>5 minutes</span>')
        ->and($html)->toContain('role="alert"')
        ->and($html)->toContain('Stay signed in</button>');
});

it('signs out at 12 hours and sends the person to the sign-in page, then back where they were', function () {
    app(Clock::class)->advance('12 hours');

    $this->get('/console/today')->assertRedirect('/console/login');

    expect(Filament::auth()->check())->toBeFalse()
        ->and(session('url.intended'))->toEndWith('/console/today');

    $this->get('/console/login')->assertOk()
        ->assertSee('You were signed out because 12 hours had passed since you signed in.');
});

it('answers a Livewire request after 12 hours with a 401 the console script turns into a sign-out', function () {
    app(Clock::class)->advance('12 hours 1 minute');

    $this->withHeaders(['X-Livewire' => '1'])
        ->post('/console/session/extend', ['code' => StaffConsole::currentCode()])
        ->assertUnauthorized()
        ->assertHeader(EndStaffSessionAfter12Hours::ENDED_HEADER, 'ended');

    expect(Filament::auth()->check())->toBeFalse();

    $this->get('/console/login')->assertOk()
        ->assertSee('You were signed out because 12 hours had passed since you signed in.');
});

it('treats a staff session with no recorded sign-in time as ended', function () {
    $this->flushSession();
    $this->actingAs($this->admin, 'staff');

    $this->get('/console/today')->assertRedirect('/console/login');
});

it('starts a fresh 12 hours when "Stay signed in" gets the right authenticator code', function () {
    app(Clock::class)->advance('11 hours 56 minutes');

    $this->postJson('/console/session/extend', ['code' => StaffConsole::currentCode()])
        ->assertOk()
        ->assertJson(['ends_at' => '2026-10-27T07:56:00Z', 'ends_at_wat' => '08:56 WAT']);

    app(Clock::class)->advance('4 hours');

    expect(warningBanner($this->get('/console/today')->assertOk()->getContent()))->toContain('hidden');
});

it('keeps the old end time when the code is wrong, with the reason in words', function () {
    app(Clock::class)->advance('11 hours 56 minutes');

    $this->postJson('/console/session/extend', ['code' => StaffConsole::wrongCode()])
        ->assertUnprocessable()
        ->assertJsonPath('errors.code.0', 'That code is not right. Check your authenticator app and enter the code it shows now.');

    $this->postJson('/console/session/extend', ['code' => '12'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.code.0', 'Enter the 6-digit code from your authenticator app.');

    expect(app(StaffSession::class)->endsAt(session()->driver())?->toIso8601ZuluString())->toBe('2026-10-26T20:00:00Z');
});

it('throttles "Stay signed in" after 5 wrong codes, for 15 minutes', function () {
    app(Clock::class)->advance('11 hours 56 minutes');

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/console/session/extend', ['code' => StaffConsole::wrongCode()])->assertUnprocessable();
    }

    $this->postJson('/console/session/extend', ['code' => StaffConsole::currentCode()])
        ->assertStatus(429)
        ->assertJsonPath('errors.code.0', fn (string $message) => str_starts_with($message, 'Too many wrong codes.'));

    expect(RateLimiter::availableIn('staff:extend-session:'.$this->admin->id))->toBeGreaterThan(14 * 60);
});

it('refuses to extend outside the last 5 minutes, in words', function () {
    app(Clock::class)->advance('11 hours');

    $this->postJson('/console/session/extend', ['code' => StaffConsole::currentCode()])
        ->assertConflict()
        ->assertJsonPath('errors.code.0', 'You can stay signed in only in the last 5 minutes of your session.');
});

it('refuses a code sent as anything but text', function () {
    app(Clock::class)->advance('11 hours 56 minutes');

    $this->postJson('/console/session/extend', ['code' => ['123456']])
        ->assertUnprocessable()
        ->assertJsonPath('errors.code.0', 'Enter the 6-digit code from your authenticator app.');
});

it('refuses an accepted code used a second time and keeps the first end time', function () {
    app(Clock::class)->advance('11 hours 56 minutes');
    $code = StaffConsole::currentCode();

    $this->postJson('/console/session/extend', ['code' => $code])->assertOk();
    $first = app(StaffSession::class)->endsAt(session()->driver())?->toIso8601ZuluString();

    app(Clock::class)->advance('11 hours 56 minutes');

    $this->postJson('/console/session/extend', ['code' => $code])->assertUnprocessable();

    expect(app(StaffSession::class)->endsAt(session()->driver())?->toIso8601ZuluString())->toBe($first);
});

it('starts a new session id when "Stay signed in" succeeds', function () {
    app(Clock::class)->advance('11 hours 56 minutes');
    $before = session()->getId();

    $this->postJson('/console/session/extend', ['code' => StaffConsole::currentCode()])->assertOk();

    expect(session()->getId())->not->toBe($before);
});

it('shows no "Stay signed in" and refuses to extend for a member with no second factor', function () {
    $this->flushSession();
    $member = $this->admin;
    app(SetAuthenticatorSecret::class)->handle($member, null);
    StaffConsole::signIn($this, $member->refresh());
    app(Clock::class)->advance('11 hours 56 minutes');

    $html = $this->get('/console/multi-factor-authentication/set-up')->assertOk()->getContent();
    expect($html)->not->toContain('data-session-warning');

    $this->postJson('/console/session/extend', ['code' => StaffConsole::currentCode()])
        ->assertConflict()
        ->assertJsonPath('errors.code.0', 'Set up an authenticator app before you can stay signed in.');

    expect(RateLimiter::attempts('staff:extend-session:'.$member->id))->toBe(0);
});

it('never lets a session lapse from idleness before 12 hours', function () {
    $saved = [$_ENV['SESSION_LIFETIME'] ?? null, $_SERVER['SESSION_LIFETIME'] ?? null];
    $_ENV['SESSION_LIFETIME'] = $_SERVER['SESSION_LIFETIME'] = '120';
    putenv('SESSION_LIFETIME=120');

    try {
        $config = require config_path('session.php');
    } finally {
        [$_ENV['SESSION_LIFETIME'], $_SERVER['SESSION_LIFETIME']] = $saved;
        putenv('SESSION_LIFETIME');
    }

    expect($config['lifetime'])->toBeGreaterThanOrEqual(720)
        ->and(config('session.lifetime'))->toBeGreaterThanOrEqual(720);
});

it('destroys the old session when "Stay signed in" starts a new one, and records the new id at once', function () {
    $this->get('/console/today')->assertOk();
    app(Clock::class)->advance('11 hours 56 minutes');
    $before = session()->getId();

    expect(DB::table('staff_sessions')->pluck('session_id')->all())->toBe([$before]);

    $this->postJson('/console/session/extend', ['code' => StaffConsole::currentCode()])->assertOk();

    expect(session()->getId())->not->toBe($before)
        ->and(DB::table('staff_sessions')->pluck('session_id')->all())->toBe([session()->getId()]);
});

it('rotates a staff session with the old one destroyed in the session store, not left behind signed in', function () {
    $handler = new ArraySessionHandler(120);
    $store = new Store('integrator_session', $handler);
    $store->start();
    $store->put('signed_in', true);
    $store->save();
    $old = $store->getId();

    expect($handler->read($old))->not->toBe('');

    $store->start();

    expect(app(StaffSessionRecords::class)->rotate($store, $this->admin->id))->toBeTrue()
        ->and($store->getId())->not->toBe($old)
        ->and($handler->read($old))->toBe('')
        ->and($store->get('signed_in'))->toBeTrue()
        ->and(DB::table('staff_sessions')->where('session_id', $store->getId())->value('staff_member_id'))->toBe($this->admin->id);
});

it('removes the session\'s staff record when the member signs out', function () {
    $this->get('/console/today')->assertOk();

    expect(DB::table('staff_sessions')->count())->toBe(1);

    $this->post('/console/logout')->assertRedirect();

    expect(DB::table('staff_sessions')->count())->toBe(0);
});
