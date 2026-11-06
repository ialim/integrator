<?php

namespace App\Modules\Notifications\Contracts;

use App\Modules\Notifications\Fakes\FakeEmailProvider;
use App\Modules\Notifications\Providers\SmtpEmailProvider;
use App\Modules\Shared\Providers\HasFake;
use App\Modules\Shared\Providers\ProviderErrored;
use App\Modules\Shared\Providers\ProviderRefused;
use App\Modules\Shared\Providers\ProviderTimedOut;

/**
 * Sends one email (AD-15). Production binds {@see SmtpEmailProvider};
 * testing and staging bind {@see FakeEmailProvider}, which can script each
 * call. Only the Notifications module's `SendEmail` Action calls it, from an
 * outbox listener, after recording the delivery key.
 */
#[HasFake(FakeEmailProvider::class)]
interface EmailProvider
{
    /**
     * @throws ProviderRefused when the provider definitively refuses the message
     * @throws ProviderTimedOut when it does not answer in time
     * @throws ProviderErrored on any other failure
     */
    public function send(EmailMessage $message): EmailReceipt;
}
