<?php

namespace App\Modules\Shared\Queries;

use Illuminate\Support\Facades\DB;

/**
 * Reads the outbox: what became of the events an aggregate emitted.
 */
final class OutboxEvents
{
    /**
     * The latest event of $types for each of $aggregateIds that has one:
     * aggregate id => [event id, whether the relay gave it up].
     *
     * @param  list<string>  $aggregateIds
     * @param  list<string>  $types
     * @return array<string, array{id: string, given_up: bool}>
     */
    public function latestOfTypes(string $aggregateType, array $aggregateIds, array $types): array
    {
        if ($aggregateIds === [] || $types === []) {
            return [];
        }

        $rows = DB::table('outbox_events')
            ->select(['id', 'aggregate_id', 'given_up_at'])
            ->selectRaw('row_number() over (partition by aggregate_id order by position desc) as recency')
            ->where('aggregate_type', $aggregateType)
            ->whereIn('aggregate_id', $aggregateIds)
            ->whereIn('type', $types);

        $latest = [];

        foreach (DB::query()->fromSub($rows, 'events')->where('recency', 1)->get() as $row) {
            /** @var object{id: string, aggregate_id: string, given_up_at: string|null} $row */
            $latest[(string) $row->aggregate_id] = ['id' => (string) $row->id, 'given_up' => $row->given_up_at !== null];
        }

        return $latest;
    }
}
