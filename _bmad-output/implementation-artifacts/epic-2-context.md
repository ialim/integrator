# Epic 2 Context: Operations builds the Catalogue and Finance sets the rate

<!-- Compiled from planning artifacts. Edit freely. Regenerate with compile-epic-context if planning docs change. -->

## Goal

Give staff the console screens to load and price the launch Catalogue before the Integrator app exists. Operations loads Products, Suppliers (with an onboarding record, documents and bank details), stock and goods-in, one at a time or by bulk upload. Admin sets Product Range markups and the margin floor. Finance sets and reconfirms the Platform Rate. Every Selling Price is computed to the kobo by one Pricing formula, and every change that needs a second person waits on one Approvals page. The epic also lands kernel parts later epics depend on: the Money type, the timer sweep, the staged upload pipeline, the Approvals mechanism, "acting as", and every Pricing formula with its price snapshot. The snapshot shape is proven here, before anything stores a price.

## Stories

- Story 2.1: Admin sets up Product Ranges and their markup
- Story 2.2: Admin records Suppliers and approves them against an onboarding checklist
- Story 2.3: Operations creates and edits a Product
- Story 2.4: A Product goes live, and leaves
- Story 2.5: Product images
- Story 2.6: A Supplier's agreement and compliance documents
- Story 2.7: Finance sets and reconfirms the Platform Rate
- Story 2.8: A rate change above 3% needs a second person
- Story 2.9: Supplier bank details, with Finance's agreement
- Story 2.10: Selling Prices computed to the kobo
- Story 2.11: The remaining Pricing formulas, proven
- Story 2.12: Admin changes a markup and sees what it will do
- Story 2.13: Stock quantities, thresholds and the ledger
- Story 2.14: Operations records goods-in
- Story 2.15: Back-order terms and the discount rule
- Story 2.16: Admin switches the Active Supplier, or stops a Supplier
- Story 2.17: Bulk upload with a dry run, and a Supplier's product list
- Story 2.18: A bulk price file waits for Admin and can be rolled back
- Story 2.19: The rate goes stale, and Finance can pause orders
- Story 2.20: Supplier documents nearing expiry
- Story 2.21: Acting as another role, and the founder's weekly review

## Requirements & Constraints

- **Price formula.** Selling Price = Supplier Cost × (1 + Platform Markup) × (1 − applicable discount) × (1 + VAT), converted at the Platform Rate. The pre-VAT price is never below Supplier Cost × (1 + margin floor). The floor starts at 8%, and a markup starts at 25% per Product Range. The unit is rounded half-up to the kobo once. A line is unit × quantity, and a total is the sum of its lines.
- **Discounts.** The larger of the Tier and Back-order Discounts applies, and they never stack. A Back-order Discount is capped at 10% unless Admin approves more. A discount of 100% or more is refused, and so is a markup below zero.
- **Platform Rate.** A rate of zero or below is refused. Each version records who, when, old and new values, source and spread, and takes effect at once on displayed prices. A move above 3% needs a second person. A move above 10% is refused unless Admin overrides it with a reason. The rate must be reconfirmed each Business Day. 48 calendar hours after the last reconfirmation, new Order creation stops (warnings at 36 and 44 hours are placeholders). Finance or Admin can pause new orders. Orders are not created yet: Epic 7 reads the stop through one Pricing Query.
- **Role split.** Operations cannot change markups, the floor, the Active Supplier or the rate, and sees those values shown and locked as Admin's. Only Admin approves Suppliers and manages Product Ranges. Finance approves Supplier bank-detail changes. The server refuses every out-of-role attempt; hiding the button is not enough.
- **Two-person changes in this epic:** a rate change above 3%, a VAT rate change, an Active Supplier switch, a bulk price file (more than 20 Products repriced, or any Supplier Cost moved more than 15%), a Supplier's bank details, a stock adjustment above the sign-off size, and a Back-order Discount above 10%.
- **Supplier onboarding.** Approval is gated on a checklist that Admin configures. The three agreement terms (returns, dead-on-arrival, counterfeit chargeback) are always required. "Approve with a reason" is the override. An expiring or expired document warns, but never un-approves a Supplier or takes goods off sale.
- **Supplier data never reaches Integrators.** Supplier documents, contacts, business details, bank details, Supplier Cost, markup and USD amounts are covered by the visibility suite.
- **Accessibility.** Every story states what a keyboard user, a screen-reader user and a 200% zoom user can do. Statuses are given in words, and errors are listed at the top of the form and beside each field.

## Technical Decisions

- **Modules.** Catalogue owns Products, Ranges, Suppliers and their onboarding, documents and bank details, the Active Supplier, sellability, stock, the ledger, goods-in, batches, `serial_units` and media. Pricing owns the rate, markup, floor, VAT, every formula and the price snapshot. Staff owns `PendingApproval`, `work_items` and the audit trail. Modules talk only through public Actions, Queries and Lock actions, and Queries return DTOs. Each module registers its own Filament resources.
- **Actions are the only writers.** Filament create, edit and buttons all call Actions. An Action locks up front in the global order, checks a declared transition table (the Product lifecycle runs draft → active → discontinued, plus a quarantine flag), refuses with `TransitionRefused`, audits inside the transaction, and emits through the outbox. Staff-tunable settings (floor, VAT, thresholds, sign-off size, checklist items) are database rows changed through Actions, never `.env` entries.
- **Money (AD-6/AD-7).** Use the `Shared` Money type: NGN `numeric` scale 2, USD scale 4, rate `numeric(12,4)`, percentages `numeric(7,4)`. Records store inputs and final rounded naira, never intermediates. In JSON, money is `{"amount": "<string>", "currency": ...}`. Only Pricing multiplies, divides or applies a percentage to money, and an architecture test enforces this. Pricing exposes `QuoteLines` (a versioned snapshot plus an Order Quote with `quote_id` and a fingerprint), `RepriceSnapshot`, `SplitVat`, `ApplyIntegratorMargin` and `RestockingFee`. The addendum test vectors are named tests and live in one shared fixture file that the PWA reads later. Pricing also has property tests.
- **Two-person approval (AD-23).** Each change is a Request Action plus an Approve Action, and the effect happens only inside Approve. A database check refuses approver = requester. A pending approval can expire. When two people decide at once, the first decision wins and the second sees who decided.
- **Timers (AD-5).** Deadlines are UTC deadline columns, and reminders are `*_due_at` and `*_sent_at` pairs. A per-minute `onOneServer` sweep calls the same Actions a person would, and each Action re-reads its deadline under lock. There are no delayed jobs. A sweep-tick command runs one sweep at the Clock's time. Each timer is tested before, at and after its deadline, and twice in a row. A missed sweep heartbeat alerts the founder.
- **Uploads (AD-14/AD-15).** Each upload goes through an upload intent (`upload_started_at`, idempotency key), presigned multipart URLs and a completion call. Files wait under a quarantine prefix until a queued step scans them, strips EXIF, sniffs the type and converts HEIC. Supplier documents are private, versioned evidence served through short-lived signed URLs and never overwritten. Product images get WebP derivatives at 320, 640 and 1280 px under content-hashed keys. The malware scanner sits behind a Catalogue interface, and staging and tests use a fake.
- **Supplier bank details (AD-27).** They are encrypted under a dedicated key (never `APP_KEY`) and masked to the last four digits. A full read goes through an audited reveal Action with a reason code. They never appear in logs, queue or outbox payloads, or exports, and audit diffs hold a keyed hash. The founder must provision the key, with escrow and the restore drill, before Story 2.9 ships.
- **Audit (AD-21).** Supplier contact fields are registered as personal-data columns, so the audit records "changed" and never the value. Audit rows carry `acting_as` for role switches.
- **Search** uses PostgreSQL `pg_trgm` over name, brand, model and SKU.
- **Constraints.** Every invariant that a constraint can express is also enforced in the database: a unique SKU, unique normalised serials, non-negative checks, and cross-module foreign keys with `ON DELETE RESTRICT`. Keys are UUIDv7.
- **Code conventions (binding from Epic 2):**
  - No `strict_types`. Remove the two existing declarations in `Shared/Formats` when a story next touches those files.
  - `Clock` comes in by constructor in Actions, Queries, listeners, jobs, commands, middleware and Livewire components. `app(Clock::class)` is allowed only in models, static helpers and Filament static render hooks.
  - `final` by default, models included. Tests swap fakes through the container and never remove `final` to mock a class.
  - Capabilities are declared through `Shared`. The first Epic 2 module that adds a capability moves `CapabilityGroup` to `Shared/Capabilities` and adds the `CapabilityDeclarations` singleton. Modules call `declare()` through `callAfterResolving` in their service provider. Names are module-prefixed (`catalogue.`, `pricing.`), and constants live in the declaring module. Staff's `CapabilityRegistry` stays the only resolver.
  - A Filament version bump follows the upgrade checklist in ADR 0002.

## UX & Interaction Patterns

- A button that cannot be pressed stays visible and dashed, with its reason written beside it ("Make it active", "Approve the supplier", "Reconfirm the rate", Approve for the requester, "Sign off the week"). Each screen has one filled button.
- Checklists ("Before it can go live", "Onboarding checklist") show each item as Clear or Needs action, in words.
- A change that touches many prices (markup, floor, rate, Supplier switch, Supplier stop) shows a preview panel beside Save, not behind a link. The preview shows the prices that move, the overrides left alone, a sample price, that existing Orders are unchanged, and the Products that would breach the floor.
- Every edit needs a reason. Free-text boxes say who will read them. A decline's reason goes back to the requester.
- Lists use filter chips, and Products and Suppliers both have a Needs attention chip. The rate is shown as "₦1,368.00 to the dollar". USD appears only for costs and the rate, always labelled. A Product page shows the Wholesale Price in USD and the base-tier Selling Price in naira with "VAT included".
- The rail shows a count for "Waiting for a second person". An Admin using another role's power first sees "You are acting as Finance" (or Operations).
- Upload rows show the file name, its size, and a meter whose figure is also in words. Scanning progress and the result are announced.

## Cross-Story Dependencies

- 2.1 (Ranges, floor) comes before 2.3, 2.4, 2.10 and 2.12. 2.2 (Suppliers) comes before 2.4 (only an approved Supplier can be Active), 2.6, 2.9, 2.16 and 2.17.
- Kernel stories come before the stories that reuse them. 2.5 (upload pipeline) comes before 2.6. 2.8 (Approvals) comes before 2.9, 2.10 (VAT change), 2.13, 2.15, 2.16 and 2.18. 2.19 (sweep) comes before 2.20.
- 2.7 refuses 3–10% rate moves until 2.8 adds the approval route. 2.17 hands over-threshold files to 2.18. 2.10 comes before 2.11, 2.12, 2.15 and 2.16.
- Epic 1 supplies the Action base, lock registry, transition helper, outbox, audit, Clock, UUIDv7, staff roles and capability registry, and the console theme.
- Later epics consume this one. Epic 7 reads the order-stop Query and stores snapshots. Epic 11 sends the bank-detail emails. Epic 4's Business Hours references the Platform Rate page (2.19). Later epics add their override types to the weekly review (2.21). The shared pricing fixture is read by the app's money tests from Story 6.5.
