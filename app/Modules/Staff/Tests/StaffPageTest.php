<?php

use App\Modules\Shared\Contracts\Clock;
use App\Modules\Staff\Actions\DeactivateStaffAccount;
use App\Modules\Staff\Filament\Pages\AcceptInvitationPage;
use App\Modules\Staff\Filament\StaffResource\ListStaff;
use App\Modules\Staff\Http\StaffSession;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use App\Modules\Staff\StatusTables\StaffAccountStatus;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\StaffConsole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(Clock::class)->set('2026-11-03T13:05:00Z');
    StaffConsole::useConsole();
});

afterEach(fn () => app(Clock::class)->reset());

it('shows every account in a console table with a caption, column headings, name and initial, role, duties and status', function () {
    $admin = StaffConsole::admin();
    StaffConsole::invite($admin, StaffRole::Finance, 'Bola Ade');
    StaffConsole::signIn($this, $admin);

    $html = $this->get('/console/staff')->assertOk()->getContent();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('<caption>')
        ->and($html)->toContain('Staff accounts: 2 people.')
        ->and($html)->toContain("J and K move between rows, Enter goes to the row's buttons, / goes to search.")
        ->and($html)->toContain('<th scope="col">Name</th>')
        ->and($html)->toContain('<th scope="col">Role</th>')
        ->and($html)->toContain('<th scope="col">Duties</th>')
        ->and($html)->toContain('<th scope="col">Status</th>')
        ->and($html)->toContain('data-console-table')
        ->and($html)->toContain('data-console-search')
        ->and($html)->toContain('<span class="console-initial" aria-hidden="true">B</span>')
        ->and($html)->toContain('Bola Ade')
        ->and($html)->toContain('Money: payments, credit notes and refunds')
        ->and($html)->toContain('Link works until Friday 6 November 2026, 14:05 WAT')
        ->and($html)->toContain('Ada Founder')
        ->and($html)->toContain('Everything, including staff accounts and settings')
        ->and($html)->not->toContain('Access denied');
});

it('refuses the Staff page to Finance and Operations on the server, in the console\'s words', function (StaffRole $role) {
    $member = StaffConsole::active(StaffConsole::admin(), $role, 'Not An Admin');
    StaffConsole::signIn($this, $member);

    $this->get('/console/staff')
        ->assertForbidden()
        ->assertSee('Only an Admin can open the Staff page.')
        ->assertDontSee('Access denied');

    $this->get('/console/today')->assertOk()->assertDontSee('href="'.url('/console/staff').'"', false);
})->with([StaffRole::Finance, StaffRole::Operations]);

it('refuses a Staff page call from a member who is not an Admin', function () {
    $founder = StaffConsole::admin();
    $finance = StaffConsole::active($founder, StaffRole::Finance, 'Not An Admin');
    $target = StaffConsole::invite($founder, name: 'Dayo Target');
    $this->actingAs($founder, 'staff');
    $page = Livewire::test(ListStaff::class);

    $this->actingAs($finance, 'staff');

    $page->call('mountAction', 'deactivate', ['member' => $target->id])->assertForbidden();
    expect($target->refresh()->status)->toBe(StaffAccountStatus::Invited);
});

it('adds an account from the dialog and says the invitation is on its way', function () {
    $admin = StaffConsole::admin();
    $this->actingAs($admin, 'staff');

    Livewire::test(ListStaff::class)
        ->callAction('create', data: ['name' => 'Bola Ade', 'email' => 'bola@example.test', 'role' => 'operations'])
        ->assertHasNoActionErrors()
        ->assertSet('notice', 'Added Bola Ade as Operations. An invitation is on its way to bola@example.test; the link works for 72 hours.')
        ->assertSee('Bola Ade');

    expect(StaffMember::query()->where('email', 'bola@example.test')->value('status'))->toBe(StaffAccountStatus::Invited);
});

it('refuses a duplicate email beside the field', function () {
    $admin = StaffConsole::admin();
    $this->actingAs($admin, 'staff');

    $page = Livewire::test(ListStaff::class)
        ->callAction('create', data: ['name' => 'Another Ada', 'email' => 'FOUNDER@example.test', 'role' => 'admin'])
        ->assertHasActionErrors(['email']);

    expect($page->errors()->first('mountedActions.0.data.email'))->toBe('A staff account already uses this email address. Each person needs their own address.');

    expect(StaffMember::query()->count())->toBe(1);
});

it('asks before deactivating, naming the person and listing what will happen, with only the irreversible button red', function () {
    $admin = StaffConsole::admin();
    $member = StaffConsole::active($admin, name: 'Bola Ade');
    $this->actingAs($admin, 'staff');

    Livewire::test(ListStaff::class)
        ->assertActionHasColor('deactivate', 'gray', ['member' => $member->id])
        ->mountAction('deactivate', arguments: ['member' => $member->id])
        ->assertMountedActionModalSee('Deactivate Bola Ade?')
        ->assertMountedActionModalSee('Bola Ade is signed out of the console everywhere, at once.')
        ->assertMountedActionModalSee('This cannot be undone')
        ->assertMountedActionModalSee('Keep the account')
        ->assertMountedActionModalSeeHtml('fi-color-danger')
        ->callMountedAction()
        ->assertSet('notice', 'Deactivated Bola Ade. They were signed out everywhere and can no longer sign in.');

    expect($member->refresh()->status)->toBe(StaffAccountStatus::Deactivated);
});

it('shows who won a race and when, never an error page', function () {
    $founder = StaffConsole::admin();
    $member = StaffConsole::active($founder, name: 'Bola Ade');
    $second = StaffConsole::active($founder, StaffRole::Admin, 'Chidi Admin');
    $this->actingAs($founder, 'staff');

    $page = Livewire::test(ListStaff::class)->mountAction('deactivate', arguments: ['member' => $member->id]);

    // Meanwhile the other Admin confirms the same dialog first.
    app(DeactivateStaffAccount::class)->handle($second, $member->id);

    $page->callMountedAction()
        ->assertSet('noticeIsRefusal', true)
        ->assertSet('notice', 'Bola Ade was already deactivated by Chidi Admin at 14:05 WAT on Tuesday 3 November 2026. Nothing else was changed.')
        ->assertSee('Bola Ade was already deactivated by Chidi Admin');
});

it('says in words why the last active Admin cannot be deactivated', function () {
    $admin = StaffConsole::admin();
    $this->actingAs($admin, 'staff');

    Livewire::test(ListStaff::class)
        ->callAction('deactivate', arguments: ['member' => $admin->id])
        ->assertSet('noticeIsRefusal', true)
        ->assertSee('Ada Founder is the only active Admin, so the account cannot be deactivated');

    expect($admin->refresh()->status)->toBe(StaffAccountStatus::Active);
});

it('resends an invitation and resets a sign-in from their dialogs', function () {
    $admin = StaffConsole::admin();
    $invited = StaffConsole::invite($admin, name: 'Bola Ade');
    $active = StaffConsole::active($admin, name: 'Chidi Okafor');
    $this->actingAs($admin, 'staff');

    Livewire::test(ListStaff::class)
        ->mountAction('resend', arguments: ['member' => $invited->id])
        ->assertMountedActionModalSee('Send Bola Ade a new invitation?')
        ->assertMountedActionModalSee('The link sent before stops working at once.')
        ->callMountedAction()
        ->assertSet('notice', 'Sent Bola Ade a new invitation. The earlier link no longer works.')
        ->mountAction('reset', arguments: ['member' => $active->id])
        ->assertMountedActionModalSee('Reset Chidi Okafor\'s sign-in?')
        ->assertMountedActionModalSee('Their password and authenticator app are cleared')
        ->callMountedAction()
        ->assertSet('notice', 'Reset Chidi Okafor\'s sign-in. A new invitation is on its way to chidi.okafor@example.test.');

    expect($active->refresh()->status)->toBe(StaffAccountStatus::Invited);
});

it('opens an invitation link to set a password, then sends the invitee to set up the authenticator before any console page', function () {
    $member = StaffConsole::invite(StaffConsole::admin(), name: 'Bola Ade');
    $token = StaffConsole::invitationToken($member);

    $html = $this->get('/console/invitation/'.$token)->assertOk()->getContent();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('Set up your staff console sign-in')
        ->and($html)->toContain('Welcome, Bola Ade.')
        ->and($html)->toContain('New password')
        ->and($html)->toContain('Type the password again');

    Livewire::test(AcceptInvitationPage::class, ['token' => $token])
        ->set('data.password', StaffConsole::INVITEE_PASSWORD)
        ->set('data.passwordConfirmation', StaffConsole::INVITEE_PASSWORD)
        ->call('accept')
        ->assertHasNoErrors()
        ->assertRedirect(Filament::getUrl());

    expect(Filament::auth()->id())->toBe($member->id);

    $this->withSession([StaffSession::KEY => app(Clock::class)->now()->getTimestamp()]);
    $this->get('/console/today')->assertRedirect('/console/multi-factor-authentication/set-up');
    $this->get('/console/staff')->assertRedirect('/console/multi-factor-authentication/set-up');
});

it('refuses a weak or mismatched password beside the field on the invitation page', function () {
    $member = StaffConsole::invite(StaffConsole::admin(), name: 'Bola Ade');
    $token = StaffConsole::invitationToken($member);

    Livewire::test(AcceptInvitationPage::class, ['token' => $token])
        ->set('data.password', 'my-bola.ade-password')
        ->set('data.passwordConfirmation', 'my-bola.ade-password')
        ->call('accept')
        ->assertHasErrors(['data.password'])
        ->assertSee('The password cannot contain the email address.')
        ->set('data.password', StaffConsole::INVITEE_PASSWORD)
        ->set('data.passwordConfirmation', 'something-else-entirely')
        ->call('accept')
        ->assertHasErrors(['data.passwordConfirmation'])
        ->assertSee('The two passwords do not match.');

    expect(Filament::auth()->check())->toBeFalse()
        ->and($member->refresh()->password)->toBeNull();
});

it('says in words when an invitation link has expired, and offers to ask an Admin', function () {
    $member = StaffConsole::invite(StaffConsole::admin());
    $token = StaffConsole::invitationToken($member);
    app(Clock::class)->advance('73 hours');

    $this->get('/console/invitation/'.$token)
        ->assertOk()
        ->assertSee('This invitation link does not work')
        ->assertSee('This invitation link has expired: links work for 72 hours. Ask an Admin to send you a new one.')
        ->assertDontSee('New password');

    $this->get('/console/invitation/notARealTokenAtAll')
        ->assertOk()
        ->assertSee('has already been used, or a newer one has replaced it')
        ->assertSee('ask an Admin to send you a new one');
});

it('records a signed-in member\'s session, and after deactivation their next request lands on the sign-in page', function () {
    $founder = StaffConsole::admin();
    $member = StaffConsole::active($founder, name: 'Bola Ade');
    $this->withSession([
        Auth::guard('staff')->getName() => $member->id,
        StaffSession::KEY => app(Clock::class)->now()->getTimestamp(),
    ]);

    $this->get('/console/today')->assertOk();

    expect(DB::table('staff_sessions')->where('staff_member_id', $member->id)->count())->toBe(1);

    app(DeactivateStaffAccount::class)->handle($founder, $member->id);
    Auth::guard('staff')->forgetUser(); // as a fresh request would, the member is read again

    $this->get('/console/today')->assertRedirect('/console/login');
    $this->get('/console/login')->assertSee('your staff account is no longer active');

    expect(DB::table('staff_sessions')->where('staff_member_id', $member->id)->count())->toBe(0);
});

it('answers a Livewire request from a deactivated member with the 401 the console script signs out on', function () {
    $founder = StaffConsole::admin();
    $member = StaffConsole::active($founder, name: 'Bola Ade');
    $this->withSession([
        Auth::guard('staff')->getName() => $member->id,
        StaffSession::KEY => app(Clock::class)->now()->getTimestamp(),
    ]);
    app(DeactivateStaffAccount::class)->handle($founder, $member->id);
    Auth::guard('staff')->forgetUser();

    $this->get('/console/today', ['X-Livewire' => 'true'])
        ->assertStatus(401)
        ->assertHeader('X-Staff-Session', 'ended');
});

it('lists in the Add dialog what will happen', function () {
    $this->actingAs(StaffConsole::admin(), 'staff');

    Livewire::test(ListStaff::class)
        ->mountAction('create')
        ->assertMountedActionModalSee('What will happen:')
        ->assertMountedActionModalSee('They get an Invited account, which cannot sign in until they finish setting it up.')
        ->assertMountedActionModalSee('a link that works for 72 hours')
        ->assertMountedActionModalSee('they choose a password, then set up an authenticator app');
});

it('warns an Admin deactivating or resetting their own account that they will be signed out at once', function () {
    $founder = StaffConsole::admin();
    StaffConsole::active($founder, StaffRole::Admin, 'Chidi Admin');
    $other = StaffConsole::active($founder, name: 'Bola Ade');
    $this->actingAs($founder, 'staff');

    Livewire::test(ListStaff::class)
        ->mountAction('deactivate', arguments: ['member' => $founder->id])
        ->assertMountedActionModalSee(ListStaff::OWN_ACCOUNT)
        ->unmountAction()
        ->mountAction('reset', arguments: ['member' => $founder->id])
        ->assertMountedActionModalSee(ListStaff::OWN_ACCOUNT)
        ->unmountAction()
        ->mountAction('deactivate', arguments: ['member' => $other->id])
        ->assertMountedActionModalDontSee(ListStaff::OWN_ACCOUNT);
});

it('shows the refusal in words when the acting Admin lost Admin rights meanwhile, not an error page', function () {
    $founder = StaffConsole::admin();
    $second = StaffConsole::active($founder, StaffRole::Admin, 'Chidi Admin');
    $member = StaffConsole::active($founder, name: 'Bola Ade');
    $this->actingAs($founder, 'staff');

    $page = Livewire::test(ListStaff::class)->mountAction('deactivate', arguments: ['member' => $member->id]);

    // Meanwhile the other Admin deactivates this one.
    app(DeactivateStaffAccount::class)->handle($second, $founder->id);

    $page->callMountedAction()
        ->assertSet('noticeIsRefusal', true)
        ->assertSet('notice', ListStaff::NO_LONGER_ADMIN);

    expect($member->refresh()->status)->toBe(StaffAccountStatus::Active);
});

it('takes a member id in any case, and answers a malformed one with a 404 in words', function () {
    $admin = StaffConsole::admin();
    $member = StaffConsole::invite($admin, name: 'Bola Ade');
    $this->actingAs($admin, 'staff');

    Livewire::test(ListStaff::class)
        ->callAction('resend', arguments: ['member' => strtoupper($member->id)])
        ->assertSet('notice', 'Sent Bola Ade a new invitation. The earlier link no longer works.');

    Livewire::test(ListStaff::class)
        ->callAction('deactivate', arguments: ['member' => 'not-a-uuid'])
        ->assertStatus(404);

    Livewire::test(ListStaff::class)
        ->callAction('deactivate', arguments: ['member' => '01900000-0000-7000-8000-000000000000'])
        ->assertStatus(404);

    expect($member->refresh()->status)->toBe(StaffAccountStatus::Invited);
});

it('gives the table rows no label of their own, so screen readers read their cells', function () {
    $admin = StaffConsole::admin();
    StaffConsole::invite($admin, StaffRole::Operations, 'Bola Ade');
    StaffConsole::signIn($this, $admin);

    $html = $this->get('/console/staff')->assertOk()->getContent();

    expect(preg_match('/<tr[^>]*aria-label/', $html))->toBe(0)
        ->and($html)->toContain('Goods, content and customer cases')
        ->and($html)->not->toContain('(Support)');
});
