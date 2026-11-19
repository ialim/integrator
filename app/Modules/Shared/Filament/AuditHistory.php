<?php

namespace App\Modules\Shared\Filament;

use App\Modules\Shared\Queries\AuditTrail;
use Illuminate\Contracts\View\View;

/**
 * A record's read-only History panel for any module's console page: who did
 * what and when, newest first, from the audit trail, in words. One History
 * may cover several audit subjects that belong together, such as a Product
 * Range and its Platform Markup. No Edit or Delete.
 */
final class AuditHistory
{
    /** The most entries a History shows; it says so when there are more. */
    public const LIMIT = 200;

    public function __construct(private readonly AuditTrail $trail, private readonly AuditWords $words) {}

    /**
     * @param  list<array{0: string, 1: string}>  $subjects  [subject type, subject id] pairs
     * @param  string  $nothingYet  the sentence shown when nothing is recorded, "Nothing has been recorded for this account yet."
     */
    public function view(array $subjects, string $nothingYet): View
    {
        $entries = $this->trail->historyOfEach($subjects, self::LIMIT + 1);
        $shown = array_slice($entries, 0, self::LIMIT);

        return view('shared::audit-history', [
            'entries' => $shown,
            'more' => count($entries) > self::LIMIT,
            'limit' => self::LIMIT,
            'words' => $this->words,
            'names' => $this->words->staffNames($shown),
            'nothingYet' => $nothingYet,
        ]);
    }
}
