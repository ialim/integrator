<?php

namespace App\Modules\Staff\Queries;

use App\Modules\Shared\Audit\ActorNames;
use App\Modules\Staff\Models\StaffMember;

/**
 * Staff members' names by id, for audit rows in words ({@see ActorNames}).
 */
final class StaffActorNames implements ActorNames
{
    public function namesOf(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        /** @var array<string, string> */
        return StaffMember::query()->whereIn('id', array_values(array_unique($ids)))->pluck('name', 'id')->all();
    }
}
