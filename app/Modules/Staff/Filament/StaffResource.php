<?php

namespace App\Modules\Staff\Filament;

use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Staff\Capabilities\StaffCapabilities;
use App\Modules\Staff\Filament\StaffResource\ListStaff;
use App\Modules\Staff\Models\StaffMember;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * The Staff page: an Admin adds Finance, Operations and Admin accounts, sends
 * invitations again, resets a sign-in, deactivates accounts and switches an
 * Operations account's Support duties On or Off. Every change
 * goes through a Staff Action. It needs `staff.manage` from the capability
 * registry (Admins only); the Actions check again on the server. It sits in the
 * rail's People group.
 */
#[NeedsCapability(StaffCapabilities::MANAGE_STAFF)]
final class StaffResource extends Resource
{
    use GuardedByCapability;

    protected static ?string $model = StaffMember::class;

    protected static ?string $slug = 'staff';

    protected static ?string $navigationLabel = 'Staff';

    protected static ?string $modelLabel = 'staff member';

    protected static ?string $pluralModelLabel = 'staff';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = ConsoleRail::PEOPLE;

    protected static ?int $navigationSort = 10;

    protected static bool $isGloballySearchable = false;

    public static function getPages(): array
    {
        return [
            'index' => ListStaff::route('/'),
        ];
    }
}
