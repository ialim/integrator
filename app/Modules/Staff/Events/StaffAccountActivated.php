<?php

namespace App\Modules\Staff\Events;

use App\Modules\Shared\Events\OutboxEvent;

/** An invited staff member finished setting up their sign-in; the account is Active. */
final class StaffAccountActivated implements OutboxEvent
{
    public function __construct(public readonly string $staffMemberId, public readonly ?string $byStaffMemberId) {}

    public static function type(): string
    {
        return 'staff.account_activated';
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
