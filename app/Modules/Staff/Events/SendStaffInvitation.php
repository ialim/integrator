<?php

namespace App\Modules\Staff\Events;

use App\Modules\Notifications\Actions\EmailOutcome;
use App\Modules\Notifications\Actions\SendEmail;
use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Events\OutboxListener;
use App\Modules\Shared\Events\OutboxMessage;
use App\Modules\Shared\Support\Wat;
use App\Modules\Staff\Actions\InvitationLinks;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\StatusTables\StaffAccountStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Emails an invitation link when one is issued ({@see StaffInvitationIssued}),
 * through the Notifications module's SendEmail Action, which records the
 * delivery key (event id plus channel) before calling the email provider, so
 * relaying the same event twice sends one email.
 *
 * A link that has since been used, replaced or expired, or an account no
 * longer invited, is not sent: a newer event carries the link that works,
 * and an expired link would not open.
 */
final class SendStaffInvitation implements OutboxListener
{
    public function __construct(
        private readonly SendEmail $sendEmail,
        private readonly Clock $clock,
    ) {}

    public function handle(OutboxMessage $message): void
    {
        $memberId = $message->payload['staff_member_id'] ?? null;
        $sealed = $message->payload['token'] ?? null;
        $expiresAt = $message->payload['expires_at'] ?? null;

        if (! is_string($memberId) || ! is_string($sealed) || ! is_string($expiresAt)) {
            Log::warning('staff.invitation_unreadable', ['event_id' => $message->id]);

            return;
        }

        $token = Crypt::decryptString($sealed);
        $member = StaffMember::query()->find($memberId);

        if (! $member instanceof StaffMember
            || $member->status !== StaffAccountStatus::Invited
            || ! is_string($member->invitation_token_hash)
            || ! hash_equals($member->invitation_token_hash, InvitationLinks::hash($token))) {
            Log::info('staff.invitation_not_sent', ['event_id' => $message->id, 'staff_id' => $memberId, 'reason' => 'link_no_longer_current']);

            return;
        }

        if ($member->invitation_expires_at === null || $member->invitation_expires_at->lessThanOrEqualTo($this->clock->now())) {
            Log::info('staff.invitation_not_sent', ['event_id' => $message->id, 'staff_id' => $memberId, 'reason' => 'link_expired']);

            return;
        }

        $issuedBy = is_string($message->payload['issued_by'] ?? null) ? StaffMember::query()->find($message->payload['issued_by']) : null;
        $outcome = $this->sendEmail->handle(
            $message->id,
            $member->email,
            $member->name,
            $message->type === StaffSignInReset::type()
                ? 'Set up your sign-in to the integrator staff console again'
                : 'Your invitation to the integrator staff console',
            $this->text($member, $issuedBy instanceof StaffMember ? $issuedBy->name : 'An Admin', $message->type, $token, CarbonImmutable::parse($expiresAt)),
        );

        if ($outcome === EmailOutcome::Uncertain) {
            // Never sent again (owner's decision, 6 November 2026); the Staff page asks the Admin to send a new invitation.
            Log::warning('staff.invitation_may_not_have_arrived', ['event_id' => $message->id, 'staff_id' => $member->id]);

            return;
        }

        Log::info('staff.invitation_sent', ['event_id' => $message->id, 'staff_id' => $member->id, 'already_sent' => $outcome === EmailOutcome::AlreadySent]);
    }

    private function text(StaffMember $member, string $issuedBy, string $type, string $token, CarbonImmutable $expiresAt): string
    {
        $why = match ($type) {
            StaffSignInReset::type() => "{$issuedBy} has reset your sign-in to the integrator staff console. Your old password and authenticator app no longer work.",
            default => "{$issuedBy} has given you a {$member->role->label()} account on the integrator staff console.",
        };

        return implode("\n", [
            "Hello {$member->name},",
            '',
            $why,
            '',
            'Open this link to set your password, then set up an authenticator app on your phone:',
            InvitationLinks::url($token),
            '',
            'The link works once, until '.Wat::dateTime($expiresAt).'. If it has stopped working, ask an Admin to send you a new one.',
            '',
            'If you were not expecting this email, you can ignore it.',
        ]);
    }
}
