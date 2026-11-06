<?php

namespace App\Modules\Staff\Http;

use App\Modules\Staff\Actions\RecordStaffSession;
use Illuminate\Contracts\Session\Session;

/**
 * Ties a staff session id to its member ({@see RecordStaffSession}), and keeps
 * the id last recorded in the session itself so each id is recorded once.
 * Sign-in, the invitation sign-in and "Stay signed in" record the new id at
 * once; the console middleware records any id still unrecorded.
 */
final class StaffSessionRecords
{
    public const KEY = 'staff_session.recorded_id';

    public function __construct(private readonly RecordStaffSession $record) {}

    /**
     * Records $session for $memberId unless it already is. Returns false when
     * the member may no longer sign in; the caller then signs them out.
     */
    public function record(Session $session, string $memberId): bool
    {
        $recorded = $session->get(self::KEY);

        if ($recorded === $session->getId()) {
            return true;
        }

        if (! $this->record->handle($memberId, $session->getId(), is_string($recorded) ? $recorded : null)) {
            return false;
        }

        $session->put(self::KEY, $session->getId());

        return true;
    }

    /**
     * The session id changes with the previous session destroyed (its stored
     * row deleted, not left behind still signed in), and the new id is
     * recorded at once.
     */
    public function rotate(Session $session, string $memberId): bool
    {
        $session->regenerate(true);

        return $this->record($session, $memberId);
    }
}
