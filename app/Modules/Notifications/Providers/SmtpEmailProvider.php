<?php

namespace App\Modules\Notifications\Providers;

use App\Modules\Notifications\Contracts\EmailMessage;
use App\Modules\Notifications\Contracts\EmailProvider;
use App\Modules\Notifications\Contracts\EmailReceipt;
use App\Modules\Shared\Providers\ProviderErrored;
use App\Modules\Shared\Providers\ProviderRefused;
use App\Modules\Shared\Providers\ProviderTimedOut;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Mail\Message;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Throwable;

/**
 * The production email adapter: Laravel's mailer, with the SMTP account from
 * Story 1.2 (the configured default mailer). Failures are reported as the
 * three shared provider kinds; their messages never carry the address.
 */
final class SmtpEmailProvider implements EmailProvider
{
    public function __construct(private readonly MailFactory $mail) {}

    public function send(EmailMessage $message): EmailReceipt
    {
        try {
            $sent = $this->mail->mailer()->raw($message->text, function (Message $mail) use ($message): void {
                $mail->to($message->toAddress, $message->toName)->subject($message->subject);
            });
        } catch (UnexpectedResponseException $e) {
            // A 5xx reply is permanent (mailbox unknown, message refused); a 4xx is worth a retry.
            $code = (int) $e->getCode();

            throw $code >= 500 && $code < 600
                ? new ProviderRefused("The mail server refused the message ({$code}).", previous: $e)
                : new ProviderErrored("The mail server could not take the message now ({$code}).", previous: $e);
        } catch (TransportExceptionInterface $e) {
            $words = strtolower($e->getMessage());

            throw str_contains($words, 'timed out') || str_contains($words, 'timeout')
                ? new ProviderTimedOut('The mail server did not answer in time.', previous: $e)
                : new ProviderErrored('The mail server could not be reached.', previous: $e);
        } catch (Throwable $e) {
            throw new ProviderErrored('Sending the email failed: '.$e::class.'.', previous: $e);
        }

        return new EmailReceipt($sent?->getMessageId());
    }
}
