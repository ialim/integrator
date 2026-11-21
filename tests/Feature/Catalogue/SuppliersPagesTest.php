<?php

use App\Modules\Catalogue\Actions\AddSupplier;
use App\Modules\Catalogue\Actions\ApproveSupplierWithReason;
use App\Modules\Catalogue\Actions\SetChecklistItemRequired;
use App\Modules\Catalogue\Capabilities\CatalogueCapabilities;
use App\Modules\Catalogue\Filament\SupplierPage;
use App\Modules\Catalogue\Filament\SuppliersPage;
use App\Modules\Catalogue\Queries\Suppliers;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Filament\ConsolePageGuard;
use App\Modules\Staff\Models\StaffRole;
use Database\Factories\Catalogue\SupplierFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\StaffConsole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(Clock::class)->set('2026-11-19T09:00:00Z');
    StaffConsole::useConsole();
});

afterEach(fn () => app(Clock::class)->reset());

it('lists Suppliers in the Catalogue group with status, Products, progress and the chips', function () {
    $admin = StaffConsole::admin();
    app(AddSupplier::class)->handle($admin, ['legal_name' => 'Brightwave Wholesale']);
    StaffConsole::signIn($this, $admin);

    $html = $this->get('/console/suppliers')->assertOk()->getContent();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('data-group-label="Catalogue"')
        ->and($html)->toContain('<th scope="col">Checklist</th>')
        ->and($html)->toContain('Brightwave Wholesale')
        ->and($html)->toContain('Not approved')
        ->and($html)->toContain('0 of 5 clear')
        ->and($html)->toContain('All · 1')
        ->and($html)->toContain('Needs attention · 1')
        ->and($html)->toContain('Approved · 0')
        ->and($html)->toContain('Not approved · 1')
        ->and($html)->toContain('Add a supplier…')
        ->and($html)->toContain('Onboarding settings…')
        ->and($html)->not->toContain(SuppliersPage::ADMIN_ONLY);
});

it('shows Finance and Operations the list with the Admin-only line and no settings button', function (StaffRole $role) {
    $admin = StaffConsole::admin();
    app(AddSupplier::class)->handle($admin, ['legal_name' => 'Brightwave Wholesale']);
    StaffConsole::signIn($this, StaffConsole::active($admin, $role, 'Not An Admin'));

    $response = $this->get('/console/suppliers')->assertOk()
        ->assertSee(SuppliersPage::ADMIN_ONLY)
        ->assertSee('Brightwave Wholesale')
        ->assertDontSee('Onboarding settings…');

    $role === StaffRole::Operations ? $response->assertSee('Add a supplier…') : $response->assertDontSee('Add a supplier…');
})->with([StaffRole::Finance, StaffRole::Operations]);

it('filters the list by a chip', function () {
    $admin = StaffConsole::admin();
    app(AddSupplier::class)->handle($admin, SupplierFactory::COMPLETE);
    app(AddSupplier::class)->handle($admin, ['legal_name' => 'Brightwave Wholesale']);
    $this->actingAs($admin, 'staff');

    Livewire::test(SuppliersPage::class)
        ->assertSee('Northgate Distribution Ltd')
        ->set('chip', Suppliers::NEEDS_ATTENTION)
        ->assertSee('Brightwave Wholesale')
        ->assertDontSee('Northgate Distribution Ltd');
});

it('adds a Supplier from the dialog, and refuses an empty legal name beside the field', function () {
    $operations = StaffConsole::active(StaffConsole::admin(), StaffRole::Operations, 'Chika Operations');
    $this->actingAs($operations, 'staff');

    Livewire::test(SuppliersPage::class)
        ->callAction('add', data: ['legal_name' => ''])
        ->assertHasActionErrors(['legal_name']);

    Livewire::test(SuppliersPage::class)
        ->callAction('add', data: ['legal_name' => 'Kable Co', 'doa_window_days' => '0'])
        ->assertHasActionErrors(['doa_window_days']);

    Livewire::test(SuppliersPage::class)
        ->callAction('add', data: ['legal_name' => 'Kable Co', 'doa_window_days' => '14'])
        ->assertHasNoActionErrors()
        ->assertSet('notice', 'Added Kable Co, Not approved. Its checklist is 0 of 5 clear.');

    expect(app(Suppliers::class)->list())->toHaveCount(1);
});

it('lets an Admin make Contacts optional from the settings dialog, with a reason', function () {
    $admin = StaffConsole::admin();
    $this->actingAs($admin, 'staff');

    Livewire::test(SuppliersPage::class)
        ->callAction('settings', data: ['required_business_details' => true, 'required_contacts' => false, 'reason' => ''])
        ->assertHasActionErrors(['reason']);

    Livewire::test(SuppliersPage::class)
        ->callAction('settings', data: ['required_business_details' => true, 'required_contacts' => false, 'reason' => 'Contacts come later'])
        ->assertHasNoActionErrors()
        ->assertSet('notice', 'Contacts: sales, and returns and RMA is now optional. Approvals from now on follow the new settings.');

    expect(app(Suppliers::class)->checklistSetting('contacts')?->required)->toBeFalse();
});

it('hides the settings and leaves them refused for Operations on the server', function () {
    $operations = StaffConsole::active(StaffConsole::admin(), StaffRole::Operations, 'Chika Operations');
    $this->actingAs($operations, 'staff');

    Livewire::test(SuppliersPage::class)->assertActionHidden('settings')->assertActionVisible('add');
});

it('shows a Supplier with gaps to an Admin: sections, Not stated, the checklist in words and the dashed off button with its reason', function () {
    $admin = StaffConsole::admin();
    $supplier = app(AddSupplier::class)->handle($admin, [...SupplierFactory::COMPLETE, 'tin' => null, 'counterfeit_terms' => null, 'doa_window_days' => '14']);
    StaffConsole::signIn($this, $admin);

    $html = $this->get('/console/suppliers/'.$supplier->id)->assertOk()->getContent();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('Northgate Distribution Ltd')
        ->and($html)->toContain('Business details')
        ->and($html)->toContain('Contacts')
        ->and($html)->toContain('Agreement terms')
        ->and($html)->toContain('Not stated')
        ->and($html)->toContain('Onboarding checklist')
        ->and($html)->toContain('Clear · 14 days')
        ->and($html)->toContain('Needs action: missing TIN')
        ->and($html)->toContain('aria-disabled="true" aria-describedby="supplier-approve-why"')
        ->and($html)->toContain('id="supplier-approve-why" class="console-off-reason" tabindex="0"')
        ->and($html)->toContain('Off because 2 required items need action: Business details and Counterfeit chargeback.')
        ->and($html)->toContain('Approve with a reason…')
        ->and($html)->toContain('Edit details…')
        ->and($html)->not->toContain(SuppliersPage::ADMIN_ONLY);
});

it('shows Finance and Operations the Supplier with the Admin-only line and no approve buttons', function (StaffRole $role) {
    $admin = StaffConsole::admin();
    $supplier = app(AddSupplier::class)->handle($admin, ['legal_name' => 'Brightwave Wholesale']);
    StaffConsole::signIn($this, StaffConsole::active($admin, $role, 'Not An Admin'));

    $this->get('/console/suppliers/'.$supplier->id)->assertOk()
        ->assertSee(SuppliersPage::ADMIN_ONLY)
        ->assertSee('Needs action')
        ->assertDontSee('Approve the supplier')
        ->assertDontSee('Approve with a reason…');
})->with([StaffRole::Finance, StaffRole::Operations]);

it('answers 404 for a Supplier that does not exist', function () {
    StaffConsole::signIn($this, StaffConsole::admin());

    $this->get('/console/suppliers/019a0000-0000-7000-8000-000000000001')->assertNotFound();
});

it('refuses a forced "Approve the supplier" in words, and approves with a reason shown on the Supplier', function () {
    $admin = StaffConsole::admin();
    $supplier = app(AddSupplier::class)->handle($admin, [...SupplierFactory::COMPLETE, 'counterfeit_terms' => null]);
    $this->actingAs($admin, 'staff');

    Livewire::test(SupplierPage::class, ['supplier' => $supplier->id])
        ->callAction('approve')
        ->assertSet('noticeIsRefusal', true)
        ->assertSee('1 required item needs action: Counterfeit chargeback');

    expect(app(Suppliers::class)->find($supplier->id)?->isApproved)->toBeFalse();

    Livewire::test(SupplierPage::class, ['supplier' => $supplier->id])
        ->callAction('approveWithReason', data: ['reason' => ''])
        ->assertHasActionErrors(['reason']);

    Livewire::test(SupplierPage::class, ['supplier' => $supplier->id])
        ->callAction('approveWithReason', data: ['reason' => 'Agreed by phone'])
        ->assertHasNoActionErrors()
        ->assertSet('notice', 'Approved Northgate Distribution Ltd with a reason.')
        ->assertSee('Agreed by phone')
        ->assertSee('Approved with a reason by Ada Founder');
});

it('approves a clear Supplier from its page, and edits it with a reason without un-approving it', function () {
    $admin = StaffConsole::admin();
    $supplier = app(AddSupplier::class)->handle($admin, SupplierFactory::COMPLETE);
    $this->actingAs($admin, 'staff');

    Livewire::test(SupplierPage::class, ['supplier' => $supplier->id])
        ->callAction('approve')
        ->assertSet('notice', 'Approved Northgate Distribution Ltd.')
        ->callAction('edit', data: [...SupplierFactory::COMPLETE, 'tin' => '', 'reason' => 'TIN to be checked'])
        ->assertHasNoActionErrors()
        ->assertSet('notice', 'Saved the details of Northgate Distribution Ltd. Its checklist is 4 of 5 clear.')
        ->mountAction('history')
        ->assertMountedActionModalSee('Supplier edited')
        ->assertMountedActionModalSee('Reason: TIN to be checked')
        ->assertMountedActionModalSee('Supplier approved');

    expect(app(Suppliers::class)->find($supplier->id)?->isApproved)->toBeTrue();
});

it('lets Finance open a Supplier but call no change: the buttons are hidden and the server refuses', function () {
    $admin = StaffConsole::admin();
    $supplier = app(AddSupplier::class)->handle($admin, [...SupplierFactory::COMPLETE, 'counterfeit_terms' => null]);
    $finance = StaffConsole::active($admin, StaffRole::Finance, 'Bola Finance');
    $this->actingAs($finance, 'staff');

    Livewire::test(SupplierPage::class, ['supplier' => $supplier->id])
        ->assertActionHidden('approve')
        ->assertActionHidden('approveWithReason')
        ->assertActionHidden('edit')
        ->assertActionVisible('history');

    expect(fn () => app(ApproveSupplierWithReason::class)->handle($finance, $supplier->id, 'x'))
        ->toThrow(AuthorizationException::class);
});

it('refuses a member without the Suppliers capability in the same words for a real and an unknown Supplier', function () {
    $admin = StaffConsole::admin();
    $supplier = app(AddSupplier::class)->handle($admin, ['legal_name' => 'Brightwave Wholesale']);
    StaffConsole::signIn($this, $admin);
    $real = app(ConsolePageGuard::class);
    app()->instance(ConsolePageGuard::class, new class($real) implements ConsolePageGuard
    {
        public function __construct(private readonly ConsolePageGuard $real) {}

        public function allows(string $capability): bool
        {
            return $capability !== CatalogueCapabilities::VIEW_SUPPLIERS && $this->real->allows($capability);
        }

        public function refusal(string $pageTitle, string $capability): string
        {
            return $this->real->refusal($pageTitle, $capability);
        }

        public function markRefused(): void
        {
            $this->real->markRefused();
        }
    });

    $known = $this->get('/console/suppliers/'.$supplier->id);
    $unknown = $this->get('/console/suppliers/019a0000-0000-7000-8000-000000000001');

    $known->assertForbidden()->assertSee('The Suppliers page is for')->assertDontSee('Brightwave Wholesale');
    $unknown->assertForbidden()->assertSee('The Suppliers page is for');
});

it('shows the error beside the item when another Admin changed it while the settings dialog was open', function () {
    $admin = StaffConsole::admin();
    $other = StaffConsole::active($admin, StaffRole::Admin, 'Second Admin', 'second.admin@example.test');
    $this->actingAs($admin, 'staff');

    $page = Livewire::test(SuppliersPage::class)->mountAction('settings');
    app(SetChecklistItemRequired::class)->handle($other, 'contacts', false, 'Contacts come later');

    $page->setActionData(['required_business_details' => true, 'required_contacts' => false, 'reason' => 'Same idea'])
        ->callMountedAction()
        ->assertHasActionErrors(['required_contacts']);

    expect($page->errors()->first('mountedActions.0.data.required_contacts'))
        ->toBe('Contacts: sales, and returns and RMA is already optional. Nothing was changed.');
});

it('saves two items made optional in one submit, then one made required again, and refuses an unchanged submit', function () {
    $admin = StaffConsole::admin();
    $this->actingAs($admin, 'staff');

    Livewire::test(SuppliersPage::class)
        ->callAction('settings', data: ['required_business_details' => false, 'required_contacts' => false, 'reason' => 'Both come later'])
        ->assertHasNoActionErrors()
        ->assertSet('notice', 'Business details is now optional. Contacts: sales, and returns and RMA is now optional. Approvals from now on follow the new settings.');

    $suppliers = app(Suppliers::class);
    expect($suppliers->checklistSetting('business_details')?->required)->toBeFalse()
        ->and($suppliers->checklistSetting('contacts')?->required)->toBeFalse()
        ->and(DB::table('onboarding_checklist_items')->whereIn('key', ['business_details', 'contacts'])->where('required', true)->count())->toBe(0);

    $rows = DB::table('audit_entries')->where('action', 'catalogue.checklist_item_set')->get();
    expect($rows)->toHaveCount(2)
        ->and($rows->map(fn ($row) => json_decode((string) $row->changes, true)['reason'])->unique()->values()->all())->toBe(['Both come later']);

    Livewire::test(SuppliersPage::class)
        ->callAction('settings', data: ['required_business_details' => true, 'required_contacts' => false, 'reason' => 'Business details first'])
        ->assertHasNoActionErrors()
        ->assertSet('notice', 'Business details is now required. Approvals from now on follow the new settings.');

    expect($suppliers->checklistSetting('business_details')?->required)->toBeTrue();

    Livewire::test(SuppliersPage::class)
        ->callAction('settings', data: ['required_business_details' => true, 'required_contacts' => false, 'reason' => 'No change'])
        ->assertHasActionErrors(['reason']);
});

it('marks an optional item "(optional)" on the Supplier page', function () {
    $admin = StaffConsole::admin();
    app(SetChecklistItemRequired::class)->handle($admin, 'business_details', false, 'Checked later');
    $supplier = app(AddSupplier::class)->handle($admin, ['legal_name' => 'Brightwave Wholesale']);
    StaffConsole::signIn($this, $admin);

    $this->get('/console/suppliers/'.$supplier->id)->assertOk()->assertSee('Business details (optional)');
});
