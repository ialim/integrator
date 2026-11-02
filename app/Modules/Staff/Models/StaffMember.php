<?php

namespace App\Modules\Staff\Models;

use App\Modules\Shared\Support\HasUuidV7;
use App\Modules\Staff\Actions\SetAuthenticatorSecret;
use App\Modules\Staff\Actions\SetRecoveryCodes;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use SensitiveParameter;

/**
 * A member of the platform team, signed in through the `staff` guard only.
 *
 * Filament's second-factor setup and recovery call the `save*` methods below;
 * they never save the model themselves but delegate to Staff Actions (AD-1),
 * which write and audit the change.
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property StaffRole $role
 * @property bool $is_founder
 * @property bool $is_active
 * @property string|null $app_authentication_secret
 * @property list<string>|null $app_authentication_recovery_codes
 */
final class StaffMember extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasName
{
    use HasUuidV7;

    public const PANEL = 'console';

    /** Personal and secret columns: the audit trail records only that they changed. */
    public const PERSONAL_FIELDS = ['name', 'email', 'password', 'app_authentication_secret', 'app_authentication_recovery_codes'];

    protected $table = 'staff_members';

    /** @var list<string> */
    protected $hidden = ['password', 'app_authentication_secret', 'app_authentication_recovery_codes'];

    /** Staff sessions never use "remember me", so there is no remember token. */
    protected $rememberTokenName = '';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => StaffRole::class,
            'is_founder' => 'boolean',
            'is_active' => 'boolean',
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === self::PANEL && $this->is_active;
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(#[SensitiveParameter] ?string $secret): void
    {
        app(SetAuthenticatorSecret::class)->handle($this, $secret);
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    /**
     * @return list<string>|null
     */
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_authentication_recovery_codes;
    }

    /**
     * @param  array<string>|null  $codes  already hashed by Filament
     */
    public function saveAppAuthenticationRecoveryCodes(#[SensitiveParameter] ?array $codes): void
    {
        app(SetRecoveryCodes::class)->handle($this, $codes);
    }
}
