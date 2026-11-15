<?php

namespace App\Modules\Staff\Http;

use App\Modules\Staff\Actions\RevokeStaffSessions;
use App\Modules\Staff\Http\Middleware\EndRevokedStaffSession;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\DB;

/**
 * The session ids {@see RevokeStaffSessions} has just deleted, each kept
 * briefly with its member's id, so the member's next request on one is ended
 * with its reason ({@see EndRevokedStaffSession}) instead of arriving as an
 * empty session. Each id is used once, and expires after the longest staff
 * session. Written only once the revoking Action's transaction commits, so a
 * rolled-back revoke remembers nothing, whatever the cache store.
 */
final class RevokedStaffSessions
{
    public const KEY_PREFIX = 'staff_session.revoked:';

    public function __construct(private readonly Repository $cache) {}

    /**
     * @param  list<string>  $sessionIds
     */
    public function remember(array $sessionIds, string $memberId): void
    {
        $entries = [];

        foreach ($sessionIds as $sessionId) {
            $entries[self::KEY_PREFIX.$sessionId] = $memberId;
        }

        // At once when no transaction is open.
        DB::afterCommit(fn () => $this->cache->putMany($entries, StaffSession::LENGTH_SECONDS));
    }

    /**
     * The member whose revoked session this was, forgetting it; null when
     * the id was not revoked (or was already used).
     */
    public function pull(string $sessionId): ?string
    {
        $memberId = $this->cache->pull(self::KEY_PREFIX.$sessionId);

        return is_string($memberId) ? $memberId : null;
    }
}
