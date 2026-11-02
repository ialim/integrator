<?php

namespace Database\Factories\Staff;

use App\Modules\Staff\Actions\CreateFirstAdmin;
use App\Modules\Staff\Models\StaffMember;
use Database\Factories\ActionFactory;

/**
 * The founder's Admin account, created through {@see CreateFirstAdmin}.
 * Test values only; the accessibility check (tests/Accessibility) signs in with them.
 *
 * @extends ActionFactory<StaffMember>
 */
final class FirstAdminFactory extends ActionFactory
{
    public const NAME = 'Ada Founder';

    public const EMAIL = 'founder@example.test';

    public const PASSWORD = 'ledger-test-password';

    protected function action(): string
    {
        return CreateFirstAdmin::class;
    }

    protected function definition(): array
    {
        return [
            'name' => self::NAME,
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ];
    }
}
