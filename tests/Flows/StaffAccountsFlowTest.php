<?php

use App\Modules\Notifications\Contracts\EmailProvider;
use App\Modules\Notifications\Fakes\FakeEmailProvider;
use App\Modules\Shared\Actions\RelayOutboxEvents;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Providers\ScriptedOutcome;
use App\Modules\Shared\Queries\AuditTrail;
use App\Modules\Shared\StatusTables\TransitionRefused;
use App\Modules\Shared\Support\Id;
use App\Modules\Staff\Actions\AcceptInvitation;
use App\Modules\Staff\Actions\CreateStaffAccount;
use App\Modules\Staff\Actions\DeactivateStaffAccount;
use App\Modules\Staff\Actions\InvalidStaffDetails;
use App\Modules\Staff\Actions\InvitationUnusable;
use App\Modules\Staff\Actions\RecordStaffSession;
use App\Modules\Staff\Actions\ResendInvitation;
use App\Modules\Staff\Actions\ResetSignIn;
use App\Modules\Staff\Actions\SetAuthenticatorSecret;
use App\Modules\Staff\Filament\StaffResource\ListStaff;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use App\Modules\Staff\StatusTables\StaffAccountStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Support\StaffConsole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    app(Clock::class)->set('2026-11-03T09:00:00Z');
    StaffConsole::useConsole();
});

afterEach(fn () => app(Clock::class)->reset());

function emails(): FakeEmailProvider
{
    $fake = app(EmailProvider::class);
    assert($fake instanceof FakeEmailProvider);

    return $fake;
}

function relay(): int
{
    return app(RelayOutboxEvents::class)->handle();
}

/** @return list<object> */
function outbox(string $aggregateId): array
{
    return DB::table('outbox_events')->where('aggregate_id', $aggregateId)->orderBy('sequence')->get()->all();
}

/** @return list<string> */
function auditActions(StaffMember $member): array
{
    return array_map(fn ($entry) => $entry->action, app(AuditTrail::class)->forSubject('staff_members', $member->id));
}

it('creates an Invited account with an audit row naming the Admin and StaffAccountCreated in the outbox', function () {
    $admin = StaffConsole::admin();

    $member = app(CreateStaffAccount::class)->handle($admin, '  Bola Ade ', 'Bola@Example.TEST', StaffRole::Finance);

    expect($member->status)->toBe(StaffAccountStatus::Invited)
        ->and($member->email)->toBe('bola@example.test')
        ->and($member->name)->toBe('Bola Ade')
        ->and($member->role)->toBe(StaffRole::Finance)
        ->and($member->password)->toBeNull()
        ->and($member->invited_by)->toBe($admin->id)
        ->and($member->invitation_expires_at?->toIso8601ZuluString())->toBe('2026-11-06T09:00:00Z');

    $entries = app(AuditTrail::class)->forSubject('staff_members', $member->id);

    expect($entries)->toHaveCount(1)
        ->and($entries[0]->actorType)->toBe('staff')
        ->and($entries[0]->actorId)->toBe($admin->id)
        ->and($entries[0]->action)->toBe('staff.account_created')
        ->and($entries[0]->changes['email'])->toBe('changed')
        ->and($entries[0]->changes['invitation_token_hash'])->toBe('changed')
        ->and($entries[0]->changes['status'])->toEqual(['from' => null, 'to' => 'invited'])
        ->and(json_encode($entries[0]->changes))->not->toContain('bola@example.test');

    $events = outbox($member->id);

    expect($events)->toHaveCount(1)
        ->and($events[0]->type)->toBe('staff.account_created')
        ->and($events[0]->aggregate_type)->toBe('staff_member')
        ->and((int) $events[0]->sequence)->toBe(1)
        ->and($events[0]->relayed_at)->toBeNull()
        // The token travels encrypted; the staff table keeps only its hash.
        ->and($events[0]->payload)->not->toContain(StaffConsole::invitationToken($member));
});

it('writes the change, its audit row and its outbox event in one transaction', function () {
    $admin = StaffConsole::admin();
    $before = [StaffMember::query()->count(), DB::table('outbox_events')->count(), count(app(AuditTrail::class)->all())];

    try {
        DB::transaction(function () use ($admin): void {
            app(CreateStaffAccount::class)->handle($admin, 'Bola Ade', 'bola@example.test', StaffRole::Operations);

            throw new RuntimeException('roll it all back');
        });
    } catch (RuntimeException) {
    }

    expect([StaffMember::query()->count(), DB::table('outbox_events')->count(), count(app(AuditTrail::class)->all())])->toBe($before);
});

it('refuses a duplicate email, whatever its case, beside the field', function () {
    $admin = StaffConsole::admin();
    StaffConsole::invite($admin, email: 'bola@example.test');

    expect(fn () => app(CreateStaffAccount::class)->handle($admin, 'Another Bola', 'BOLA@example.test', StaffRole::Finance))
        ->toThrow(function (InvalidStaffDetails $e) {
            expect($e->field)->toBe('email')->and($e->getMessage())->toContain('already uses this email address');
        });

    expect(StaffMember::query()->count())->toBe(2);
});

it('emails one invitation through the email provider when the relay picks the event up', function () {
    $admin = StaffConsole::admin();
    $member = StaffConsole::invite($admin, StaffRole::Finance);

    expect(relay())->toBe(1);

    $sent = emails()->sentTo('bola.ade@example.test');

    expect($sent)->toHaveCount(1)
        ->and($sent[0]->toName)->toBe('Bola Ade')
        ->and($sent[0]->subject)->toBe('Your invitation to the integrator staff console')
        ->and($sent[0]->text)->toContain('Ada Founder has given you a Finance account')
        ->and($sent[0]->text)->toContain(url('/console/invitation/'.StaffConsole::invitationToken($member)))
        ->and($sent[0]->text)->toContain('until Friday 6 November 2026, 10:00 WAT');

    expect(outbox($member->id)[0]->relayed_at)->not->toBeNull()
        ->and(DB::table('notification_deliveries')->where('event_id', outbox($member->id)[0]->id)->value('status'))->toBe('sent');
});

it('sends no second email when the same event is relayed twice', function () {
    $member = StaffConsole::invite(StaffConsole::admin());
    relay();

    // As if the relay had died after sending but before marking the event relayed.
    DB::table('outbox_events')->where('aggregate_id', $member->id)->update(['relayed_at' => null]);

    expect(relay())->toBe(1)
        ->and(emails()->sent())->toHaveCount(1)
        ->and(emails()->calls())->toHaveCount(1)
        ->and(DB::table('notification_deliveries')->count())->toBe(1);
});

it('frees the delivery key when the provider errors, so the retry sends once', function () {
    $member = StaffConsole::invite(StaffConsole::admin());
    emails()->script(ScriptedOutcome::Error);

    expect(relay())->toBe(0)
        ->and(emails()->sent())->toBe([]);

    $event = outbox($member->id)[0];

    expect($event->relayed_at)->toBeNull()
        ->and($event->given_up_at)->toBeNull()
        ->and((int) $event->attempts)->toBe(1)
        ->and($event->last_error)->toContain('ProviderErrored')
        ->and(DB::table('notification_deliveries')->count())->toBe(0);

    // Not due again until the back-off has passed.
    expect(relay())->toBe(0);
    app(Clock::class)->advance('1 minute');

    expect(relay())->toBe(1)
        ->and(emails()->sent())->toHaveCount(1);

    relay();

    expect(emails()->sent())->toHaveCount(1);
});

it('gives a definitively refused invitation up at once, and the Staff page says it may not have arrived', function () {
    $admin = StaffConsole::admin();
    $member = StaffConsole::invite($admin);
    emails()->script(ScriptedOutcome::Refused);

    expect(relay())->toBe(0);

    $event = outbox($member->id)[0];

    expect($event->given_up_at)->not->toBeNull()
        ->and((int) $event->attempts)->toBe(1)
        ->and(DB::table('notification_deliveries')->count())->toBe(0);

    app(Clock::class)->advance('2 hours');

    expect(relay())->toBe(0)
        ->and(emails()->calls())->toHaveCount(1);

    StaffConsole::signIn($this, $admin)->get('/console/staff')
        ->assertOk()
        ->assertSee(ListStaff::INVITATION_IN_DOUBT);

    // A new invitation is a new event, and the doubt goes with the old one.
    app(ResendInvitation::class)->handle($admin, $member->id);
    relay();

    expect(emails()->sent())->toHaveCount(1);
    StaffConsole::signIn($this, $admin)->get('/console/staff')->assertDontSee(ListStaff::INVITATION_IN_DOUBT);
});

it('never sends again after a timeout, marks the email uncertain and says so on the Staff page', function () {
    $admin = StaffConsole::admin();
    $member = StaffConsole::invite($admin);
    emails()->script(ScriptedOutcome::Timeout);

    // The event is settled: retrying could send a second copy.
    expect(relay())->toBe(1);

    $event = outbox($member->id)[0];

    expect($event->relayed_at)->not->toBeNull()
        ->and(DB::table('notification_deliveries')->where('event_id', $event->id)->value('status'))->toBe('uncertain');

    DB::table('outbox_events')->where('id', $event->id)->update(['relayed_at' => null]);
    app(Clock::class)->advance('1 hour');
    relay();

    expect(emails()->calls())->toHaveCount(1)
        ->and(emails()->sent())->toBe([]);

    StaffConsole::signIn($this, $admin)->get('/console/staff')->assertSee(ListStaff::INVITATION_IN_DOUBT);
});

it('marks the email uncertain and sends nothing when an earlier relay died after committing its delivery key', function () {
    $member = StaffConsole::invite(StaffConsole::admin());
    $event = outbox($member->id)[0];

    // As if a relay had committed the key, called the provider and died before recording the answer.
    DB::table('notification_deliveries')->insert([
        'id' => Id::generate(), 'event_id' => $event->id, 'channel' => 'email', 'status' => 'sending', 'created_at' => app(Clock::class)->now(),
    ]);

    expect(relay())->toBe(1)
        ->and(emails()->calls())->toBe([])
        ->and(DB::table('notification_deliveries')->where('event_id', $event->id)->value('status'))->toBe('uncertain');
});

it('does not email a link that has expired by the time the relay picks it up', function () {
    $member = StaffConsole::invite(StaffConsole::admin());
    app(Clock::class)->advance('73 hours');

    expect(relay())->toBe(1)
        ->and(emails()->calls())->toBe([])
        ->and(DB::table('notification_deliveries')->count())->toBe(0);

    expect($member->refresh()->status)->toBe(StaffAccountStatus::Invited);
});

it('lets the invitee set a password, then the authenticator, and only then makes the account Active', function () {
    $admin = StaffConsole::admin();
    $member = StaffConsole::invite($admin);

    $accepted = app(AcceptInvitation::class)->handle(StaffConsole::invitationToken($member), StaffConsole::INVITEE_PASSWORD);

    expect($accepted->status)->toBe(StaffAccountStatus::Invited)
        ->and($accepted->invitation_token_hash)->toBeNull()
        ->and(Hash::check(StaffConsole::INVITEE_PASSWORD, (string) $accepted->password))->toBeTrue()
        ->and($accepted->canSignIn())->toBeTrue();

    app(SetAuthenticatorSecret::class)->handle($accepted, StaffConsole::SECRET);

    expect($accepted->refresh()->status)->toBe(StaffAccountStatus::Active)
        ->and(auditActions($member))->toBe(['staff.account_created', 'staff.invitation_accepted', 'staff.authenticator_set', 'staff.account_activated'])
        ->and(array_column(outbox($member->id), 'type'))->toBe(['staff.account_created', 'staff.account_activated']);
});

it('refuses a password that breaks the staff password rule, and keeps the link usable', function () {
    $member = StaffConsole::invite(StaffConsole::admin());
    $token = StaffConsole::invitationToken($member);

    expect(fn () => app(AcceptInvitation::class)->handle($token, 'short'))
        ->toThrow(InvalidStaffDetails::class, 'at least 12 characters');

    expect(app(AcceptInvitation::class)->memberFor($token)->id)->toBe($member->id);
});

it('says in words when a link has expired or has been used', function () {
    $member = StaffConsole::invite(StaffConsole::admin());
    $token = StaffConsole::invitationToken($member);

    app(Clock::class)->advance('72 hours');

    expect(fn () => app(AcceptInvitation::class)->handle($token, StaffConsole::INVITEE_PASSWORD))
        ->toThrow(InvitationUnusable::class, 'expired');

    app(Clock::class)->set('2026-11-03T10:00:00Z');
    app(AcceptInvitation::class)->handle($token, StaffConsole::INVITEE_PASSWORD);

    expect(fn () => app(AcceptInvitation::class)->handle($token, StaffConsole::INVITEE_PASSWORD))
        ->toThrow(InvitationUnusable::class, 'already been used');
});

it('resends an invitation with a new link and voids the old one', function () {
    $admin = StaffConsole::admin();
    $member = StaffConsole::invite($admin);
    $old = StaffConsole::invitationToken($member);
    relay();

    app(Clock::class)->advance('2 days');
    app(ResendInvitation::class)->handle($admin, $member->id);
    $new = StaffConsole::invitationToken($member);

    expect($new)->not->toBe($old)
        ->and($member->refresh()->invitation_expires_at?->toIso8601ZuluString())->toBe('2026-11-08T09:00:00Z')
        ->and(fn () => app(AcceptInvitation::class)->memberFor($old))->toThrow(InvitationUnusable::class, 'already been used');

    relay();

    expect(emails()->sent())->toHaveCount(2)
        ->and(emails()->sent()[1]->text)->toContain($new)
        ->and(auditActions($member))->toBe(['staff.account_created', 'staff.invitation_resent']);
});

it('does not email a link that a newer one has already replaced', function () {
    $admin = StaffConsole::admin();
    $member = StaffConsole::invite($admin);
    app(ResendInvitation::class)->handle($admin, $member->id);

    expect(relay())->toBe(2)
        ->and(emails()->sent())->toHaveCount(1)
        ->and(emails()->sent()[0]->text)->toContain(StaffConsole::invitationToken($member));
});

it('resets an Active member\'s sign-in: clears both factors, revokes sessions and sends a fresh invitation', function () {
    $admin = StaffConsole::admin();
    $member = StaffConsole::active($admin);
    DB::table('sessions')->insert(['id' => 'bola-session', 'payload' => '', 'last_activity' => 0]);
    app(RecordStaffSession::class)->handle($member->id, 'bola-session');
    relay();

    app(ResetSignIn::class)->handle($admin, $member->id);
    $member->refresh();

    expect($member->status)->toBe(StaffAccountStatus::Invited)
        ->and($member->password)->toBeNull()
        ->and($member->getAppAuthenticationSecret())->toBeNull()
        ->and($member->getAppAuthenticationRecoveryCodes())->toBeNull()
        ->and($member->canSignIn())->toBeFalse()
        ->and(DB::table('sessions')->where('id', 'bola-session')->exists())->toBeFalse()
        ->and(array_slice(auditActions($member), -2))->toBe(['staff.sign_in_reset', 'staff.sessions_revoked']);

    // The first invitation was used before the relay ran, so it was never sent; only the reset's is.
    relay();
    $email = emails()->sentTo($member->email);

    expect($email)->toHaveCount(1)
        ->and($email[0]->subject)->toBe('Set up your sign-in to the integrator staff console again')
        ->and($email[0]->text)->toContain('Ada Founder has reset your sign-in')
        ->and($email[0]->text)->toContain(StaffConsole::invitationToken($member));

    // They set both up again through the new link.
    app(AcceptInvitation::class)->handle(StaffConsole::invitationToken($member), 'a-brand-new-passphrase');
    app(SetAuthenticatorSecret::class)->handle($member->refresh(), StaffConsole::SECRET);

    expect($member->refresh()->status)->toBe(StaffAccountStatus::Active);
});

it('refuses the last active Admin resetting their own sign-in, in words', function () {
    $admin = StaffConsole::admin();

    expect(fn () => app(ResetSignIn::class)->handle($admin, $admin->id))
        ->toThrow(function (TransitionRefused $e) {
            expect($e->reason)->toContain('Ada Founder is the only active Admin')
                ->and($e->current)->toBe(StaffAccountStatus::Active);
        });

    expect($admin->refresh()->status)->toBe(StaffAccountStatus::Active)
        ->and($admin->getAppAuthenticationSecret())->not->toBeNull();
});

it('deactivates an account, revokes every session at once and writes the audit row', function () {
    $admin = StaffConsole::admin();
    $member = StaffConsole::active($admin);
    DB::table('sessions')->insert([
        ['id' => 'laptop', 'payload' => '', 'last_activity' => 0],
        ['id' => 'desktop', 'payload' => '', 'last_activity' => 0],
        ['id' => 'someone-else', 'payload' => '', 'last_activity' => 0],
    ]);
    app(RecordStaffSession::class)->handle($member->id, 'laptop');
    app(RecordStaffSession::class)->handle($member->id, 'desktop');
    app(RecordStaffSession::class)->handle($admin->id, 'someone-else');

    app(DeactivateStaffAccount::class)->handle($admin, $member->id);
    $member->refresh();

    expect($member->status)->toBe(StaffAccountStatus::Deactivated)
        ->and($member->deactivated_at?->toIso8601ZuluString())->toBe('2026-11-03T09:00:00Z')
        ->and($member->canSignIn())->toBeFalse()
        ->and(DB::table('sessions')->pluck('id')->all())->toBe(['someone-else'])
        ->and(DB::table('staff_sessions')->where('staff_member_id', $member->id)->count())->toBe(0);

    $entries = array_slice(app(AuditTrail::class)->forSubject('staff_members', $member->id), -2);

    expect($entries[0]->action)->toBe('staff.account_deactivated')
        ->and($entries[0]->actorId)->toBe($admin->id)
        ->and($entries[0]->changes['status'])->toEqual(['from' => 'active', 'to' => 'deactivated'])
        ->and($entries[1]->action)->toBe('staff.sessions_revoked')
        ->and($entries[1]->changes)->toEqual(['sessions' => ['from' => 2, 'to' => 0]])
        ->and(array_column(outbox($member->id), 'type'))->toContain('staff.account_deactivated');
});

it('deactivates an invitation nobody has answered yet, and the link stops working', function () {
    $admin = StaffConsole::admin();
    $member = StaffConsole::invite($admin);
    $token = StaffConsole::invitationToken($member);

    app(DeactivateStaffAccount::class)->handle($admin, $member->id);

    expect($member->refresh()->status)->toBe(StaffAccountStatus::Deactivated)
        ->and(fn () => app(AcceptInvitation::class)->memberFor($token))->toThrow(InvitationUnusable::class);
});

it('never deactivates the last active Admin, and says why', function () {
    $admin = StaffConsole::admin();

    expect(fn () => app(DeactivateStaffAccount::class)->handle($admin, $admin->id))
        ->toThrow(TransitionRefused::class, 'Ada Founder is the only active Admin, so the account cannot be deactivated');

    expect($admin->refresh()->status)->toBe(StaffAccountStatus::Active);
});

it('lets an Admin be deactivated while another Admin stays active', function () {
    $founder = StaffConsole::admin();
    $second = StaffConsole::active($founder, StaffRole::Admin, 'Chidi Admin');

    app(DeactivateStaffAccount::class)->handle($second, $founder->id);

    expect($founder->refresh()->status)->toBe(StaffAccountStatus::Deactivated)
        ->and(fn () => app(DeactivateStaffAccount::class)->handle($second, $second->id))
        ->toThrow(TransitionRefused::class, 'only active Admin');
});

it('refuses a second deactivation with who did it and when, and changes nothing', function () {
    $founder = StaffConsole::admin();
    $member = StaffConsole::active($founder);
    $second = StaffConsole::active($founder, StaffRole::Admin, 'Chidi Admin');

    app(Clock::class)->set('2026-11-03T13:05:00Z');
    app(DeactivateStaffAccount::class)->handle($second, $member->id);
    $audited = count(app(AuditTrail::class)->all());

    expect(fn () => app(DeactivateStaffAccount::class)->handle($founder, $member->id))
        ->toThrow(function (TransitionRefused $e) use ($second) {
            expect($e->current)->toBe(StaffAccountStatus::Deactivated)
                ->and($e->reason)->toBeNull()
                ->and($e->lastChange?->actorId)->toBe($second->id)
                ->and($e->lastChange?->occurredAt->toIso8601ZuluString())->toBe('2026-11-03T13:05:00Z');
        });

    expect(count(app(AuditTrail::class)->all()))->toBe($audited);
});

it('refuses every Staff Action to Finance and Operations on the server', function (StaffRole $role) {
    $founder = StaffConsole::admin();
    $actor = StaffConsole::active($founder, $role, 'Not An Admin');
    $member = StaffConsole::invite($founder, name: 'Dayo Target');

    expect(fn () => app(CreateStaffAccount::class)->handle($actor, 'Someone New', 'new@example.test', StaffRole::Admin))->toThrow(AuthorizationException::class)
        ->and(fn () => app(DeactivateStaffAccount::class)->handle($actor, $member->id))->toThrow(AuthorizationException::class)
        ->and(fn () => app(ResendInvitation::class)->handle($actor, $member->id))->toThrow(AuthorizationException::class)
        ->and(fn () => app(ResetSignIn::class)->handle($actor, $founder->id))->toThrow(AuthorizationException::class);

    expect(StaffMember::query()->where('email', 'new@example.test')->exists())->toBeFalse()
        ->and($member->refresh()->status)->toBe(StaffAccountStatus::Invited);
})->with([StaffRole::Finance, StaffRole::Operations]);

it('refuses a deactivated Admin\'s Actions', function () {
    $founder = StaffConsole::admin();
    $second = StaffConsole::active($founder, StaffRole::Admin, 'Chidi Admin');
    app(DeactivateStaffAccount::class)->handle($founder, $second->id);

    expect(fn () => app(CreateStaffAccount::class)->handle($second, 'Someone New', 'new@example.test', StaffRole::Admin))
        ->toThrow(AuthorizationException::class);
});

it('converts is_active into a status when the status migration runs, and back again', function () {
    // Back to before the status migration (and the delivery keys after it), then add accounts the old way.
    Artisan::call('migrate:rollback', ['--step' => 2, '--force' => true]);

    expect(Schema::hasColumn('staff_members', 'is_active'))->toBeTrue()
        ->and(Schema::hasColumn('staff_members', 'status'))->toBeFalse();

    $now = app(Clock::class)->now();
    $old = fn (string $id, string $email, bool $active, bool $founder) => [
        'id' => $id, 'name' => 'Person '.$email, 'email' => $email, 'password' => Hash::make('a long old password'),
        'role' => 'admin', 'is_founder' => $founder, 'is_active' => $active, 'created_at' => $now, 'updated_at' => $now,
    ];
    $founder = Id::generate();
    $former = Id::generate();
    DB::table('staff_members')->insert([$old($founder, 'ada@example.test', true, true), $old($former, 'old@example.test', false, false)]);

    Artisan::call('migrate', ['--force' => true]);

    expect(DB::table('staff_members')->where('id', $founder)->value('status'))->toBe('active')
        ->and(DB::table('staff_members')->where('id', $former)->value('status'))->toBe('deactivated')
        ->and(Schema::hasColumn('staff_members', 'is_active'))->toBeFalse();

    // Down again: an invitee who has set a password counts as active, an unanswered invitation does not.
    $founderMember = StaffMember::query()->findOrFail($founder);
    $answered = StaffConsole::invite($founderMember, StaffRole::Finance, 'Bola Ade');
    app(AcceptInvitation::class)->handle(StaffConsole::invitationToken($answered), StaffConsole::INVITEE_PASSWORD);
    $unanswered = StaffConsole::invite($founderMember, StaffRole::Operations, 'Chika Obi');
    DB::table('notification_deliveries')->delete();

    Artisan::call('migrate:rollback', ['--step' => 2, '--force' => true]);

    expect((bool) DB::table('staff_members')->where('id', $answered->id)->value('is_active'))->toBeTrue()
        ->and((bool) DB::table('staff_members')->where('id', $unanswered->id)->value('is_active'))->toBeFalse()
        ->and((bool) DB::table('staff_members')->where('id', $former)->value('is_active'))->toBeFalse()
        ->and((bool) DB::table('staff_members')->where('id', $founder)->value('is_active'))->toBeTrue();

    // The unanswered invitation has no password, so the old NOT NULL is not restored.
    expect(DB::selectOne("SELECT is_nullable FROM information_schema.columns WHERE table_name = 'staff_members' AND column_name = 'password'")->is_nullable)->toBe('YES');

    Artisan::call('migrate', ['--force' => true]);
});

it('lets a deactivated person be added again under the same email, as a new account', function () {
    $admin = StaffConsole::admin();
    $first = StaffConsole::active($admin, StaffRole::Finance, 'Bola Ade');
    app(DeactivateStaffAccount::class)->handle($admin, $first->id);

    $again = app(CreateStaffAccount::class)->handle($admin, 'Bola Ade', 'BOLA.ADE@example.test', StaffRole::Finance);

    expect($again->id)->not->toBe($first->id)
        ->and($again->status)->toBe(StaffAccountStatus::Invited)
        ->and($first->refresh()->status)->toBe(StaffAccountStatus::Deactivated);

    // But never a second current account under one address.
    expect(fn () => app(CreateStaffAccount::class)->handle($admin, 'Bola Ade', 'bola.ade@example.test', StaffRole::Finance))
        ->toThrow(InvalidStaffDetails::class, 'already uses this email address');

    $indexes = collect(DB::select("SELECT indexdef FROM pg_indexes WHERE tablename = 'staff_members'"))->pluck('indexdef')->implode("\n");

    expect($indexes)->toContain('UNIQUE INDEX staff_members_email_current_unique ON public.staff_members USING btree (email) WHERE ((status)::text = ANY')
        ->and($indexes)->not->toContain('staff_members_email_unique');
});

it('reports "email taken" only for the email constraint, by its name', function () {
    $admin = StaffConsole::admin();
    $member = StaffConsole::invite($admin);

    // A unique violation of another constraint whose words mention email is not "email taken".
    expect(fn () => DB::transaction(fn () => DB::table('staff_members')->where('id', $admin->id)->update([
        'invitation_token_hash' => DB::table('staff_members')->where('id', $member->id)->value('invitation_token_hash'),
    ])))->toThrow(function (UniqueConstraintViolationException $e) {
        expect($e->index)->toBe('staff_members_invitation_token_hash_unique');
    });
});

it('keeps the founder\'s existing Admin Active through the status migration', function () {
    expect(StaffConsole::admin()->status)->toBe(StaffAccountStatus::Active)
        ->and(DB::selectOne("SELECT count(*) AS n FROM information_schema.columns WHERE table_name = 'staff_members' AND column_name = 'is_active'")->n)->toBe(0);
});

it('starts a part-way invitee again on resend: password and second factor cleared, sessions revoked', function () {
    $admin = StaffConsole::admin();
    $member = StaffConsole::invite($admin);
    $accepted = app(AcceptInvitation::class)->handle(StaffConsole::invitationToken($member), StaffConsole::INVITEE_PASSWORD);
    DB::table('sessions')->insert(['id' => 'bola-setup', 'payload' => '', 'last_activity' => 0]);

    expect(app(RecordStaffSession::class)->handle($member->id, 'bola-setup'))->toBeTrue();

    app(ResendInvitation::class)->handle($admin, $member->id);
    $member->refresh();

    expect($member->status)->toBe(StaffAccountStatus::Invited)
        ->and($member->password)->toBeNull()
        ->and($member->invitation_accepted_at)->toBeNull()
        ->and($member->getAppAuthenticationSecret())->toBeNull()
        ->and($member->canSignIn())->toBeFalse()
        ->and(DB::table('sessions')->where('id', 'bola-setup')->exists())->toBeFalse()
        ->and(array_slice(auditActions($member), -2))->toBe(['staff.invitation_resent', 'staff.sessions_revoked']);

    // An authenticator save that lost the race to the resend is refused under the lock.
    expect(fn () => app(SetAuthenticatorSecret::class)->handle($accepted, StaffConsole::SECRET))
        ->toThrow(TransitionRefused::class, 'can no longer be set up');

    expect($member->refresh()->getAppAuthenticationSecret())->toBeNull()
        ->and($member->status)->toBe(StaffAccountStatus::Invited);
});

it('clears a second factor left from before when the invitation is accepted', function () {
    $member = StaffConsole::invite(StaffConsole::admin());
    DB::table('staff_members')->where('id', $member->id)->update(['app_authentication_secret' => Crypt::encryptString('LEFTOVERSECRET')]);

    $accepted = app(AcceptInvitation::class)->handle(StaffConsole::invitationToken($member), StaffConsole::INVITEE_PASSWORD);

    expect($accepted->refresh()->getAppAuthenticationSecret())->toBeNull()
        ->and(AcceptInvitation::isAwaitingAuthenticator($accepted))->toBeTrue();
});

it('bounds the whole set-up by the invitation\'s 72 hours: the password works only until the link would have expired', function () {
    $admin = StaffConsole::admin();
    $member = StaffConsole::invite($admin);
    app(Clock::class)->advance('70 hours');
    $accepted = app(AcceptInvitation::class)->handle(StaffConsole::invitationToken($member), StaffConsole::INVITEE_PASSWORD);

    expect($accepted->canSignIn())->toBeTrue()
        ->and($accepted->invitation_expires_at?->toIso8601ZuluString())->toBe('2026-11-06T09:00:00Z');

    app(Clock::class)->advance('2 hours');

    expect($accepted->refresh()->canSignIn())->toBeFalse()
        ->and(fn () => app(SetAuthenticatorSecret::class)->handle($accepted, StaffConsole::SECRET))->toThrow(TransitionRefused::class, 'hours ran out');

    // The Admin sends a new invitation, which starts the set-up again.
    app(ResendInvitation::class)->handle($admin, $member->id);

    expect($member->refresh()->password)->toBeNull()
        ->and($member->invitation_expires_at?->toIso8601ZuluString())->toBe('2026-11-09T09:00:00Z');
});

it('records no session for a member who may no longer sign in, so none lands after a revocation', function () {
    $admin = StaffConsole::admin();
    $member = StaffConsole::active($admin);
    app(DeactivateStaffAccount::class)->handle($admin, $member->id);

    expect(app(RecordStaffSession::class)->handle($member->id, 'late-session'))->toBeFalse()
        ->and(DB::table('staff_sessions')->where('staff_member_id', $member->id)->exists())->toBeFalse()
        ->and(app(RecordStaffSession::class)->handle($admin->id, 'admin-session'))->toBeTrue();
});
