# Adversarial review: architecture spine (2026-10-09)

Independent reviewer. Lens: two units one level down that each obey every AD to the letter yet build incompatibly. Spine not edited. Sources: ARCHITECTURE-SPINE.md, PRD, PRD addendum (status model, edge-case register rows cited as "row N").

Verdict: the paradigm holds, but AD-4's lock contract cannot be followed as written under AD-2. Events have no durable delivery. Several money paths (repricing, delivery VAT, Credit Note application, split transfers) have no owner contract, so compliant teams will compute or count money differently.

---

## Critical

### C1. AD-4 lock roots belong to other modules, and AD-2 forbids touching them
- **Unit A (Fulfilment, MarkShipmentDelivered).** AD-4 says the Shipment's root is the **Order**. Orders owns the Order table, and AD-2 forbids Fulfilment from querying it. The team obeys AD-2: it locks the Shipment row only, or locks nothing.
- **Unit B (Orders, RequestCancellation, FR-57 / row 52).** Locks the Order, sets "Shipments frozen", and commits.
- **Clash.** A runs concurrently with B, never touches the Order row, and moves the Shipment to In-transit after the freeze. The same gap exists for Catalogue reservations ("root: Order", owned by Catalogue), for Order creation ("locks its Business Account first", owned by Identity), and for CreditNotes refunds versus Hold (Business Account).
- **Second clash: nested call order inverts the global order.** CreditNotes `ApplyCreditNote` locks the Credit Note (its root), then calls Orders `RecordCreditApplication`, which locks the Order. Orders `CancelOrder` locks the Order, then calls CreditNotes `RestoreApplication` (row 57), which locks the Credit Note. Each module "locks its own root first", but the two transactions take the locks in opposite order, so they deadlock. AD-4's fixed order assumes one Action knows every root in advance. Nested cross-module Actions don't.
- **Missing roots.** The list omits Return Request, Case, Issue Code, Shipment, Product (non-stock flags such as quarantine and counterfeit), Proposal, Vouch Code, and the ordering between two roots of the same type. Vouch Code redemption locks two Business Accounts: the voucher (FR-5 cap) and the redeemer. Suspending a vouchee for fraud also suspends the voucher (FR-7), locking both in the other order.
- **Proposed rule (AD-4.1).**
  1. Every root type exposes a public `Lock{Root}` Action in its owning module. That is the only way another module may lock it.
  2. The entry Action (the one a caller invokes) acquires every root it will touch, in the global order, before calling any nested Action. Nested Actions assert that their root is already locked in the current transaction (a lock registry in `Shared`) and never lock on their own.
  3. Extend the order to: Business Account (ascending id) > Project > Proposal > Order > Shipment > Return Request/Case > Credit Note (ascending id) > Product/stock rows (ascending Product id) > audit (see H4).

### C2. After-commit events have no durable delivery, but listeners drive state changes
- **Unit A (Orders).** Emits `OrderPaid` after the outermost commit, as AD-4.6 requires.
- **Unit B (CreditNotes).** Issues the tax invoice (FR-77) from a queued listener on `OrderPaid`. A Projects listener on `ProductFlaggedCounterfeit` marks Proposals stale (row 29). The diagram explicitly allows `LST --> ACT`.
- **Clash.** The process dies between the commit and the dispatch, or during a deploy, or the queue push fails. The event is gone: no tax invoice for a Paid Order, no stale Proposal, and no notification. The sweep cannot recover it, because nothing records that a reaction was owed.
- **Proposed rule (AD-4.6 tightened).** Domain events are written to an `outbox` table inside the Action's transaction. A relay dispatches them, at least once, keyed by event id. Listeners are idempotent on event id. Any reaction that changes another module's state and is required for correctness (invoice issue, stale marking, Completed derivation) must either be called synchronously inside the transaction or be outbox-driven. A best-effort after-commit dispatch is allowed only for notifications.

### C3. No Pricing contract for repricing, delivery charges or delivery VAT, so Orders must compute prices
- **Unit A (Pricing).** Exposes exactly what AD-7 says: one quote Query (lines, Business Account, current rate version) that returns a snapshot.
- **Unit B (Orders, RepriceOrder, FR-38).** Must change only the rate, keeping tier, markup and VAT as captured. The quote Query re-reads the current tier and markup, which is wrong. Orders therefore scales the stored naira by `new_rate / old_rate`, which is legal under AD-6 because it uses the Money type. That double-rounds, and the result differs from the addendum vector (NGN 752,500) on any multi-line Order. The same gap applies to:
  - FR-35: the Proposal Rate is used instead of the current rate, but the Query only takes "the current Platform Rate version".
  - FR-37/FR-75: Zone Rate times Shipments, and delivery VAT on the tax invoice (FR-77).
  - FR-61: the restocking fee on the pre-VAT price.
  Each of those amounts is computed by whichever module needs it.
- **Proposed rule (AD-7 extended).** Pricing alone exposes:
  - `QuoteLines(lines, account, rate_version, tier_as_of)`
  - `RepriceSnapshot(snapshot, new_rate_version)`, which changes the rate only
  - `PriceDelivery(zone_rate, shipments, vat_rate)`
  - `SplitVat(gross)` and `PreVatOf(line)`

  No other module multiplies, divides or applies VAT to money. A Money-type-only arithmetic rule is not enough: the formula must also have one owner.

### C4. AD-10 contradicts the split-transfer rule, and duplicate statement imports defeat it
- **Unit A (Orders, ConfirmPayment).** Enforces AD-10: a unique constraint means one statement line confirms at most one Payment.
- **Unit B (Orders, Finance split, row 55 / FR-41).** One transfer covers two Orders: "Finance splits with allocation records". That needs one statement line to fund two Payments, which AD-10's constraint refuses. A team that follows row 55 must either break AD-10 or invent a fake second statement line.
- **Second clash.** The same bank CSV is imported twice, or an overlapping date range is imported. That creates two statement-line rows for one credit, and two forged Receipts each confirm against one of them. AD-10 holds, and the platform is paid once while recording two Payments.
- **Proposed rule (AD-10 rewritten).**
  - A statement line has a natural-key unique constraint: account, value date, amount and bank reference. Re-import is idempotent.
  - Confirmation goes through `PaymentAllocation(statement_line_id, payment_id, amount)`.
  - A check or trigger keeps the sum of allocations at or below the statement line amount. A Payment has at most one allocation unless Finance splits with a second approver.
  - FR-41's "bank reference used once" applies to the statement line, not the Payment.

---

## High

### H1. Order status transitions have two candidate owners
- **Unit A (Fulfilment).** Owns Approval (FR-44) and Shipments, so it writes `Approved` and derives `Completed` (row 62) through Orders Actions it calls synchronously.
- **Unit B (Orders).** Owns the Order status machine and listens to `ShipmentDelivered` to derive `Completed` itself.
- **Clash.**
  - Both run, so the Order completes twice, or the second attempt gets a 409 that is logged as an error.
  - Under A, the FR-44 credit-line action (Fulfilment) cancels Order Lines that Orders owns.
  - Neither team excludes the FR-61 zero-value Replacement Shipment on a Completed Order. Its new Processing Shipment either re-opens completion (there is no Completed to Approved transition) or counts toward tier spend.
- **Proposed rule.**
  - Orders alone writes Order and Order Line status, including Approved, Completed, Cancelled and line-credited. It exposes `ApproveOrder`, `CreditOrderLine` and `EvaluateCompletion`.
  - Fulfilment calls these synchronously within its entry Action, under the C1 lock rule.
  - A Shipment carries `kind ∈ {normal, replacement}`. Replacement Shipments are excluded from completion, spend, invoices and Established counts by the owning Queries, not by each consumer.

### H2. Credit Note application at request time has no ledger entry, so it can be spent twice
- **Unit A (Orders).** Per FR-43, "an application counts as a Payment from the moment of the request". It records a pending credit Payment and stops the expiry clock.
- **Unit B (CreditNotes).** AD-9 entry types are only issue, apply, refund and expire. The team writes `apply` when Finance completes the application, which is legal because the balance is derived from entries.
- **Clash.** The Owner requests the same NGN 300k note against two Orders. Each request sees the full balance, so both Orders count NGN 300k as paid and both reach Paid. Restoring an application after a cancel (row 57, "original expiry") and refund-versus-apply (row 58) have no entry type either. Teams will invent `restore` with different signs and status derivations.
- **Proposed rule (AD-9 extended).**
  - Entry types are issue, hold (request), release (hold withdrawn), apply (completes a hold), restore (reverses an apply, keeps the original expiry), refund and expire.
  - Available balance = issue − holds − applies + restores − refunds − expires.
  - Request, hold and refund all lock the Credit Note, and the balance is checked under the lock.
  - Status derivation is defined once, in a CreditNotes Query.

### H3. Tax invoice numbering and timing are undefined
- **Unit A (CreditNotes).** Owns the sequence (AD-9). It uses a PostgreSQL `SEQUENCE` and issues invoices from an `OrderPaid` listener.
- **Unit B (Orders).** Calls `IssueTaxInvoice` inside ConfirmPayment's transaction.
- **Clash.**
  - Sequences are non-transactional, so rolled-back confirmations leave gaps. "Unique, sequential" invoice numbers for VAT may need to be gapless.
  - Listener issuance numbers invoices in completion order, not payment order.
  - A Reversed Payment (FR-41, Paid back to Awaiting Payment) leaves an invoice for an unpaid Order. When the Order is re-Paid, it gets a second invoice, which breaks "exactly one tax invoice".
  - FR-77 wants serial numbers on the invoice, but serials are recorded at dispatch (FR-65), after Paid, and AD-14 forbids regenerating the invoice.
  - A credit-note document must "reference the invoice it adjusts", but a Credit Note for an Expired Order (overpayment) has no invoice.
- **Proposed rule.**
  - Numbers come from a counter row locked `FOR UPDATE` in the issuing transaction, so they are gapless.
  - A unique constraint enforces one invoice per Order.
  - Reversal after an invoice issues a credit-note document, and re-Paid issues a new invoice. The accountant confirms this flow.
  - Serials go on a delivery note, not the invoice, or the invoice is issued at first dispatch. Pick one in the AD.
  - A credit-note document references an invoice when one exists, and otherwise the Order reference.

### H4. The hash-chained audit trail serialises every writer, and nested audit writes deadlock
- **Unit A (Orders, ConfirmPayment).** Writes its audit row first (AD-4.5), which locks the chain tail to read the previous hash. Then it calls Catalogue `ReserveStock`, which locks stock rows.
- **Unit B (Returns, DecideRemedy).** Calls Catalogue `Restock` (locks stock rows), then writes its audit row (locks the chain tail).
- **Clash.**
  - A deadlock: A holds the tail and wants stock, while B holds stock and wants the tail.
  - Every Action in every module, including each NIN or bank-detail read (AD-21), serialises on one row.
  - Reporting reads bank details directly (AD-2 exception) with no Action, so FR-53 "every read of a bank detail" goes unaudited in the reconciliation export.
- **Proposed rule (AD-21 tightened).**
  - Actions insert audit rows with a `bigserial` sequence and no hash in the transaction.
  - A single-writer chain job, run by the sweep, computes the hashes in sequence order afterwards. Alternatively, audit rows are buffered and flushed as the last statement before the outermost commit; the audit lock then comes last in the C1 order.
  - Reporting and exports read bank detail and NIN-adjacent fields only through an audited Query.

### H5. Sync change cursors skip rows, and prices have no cursor
- **Unit A (Catalogue).** The change cursor is `updated_at` (server time). The pull query is `WHERE updated_at > :cursor`.
- **Unit B (Pricing).** A Platform Rate or tier change alters every price, but no Catalogue row changes.
- **Clash.**
  - A transaction that started earlier commits later with an older `updated_at`, so the device's cursor has already passed it and never pulls the row.
  - Offline prices never refresh after a rate change. The "as of" label on prices shows the time the Product row last changed, not the time of the rate in force.
  - AD-12 also syncs "the user's own Projects", but FR-28 needs Projects to appear on the Business Account's other devices.
  - The purge triggers are only "User removed or changes role". Level 3 revocation and Hold (FR-72) leave Selling Prices on the device.
- **Proposed rule (AD-12).**
  - The cursor is a value assigned so that rows only become visible in cursor order: a commit-ordered `xid8` with `pg_snapshot_xmin`, or a sequence fetched with a safety lag, such as pulling only below the oldest in-flight transaction.
  - The price payload carries its own cursor, which bumps on rate, tier or markup change, and its own "as of" time.
  - Sync scope is the Business Account's Projects, filtered by role.
  - A purge is triggered by any change to role, Access Level, Hold or membership.

### H6. Restricted-field filtering binds only the API and sync, while Queries, notifications, PDFs and exports are unfiltered
- **Unit A (Orders).** `OrderSummaryQuery` returns a DTO with `amount_due` (AD-2: Queries return DTOs). The Orders API resource strips it for Technicians (AD-13).
- **Unit B (Notifications).** A listener re-reads `OrderSummaryQuery` (conventions: "listeners re-read through Queries") and renders the template to every recipient on the Order, including a Technician.
- **Clash.** The Technician receives the amount in WhatsApp text (FR-11 and FR-51 forbid this), and no AD is broken. The same gap covers:
  - Catalogue search ordering by price (FR-2, "no price-derived ordering"), where AD-13 only removes fields.
  - The Parts-list PDF.
  - Re-reading at send time also means the message states current state, not the event's state. For example, "Payment unconfirmed" is sent after the re-upload has already been Confirmed.
- **Proposed rule (AD-13 widened).**
  - Every outbound surface — API, sync, notification render, PDF, export, sort and filter parameters — goes through one `Shared` visibility policy keyed by (Access Level, role, staff role).
  - Notification recipients are resolved and filtered per recipient.
  - User-facing events carry the facts the message states, snapshotted at emit time, and a per-aggregate sequence number. Listeners drop events older than the last one sent for that aggregate.

### H7. Counterfeit "Suspected" has no single owner, and blocks rely on fan-out
- **Unit A (Returns).** Owns counterfeit cases (module table), so it writes Suspected, Confirmed and Cleared, and emits `CounterfeitSuspected`.
- **Unit B (Catalogue).** Owns the Product quarantine flag (FR-12) and batches (goods-in records, FR-74). It marks the Product or batch Suspected itself (FR-66 "Operations marks a Product or batch").
- **Clash.**
  - There are two Suspected states with no defined link.
  - Orders, Fulfilment and Projects learn of it by listener, so a Payment confirmed or Shipment dispatched in the window between commit and listener goes through, despite FR-66 "blocks payment confirmation and shipment of affected lines".
  - Batch-level holds need serial-to-batch mapping across Catalogue (goods-in serials) and Fulfilment (dispatch serials) (see M1).
- **Proposed rule.**
  - Catalogue owns sellability (`quarantined`/`suspected` on the Product or batch). Returns owns the case and calls Catalogue `SetSuspected`.
  - Blocking is pull-based: ConfirmPayment, ReserveStock, dispatch and AddProjectItem each call Catalogue `AssertSellable` under the stock-row lock (`FOR SHARE`).
  - Fan-out events only notify and mark stale.

### H8. Reporting reads private tables and opaque JSON with no contract
- **Unit A (Pricing).** Ships snapshot v2: it renames `tier_discount` to `discount{type,pct}` (AD-7: "a versioned DTO whose shape Pricing alone defines").
- **Unit B (Reporting).** Reads `order_lines.price_snapshot->>'tier_discount'` directly (AD-2 exception) for SM-C1. It recomputes Credit Note balances from ledger rows with its own sign convention.
- **Clash.**
  - SM-C1 silently drops every v2 line.
  - The Credit Note ageing export disagrees with the CreditNotes Query.
  - The VAT export (FR-80) uses its own VAT extraction and fails to reconcile with invoices (see H9).
- **Proposed rule (AD-2 exception narrowed).** Reporting reads only versioned reporting views that each owning module publishes in its own migrations. Each view has a contract test in the owner's suite. Derived figures (balances, status, net spend, VAT) appear in the views computed by the owner's logic, never recomputed in Reporting.

### H9. VAT extraction and client pricing are implemented by several modules
- **Units.** Each team uses the Money type, as AD-6 allows:
  - Projects computes Proposal client prices and the FR-30 pre-VAT lines (divide by 1 + VAT, round).
  - CreditNotes computes the FR-43 "VAT component" and the FR-61 restocking fee on the pre-VAT price.
  - Orders computes invoice line VAT and delivery VAT.
- **Clash.** Three VAT-split implementations: one rounds per unit, one per line, one subtracts. The monthly VAT export cannot reconcile to invoices and credit notes (FR-80), and the Proposal kobo rule (row 30) holds in one place only.
- **Proposed rule.** Folded into C3. Pricing owns `SplitVat`, `ApplyIntegratorMargin` and `RestockingFee`, with the addendum test vectors in Pricing's suite. Other modules call them.

### H10. Work queues, deputies and Business-Hours deadlines have no owning contract
- **Unit A (Staff).** Owns "work queues" (module table) with a generic `queue_items` table: owner, due_at, escalation. Modules push items into it through a Staff Action.
- **Unit B (Orders).** Per AD-3, its Filament Receipts queue lists Payments in Submitted, with an `assignee` column on Payments.
- **Clash.**
  - Two sources of truth: a Payment confirmed in Orders leaves a stale Staff queue item.
  - An out-of-office switch (FR-76, "within a minute") moves the Staff items, but not Orders' assignees, because AD-2 forbids Staff from writing them.
  - Due dates stored at enqueue time ignore a holiday Admin adds later (AD-5 computes Business Hours "over Staff's holiday calendar", but at what moment?).
- **Proposed rule.**
  - Staff owns `queue_items` as the only assignment and SLA record. Each module creates and closes items through Staff Actions, in the same transaction as the state change.
  - Business-Hours `due_at` is recomputed by a Staff Action whenever the calendar changes.
  - Reassignment touches only Staff's table.

---

## Medium

### M1. Serial numbers and back-order markers each have two owners
- Catalogue records serials at goods-in (FR-74). Fulfilment records serials at dispatch (FR-65). Returns looks serials up (FR-67) and records new ones on replacements (FR-61).
- Each team builds its own serial table with its own normalisation (row 64). Uniqueness "across active dispatches" and the serial-to-batch link needed for FR-66 then span two modules.
- The back-order "ordered from Supplier" and "received" markers sit on Order Lines (Orders), but FR-74 is mapped to Catalogue, which "cannot mark received without a goods-in record".
- **Rule.**
  - Catalogue owns `serial_units` (serial, Product, batch, normalised form) with a unique constraint.
  - Fulfilment owns `dispatch_serials(serial_unit_id, shipment_id, active)`, with a partial unique constraint on active rows.
  - Orders owns the markers, set through an Orders Action that calls Catalogue `AssertGoodsIn`.

### M2. Timer Actions don't re-check deadlines under lock, and reminders have no columns
- The sweep selects an Order due at hour 48. A Receipt upload commits first and extends `expires_at`. The sweep then calls ExpireOrder, which checks only the transition table (Awaiting Payment to Expired is valid) and expires it.
- The reminders at hours 20 and 40, the Issue Code reminder at hour 36, the Credit Note reminders at 60 and 14 days, the uncollected-pickup reminder at 7 days and the FR-39 offer at 14 days are not state transitions, so "the same Actions a person would" call does not exist. Teams will use delayed jobs (banned) or ad-hoc flags.
- The pause of up to 7 days on the Issue Code (FR-68) and FR-4's NIN retry window are deadlines that move.
- **Rule (AD-5).**
  - Each timer Action re-reads its deadline under the root lock and refuses unless `deadline <= now()`, using database transaction time.
  - Each reminder is a `{name}_due_at` and `{name}_sent_at` column pair on the owning entity, set by a `Send{Name}Reminder` Action.
  - A paused clock stores `paused_at` and the remaining duration.
  - Transport retries (WhatsApp fallback, provider backoff) may use queue backoff. Business deadlines may not.

### M3. A refusal from a nested Action is either a return value or an exception
- Catalogue `ReserveStock` "returns the current state" (AD-4.3) as a result object. Orders' ConfirmPayment ignores it and commits Paid without a reservation.
- **Rule.** Inside a joined transaction, a refusal throws a typed `TransitionRefused` exception. Only the entry Action converts it to a result or a 409. A partial outcome (FR-18 partial reservation) is an explicit return type.

### M4. Sync push can name another User, and demoted Users' queued operations are applied
- AD-12 says each operation names "its acting User", and AD-4 audits that actor. A device can name any User of the Business Account.
- Row 9 requires queued edits from a removed User to be held for a Manager. AD-12 only purges.
- **Rule.** The acting User must equal the session User, and otherwise the operation is refused. Operations from a User removed or re-roled since they were queued are stored as held conflicts for an Owner or Manager, never applied.

### M5. Filament's built-in save bypasses audit, and "state" is undefined
- AD-1 allows a Filament save of non-money, non-state fields. FR-12 requires every Product change to be audited, and row 67 covers Lead Time, thresholds and windows. A team saves a Product description through Filament, and no audit row is written.
- It is also undecided whether Stock Threshold or Lead Time count as "state".
- **Rule.** No Filament built-in model save for any entity that FR-53 or row 67 audits. Each model declares its editable-field class in code, and an architecture test enforces it.

### M6. Cross-module foreign keys can cascade
- A Projects migration adds `project_items.product_id REFERENCES products ON DELETE CASCADE`. Deleting a draft Product (Catalogue) then deletes Project Items, which makes the database a second writer. AD-2 allows cross-module foreign keys.
- **Rule.** Cross-module foreign keys are `ON DELETE RESTRICT` and `ON UPDATE NO ACTION`, enforced by an architecture test over migrations.

### M7. Money scale versus full-precision intermediates
- AD-6 stores USD at 4 decimal places. The test vector's intermediate is USD 59.259375, at 6 decimal places.
- A module that persists the discounted USD unit price at 4 decimal places, then recomputes naira from it (tier spend, Reporting, repricing), gets a different kobo from FR-19's "full precision, round once".
- Platform Rate scale is unstated.
- **Rule.**
  - Snapshots store inputs (cost, percentages, rate) and the final rounded naira only. Derived USD figures are never stored and later re-multiplied.
  - Percentage and rate scales are declared (for example `numeric(9,6)` and `numeric(12,4)`).

### M8. Per-field last-writer-wins and tombstones conflict on delete versus edit
- Device A deletes a Project Item (tombstone, received at t1). Device B, offline, edits the quantity (received at t2 > t1).
- The server team resurrects the item ("last writer wins per field"). The PWA team treats the tombstone as final. The devices diverge.
- **Rule.** A tombstone is a field (`deleted_at`) under the same last-writer-wins rule. An edit received after the tombstone resurrects the item and records a conflict. Both the server and the PWA implement it from one shared spec with test vectors.

### M9. Proposal immutability, the first-Order flag and document inputs
- AD-14 says "never overwritten". FR-31 says regeneration replaces the earlier Proposal and "the platform holds no earlier version". A team obeying AD-14 keeps old Proposal PDFs, which breaks FR-31.
- FR-35's "Proposal Rate applies only to the first Order" needs a flag on the Proposal (Projects) that Orders must set, but no Action is defined for it.
- PDF jobs re-read through Queries at job time, so a Trade Name edited (FR-70) between commit and render changes an "issued" invoice or Proposal.
- **Rule.**
  - AD-14 exempts Proposal PDFs: delete the previous file after the new one commits.
  - Projects exposes `ConsumeProposalRate`, called by CreateOrder under the Project lock.
  - Every document job renders only from a snapshot row written in the issuing transaction.

### M10. Entities and settings with no clear owner
- **Platform bank details** (FR-40, FR-52: second approver, effective after 24 hours, banner): absent from the module table.
- **WhatsApp opt-in:** claimed by Identity (FR-69, Terms) and by Notifications (the module table lists "opt-ins").
- **Established Integrator status** (Glossary, row 13): needs Completed Orders net of returns (Orders, Fulfilment, Returns), but is used by Identity.
- **Loyalty spend basis** (row 32): needs the same data. Pricing and Reporting would each build it.
- **Order Quote** (FR-34): ownership unspecified.
- **NGN 5 tolerance:** Pricing or Projects.
- **Rule.**
  - Assign each item explicitly: bank details to Orders; opt-ins to Notifications, with Identity calling them at Terms acceptance.
  - Orders publishes a `NetDeliveredGoodsQuery` that Identity, Pricing and Reporting all use.
  - Order Quote and tolerance go to Pricing.

---

## Low

### L1. Idempotency key scope
- One team stores keys per endpoint and User; another stores them globally.
- A replay with the same key and a different payload is undefined.
- **Rule.** Keys are unique per (User, operation), along with a payload hash. A mismatch returns 422.

### L2. The 14-day lock judged on the device
- The device clock can be wound back, contradicting AD-12's own distrust of device clocks.
- **Rule.** Store the server time of the last sync and refuse when the device time is earlier than that stored time.

### L3. AD-3 published views
- A view published by Orders that joins Identity tables is Orders' SQL reading Identity's tables.
- **Rule.** A module's view selects only from that module's own tables. Cross-module joins happen in the consuming Filament resource over two owners' views.

### L4. AD-20 restore and the AD-21 chain
- A logical dump restored elsewhere must recreate the audit trigger and roles before the application writes to the database. Add this to the restore-drill checklist.
