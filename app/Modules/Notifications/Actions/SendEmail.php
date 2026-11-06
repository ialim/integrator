<?php

namespace App\Modules\Notifications\Actions;

use App\Modules\Notifications\Contracts\EmailMessage;
use App\Modules\Notifications\Contracts\EmailProvider;
use App\Modules\Notifications\Queries\EmailDeliveries;
use App\Modules\Shared\Actions\Action;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Providers\ProviderErrored;
use App\Modules\Shared\Providers\ProviderFailure;
use App\Modules\Shared\Providers\ProviderRefused;
use App\Modules\Shared\Providers\ProviderTimedOut;
use App\Modules\Shared\Support\Id;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends one email for one outbox event (AD-4, AD-15). The delivery key (event
 * id plus the `email` channel, unique) decides what happens:
 *
 * 1. In a transaction of its own, the key is recorded as `sending` and that
 *    transaction commits before the provider is called (call this outside any
 *    other transaction; the outbox relay does).
 * 2. The provider is called with no transaction open.
 * 3. The answer is recorded:
 *    - sent: the key becomes `sent`; a later relay of the event sends nothing;
 *    - refused or errored (the email definitely did not go): the key is
 *      deleted and the failure rethrown, so the relay's retry sends it (a
 *      refusal is not retried: the relay gives the event up);
 *    - timed out (it may or may not have gone): the key becomes `uncertain`
 *      and the email is never sent again (owner's decision, 6 November 2026).
 *
 * A later attempt that finds the key still `sending` means the process died
 * after the key was committed, perhaps mid-call: the key becomes `uncertain`
 * and nothing is sent. So an email is sent at most once; when the outcome is
 * unknown it may not have arrived, which the Staff page shows for invitations
 * ({@see EmailDeliveries}).
 *
 * Only outbox listeners call this; the address and text are never stored.
 */
final class SendEmail extends Action
{
    public const CHANNEL = 'email';

    public const SENDING = 'sending';

    public const SENT = 'sent';

    public const UNCERTAIN = 'uncertain';

    public function __construct(
        private readonly EmailProvider $provider,
        private readonly Clock $clock,
    ) {}

    /**
     * @throws ProviderRefused when the provider definitively refused the email (not sent)
     * @throws ProviderErrored when the provider failed before sending it (not sent)
     */
    public function handle(string $eventId, string $toAddress, string $toName, string $subject, string $text): EmailOutcome
    {
        $deliveryId = $this->claimKey($eventId);

        if ($deliveryId instanceof EmailOutcome) {
            return $deliveryId;
        }

        try {
            $receipt = $this->provider->send(new EmailMessage($toAddress, $toName, $subject, $text));
        } catch (ProviderTimedOut $e) {
            $this->markUncertain($deliveryId, $eventId, 'timed_out');

            return EmailOutcome::Uncertain;
        } catch (ProviderFailure $e) {
            // Refused or errored: definitely not sent, so the key is freed and the relay decides on a retry.
            $this->transaction(fn () => DB::table('notification_deliveries')->where('id', $deliveryId)->delete());

            throw $e;
        } catch (Throwable $e) {
            // Anything else may have happened mid-send: never send again.
            $this->markUncertain($deliveryId, $eventId, 'unexpected_error');

            throw $e;
        }

        $this->transaction(fn () => DB::table('notification_deliveries')->where('id', $deliveryId)->update([
            'status' => self::SENT,
            'provider_message_id' => $receipt->providerMessageId === null ? null : mb_substr($receipt->providerMessageId, 0, 255),
            'sent_at' => $this->clock->now(),
        ]));

        return EmailOutcome::Sent;
    }

    /**
     * Commits the delivery key as `sending` and returns its id, or returns
     * what an earlier attempt already settled.
     */
    private function claimKey(string $eventId): string|EmailOutcome
    {
        return $this->transaction(function () use ($eventId): string|EmailOutcome {
            $inserted = DB::table('notification_deliveries')->insertOrIgnore([
                'id' => Id::generate(),
                'event_id' => $eventId,
                'channel' => self::CHANNEL,
                'status' => self::SENDING,
                'created_at' => $this->clock->now(),
            ]);

            /** @var object{id: string, status: string} $delivery */
            $delivery = DB::table('notification_deliveries')
                ->where('event_id', $eventId)
                ->where('channel', self::CHANNEL)
                ->lockForUpdate()
                ->first(['id', 'status']);

            if ($inserted === 1) {
                return $delivery->id;
            }

            return match ($delivery->status) {
                self::SENT => EmailOutcome::AlreadySent,
                self::UNCERTAIN => EmailOutcome::Uncertain,
                // Still `sending` from an attempt that never finished: the process died, perhaps mid-call.
                default => $this->settleAbandoned($delivery->id, $eventId),
            };
        });
    }

    private function settleAbandoned(string $deliveryId, string $eventId): EmailOutcome
    {
        DB::table('notification_deliveries')->where('id', $deliveryId)->update(['status' => self::UNCERTAIN]);
        Log::warning('notifications.email_uncertain', ['event_id' => $eventId, 'reason' => 'abandoned_while_sending']);

        return EmailOutcome::Uncertain;
    }

    private function markUncertain(string $deliveryId, string $eventId, string $reason): void
    {
        $this->transaction(fn () => DB::table('notification_deliveries')->where('id', $deliveryId)->update(['status' => self::UNCERTAIN]));
        Log::warning('notifications.email_uncertain', ['event_id' => $eventId, 'reason' => $reason]);
    }
}
