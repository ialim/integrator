<?php

namespace App\Modules\Staff\Console;

use App\Modules\Staff\Actions\CreateFirstAdmin;
use App\Modules\Staff\Actions\FirstAdminRefused;
use App\Modules\Staff\Actions\StaffPassword;
use App\Modules\Staff\Models\StaffMember;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

/**
 * Creates the founder's Admin account on a fresh installation. The password is
 * read at a hidden prompt, asked twice, and never echoed or logged.
 */
final class CreateFirstAdminCommand extends Command
{
    protected $signature = 'staff:create-first-admin
        {--name= : The Admin\'s full name}
        {--email= : The email address the Admin signs in with}';

    protected $description = 'Create the first Admin staff account, only while no staff account exists';

    public function handle(CreateFirstAdmin $createFirstAdmin): int
    {
        if (StaffMember::query()->exists()) {
            $this->components->error(FirstAdminRefused::staffExist()->getMessage());

            return self::FAILURE;
        }

        $details = [
            'name' => trim((string) $this->option('name')),
            'email' => trim((string) $this->option('email')),
        ];

        $detailErrors = Validator::make($details, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ], [
            'name.required' => 'Give the Admin\'s name with --name.',
            'email.required' => 'Give the Admin\'s email address with --email.',
            'email.email' => 'The email address is not valid.',
        ])->errors();

        if ($detailErrors->isNotEmpty()) {
            foreach ($detailErrors->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password (at least '.StaffPassword::MIN_LENGTH.' characters)');
        $confirmation = (string) $this->secret('Type the password again');
        $problem = StaffPassword::problem($password, $details['name'], $details['email']);

        if ($problem !== null) {
            $this->components->error($problem.' Nothing was created.');

            return self::FAILURE;
        }

        if (! hash_equals($password, $confirmation)) {
            $this->components->error('The two passwords do not match. Nothing was created.');

            return self::FAILURE;
        }

        try {
            $admin = $createFirstAdmin->handle($details['name'], $details['email'], $password);
        } catch (FirstAdminRefused|InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Created the Admin account for {$admin->email}.");
        $this->line('To finish setting it up:');
        $this->line('  1. Open '.Filament::getPanel(StaffMember::PANEL)->getLoginUrl().' and sign in with this email and password.');
        $this->line('  2. Set up an authenticator app when asked. No console page opens until this is done.');
        $this->line('  3. Keep the recovery codes it shows somewhere safe and offline.');

        return self::SUCCESS;
    }
}
