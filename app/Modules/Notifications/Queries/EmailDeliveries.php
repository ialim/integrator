<?php

namespace App\Modules\Notifications\Queries;

use App\Modules\Notifications\Actions\SendEmail;
use Illuminate\Support\Facades\DB;

/**
 * Reads what became of the emails sent for outbox events.
 */
final class EmailDeliveries
{
    /**
     * Of $eventIds, those whose email may not have arrived: the provider timed
     * out, or the sending process died mid-call, so it is never sent again.
     *
     * @param  list<string>  $eventIds
     * @return list<string>
     */
    public function uncertain(array $eventIds): array
    {
        if ($eventIds === []) {
            return [];
        }

        return DB::table('notification_deliveries')
            ->whereIn('event_id', $eventIds)
            ->where('channel', SendEmail::CHANNEL)
            ->where('status', SendEmail::UNCERTAIN)
            ->pluck('event_id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all();
    }
}
