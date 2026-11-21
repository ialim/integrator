{{--
    One Supplier's page: Business details, Contacts, Agreement terms, then
    the Onboarding checklist and its approve routes. A missing detail reads
    "Not stated". An off "Approve the supplier" stays visible and dashed,
    focusable, with its reason beside it, which is focusable too (UX-DR29).
    Finance and Operations see one line instead of the approve buttons.
--}}
<x-filament-panels::page>
    @php($supplier = $this->record())
    @php($d = fn (string $field) => ($v = $supplier->detail($field)) === null ? 'Not stated' : (string) $v)

    <div class="console-notice-region" role="status" aria-live="polite">
        @if ($notice)
            <p @class(['console-notice', 'console-notice-refused' => $noticeIsRefusal])>{{ $notice }}</p>
        @endif
    </div>

    @if ($supplier->approvedWithReason())
        <p class="console-panel console-approval-reason" data-approval-reason>
            <strong>Reason given for approving it:</strong> {{ $supplier->approvalReason }}
        </p>
    @endif

    <section class="console-panel" aria-labelledby="supplier-business-heading">
        <h2 id="supplier-business-heading" class="console-panel-heading">Business details</h2>
        <dl class="console-facts console-facts-grid">
            <div><dt>Legal name</dt><dd>{{ $d('legal_name') }}</dd></div>
            <div><dt>Trade name</dt><dd>{{ $d('trade_name') }}</dd></div>
            <div><dt>CAC RC number</dt><dd>{{ $d('cac_rc_number') }}</dd></div>
            <div><dt>TIN</dt><dd>{{ $d('tin') }}</dd></div>
            <div><dt>Address</dt><dd>{{ $d('address') }}</dd></div>
            <div><dt>Invoices in</dt><dd>{{ $d('invoice_currency') }}</dd></div>
            <div><dt>Payment terms</dt><dd>{{ $d('payment_terms') }}</dd></div>
        </dl>
    </section>

    <section class="console-panel" aria-labelledby="supplier-contacts-heading">
        <h2 id="supplier-contacts-heading" class="console-panel-heading">Contacts</h2>
        <div class="console-table-scroll">
            <table class="console-table">
                <caption class="sr-only">Contacts</caption>
                <thead>
                    <tr>
                        <th scope="col">For</th>
                        <th scope="col">Name</th>
                        <th scope="col">Phone</th>
                        <th scope="col">Email</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (['sales' => 'Sales', 'returns' => 'Returns and RMA'] as $who => $for)
                        <tr>
                            <th scope="row">{{ $for }}</th>
                            <td>{{ $d($who.'_contact_name') }}</td>
                            <td>{{ $d($who.'_contact_phone') }}</td>
                            <td>{{ $d($who.'_contact_email') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="console-panel" aria-labelledby="supplier-terms-heading">
        <h2 id="supplier-terms-heading" class="console-panel-heading">Agreement terms</h2>
        <dl class="console-facts">
            <div><dt>Returns</dt><dd>{{ $d('returns_terms') }}</dd></div>
            <div>
                <dt>Dead on arrival</dt>
                <dd>
                    {{ $d('doa_terms') }}
                    <span class="console-muted">Window: {{ $supplier->detail('doa_window_days') === null ? 'Not stated' : $supplier->detail('doa_window_days').' days' }}</span>
                </dd>
            </div>
            <div><dt>Counterfeit chargeback</dt><dd>{{ $d('counterfeit_terms') }}</dd></div>
        </dl>
    </section>

    <section class="console-panel" aria-labelledby="supplier-checklist-heading" data-onboarding-checklist>
        <h2 id="supplier-checklist-heading" class="console-panel-heading">Onboarding checklist</h2>
        <p class="console-muted">{{ $supplier->progressWords() }}: {{ $supplier->clearCount() }} of the {{ $supplier->requiredCount() }} required items are clear.</p>
        <x-shared::checklist labelledby="supplier-checklist-heading" :items="$this->checklistItems($supplier)" />

        @if ($this->canApprove())
            @unless ($supplier->isApproved)
                <div class="console-row-actions console-approve-actions">
                    @if ($supplier->isClear())
                        {{ $this->approveAction }}
                    @else
                        <button type="button" class="console-button-off" aria-disabled="true" aria-describedby="supplier-approve-why" data-approve-off>Approve the supplier</button>
                        {{ $this->approveWithReasonAction }}
                    @endif
                </div>
                @unless ($supplier->isClear())
                    <p id="supplier-approve-why" class="console-off-reason" tabindex="0">{{ $supplier->offBecause() }}</p>
                @endunless
            @endunless
        @else
            <p class="console-muted" data-admin-only>{{ \App\Modules\Catalogue\Filament\SuppliersPage::ADMIN_ONLY }}</p>
        @endif
    </section>
</x-filament-panels::page>
