<?php

namespace App\Modules\Staff\Actions;

use App\Modules\Shared\Actions\Action;
use App\Modules\Shared\Audit\Actor;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SensitiveParameter;

/**
 * Creates the founder's Admin account, and only while no staff account exists.
 * The actor is `system`: nobody is signed in yet.
 */
final class CreateFirstAdmin extends Action
{
    /**
     * @throws FirstAdminRefused when any staff account already exists
     * @throws InvalidArgumentException when the name, email or password is not acceptable
     */
    public function handle(string $name, string $email, #[SensitiveParameter] string $password): StaffMember
    {
        $name = trim($name);
        $email = Str::lower(trim($email));

        if ($name === '' || mb_strlen($name) > 255) {
            throw new InvalidArgumentException('The name must be between 1 and 255 characters.');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 255) {
            throw new InvalidArgumentException('The email address is not valid.');
        }

        $problem = StaffPassword::problem($password, $name, $email);

        if ($problem !== null) {
            throw new InvalidArgumentException($problem);
        }

        return $this->transaction(function () use ($name, $email, $password): StaffMember {
            // Serialises concurrent runs, so two can never both see an empty table.
            DB::statement('LOCK TABLE staff_members IN SHARE ROW EXCLUSIVE MODE');

            if (StaffMember::query()->exists()) {
                throw FirstAdminRefused::staffExist();
            }

            $admin = new StaffMember;
            $admin->forceFill([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => StaffRole::Admin,
                'is_founder' => true,
                'is_active' => true,
            ]);

            $this->saveAndAudit(Actor::system(), 'staff.first_admin_created', $admin);

            return $admin;
        });
    }
}
