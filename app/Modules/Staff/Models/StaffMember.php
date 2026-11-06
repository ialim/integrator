<?php

namespace App\Modules\Staff\Models;

use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Support\HasUuidV7;
use App\Modules\Staff\Actions\SetAuthenticatorSecret;
use App\Modules\Staff\Actions\SetRecoveryCodes;
use App\Modules\Staff\StatusTables\StaffAccountStatus;
use Carbon\CarbonImmutable;
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
 * @property string|null $password
 * @property StaffRole $role
 * @property bool $is_founder
 * @property StaffAccountStatus $status
 * @property string|null $invitation_token_hash
 * @property CarbonImmutable|null $invitation_expires_at
 * @property CarbonImmutable|null $invitation_accepted_at
 * @property string|null $invited_by
 * @property CarbonImmutable|null $deactivated_at
 * @property string|null $app_authentication_secret
 * @property list<string>|null $app_authentication_recovery_codes
 */
final class StaffMember extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasName
{
    use HasUuidV7;

    public const PANEL = 'console';

    /** Personal and secret columns: the audit trail records only that they changed. */
    public const PERSONAL_FIELDS = ['name', 'email', 'password', 'app_authentication_secret', 'app_authentication_recovery_codes', 'invitation_token_hash'];

    protected $table = 'staff_members';

    /** @var list<string> */
    protected $hidden = ['password', 'app_authentication_secret', 'app_authentication_recovery_codes', 'invitation_token_hash'];

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
            'status' => StaffAccountStatus::class,
            'invitation_expires_at' => 'immutable_datetime',
            'invitation_accepted_at' => 'immutable_datetime',
            'deactivated_at' => 'immutable_datetime',
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
        ];
    }

    /**
     * Active members, and invitees who have set their password and still have
     * to set up an authenticator app (the console then shows them only the
     * set-up page), until the invitation's 72 hours run out: the link's time
     * bounds the whole set-up. Never a deactivated member or an unanswered
     * invitation.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === self::PANEL && $this->canSignIn();
    }

    public function canSignIn(): bool
    {
        return match ($this->status) {
            StaffAccountStatus::Active => true,
            StaffAccountStatus::Invited => $this->invitation_accepted_at !== null
                && filled($this->password)
                && $this->invitation_expires_at !== null
                && $this->invitation_expires_at->greaterThan(app(Clock::class)->now()),
            StaffAccountStatus::Deactivated => false,
        };
    }

    public function isActiveAdmin(): bool
    {
        return $this->role === StaffRole::Admin && $this->status === StaffAccountStatus::Active;
    }

    /** "A" for Ada Founder: the initial shown beside the name. */
    public function initial(): string
    {
        return mb_strtoupper(mb_substr(trim($this->name), 0, 1));
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
