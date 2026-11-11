<?php

use App\Modules\Shared\Actions\SealAuditEntries;
use App\Modules\Shared\Actions\WriteAuditEntry;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Queries\AuditTrail as AuditTrailQuery;
use App\Modules\Shared\Support\Id;
use App\Modules\Staff\Actions\SetSupportDuties;
use App\Modules\Staff\Capabilities\CapabilityRegistry;
use App\Modules\Staff\Capabilities\StaffCapabilities;
use App\Modules\Staff\Filament\Pages\AuditTrail;
use App\Modules\Staff\Filament\StaffResource\ListStaff;
use App\Modules\Staff\Models\StaffRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\StaffConsole;

/*
 * The Audit trail page and the History panel (Story 1.6). One case tampers
 * with a row on purpose, so the file lives outside app/Modules, where the
 * single-write-path rule allows only Actions to write.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(Clock::class)->set('2026-11-09T09:00:00Z');
    StaffConsole::useConsole();
});

afterEach(fn () => app(Clock::class)->reset());

it('holds audit.view for Admins only', function () {
    $registry = app(CapabilityRegistry::class);
    $founder = StaffConsole::admin();

    expect($registry->allows($founder, StaffCapabilities::VIEW_AUDIT))->toBeTrue()
        ->and($registry->allows(StaffConsole::active($founder, StaffRole::Finance, 'Bola Finance'), StaffCapabilities::VIEW_AUDIT))->toBeFalse()
        ->and($registry->allows(StaffConsole::active($founder, StaffRole::Operations, 'Chika Operations'), StaffCapabilities::VIEW_AUDIT))->toBeFalse()
        ->and($registry->whoCan(StaffCapabilities::VIEW_AUDIT))->toBe('Admins');
});

it('shows an Admin the latest rows newest first, names for staff actors, in the Watch group, with no Edit, Delete or export', function () {
    $founder = StaffConsole::admin();
    $member = StaffConsole::invite($founder, StaffRole::Finance, 'Bola Ade');
    StaffConsole::signIn($this, $founder);

    $html = $this->get('/console/audit-trail')->assertOk()->getContent();

    $created = strpos($html, 'Account created');
    $firstAdmin = strpos($html, 'First admin created');

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('<caption>')
        ->and($html)->toContain('newest first')
        ->and($html)->toContain('<th scope="col">Sequence</th>')
        ->and($html)->toContain('Staff account: Bola Ade')
        ->and($html)->toContain('Email: changed')
        ->and($html)->toContain('Role: from none to finance')
        ->and($html)->toContain('Waiting')
        ->and($created)->toBeLessThan($firstAdmin)
        ->and($html)->toMatch('/fi-sidebar-group-label[^>]*>\s*Watch\s*</')
        ->and($html)->toContain('href="'.url('/console/audit-trail').'"')
        ->and($html)->not->toContain('bola.ade@example.test')
        ->and($html)->not->toContain('>Edit<')
        ->and($html)->not->toContain('>Delete<')
        ->and($html)->not->toContain('Export');

    // Names show for staff actors: the founder created Bola's account.
    expect($html)->toMatch('/Ada Founder<\/td>\s*<td>Account created/');
    expect($member->id)->not->toBeEmpty();
});

it('refuses the Audit trail page to Finance and Operations on the server, naming who can, inside the console shell', function (StaffRole $role) {
    $member = StaffConsole::active(StaffConsole::admin(), $role, 'Not An Admin');
    StaffConsole::signIn($this, $member);

    $html = $this->get('/console/audit-trail')
        ->assertForbidden()
        ->assertSee('The Audit trail page is for Admins. Ask Ada Founder.')
        ->assertSee('Staff console')
        ->assertDontSee('Access denied')
        ->assertDontSee('Check the chain')
        ->getContent();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->not->toContain('<caption>');

    $this->get('/console/today')->assertOk()->assertDontSee('href="'.url('/console/audit-trail').'"', false);
})->with([StaffRole::Finance, StaffRole::Operations]);

it('refuses Look up and the chain check to a member who is not an Admin', function (string $method) {
    $founder = StaffConsole::admin();
    $finance = StaffConsole::active($founder, StaffRole::Finance, 'Not An Admin');
    $this->actingAs($founder, 'staff');
    $page = Livewire::test(AuditTrail::class)->set('identifier', $founder->id);

    $this->actingAs($finance, 'staff');

    $page->call($method)->assertForbidden();
})->with(['lookUp', 'checkChain']);

it('looks up every row about a record, by a person, or done for a person', function () {
    $founder = StaffConsole::admin();
    $operations = StaffConsole::active($founder, StaffRole::Operations, 'Chika Operations');
    app(SetSupportDuties::class)->handle($founder, $operations->id, true);
    $this->actingAs($founder, 'staff');

    $bySubject = Livewire::test(AuditTrail::class)->set('identifier', strtoupper($operations->id))->call('lookUp');

    $bySubject->assertSet('lookedUp', $operations->id)
        ->assertSet('identifierError', null)
        ->assertSee("Rows for {$operations->id}, newest first")
        ->assertSee('Support duties: from no to yes')
        ->assertSee('Invitation accepted')
        ->assertDontSee('First admin created');

    // By actor: the founder's own rows, including those about other accounts.
    Livewire::test(AuditTrail::class)->set('identifier', $founder->id)->call('lookUp')
        ->assertSee('First admin created')
        ->assertSee('Support duties set');

    Livewire::test(AuditTrail::class)->set('identifier', $founder->id)->call('lookUp')->call('showLatest')
        ->assertSet('lookedUp', null)
        ->assertSee('of the audit trail, newest first');
});

it('finds a row done for a person, naming the role acted as and the person acted for', function () {
    $founder = StaffConsole::admin();
    $operations = StaffConsole::active($founder, StaffRole::Operations, 'Chika Operations');
    DB::transaction(fn () => app(WriteAuditEntry::class)->handle(
        Actor::staff($founder->id)->actingAs('finance')->actingFor($operations->id),
        'probe.confirmed',
        'probes',
        Id::generate(),
        [],
    ));
    $this->actingAs($founder, 'staff');

    Livewire::test(AuditTrail::class)->set('identifier', $operations->id)->call('lookUp')
        ->assertSee('Confirmed')
        ->assertSee('Ada Founder, acting as Finance, for Chika Operations');
});

it('says so when older rows are left out of the latest rows', function () {
    $founder = StaffConsole::admin();
    DB::transaction(function () {
        for ($i = 0; $i < AuditTrail::RECENT; $i++) {
            app(WriteAuditEntry::class)->handle(Actor::system(), 'probe.done', 'probes', Id::generate(), []);
        }
    });
    $this->actingAs($founder, 'staff');

    Livewire::test(AuditTrail::class)
        ->assertSee('The latest 50 rows of the audit trail, newest first.')
        ->assertSee('Showing the newest 50 rows; older rows are not shown.');
});

it('shows a row slipped in behind the chain as not sealed', function () {
    $founder = StaffConsole::admin();
    app(SealAuditEntries::class)->handle();
    DB::table('audit_entries')->insert([
        'id' => Id::generate(), 'sequence' => 0, 'occurred_at' => app(Clock::class)->now(), 'actor_type' => 'system',
        'action' => 'probe.slipped_in', 'subject_type' => 'probes', 'changes' => '{}',
    ]);
    $this->actingAs($founder, 'staff');

    $html = Livewire::test(AuditTrail::class)->html();

    expect($html)->toMatch('/Slipped in<\/td>(?:(?!<\/tr>).)*<td>Waiting<\/td>/s')
        ->and($html)->toMatch('/First admin created<\/td>(?:(?!<\/tr>).)*<td>Sealed<\/td>/s');
});

it('refuses a Look up that is not an identifier, in words beside the field, and stops showing the last one', function () {
    $founder = StaffConsole::admin();
    $this->actingAs($founder, 'staff');

    Livewire::test(AuditTrail::class)
        ->set('identifier', $founder->id)->call('lookUp')
        ->assertSet('lookedUp', $founder->id)
        ->set('identifier', 'bola@example.test')->call('lookUp')
        ->assertSet('lookedUp', null)
        ->assertDontSee("Rows for {$founder->id}")
        ->assertSet('identifierError', AuditTrail::NOT_AN_IDENTIFIER)
        ->assertSeeHtml('aria-invalid="true"')
        ->assertSeeHtml('aria-describedby="audit-lookup-hint audit-lookup-error"');
});

it('reports the chain intact up to the last sealed sequence, in a status region', function () {
    $founder = StaffConsole::admin();
    StaffConsole::invite($founder, StaffRole::Finance, 'Bola Ade');
    app(SealAuditEntries::class)->handle();
    $last = (int) DB::table('audit_seals')->max('sequence');
    $this->actingAs($founder, 'staff');

    Livewire::test(AuditTrail::class)
        ->assertSeeHtml('role="status" aria-live="polite" data-chain-result')
        ->call('checkChain')
        ->assertSet('chainBroken', false)
        ->assertSet('chainResult', "The chain is intact up to sequence {$last}.")
        ->assertSee('Sealed');
});

it('names the first broken sequence when a row no longer matches its seal', function () {
    $founder = StaffConsole::admin();
    app(SealAuditEntries::class)->handle();
    $first = (int) DB::table('audit_seals')->min('sequence');

    DB::statement('ALTER TABLE audit_entries DISABLE TRIGGER audit_entries_insert_only');
    DB::table('audit_entries')->where('sequence', $first)->update(['action' => 'staff.tampered']);
    DB::statement('ALTER TABLE audit_entries ENABLE ALWAYS TRIGGER audit_entries_insert_only');
    $this->actingAs($founder, 'staff');

    Livewire::test(AuditTrail::class)->call('checkChain')
        ->assertSet('chainBroken', true)
        ->assertSet('chainResult', "The chain is broken at sequence {$first}: the row's stored values no longer match its seal. Rows from there on cannot be trusted. Tell the founder.");
});

it('offers History on every staff row: read-only, newest first, titled with the person', function () {
    $founder = StaffConsole::admin();
    $operations = StaffConsole::active($founder, StaffRole::Operations, 'Chika Operations');
    app(SetSupportDuties::class)->handle($founder, $operations->id, true);
    StaffConsole::deactivate($operations);
    $this->actingAs($founder, 'staff');

    Livewire::test(ListStaff::class)
        ->assertActionVisible('history', ['member' => $operations->id])
        ->assertActionVisible('history', ['member' => $founder->id])
        ->mountAction('history', arguments: ['member' => $operations->id])
        ->assertMountedActionModalSee('History of Chika Operations')
        ->assertMountedActionModalSee('Read-only: it cannot be edited.')
        ->assertMountedActionModalSee('Support duties set')
        ->assertMountedActionModalSee('Support duties: from no to yes')
        ->assertMountedActionModalSee('Email: changed')
        ->assertMountedActionModalSee('by Ada Founder')
        ->assertMountedActionModalSee('Close')
        ->assertMountedActionModalDontSee('Edit')
        ->assertMountedActionModalDontSee('Delete')
        ->assertMountedActionModalDontSee('chika.operations@example.test');

    // Newest first.
    $actions = array_map(fn ($entry) => $entry->action, app(AuditTrailQuery::class)->historyOf('staff_members', $operations->id));

    expect(array_slice($actions, 0, 2))->toBe(['staff.sessions_revoked', 'staff.account_deactivated'])
        ->and(end($actions))->toBe('staff.account_created');
});
