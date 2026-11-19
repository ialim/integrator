<?php

use App\Modules\Catalogue\Actions\CreateProductRange;
use App\Modules\Catalogue\Actions\InvalidRangeDetails;
use App\Modules\Catalogue\Actions\RenameProductRange;
use App\Modules\Catalogue\Actions\RetireProductRange;
use App\Modules\Catalogue\Actions\SetRangeCompliance;
use App\Modules\Catalogue\Models\ProductRange;
use App\Modules\Catalogue\Queries\ProductRanges;
use App\Modules\Pricing\Queries\PricingRules;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Queries\AuditTrail;
use App\Modules\Shared\StatusTables\TransitionRefused;
use App\Modules\Shared\Support\Id;
use App\Modules\Staff\Capabilities\CapabilityRefused;
use App\Modules\Staff\Models\StaffRole;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\StaffConsole;

uses(RefreshDatabase::class);

beforeEach(fn () => app(Clock::class)->set('2026-11-19T09:00:00Z'));

afterEach(function () {
    app(Clock::class)->reset();
    ProductRange::flushEventListeners();
});

it('creates a range Active at a 25% markup, both audited in one go', function () {
    $admin = StaffConsole::admin();

    $range = app(CreateProductRange::class)->handle($admin, '  IP   cameras ', 'ncc_type_approval');

    expect($range->name)->toBe('IP cameras')
        ->and($range->statusLabel)->toBe('Active')
        ->and($range->complianceLabel)->toBe('NCC type approval')
        ->and($range->productCount)->toBe(0)
        ->and(app(PricingRules::class)->markupFor($range->id)?->words())->toBe('25%');

    $created = app(AuditTrail::class)->forSubject('product_ranges', $range->id);
    $markup = app(PricingRules::class)->markupFor($range->id);
    $markupRows = app(AuditTrail::class)->forSubject('range_markups', (string) $markup?->id);

    expect($created)->toHaveCount(1)
        ->and($created[0]->action)->toBe('catalogue.range_created')
        ->and($created[0]->actorId)->toBe($admin->id)
        ->and($created[0]->changes['name'])->toEqual(['from' => null, 'to' => 'IP cameras'])
        ->and($markupRows)->toHaveCount(1)
        ->and($markupRows[0]->changes)->toEqual(['markup_percent' => ['from' => null, 'to' => '25']]);
});

it('refuses a duplicate active name in words, whatever its case', function () {
    $admin = StaffConsole::admin();
    app(CreateProductRange::class)->handle($admin, 'Recorders', 'none');

    expect(fn () => app(CreateProductRange::class)->handle($admin, 'recorders', 'none'))
        ->toThrow(InvalidRangeDetails::class, 'An active Product Range is already called recorders.');

    expect(DB::table('product_ranges')->count())->toBe(1);
});

it('refuses a duplicate name in the database too', function () {
    $admin = StaffConsole::admin();
    app(CreateProductRange::class)->handle($admin, 'Recorders', 'none');

    expect(fn () => DB::transaction(fn () => DB::table('product_ranges')->insert([
        'id' => '019a0000-0000-7000-8000-000000000001', 'name' => 'RECORDERS', 'compliance' => 'none', 'status' => 'active',
    ])))->toThrow(UniqueConstraintViolationException::class);
});

it('renames with a required reason, audited with the old name, the new name and the reason', function () {
    $admin = StaffConsole::admin();
    $range = app(CreateProductRange::class)->handle($admin, 'Switches', 'none');

    expect(fn () => app(RenameProductRange::class)->handle($admin, $range->id, 'Network switches', '  '))
        ->toThrow(InvalidRangeDetails::class, 'Give a reason for the change.');

    app(RenameProductRange::class)->handle($admin, $range->id, 'Network switches', 'Clearer for buyers');
    $latest = app(AuditTrail::class)->latestFor('product_ranges', $range->id);

    expect(app(ProductRanges::class)->find($range->id)?->name)->toBe('Network switches')
        ->and($latest?->action)->toBe('catalogue.range_renamed')
        ->and($latest?->changes)->toEqual(['name' => ['from' => 'Switches', 'to' => 'Network switches'], 'reason' => 'Clearer for buyers']);
});

it('changes the compliance flag with a reason, and refuses an unknown one beside the field', function () {
    $admin = StaffConsole::admin();
    $range = app(CreateProductRange::class)->handle($admin, 'Solar inverters', 'none');

    try {
        app(SetRangeCompliance::class)->handle($admin, $range->id, 'ce_mark', 'Wrong flag');
        $this->fail('An unknown flag was accepted.');
    } catch (InvalidRangeDetails $e) {
        expect($e->field)->toBe('compliance');
    }

    $updated = app(SetRangeCompliance::class)->handle($admin, $range->id, 'soncap', 'SONCAP applies to inverters');

    expect($updated->complianceLabel)->toBe('SONCAP')
        ->and(app(AuditTrail::class)->latestFor('product_ranges', $range->id)?->changes['reason'])->toBe('SONCAP applies to inverters');
});

it('retires a range with a reason; it stays with its History and takes no further edits', function () {
    $admin = StaffConsole::admin();
    $range = app(CreateProductRange::class)->handle($admin, 'Cabling', 'none');

    $retired = app(RetireProductRange::class)->handle($admin, $range->id, 'Folded into Accessories');

    expect($retired->isRetired)->toBeTrue()
        ->and($retired->statusLabel)->toBe('Retired')
        ->and(app(ProductRanges::class)->all())->toHaveCount(1);

    expect(fn () => app(RenameProductRange::class)->handle($admin, $range->id, 'Cables', 'Try'))
        ->toThrow(TransitionRefused::class, 'Cabling is retired, so it takes no further edits.');

    // A retired range's name is free for a new active range.
    expect(app(CreateProductRange::class)->handle($admin, 'Cabling', 'none')->isRetired)->toBeFalse();
});

it('words the refusal to retire a range that still holds Products', function () {
    expect(RetireProductRange::holdsProducts('Phones', 3))->toBe('Nothing was changed: Phones still holds 3 Products. Move its 3 Products to another range first.')
        ->and(RetireProductRange::holdsProducts('Phones', 1))->toContain('Move its 1 Product to another range first.');
});

it('refuses Finance and Operations on every range Action, naming who can, and saves nothing', function (StaffRole $role) {
    $admin = StaffConsole::admin();
    $range = app(CreateProductRange::class)->handle($admin, 'Recorders', 'none');
    $member = StaffConsole::active($admin, $role, 'Not An Admin');
    $before = DB::table('audit_entries')->count();

    foreach ([
        fn () => app(CreateProductRange::class)->handle($member, 'Phones', 'none'),
        fn () => app(RenameProductRange::class)->handle($member, $range->id, 'Phones', 'Because'),
        fn () => app(SetRangeCompliance::class)->handle($member, $range->id, 'soncap', 'Because'),
        fn () => app(RetireProductRange::class)->handle($member, $range->id, 'Because'),
    ] as $call) {
        expect($call)->toThrow(CapabilityRefused::class, 'Nothing was changed: setting up Product Ranges is for Admins. Ask Ada Founder.');
    }

    expect(DB::table('audit_entries')->count())->toBe($before)
        ->and(app(ProductRanges::class)->find($range->id)?->name)->toBe('Recorders');
})->with([StaffRole::Finance, StaffRole::Operations]);

/**
 * Another active range with $name, written straight to the table while the Action is inside
 * its transaction, after its own check: two Admins choosing the same name at the same moment.
 */
function competingRange(string $name): void
{
    DB::table('product_ranges')->insert([
        'id' => Id::generate(), 'name' => $name, 'compliance' => 'none', 'status' => 'active',
    ]);
}

it('refuses a name taken at the same moment by the database index, beside the field, in the name as saved', function () {
    $admin = StaffConsole::admin();
    ProductRange::creating(fn () => competingRange('Recorders'));

    try {
        app(CreateProductRange::class)->handle($admin, '  recorders ', 'none');
        $this->fail('The index did not fire.');
    } catch (InvalidRangeDetails $e) {
        expect($e->field)->toBe('name')
            ->and($e->getMessage())->toBe('An active Product Range is already called recorders. Give this one another name.');
    }

    expect(DB::table('product_ranges')->count())->toBe(0);
});

it('refuses a rename to a name taken at the same moment by the database index, beside the field', function () {
    $admin = StaffConsole::admin();
    $range = app(CreateProductRange::class)->handle($admin, 'Switches', 'none');
    ProductRange::updating(fn () => competingRange('Network switches'));

    try {
        app(RenameProductRange::class)->handle($admin, $range->id, ' Network   switches', 'Clearer');
        $this->fail('The index did not fire.');
    } catch (InvalidRangeDetails $e) {
        expect($e->field)->toBe('name')
            ->and($e->getMessage())->toContain('already called Network switches.');
    }

    expect(app(ProductRanges::class)->find($range->id)?->name)->toBe('Switches');
});

it('rethrows a unique violation that is not about the name unchanged', function () {
    $admin = StaffConsole::admin();
    ProductRange::creating(fn () => DB::table('pricing_settings')->insert(['id' => Id::generate(), 'key' => 'margin_floor', 'percent' => '1']));

    try {
        app(CreateProductRange::class)->handle($admin, 'Recorders', 'none');
        $this->fail('No violation was thrown.');
    } catch (UniqueConstraintViolationException $e) {
        expect($e->index)->toBe('pricing_settings_key_unique');
    }
});

it('refuses a rename to another active range\'s name in another case, and changes nothing', function () {
    $admin = StaffConsole::admin();
    $switches = app(CreateProductRange::class)->handle($admin, 'Switches', 'none');
    app(CreateProductRange::class)->handle($admin, 'Recorders', 'none');
    $before = DB::table('audit_entries')->count();

    try {
        app(RenameProductRange::class)->handle($admin, $switches->id, 'RECORDERS', 'Merge');
        $this->fail('A taken name was accepted.');
    } catch (InvalidRangeDetails $e) {
        expect($e->field)->toBe('name');
    }

    expect(app(ProductRanges::class)->find($switches->id)?->name)->toBe('Switches')
        ->and(DB::table('audit_entries')->count())->toBe($before);
});

it('renames a range to its own name in another case', function () {
    $admin = StaffConsole::admin();
    $range = app(CreateProductRange::class)->handle($admin, 'Recorders', 'none');

    expect(app(RenameProductRange::class)->handle($admin, $range->id, 'RECORDERS', 'House style')->name)->toBe('RECORDERS');
});

it('refuses a rename to the identical name and records nothing', function () {
    $admin = StaffConsole::admin();
    $range = app(CreateProductRange::class)->handle($admin, 'Recorders', 'none');
    $before = DB::table('audit_entries')->count();

    try {
        app(RenameProductRange::class)->handle($admin, $range->id, 'Recorders', 'No change');
        $this->fail('The same name was accepted.');
    } catch (InvalidRangeDetails $e) {
        expect($e->field)->toBe('name');
    }

    expect(DB::table('audit_entries')->count())->toBe($before);
});

it('refuses the compliance the range already needs, beside the field, and records nothing', function () {
    $admin = StaffConsole::admin();
    $range = app(CreateProductRange::class)->handle($admin, 'Inverters', 'soncap');
    $before = DB::table('audit_entries')->count();

    try {
        app(SetRangeCompliance::class)->handle($admin, $range->id, 'soncap', 'Again');
        $this->fail('The same flag was accepted.');
    } catch (InvalidRangeDetails $e) {
        expect($e->field)->toBe('compliance');
    }

    expect(DB::table('audit_entries')->count())->toBe($before);
});
