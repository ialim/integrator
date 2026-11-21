<?php

use App\Modules\Catalogue\Actions\AddSupplier;
use App\Modules\Catalogue\Actions\ApproveSupplier;
use App\Modules\Catalogue\Actions\ApproveSupplierWithReason;
use App\Modules\Catalogue\Actions\EditSupplier;
use App\Modules\Catalogue\Actions\InvalidSupplierDetails;
use App\Modules\Catalogue\Actions\SetChecklistItemRequired;
use App\Modules\Catalogue\Queries\Suppliers;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Queries\AuditTrail;
use App\Modules\Shared\StatusTables\TransitionRefused;
use App\Modules\Shared\Support\Id;
use App\Modules\Staff\Capabilities\CapabilityRefused;
use App\Modules\Staff\Models\StaffRole;
use Database\Factories\Catalogue\SupplierFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\StaffConsole;

uses(RefreshDatabase::class);

beforeEach(fn () => app(Clock::class)->set('2026-11-19T09:00:00Z'));

afterEach(fn () => app(Clock::class)->reset());

/** A complete Supplier but for the given details, by $actor. */
function supplierWith(mixed $actor, array $overrides = []): string
{
    return app(AddSupplier::class)->handle($actor, [...SupplierFactory::COMPLETE, ...$overrides])->id;
}

/**
 * @param  Closure(): mixed  $call
 */
function refusedField(Closure $call): string
{
    try {
        $call();
    } catch (InvalidSupplierDetails $e) {
        return $e->field;
    }

    throw new LogicException('The call was not refused.');
}

it('adds a Supplier from its legal name only: Not approved, 0 of 5 clear, every other detail missing, audited', function () {
    $admin = StaffConsole::admin();
    $operations = StaffConsole::active($admin, StaffRole::Operations, 'Chika Operations');

    $supplier = app(AddSupplier::class)->handle($operations, ['legal_name' => '  Brightwave   Wholesale ']);

    expect($supplier->legalName)->toBe('Brightwave Wholesale')
        ->and($supplier->statusLabel)->toBe('Not approved')
        ->and($supplier->progressWords())->toBe('0 of 5 clear')
        ->and($supplier->detail('tin'))->toBeNull()
        ->and($supplier->productCount)->toBe(0)
        ->and(array_map(fn ($item) => $item->words(), $supplier->checklist))->toBe([
            'Needs action: missing CAC RC number, TIN, address, invoicing currency, payment terms',
            'Needs action: missing sales name, sales phone, sales email, returns and RMA name, returns and RMA phone, returns and RMA email',
            'Needs action: not stated',
            'Needs action: not stated',
            'Needs action: not stated',
        ]);

    $audit = app(AuditTrail::class)->forSubject('suppliers', $supplier->id);
    expect($audit)->toHaveCount(1)
        ->and($audit[0]->action)->toBe('catalogue.supplier_added')
        ->and($audit[0]->actorId)->toBe($operations->id)
        ->and($audit[0]->changes['legal_name'])->toEqual(['from' => null, 'to' => 'Brightwave Wholesale']);
});

it('refuses an empty legal name beside the field and saves nothing', function () {
    $admin = StaffConsole::admin();

    expect(refusedField(fn () => app(AddSupplier::class)->handle($admin, ['legal_name' => '   ', 'tin' => '123'])))->toBe('legal_name')
        ->and(refusedField(fn () => app(AddSupplier::class)->handle($admin, ['tin' => '123'])))->toBe('legal_name')
        ->and(DB::table('suppliers')->count())->toBe(0);
});

it('refuses a bad currency, email or phone beside its field', function (string $field, string $value) {
    $admin = StaffConsole::admin();

    expect(refusedField(fn () => app(AddSupplier::class)->handle($admin, ['legal_name' => 'Kable Co', $field => $value])))->toBe($field);
})->with([
    ['invoice_currency', 'EUR'],
    ['sales_contact_email', 'not-an-email'],
    ['returns_contact_phone', 'call me'],
]);

it('stores a short dead-on-arrival window, and the Query gives the shorter of it and 30 days', function () {
    $admin = StaffConsole::admin();
    $short = supplierWith($admin, ['doa_window_days' => '14']);
    $long = supplierWith($admin, ['legal_name' => 'Long Window Ltd', 'doa_window_days' => '45']);
    $none = app(AddSupplier::class)->handle($admin, ['legal_name' => 'No Window Ltd'])->id;

    expect(DB::table('suppliers')->where('id', $short)->value('doa_window_days'))->toBe(14)
        ->and(app(Suppliers::class)->doaWindowDays($short))->toBe(14)
        ->and(app(Suppliers::class)->doaWindowDays($long))->toBe(30)
        ->and(app(Suppliers::class)->doaWindowDays($none))->toBe(30);
});

it('refuses a dead-on-arrival window of 0 or below, or not whole, beside the field', function (string $days) {
    $admin = StaffConsole::admin();

    expect(refusedField(fn () => app(AddSupplier::class)->handle($admin, ['legal_name' => 'Kable Co', 'doa_window_days' => $days])))->toBe('doa_window_days');
})->with(['0', '-3', '1.5', 'two']);

it('refuses a window outside 1 to 9,999 and a third currency in the database too', function () {
    $insert = fn (array $row) => DB::transaction(fn () => DB::table('suppliers')->insert(['id' => Id::generate(), 'legal_name' => 'Raw Ltd', 'status' => 'not_approved', ...$row]));

    expect(fn () => $insert(['doa_window_days' => 0]))->toThrow(QueryException::class)
        ->and(fn () => $insert(['doa_window_days' => 10000]))->toThrow(QueryException::class)
        ->and(fn () => $insert(['invoice_currency' => 'EUR']))->toThrow(QueryException::class)
        ->and(fn () => $insert(['status' => 'approved']))->toThrow(QueryException::class);
});

it('approves a Supplier whose required items are all Clear, with who and when, audited', function () {
    $admin = StaffConsole::admin();
    $id = supplierWith($admin);

    $supplier = app(ApproveSupplier::class)->handle($admin, $id);

    expect($supplier->isApproved)->toBeTrue()
        ->and($supplier->approvedBy)->toBe($admin->id)
        ->and($supplier->approvedAt?->toIso8601ZuluString())->toBe('2026-11-19T09:00:00Z')
        ->and($supplier->approvalReason)->toBeNull()
        ->and($supplier->progressWords())->toBe('5 of 5 clear');

    $latest = app(AuditTrail::class)->latestFor('suppliers', $id);
    expect($latest?->action)->toBe('catalogue.supplier_approved')
        ->and($latest?->actorId)->toBe($admin->id)
        ->and($latest?->changes['status'])->toEqual(['from' => 'not_approved', 'to' => 'approved']);
});

it('refuses "Approve the supplier" in words while 2 required items need action', function () {
    $admin = StaffConsole::admin();
    $id = supplierWith($admin, ['tin' => null, 'counterfeit_terms' => null]);
    $supplier = app(Suppliers::class)->find($id);

    expect($supplier?->offBecause())->toBe('Off because 2 required items need action: Business details and Counterfeit chargeback.');
    expect(fn () => app(ApproveSupplier::class)->handle($admin, $id))
        ->toThrow(TransitionRefused::class, 'Nothing was changed: 2 required items need action: Business details and Counterfeit chargeback.');
    expect(app(Suppliers::class)->find($id)?->isApproved)->toBeFalse();
});

it('approves with a reason while items need action; the reason is stored and shown; an empty one is refused beside the field', function () {
    $admin = StaffConsole::admin();
    $id = supplierWith($admin, ['counterfeit_terms' => null]);

    expect(refusedField(fn () => app(ApproveSupplierWithReason::class)->handle($admin, $id, '  ')))->toBe('reason');

    $supplier = app(ApproveSupplierWithReason::class)->handle($admin, $id, 'Agreed by phone.');

    expect($supplier->isApproved)->toBeTrue()
        ->and($supplier->approvedWithReason())->toBeTrue()
        ->and($supplier->approvalReason)->toBe('Agreed by phone.')
        ->and(app(AuditTrail::class)->latestFor('suppliers', $id)?->action)->toBe('catalogue.supplier_approved_with_reason');
});

it('offers no reason route for a clear Supplier, and refuses approving twice', function () {
    $admin = StaffConsole::admin();
    $id = supplierWith($admin);

    expect(fn () => app(ApproveSupplierWithReason::class)->handle($admin, $id, 'Because'))
        ->toThrow(TransitionRefused::class, 'every required item for Northgate Distribution Ltd is clear');

    app(ApproveSupplier::class)->handle($admin, $id);

    expect(fn () => app(ApproveSupplier::class)->handle($admin, $id))
        ->toThrow(TransitionRefused::class, 'Northgate Distribution Ltd is already approved. Nothing was changed.');
});

it('refuses making an agreement term optional, in the Action and the database', function (string $key) {
    $admin = StaffConsole::admin();

    try {
        app(SetChecklistItemRequired::class)->handle($admin, $key, false, 'Trying');
        $this->fail('A term was made optional.');
    } catch (InvalidSupplierDetails $e) {
        expect($e->getMessage())->toBe('The three agreement terms are always required.')
            ->and($e->field)->toBe('required_'.$key);
    }

    expect(fn () => DB::transaction(fn () => DB::table('onboarding_checklist_items')->where('key', $key)->update(['required' => false])))
        ->toThrow(QueryException::class);
})->with(['returns_terms', 'doa_terms', 'counterfeit_chargeback']);

it('refuses setting an item to what it already is, beside that item, and records nothing', function () {
    $admin = StaffConsole::admin();
    $before = DB::table('audit_entries')->count();

    try {
        app(SetChecklistItemRequired::class)->handle($admin, 'contacts', true, 'Again');
        $this->fail('An unchanged setting was accepted.');
    } catch (InvalidSupplierDetails $e) {
        expect($e->field)->toBe('required_contacts')
            ->and($e->getMessage())->toBe('Contacts: sales, and returns and RMA is already required. Nothing was changed.');
    }

    expect(DB::table('audit_entries')->count())->toBe($before);
});

it('refuses a legal name of only no-break spaces beside the field, and stores a term of only Unicode spaces as missing', function () {
    $admin = StaffConsole::admin();
    $nbsp = "\u{00A0}";

    expect(refusedField(fn () => app(AddSupplier::class)->handle($admin, ['legal_name' => $nbsp.$nbsp])))->toBe('legal_name')
        ->and(DB::table('suppliers')->count())->toBe(0);

    $supplier = app(AddSupplier::class)->handle($admin, ['legal_name' => $nbsp.'Kable Co'.$nbsp, 'returns_terms' => $nbsp."\u{2003}".$nbsp]);

    expect($supplier->legalName)->toBe('Kable Co')
        ->and($supplier->detail('returns_terms'))->toBeNull()
        ->and(DB::table('suppliers')->where('id', $supplier->id)->value('returns_terms'))->toBeNull()
        ->and($supplier->checklist[2]->words())->toBe('Needs action: not stated');
});

it('needs both halves of the dead-on-arrival term: its text and its window', function () {
    $admin = StaffConsole::admin();
    $noWindow = supplierWith($admin, ['doa_window_days' => null]);
    $noTerms = supplierWith($admin, ['legal_name' => 'No Terms Ltd', 'doa_terms' => null]);
    $words = fn (string $id) => app(Suppliers::class)->find($id)?->checklist[3]->words();

    expect($words($noWindow))->toBe('Needs action: missing the window in days')
        ->and($words($noTerms))->toBe('Needs action: missing the terms')
        ->and(fn () => app(ApproveSupplier::class)->handle($admin, $noWindow))->toThrow(TransitionRefused::class, 'Dead-on-arrival terms')
        ->and(fn () => app(ApproveSupplier::class)->handle($admin, $noTerms))->toThrow(TransitionRefused::class, 'Dead-on-arrival terms');
});

it('makes Business details optional with a reason: an approved Supplier lacking it stays approved, and a later approval no longer needs it', function () {
    $admin = StaffConsole::admin();
    $approved = supplierWith($admin);
    app(ApproveSupplier::class)->handle($admin, $approved);
    app(EditSupplier::class)->handle($admin, $approved, ['tin' => null], 'TIN was wrong; asking again');
    $later = supplierWith($admin, ['legal_name' => 'Later Ltd', 'tin' => null]);

    expect(app(Suppliers::class)->find($approved)?->isApproved)->toBeTrue()
        ->and(app(Suppliers::class)->find($approved)?->progressWords())->toBe('4 of 5 clear')
        ->and(fn () => app(ApproveSupplier::class)->handle($admin, $later))->toThrow(TransitionRefused::class);

    expect(refusedField(fn () => app(SetChecklistItemRequired::class)->handle($admin, 'business_details', false, '')))->toBe('reason');
    $setting = app(SetChecklistItemRequired::class)->handle($admin, 'business_details', false, 'CAC checks happen later');

    expect($setting->words())->toBe('Optional')
        ->and(app(Suppliers::class)->find($approved)?->isApproved)->toBeTrue()
        ->and(app(Suppliers::class)->find($approved)?->progressWords())->toBe('4 of 4 clear')
        ->and(app(ApproveSupplier::class)->handle($admin, $later)->isApproved)->toBeTrue();

    $audit = app(AuditTrail::class)->latestFor('onboarding_checklist_items', $setting->id);
    expect($audit?->action)->toBe('catalogue.checklist_item_set')
        ->and($audit?->changes['required'])->toEqual(['from' => true, 'to' => false])
        ->and($audit?->changes['reason'])->toBe('CAC checks happen later');
});

it('edits an approved Supplier with a reason without un-approving it, its contacts audited only as changed', function () {
    $admin = StaffConsole::admin();
    $operations = StaffConsole::active($admin, StaffRole::Operations, 'Chika Operations');
    $id = supplierWith($admin);
    app(ApproveSupplier::class)->handle($admin, $id);

    expect(refusedField(fn () => app(EditSupplier::class)->handle($operations, $id, ['sales_contact_phone' => '0809 111 1111'], '')))->toBe('reason')
        ->and(refusedField(fn () => app(EditSupplier::class)->handle($operations, $id, ['tin' => '12345678-0001'], 'Same')))->toBe('reason');

    $supplier = app(EditSupplier::class)->handle($operations, $id, ['sales_contact_phone' => '0809 111 1111', 'counterfeit_terms' => ''], 'New sales line');
    $latest = app(AuditTrail::class)->latestFor('suppliers', $id);

    expect($supplier->isApproved)->toBeTrue()
        ->and($supplier->detail('sales_contact_phone'))->toBe('0809 111 1111')
        ->and($supplier->progressWords())->toBe('4 of 5 clear')
        ->and($latest?->action)->toBe('catalogue.supplier_edited')
        ->and($latest?->changes['sales_contact_phone'])->toBe('changed')
        ->and($latest?->changes['reason'])->toBe('New sales line')
        ->and(json_encode($latest?->changes))->not->toContain('0809');
});

it('records contact names, phones and emails as changed, never their values, when a Supplier is added', function () {
    $admin = StaffConsole::admin();
    $id = supplierWith($admin);
    $changes = app(AuditTrail::class)->forSubject('suppliers', $id)[0]->changes;

    foreach (['sales_contact_name', 'sales_contact_phone', 'sales_contact_email', 'returns_contact_name', 'returns_contact_phone', 'returns_contact_email'] as $field) {
        expect($changes[$field])->toBe('changed');
    }

    expect(json_encode($changes))->not->toContain('northgate.example');
});

it('lists Suppliers with progress, and counts and filters by the chips', function () {
    $admin = StaffConsole::admin();
    $clear = supplierWith($admin, ['legal_name' => 'Apex Power']);
    app(ApproveSupplier::class)->handle($admin, $clear);
    $gaps = supplierWith($admin, ['legal_name' => 'Brightwave', 'counterfeit_terms' => null]);
    $reasoned = supplierWith($admin, ['legal_name' => 'Sentinel', 'tin' => null]);
    app(ApproveSupplierWithReason::class)->handle($admin, $reasoned, 'Agreed');
    $names = fn (string $chip) => array_map(fn ($s) => $s->legalName, app(Suppliers::class)->list($chip));

    expect(app(Suppliers::class)->chipCounts())->toBe(['all' => 3, 'needs_attention' => 2, 'approved' => 2, 'not_approved' => 1])
        ->and($names('all'))->toBe(['Apex Power', 'Brightwave', 'Sentinel'])
        ->and($names('needs_attention'))->toBe(['Brightwave', 'Sentinel'])
        ->and($names('approved'))->toBe(['Apex Power', 'Sentinel'])
        ->and($names('not_approved'))->toBe(['Brightwave'])
        ->and(app(Suppliers::class)->find($gaps)?->progressWords())->toBe('4 of 5 clear');
});

it('refuses Finance every write and Operations the approvals and settings, naming who can, and saves nothing', function () {
    $admin = StaffConsole::admin();
    $id = supplierWith($admin, ['counterfeit_terms' => null]);
    $finance = StaffConsole::active($admin, StaffRole::Finance, 'Bola Finance');
    $operations = StaffConsole::active($admin, StaffRole::Operations, 'Chika Operations');
    $before = DB::table('audit_entries')->count();

    foreach ([
        [fn () => app(AddSupplier::class)->handle($finance, ['legal_name' => 'X']), 'recording and editing Suppliers is for Operations staff and Admins'],
        [fn () => app(EditSupplier::class)->handle($finance, $id, ['tin' => '1'], 'Because'), 'recording and editing Suppliers is for Operations staff and Admins'],
        [fn () => app(ApproveSupplierWithReason::class)->handle($finance, $id, 'Because'), 'approving Suppliers is for Admins'],
        [fn () => app(ApproveSupplierWithReason::class)->handle($operations, $id, 'Because'), 'approving Suppliers is for Admins'],
        [fn () => app(ApproveSupplier::class)->handle($operations, $id), 'approving Suppliers is for Admins'],
        [fn () => app(SetChecklistItemRequired::class)->handle($operations, 'contacts', false, 'Because'), 'changing the Supplier onboarding checklist is for Admins'],
    ] as [$call, $words]) {
        expect($call)->toThrow(CapabilityRefused::class, $words);
    }

    expect(DB::table('audit_entries')->count())->toBe($before)
        ->and(app(Suppliers::class)->find($id)?->isApproved)->toBeFalse()
        ->and(app(Suppliers::class)->checklistSetting('contacts')?->required)->toBeTrue();
});
