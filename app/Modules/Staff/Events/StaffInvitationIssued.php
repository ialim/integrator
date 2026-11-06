<?php

namespace App\Modules\Staff\Events;

use App\Modules\Shared\Events\OutboxEvent;
use App\Modules\Shared\Support\Iso8601;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use SensitiveParameter;

/**
 * An invitation link was issued to a staff member; {@see SendStaffInvitation}
 * emails it. The link's token travels encrypted with the application key, so
 * the outbox row never holds it in clear; the staff table keeps only its hash.
 */
abstract class StaffInvitationIssued implements OutboxEvent
{
    public function __construct(
        public readonly string $staffMemberId,
        #[SensitiveParameter] private readonly string $token,
        public readonly CarbonImmutable $expiresAt,
        public readonly string $issuedBy,
    ) {}

    /**
     * The outbox types of every kind of invitation.
     *
     * @return list<string>
     */
    public static function types(): array
    {
        return [StaffAccountCreated::type(), StaffInvitationResent::type(), StaffSignInReset::type()];
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
        return [
            'staff_member_id' => $this->staffMemberId,
            'token' => Crypt::encryptString($this->token),
            'expires_at' => Iso8601::format($this->expiresAt),
            'issued_by' => $this->issuedBy,
        ];
    }
}
