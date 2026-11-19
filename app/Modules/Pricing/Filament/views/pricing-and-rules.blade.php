{{--
    The Pricing and rules page: its one section, "Markups and the floor".
    The margin floor, then one console table captioned "Platform Markup by
    Product Range". J and K move between rows, Enter goes to the row's first
    button (resources/console/table-keys.ts).

    A member who cannot change these sees the same values under the line
    "Set by an Admin. Only an Admin can change these." and no change buttons;
    nothing is shown disabled. A member without the page's capability never
    sees this view: GuardedByCapability swaps in shared::refused-page.
--}}
<x-filament-panels::page>
    @php($ranges = $this->getRanges())
    @php($markups = $this->getMarkups())
    @php($floor = $this->getFloor())
    @php($manageRanges = $this->canManageRanges())
    @php($manageRules = $this->canManageRules())

    <div class="console-notice-region" role="status" aria-live="polite">
        @if ($notice)
            <p @class(['console-notice', 'console-notice-refused' => $noticeIsRefusal])>{{ $notice }}</p>
        @endif
    </div>

    <section class="console-panel" aria-labelledby="pricing-markups-heading">
        <h2 id="pricing-markups-heading" class="console-panel-heading">Markups and the floor</h2>
        @unless ($manageRanges || $manageRules)
            <p class="console-muted" data-read-only>{{ \App\Modules\Pricing\Filament\PricingAndRules::READ_ONLY }}</p>
        @endunless

        <dl class="console-facts">
            <div>
                <dt>Margin floor</dt>
                <dd data-margin-floor>{{ $floor->words() }} over Supplier Cost. No price before VAT may fall below it.</dd>
            </div>
        </dl>
        <div class="console-row-actions">
            @if ($manageRules)
                {{ $this->floorAction }}
            @endif
            {{ $this->floorHistoryAction }}
        </div>
    </section>

    <div class="console-table-scroll" data-console-table>
        <table class="console-table">
            <caption>
                Platform Markup by Product Range
                <span class="console-table-keys">J and K move between rows, Enter goes to the row's buttons.</span>
            </caption>
            <thead>
                <tr>
                    <th scope="col">Product Range</th>
                    <th scope="col">Compliance</th>
                    <th scope="col" class="console-num">Markup</th>
                    <th scope="col" class="console-num">Products</th>
                    <th scope="col">Status</th>
                    <th scope="col">Changes</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ranges as $range)
                    <tr wire:key="range-{{ $range->id }}" data-row tabindex="-1">
                        <th scope="row">{{ $range->name }}</th>
                        <td>{{ $range->complianceLabel }}</td>
                        <td class="console-num">{{ isset($markups[$range->id]) ? $markups[$range->id]->words() : 'Not set' }}</td>
                        <td class="console-num">{{ $range->productCount }}</td>
                        <td><span @class(['console-status', 'console-status-active' => ! $range->isRetired, 'console-status-deactivated' => $range->isRetired])>{{ $range->statusLabel }}</span></td>
                        <td>
                            <span class="console-row-actions">
                                @unless ($range->isRetired)
                                    @if ($manageRules)
                                        {{ ($this->markupAction)(['range' => $range->id]) }}
                                    @endif
                                    @if ($manageRanges)
                                        {{ ($this->renameAction)(['range' => $range->id]) }}
                                        {{ ($this->complianceAction)(['range' => $range->id]) }}
                                        {{ ($this->retireAction)(['range' => $range->id]) }}
                                    @endif
                                @endunless
                                {{-- Read-only: who changed the range or its markup, when and why, from the audit trail. --}}
                                {{ ($this->historyAction)(['range' => $range->id]) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No Product Range yet.{{ $manageRanges ? ' Add one with "Add a Product Range".' : '' }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
