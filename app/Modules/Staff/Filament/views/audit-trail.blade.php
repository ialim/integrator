{{--
    The Audit trail page: the chain check (its result announced in a status
    region), Look up by an identifier, and one read-only console table of
    rows, newest first. No Edit, Delete, re-seal or export anywhere.

    A member without the capability never sees this view: GuardedByCapability
    swaps in shared::refused-page, inside the console shell, sent as 403.
--}}
<x-filament-panels::page>
    @php($words = app(\App\Modules\Shared\Filament\AuditWords::class))
    @php($now = app(\App\Modules\Shared\Contracts\Clock::class)->now())
    @php($shown = $this->getEntries())
    @php($entries = $shown['entries'])
    @php($names = $words->staffNames($entries))
    @php($sealed = $this->sealed($entries))

    <section class="console-panel" aria-labelledby="audit-chain-heading">
        <h2 id="audit-chain-heading" class="console-panel-heading">Check the chain</h2>
        <p>Every row is sealed into a hash chain a few minutes after it is written. The check recomputes every seal from the stored rows and names the first sealed row that was changed, removed or slipped in. It cannot tell whether the newest rows were removed together with their seals: for that, compare where the chain ends with the founder's heartbeat log or the offsite copy. It changes nothing.</p>
        <div class="console-audit-actions">
            <button type="button" class="console-button" wire:click="checkChain" wire:loading.attr="aria-busy" wire:target="checkChain" data-chain-check>
                Check the chain
            </button>
        </div>
        <div class="console-notice-region" role="status" aria-live="polite" data-chain-result>
            @if ($chainResult)
                <p @class(['console-notice', 'console-notice-refused' => $chainBroken])>{{ $chainResult }}</p>
            @endif
        </div>
    </section>

    <section class="console-panel" aria-labelledby="audit-lookup-heading">
        <h2 id="audit-lookup-heading" class="console-panel-heading">Look up</h2>
        <form wire:submit="lookUp" class="console-audit-lookup" novalidate>
            <label for="audit-lookup" class="console-label">Record or person identifier</label>
            <input
                id="audit-lookup"
                type="text"
                class="console-input"
                wire:model="identifier"
                autocomplete="off"
                spellcheck="false"
                aria-describedby="audit-lookup-hint{{ $identifierError ? ' audit-lookup-error' : '' }}"
                @if ($identifierError) aria-invalid="true" @endif
                data-no-draft
            />
            <p id="audit-lookup-hint" class="console-muted">Shows every row about that record, by that person, or done for that person.</p>
            <p id="audit-lookup-error" class="console-field-error">{{ $identifierError }}</p>
            <div class="console-audit-actions">
                <button type="submit" class="console-button">Look up</button>
                @if ($lookedUp)
                    <button type="button" class="console-button-secondary" wire:click="showLatest">Show the latest rows</button>
                @endif
            </div>
        </form>
    </section>

    <div class="console-table-scroll" data-console-table>
        <table class="console-table">
            <caption>
                @if ($lookedUp)
                    Rows for {{ $lookedUp }}, newest first: {{ count($entries) }} {{ count($entries) === 1 ? 'row' : 'rows' }}.
                @else
                    The latest {{ count($entries) }} {{ count($entries) === 1 ? 'row' : 'rows' }} of the audit trail, newest first.
                @endif
                @if ($shown['more'])
                    Showing the newest {{ $shown['limit'] }} rows; older rows are not shown.
                @endif
                <span class="console-table-keys">Read-only: rows cannot be edited or deleted.</span>
            </caption>
            <thead>
                <tr>
                    <th scope="col">Sequence</th>
                    <th scope="col">When</th>
                    <th scope="col">Who</th>
                    <th scope="col">What</th>
                    <th scope="col">Record</th>
                    <th scope="col">Changes</th>
                    <th scope="col">Seal</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr wire:key="audit-{{ $entry->id }}" data-row tabindex="-1">
                        <th scope="row">{{ $entry->sequence }}</th>
                        <td><time datetime="{{ \App\Modules\Shared\Support\Iso8601::format($entry->occurredAt) }}">{{ \App\Modules\Shared\Formats\Format::dateTime($entry->occurredAt, $now) }}</time></td>
                        <td>{{ $words->who($entry, $names) }}</td>
                        <td>{{ $words->what($entry) }}</td>
                        <td>{{ $words->record($entry, $names) }}</td>
                        <td>
                            @php($changes = $words->changes($entry))
                            @if ($changes === [])
                                <span class="console-muted">No field changes</span>
                            @else
                                <ul class="console-change-list">
                                    @foreach ($changes as $change)
                                        <li>{{ $change }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                        <td>{{ isset($sealed[$entry->sequence]) ? 'Sealed' : 'Waiting' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">{{ $lookedUp ? 'No row names that identifier.' : 'The audit trail is empty.' }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
