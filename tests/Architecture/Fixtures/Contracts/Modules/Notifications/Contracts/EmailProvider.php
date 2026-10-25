<?php

namespace ArchFixtures\Contracts\Modules\Notifications\Contracts;

// Fixture: a provider interface that declares no fake is reported.
interface EmailProvider
{
    public function send(string $to, string $subject, string $body): void;
}
