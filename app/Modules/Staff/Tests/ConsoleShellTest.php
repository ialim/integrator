<?php

use App\Modules\Shared\Contracts\Clock;
use App\Modules\Staff\Filament\LedgerTokens;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\StaffConsole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(Clock::class)->set('2026-10-26T13:05:00Z');
    StaffConsole::useConsole();
});

afterEach(fn () => app(Clock::class)->reset());

it('opens on the empty Today page once both steps have passed', function () {
    StaffConsole::signIn($this, StaffConsole::admin());

    $this->get('/console')->assertRedirect('/console/today');

    $html = $this->get('/console/today')->assertOk()->getContent();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('Today')
        ->and($html)->toContain('Nothing needs you yet');
});

it('shows the wordmark, "Staff console", the WAT time and the person\'s name and role in the top bar', function () {
    StaffConsole::signIn($this, StaffConsole::admin());

    $this->get('/console/today')
        ->assertOk()
        ->assertSee('integrator')
        ->assertSee('Staff console')
        ->assertSee('14:05 WAT')
        ->assertSee('datetime="2026-10-26T13:05:00Z"', false)
        ->assertSee('Ada Founder')
        ->assertSee('Admin');
});

it('has a skip link, the phone notice and a named menu button for narrow windows', function () {
    StaffConsole::signIn($this, StaffConsole::admin());

    $this->get('/console/today')
        ->assertOk()
        ->assertSee('href="#fi-main-content"', false)
        ->assertSee('Skip to the page')
        ->assertSee('The staff console is made for a desktop.')
        ->assertSee('Open the menu');
});

it('loads no web font and offers no dark mode', function () {
    StaffConsole::signIn($this, StaffConsole::admin());

    $html = $this->get('/console/today')->assertOk()->getContent();

    expect($html)->not->toContain('fonts.bunny.net')
        ->and($html)->not->toContain('fonts.googleapis.com')
        ->and($html)->not->toContain('.woff')
        ->and($html)->not->toContain('fonts/filament')
        ->and($html)->toContain("localStorage.setItem('filament:theme:mode', 'light')");
});

it('builds the console palette from the one Ledger token file', function () {
    $tokens = file_get_contents(resource_path('tokens/ledger.css'));

    expect(LedgerTokens::color('green'))->toBe('#153d2e')
        ->and($tokens)->toContain('--ledger-green: #153d2e;')
        ->and(file_get_contents(resource_path('css/filament/console/theme.css')))->toContain("@import '../../../tokens/ledger.css';")
        ->and(file_get_contents(resource_path('app/app.css')))->toContain("@import '../tokens/ledger.css';");
});
