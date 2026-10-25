<?php

namespace ArchFixtures\Contracts\Modules\Notifications\Contracts;

use App\Modules\Shared\Providers\HasFake;

// Fixture: a provider interface that declares its fake is not reported.
#[HasFake(FakeWhatsAppProvider::class)]
interface WhatsAppProvider
{
    public function send(string $to, string $template): void;
}
