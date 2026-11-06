<?php

namespace App\Modules\Staff\Filament;

use App\Modules\Shared\Audit\Actor;
use App\Modules\Shared\StatusTables\TransitionRefused;
use App\Modules\Shared\Support\Wat;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\StatusTables\StaffAccountStatus;

/**
 * Turns a refused staff-account change into words for the screen: the rule's
 * own reason, or for a lost race the account's current state with who changed
 * it and when ("Bola Ade was already deactivated by Ada Founder at 14:05 WAT
 * on Tuesday 3 November 2026"). Never an error page.
 */
final class RefusalWords
{
    public function for(TransitionRefused $refused): string
    {
        if ($refused->reason !== null) {
            return $refused->reason;
        }

        $member = StaffMember::query()->find($refused->subjectId);
        $name = $member->name ?? 'This staff member';
        $change = $refused->lastChange;
        $by = $change === null ? null : $this->who($change->actorType, $change->actorId);
        $when = $change === null ? null : $this->when($change->occurredAt);
        $byWhom = $by === null ? '' : " by {$by} at {$when}";

        return match ($refused->current) {
            StaffAccountStatus::Deactivated => "{$name} was already deactivated{$byWhom}. Nothing else was changed.",
            StaffAccountStatus::Invited => "{$name} has not finished setting up their sign-in yet, so this cannot be done now. The account was last changed{$byWhom}. Nothing was changed.",
            StaffAccountStatus::Active => "{$name} has already set up their sign-in and is active, so this cannot be done now. The account was last changed{$byWhom}. Nothing was changed.",
            default => "{$name} is now {$refused->current->label()}, so this cannot be done. Nothing was changed.",
        };
    }

    private function who(string $type, ?string $id): string
    {
        if ($type === Actor::SYSTEM || $id === null) {
            return 'the system';
        }

        $member = StaffMember::query()->find($id);

        return $member->name ?? 'another staff member';
    }

    private function when(\DateTimeInterface $at): string
    {
        $wat = Wat::of($at);

        return $wat->format('H:i').' '.Wat::LABEL.' on '.$wat->format('l j F Y');
    }
}
