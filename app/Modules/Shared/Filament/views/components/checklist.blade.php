{{--
    A checklist of items, each Clear or Needs action in words ("Onboarding
    checklist" here, "Before it can go live" in Story 2.4), for any module's
    console page: <x-shared::checklist>. The list is named by the heading the
    page gives it. Each item reads as its name, then its state in words, so
    a screen reader hears "Business details: Needs action: missing TIN". The
    mark beside the words is decoration only.

    @var string $labelledby  the id of the heading that names the list
    @var list<array{label: string, clear: bool, words: string, optional?: bool}> $items
--}}
@props(['labelledby', 'items'])

<ul class="console-checklist" aria-labelledby="{{ $labelledby }}">
    @foreach ($items as $item)
        <li @class(['console-checklist-item', 'console-checklist-need' => ! $item['clear']]) data-checklist-item>
            <span class="console-checklist-label">{{ $item['label'].(($item['optional'] ?? false) ? ' (optional)' : '') }}<span class="sr-only">:</span></span>
            <span class="console-checklist-state"><span class="console-checklist-mark" aria-hidden="true">{{ $item['clear'] ? '✓' : '!' }}</span>{{ $item['words'] }}</span>
        </li>
    @endforeach
</ul>
