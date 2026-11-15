<?php

use App\Modules\Shared\Contracts\Clock;
use App\Modules\Staff\Actions\AcceptInvitation;
use App\Modules\Staff\Actions\DeactivateStaffAccount;
use App\Modules\Staff\Actions\ResendInvitation;
use App\Modules\Staff\Actions\ResetSignIn;
use App\Modules\Staff\Http\Middleware\EndStaffSessionAfter12Hours;
use App\Modules\Staff\Http\Middleware\SignOutInactiveStaff;
use App\Modules\Staff\Http\RevokedStaffSessions;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use Filament\Facades\Filament;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\Support\StaffConsole;

/*
 * Retro finding X1: a member an Admin deactivates, resets or re-invites while
 * signed in has their stored session deleted. Their next request must end
 * with the "no longer active" message, not Livewire's 419. These run on the
 * production stores (database sessions and cache, not phpunit.xml's arrays),
 * and each request sees only what the database and the session cookie hold,
 * as a browser's would.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(Clock::class)->set('2026-10-26T08:00:00Z');
    config(['session.driver' => 'database', 'cache.default' => 'database']);
    app()->forgetInstance('cache.store');
    Cache::clearResolvedInstances();
    StaffConsole::useConsole();
    $this->admin = StaffConsole::admin();
});

afterEach(fn () => app(Clock::class)->reset());

/** Forgets the signed-in user and the session held in memory, as between two browser requests. */
function nextRequest(): void
{
    app('auth')->forgetGuards();
    app('session')->forgetDrivers();
    app()->forgetInstance('session.store');
    // Livewire keeps per-request state that a real request starts without.
    Livewire::flushState();
}

/**
 * Signs $member in to a fresh database session and opens $path on it, as the
 * sign-in would leave it. Returns the session id (the browser's cookie).
 */
function openSession(StaffMember $member, string $path = '/console/today'): string
{
    nextRequest();
    Filament::auth()->login($member);
    test()->get($path);
    $sessionId = app('session.store')->getId();

    expect(DB::table('sessions')->where('id', $sessionId)->exists())->toBeTrue()
        ->and(DB::table('staff_sessions')->where('session_id', $sessionId)->value('staff_member_id'))->toBe($member->id);

    return $sessionId;
}

/**
 * A request from the browser holding only the session cookie $sessionId (none
 * for null). Returns the response and the session id the request leaves.
 *
 * @param  array<string, mixed>|null  $json
 * @return array{TestResponse, string}
 */
function asBrowser(?string $sessionId, string $method, string $uri, ?array $json = null, array $server = []): array
{
    nextRequest();
    $name = (string) config('session.cookie');
    $cookies = $sessionId === null ? [] : [$name => encrypt(CookieValuePrefix::create($name, app('encrypter')->getKey()).$sessionId, false)];

    if ($json !== null) {
        $server += ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
    }

    $response = test()->call($method, $uri, [], $cookies, [], $server, $json === null ? null : json_encode($json));

    return [$response, app('session.store')->getId()];
}

/**
 * The page's Livewire update for its first component, as the open page's
 * script would send it, with the token it was rendered with.
 *
 * @return array{TestResponse, string}
 */
function livewireUpdate(string $sessionId, string $html, array $server = []): array
{
    $snapshot = html_entity_decode((string) str($html)->betweenFirst('wire:snapshot="', '"'), ENT_QUOTES);

    return asBrowser($sessionId, 'POST', Livewire::getUpdateUri(), [
        '_token' => 'the-old-token',
        'components' => [['snapshot' => $snapshot, 'updates' => [], 'calls' => []]],
    ], ['HTTP_X_LIVEWIRE' => '1'] + $server);
}

function anInvitee(StaffMember $admin): StaffMember
{
    $member = StaffConsole::invite($admin);

    return app(AcceptInvitation::class)->handle(StaffConsole::invitationToken($member), StaffConsole::INVITEE_PASSWORD)->refresh();
}

dataset('revocations', [
    'deactivated' => [fn (StaffMember $admin) => StaffConsole::active($admin), fn (StaffMember $admin, StaffMember $member) => app(DeactivateStaffAccount::class)->handle($admin, $member->id)],
    'sign-in reset' => [fn (StaffMember $admin) => StaffConsole::active($admin), fn (StaffMember $admin, StaffMember $member) => app(ResetSignIn::class)->handle($admin, $member->id)],
    're-invited' => [fn (StaffMember $admin) => anInvitee($admin), fn (StaffMember $admin, StaffMember $member) => app(ResendInvitation::class)->handle($admin, $member->id)],
]);

it('answers the open page\'s next Livewire call with the ended 401, then the sign-in page says why', function (Closure $makeMember, Closure $revoke) {
    $member = $makeMember($this->admin);
    $sessionId = openSession($member);

    // The page as the member has it open, read on the stored session alone.
    [$page] = asBrowser($sessionId, 'GET', '/console/today');
    $html = (string) $page->getContent();

    if ($member->status->value === 'active') {
        $page->assertOk();
        livewireUpdate($sessionId, $html)[0]->assertOk();
    } else {
        // An invitee is still setting up: the console sends them on to that step.
        $html = (string) asBrowser($sessionId, 'GET', (string) $page->headers->get('Location', '/console/today'))[0]->getContent();
    }

    $revoke($this->admin, $member);
    Log::spy();

    /** @var TestResponse $response */
    [$response, $newId] = livewireUpdate($sessionId, $html);

    $response->assertUnauthorized()
        ->assertHeader(EndStaffSessionAfter12Hours::ENDED_HEADER, 'ended')
        ->assertJson(['message' => SignOutInactiveStaff::MESSAGE]);
    Log::shouldHaveReceived('info')->with('staff.session_ended', ['staff_id' => $member->id, 'reason' => 'session_revoked']);

    expect($newId)->not->toBe($sessionId);

    asBrowser($newId, 'GET', '/console/login')[0]->assertOk()->assertSee(SignOutInactiveStaff::MESSAGE);
})->with('revocations');

it('sends a page load after the revoke to sign-in, which says why once', function (Closure $makeMember, Closure $revoke) {
    $member = $makeMember($this->admin);
    $sessionId = openSession($member);

    $revoke($this->admin, $member);

    [$response, $newId] = asBrowser($sessionId, 'GET', '/console/staff');
    $response->assertRedirect('/console/login');

    [$signIn, $afterId] = asBrowser($newId, 'GET', '/console/login');
    $signIn->assertOk()->assertSee(SignOutInactiveStaff::MESSAGE);

    asBrowser($afterId, 'GET', '/console/login')[0]->assertOk()->assertDontSee(SignOutInactiveStaff::MESSAGE);
})->with('revocations');

it('forgets a revoked id once used, so the next request on it is any guest\'s', function () {
    $member = StaffConsole::active($this->admin);
    $sessionId = openSession($member);
    StaffConsole::deactivate($member);

    asBrowser($sessionId, 'GET', '/console/today')[0]->assertRedirect('/console/login');

    expect(app(RevokedStaffSessions::class)->pull($sessionId))->toBeNull();

    [$again] = asBrowser($sessionId, 'GET', '/console/today');
    $again->assertRedirect('/console/login');

    expect(app('session.store')->get('staff_session.ended'))->toBeNull();
});

it('leaves other members\' sessions and guests as they were', function () {
    $member = StaffConsole::active($this->admin);
    $other = StaffConsole::active($this->admin, StaffRole::Operations, 'Chidi Eze');
    $memberSession = openSession($member);
    $otherSession = openSession($other);

    StaffConsole::deactivate($member);

    asBrowser($otherSession, 'GET', '/console/today')[0]->assertOk();

    asBrowser(null, 'GET', '/console/login')[0]->assertOk()->assertDontSee(SignOutInactiveStaff::MESSAGE);

    expect(app(RevokedStaffSessions::class)->pull($memberSession))->toBe($member->id);
});

it('remembers nothing when the revoking Action rolls back, whatever the cache store', function (string $store) {
    config(['cache.default' => $store]);
    app()->forgetInstance('cache.store');
    Cache::clearResolvedInstances();

    $member = StaffConsole::active($this->admin);
    $sessionId = openSession($member);

    expect(fn () => DB::transaction(function () use ($member) {
        app(DeactivateStaffAccount::class)->handle($this->admin, $member->id);

        throw new RuntimeException('Rolled back after the revoke.');
    }))->toThrow(RuntimeException::class);

    expect(app(RevokedStaffSessions::class)->pull($sessionId))->toBeNull()
        ->and(DB::table('sessions')->where('id', $sessionId)->exists())->toBeTrue();
})->with(['database', 'array']);

/** The forgery check as it runs outside tests (it lets every request through under unit tests). */
function withForgeryCheck(): void
{
    app()->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
    {
        protected function runningUnitTests()
        {
            return false;
        }
    });
}

it('ends a revoked Livewire call with its reason even with the forgery check on', function () {
    $member = StaffConsole::active($this->admin);
    $sessionId = openSession($member);
    $html = (string) asBrowser($sessionId, 'GET', '/console/today')[0]->assertOk()->getContent();
    StaffConsole::deactivate($member);
    withForgeryCheck();

    // A browser's same-origin call passes the check without a token, then meets the revoke.
    [$response, $newId] = livewireUpdate($sessionId, $html, ['HTTP_SEC_FETCH_SITE' => 'same-origin']);

    $response->assertUnauthorized()
        ->assertHeader(EndStaffSessionAfter12Hours::ENDED_HEADER, 'ended')
        ->assertJson(['message' => SignOutInactiveStaff::MESSAGE]);
    asBrowser($newId, 'GET', '/console/login')[0]->assertOk()->assertSee(SignOutInactiveStaff::MESSAGE);
});

it('ends a revoked console POST with its reason before the forgery check refuses its old token', function () {
    $member = StaffConsole::active($this->admin);
    $sessionId = openSession($member);
    StaffConsole::deactivate($member);
    withForgeryCheck();

    [$response, $newId] = asBrowser($sessionId, 'POST', '/console/session/extend', ['_token' => 'the-old-token', 'code' => StaffConsole::currentCode()]);

    $response->assertUnauthorized()
        ->assertHeader(EndStaffSessionAfter12Hours::ENDED_HEADER, 'ended')
        ->assertJson(['message' => SignOutInactiveStaff::MESSAGE]);
    asBrowser($newId, 'GET', '/console/login')[0]->assertOk()->assertSee(SignOutInactiveStaff::MESSAGE);
});

it('still refuses a forged console POST on a session nobody revoked', function () {
    $member = StaffConsole::active($this->admin);
    $sessionId = openSession($member);
    withForgeryCheck();

    asBrowser($sessionId, 'POST', '/console/session/extend', ['_token' => 'not-the-token', 'code' => StaffConsole::currentCode()])[0]
        ->assertStatus(419);
});
