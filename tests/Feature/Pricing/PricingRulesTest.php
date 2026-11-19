<?php

use App\Modules\Catalogue\Actions\CreateProductRange;
use App\Modules\Catalogue\Actions\RetireProductRange;
use App\Modules\Pricing\Actions\InvalidPricingInput;
use App\Modules\Pricing\Actions\Percentage;
use App\Modules\Pricing\Actions\SetMarginFloor;
use App\Modules\Pricing\Actions\SetRangeMarkup;
use App\Modules\Pricing\Queries\PricingRules;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Queries\AuditTrail;
use App\Modules\Staff\Capabilities\CapabilityRefused;
use App\Modules\Staff\Models\StaffRole;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\StaffConsole;

uses(RefreshDatabase::class);

beforeEach(fn () => app(Clock::class)->set('2026-11-19T09:00:00Z'));

afterEach(fn () => app(Clock::class)->reset());

it('starts with an 8% margin floor, stored as a database row', function () {
    $floor = app(PricingRules::class)->marginFloor();

    expect($floor->words())->toBe('8%')
        ->and($floor->percent)->toBe('8.0000')
        ->and(DB::table('pricing_settings')->where('key', 'margin_floor')->count())->toBe(1);
});

it('changes a markup with a reason; History holds the old value, the new one and the reason', function () {
    $admin = StaffConsole::admin();
    $range = app(CreateProductRange::class)->handle($admin, 'IP cameras', 'none');

    $markup = app(SetRangeMarkup::class)->handle($admin, $range->id, '30', 'Matching a competitor');
    $latest = app(AuditTrail::class)->latestFor('range_markups', $markup->id);

    expect($markup->words())->toBe('30%')
        ->and($latest?->action)->toBe('pricing.markup_set')
        ->and($latest?->changes)->toEqual(['markup_percent' => ['from' => '25', 'to' => '30'], 'reason' => 'Matching a competitor']);
});

it('needs a reason for every markup change after the first', function () {
    $admin = StaffConsole::admin();
    $range = app(CreateProductRange::class)->handle($admin, 'IP cameras', 'none');

    try {
        app(SetRangeMarkup::class)->handle($admin, $range->id, '30', '');
        $this->fail('A change without a reason was saved.');
    } catch (InvalidPricingInput $e) {
        expect($e->field)->toBe('reason');
    }

    expect(app(PricingRules::class)->markupFor($range->id)?->words())->toBe('25%');
});

it('refuses a markup below zero in a sentence, and the database refuses it too', function () {
    $admin = StaffConsole::admin();
    $range = app(CreateProductRange::class)->handle($admin, 'IP cameras', 'none');

    expect(fn () => app(SetRangeMarkup::class)->handle($admin, $range->id, '-1', 'Testing'))
        ->toThrow(InvalidPricingInput::class, 'A markup cannot be below zero.');

    expect(fn () => DB::transaction(fn () => DB::table('range_markups')->where('product_range_id', $range->id)->update(['markup_percent' => '-1'])))
        ->toThrow(QueryException::class, 'range_markups_markup_percent_check');

    expect(app(PricingRules::class)->markupFor($range->id)?->words())->toBe('25%');
});

it('changes the floor with a reason, audited, and refuses one below zero', function () {
    $admin = StaffConsole::admin();

    expect(fn () => app(SetMarginFloor::class)->handle($admin, '-0.5', 'Testing'))
        ->toThrow(InvalidPricingInput::class, 'The margin floor cannot be below zero.');

    $floor = app(SetMarginFloor::class)->handle($admin, '10', 'Supplier costs rose');
    $latest = app(AuditTrail::class)->latestFor('pricing_settings', $floor->id);

    expect($floor->words())->toBe('10%')
        ->and($latest?->action)->toBe('pricing.margin_floor_set')
        ->and($latest?->changes)->toEqual(['floor_percent' => ['from' => '8', 'to' => '10'], 'reason' => 'Supplier costs rose']);

    expect(fn () => DB::transaction(fn () => DB::table('pricing_settings')->update(['percent' => '-1'])))->toThrow(QueryException::class, 'pricing_settings_percent_check');
});

it('refuses a retired range\'s markup change', function () {
    $admin = StaffConsole::admin();
    $range = app(CreateProductRange::class)->handle($admin, 'Cabling', 'none');
    app(RetireProductRange::class)->handle($admin, $range->id, 'Folded into Accessories');

    expect(fn () => app(SetRangeMarkup::class)->handle($admin, $range->id, '30', 'Late change'))
        ->toThrow(InvalidPricingInput::class, 'Cabling is retired, so its markup cannot change.');
});

it('refuses Finance and Operations on the markup and the floor, naming who can, and saves nothing', function (StaffRole $role) {
    $admin = StaffConsole::admin();
    $range = app(CreateProductRange::class)->handle($admin, 'IP cameras', 'none');
    $member = StaffConsole::active($admin, $role, 'Not An Admin');

    expect(fn () => app(SetRangeMarkup::class)->handle($member, $range->id, '30', 'Because'))
        ->toThrow(CapabilityRefused::class, 'Nothing was changed: changing markups and the margin floor is for Admins. Ask Ada Founder.');
    expect(fn () => app(SetMarginFloor::class)->handle($member, '10', 'Because'))
        ->toThrow(CapabilityRefused::class, 'is for Admins');

    expect(app(PricingRules::class)->markupFor($range->id)?->words())->toBe('25%')
        ->and(app(PricingRules::class)->marginFloor()->words())->toBe('8%');
})->with([StaffRole::Finance, StaffRole::Operations]);

it('reads and writes percentages as numeric(7,4) strings, never floats', function (string $typed, string $stored, string $words) {
    expect(Percentage::parse($typed, 'markup', 'A markup'))->toBe($stored)
        ->and(Percentage::words($stored))->toBe($words);
})->with([
    ['25', '25.0000', '25%'],
    ['27.5', '27.5000', '27.5%'],
    [' 8.125% ', '8.1250', '8.125%'],
    ['0', '0.0000', '0%'],
    ['999.9999', '999.9999', '999.9999%'],
]);

it('refuses what is not a percentage in words', function (string $typed, string $words) {
    expect(fn () => Percentage::parse($typed, 'markup', 'A markup'))->toThrow(InvalidPricingInput::class, $words);
})->with([
    ['abc', 'Enter a percentage, like 25 or 27.5.'],
    ['1000', 'A markup must be under 1,000%.'],
    ['1.23456', 'Use at most four decimal places'],
    ['--5', 'Enter a percentage, like 25 or 27.5.'],
    ['2 5', 'Enter a percentage, like 25 or 27.5.'],
    ['-5', 'A markup cannot be below zero.'],
]);

it('refuses the markup the range already has, beside the field, and records nothing', function () {
    $admin = StaffConsole::admin();
    $range = app(CreateProductRange::class)->handle($admin, 'IP cameras', 'none');
    $before = DB::table('audit_entries')->count();

    try {
        app(SetRangeMarkup::class)->handle($admin, $range->id, '25.0', 'Again');
        $this->fail('The same markup was accepted.');
    } catch (InvalidPricingInput $e) {
        expect($e->field)->toBe('markup')
            ->and($e->getMessage())->toContain('is already 25%');
    }

    expect(DB::table('audit_entries')->count())->toBe($before);
});

it('refuses the floor it already is, beside the field, and records nothing', function () {
    $admin = StaffConsole::admin();
    $before = DB::table('audit_entries')->count();

    try {
        app(SetMarginFloor::class)->handle($admin, '8', 'Again');
        $this->fail('The same floor was accepted.');
    } catch (InvalidPricingInput $e) {
        expect($e->field)->toBe('floor')
            ->and($e->getMessage())->toContain('already 8%');
    }

    expect(DB::table('audit_entries')->count())->toBe($before);
});
