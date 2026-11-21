{{--
    A list's filter chips, each with its count (UX: "Lists use filter
    chips"), for any module's console page: <x-shared::filter-chips>. Each
    chip is a toggle button that sets the page's Livewire property; the one
    in use is pressed, so a screen reader hears "Approved · 7, toggle button,
    pressed".

    @var string $label     names the group: "Show Suppliers"
    @var string $property  the Livewire property a chip sets: "chip"
    @var list<array{key: string, label: string, count: int}> $chips
    @var string $current   the key of the chip in use
--}}
@props(['label', 'property', 'chips', 'current'])

<div class="console-chips" role="group" aria-label="{{ $label }}" data-filter-chips>
    @foreach ($chips as $chip)
        <button
            type="button"
            class="console-chip"
            aria-pressed="{{ $chip['key'] === $current ? 'true' : 'false' }}"
            wire:click="$set('{{ $property }}', '{{ $chip['key'] }}')"
            wire:key="chip-{{ $chip['key'] }}"
        >
            {{ $chip['label'] }} · {{ $chip['count'] }}
        </button>
    @endforeach
</div>
