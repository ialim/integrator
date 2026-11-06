<?php

namespace App\Modules\Notifications\Fakes;

use App\Modules\Notifications\Contracts\EmailMessage;
use App\Modules\Notifications\Contracts\EmailProvider;
use App\Modules\Notifications\Contracts\EmailReceipt;
use App\Modules\Shared\Providers\ScriptsOutcomes;

/**
 * The email provider for testing and staging (AD-15, TC-3): sends nothing and
 * keeps what it was asked to send. A test scripts each call with
 * {@see ScriptsOutcomes::script()}: success, definitive failure (Refused),
 * timeout or error, in order; unscripted calls succeed.
 */
final class FakeEmailProvider implements EmailProvider
{
    use ScriptsOutcomes;

    /** @var list<EmailMessage> */
    private array $sent = [];

    public function send(EmailMessage $message): EmailReceipt
    {
        $this->play('send');
        $this->sent[] = $message;

        return new EmailReceipt('fake-'.count($this->sent));
    }

    /**
     * Messages sent successfully, oldest first.
     *
     * @return list<EmailMessage>
     */
    public function sent(): array
    {
        return $this->sent;
    }

    /**
     * @return list<EmailMessage>
     */
    public function sentTo(string $address): array
    {
        return array_values(array_filter($this->sent, fn (EmailMessage $m) => strcasecmp($m->toAddress, $address) === 0));
    }
}
