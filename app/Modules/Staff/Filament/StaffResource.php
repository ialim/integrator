<?php

namespace App\Modules\Staff\Filament;

use App\Modules\Staff\Filament\StaffResource\ListStaff;
use App\Modules\Staff\Models\StaffMember;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

/**
 * The Staff page: an Admin adds Finance, Operations and Admin accounts, sends
 * invitations again, resets a sign-in and deactivates accounts. Every change
 * goes through a Staff Action. Only an Admin can open it; the Actions check
 * again on the server. Story 1.5 replaces the Admin check with the capability
 * registry.
 */
final class StaffResource extends Resource
{
    protected static ?string $model = StaffMember::class;

    protected static ?string $slug = 'staff';

    protected static ?string $navigationLabel = 'Staff';

    protected static ?string $modelLabel = 'staff member';

    protected static ?string $pluralModelLabel = 'staff';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 10;

    protected static bool $isGloballySearchable = false;

    /** Only an active Admin. */
    public static function canAccess(): bool
    {
        $member = Filament::auth()->user();

        return $member instanceof StaffMember && $member->isActiveAdmin();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaff::route('/'),
        ];
    }
}
