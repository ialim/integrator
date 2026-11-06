<?php

namespace App\Modules\Staff\Locks;

use App\Modules\Shared\Locks\LockAction;
use App\Modules\Staff\Models\StaffMember;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Locks staff members' rows (the `staff_member` aggregate root) until the
 * Action's transaction ends, in ascending id order, and returns them as read
 * under the lock. Lock every row the Action needs in one call.
 */
final class LockStaffMember extends LockAction
{
    public const ROOT = 'staff_member';

    public static function root(): string
    {
        return self::ROOT;
    }

    protected function table(): string
    {
        return (new StaffMember)->getTable();
    }

    /**
     * @return array<string, StaffMember> id => member, read after the lock was taken
     *
     * @throws ModelNotFoundException when a member does not exist
     */
    public function handle(string ...$ids): array
    {
        $this->lockRows(array_values($ids));

        return StaffMember::query()->whereKey(array_values($ids))->get()->keyBy('id')->all();
    }
}
