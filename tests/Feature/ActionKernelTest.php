<?php

use App\Modules\Shared\Actions\RelayOutboxEvents;
use App\Modules\Shared\Actions\WriteOutboxEvent;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Events\OutboxEvent;
use App\Modules\Shared\Events\OutboxListener;
use App\Modules\Shared\Events\OutboxListeners;
use App\Modules\Shared\Events\OutboxMessage;
use App\Modules\Shared\Events\RelayOutbox;
use App\Modules\Shared\Http\QueryExceptionRedactor;
use App\Modules\Shared\Locks\LockOrder;
use App\Modules\Shared\Locks\LockRegistry;
use App\Modules\Shared\StatusTables\TransitionRefused;
use App\Modules\Shared\StatusTables\Transitions;
use App\Modules\Shared\Support\Id;
use App\Modules\Staff\Actions\RevokeStaffSessions;
use App\Modules\Staff\Locks\LockStaffMember;
use App\Modules\Staff\StatusTables\StaffAccountStatus;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Tests\Support\StaffConsole;

uses(RefreshDatabase::class);

beforeEach(fn () => app(Clock::class)->set('2026-11-03T09:00:00Z'));

afterEach(fn () => app(Clock::class)->reset());

final class KernelProbeEvent implements OutboxEvent
{
    public function __construct(private readonly string $id) {}

    public static function type(): string
    {
        return 'kernel.probe';
    }

    public function aggregateType(): string
    {
        return 'probe';
    }

    public function aggregateId(): string
    {
        return $this->id;
    }

    public function payload(): array
    {
        return ['probe' => $this->id];
    }
}

final class FailingProbeListener implements OutboxListener
{
    public function handle(OutboxMessage $message): void
    {
        throw new RuntimeException('listener down');
    }
}

final class RecordingProbeListener implements OutboxListener
{
    /** @var list<string> */
    public static array $seen = [];

    public function handle(OutboxMessage $message): void
    {
        self::$seen[] = "{$message->type}#{$message->sequence}:{$message->payload['probe']}";
    }
}

/** Runs $work inside an Action-style scope: a transaction the lock registry knows about. */
function inActionScope(Closure $work): mixed
{
    $locks = app(LockRegistry::class);
    $locks->enter();

    try {
        return DB::transaction($work);
    } finally {
        $locks->leave();
    }
}

it('takes a lock only inside an Action\'s transaction', function () {
    expect(fn () => app(LockStaffMember::class)->handle(Id::generate()))
        ->toThrow(LogicException::class, 'only inside an Action\'s transaction');
});

it('locks rows with SELECT ... FOR UPDATE in ascending id order, and refuses a lower id once a higher one is held', function () {
    $admin = StaffConsole::admin();
    $other = StaffConsole::invite($admin);
    [$low, $high] = strcmp($admin->id, $other->id) < 0 ? [$admin->id, $other->id] : [$other->id, $admin->id];

    $queries = [];
    DB::listen(function ($query) use (&$queries) {
        $queries[] = $query->sql;
    });

    inActionScope(function () use ($low, $high) {
        $locked = app(LockStaffMember::class)->handle($high, $low);

        expect(array_keys($locked))->toEqualCanonicalizing([$low, $high])
            ->and(app(LockRegistry::class)->isHeld(LockStaffMember::ROOT, $low))->toBeTrue();
    });

    expect(collect($queries)->first(fn ($sql) => str_contains($sql, 'for update')))->toContain('order by "id" asc');

    expect(fn () => inActionScope(function () use ($low, $high) {
        app(LockStaffMember::class)->handle($high);
        app(LockStaffMember::class)->handle($low);
    }))->toThrow(LogicException::class, 'ascending id order');

    // Leaving the outermost scope releases the registry.
    expect(app(LockRegistry::class)->isHeld(LockStaffMember::ROOT, $low))->toBeFalse();
});

it('knows the fixed global lock order and refuses a root that is not in it', function () {
    expect(LockOrder::rank('staff_member'))->toBe(0)
        ->and(fn () => LockOrder::rank('no_such_root'))->toThrow(LogicException::class, 'not in the fixed lock order');
});

it('makes a nested Action assert the lock its caller holds, instead of locking itself', function () {
    $member = StaffConsole::admin();

    expect(fn () => inActionScope(fn () => app(RevokeStaffSessions::class)->handle($member)))
        ->toThrow(LogicException::class, 'must be locked by the calling Action first');

    expect(inActionScope(function () use ($member) {
        app(LockStaffMember::class)->handle($member->id);

        return app(RevokeStaffSessions::class)->handle($member);
    }))->toBe(0);
});

it('reads a declared transition table and refuses a move it does not declare', function () {
    expect(Transitions::target(StaffAccountStatus::Active, 'deactivate'))->toBe(StaffAccountStatus::Deactivated)
        ->and(Transitions::target(StaffAccountStatus::Deactivated, 'deactivate'))->toBeNull()
        ->and(Transitions::target(StaffAccountStatus::Invited, 'activate'))->toBe(StaffAccountStatus::Active)
        ->and(Transitions::target(StaffAccountStatus::Active, 'reset_sign_in'))->toBe(StaffAccountStatus::Invited)
        ->and(Transitions::target(StaffAccountStatus::Deactivated, 'resend_invitation'))->toBeNull()
        ->and(fn () => Transitions::target(StaffAccountStatus::Active, 'reactivate'))->toThrow(LogicException::class, 'declares no move [reactivate]');
});

it('numbers each aggregate\'s outbox events from 1', function () {
    $a = Id::generate();
    $b = Id::generate();

    DB::transaction(function () use ($a, $b) {
        app(WriteOutboxEvent::class)->handle(new KernelProbeEvent($a));
        app(WriteOutboxEvent::class)->handle(new KernelProbeEvent($b));
        app(WriteOutboxEvent::class)->handle(new KernelProbeEvent($a));
    });

    expect(DB::table('outbox_events')->where('aggregate_id', $a)->orderBy('position')->pluck('sequence')->map(fn ($s) => (int) $s)->all())->toBe([1, 2])
        ->and((int) DB::table('outbox_events')->where('aggregate_id', $b)->value('sequence'))->toBe(1);
});

it('relays pending events oldest first to their listeners, once each', function () {
    RecordingProbeListener::$seen = [];
    app(OutboxListeners::class)->listen(KernelProbeEvent::class, RecordingProbeListener::class);
    $a = Id::generate();

    DB::transaction(function () use ($a) {
        app(WriteOutboxEvent::class)->handle(new KernelProbeEvent($a));
        app(WriteOutboxEvent::class)->handle(new KernelProbeEvent($a));
    });

    (new RelayOutbox)->handle(app(RelayOutboxEvents::class));
    app(RelayOutboxEvents::class)->handle();

    expect(RecordingProbeListener::$seen)->toBe(["kernel.probe#1:{$a}", "kernel.probe#2:{$a}"])
        ->and(DB::table('outbox_events')->whereNull('relayed_at')->count())->toBe(0);
});

it('retries a failing listener with back-off, then gives up after the last attempt and alerts the founder', function () {
    Mail::fake();
    config(['alerts.founder_email' => 'founder@example.test']);
    app(OutboxListeners::class)->listen(KernelProbeEvent::class, FailingProbeListener::class);
    DB::transaction(fn () => app(WriteOutboxEvent::class)->handle(new KernelProbeEvent(Id::generate())));

    foreach (range(1, RelayOutboxEvents::MAX_ATTEMPTS) as $attempt) {
        expect(app(RelayOutboxEvents::class)->handle())->toBe(0);
        app(Clock::class)->advance('1 hour');
    }

    $event = DB::table('outbox_events')->sole();

    expect((int) $event->attempts)->toBe(RelayOutboxEvents::MAX_ATTEMPTS)
        ->and($event->given_up_at)->not->toBeNull()
        ->and($event->last_error)->toBe(RuntimeException::class)
        ->and(app(RelayOutboxEvents::class)->handle())->toBe(0);
});

it('schedules the relay as a queued job every minute', function () {
    $event = collect(app(Schedule::class)->events())->first(fn ($event) => $event->description === 'outbox-relay');

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('* * * * *');
});

it('turns a refused move into a 409 with the current state under /api/v1', function () {
    Route::middleware('api')->get('api/v1/probe-refused', function () {
        throw TransitionRefused::because('Already done.', 'probes', Id::generate(), 'finish', StaffAccountStatus::Deactivated);
    });

    $this->getJson('/api/v1/probe-refused')
        ->assertStatus(409)
        ->assertJsonPath('code', 'transition_refused')
        ->assertJsonPath('message', 'Already done.')
        ->assertJsonPath('current.state', 'deactivated');
});

final class ProbeDatabaseError extends PDOException
{
    public function __construct(string $sqlState, string $message)
    {
        parent::__construct($message);
        $this->code = $sqlState;
        $this->errorInfo = [$sqlState, 7, null];
    }
}

final class ConcurrencyFailingProbeListener implements OutboxListener
{
    public static string $sqlState = '40P01';

    public function handle(OutboxMessage $message): void
    {
        DB::transaction(function (): void {
            // A real database error first, so the transaction is aborted as a deadlock leaves it.
            try {
                DB::select('SELECT 1 / 0');
            } catch (QueryException) {
            }

            $cause = new ProbeDatabaseError(self::$sqlState, self::$sqlState === '40001'
                ? 'SQLSTATE[40001]: Serialization failure: could not serialize access due to concurrent update'
                : 'SQLSTATE[40P01]: Deadlock detected: 7 ERROR:  deadlock detected');

            throw new QueryException('pgsql', 'UPDATE probes SET n = n + 1', [], $cause);
        });
    }
}

final class CountingProbeListener implements OutboxListener
{
    public static int $calls = 0;

    public function handle(OutboxMessage $message): void
    {
        self::$calls++;
    }
}

it('records a listener\'s deadlock or serialization failure as a counted attempt, with the connection rolled back clean', function (string $sqlState) {
    ConcurrencyFailingProbeListener::$sqlState = $sqlState;
    app(OutboxListeners::class)->listen(KernelProbeEvent::class, ConcurrencyFailingProbeListener::class);
    DB::transaction(fn () => app(WriteOutboxEvent::class)->handle(new KernelProbeEvent(Id::generate())));
    $level = DB::transactionLevel();

    expect(app(RelayOutboxEvents::class)->handle())->toBe(0);

    $event = DB::table('outbox_events')->sole();

    expect((int) $event->attempts)->toBe(1)
        ->and($event->last_error)->toContain('Exception')
        ->and($event->claimed_until)->toBeNull()
        ->and($event->given_up_at)->toBeNull()
        ->and($event->available_at)->toBe('2026-11-03 09:01:00+00')
        ->and(DB::transactionLevel())->toBe($level)
        ->and(DB::selectOne('SELECT 1 AS ok')->ok)->toBe(1);
})->with(['deadlock' => '40P01', 'serialization failure' => '40001']);

it('claims an event under a lease, so another relay skips it until the lease runs out, and re-checks that it is due', function () {
    CountingProbeListener::$calls = 0;
    app(OutboxListeners::class)->listen(KernelProbeEvent::class, CountingProbeListener::class);
    DB::transaction(fn () => app(WriteOutboxEvent::class)->handle(new KernelProbeEvent(Id::generate())));
    $id = DB::table('outbox_events')->value('id');

    // As if another relay had claimed it a moment ago and were still running its listeners.
    DB::table('outbox_events')->where('id', $id)->update(['attempts' => 1, 'claimed_until' => app(Clock::class)->now()->addMinutes(RelayOutboxEvents::LEASE_MINUTES)]);

    expect(app(RelayOutboxEvents::class)->handle())->toBe(0)
        ->and(CountingProbeListener::$calls)->toBe(0);

    // Not yet due by its back-off either: a relay never runs it early.
    DB::table('outbox_events')->where('id', $id)->update(['claimed_until' => null, 'available_at' => app(Clock::class)->now()->addMinute()]);

    expect(app(RelayOutboxEvents::class)->handle())->toBe(0);

    app(Clock::class)->advance('1 minute');

    expect(app(RelayOutboxEvents::class)->handle())->toBe(1)
        ->and(CountingProbeListener::$calls)->toBe(1)
        ->and((int) DB::table('outbox_events')->value('attempts'))->toBe(2);
});

it('takes an event up again once a dead relay\'s lease runs out, and gives it up when its attempts are spent', function () {
    Mail::fake();
    config(['alerts.founder_email' => 'founder@example.test']);
    CountingProbeListener::$calls = 0;
    app(OutboxListeners::class)->listen(KernelProbeEvent::class, CountingProbeListener::class);
    DB::transaction(fn () => app(WriteOutboxEvent::class)->handle(new KernelProbeEvent(Id::generate())));

    // A relay died holding the lease: once it runs out, the next relay takes the event up.
    DB::table('outbox_events')->update(['attempts' => 1, 'claimed_until' => app(Clock::class)->now()->addMinutes(RelayOutboxEvents::LEASE_MINUTES)]);
    app(Clock::class)->advance(RelayOutboxEvents::LEASE_MINUTES.' minutes');

    expect(app(RelayOutboxEvents::class)->handle())->toBe(1)
        ->and(CountingProbeListener::$calls)->toBe(1);

    // One that keeps killing its relay is given up once its attempts are spent, without running again.
    DB::transaction(fn () => app(WriteOutboxEvent::class)->handle(new KernelProbeEvent(Id::generate())));
    DB::table('outbox_events')->whereNull('relayed_at')->update(['attempts' => RelayOutboxEvents::MAX_ATTEMPTS, 'claimed_until' => app(Clock::class)->now()]);

    expect(app(RelayOutboxEvents::class)->handle())->toBe(0)
        ->and(CountingProbeListener::$calls)->toBe(1)
        ->and(DB::table('outbox_events')->whereNull('relayed_at')->value('given_up_at'))->not->toBeNull();
});

it('claims no new event once the run\'s time budget is spent, which is below the job\'s timeout', function () {
    RecordingProbeListener::$seen = [];
    app(OutboxListeners::class)->listen(KernelProbeEvent::class, RecordingProbeListener::class);
    DB::transaction(fn () => app(WriteOutboxEvent::class)->handle(new KernelProbeEvent(Id::generate())));

    expect(app(RelayOutboxEvents::class)->handle(budgetSeconds: 0))->toBe(0)
        ->and(RecordingProbeListener::$seen)->toBe([])
        ->and((new RelayOutbox)->timeout)->toBeGreaterThan(RelayOutboxEvents::TIME_BUDGET_SECONDS);
});

it('counts a row as held only once FOR UPDATE has locked it', function () {
    $missing = Id::generate();

    inActionScope(function () use ($missing) {
        expect(fn () => app(LockStaffMember::class)->handle($missing))->toThrow(ModelNotFoundException::class);
        expect(app(LockRegistry::class)->isHeld(LockStaffMember::ROOT, $missing))->toBeFalse();
    });
});

it('refuses a lock inside a nested Action or a savepoint, whose rollback would release it while still counted as held', function () {
    $member = StaffConsole::admin();

    expect(fn () => inActionScope(fn () => inActionScope(fn () => app(LockStaffMember::class)->handle($member->id))))
        ->toThrow(LogicException::class, 'nested Action never takes');

    expect(fn () => inActionScope(fn () => DB::transaction(fn () => app(LockStaffMember::class)->handle($member->id))))
        ->toThrow(LogicException::class, 'never inside a savepoint');

    // Locked in the Action's own transaction (whatever the id's case), then a nested Action asserts it.
    expect(inActionScope(function () use ($member) {
        app(LockStaffMember::class)->handle(strtoupper($member->id));

        return inActionScope(fn () => app(LockRegistry::class)->isHeld(LockStaffMember::ROOT, $member->id));
    }))->toBeTrue();
});

it('reports a database error without its bound values or the values in the database\'s own message', function () {
    $secret = 'bola.secret@example.test';
    $logged = [];
    Log::listen(function (MessageLogged $event) use (&$logged) {
        $exception = $event->context['exception'] ?? null;
        $logged[] = $event->message.' '.($exception instanceof Throwable ? (string) $exception : '');
    });

    $admin = StaffConsole::admin();
    $redacted = null;

    try {
        DB::transaction(fn () => DB::table('staff_members')->insert([
            'id' => Id::generate(), 'name' => 'Bola Secret', 'email' => $admin->email, 'role' => 'admin', 'status' => 'active',
            'password' => 'hash-'.$secret, 'is_founder' => false, 'created_at' => app(Clock::class)->now(), 'updated_at' => app(Clock::class)->now(),
        ]));
    } catch (UniqueConstraintViolationException $e) {
        report($e);
        $redacted = QueryExceptionRedactor::redact($e);
    }

    $log = implode("\n", $logged);

    expect($logged)->not->toBeEmpty()
        ->and($log)->not->toContain($secret)
        ->and($log)->not->toContain($admin->email)
        ->and($log)->not->toContain('Bola Secret')
        ->and($log)->toContain('staff_members_email_current_unique');

    expect($redacted)->toBeInstanceOf(UniqueConstraintViolationException::class)
        ->and($redacted->getBindings())->toBe([])
        ->and($redacted->index)->toBe('staff_members_email_current_unique')
        ->and($redacted->getMessage())->toContain('insert into "staff_members"')
        ->and($redacted->getTrace())->not->toBeEmpty()
        ->and(collect($redacted->getTrace())->contains(fn (array $frame) => array_key_exists('args', $frame)))->toBeFalse();

    $quoted = QueryExceptionRedactor::redact(new QueryException('pgsql', 'select * from staff_members where id = ?', ['not-a-uuid'], new PDOException('SQLSTATE[22P02]: Invalid text representation: 7 ERROR:  invalid input syntax for type uuid: "not-a-uuid"')));

    expect($quoted->getMessage())->not->toContain('not-a-uuid')
        ->and($quoted->getMessage())->toContain('invalid input syntax for type uuid: "[redacted]"');
});
