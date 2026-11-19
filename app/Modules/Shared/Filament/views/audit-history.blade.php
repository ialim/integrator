{{--
    A record's read-only History panel: who did what and when, newest first,
    from the audit trail. Names are shown for staff actors; values only where
    the row holds them (a personal field reads "changed"); a reason as written.
    No Edit or Delete. Any module's console page shows it through
    \App\Modules\Shared\Filament\AuditHistory.

    @var list<\App\Modules\Shared\Queries\AuditEntry> $entries
    @var \App\Modules\Shared\Filament\AuditWords $words
    @var array<string, string> $names
    @var bool $more
    @var int $limit
    @var string $nothingYet
--}}
@php($now = app(\App\Modules\Shared\Contracts\Clock::class)->now())
@if ($entries === [])
    <p>{{ $nothingYet }}</p>
@else
    <ol class="console-history" aria-label="History, newest first">
        @foreach ($entries as $entry)
            @php($changes = $words->changes($entry))
            <li class="console-history-entry">
                <p class="console-history-line">
                    <time datetime="{{ \App\Modules\Shared\Support\Iso8601::format($entry->occurredAt) }}">{{ \App\Modules\Shared\Formats\Format::dateTime($entry->occurredAt, $now) }}</time>:
                    <strong>{{ $words->what($entry) }}</strong>, by {{ $words->who($entry, $names) }}.
                </p>
                @if ($changes !== [])
                    <ul class="console-change-list">
                        @foreach ($changes as $change)
                            <li>{{ $change }}</li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ol>
    @if ($more)
        <p class="console-muted">Showing the newest {{ $limit }} rows; older rows are not shown.</p>
    @endif
@endif
