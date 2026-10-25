# Review: rubric pass, independent reviewer (2026-10-09)

Spine: `ARCHITECTURE-SPINE.md` (draft, 2026-10-09). Inputs checked: PRD §4.1–4.15 (FR-1..80, NFR-1..10), addendum (Delivery surface, Integrations, Status model, Fulfilment notes, Edge-case register rows 1–82), EXPERIENCE.md (Money and numbers, Time and clocks, Who sees what, Trust and verification, Offline and weak signal). The author's pass (`review-author-pass.md`) was read only to avoid repeating its closed items.

**Verdict.** The spine is a solid base: the single write path, the Action contract, money-as-decimal and server-side field removal are the right calls. Two ADs, as written, forbid behaviour the PRD requires (AD-7 rules out repricing, AD-10 rules out split allocation). Several real cross-epic divergence points have no AD: maker-checker and segregation of duties, the shared work-queue item, how uploads are timed, the sync cursor, and step-up codes. The operational envelope has gaps that are neither decided nor deferred, and the spine has no Open Questions section where they could sit.

Checklist summary:

| # | Check | Result |
|---|---|---|
| 1 | Real divergence points fixed, none missed | Partial: see H-3, H-4, H-5, H-6, M-8 |
| 2 | Every Rule enforceable and prevents its divergence | Partial: AD-7 and AD-10 contradict the PRD (C-1, C-2); AD-1's Filament carve-out breaks the audit (M-1); AD-12's cursor is unspecified (H-2) |
| 3 | Nothing Deferred lets units diverge | Mostly: the PDF engine has no owner (L-3), and e-invoicing readiness is waved off (L-4) |
| 4 | Tech current | OK. Spot-checked today: typescript 7.0.2, vite 8.3.4, vite-plugin-pwa 2.0.0 (peer accepts vite ^8). "PHP 8.3+" is loose (L-1) |
| 5 | PRD capabilities covered, no quiet requirement dropped | Gaps: edge rows 9, 41, 44, 55, the FR-38 repricing, FR-40's malware scan, FR-53's no-PII diffs, FR-72 erasure, FR-73's no bulk price listing, and the EXPERIENCE staff-identity redaction (C-1, C-2, H-3, M-4, M-6, M-7, M-9, M-10) |
| 6 | Every dimension decided, deferred or open | Fails for the operational envelope: SMS/OTP, bot checks, malware scanning, rate-limit and cache store, image pipeline, DR key escrow, DSAR and breach runbook (M-2). No Open Questions section |

---

## Critical

### C-1. AD-7 forbids the repricing that FR-38 requires
- **Where:** AD-7 ("Proposals, Orders and Order Lines store that snapshot as it was returned and never recompute it").
- **Problem:** FR-38 and edge row 42 require an Order to be **repriced once** after the lock window: the exchange rate changes, while tier, markup, VAT and delivery stay as captured, and the FR-35 tolerance is re-applied at that moment. Edge row 41 also requires a recompute at confirm. AD-7 forbids recomputing and names no sanctioned path.
- **Divergence:** The Orders epic will either break AD-7 by repricing inside Orders, which duplicates price logic (exactly what AD-7 exists to prevent), or ask Pricing for a fresh quote. A fresh quote would wrongly re-apply the current tier, markup and VAT, breaking FR-38's "only the exchange rate changes".
- **Fix:** Add to AD-7: Pricing exposes `RepriceSnapshot(snapshot, rateVersion)`, which changes only the rate (applying the FR-35 Proposal-Rate rule) and returns a new snapshot version. The Order keeps the original and the repriced snapshot, each stamped with the instant used. This is the only allowed recompute. Order-create confirmation (row 41) compares a quote fingerprint; see H-1.

### C-2. AD-10 forbids the split allocation the edge register requires
- **Where:** AD-10 ("a statement line can confirm at most one Payment", enforced by a unique constraint).
- **Problem:** Edge row 55 says that when one transfer covers two Orders, "Finance splits with allocation records". FR-41 makes the *bank reference* single-use, not the credit single-Payment. Row 54 also routes credits to an unmatched queue that Finance resolves into a Credit Note, which is a non-Payment destination.
- **Divergence:** Built to AD-10, the schema cannot represent one credit paying two Orders. Finance would then work around it with fake Credit Notes, or a later epic would drop the constraint. Either way, the forged-Receipt protection AD-10 exists for becomes inconsistent.
- **Fix:** Rewrite the AD as "a bank credit is allocated at most once in total". Use a `statement_line_allocations` table (statement line to Payment or Credit Note), with a constraint or trigger keeping the allocation sum at or below the credit amount. Keep the unique bank reference on the statement line, and a unique (statement line, Payment) pair. Allocations are append-only with compensating rows, in line with FR-41's "corrections are compensating lines".

---

## High

### H-1. The Order Quote and Project revision contract is missing
- **Where:** AD-7, AD-12, Orders/Projects boundary.
- **Problem:** FR-34 and row 41 need the Order to be bound to the quote the user confirmed: the rate version, tier, Proposal, **project revision**, Back-order terms and stock availability. FR-35 needs "the Project's contents are identical to the Proposal's snapshot", and row 37 needs a stale-Proposal flag. Nothing defines a Project revision, or what the client echoes back at confirm.
- **Divergence:** Projects might bump a revision only on online edits, while sync merges (AD-12) do not. Orders might compare item lists itself. The PWA might send back the whole quote, or nothing. "Project edited since Proposal" would then fire inconsistently.
- **Fix:** Projects owns a monotonic `revision` on the Project, bumped by **every** item or quantity change, including sync merges and Unavailable flagging. Proposals store the revision they were made from. Pricing's quote returns a `quote_id` plus a fingerprint over (rate version, tier, Proposal id and revision, Back-order terms, availability). `POST /orders` takes the `quote_id`. The server recomputes, and on any mismatch returns `409` with the new quote (one convention, matching AD-4.3).

### H-2. The sync change cursor is unspecified and naive cursors lose rows
- **Where:** AD-12 Pull ("Every syncable table carries a change cursor").
- **Problem:** Each owning module (Catalogue, Documentation, Projects) will pick its own cursor. An `updated_at` or plain sequence cursor silently skips rows when a transaction that took an earlier value commits after a device has pulled past it. Deletions and role-filter changes on Catalogue rows (a Product quarantined, a Product moving out of a role's scope) have no tombstone rule; tombstones are defined only for Projects.
- **Divergence:** Three modules, three cursor semantics, one sync engine. Devices permanently miss Catalogue or Project rows with no error.
- **Fix:** Fix one cursor scheme in the AD. Either a bigint from one shared sequence plus a pull filter of `cursor < min in-flight` (using `pg_snapshot_xmin(pg_current_snapshot())` and `xid8`), or a safety lag window with re-pull of overlap. Require tombstones, or "left your scope" markers, for every syncable table, not just Projects.

### H-3. AD-12 sync scope contradicts FR-28 ("the user's own Projects")
- **Where:** AD-12 Scope.
- **Problem:** FR-28's tests need "Offline-created Projects appear on the Business Account's other devices" and "Two Technicians editing different Rooms offline lose no edits". Projects belong to the Business Account (spine ER diagram). "The user's own Projects" would sync only the creator's.
- **Divergence:** The Projects epic (account-scoped) and the sync epic (user-scoped) disagree on which rows a device receives. Collaborative offline editing, the core of UJ-5, breaks.
- **Fix:** Scope = the Projects of the User's Business Account that the role may see (all, for Owner, Manager and Technician per FR-11), price-stripped per AD-13. Also decide edge row 9: the addendum's "hold queued edits for a Manager" conflicts with EXPERIENCE's "can destroy their unsynced changes". Pick one, and if held, say where (a server-side quarantine of pushed ops) and whether a removed User's device may push once before the purge.

### H-4. No maker-checker or segregation-of-duties contract, though seven modules need it
- **Where:** Missing AD. Touches Pricing (rate over 3%), CreditNotes (over NGN 100,000), Orders (Payment reversal, sender mismatch, tolerance change), Catalogue (Active Supplier switch, bulk price change, stock adjustment over the set size, bulk upload pending change and rollback), Fulfilment (serial correction), Returns (whole-Product counterfeit hold, serial exception, "investigator does not decide"), Staff (bank details effective after 24 h).
- **Problem:** EXPERIENCE ("Approve is off for the person who asked, a decline needs a reason, every approval recorded with both names") and FR-52 make this one cross-cutting pattern. The segregation rule ("nobody confirms a payment … for a business they created, approved or vouched for") needs a cross-module fact: which staff touched which Business Account.
- **Divergence:** Each epic invents its own pending-change row, status flag or second-click, with different "requester is not approver" enforcement. The founder's weekly override review and the FR-53 audit get differently shaped data per module.
- **Fix:** Add an AD. Staff owns a `PendingApproval` mechanism (requester, approver, reason, payload reference, expiry), with a DB check that approver ≠ requester. Two-person Actions are split into Request and Approve, and the effect applies only inside the Approve Action. Identity, or Staff, publishes a `StaffRelationshipQuery(staffId, businessAccountId)` that every segregation-gated Action must call. The actor model in AD-4.5 gains `acting_as` and `acting_for` (FR-52, FR-76).

### H-5. No shared work-queue item contract (FR-76, FR-78)
- **Where:** Staff "owns … work queues" (module table). No AD.
- **Problem:** The queue items live in Orders (Receipts), Identity (Level 3, NIN), Fulfilment (approvals), CreditNotes (requests) and Returns. FR-78 needs a uniform count, oldest age, owner, Business-Hours due time, escalation at 75%, on-time rate and arrival alerts. FR-76 needs a deputy reassignment "within a minute" and on deactivation.
- **Divergence:** Five modules model assignment, due time and escalation five ways. Reassignment and on-time reporting cannot work across them without reaching into every module's tables, which AD-2 forbids.
- **Fix:** Add an AD. Staff owns a `work_items` table (queue, polymorphic subject reference, owner, `due_at`, `escalate_at`, `closed_at`, `acting_for`). Modules open and close items only through Staff Actions, inside their own transaction. The AD-5 sweep drives escalation from `escalate_at`.

### H-6. Upload timing and handling are undefined, and Receipt time is money
- **Where:** AD-14, AD-4.4. Rows 44 and 46; FR-38, FR-40, FR-55.
- **Problem:** The lock test classifies a Receipt by the "server-recorded upload start". FR-55 requires resumable uploads. FR-40 requires EXIF stripping, a malware scan, refusal of corrupt or blank files, and HEIC support. AD-14 fixes none of this: not whether bytes go through Laravel or direct to S3 by presigned URL, not where the time stamp comes from, not when a file becomes usable.
- **Divergence:** Receipts, return photos, Documentation and logos each pick a different path. If Receipts go direct-to-S3, the server never sees "upload start". The Price Lock decision then depends on which epic built the upload.
- **Fix:** Add to AD-14. Every upload starts with a `POST` creating an upload intent. That stamps `upload_started_at` (the time row 44 counts), takes the idempotency key, and returns presigned multipart URLs. Completion is a second call. Files sit in a `quarantine/` prefix until a queued scan and normalisation step (malware, EXIF strip, type sniffing, HEIC handling) promotes them. Name the scanner, or list it as an open question (see M-2).

### H-7. Cross-module aggregate locking is undefined: Shipments, reservations, fan-out Actions
- **Where:** AD-4.1 lock roots and lock order.
- **Problem:** AD-4.1 makes the **Order** the root for Shipments (owned by Fulfilment) and reservations (owned by Catalogue). AD-2 forbids touching Orders' tables from those modules, and no public lock primitive exists. The order list also omits roots that Actions will lock: Return Request and Case, statement line, Vouch Code, Proposal, Product (counterfeit hold, Active Supplier switch, Platform Rate). Fan-out Actions (a counterfeit hold flags many Orders, Carts and Proposals, row 29; a rate change; a bank-detail change) would lock many Orders after a Product lock, which inverts the stated order (Order before stock rows). That is a deadlock against Order approval.
- **Divergence:** Fulfilment either skips the Order lock (racing cancel against Shipment progress, rows 51 and 52) or reads Orders' table directly. Returns and Catalogue pick lock orders ad hoc.
- **Fix:** (a) Orders publishes `LockOrderForUpdate(id)`, a public Action returning a read model, as the sanctioned cross-module lock, and AD-2 names it. (b) Complete the order: Business Account, Project, Proposal, Order, Payment or statement line, Return Request or Case, Credit Note, Product or stock rows by id, then the audit chain head last. (c) Rule: a fan-out Action never locks more than one Order. It flips its own flag in one transaction and enqueues one per-entity Action per affected Order, Proposal or Cart.

---

## Medium

### M-1. AD-1's Filament carve-out skips the audit for audited entities
- **Where:** AD-1 ("Filament's built-in model save may only touch fields that are neither money nor state").
- **Problem:** FR-12 says every Product change is audited. Row 67 adds Zone Rates, windows and stock. FR-53 adds bank details and pricing parameters. Non-money, non-state fields on Products, Stores, Zones, Documentation and the business profile still need AD-4.5 audit rows, and Filament's default save writes none.
- **Fix:** Remove the carve-out. All Filament create, edit and delete calls an Action, by overriding `handleRecordCreation` and `handleRecordUpdate` in a module base Resource. Enforce it with an architecture test (Pest arch: no `->save()` outside `Actions/`).

### M-2. Operational envelope gaps that are neither decided nor deferred, and no Open Questions section
- **Where:** AD-15, AD-20, Deferred.
- **Gaps:**
  - **SMS/OTP delivery** for phone sign-up, login and step-up (FR-1, FR-71). Not among AD-15's providers, and login depends on it.
  - **Bot checks** at sign-up (FR-1).
  - **Malware scanning** (FR-40; see H-6).
  - **Rate-limit and cache store** for per-contact, device and IP throttles (FR-1, FR-4, FR-73). No Redis or KV is named, and Laravel Cloud's KV is not in the Stack.
  - **Image derivatives and CDN** (see M-5).
  - **Passkeys/WebAuthn** library (FR-71).
  - **NIN verification daily spend alert** (FR-4).
  - **DSAR export and the 72-hour breach runbook** (NFR-3).
  - The author pass's open items (TypeScript 7 tooling, a second DB role) were not carried into the spine.
- **Fix:** Add SMS/OTP and bot checks to AD-15's provider list and to Deferred, with a decide-by epic. Decide the cache and rate-limit store in AD-20. Add an **Open Questions** section listing the remaining items, each with an owner and a decide-by epic.

### M-3. DR as stated misses NFR-5's RPO outside Laravel Cloud, and DR keys are not escrowed
- **Where:** AD-20, AD-16.
- **Problem:** NFR-5 sets a 1-hour RPO. PITR meets it only while Laravel Cloud is up. The offsite copy is nightly (RPO about 24 h), and the object-storage copy frequency is unstated, so Receipts (evidence of payment) uploaded since the last copy are lost in a provider outage. A restore elsewhere also needs `APP_KEY`, the NIN encryption key and the HMAC key, which AD-16 keeps only in "the host's secret store", which is Laravel Cloud.
- **Fix:** Either accept and record an offsite RPO of 24 h as a deliberate exception, or raise dump and object-sync frequency (hourly object sync at least for Receipts). Require the keys to be escrowed in a second secret store outside Laravel Cloud, and test them in the quarterly drill.

### M-4. NIN deletion versus backups, and personal data in audit diffs
- **Where:** AD-16, AD-21, AD-20. NFR-3, FR-53, row 8, FR-72.
- **Problem:** The full NIN must be deleted 30 days after a manual-review decision, and "its keys … cover backups". With one dedicated key, a deleted NIN lives on in every offsite dump. AD-21's audit rows store "field differences". Diffs of name, address, contact or client details are personal data in an immutable hash chain, so erasure (row 8, FR-72) becomes impossible. FR-53 says entries hold identifiers, not copies of a NIN or full name; AD-21 omits that.
- **Fix:** AD-16: use a per-record data key, wrapped by the dedicated master key. Deleting the wrapped key crypto-shreds the NIN in all backups. The 30-day deletion is a deadline column under AD-5. AD-21: personal-data fields are recorded in diffs as "changed" (or as a keyed hash), never as values. Keep a list of personal-data columns per module.

### M-5. AD-14's short-lived signed URLs for every file defeat caching on slow 3G and offline
- **Where:** AD-14 against FR-55, NFR-7, FR-50, AD-12.
- **Problem:** Product images and Documentation (Level 1 and up) are catalogue media. Short-lived signed URLs change on every fetch, so the service worker and HTTP caches miss and re-download over 400 kbps. Offline copies keyed by URL break. No image derivatives (thumbnails, responsive sizes) are decided, yet the Catalogue list has a 5-second budget.
- **Divergence:** The Catalogue and Documentation epics each improvise caching keys and resizing.
- **Fix:** Split AD-14 in two. **Evidence and private documents** (Receipts, invoices, return photos, Proposals) keep short-lived signed URLs. **Catalogue media** use immutable, content-hashed keys behind a stable URL (a CDN or an authenticated app route), cacheable long-term. The service worker caches by content hash. Images are derived at upload into fixed sizes (for example 320, 640 and 1280 px, WebP).

### M-6. Catalogue sync with prices conflicts with FR-73's "no bulk price listing", and daily rate changes re-sync everything
- **Where:** AD-12 Scope, AD-13, FR-73.
- **Problem:** FR-73 forbids price export or bulk listing and rate-limits price endpoints. AD-12 ships the whole priced Catalogue to every Owner and Manager device, which amounts to a bulk price listing. Prices also depend on the Platform Rate (reconfirmed each Business Day) and the account's tier, so embedding NGN prices in Product rows changes every row's cursor daily: 5,000 rows on slow 3G.
- **Fix:** Decide explicitly. Sync prices as a separate compact "price sheet" per (rate version, tier), keyed by Product id and pulled whole, with a rate limit per account. Or sync prices only for Products in the user's Projects and recently viewed ones. Record the accepted exposure against FR-73, or raise it with the PM as a PRD change.

### M-7. AD-13 covers API and sync only; notifications, PDFs, exports and staff identity are left to each epic
- **Where:** AD-13. EXPERIENCE "Who sees what"; FR-11, FR-30, FR-51; row 66.
- **Problem:** The same field-removal rule must hold for notification text (no amounts to Technicians), Proposal PDFs (no cost, tier or margin, including metadata), and exports and search ordering. Separately, staff names, role names and the BA-, P- and L- references must never reach a business, and reasons must be stored as business-visible or staff-only, never one column. FR-11's "published Technician-safe response schema" with an automated test needs one mechanism to test against.
- **Divergence:** Each module stores a single `reason` or `note` column and serialises it ad hoc. Notifications templates read Query DTOs that contain money.
- **Fix:** Extend AD-13 with a single field-visibility policy registry (field classes such as money, cost, staff-identity, staff-note) that API resources, sync, notification renderers and PDF builders all consult. Text that staff enter is always split into `reason_public` and `note_internal`. One test runs every Technician, Level 1 and Level 2 surface against the registry.

### M-8. Step-up one-time codes have no shared contract
- **Where:** AD-18. FR-71; EXPERIENCE "Trust and verification".
- **Problem:** Step-up is needed by Identity (vouch code, role, Owner, contact, passkey), CreditNotes (refund, apply) and possibly Orders. It must be fresh, valid 10 minutes, for one action only, and finish the action "in the same step".
- **Divergence:** Each module checks something different, such as a session flag, a timestamp or its own code table. A code proven for one action could then be reused for another.
- **Fix:** Identity owns `StepUpGrant` (user, action type, target id, expiry, consumed_at). The gated Action consumes it via Identity's public Action inside its own transaction. The API sends the code with the action request (no separate "unlock" state).

### M-9. Money-math edges: scales, intermediates, credit amounts, delivery VAT, client arithmetic
- **Where:** AD-6, AD-7.
- **Problems:**
  - No scale is fixed for the Platform Rate, percentages (markup, discounts, VAT, floor), tolerances, or USD spend for tiers. The tier spend needs NGN to USD at the stored rate, a division with no stated rounding.
  - Nothing forbids storing a rounded intermediate (for example a USD Wholesale Price at 4 dp) and pricing from it. Test vector 2 (59.259375 USD) shows this gives a different kobo.
  - Credit amounts are computed outside Pricing: shortfall quantity credits (FR-18), the restocking fee on pre-VAT with VAT credited in full (FR-61), line credits, freed-Shipment delivery refunds (row 49). The "VAT component" on a Credit Note (FR-43) has no named owner.
  - Delivery: is the Zone Rate VAT-inclusive? FR-77 needs "delivery VAT" on the invoice, and the AD-7 snapshot has no delivery.
  - Client arithmetic: EXPERIENCE allows the phone to multiply and sum "in whole kobo, never floating point", but AD-6 does not fix the representation (BigInt kobo parsed from the decimal string). The Projects and Cart screens could diverge from server totals.
- **Fix:** Fix scales in AD-6 (for example rate `numeric(12,4)`, percentages `numeric(7,4)`). Add "only inputs are stored; derived amounts are recomputed from inputs at full precision, then rounded once". Assign credit-amount computation to one owner (CreditNotes via the Money type, from the Order Line snapshot). Decide delivery VAT treatment and add delivery to the snapshot. Add one TS money module (BigInt kobo) as the only client arithmetic.

### M-10. Timers: event delivery reliability, notification timing, pausable and calendar-dependent deadlines, client clock
- **Where:** AD-4.6, AD-5. FR-51, FR-68, FR-78; EXPERIENCE "Time and clocks".
- **Problems:**
  - Events are dispatched after commit with no outbox, so a crash between commit and queue push loses money-protecting messages (Payment Unconfirmed, hour 20 and hour 40).
  - The 15-minute WhatsApp-to-email fallback and the "wait until 9 am" quiet hours are timers that AD-5's "no delayed jobs" rule covers only implicitly. The Notifications epic will reach for `->delay()`.
  - The Issue Code clock pauses for up to 7 days during Manager approval, with no representation fixed.
  - Business-Hours `due_at` values are stored, but there is no rule for a later holiday-calendar edit (recompute open deadlines or not).
  - Clients must show "our clock, not the phone's", yet the API exposes no server time and no computed `due_at`, so the PWA might compute Business Hours itself.
- **Fix:**
  - Add a transactional outbox (an events table written in the transaction, relayed by the worker and the sweep).
  - State that notification fallback and quiet-hours sends are deadline columns on the delivery log.
  - Represent a pausable clock as `remaining_seconds` plus `paused_at`.
  - Rule: calendar edits recompute open Business-Hours deadlines (or never do; pick one).
  - Every deadline is sent as a server-computed UTC `*_at`, and responses carry server time so the PWA can correct for clock skew. The PWA never does Business-Hours arithmetic.

### M-11. Statuses beyond the addendum's six entities are undeclared
- **Where:** AD-4.2.
- **Problem:** Transition tables are "as in the PRD addendum's status model", which covers only Order, Shipment, Payment, Credit Note, Return Request and Counterfeit case. Many other entities need states: Case, Level 3 request, NIN verification, Vouch Code, Invitation, Proposal (stale or expired), Product lifecycle, Issue Code, Order Line markers (credited, Supplier-ordered, received), pending approvals, and Business Account (Pending Activation, Hold, closed). Shipment reversals also need a role guard (Admin only, with a reason), and AD-4.2 has no place for one.
- **Fix:** Require each module to publish its transition table (states, moves, allowed actor roles, required evidence) as code in `Domain/StatusTable`. List the entities above as owed by their epics, with names taken from the Glossary.

### M-12. Personal-data erasure and DSAR have no cross-module contract
- **Where:** Missing. FR-72, NFR-3, row 8.
- **Problem:** Closure erases or pseudonymises personal data spread across Identity, Projects (client details), Orders (delivery receivers, sender names), Returns and Notifications logs. No module is told to provide this.
- **Fix:** Each module that holds personal data implements `ErasePersonalData(businessAccountId)` and `ExportPersonalData(...)` Actions, registered with Identity, which orchestrates them. Erasure respects retention holds (NFR-8, deferred), and the audit stays PII-free (M-4).

### M-13. Tax invoice numbering must be gapless; a PostgreSQL sequence is not
- **Where:** AD-9 ("numbers from a sequence owned by CreditNotes").
- **Problem:** FR-77 requires a unique, **sequential** number, and invoices are generally expected to have no gaps. A PG `SEQUENCE` leaves gaps on rollback, and AD-4 Actions roll back on lost races.
- **Fix:** Use a counter row per document series, locked `FOR UPDATE` inside the issuing Action (last in the lock order). Also fix the format and its per-year reset.

---

## Low

- **L-1. PHP "8.3+".** 8.3 left active support at the end of 2025. An open range lets local, staging and production differ. Pin one minor (8.4 or 8.5) to match Laravel Cloud's runtime.
- **L-2. The 14-day lock is "judged on the device".** Rolling the device clock back defeats it, and the lock is the control for removed users' cached data. Store the last-sync *server* time, and lock when the device clock is earlier than that (clock rollback detected) or 14 days past it.
- **L-3. The PDF engine is deferred with no owner.** Projects (Proposal, Parts list) and CreditNotes (invoices) will each pick one. FR-30's metadata scrub depends on the engine. Name a `Shared` `PdfRenderer` contract now and defer only the implementation.
- **L-4. e-invoicing readiness (FR-77) is waved off to "after launch".** Require structured invoice records (lines, VAT, TIN, delivery VAT) as the source of truth, with the PDF as a rendering, so the July 2027 integration is an adapter, not a remodel.
- **L-5. The idempotency store is unspecified.** Without one Shared table (key, actor, operation, request hash, response, TTL; a same key with a different payload returns `422`), modules will scope keys differently.
- **L-6. Audit chain concurrency.** Hash-chaining needs a serialised head. Say that the chain-head lock is taken last (see H-7), or use an async sealer. Audited *reads* (NIN, bank details) happen in Queries, which AD-1 says never write. Name them as Actions, or as an allowed Query side effect.
- **L-7. Proposal overwrite (FR-31: "the platform holds no earlier version")** conflicts with AD-14's never-overwrite rule and with the offsite object copy. State that Proposal PDFs are hard-replaced and excluded from object versioning, or accept that backups retain them.
- **L-8. Staging is "a full copy".** Say whether that is a copy of the stack only. A copy of production data would put client and receiver PII in staging.
- **L-9. Reporting reads raw tables.** It could re-derive Credit Note balances, tier spend or Order status differently from their owners. Require Reporting to use owner-published views for derived values.
- **L-10. Session revocation on removal and deactivation (rows 9, 14; FR-10).** This needs a session store queryable by user (the database driver). State it in AD-18.
