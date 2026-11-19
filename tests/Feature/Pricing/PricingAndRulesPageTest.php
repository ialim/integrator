<?php

use App\Modules\Catalogue\Actions\CreateProductRange;
use App\Modules\Catalogue\Queries\ProductRanges;
use App\Modules\Pricing\Actions\SetRangeMarkup;
use App\Modules\Pricing\Filament\PricingAndRules;
use App\Modules\Pricing\Queries\PricingRules;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Staff\Models\StaffRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\StaffConsole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(Clock::class)->set('2026-11-19T09:00:00Z');
    StaffConsole::useConsole();
});

afterEach(fn () => app(Clock::class)->reset());

it('shows an Admin the table captioned Platform Markup by Product Range, the floor, and the change buttons', function () {
    $admin = StaffConsole::admin();
    app(CreateProductRange::class)->handle($admin, 'IP cameras', 'ncc_type_approval');
    StaffConsole::signIn($this, $admin);

    $html = $this->get('/console/pricing-and-rules')->assertOk()->getContent();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('Pricing and rules')
        ->and($html)->toContain('Every change asks for a reason and is audited.')
        ->and($html)->toContain('Platform Markup by Product Range')
        ->and($html)->toContain('<th scope="col">Product Range</th>')
        ->and($html)->toContain('IP cameras')
        ->and($html)->toContain('NCC type approval')
        ->and($html)->toContain('25%')
        ->and($html)->toContain('8% over Supplier Cost')
        ->and($html)->toContain('Add a Product Range')
        ->and($html)->toContain('Change markup')
        ->and($html)->toContain('Change the floor')
        ->and($html)->toContain('History')
        ->and($html)->not->toContain(PricingAndRules::READ_ONLY);
});

it('puts the page in the rail\'s Rules group for Admin, Finance and Operations', function (StaffRole $role) {
    $admin = StaffConsole::admin();
    $member = $role === StaffRole::Admin ? $admin : StaffConsole::active($admin, $role, 'Not An Admin');
    StaffConsole::signIn($this, $member);

    $this->get('/console/today')->assertOk()
        ->assertSee('data-group-label="Rules"', false)
        ->assertSee('href="'.url('/console/pricing-and-rules').'"', false);
})->with([StaffRole::Admin, StaffRole::Finance, StaffRole::Operations]);

it('shows Finance and Operations the same values read-only, with no change buttons at all', function (StaffRole $role) {
    $admin = StaffConsole::admin();
    app(CreateProductRange::class)->handle($admin, 'IP cameras', 'none');
    StaffConsole::signIn($this, StaffConsole::active($admin, $role, 'Not An Admin'));

    $this->get('/console/pricing-and-rules')->assertOk()
        ->assertSee(PricingAndRules::READ_ONLY)
        ->assertSee('IP cameras')
        ->assertSee('25%')
        ->assertSee('8% over Supplier Cost')
        ->assertSee('History')
        ->assertDontSee('Add a Product Range')
        ->assertDontSee('Change markup')
        ->assertDontSee('Change the floor')
        ->assertDontSee('Retire');
})->with([StaffRole::Finance, StaffRole::Operations]);

it('adds a range from the dialog and says so', function () {
    $admin = StaffConsole::admin();
    $this->actingAs($admin, 'staff');

    Livewire::test(PricingAndRules::class)
        ->callAction('create', data: ['name' => 'Recorders', 'compliance' => 'none'])
        ->assertHasNoActionErrors()
        ->assertSet('notice', 'Added Recorders, Active, at a 25% markup.')
        ->assertSee('Recorders');

    expect(app(ProductRanges::class)->all())->toHaveCount(1);
});

it('refuses a negative markup beside the field, in words', function () {
    $admin = StaffConsole::admin();
    $range = app(CreateProductRange::class)->handle($admin, 'IP cameras', 'none');
    $this->actingAs($admin, 'staff');

    Livewire::test(PricingAndRules::class)
        ->callAction('markup', data: ['markup' => '-1', 'reason' => 'Testing'], arguments: ['range' => $range->id])
        ->assertHasActionErrors(['markup']);

    expect(app(PricingRules::class)->markupFor($range->id)?->words())->toBe('25%');
});

it('changes a markup from the dialog, and the range\'s History shows the old value, the new one and the reason', function () {
    $admin = StaffConsole::admin();
    $range = app(CreateProductRange::class)->handle($admin, 'IP cameras', 'none');
    $this->actingAs($admin, 'staff');

    Livewire::test(PricingAndRules::class)
        ->callAction('markup', data: ['markup' => '30', 'reason' => 'Matching a competitor'], arguments: ['range' => $range->id])
        ->assertHasNoActionErrors()
        ->assertSet('notice', 'The markup for IP cameras is now 30%.')
        ->mountAction('history', ['range' => $range->id])
        ->assertMountedActionModalSee('History of IP cameras')
        ->assertMountedActionModalSee('Markup set')
        ->assertMountedActionModalSee('Markup percent: from 25 to 30')
        ->assertMountedActionModalSee('Reason: Matching a competitor')
        ->assertMountedActionModalSee('Range created');
});

it('shows the floor\'s History', function () {
    $admin = StaffConsole::admin();
    $this->actingAs($admin, 'staff');

    Livewire::test(PricingAndRules::class)
        ->callAction('floor', data: ['floor' => '10', 'reason' => 'Supplier costs rose'])
        ->assertSet('notice', 'The margin floor is now 10% over Supplier Cost.')
        ->mountAction('floorHistory')
        ->assertMountedActionModalSee('Margin floor set')
        ->assertMountedActionModalSee('Floor percent: from 8 to 10')
        ->assertMountedActionModalSee('Reason: Supplier costs rose');
});

it('keeps a retired range in the table, labelled Retired, with only its History', function () {
    $admin = StaffConsole::admin();
    $range = app(CreateProductRange::class)->handle($admin, 'Cabling', 'none');
    $this->actingAs($admin, 'staff');

    Livewire::test(PricingAndRules::class)
        ->callAction('retire', data: ['reason' => 'Folded into Accessories'], arguments: ['range' => $range->id])
        ->assertSet('notice', 'Retired Cabling. It stays in the table with its History.')
        ->assertSee('Cabling')
        ->assertSee('Retired')
        ->assertDontSee('Change markup');
});

it('leaves a Finance member no change to call: the server refuses the write', function () {
    $admin = StaffConsole::admin();
    $range = app(CreateProductRange::class)->handle($admin, 'IP cameras', 'none');
    $finance = StaffConsole::active($admin, StaffRole::Finance, 'Bola Finance');
    $this->actingAs($finance, 'staff');

    Livewire::test(PricingAndRules::class)
        ->assertActionHidden('markup', ['range' => $range->id])
        ->assertActionHidden('retire', ['range' => $range->id])
        ->assertActionHidden('floor')
        ->assertActionHidden('create')
        ->assertActionVisible('history', ['range' => $range->id]);

    expect(app(PricingRules::class)->markupFor($range->id)?->words())->toBe('25%');
    expect(fn () => app(SetRangeMarkup::class)->handle($finance, $range->id, '30', 'Because'))->toThrow(AuthorizationException::class);
});
