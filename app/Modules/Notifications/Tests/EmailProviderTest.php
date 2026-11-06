<?php

use App\Modules\Notifications\Actions\EmailOutcome;
use App\Modules\Notifications\Actions\SendEmail;
use App\Modules\Notifications\Contracts\EmailMessage;
use App\Modules\Notifications\Contracts\EmailProvider;
use App\Modules\Notifications\Contracts\EmailReceipt;
use App\Modules\Notifications\Fakes\FakeEmailProvider;
use App\Modules\Notifications\Providers\SmtpEmailProvider;
use App\Modules\Notifications\Queries\EmailDeliveries;
use App\Modules\Shared\Actions\WriteOutboxEvent;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Events\OutboxEvent;
use App\Modules\Shared\Providers\ProviderErrored;
use App\Modules\Shared\Providers\ProviderRefused;
use App\Modules\Shared\Providers\ProviderTimedOut;
use App\Modules\Shared\Providers\ScriptedOutcome;
use App\Modules\Shared\Support\Id;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(fn () => app(Clock::class)->set('2026-11-03T09:00:00Z'));

afterEach(fn () => app(Clock::class)->reset());

function fakeEmail(): FakeEmailProvider
{
    $fake = app(EmailProvider::class);
    assert($fake instanceof FakeEmailProvider);

    return $fake;
}

function message(string $to = 'ada@example.test'): EmailMessage
{
    return new EmailMessage($to, 'Ada', 'Hello', 'Plain text.');
}

/** An outbox event to hang a delivery key on. */
function anEvent(): string
{
    return DB::transaction(fn () => app(WriteOutboxEvent::class)->handle(new class implements OutboxEvent
    {
        public static function type(): string
        {
            return 'probe.email';
        }

        public function aggregateType(): string
        {
            return 'probe';
        }

        public function aggregateId(): string
        {
            return Id::generate();
        }

        public function payload(): array
        {
            return [];
        }
    }));
}

it('binds the fake email provider in testing', function () {
    expect(app(EmailProvider::class))->toBeInstanceOf(FakeEmailProvider::class)
        ->and(app(EmailProvider::class))->toBe(app(EmailProvider::class));
});

it('plays a scripted failure, timeout, error and success, one per call and in order, then succeeds', function () {
    $fake = fakeEmail()->script(ScriptedOutcome::Refused, ScriptedOutcome::Timeout, ScriptedOutcome::Error, ScriptedOutcome::Success);

    expect(fn () => $fake->send(message()))->toThrow(ProviderRefused::class)
        ->and(fn () => $fake->send(message()))->toThrow(ProviderTimedOut::class)
        ->and(fn () => $fake->send(message()))->toThrow(ProviderErrored::class);

    expect($fake->send(message())->providerMessageId)->toBe('fake-1')
        ->and($fake->send(message('bola@example.test'))->providerMessageId)->toBe('fake-2')
        ->and($fake->remainingScript())->toBe([])
        ->and(array_column($fake->calls(), 'outcome'))->toBe([
            ScriptedOutcome::Refused, ScriptedOutcome::Timeout, ScriptedOutcome::Error, ScriptedOutcome::Success, ScriptedOutcome::Success,
        ])
        ->and($fake->sent())->toHaveCount(2)
        ->and($fake->sentTo('BOLA@example.test'))->toHaveCount(1);
});

it('commits the delivery key as sending before calling the provider, with no transaction open, so one event sends one email', function () {
    $event = anEvent();
    $level = DB::transactionLevel();
    $seen = [];
    app()->instance(EmailProvider::class, new class($seen) implements EmailProvider
    {
        /** @param  list<array{level: int, status: mixed}>  $seen */
        public function __construct(private array &$seen) {}

        public function send(EmailMessage $message): EmailReceipt
        {
            $this->seen[] = ['level' => DB::transactionLevel(), 'status' => DB::table('notification_deliveries')->value('status')];

            return new EmailReceipt('probe-1');
        }
    });

    expect(app(SendEmail::class)->handle($event, 'ada@example.test', 'Ada', 'Hello', 'Plain text.'))->toBe(EmailOutcome::Sent)
        ->and(app(SendEmail::class)->handle($event, 'ada@example.test', 'Ada', 'Hello', 'Plain text.'))->toBe(EmailOutcome::AlreadySent)
        ->and($seen)->toBe([['level' => $level, 'status' => 'sending']]);

    $delivery = DB::table('notification_deliveries')->sole();

    expect($delivery->event_id)->toBe($event)
        ->and($delivery->channel)->toBe('email')
        ->and($delivery->status)->toBe('sent')
        ->and($delivery->provider_message_id)->toBe('probe-1')
        ->and(json_encode($delivery))->not->toContain('ada@example.test');
});

it('frees the delivery key when the provider definitely did not send, so a retry sends', function (ScriptedOutcome $failure, string $exception) {
    $event = anEvent();
    fakeEmail()->script($failure);

    expect(fn () => app(SendEmail::class)->handle($event, 'ada@example.test', 'Ada', 'Hello', 'Plain text.'))
        ->toThrow($exception);

    expect(DB::table('notification_deliveries')->count())->toBe(0)
        ->and(app(SendEmail::class)->handle($event, 'ada@example.test', 'Ada', 'Hello', 'Plain text.'))->toBe(EmailOutcome::Sent)
        ->and(fakeEmail()->sent())->toHaveCount(1);
})->with([
    'refused' => [ScriptedOutcome::Refused, ProviderRefused::class],
    'errored' => [ScriptedOutcome::Error, ProviderErrored::class],
]);

it('marks the key uncertain on a timeout and never sends that email again', function () {
    $event = anEvent();
    fakeEmail()->script(ScriptedOutcome::Timeout);

    expect(app(SendEmail::class)->handle($event, 'ada@example.test', 'Ada', 'Hello', 'Plain text.'))->toBe(EmailOutcome::Uncertain)
        ->and(DB::table('notification_deliveries')->value('status'))->toBe('uncertain')
        ->and(app(SendEmail::class)->handle($event, 'ada@example.test', 'Ada', 'Hello', 'Plain text.'))->toBe(EmailOutcome::Uncertain)
        ->and(fakeEmail()->calls())->toHaveCount(1)
        ->and(fakeEmail()->sent())->toBe([])
        ->and(app(EmailDeliveries::class)->uncertain([$event, Id::generate()]))->toBe([$event]);
});

// A key left `sending` by a process that died is covered in tests/Flows/StaffAccountsFlowTest.php,
// which may write the delivery table directly.

it('keeps the delivery key unique per event and channel in the database', function () {
    $indexes = collect(DB::select("SELECT indexdef FROM pg_indexes WHERE tablename = 'notification_deliveries'"))->pluck('indexdef')->implode('
');

    expect($indexes)->toContain('UNIQUE INDEX notification_deliveries_event_id_channel_unique ON public.notification_deliveries USING btree (event_id, channel)');
});

it('sends through Laravel\'s mailer in production', function () {
    config(['mail.default' => 'array']);

    $receipt = app(SmtpEmailProvider::class)->send(message());
    $sent = app('mailer')->getSymfonyTransport()->messages();

    expect($sent)->toHaveCount(1)
        ->and($sent[0]->getOriginalMessage()->getTo()[0]->getAddress())->toBe('ada@example.test')
        ->and($sent[0]->getOriginalMessage()->getSubject())->toBe('Hello')
        ->and($receipt->providerMessageId)->not->toBeNull();
});
