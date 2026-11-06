<?php

namespace App\Modules\Staff\Events;

use App\Modules\Shared\Events\OutboxEvent;

/** An Admin deactivated a staff account; every session of that person was revoked. */
final class StaffAccountDeactivated implements OutboxEvent
{
    public function __construct(public readonly string $staffMemberId, public readonly ?string $byStaffMemberId) {}

    public static function type(): string
    {
        return 'staff.account_deactivated';
    }

    public function aggregateType(): string
    {
        return 'staff_member';
    }

    public function aggregateId(): string
    {
        return $this->staffMemberId;
    }

    public function payload(): array
    {
        return ['staff_member_id' => $this->staffMemberId, 'by' => $this->byStaffMemberId];
    }
}
