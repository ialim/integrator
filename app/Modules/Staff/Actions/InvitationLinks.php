<?php

namespace App\Modules\Staff\Actions;

use App\Modules\Staff\Models\StaffMember;
use Illuminate\Support\Str;
use SensitiveParameter;

/**
 * Invitation links: a random token in the URL, of which only the SHA-256 hash
 * is stored. A link works once, for {@see self::VALID_HOURS} hours, and a new
 * one (resend, reset sign-in) voids the old.
 */
class InvitationLinks
{
    /** Decision (owner, 3 November 2026): a link is valid for 72 hours. */
    public const VALID_HOURS = 72;

    public const ROUTE = 'filament.'.StaffMember::PANEL.'.invitation';

    public function newToken(): string
    {
        return Str::random(64);
    }

    public static function hash(#[SensitiveParameter] string $token): string
    {
        return hash('sha256', $token);
    }

    public static function url(#[SensitiveParameter] string $token): string
    {
        return route(self::ROUTE, ['token' => $token]);
    }
}
