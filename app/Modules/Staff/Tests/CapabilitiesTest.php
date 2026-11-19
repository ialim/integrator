<?php

use App\Modules\Shared\Capabilities\CapabilityGroup;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Queries\AuditTrail;
use App\Modules\Shared\StatusTables\TransitionRefused;
use App\Modules\Staff\Actions\CreateStaffAccount;
use App\Modules\Staff\Actions\DeactivateStaffAccount;
use App\Modules\Staff\Actions\ResendInvitation;
use App\Modules\Staff\Actions\ResetSignIn;
use App\Modules\Staff\Actions\SetSupportDuties;
use App\Modules\Staff\Capabilities\CapabilityRefused;
use App\Modules\Staff\Capabilities\CapabilityRegistry;
use App\Modules\Staff\Capabilities\StaffCapabilities;
use App\Modules\Staff\Filament\RefusalWords;
use App\Modules\Staff\Filament\StaffResource\ListStaff;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use App\Modules\Staff\StatusTables\StaffAccountStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\Support\StaffConsole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(Clock::class)->set('2026-11-06T09:00:00Z');
    StaffConsole::useConsole();
});

afterEach(fn () => app(Clock::class)->reset());

function capabilityRegistry(): CapabilityRegistry
{
    return app(CapabilityRegistry::class);
}

/**
 * Staff's own capabilities, in the order Staff declares them; other modules declare theirs alongside.
 *
 * @return list<string>
 */
function staffCapabilityNames(): array
{
    $staff = [StaffCapabilities::TODAY, StaffCapabilities::MANAGE_STAFF, StaffCapabilities::SUPPORT_DESK, StaffCapabilities::VIEW_AUDIT];

    return array_values(array_filter(capabilityRegistry()->names(), fn (string $name): bool => in_array($name, $staff, true)));
}

/** The rail's group labels, in the order the page renders them. */
function railGroupLabels(string $html): array
{
    preg_match_all('/data-group-label="([^"]+)"/', $html, $matches);

    return $matches[1];
}

it('holds each capability for the right groups, with Admin holding every one', function () {
    expect(capabilityRegistry()->groupsHolding(StaffCapabilities::TODAY))->toBe([CapabilityGroup::Finance, CapabilityGroup::Operations, CapabilityGroup::SupportDuties, CapabilityGroup::Admin])
        ->and(capabilityRegistry()->groupsHolding(StaffCapabilities::MANAGE_STAFF))->toBe([CapabilityGroup::Admin])
        ->and(capabilityRegistry()->groupsHolding(StaffCapabilities::SUPPORT_DESK))->toBe([CapabilityGroup::SupportDuties, CapabilityGroup::Admin])
        ->and(capabilityRegistry()->groupsHolding(StaffCapabilities::VIEW_AUDIT))->toBe([CapabilityGroup::Admin])
        ->and(staffCapabilityNames())->toBe([StaffCapabilities::TODAY, StaffCapabilities::MANAGE_STAFF, StaffCapabilities::SUPPORT_DESK, StaffCapabilities::VIEW_AUDIT]);
});

it('allows each role only its own capabilities, and Support duties only while the switch is On', function () {
    $admin = StaffConsole::admin();
    $finance = StaffConsole::active($admin, StaffRole::Finance, 'Bola Finance');
    $operations = StaffConsole::active($admin, StaffRole::Operations, 'Chika Operations');

    $matrix = fn (StaffMember $member): array => array_map(fn (string $capability) => capabilityRegistry()->allows($member->refresh(), $capability), staffCapabilityNames());

    expect($matrix($admin))->toBe([true, true, true, true])
        ->and($matrix($finance))->toBe([true, false, false, false])
        ->and($matrix($operations))->toBe([true, false, false, false])
        ->and(capabilityRegistry()->groupsOf($operations))->toBe([CapabilityGroup::Operations]);

    app(SetSupportDuties::class)->handle($admin, $operations->id, true);

    expect($matrix($operations))->toBe([true, false, true, false])
        ->and(capabilityRegistry()->groupsOf($operations))->toBe([CapabilityGroup::Operations, CapabilityGroup::SupportDuties]);

    app(SetSupportDuties::class)->handle($admin, $operations->id, false);

    expect($matrix($operations))->toBe([true, false, false, false]);
});

it('refuses an unknown capability rather than quietly allowing it', function () {
    expect(fn () => capabilityRegistry()->allows(StaffConsole::admin(), 'orders.cancel'))->toThrow(LogicException::class, 'orders.cancel');
});

it('allows nothing to an account that cannot sign in', function () {
    $admin = StaffConsole::admin();
    $invited = StaffConsole::invite($admin, StaffRole::Operations, 'Bola Ade');
    $gone = StaffConsole::active($admin, StaffRole::Finance, 'Dayo Gone');
    StaffConsole::deactivate($gone);

    expect(capabilityRegistry()->allows($invited->refresh(), StaffCapabilities::TODAY))->toBeFalse()
        ->and(capabilityRegistry()->allows($gone->refresh(), StaffCapabilities::TODAY))->toBeFalse();
});

it('words the duties from the registry', function () {
    $admin = StaffConsole::admin();
    $finance = StaffConsole::invite($admin, StaffRole::Finance, 'Bola Finance');
    $operations = StaffConsole::invite($admin, StaffRole::Operations, 'Chika Operations');

    expect(capabilityRegistry()->duties($admin))->toBe('Everything')
        ->and(capabilityRegistry()->duties($finance))->toBe('Money')
        ->and(capabilityRegistry()->duties($operations))->toBe('Goods');

    app(SetSupportDuties::class)->handle($admin, $operations->id, true);

    expect(capabilityRegistry()->duties($operations->refresh()))->toBe('Goods and Support')
        ->and(capabilityRegistry()->whoCan(StaffCapabilities::SUPPORT_DESK))->toBe('Operations staff with Support duties and Admins')
        ->and(capabilityRegistry()->whoCan(StaffCapabilities::TODAY))->toBe('Finance staff, Operations staff, Operations staff with Support duties and Admins')
        ->and(capabilityRegistry()->whoCan(StaffCapabilities::MANAGE_STAFF))->toBe('Admins');
});

it('names the alphabetically first active holder when refusing', function () {
    $founder = StaffConsole::admin();
    StaffConsole::active($founder, StaffRole::Admin, 'Zainab Admin');
    StaffConsole::invite($founder, StaffRole::Admin, 'Aaron Invited');
    $operations = StaffConsole::active($founder, StaffRole::Operations, 'Chika Operations');

    expect(capabilityRegistry()->pageRefusal('Staff', StaffCapabilities::MANAGE_STAFF, $operations))->toBe('The Staff page is for Admins. Ask Ada Founder.')
        ->and(capabilityRegistry()->pageRefusal('Staff', StaffCapabilities::MANAGE_STAFF, $founder))->toBe('The Staff page is for Admins. Ask Zainab Admin.');

    app(SetSupportDuties::class)->handle($founder, $operations->id, true);

    expect(capabilityRegistry()->activeHolders(StaffCapabilities::SUPPORT_DESK))->sequence(
        fn ($holder) => $holder->name->toBe('Ada Founder'),
        fn ($holder) => $holder->name->toBe('Chika Operations'),
        fn ($holder) => $holder->name->toBe('Zainab Admin'),
    );
});

it('shows Finance only its own groups: Queues with Today, marked as the current place, and Rules', function () {
    $finance = StaffConsole::active(StaffConsole::admin(), StaffRole::Finance, 'Bola Finance');
    StaffConsole::signIn($this, $finance);

    $html = $this->get('/console/today')->assertOk()->getContent();

    expect(railGroupLabels($html))->toBe(['Queues', 'Rules'])
        ->and($html)->not->toContain('href="'.url('/console/staff').'"')
        ->and($html)->toMatch('/<ul[^>]*aria-labelledby="(fi-sidebar-group-label-queues-[0-9a-f]{8})"/')
        ->and($html)->toMatch('/<span id="fi-sidebar-group-label-queues-[0-9a-f]{8}" class="fi-sidebar-group-label">\s*Queues\s*<\/span>/')
        ->and($html)->toMatch('/href="'.preg_quote(url('/console/today'), '/').'"[^>]*aria-current="page"|aria-current="page"[^>]*href="'.preg_quote(url('/console/today'), '/').'"/')
        ->and($html)->not->toContain('lock-closed')
        ->and($html)->not->toContain('People');
});

it('shows an Admin every group that has a page, People with Staff included, in rail order', function () {
    StaffConsole::signIn($this, StaffConsole::admin());

    $html = $this->get('/console/today')->assertOk()->getContent();

    expect(railGroupLabels($html))->toBe(['Queues', 'People', 'Rules', 'Watch'])
        ->and($html)->toContain('href="'.url('/console/staff').'"')
        ->and($html)->toMatch('/<ul[^>]*aria-labelledby="fi-sidebar-group-label-people-[0-9a-f]{8}"/');

    // On the Staff page the Staff link is the current place.
    $staff = $this->get('/console/staff')->assertOk()->getContent();
    expect($staff)->toMatch('/href="'.preg_quote(url('/console/staff'), '/').'"[^>]*aria-current="page"|aria-current="page"[^>]*href="'.preg_quote(url('/console/staff'), '/').'"/');
});

it('refuses Operations the Staff page on load, inside the shell, naming an active Admin', function () {
    $founder = StaffConsole::admin();
    $operations = StaffConsole::active($founder, StaffRole::Operations, 'Chika Operations');
    StaffConsole::signIn($this, $operations);

    $this->get('/console/staff')
        ->assertForbidden()
        ->assertSee('The Staff page is for Admins. Ask Ada Founder.')
        ->assertSee('Chika Operations')
        ->assertDontSee('Access denied')
        ->assertDontSee('Forbidden');
});

it('refuses every Staff Action called by Finance, in words naming who can, changing nothing and auditing nothing', function () {
    $founder = StaffConsole::admin();
    $finance = StaffConsole::active($founder, StaffRole::Finance, 'Bola Finance');
    $operations = StaffConsole::active($founder, StaffRole::Operations, 'Chika Operations');
    $audited = count(app(AuditTrail::class)->all());
    $words = 'Nothing was changed: managing staff accounts and their Support duties is for Admins. Ask Ada Founder.';

    foreach ([
        fn () => app(CreateStaffAccount::class)->handle($finance, 'Someone New', 'new@example.test', StaffRole::Admin),
        fn () => app(DeactivateStaffAccount::class)->handle($finance, $operations->id),
        fn () => app(ResendInvitation::class)->handle($finance, $operations->id),
        fn () => app(ResetSignIn::class)->handle($finance, $operations->id),
        fn () => app(SetSupportDuties::class)->handle($finance, $operations->id, true),
    ] as $call) {
        expect($call)->toThrow(CapabilityRefused::class, $words);
    }

    expect(count(app(AuditTrail::class)->all()))->toBe($audited)
        ->and($operations->refresh()->support_duties)->toBeFalse()
        ->and($operations->status)->toBe(StaffAccountStatus::Active)
        ->and(StaffMember::query()->where('email', 'new@example.test')->exists())->toBeFalse();
});

it('switches Support duties On from the Staff page, audited, effective on the member\'s next request', function () {
    $founder = StaffConsole::admin();
    $operations = StaffConsole::active($founder, StaffRole::Operations, 'Chika Operations');
    $this->actingAs($founder, 'staff');

    Livewire::test(ListStaff::class)
        ->assertSeeHtml('role="switch"')
        ->assertSeeHtml('aria-checked="false"')
        ->assertSeeHtml('<span class="sr-only">for Chika Operations:</span>')
        ->assertSeeHtml('wire:loading.attr="aria-busy"')
        ->assertDontSeeHtml('wire:loading.attr="disabled"')
        ->call('switchSupportDuties', $operations->id, true)
        ->assertSet('noticeIsRefusal', false)
        ->assertSet('notice', 'Support duties are now On for Chika Operations. It applies from their next page or click.')
        ->assertSeeHtml('aria-checked="true"')
        ->assertSeeHtml('<span class="console-switch-word">On</span>')
        ->assertSeeHtml('<span class="console-duties">Goods and Support</span>');

    $entry = app(AuditTrail::class)->latestFor('staff_members', $operations->id);

    expect($entry?->action)->toBe('staff.support_duties_set')
        ->and($entry?->actorId)->toBe($founder->id)
        ->and($entry?->changes)->toBe(['support_duties' => ['to' => true, 'from' => false]]);

    // The member's next request reads their account afresh: the Support duties group is theirs.
    expect(capabilityRegistry()->allows(StaffMember::query()->findOrFail($operations->id), StaffCapabilities::SUPPORT_DESK))->toBeTrue();
});

it('switches Support duties Off, audited, and the member loses the group', function () {
    $founder = StaffConsole::admin();
    $operations = StaffConsole::active($founder, StaffRole::Operations, 'Chika Operations');
    app(SetSupportDuties::class)->handle($founder, $operations->id, true);
    $this->actingAs($founder, 'staff');

    Livewire::test(ListStaff::class)
        ->call('switchSupportDuties', $operations->id, false)
        ->assertSet('notice', 'Support duties are now Off for Chika Operations. It applies from their next page or click.')
        ->assertSeeHtml('<span class="console-switch-word">Off</span>');

    expect(app(AuditTrail::class)->latestFor('staff_members', $operations->id)?->changes)->toBe(['support_duties' => ['to' => false, 'from' => true]])
        ->and(capabilityRegistry()->allows(StaffMember::query()->findOrFail($operations->id), StaffCapabilities::SUPPORT_DESK))->toBeFalse();
});

it('writes no audit row when the switch already has the asked value', function () {
    $founder = StaffConsole::admin();
    $operations = StaffConsole::active($founder, StaffRole::Operations, 'Chika Operations');
    $audited = count(app(AuditTrail::class)->all());

    app(SetSupportDuties::class)->handle($founder, $operations->id, false);

    expect(count(app(AuditTrail::class)->all()))->toBe($audited);
});

it('offers the switch only on Operations accounts that are not deactivated', function () {
    $founder = StaffConsole::admin();
    StaffConsole::active($founder, StaffRole::Finance, 'Bola Finance');
    StaffConsole::invite($founder, StaffRole::Operations, 'Chika Invited');
    $gone = StaffConsole::active($founder, StaffRole::Operations, 'Dayo Gone');
    StaffConsole::deactivate($gone);
    StaffConsole::signIn($this, $founder);

    $html = $this->get('/console/staff')->assertOk()->getContent();

    expect(substr_count($html, 'data-support-duties'))->toBe(1);
});

it('refuses the switch on a Finance, Admin or deactivated account, in words', function (string $kind, string $words) {
    $founder = StaffConsole::admin();
    $member = match ($kind) {
        'finance' => StaffConsole::active($founder, StaffRole::Finance, 'Bola Finance'),
        'admin' => StaffConsole::active($founder, StaffRole::Admin, 'Bola Admin'),
        'deactivated' => tap(StaffConsole::active($founder, StaffRole::Operations, 'Bola Gone'), fn ($m) => StaffConsole::deactivate($m)),
    };
    $audited = count(app(AuditTrail::class)->all());

    expect(fn () => app(SetSupportDuties::class)->handle($founder, $member->id, true))
        ->toThrow(fn (TransitionRefused $e) => expect($e->reason)->toBe($words));

    expect($member->refresh()->support_duties)->toBeFalse()
        ->and(count(app(AuditTrail::class)->all()))->toBe($audited);
})->with([
    ['finance', 'Support duties are only for Operations accounts. Bola Finance has a Finance account, so nothing was changed.'],
    ['admin', 'Support duties are only for Operations accounts. Bola Admin has an Admin account, so nothing was changed.'],
    ['deactivated', "Bola Gone's account is deactivated, so their Support duties cannot be switched. Nothing was changed."],
]);

it('refuses a switch call from a member who is not an Admin on the server', function () {
    $founder = StaffConsole::admin();
    $finance = StaffConsole::active($founder, StaffRole::Finance, 'Bola Finance');
    $operations = StaffConsole::active($founder, StaffRole::Operations, 'Chika Operations');
    $this->actingAs($founder, 'staff');
    $page = Livewire::test(ListStaff::class);

    $this->actingAs($finance, 'staff');

    $page->call('switchSupportDuties', $operations->id, true)->assertForbidden();
    expect($operations->refresh()->support_duties)->toBeFalse();
});

it('shows a refused switch in words above the table when the Admin\'s own account stopped being active meanwhile', function () {
    $founder = StaffConsole::admin();
    $second = StaffConsole::active($founder, StaffRole::Admin, 'Chidi Admin');
    $operations = StaffConsole::active($founder, StaffRole::Operations, 'Chika Operations');
    $this->actingAs($founder, 'staff');
    $page = Livewire::test(ListStaff::class);

    app(DeactivateStaffAccount::class)->handle($second, $founder->id);

    $page->call('switchSupportDuties', $operations->id, true)
        ->assertSet('noticeIsRefusal', true)
        ->assertSet('notice', 'Your account is not active, so nothing was changed.');

    expect($operations->refresh()->support_duties)->toBeFalse();
});

it('starts every new account with Support duties Off', function () {
    $operations = StaffConsole::invite(StaffConsole::admin(), StaffRole::Operations, 'Chika Operations');

    expect($operations->refresh()->support_duties)->toBeFalse();
});

it('tells the second Admin the switch was already On, with who set it and when, and logs and audits nothing', function () {
    $founder = StaffConsole::admin();
    $operations = StaffConsole::active($founder, StaffRole::Operations, 'Chika Operations');
    app(SetSupportDuties::class)->handle($founder, $operations->id, true);
    $audited = count(app(AuditTrail::class)->all());
    $this->actingAs($founder, 'staff');
    Log::spy();

    Livewire::test(ListStaff::class)
        ->call('switchSupportDuties', $operations->id, true)
        ->assertSet('noticeIsRefusal', false)
        ->assertSet('notice', 'Support duties were already On for Chika Operations: Ada Founder switched them On at 10:00 am on Fri 6 Nov. Nothing was changed.');

    expect(count(app(AuditTrail::class)->all()))->toBe($audited);
    Log::shouldNotHaveReceived('info', ['staff.support_duties_set', Mockery::any()]);
});

it('says Off was already the value without a name when nobody has switched it', function () {
    $founder = StaffConsole::admin();
    $operations = StaffConsole::active($founder, StaffRole::Operations, 'Chika Operations');

    $result = app(SetSupportDuties::class)->handle($founder, $operations->id, false);

    expect($result->changed)->toBeFalse()
        ->and(app(RefusalWords::class)->supportDutiesAlready($result->member))->toBe('Support duties were already Off for Chika Operations. Nothing was changed.');
});

it('clears Support duties when the account is deactivated, in the same audit row; reset and resend keep them', function () {
    $founder = StaffConsole::admin();
    $operations = StaffConsole::active($founder, StaffRole::Operations, 'Chika Operations');
    $invited = StaffConsole::invite($founder, StaffRole::Operations, 'Dayo Invited');
    app(SetSupportDuties::class)->handle($founder, $operations->id, true);
    app(SetSupportDuties::class)->handle($founder, $invited->id, true);

    app(ResetSignIn::class)->handle($founder, $operations->id);
    app(ResendInvitation::class)->handle($founder, $invited->id);

    expect($operations->refresh()->support_duties)->toBeTrue()
        ->and($invited->refresh()->support_duties)->toBeTrue();

    app(DeactivateStaffAccount::class)->handle($founder, $operations->id);
    $deactivation = collect(app(AuditTrail::class)->forSubject('staff_members', $operations->id))
        ->firstWhere('action', 'staff.account_deactivated');

    expect($operations->refresh()->support_duties)->toBeFalse()
        ->and($deactivation?->changes['support_duties'] ?? null)->toBe(['to' => false, 'from' => true])
        ->and(capabilityRegistry()->duties($operations))->toBe('Goods');
});

it('never counts Support duties on a deactivated account, even if the switch were left On', function () {
    $founder = StaffConsole::admin();
    $operations = StaffConsole::active($founder, StaffRole::Operations, 'Chika Operations');
    $operations->forceFill(['support_duties' => true, 'status' => StaffAccountStatus::Deactivated]);

    expect(capabilityRegistry()->groupsOf($operations))->toBe([CapabilityGroup::Operations])
        ->and(capabilityRegistry()->duties($operations))->toBe('Goods');
});

it('tells an actor whose own account is not active that it is their account, not their role', function () {
    $founder = StaffConsole::admin();
    $second = StaffConsole::active($founder, StaffRole::Admin, 'Chidi Admin');
    $operations = StaffConsole::active($founder, StaffRole::Operations, 'Chika Operations');
    app(DeactivateStaffAccount::class)->handle($founder, $second->id);

    expect(fn () => app(SetSupportDuties::class)->handle($second, $operations->id, true))
        ->toThrow(CapabilityRefused::class, CapabilityRegistry::NOT_ACTIVE);
});

it('gives Today to an account that holds only the Support duties group', function () {
    expect(capabilityRegistry()->groupsHolding(StaffCapabilities::TODAY))->toContain(CapabilityGroup::SupportDuties);
});
