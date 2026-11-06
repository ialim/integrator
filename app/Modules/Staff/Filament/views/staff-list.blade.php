{{--
    The Staff page: one console table with a fixed header and a caption.
    J and K move between rows, Enter goes to the row's first button, and /
    goes to the search box (resources/console/table-keys.ts).
--}}
<x-filament-panels::page>
    @php($members = $this->getMembers())
    @php($now = app(\App\Modules\Shared\Contracts\Clock::class)->now())
    @php($inDoubt = $this->invitationsInDoubt($members))

    <div class="console-notice-region" role="status" aria-live="polite">
        @if ($notice)
            <p @class(['console-notice', 'console-notice-refused' => $noticeIsRefusal])>{{ $notice }}</p>
        @endif
    </div>

    <div class="console-table-tools">
        <label for="staff-search" class="console-label">Search by name or email</label>
        <input
            id="staff-search"
            type="search"
            class="console-input"
            wire:model.live.debounce.300ms="search"
            autocomplete="off"
            data-console-search
            data-no-draft
        />
    </div>

    <div class="console-table-scroll" data-console-table>
        <table class="console-table">
            <caption>
                Staff accounts: {{ $members->count() }} {{ $members->count() === 1 ? 'person' : 'people' }}.
                <span class="console-table-keys">J and K move between rows, Enter goes to the row's buttons, / goes to search.</span>
            </caption>
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Role</th>
                    <th scope="col">Duties</th>
                    <th scope="col">Status</th>
                    <th scope="col">Changes</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($members as $member)
                    <tr wire:key="staff-{{ $member->id }}" data-row tabindex="-1">
                        <th scope="row">
                            <span class="console-person-cell">
                                <span class="console-initial" aria-hidden="true">{{ $member->initial() }}</span>
                                <span>
                                    <span class="console-person-cell-name">{{ $member->name }}</span>
                                    <span class="console-person-cell-email">{{ $member->email }}</span>
                                </span>
                            </span>
                        </th>
                        <td>{{ $member->role->label() }}</td>
                        <td>{{ $member->role->duties() }}</td>
                        <td>
                            <span @class(['console-status', 'console-status-'.$member->status->value])>{{ $member->status->label() }}</span>
                            @if ($member->status === \App\Modules\Staff\StatusTables\StaffAccountStatus::Invited)
                                <span class="console-status-detail">
                                    @if ($member->invitation_accepted_at && $member->canSignIn())
                                        Password set; authenticator app still to set up, until {{ \App\Modules\Shared\Support\Wat::dateTime($member->invitation_expires_at) }}
                                    @elseif ($member->invitation_accepted_at)
                                        The time to finish setting up has run out; send a new invitation
                                    @elseif (isset($inDoubt[$member->id]))
                                        {{ \App\Modules\Staff\Filament\StaffResource\ListStaff::INVITATION_IN_DOUBT }}
                                    @elseif ($member->invitation_expires_at && $member->invitation_expires_at->greaterThan($now))
                                        Link works until {{ \App\Modules\Shared\Support\Wat::dateTime($member->invitation_expires_at) }}
                                    @else
                                        Link has expired; send a new invitation
                                    @endif
                                </span>
                            @elseif ($member->status === \App\Modules\Staff\StatusTables\StaffAccountStatus::Deactivated && $member->deactivated_at)
                                <span class="console-status-detail">Since {{ \App\Modules\Shared\Support\Wat::dateTime($member->deactivated_at) }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="console-row-actions">
                                @if ($member->status === \App\Modules\Staff\StatusTables\StaffAccountStatus::Invited)
                                    {{ ($this->resendAction)(['member' => $member->id]) }}
                                @elseif ($member->status === \App\Modules\Staff\StatusTables\StaffAccountStatus::Active)
                                    {{ ($this->resetAction)(['member' => $member->id]) }}
                                @endif
                                @if ($member->status !== \App\Modules\Staff\StatusTables\StaffAccountStatus::Deactivated)
                                    {{ ($this->deactivateAction)(['member' => $member->id]) }}
                                @else
                                    <span class="console-muted">None: deactivated accounts stay closed</span>
                                @endif
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No staff account matches “{{ $search }}”.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
