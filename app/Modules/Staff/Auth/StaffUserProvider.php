<?php

namespace App\Modules\Staff\Auth;

use App\Modules\Staff\Actions\RehashPassword;
use App\Modules\Staff\Models\StaffMember;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;
use SensitiveParameter;

/**
 * The `staff` guard's user provider (AD-18). It finds staff members by
 * lower-cased email and routes the sign-in password re-hash through a Staff
 * Action instead of saving the model directly.
 */
final class StaffUserProvider extends EloquentUserProvider
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(#[SensitiveParameter] array $credentials): ?Authenticatable
    {
        if (isset($credentials['email']) && is_string($credentials['email'])) {
            $credentials['email'] = Str::lower(trim($credentials['email']));
        }

        return parent::retrieveByCredentials($credentials);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function rehashPasswordIfRequired(Authenticatable $user, #[SensitiveParameter] array $credentials, bool $force = false): void
    {
        if (! $user instanceof StaffMember || ! is_string($credentials['password'] ?? null)) {
            return;
        }

        if (! $this->hasher->needsRehash($user->getAuthPassword()) && ! $force) {
            return;
        }

        app(RehashPassword::class)->handle($user, $credentials['password']);
    }
}
