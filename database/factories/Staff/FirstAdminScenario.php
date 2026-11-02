<?php

namespace Database\Factories\Staff;

/**
 * A fresh installation with only the founder's Admin, who has not yet set up
 * an authenticator app.
 */
final class FirstAdminScenario
{
    public function __invoke(): void
    {
        FirstAdminFactory::new()->create();
    }
}
