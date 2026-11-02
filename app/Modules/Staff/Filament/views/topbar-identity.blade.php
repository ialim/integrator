{{-- The right of the green top bar: the West Africa Time clock, then the person's name and role. --}}
<div class="console-identity">
    <time
        class="console-clock"
        datetime="{{ $nowIso }}"
        data-console-clock
        aria-label="Time now, West Africa Time: {{ $nowWat }}"
    >{{ $nowWat }}</time>
    @if ($member)
        <span class="console-person">
            <span class="console-person-name">{{ $member->name }}</span>
            <span class="console-person-role">{{ $member->role->label() }}</span>
        </span>
    @endif
</div>
