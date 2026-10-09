---
id: SPEC-integrator
companions:
  - ../../planning-artifacts/prds/prd-integrator-2026-09-27/prd.md
  - ../../planning-artifacts/prds/prd-integrator-2026-09-27/addendum.md
  - ../../planning-artifacts/ux-designs/ux-integrator-2026-10-07/prd-change-requests.md
  - ../../planning-artifacts/ux-designs/ux-integrator-2026-10-07/EXPERIENCE.md
  - ../../planning-artifacts/ux-designs/ux-integrator-2026-10-07/DESIGN.md
  - ../../planning-artifacts/architecture/architecture-integrator-2026-10-09/ARCHITECTURE-SPINE.md
sources:
  - ../../planning-artifacts/briefs/brief-integrator-2026-09-27/brief.md
---

> **Canonical contract.** This SPEC and the files in `companions:` are the complete, preservation-validated contract for what to build, test, and validate. Source documents listed in frontmatter are for traceability — consult them only if you need narrative rationale or prose color this contract intentionally omits.

# integrator v1

*Working title.* Companion roles: `prd.md` holds every FR (with testable consequences), the Glossary and NFR-1 to NFR-10; `addendum.md` holds the status model, edge-case register and pricing test vectors; `prd-change-requests.md` holds UX positions that override the PRD; `EXPERIENCE.md` and `DESIGN.md` hold behaviour and look; `ARCHITECTURE-SPINE.md` holds binding decisions AD-1 to AD-26.

## Why

A **vision with an opportunity behind it**: a systems integrator builds a platform for systems integrators in Nigeria. Today an integrator stitches each job across spreadsheets, supplier calls, hand-calculated markups and whatever guide came in the box, while dollar-linked prices move under them. integrator gives one flow from first room to delivered equipment: design a job room by room against live wholesale prices, send the client a Proposal with the integrator's own margin, order everything at a price that does not move without warning, pay by bank transfer, and install with the right guide at hand, even offline. Revenue is the markup on goods only. It succeeds if integrators come back to buy again; the founder's supplier and integrator network is the cold-start advantage.

## Capabilities

- **CAP-1** — Tiered access and teams (FR-1–11, FR-69–72)
  - **intent:** A business earns access in three Levels (verified contact; NIN-verified profile; vouched by an Established Integrator and approved by Admin), and its Owner runs a team of Managers and Technicians, with Terms acceptance, recovery, Holds and closure.
  - **success:** An account reaches Level 3 only after NIN verification, a valid unexpired Vouch Code and Admin approval naming the voucher; before Level 3 no surface returns a Selling Price; a definitive NIN mismatch routes every later NIN check for that account to manual review.
- **CAP-2** — Catalogue, Suppliers and stock (FR-12–18, FR-73, FR-74)
  - **intent:** Operations keeps a curated Catalogue of IT, AV, security and home-automation Products with USD Supplier Costs, one Active Supplier each, stock against thresholds, back-order, bulk upload, search and a goods-in record.
  - **success:** A launch file of 500+ rows imports in one upload with each failed row reported by number and reason; an Out of Stock Product with back-order enabled is orderable with its discount and Lead Time, and without it is shown disabled with its reason.
- **CAP-3** — Pricing, Platform Rate and loyalty (FR-19–24)
  - **intent:** Every Selling Price derives from USD cost, Platform Markup, one guarded Platform Rate, Tier and Back-order Discounts and VAT, with Loyalty Tiers earned on trailing six-month spend.
  - **success:** The addendum's test vectors reproduce to the kobo; a rate change above 3% needs a second approver; a rate older than 48 hours blocks Order creation.
- **CAP-4** — Project Designer (FR-25–28)
  - **intent:** An Owner, Manager or Technician builds a job room by room as a Parts List, online or offline; Technicians design without prices.
  - **success:** Two Technicians editing different Rooms offline lose no edits after sync; offline-created Projects appear on the Business Account's other devices; no Technician payload contains a money field.
- **CAP-5** — Proposals (FR-29–32)
  - **intent:** An Owner or Manager turns a saved Project into a client-ready Proposal PDF with one Integrator Margin and custom lines.
  - **success:** The PDF carries no cost, tier or margin in its text or hidden data; after regeneration only the latest Proposal exists; generation is refused offline.
- **CAP-6** — Cart and Orders (FR-33–39, FR-57, FR-75)
  - **intent:** An Owner or Manager orders a whole Project or a Cart at exactly the confirmed price, sees what changed since the Proposal (rate, tier, items, delivery), opts in to back-orders, chooses delivery or pick-up and shipping in parts or in full, and can cancel or amend before Approval.
  - **success:** FR-38's vectors hold: a Receipt uploaded at hour 20 and confirmed at hour 30 keeps its price; USD 500 at NGN 1,330 reprices to NGN 752,500 when the remainder lands at hour 30 at NGN 1,400; an Order with no Receipt expires at hour 48.
- **CAP-7** — Payments by bank transfer (FR-40–42)
  - **intent:** The Integrator pays by bank transfer and uploads a Receipt per transfer; Finance confirms each against the platform's bank statement, with partial payments and an audited underpayment tolerance.
  - **success:** A Payment is confirmed only against a bank credit not already allocated; an Order becomes Paid when confirmed Payments cover the Amount Due; 95% of Receipts arriving inside Business Hours are confirmed within two Business Hours.
- **CAP-8** — Credit Notes and tax documents (FR-43, FR-77)
  - **intent:** The platform holds money only as Credit Notes (overpayment, stock-out, cancellation, approved return), applied or refunded by Finance, and issues a tax invoice per Paid Order and a document per Credit Note.
  - **success:** Concurrent applications never spend more than a Credit Note's balance; each Paid Order has exactly one tax invoice; the monthly VAT export reconciles to the documents issued.
- **CAP-9** — Fulfilment and tracking (FR-44–47, FR-56, FR-65)
  - **intent:** Operations approves Paid Orders and fulfils them in Shipments with tracked statuses and exceptions, recording serial numbers at dispatch, with Delivery Zones, Zone Rates and Stores.
  - **success:** Each Shipment reaches Delivered, Signed For or an exception status independently; every serialised unit dispatched has its serial recorded; the zone is found from State and local government area, never free text.
- **CAP-10** — Installation Documentation (FR-48–50)
  - **intent:** Every Product can carry a written guide, a manual PDF and a video, reachable from the Product, Projects and Completed Orders, and saved to the phone for offline use.
  - **success:** A Technician opens a saved Project's guide and manual with no signal; a Product without Documentation says "Documentation coming soon"; coverage of Products ordered in the trailing 90 days reaches 80% by month 6.
- **CAP-11** — Notifications (FR-51)
  - **intent:** The platform notifies Integrators and Staff by email and WhatsApp for the frozen launch set of 12 events.
  - **success:** Each launch event reaches its recipient roles once per channel; no Technician message states an amount.
- **CAP-12** — Staff console, queues and audit (FR-52, FR-53, FR-76, FR-78)
  - **intent:** Admin, Finance and Operations (with Support duties) work from queues with targets, escalation, deputies and two-person approvals, over an append-only audit trail.
  - **success:** Each queue shows count, oldest age and owner; an item past 75% of its target escalates to Admin; an out-of-office switch moves a queue within a minute; every money, stock, access, identity and pricing change has an audit entry.
- **CAP-13** — Installable, low-bandwidth app (FR-54, FR-55)
  - **intent:** Integrators use one installable web app on phone and desktop that stays usable on slow, patchy data.
  - **success:** The installed app opens without a browser address bar on Android 9+ Chrome and iOS 16.4+; the Catalogue list loads within about 5 seconds on slow 3G (400 kbps, 400 ms) on a 2 GB Android phone; an interrupted Receipt upload resumes without re-entering data.
- **CAP-14** — Returns and warranty (FR-58–68, FR-79)
  - **intent:** Faults go Support-first through a Case and, if unresolved, an Issue Code and Return Request approved by the business, with remedies decided by Admin, change-of-mind returns, non-delivery claims and a counterfeit procedure.
  - **success:** An Issue Code works once for its Business Account and serials and cannot be generated for a serial with no dispatch record without an approved exception; a Return Request whose device misses Store or courier handover within 48 hours (excluding the approval pause) moves to Lapsed.
- **CAP-15** — Reporting and finance exports (FR-80)
  - **intent:** The founder sees every success measure, and Finance runs monthly work from platform exports instead of spreadsheets.
  - **success:** SM-C1 is computable for any Completed Order from its Order Lines alone; CSV exports cover Orders, Payments, Credit Note ageing, monthly VAT output and bank reconciliation.

## Constraints

- Nigeria only; Products priced in USD and shown in naira at one Platform Rate set by Finance.
- Payment is manual bank transfer confirmed by people against the bank statement: no gateway, no wallet, no trade credit; a Credit Note is the only balance the platform holds.
- Selling Prices and ordering only for Level 3 Business Accounts; Technicians and Level 1–2 users never receive a money field; Integrators never see Supplier Cost, Platform Markup or USD amounts; no bulk price listing reaches a device (FR-73).
- Installable web app only; offline covers browsing the last-synced Catalogue, Documentation and Project editing; Proposals, Carts, Orders, Receipts and Credit Notes need a connection and current prices.
- Money uses decimal arithmetic with one rounding rule, and every priced artifact stores the pricing inputs it used (NFR-4).
- Every money-affecting transition is atomic and idempotent, and the first valid of two racing actions wins (NFR-10).
- NIN is stored as provider reference, keyed hash and last four digits; the full NIN only during manual review, deleted 30 days after the decision (NFR-3).
- 99.5% monthly availability; recovery point 1 hour, recovery time 4 hours; quarterly restore drill (NFR-5).
- Timers stored in UTC, shown in West Africa Time, idempotent, alerting the founder on a miss (NFR-9).
- Designed for 500 Integrators, 5,000 Products and 500 Orders a month; launch at 10, 500+ and about 15 (NFR-1).
- Build order is Priority 1 (first Order), 2 (launch week), 3 (returns workflow about three months after launch); returns run manually until then.
- Where the PRD and `prd-change-requests.md` disagree, a settled UX position (Added, Changed, Filled) wins until the PRD is updated.
- `ARCHITECTURE-SPINE.md` decisions AD-1 to AD-26 are binding on every epic and story.
- Return, warranty, cancellation and Credit Note terms ship in counsel-vetted Terms without "no refunds" wording.

## Non-goals

- Supplier accounts, supplier self-onboarding, purchase orders or supplier-chargeback workflow beyond the goods-in record.
- End-client accounts, B2C, or hiring an integrator through the platform.
- Payment gateway, spendable wallet, per-Integrator virtual accounts, financing or trade credit.
- Native app-store apps; AR or on-site measurement tooling.
- Platform fee, commission or subscription.
- Bundled "solutions"; per-Room or per-line Integrator Margin; labour pricing; partial ordering of a Project by Room.
- Industrial automation and complex commercial projects; markets beyond Nigeria.
- Weight-based delivery rates, automated courier tracking, supplier-facing reporting; Admin override of repricing between hours 24 and 48.

## Success signal

- By month 12, 60% or more of onboarded Business Accounts place a second Completed Order within 90 days of their first (SM-1), while realized markup on Completed Orders stays at 22% or more (SM-C1).
- A demonstration of UJ-1 end to end: a Level 3 Owner designs a Project, sends a Proposal, orders it, pays by transfer, has the Receipt confirmed within two Business Hours, and follows each Shipment to Delivered, with the Order counted toward the Loyalty Tier.

## Assumptions

- Settled UX positions in `prd-change-requests.md` are correct readings of the founder's intent; the PRD will be updated to match.
- PRD success targets (SM-1 to SM-C5) are the founder's working figures, still marked `[ASSUMPTION]` in the PRD.
- When Admin edits the holiday calendar, open Business-Hours deadlines are recomputed (architecture choice, reversible).

## Open Questions

- **Founder (from UX):** wrong-code and resend caps (FR-1); re-requesting a rejected Level 3 (FR-6, FR-7); queues and targets for recovery, cancel and change requests, delivery-price requests and call-backs (FR-71, FR-78); closing an account that holds a credit-only balance (FR-72); which units allow fractional quantities (FR-26); per-phone Documentation version tracking (FR-50); baselines for unusual-pattern alerts (FR-53); staff two-step sign-in method (FR-71); dashboard plan start date and SM-6 cost inputs (FR-80); Supplier order entity for back-orders (FR-17, FR-74); zone coverage checks, the initial local-government-area list per zone and real Zone Rates (FR-47, FR-56); who manages Product Ranges.
- **Founder (from PRD §8.3):** whether flat Zone Rates need weight bands at launch; whether a second Store changes Zone Rates; whether Suppliers can meet Lead Times when ordered only after payment; real breakeven inputs and inventory-float funding; whether far zones need longer than 48 hours for courier handover.
- **Accountant:** VAT registration, tax-invoice and credit-note fields and numbering, serials on the invoice or a delivery note, VAT on delivery and Credit Notes, e-invoicing timing, financial-record retention.
- **Counsel:** Credit Note ledger as deposit-taking, expiry and refund terms, returns terms, consumer-law exposure, NDPA obligations including the transfer basis for London hosting and Cloudflare R2 storage, NCC and SONCAP for listed Products, the nine wording items in `EXPERIENCE.md`.
- **Builder:** whether Laravel Cloud Serverless Postgres allows a least-privilege application role (AD-21), before the Staff epic.
