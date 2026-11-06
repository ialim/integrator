<?php

namespace Database\Factories\Staff;

use App\Modules\Staff\Actions\CreateStaffAccount;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use Database\Factories\ActionFactory;

/**
 * An invited staff account, created by an Admin through
 * {@see CreateStaffAccount}; pass the acting Admin as `admin`. Test values only.
 *
 * @extends ActionFactory<StaffMember>
 */
final class StaffAccountFactory extends ActionFactory
{
    public const NAME = 'Bola Operations';

    public const EMAIL = 'bola@example.test';

    protected function action(): string
    {
        return CreateStaffAccount::class;
    }

    protected function definition(): array
    {
        return [
            'name' => self::NAME,
            'email' => self::EMAIL,
            'role' => StaffRole::Operations,
        ];
    }
}
