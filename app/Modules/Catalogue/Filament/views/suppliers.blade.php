{{--
    The Suppliers list: filter chips with counts, then one console table
    captioned "Suppliers". J and K move between rows, Enter goes to the row's
    link (resources/console/table-keys.ts).

    Finance and Operations see "Only an Admin approves Suppliers or changes
    the checklist." in place of the settings button; nothing is greyed out.
    A member without the page's capability never sees this view:
    GuardedByCapability swaps in shared::refused-page.
--}}
<x-filament-panels::page>
    @php($suppliers = $this->getSuppliers())
    @php($chip = $this->currentChip())

    <div class="console-notice-region" role="status" aria-live="polite">
        @if ($notice)
            <p @class(['console-notice', 'console-notice-refused' => $noticeIsRefusal])>{{ $notice }}</p>
        @endif
    </div>

    @unless ($this->canConfigure())
        <p class="console-muted" data-admin-only>{{ \App\Modules\Catalogue\Filament\SuppliersPage::ADMIN_ONLY }}</p>
    @endunless

    <x-shared::filter-chips label="Show Suppliers" property="chip" :chips="$this->getChips()" :current="$chip" />

    <div class="console-table-scroll" data-console-table>
        <table class="console-table">
            <caption>
                Suppliers: {{ \App\Modules\Catalogue\Queries\Suppliers::CHIPS[$chip] }}, {{ count($suppliers) }} shown.
                <span class="console-table-keys">J and K move between rows, Enter goes to the row's link.</span>
            </caption>
            <thead>
                <tr>
                    <th scope="col">Supplier</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="console-num">Products</th>
                    <th scope="col">Checklist</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($suppliers as $supplier)
                    <tr wire:key="supplier-{{ $supplier->id }}" data-row tabindex="-1">
                        <th scope="row">
                            <a class="console-link" href="{{ $this->supplierUrl($supplier->id) }}">{{ $supplier->legalName }}</a>
                            @if ($supplier->approvedWithReason())
                                <span class="console-muted">Approved with a reason</span>
                            @endif
                        </th>
                        <td><span @class(['console-status', 'console-status-active' => $supplier->isApproved])>{{ $supplier->statusLabel }}</span></td>
                        <td class="console-num">{{ $supplier->productCount }}</td>
                        <td>
                            <span @class(['console-progress', 'console-progress-need' => ! $supplier->isClear()])>{{ $supplier->progressWords() }}</span>
                            @unless ($supplier->isClear())
                                <span class="console-muted">Needs action: {{ \App\Modules\Catalogue\Queries\SupplierChecklist::namesNeedingAction($supplier->checklist) }}</span>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            {{ $this->emptyWords() }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($this->canConfigure())
        <div class="console-row-actions console-after-table">
            {{ $this->settingsHistoryAction }}
        </div>
    @endif
</x-filament-panels::page>
