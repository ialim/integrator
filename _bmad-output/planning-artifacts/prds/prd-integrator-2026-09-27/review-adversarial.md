---
title: Adversarial review of the integrator PRD
reviewed: prd.md (draft, updated 2026-10-05) and addendum.md (draft, 2026-10-05)
lens: .claude/skills/bmad-review/references/lens-adversarial.md
review_date: 2026-10-07
---

# Adversarial review: integrator PRD

Findings are listed in the order of the PRD sections they attach to. The order is not a ranking and no severity or priority is assigned. Line references are to `prd.md` unless marked `addendum.md`. Where a finding touches a Section 8 open question it says what it adds beyond the question. Figures marked "my arithmetic" were recomputed from the PRD's own numbers.

Each finding has the four canonical fields: `location`, `trigger_condition`, `guard_snippet`, `potential_consequence`. A coverage map at the end shows which of the requested focus areas each finding serves.

## Findings

### F-01: Level 3 does not protect wholesale prices; Level 2 shows them to anyone with a NIN

- **location:** prd.md section 2.2 (L38); FR-3 (L172-177); Constraints "Trust" (L750); addendum.md "Level 3 verification" (L31)
- **trigger_condition:** Section 2.2 says the Level 3 vouching gate keeps retail and non-integrator buyers out of wholesale pricing, but FR-3 shows every Selling Price to any account that passes a NIN check at Level 2, before any vouch or trade evidence.
- **guard_snippet:** Pick one and write it down. Either hide prices until Level 3 (Level 2 sees the Catalogue and "price on approval"), or keep Level 2 prices and rewrite section 2.2, Constraints and the addendum to say the gate protects ordering only. In both cases add: per-account and per-IP rate limits on every price-bearing endpoint, no price export or bulk listing API, bot detection on sign-up, and a rule that a Level 2 account with no Level 3 request after 30 days loses price visibility.
- **potential_consequence:** An integrator's own end client or a rival reseller registers with a NIN, reads every wholesale price and shops the integrator's quote against the platform. The channel promise (protected integrator margin) fails and scraping is trivial.

### F-02: "A fake buyer cannot cost the platform money" is false by the PRD's own design

- **location:** addendum.md "Level 3 verification" (L31); prd.md FR-43 (L518); FR-61 (L698); FR-62 (L705); FR-66 (L729); Risks "Vouching abuse" (L758)
- **trigger_condition:** The trust model rests on prepayment, yet the PRD adds money-out paths a vouched fake or bad-faith buyer can use: bank refunds of Credit Notes, cash refunds for "counterfeit" or "non-delivery", freight reimbursement, replacements from stock and change-of-mind Credit Notes.
- **guard_snippet:** Replace the premise with an explicit money-out threat list and add controls: cash and Credit Note refunds only to a sender account recorded on a confirmed Payment of that Business Account; no cash refund within 14 days of Level 3 approval; cumulative returns, claims and refunds per Business Account shown to the deciding staff member at decision time; a second approver on any single refund above a set amount; every return decision displays who vouched for the account.
- **potential_consequence:** Fraud economics invert: pay once with a convincing receipt, then extract the money or the goods back through claims. At about USD 80 contribution per USD 500 Order, one bad refund erases several Orders of profit.

### F-03: UJ-6 promises what the staffing model cannot deliver; Finance and Admin are single points of failure

- **location:** prd.md UJ-6 (L83-89, resolution at L88); Glossary "Business Hours" (L148); NFR-2 (L740); SM-C4 (L819); Risks "Finance bottleneck" (L757); addendum.md staff cost row (L63). Touches OQ11 (L835); adds the points below.
- **trigger_condition:** UJ-6 ends with "No Order is waiting on a person outside Business Hours" while FR-38 clocks run around the clock, Business Hours is 60 scheduled hours a week with no timezone or holiday calendar, each role is one named person on roughly USD 450 a month for all three, and the Finance-bottleneck "mitigation" (lock at upload, two-hour target) puts nobody in the chair.
- **guard_snippet:** Delete the UJ-6 resolution and replace it with a measurable one ("a Receipt uploaded at any time is confirmed within two Business Hours of the next opening"). Define Business Hours in WAT with an Admin-maintained holiday calendar. Add a coverage requirement: every staff role has a named deputy, an out-of-office switch moves its queue to the deputy with an "acting for" audit flag, and the breakeven staffing line is rebuilt for 60 scheduled hours a week (about 1.5 people per role) or Business Hours are shortened.
- **potential_consequence:** One sick day or a weekday public holiday breaks the confirmation SLA and stalls reservation, approval and shipping for paid Orders. At about 15 to 25 Payments a month a single late confirmation is already 4% to 7% of volume, so SM-C4's 95% target fails in the first month.

### F-04: Only Finance has an SLA, and no requirement tells any staff member that work is waiting

- **location:** prd.md FR-4 (L179); FR-7 (L200); FR-43 (L518, "ask Finance to apply"); FR-44 (L531); FR-60 (L691); FR-51 (L599, notifies Integrators only); NFR-2 (L740); section 4.11 ("staff console" is defined only by a permissions table)
- **trigger_condition:** NIN manual reviews, Level 3 approvals, Order approvals, Credit Note application requests and return investigations are all human queues with no target time, no arrival alert to staff, no ageing view, no escalation and no definition of the console beyond who may do what.
- **guard_snippet:** Add a Staff Work Queue requirement: each queue (Receipts, Level 3 requests, NIN reviews, Order approvals, Credit Note requests, returns) shows count, oldest item age and owner; staff are alerted on arrival by email and WhatsApp; items escalate to Admin at 75% of target. Targets: Receipts 2 Business Hours, Level 3 and NIN review 1 Business Day, Order approval 4 Business Hours, Credit Note application 1 Business Day. On-time rate per queue is reported.
- **potential_consequence:** The first cohort stalls on one Admin, Paid Orders sit unapproved unnoticed, and the two-hour promise is found broken only when an Integrator complains.

### F-05: The Glossary the document says to use "verbatim" contradicts the requirements in six places

- **location:** prd.md section 0 (L13); Glossary (L99-149); UJ-1 (L47); FR-38 (L467); FR-43 (L518); FR-45 (L538); FR-61 (L698); SM-2 (L810); addendum.md "Status naming" (L41-43)
- **trigger_condition:** The same state has two names (Delivered Order vs Completed); UJ-1 moves the Order to "Delivered or Signed For" although only Shipments carry those statuses; Price Lock is defined as a guarantee from creation but FR-38 makes it conditional on timely payment; the Glossary lists four Credit Note reasons, FR-43 allows two and FR-41, FR-57, FR-61, FR-62 and FR-66 add more; Return Request is defined for "a delivered Order Line" yet FR-61 refunds non-delivery; "onboarded" (SM-2) is undefined and the addendum status list omits Cancelled.
- **guard_snippet:** One term per state. Order statuses: Awaiting Payment, Paid, Approved, Completed (all Shipments Delivered or Signed For), Expired, Cancelled; define "Delivered Order" as "a Completed Order" or remove it. Define Price Lock as "the price captured at creation, kept only if the FR-38 payment conditions are met". One Credit Note reason list shared by the Glossary and every FR. Reword UJ-1 to Shipment statuses. Add "Onboarded" and "Case" to the Glossary. Update the addendum status list.
- **potential_consequence:** UX labels, API enums and test cases diverge across teams, and "Delivered" ends up meaning three things across loyalty, vouching and warranty.

### F-06: No authentication, session, recovery or membership model for accounts that move money

- **location:** prd.md FR-1 (L159-164); FR-10 (L219-224); FR-11 (L226-244); FR-43 (L518); FR-52 (L611)
- **trigger_condition:** Identity is "a verified email or phone", but the PRD never defines the login method, session life, step-up before money actions, recovery when an Owner loses a phone or SIM, whether one person can belong to several Business Accounts (freelance technicians), whether a Manager can remove or demote the Owner (FR-10 lets Managers "remove Users" and the Owner is a User), or staff MFA and offboarding.
- **guard_snippet:** Add authentication requirements: OTP or passkey login; fresh OTP (step-up) before refund requests, Vouch Code generation, role changes and bank-detail view; sessions 30 days idle for Integrators and 12 hours for staff; staff MFA mandatory and staff disabled on departure with an audit entry; "Manage Users" cannot act on the Owner or on another Manager (Owner only); Owner recovery handled by Admin with an ID check and a 72-hour cooling period notified to every contact on record; a person may hold memberships in several Business Accounts with a role per membership and per-membership data and cache scope.
- **potential_consequence:** A SIM swap hands an attacker an Owner's account and refund rights, a rogue Manager can lock the Owner out, and the architect designs identity and tenancy by guesswork.

### F-07: "NIN verified" does not say what was verified, and the one-attempt rule is exploitable and brittle

- **location:** prd.md FR-3 (L172); FR-4 (L179-184); NFR-3 (L741); addendum.md "Integrations" (L19)
- **trigger_condition:** "Successful verification" is never defined (NIN exists, name match, date-of-birth match, selfie), nothing ties a NIN to one Business Account, any provider timeout counts as the single failed attempt that makes the account manual for ever, each free sign-up triggers a paid lookup, and legal residents without a NIN have no route.
- **guard_snippet:** FR-4: pass = NIN exists AND registered name matches the profile name (normalised) AND date of birth matches, with selfie match where the provider offers it; fail only on a definitive mismatch; provider errors retry with backoff up to 3 times over 24 hours without consuming the attempt; store a keyed hash of the NIN and allow it on one Business Account only; throttle lookups per contact, device and IP and alert when daily verification spend passes a budget; add an Admin-reviewed alternative (passport or residence permit plus CAC number) for owners without a NIN.
- **potential_consequence:** Anyone with a stranger's NIN and name passes; a suspended person re-registers with a new email; a provider outage drops every sign-up into Admin's manual queue permanently; bots burn the lookup budget and use the form as a NIN-and-name oracle.

### F-08: Vouching can be minted by unverified Managers, qualifies on trivial orders and leaves no accountable lineage

- **location:** prd.md FR-5 (L186-192); FR-6 (L194); FR-7 (L200-205); FR-8 (L207); FR-9 (L213); Glossary "Established Integrator" (L110); Risks (L758)
- **trigger_condition:** Any Manager (a person with only a verified contact) of an account with three Delivered Orders of any value can issue two codes every 48 hours indefinitely; the voucher-to-vouchee link is not required to be stored, shown or acted on; code binding, length, guess limits and the fate of a request already queued when the voucher is suspended are unspecified; Admin approval has no criteria.
- **guard_snippet:** FR-5: only the Owner generates codes (a Manager may request one) with step-up auth; at most 6 vouchees per rolling 90 days; Established requires 3 Delivered Orders each worth at least USD 100 (setting) with no open Return Request; a code is 10 or more random characters, bound to the named prospect's name and phone or email, redeemable only by a profile whose verified NIN name matches, locked after 5 wrong attempts. FR-6 and FR-7: persist `vouched_by`; the Admin screen shows the voucher, its history, its open claims and a mandatory checklist (online presence, proof of an installed job, a phone call) with a reason on every decision; a vouchee suspended for fraud auto-suspends the voucher pending review; a queued request survives voucher suspension only after Admin re-confirms.
- **potential_consequence:** A departed Manager or a bought code brings in resellers, colluding accounts self-certify in a ring, nobody can trace who introduced a fraudster, and Admin approvals become unexplainable favours.

### F-09: There is no way to suspend a Business Account, revoke Level 3 or block a returning fraudster

- **location:** prd.md FR-7 (L200); FR-9 (L213); FR-10 (L219); FR-52 (L623); Risks (L758)
- **trigger_condition:** Admin can approve Level 3 and suspend vouching rights, but no requirement lets anyone freeze an account, revoke Level 3, force a User out, hold Orders, or block a NIN, phone, email or sending bank account from signing up again.
- **guard_snippet:** Add an FR: Admin (or Finance for payment fraud, ratified by Admin within one Business Day) can put a Business Account on Hold (no new Orders or refunds, Payments still processed), revoke Level 3, disable a User, and add a NIN hash, contact or sender bank account to a blocklist checked at sign-up and at Payment confirmation. Each action needs a reason, is audited and notifies the Owner unless Admin flags a fraud review.
- **potential_consequence:** The first fake-receipt or returns fraudster keeps ordering and re-registers under a new email while staff can only "suspend vouching".

### F-10: The Technician and Level 1 leak contract is narrower than the Constraint, FR-3 contradicts FR-11, and the role matrix is incomplete

- **location:** prd.md FR-3 (L173); FR-11 (L226-244); FR-19 (L311); FR-27 (L373); Constraints "Pricing" (L749); FR-28 (L379); FR-51 (L599)
- **trigger_condition:** FR-3 says "Users" of a Level 2 account see Selling Prices (which includes Technicians); the FR-11 test blocks only five data types (not Selling Price, line prices, Credit Notes, delivery cost, Zone Rates, tier spend or Back-order Discount) although the Constraint bans anything revealing "cost, markup, or margin"; price-derived surfaces (sort and filter by price, search ranking, offline cache, exports, notification text) are not covered; and the matrix omits Vouch Codes, Cart, Order cancellation, Credit Notes, loyalty tier and the Level 3 request.
- **guard_snippet:** FR-11: one matrix row per FR action; replace the denylist test with an allow-list contract (a Technician response may contain only fields in a published "technician-safe" schema) plus an automated test that fails when any money field or price-derived ordering is reachable by a Technician or Level 1 user; FR-3: prices shown to Owner and Manager only; offline cache keys and notification templates are role-specific.
- **potential_consequence:** A Technician or stranger reconstructs cost from sort order, exports or cached payloads while every listed acceptance test passes, and UX and API teams implement different Technician views.

### F-11: Nothing requires capturing acceptance of the Terms or a privacy notice, yet returns, fees and Credit Note expiry depend on them

- **location:** prd.md FR-63 (L711-715); Constraints "Terms" (L751); FR-8 (L207); FR-10 (L219); FR-51 (L599); addendum.md NDPA note (L19)
- **trigger_condition:** Return, warranty, cancellation and Credit Note terms are "accepted at sign-up" and WhatsApp messaging is planned, but no FR presents the Terms and a privacy notice, versions and records acceptance, collects WhatsApp opt-in, forces re-acceptance on change, or covers Admin-seeded accounts and invited Users, who never go through sign-up.
- **guard_snippet:** Add an FR: sign-up and Level 3 approval require acceptance of the current Terms and Privacy Notice (version, timestamp, user id, IP); Admin-seeded accounts accept at first login before any Order; a new Terms version blocks Orders until the Owner re-accepts; WhatsApp requires an explicit per-number opt-in and an opt-out; invitees accept the Privacy Notice at first login; the notice also covers third-party data Integrators enter (client details on Proposals, delivery receivers); acceptance records are kept for the life of the account plus the legal retention period.
- **potential_consequence:** The 15% restocking fee, exclusions and 12-month Credit Note expiry cannot be enforced against anyone who never accepted them, and WhatsApp sends without opt-in invite number blocking and NDPA complaints.

### F-12: Product master data lacks the fields later FRs rely on, and bulk upload has no key

- **location:** prd.md FR-12 (L252-257); FR-15 (L274-279); FR-17 (L287); FR-59 (L684); FR-63 (L711); FR-64 (L717); FR-65 (L723); FR-66 (L729); Glossary "Product" (L112)
- **trigger_condition:** FR-12's field list has no SKU or manufacturer part number, brand, unit of measure or pack size, weight and dimensions, serialised flag, Warranty Period, dead-on-arrival window, returnable flag, compliance reference or lifecycle status, all of which later FRs assume, and FR-15 promises "create and update" with no unique key to match rows.
- **guard_snippet:** FR-12: define the Product record: `sku` (unique, immutable), `brand`, `model`, `uom` (each, metre, box of N), `pack_size`, `weight_kg`, `dimensions`, `serialised`, `warranty_months`, `doa_window_days`, `returnable`, `compliance_ref` (NCC type approval or SONCAP where applicable), `lifecycle` (draft, active, discontinued), `quarantined`, plus Back-order Discount from FR-17. FR-15: update key is `sku`; a downloadable template and failed-row report; discontinued Products stay on existing Projects with a warning.
- **potential_consequence:** Every re-import duplicates Products, cable sold by the metre or box cannot be quantified, warranty, returns and counterfeit stories each invent their own fields, and a radio or camera without NCC or SONCAP approval can be listed with no gate.

### F-13: Catalogue discovery is one sentence ("browse") for a catalogue that grows from 500 to 4,500 Products

- **location:** prd.md FR-2 (L166); FR-26 (L367); UJ-1 (L46); SM-5 (L813)
- **trigger_condition:** There is no requirement for search by model, SKU or brand, categories, filters, sorting, availability filtering, product page content or compatibility help, although UJ-1 has the integrator choose Products "to meet the room's needs".
- **guard_snippet:** Add an FR: search by name, brand, model and SKU with typo tolerance; category tree; filters for brand, Product Range and stock status; sort by name and, for Owner and Manager only, by price; product page contents (images, specs, Documentation, stock status, Lead Time); search works offline on the synced Catalogue; define empty and no-result states.
- **potential_consequence:** UX invents discovery from nothing, an integrator who knows the model number cannot find it, and catalogue growth (SM-5) outruns findability.

### F-14: Stock arithmetic is undefined: "quantity", thresholds, partial fills, physical shortages, "batch" and "ordered from a Supplier" have no data behind them

- **location:** prd.md FR-16 (L281-285); FR-18 (L294-298); FR-44 (L531); FR-57 (L484); FR-66 (L729); UJ-4 (L67-73)
- **trigger_condition:** Stock is a hand-edited number with no definition of available (on hand minus reserved) versus physical, no inbound or adjustment ledger, no rule for a partial fill (stock 6, line 10) or a shortage found at Operations' stock check; units at or below the Stock Threshold are Out of Stock even when physically on the shelf (unsellable unless back-order is on, then sold at a discount with a Lead Time); and FR-57 and FR-66 rely on "already ordered from a Supplier" and "batch", neither of which exists as data.
- **guard_snippet:** Section 4.2: `available = on_hand - reserved`; a threshold is a reorder signal and does not make on-hand units unsellable unless Admin sets "hold safety stock"; every stock change is a ledger entry (receipt, reservation, release, adjustment, quarantine) with reason and reference; an inbound receiving record (Supplier, date, quantity, batch or lot) is the source of "batch"; Back-order lines carry `supplier_ordered_at` set by Operations. Partial fill rule: reserve what exists, offer conversion to Back-order where enabled, otherwise Credit Note the rest and let the Integrator cancel a whole-Project Order within 4 Business Hours without fee.
- **potential_consequence:** Stock drifts from the shelf with no trail, shelf units are sold at a discount as "back-order", whole-Project Orders are silently half-filled, and a counterfeit batch cannot be traced to its Supplier or buyers.

### F-15: Operations can price below cost without touching the Admin-only markup control, and bulk upload applies price changes with no diff or approval

- **location:** prd.md FR-12 (L257); FR-13 (L259-265); FR-14 (L267); FR-15 (L274-279); FR-17 (L287); FR-23 (L342); FR-52 (L618-622)
- **trigger_condition:** Only Admin may edit Platform Markup, but Operations sets Supplier Cost, Back-order Discount (any percentage) and Stock Thresholds, and a 500-row spreadsheet applies valid rows immediately. At the default 25% markup a Back-order Discount of 20% sells at cost (1.25 x 0.80 = 1.00), and the 6% Tier 4 discount sells below cost on any Product whose markup override is under 6.4% (my arithmetic).
- **guard_snippet:** Add an Admin-set margin floor (for example 8% over Supplier Cost) enforced at price computation and at save: no discount combination may take the pre-VAT Selling Price under Supplier Cost x (1 + floor). A Back-order Discount above 10% needs Admin approval. A Supplier Cost change above 15%, or any bulk change touching more than 20 Products, lands as a pending change with a before/after diff for Admin approval. Bulk upload runs a dry-run report first and keeps the previous state for one-click rollback. Each Supplier Cost carries `valid_as_of` and Products unreviewed for 30 days are flagged.
- **potential_consequence:** A decimal slip (USD 45.00 typed as 4.50) or a generous discount sells goods below cost, Price Lock makes the loss permanent once the first Receipt is confirmed, and an insider can discount a friend's account without any Admin control firing.

### F-16: Rounding level, currency and arithmetic type are unspecified, so the FR-30 grand-total invariant cannot be guaranteed

- **location:** prd.md FR-19 (L306-311); FR-30 (L402-409); NFR-4 (L742); FR-38 tests (L470-473)
- **trigger_condition:** "Round half-up to two decimals in the currency shown" does not say whether rounding is per unit, line, Room or Order, whether USD is rounded before conversion to naira, or what number type to use; and FR-30's claim that the grand total is "identical" with or without a VAT line fails on a plain implementation (three lines of 10.10 before VAT total 32.57 when VAT is computed on the sum and 32.58 when each VAT-inclusive line is rounded).
- **guard_snippet:** FR-19 and NFR-4: decimal arithmetic only, never binary floats; compute the USD Selling Price per unit at full precision, convert to NGN at the stored rate, round half-up to kobo once per unit; line total = unit x quantity; Order total = sum of lines. The Proposal VAT line = VAT-inclusive total minus total/1.075, rounded once, with any kobo difference placed on the VAT line so both layouts show the same grand total. Add test vectors (12.345 x 7, three lines of 10.10, quantity 1,000, a discounted line) to the acceptance criteria and restate the FR-19 example in naira, since that is what Integrators see and pay.
- **potential_consequence:** Finance's expected transfer and the customer's screen disagree by kobo, exact-amount matching fails, and the invariant test fails on real data.

### F-17: The Platform Rate is hand-set with no reference source, spread, staleness limit, sanity bound or kill switch

- **location:** prd.md FR-20 (L313-319); Glossary "Platform Rate" (L120); Risks "Naira volatility" (L755); addendum.md breakeven FX line (L51)
- **trigger_condition:** Finance changes the rate "only when it has moved meaningfully" with no reference rate, no spread over what the platform actually pays for dollars (the addendum records official 1,329 against parallel 1,375 to 1,390, a 3.5% to 4.6% gap), no maximum age, no limit on a single change, no preview and no second approval, while every Cart, Project and unlocked Order reprices instantly.
- **guard_snippet:** FR-20: the record holds `rate`, `reference_source`, `spread_applied`, `effective_from`. Finance must reconfirm the rate each Business Day; after 24 hours without reconfirmation a banner shows and Order creation is blocked unless Admin overrides with a reason. A change above 2% needs Admin approval and one more than 10% from the previous value is rejected as a probable typo. Finance or Admin can freeze Order creation ("prices are being updated"). The screen shows how many open Carts, Projects and unlocked Orders a change affects. The PRD states which market rate is followed: replacing stock at the parallel rate while selling at the official one costs about USD 16 to 22 on a USD 500 Order (500 x (1 - 1,330/1,375 to 1,390), my arithmetic), against a USD 80 contribution.
- **potential_consequence:** A stale or mistyped rate (133.0 for 1,330) sells below replacement cost until someone notices and Price Lock freezes the loss; margin leaks silently into the official-to-parallel gap.

### F-18: Loyalty spend has no stated basis, conversion rate, timing or protection against gaming

- **location:** prd.md FR-21 (L321-334); FR-24 (L348); FR-5 (L186); FR-57 (L487); SM-1 (L806); addendum.md "Loyalty tier history" (L33-35)
- **trigger_condition:** "Delivered spend converted to USD equivalent" does not say whether spend is pre-VAT goods or the VAT-and-delivery-inclusive Amount Due, which rate converts it, whether returns and Credit Notes reduce it, which day the monthly recalculation runs, or whether a Back-order Line delays the whole Order's spend until its last Shipment lands; the same Delivered Order count also grants vouching rights.
- **guard_snippet:** FR-21: spend = pre-VAT, pre-delivery goods value of Delivered Order Lines in USD at each Order's stored rate, net of returned and credited lines, counted when the line's own Shipment is Delivered; recalculation at 00:00 WAT on the 1st with the result visible 7 days earlier (FR-22); state the basis explicitly because the thresholds were calibrated on pre-VAT USD 500 Orders (section 7).
- **potential_consequence:** Counting VAT alone lifts accounts at least 7.5% faster than the thresholds were calibrated for, goods returned after a tier is earned keep the discount, and Back-order Lead Times delay both tier credit and the SM-1 clock.

### F-19: Offline sync loses edits in the normal multi-technician case, and the offline Documentation promise contradicts the requirement

- **location:** prd.md FR-28 (L379-387); FR-50 (L587-591); UJ-5 (L75-81); FR-10 (L219); FR-54 and FR-55 (L641-651); addendum.md "Delivery surface" (L14-15)
- **trigger_condition:** "Later edit wins" is applied to the whole Project with the clock source unstated, so two Technicians editing different Rooms offline lose one set of edits; the "earlier version kept for recovery" has no way to be found or restored; UJ-5 assumes Documentation was "synced earlier" while FR-50 only says a User "can save" it; offline caches are not purged on logout, User removal or role change; and iOS limits on storage and background sync are ignored.
- **guard_snippet:** FR-28: merge per Room and per Project Item, last-writer-wins per field using server receipt time (never the device clock); deletions are tombstoned; changes record user and time and Owner and Manager can see who changed what; a sync screen lists conflicts and restores earlier versions for 30 days. FR-50: add "Make this Project available offline", which downloads all PDFs and guides for its Products (video on request) and shows the size. FR-10 and FR-54: removing a User or changing a role purges that device's cache at next connection and the cache refuses to open after 14 days without a check-in; define a storage budget and iOS behaviour (install guidance, eviction warning, manual sync).
- **potential_consequence:** Edits vanish silently on shared jobs, former staff keep Projects and cached prices on their phones, and Chidi, on a no-signal site, finds the one PDF he needs was never saved.

### F-20: Tax: the Proposal VAT line is offered to integrators of unknown VAT status, and the platform issues no tax invoice or proof of purchase

- **location:** prd.md FR-30 (L402-409); FR-64 (L717); Glossary "VAT" (L118); Open Question 1 (L825); addendum.md compliance notes (L91)
- **trigger_condition:** Any Owner or Manager can switch a VAT line onto the client PDF with no VAT-registration status or TIN on the profile, and the platform issues no numbered tax invoice, so a VAT-registered integrator cannot document the 7.5% input VAT it paid and manufacturer RMAs lack proof of purchase. Adds to OQ1: the invoice and the PDF toggle are the decisions that depend on the answer, along with delivery VAT and Credit Note VAT.
- **guard_snippet:** Profile: optional `tin` and `vat_registered` (Owner declares, staff may verify). FR-30: offer the VAT line only when `vat_registered` is true; otherwise the PDF is VAT-inclusive with no VAT line. Add an FR: a numbered tax invoice per Paid Order (buyer name, TIN if given, line VAT, delivery VAT, serials where recorded) and a credit-note document per Credit Note, downloadable as PDF. Resolve OQ1 now because the invoice template, the Credit Note document and the monthly VAT return all depend on it.
- **potential_consequence:** Registered integrators absorb 7.5% they should have reclaimed, unregistered ones issue VAT-looking quotes, warranty claims stall for lack of an invoice, and the monthly VAT return (due by the 21st per the addendum) has no source documents.

### F-21: The "client-ready" Proposal cannot carry labour or custom lines, and its logo, client fields and FX wording are undefined

- **location:** prd.md FR-29 (L395-400); FR-30 (L402-409); FR-3 (L172); UJ-1 (L46)
- **trigger_condition:** The PDF is promised to show the integrator's logo, client details and a one-month validity, but no FR collects a logo, defines client fields, allows labour, commissioning or other non-catalogue lines or a per-line margin (there is one margin for the whole Project), states what the client is told about the exchange rate, or shows Lead Time on Back-order lines.
- **guard_snippet:** FR-29 and FR-30: define profile fields `logo` (PNG or JPG, 2 MB), `trade_name`, `address`, `phone`, `email`, `tin`; client fields (name, company, address, email, phone, site address); allow custom lines (labour, travel, commissioning) with their own price and no Supplier Cost; show Lead Time on Back-order lines and a validity sentence stating the FX condition. Or record in section 5 that the Proposal is hardware-only in v1.
- **potential_consequence:** Integrators finish quotes in their own spreadsheets, which breaks the "one flow" promise and SM-4; UX designs forms without agreed fields; the client reads a one-month price promise the platform does not underwrite.

### F-22: Orders are created from the live Project, not the quoted one, so edits, repeat Orders and unorderable items break the Proposal logic

- **location:** prd.md FR-26 (L367); FR-31 (L411); FR-34 (L437-443); FR-35 (L445-451); FR-66 (L733); Glossary "Order" (L134); UJ-1 edge case (L49)
- **trigger_condition:** A Project edited after the Proposal (by a Technician, by the Owner, or because an item went out of stock, was discontinued or was quarantined) still gets the Proposal Rate within NGN 5; a second Order from the same Project in the month keeps it too (FR-34 says "priced afresh"); FR-34 forces the Order to contain every item while FR-26 and FR-16 forbid ordering Out of Stock items without back-order; and the "difference" compares an Order total that includes delivery with a Proposal that has no delivery line, mixing every driver into one number.
- **guard_snippet:** FR-29: snapshot Project contents with the Proposal. FR-35: the Proposal Rate applies only to Order Lines identical (item, quantity) to the snapshot and only to the first Order from that Proposal; show "Project edited since Proposal" when contents differ. FR-34: Order creation lists unorderable items and requires the Owner to remove or replace them in the Project (no silent drops). The "difference" is goods-only and decomposed into rate, tier, cost or markup or supplier change, items added or removed, and availability, with delivery on a separate line.
- **potential_consequence:** Integrators hold the old rate on items added after quoting and on repeat jobs, or cannot order at all because one line went out of stock, and Tunde cannot tell which part of the "difference" is FX and which he should pass to his client.

### F-23: The NGN 5 tolerance is 0.38% against a one-month Proposal, and it is a hard-coded absolute constant

- **location:** prd.md FR-35 (L445-451); UJ-1 edge case (L49); Risks "Naira volatility" (L755); section 2.1 (L28)
- **trigger_condition:** The only Proposal-to-Order rate protection is an absolute NGN 5 band (0.38% of 1,330) on a Proposal valid for a month, so the UJ-1 "edge case" is the normal case and the stated job ("so I'm not squeezed when the naira moves") is not delivered; the band is not defined as a setting, as absolute or percentage, or for decimal rates; and the addendum's own official-to-parallel gap is 9 to 12 times the band.
- **guard_snippet:** Make `proposal_rate_tolerance` an Admin setting expressed as a percentage of the Proposal Rate (audited), choose its value from the observed distribution of three-week rate moves instead of NGN 5, and print the real rule on the Proposal PDF in client-safe wording ("prices hold while the exchange rate stays within X%, otherwise they adjust"). If NGN 5 stays, rewrite the JTBD and Risks to say the Proposal gives no meaningful FX protection and the 24-hour Price Lock is the only one.
- **potential_consequence:** Integrators quote clients in naira for a month on a guarantee that lapses at 0.38%, then absorb the move or renegotiate with clients, which is exactly the squeeze the product claims to remove, and repeat purchase (SM-1) suffers.

### F-24: Delivery pricing and zoning cannot be built from the text as written

- **location:** prd.md FR-37 (L459-465); FR-39 (L477-481); FR-47 (L551-556); addendum.md per-order economics (L53) and "Fulfilment notes" (L99-101)
- **trigger_condition:** Delivery is a flat Zone Rate per Shipment "calculated automatically from the address", but there is no address structure (Nigerian addresses lack reliable postcodes), no rule that resolves an address to exactly one zone, no weight or volume component, and no way to know at creation how many Shipments an in-parts Order will have, while the breakeven assumes delivery "passes through at the courier's cost".
- **guard_snippet:** FR-37 and FR-47: structured delivery address (State, LGA, area, landmark, receiver name, receiver phone) with the zone looked up from State and LGA (Lagos areas defined as LGA lists), never geocoded from free text. Zone Rate = base + per-kg band from Product weight, plus a declared-value surcharge. At creation the Amount Due shows the delivery charge for the Shipments implied by the chosen method. Operations cannot add a Shipment that raises the Integrator's charge after payment; any extra courier cost is absorbed and reported. Zone Rates are reviewed whenever the courier tariff changes.
- **potential_consequence:** Wrong-zone quotes from day one, a loss on every heavy or bulky Shipment (racks, UPS units, displays), and an Amount Due that cannot be known or paid at creation for in-parts Orders.

### F-25: Price Lock hands integrators a free option on the exchange rate

- **location:** prd.md FR-38 (L467-475); FR-34 (L437); FR-20 (L313); Risks (L755)
- **trigger_condition:** Creating an Order costs nothing and reserves nothing and captures a hand-set rate; payment inside 24 hours locks that rate while payment between 24 and 48 hours reprices at the current rate, so an integrator can watch the market and pay inside the window when the naira weakens and just after it when the naira strengthens, or create several Orders from one Project and pay only the best.
- **guard_snippet:** Decide the policy and write it down: either a late payer (24 to 48 hours) pays the higher of the creation rate and the current rate (stated in the Terms), or the platform accepts the option and measures its cost. In either case cap unpaid Orders at two per Project per Business Account, re-evaluate unlocked Orders against any Platform Rate change above 2% before they can lock, and report "rate gain or loss on locked Orders" monthly so SM-C1 shows the cost.
- **potential_consequence:** Rate-sensitive integrators select against the platform on every Order, so realised markup (SM-C1) erodes by the option value, and in a sharp devaluation the lock sells stock below replacement cost.

### F-26: The Price Lock clock trusts upload time, an upload "stops" expiry, and Unconfirmed outcomes are unspecified

- **location:** prd.md FR-38 (L467-475); FR-41 (L502-508); Glossary "Payment" (L142)
- **trigger_condition:** Lock and expiry are keyed to when a Receipt is uploaded (a self-reported event) while the examples and the second sentence talk about when "payment arrives"; an upload "stops" the 48-hour expiry with no rule for a later Unconfirmed outcome, so as written any image at hour 23 earns the lock window, re-uploads are unlimited (FR-41), an Order with any Receipt can never expire, and a payment after hour 48 on a part-paid Order has no defined price.
- **guard_snippet:** FR-38: one evidence-based clock: the lock is earned only by a Receipt whose bank reference and value time, verified by Finance against the platform bank statement, fall within 24 hours of creation. An upload pauses expiry only until Finance marks it; an Unconfirmed outcome resumes the clock with a floor of 4 Business Hours; at most 3 Unconfirmed uploads per Order before staff review. Define the price for payments after hour 48 on a part-paid Order (current rate, expiry 7 days after the last confirmed Payment, then Credit Note).
- **potential_consequence:** A person holds an Order and a lock open with worthless uploads, an honest integrator with a correct but oddly timed Receipt has no rule to rely on, and Finance cannot refuse a lock without contradicting FR-38 as written.

### F-27: Repricing is undefined: what changes, from what base, how it is communicated, and the tests are in dollars for naira payments

- **location:** prd.md FR-38 (L467-475, Out of Scope at L475); FR-35 (L445); FR-42 (L510); FR-51 (L599); NFR-4 (L742)
- **trigger_condition:** "The Order reprices at the current rate" does not say whether only the rate changes or also cost, markup, tier and VAT, whether confirmed naira is credited as naira or as USD-equivalent, when the new Amount Due is fixed (upload, confirmation, final payment), or what the Integrator is told (FR-51 has no repricing event); the tests are written in dollars ("a $450 Receipt on a $500 Order") although every Payment is a naira transfer; and "Admin override deferred to v2" leaves no waiver for a NGN 100 short-pay or a platform outage.
- **guard_snippet:** FR-38: add a worked rule. On reprice only the Platform Rate changes (Tier Discount, markup and VAT stay as captured); confirmed Payments are credited in naira; the new Amount Due is computed once, at the first confirmation after hour 24, stored with its rate, and the Integrator is notified of the new Amount Due and the shortfall or Credit Note. Finance can waive shortfalls under NGN 1,000 or 0.2% with a reason code and can extend the lock with a reason code when logs show an upload failure, an outage or a documented bank daily-transfer limit. Rewrite the tests in naira with a stated rate path and add "Order repriced" to FR-51. Worked example (my arithmetic): a USD 500 Order at 1,330 is NGN 665,000; NGN 598,500 is paid in time; the rate is 1,400 at hour 30; a full reprice makes NGN 700,000 due, a shortfall of NGN 101,500 (USD 72.5), not the USD 50 the PRD's example implies.
- **potential_consequence:** Finance and Integrators compute different Amount Dues for the same Order, every part-paid Order becomes a dispute, and one short-pay drags a whole Order to the current rate.

### F-28: Back-order is prepaid with no lateness remedy, an unhedged platform FX exposure, no Lead Time snapshot and open-ended stock holds

- **location:** prd.md FR-17 (L287); FR-36 (L453); FR-39 (L477); FR-57 (L484); SM-C3 (L818); UJ-4 (L67-73)
- **trigger_condition:** An integrator prepays in full for a Back-order Item, cannot cancel once it is "already ordered from a Supplier", has no remedy if the Lead Time is missed; the platform's USD cost for the item is exposed to the naira for the whole Lead Time because the naira price is fixed at payment; "ship in full" holds the in-stock lines for weeks; and the promised Lead Time is not stored on the Order Line although SM-C3 needs it.
- **guard_snippet:** FR-36, FR-39 and FR-57: store `promised_lead_time` and `promised_ship_by` on the Order Line at creation. When `promised_ship_by` + 7 days passes, the Owner or Manager may cancel that line for a Credit Note or bank refund at their choice regardless of Supplier-order status. Reprice Back-order lines at payment when the rate has moved beyond the tolerance since creation, or state that Operations buys the dollars on payment confirmation. "Ship in full" holds in-stock lines for N days and then offers "ship in parts". Add the Supplier-ordered marker as data.
- **potential_consequence:** Customer cash sits with the platform for open-ended periods (the stored-value concern the wallet research raised), integrators who cannot wait have no exit, and a naira slide during a long Lead Time wipes out margin the discount has already cut.

### F-29: Cancellation and amendment: partial Payments, late bank credits, and no way to amend an Order

- **location:** prd.md FR-57 (L483-488); FR-41 (L503); FR-38 (L467)
- **trigger_condition:** "An Awaiting Payment Order closes at no cost" ignores that such an Order may already hold confirmed partial Payments or unconfirmed Receipts; a bank credit can land after cancellation or expiry (the Credit Note rule is written only for expiry); and there is no way to amend an Order (address, Store, ship in parts or in full) short of cancelling and recreating it, which reprices it.
- **guard_snippet:** FR-57 and FR-41: cancelling or expiring any Order that has confirmed or pending Payments creates a Credit Note (or refund request) for the confirmed amount and routes pending Receipts to Finance; late credits on Cancelled and Expired Orders follow the same rule; an Order with a Payment in review cannot be cancelled without Finance consent. Add an amend action before Approval for address, receiver, Store and shipping choice that never reprices.
- **potential_consequence:** Money received for a closed Order has no home, the Integrator sees "no cost" while Finance holds their funds, and fixing a typo in an address forces a repriced new Order.

### F-30: Payments are a binary "confirmed or not", but the money logic needs amounts, references and senders

- **location:** prd.md FR-40 (L496-500); FR-41 (L502-508); FR-42 (L510-515); FR-43 (L517); Glossary "Payment" (L142)
- **trigger_condition:** A Payment is only confirmed or Unconfirmed, yet partial payments, overpayments, shortfalls and Credit Notes need a confirmed naira amount per Payment, and nothing captures the declared amount, sender name, bank and account, bank reference, value time or intended Order.
- **guard_snippet:** FR-40 and FR-41: Payment fields `declared_amount` (entered by the Integrator at upload), `bank_reference` (NIP session id or equivalent), `sender_name`, `sender_bank`, `value_time`; Finance records `confirmed_amount` (may differ from declared), `confirmed_at`, `reviewer`; `bank_reference` is unique; a Payment under review is locked to one Finance user and confirmation is idempotent; each confirmation writes an immutable ledger line and corrections are compensating lines; overpayment (sum confirmed minus Amount Due) opens a draft Credit Note for Finance to issue.
- **potential_consequence:** Stories for partial, over and short payment cannot be written without inventing the data model, Finance keeps amounts in a spreadsheet beside the platform, and the audit trail says "confirmed" without saying how much.

### F-31: Finance confirms an image against a number, not funds against the bank statement

- **location:** prd.md FR-41 (L502-508); UJ-6 (L86-89); NFR-2 (L740); SM-C4 (L819); FR-34 (L437)
- **trigger_condition:** Finance confirms "each uploaded Receipt against the Order's Amount Due" with no requirement to match the platform's own bank statement, no duplicate detection across Orders and Business Accounts, no third-party payer rule, no queue for bank credits that arrive with no upload, and no way to reverse a confirmation if the transfer is recalled, while the two-hour SLA and a 95% target push staff to hurry.
- **guard_snippet:** FR-41: confirmation requires a matching credit on the platform bank statement (statement upload or CSV import in v1) for amount, reference and time; one `bank_reference` can be used once; a Receipt whose sender name does not match the Owner or trade name goes to a second reviewer; a daily "unmatched credits" queue lists bank credits with no Payment; add a Payment state `Reversed` (Finance with reason, second approval) that returns the Order to Awaiting Payment and blocks Approved and Shipped transitions pending review; SM-C4 excludes Payments that waited for bank-statement evidence.
- **potential_consequence:** Edited bank-app screenshots release goods, one genuine Receipt is reused against two identical Orders (FR-34 makes identical Amount Dues likely), and a recalled transfer after dispatch has no process, so the loss is absorbed and unexplainable in the audit.

### F-32: The platform's bank details are shown to every Integrator but no role manages them and no audit covers them

- **location:** prd.md FR-40 (L496); FR-52 (L611-627); FR-53 (L629-633)
- **trigger_condition:** FR-40 says Integrators "view the platform's bank details", but no FR says who edits them, the FR-52 role table has no row for them, and FR-53's audit list omits them.
- **guard_snippet:** FR-52: add "Edit platform bank details: Admin only, second approver Finance, effective after 24 hours, audited, emailed to Admin and Finance". Integrators see a "bank details changed on <date>" banner for 7 days, and each Order stores the bank details shown at creation.
- **potential_consequence:** One compromised staff session changes an account number and diverts every Integrator payment until someone notices, with no record of who did it.

### F-33: Credit Notes are an undefined stored-value ledger: no currency, no states, and they collide with Price Lock and expiry

- **location:** prd.md FR-43 (L517-523); FR-38 (L467); FR-57 (L483); FR-61 and FR-62 (L698-709); Glossary "Credit Note" (L143); Constraints "Payments" (L748); Vision (L19); section 5 (L767)
- **trigger_condition:** A Credit Note "records the Business Account, amount, reason, and issuer" and nothing more: no currency, source Order Line, VAT component, balance or states, so a note applied to an Order that later expires or cancels can reset its 12-month expiry, an Order funded only by a Credit Note has no Receipt and so expires at 48 hours and cannot earn a lock, a naira note held for up to 12 months loses real value in a dollar-priced market, and expiry turns customer cash into revenue, contradicting "markup on goods is the only revenue".
- **guard_snippet:** FR-43: add `currency` (NGN fixed at issue; state who bears FX), `source_order_line`, `vat_component`, `balance`, `issued_at`, `expires_at`; states Issued, Partially applied, Applied, Refunded, Expired, with application writing ledger lines; if the Order a note funded cancels or expires, the amount returns to the original note with its original expiry; refunds go only to a sender account recorded on a confirmed Payment of that Business Account. FR-38: a Credit Note applied by Finance within 24 hours counts as a Receipt for lock and expiry, and an Order fully funded by a Credit Note does not expire for lack of a Receipt. Extend counsel's OQ4 and the wallet questions (deposit-taking, VAT on prepayments, insolvency, forfeiture on expiry and how it is booked) to Credit Notes.
- **potential_consequence:** Credit loses value, apply-and-cancel loops refresh expiry, credit-funded Orders expire or reprice by accident, and the platform holds customer balances under a different name without the analysis the addendum gave wallets.

### F-34: Shipment lifecycle has only the happy path, "Delivered" is typed in by hand, and goods are released without handover proof

- **location:** prd.md FR-45 (L537-543); FR-56 (L558-567); FR-59 (L684); FR-64 (L717); FR-21 (L321)
- **trigger_condition:** Shipments have no failed, lost, damaged-in-transit, returned-to-sender or uncollected state, no proof of delivery or collection (courier POD reference, receiver OTP, collector name and ID) and no risk-of-loss rule, yet "Delivered", entered by Operations from courier tracking, starts return windows and warranty and feeds loyalty and vouching.
- **guard_snippet:** FR-45: add statuses Failed Delivery, Lost, Returned to Sender and Uncollected (after 14 days, then a restock decision). Delivered requires a proof reference (courier POD) or the receiver's OTP entered at handover. Signed For records collector name, ID type and last four digits, and any named third-party collector. State that the platform bears loss until Delivered for goods sent by its courier partner. An Order cannot become Completed while any Shipment is Lost or Failed, and those statuses open a non-delivery case (see F-39).
- **potential_consequence:** A lost parcel leaves the Order Approved for ever (never Completed, never counted for spend), a courier's false "delivered" scan starts the damage clock for goods never received, and goods can be released to anyone quoting an order number.

### F-35: The launch is nationwide in scope but one Store in practice, with no service area, delivery estimate or courier SLA

- **location:** prd.md Vision (L17-21); FR-47 (L551); FR-56 (L558); FR-68 (L666); section 6.1 (L788); Open Questions 6 and 14 (L830, L838)
- **trigger_condition:** Delivery Zones span "the rest of Nigeria" and Nigeria is the launch market, but there is one Store, no delivery estimate by zone, no courier SLA, no stated serviceable area, and the warehouse-to-Store move is outside the platform. OQ6 and OQ14 raise the transfer step and the return handover, not what an Integrator in Abuja or Port Harcourt is promised or can realistically use.
- **guard_snippet:** State a launch service area (for example Lagos delivery and pick-up plus named South-West states) and block Delivery orders outside it with "coming soon", or add per-zone delivery estimates shown at Order creation, a courier SLA, and a rule that a Pick-up Order only becomes Ready for Pickup when goods are physically at the Store. Adopt OQ14's courier-handover option (clock stops at courier handover with a tracking reference) for returns from distant zones.
- **potential_consequence:** Integrators outside Lagos pay for delivery with no estimate and cannot realistically use the return process, and the first Abuja or Port Harcourt complaint sets the platform's reputation.

### F-36: Notifications omit the events the flows depend on, define no recipients by role, and give staff no alerts

- **location:** prd.md FR-51 (L599-603); FR-58 (L675); FR-68 (L666); FR-66 (L729); addendum.md "Integrations" (L20)
- **trigger_condition:** The event list omits repricing or short-pay, stock-out Credit Notes, Credit Note issue and expiry, a Return Request awaiting a Manager's approval, Issue Code issue and expiry, Back-order lateness, Shipment dispatch with tracking and recall notices; it names no recipient roles (a Technician must not receive an Amount Due), has no staff alerts, no fallback when WhatsApp delivery fails and no opt-in or template-approval rule.
- **guard_snippet:** FR-51: a notification matrix (event x recipient role x channel x template id x trigger time) with a launch set of at most 12 events. Time-sensitive events (Payment Unconfirmed, repriced, Order expiry at hour 40, Issue Code at hour 36, Return approval pending) go by WhatsApp with email fallback after 15 minutes undelivered. Technician templates carry no amounts. Staff alerts per F-04.
- **potential_consequence:** A Technician's request lapses because no Manager was told it was waiting, Integrators learn they owe a top-up only when the Order expires, and WhatsApp template rejection and fees surprise the founder.

### F-37: Staff have no segregation of duties: Admin can do everything, Finance issues and pays, and one Operations officer approves, adjusts stock, records serials, supports and decides

- **location:** prd.md FR-52 (L611-627); FR-8 (L207); FR-12 (L252); FR-43 (L518); FR-60 (L691)
- **trigger_condition:** FR-52 gives Admin every capability (including confirming Payments, paying refunds, creating Level 3 accounts and editing stock) and lets Operations approve Orders, edit stock, record serials, run Support, investigate and decide remedies on the same case, with no maker-checker on any action.
- **guard_snippet:** FR-52: add rules. (a) No one confirms a Payment or issues or pays a Credit Note for a Business Account they created, approved or vouched. (b) The person who investigated a return cannot decide it (Admin decides when Operations investigated). (c) Refunds and Credit Notes above NGN X, Platform Rate changes above 2%, Active Supplier switches and bulk price changes need a second approver. (d) Use of Admin powers "acting as" another role is flagged and reviewed weekly by the founder. (e) Stock adjustments above N units need Admin sign-off.
- **potential_consequence:** One person can create a ghost Level 3 account, "confirm" a payment that never arrived, approve and "deliver" the Order, or siphon stock through adjustments, and the audit trail shows only valid actions by valid roles.

### F-38: The audit trail scope contradicts other FRs, "immutable" collides with erasure and retention, and nobody reviews it

- **location:** prd.md FR-53 (L629-633); FR-12 (L257); NFR-3 (L741); NFR-5 (L743)
- **trigger_condition:** FR-53 lists nine event types while FR-12 audits "every Product change" and NFR-3 logs every NIN access (neither is in FR-53); unlisted high-impact events (Order approval and cancellation, stock adjustments, serial entry and correction, Shipment status changes, VAT rate, return windows, tolerance, Store and Zone Rate edits, bank-detail changes, Support lookups, User removal) have no audit requirement; the "immutable" trail stores personal data and cannot honour deletion; "any role" ignores database administrators and developers; and no one is assigned to read it.
- **guard_snippet:** FR-53: define the audited catalogue as every state change to money, stock, access, identity data, pricing parameters and Case or Return records, and every read of NIN or bank details. Store entity ids and field diffs (not copies of NIN or full names) so personal data can be erased elsewhere. Append-only storage with hash chaining and credentials separate from application admin; financial entries retained at least 6 years. The founder reviews Admin and Finance override events weekly, and an alert fires on unusual patterns (many NIN reads, many Credit Notes by one user).
- **potential_consequence:** The trail cannot answer who changed a price, threshold or serial, an NDPA access or deletion request collides with immutability, and Admin misuse (read NINs, self-approve) is logged but never seen.

### F-39: The return flow contradicts itself on refunds, Issue Codes for non-fault reasons and non-delivery

- **location:** prd.md Glossary "Return Request" (L145); UJ-7 climax (L95); FR-58 (L675-682); FR-61 (L698-703); FR-68 (L666-673)
- **trigger_condition:** FR-61 grants a cash refund "for non-delivery" while a Return Request is about a delivered Order Line and FR-58's reasons have no non-delivery; FR-68 issues a code only "if remote assistance does not fix the fault", which cannot apply to change of mind, wrong item or non-delivery, yet FR-58 requires a code for every reason; UJ-7 lists "a refund" as an outcome for a faulty camera, which FR-61 allows only for counterfeits and non-delivery.
- **guard_snippet:** Separate the cases: a `Non-delivery claim` (no Issue Code; opened by Owner or Manager when a Shipment is Lost or N days late; remedy redispatch or cash refund); Issue Code only for device reasons (dead on arrival, warranty, suspected counterfeit, transit damage, wrong item); change of mind needs a Support-issued return authorisation with no fault check. Key the FR-58 reason list and the FR-61 remedy table by reason, and correct UJ-7's outcome list to the FR-61 remedies.
- **potential_consequence:** Stories for non-delivery and change of mind cannot be written, Support improvises, and the most common Nigerian delivery failure has no remedy workflow.

### F-40: The return window can expire while the integrator waits on Support, runs from a hand-entered date, and 7 days for dead-on-arrival ignores installation reality

- **location:** prd.md FR-59 (L684-689); FR-68 (L666); FR-45 (L538); UJ-7 (L91-97); addendum.md "Returns and warranty research" (L107, L115)
- **trigger_condition:** The 2-day and 7-day windows run from the hand-entered Delivered or Signed For date until the Return Request is raised, but raising needs a Support-issued Issue Code after a remote-fix attempt that Support controls (a lapsed 48-hour code means a new code issued after the window closed); UJ-7 is a fault found during installation, often weeks after delivery; and the addendum's distributors give 30 days for dead-on-arrival while the 7-day figure comes from one retailer.
- **guard_snippet:** FR-59: the window is met by the timestamp of the first logged contact with Support (call or WhatsApp logged by Support within 1 Business Day, otherwise the Integrator's in-app "Report a problem" submit time), not by the raise time; a lapsed Issue Code neither closes nor reopens the window. Set dead-on-arrival at 30 days (or the Supplier's window if shorter) and transit damage at 5 days, or document why the stricter figures were chosen and what the upstream Supplier allows. Add the in-app button that starts the clock.
- **potential_consequence:** Slow Support or a Sunday removes the integrator's right to a remedy, and most install-time faults fall outside a 7-day window into manufacturer-warranty cases with no stock replacement, which is the failure UJ-7 was written to prevent.

### F-41: Nothing verifies that the returned device is the one dispatched (swap fraud), and non-serialised goods have no identity at all

- **location:** prd.md FR-60 (L691-696); FR-67 (L659); FR-68 (L666); addendum.md "Counterfeit practice" (L113)
- **trigger_condition:** Support checks the serial against dispatch records before issuing the code, but nothing requires comparing the physical serial, label, packaging and condition at arrival with that record (FR-60 only says Support "records arrival"), and non-serialised Products are verified by Order Line alone.
- **guard_snippet:** FR-60: at arrival Support photographs the unit, label and packaging and records the physical serial; a mismatch with the Issue Code serial rejects the return automatically and flags the account; non-serialised returns need photos taken before shipping and a sealed-packaging check. Require, as a listing precondition, that each Supplier agreement states return, dead-on-arrival and counterfeit chargeback terms, and record the Supplier of each dispatched unit.
- **potential_consequence:** Integrators keep the good unit and return a failed or fake one under its serial for a replacement or Credit Note, and the platform has no upstream recovery from the Supplier.

### F-42: Serial numbers are hand-keyed, so one typo becomes "no dispatch record" and a denied return

- **location:** prd.md FR-65 (L723-727); FR-67 (L659-664); FR-68 (L666-673); UJ-7 edge case (L97)
- **trigger_condition:** Operations types serials onto each Shipment; a single mis-keyed digit means "no dispatch record", which under FR-68 and UJ-7 ends the process with no Issue Code, and the only fallback (verify by Order Line) is defined for Products without serials.
- **guard_snippet:** FR-65: serial entry by camera barcode scan or CSV paste, with a uniqueness check across all dispatches, a count check against shipped quantity and a model-specific format pattern where the Product defines one. FR-67 and FR-68: when a serialised device's serial finds no record, Support can open a "serial exception" (Order Line plus label photo plus box photo) approved by Operations and Admin and audited. A recorded serial is corrected only with a reason and a second person.
- **potential_consequence:** Honest integrators are refused remedies for the platform's own data-entry mistakes, and Operations spends warehouse time keying long serials for every 24-camera Order.

### F-43: An Issue Code is one code, one serial, one request, but returns have quantities and multiple lines

- **location:** prd.md FR-58 (L675); FR-67 (L659); FR-68 (L666)
- **trigger_condition:** An Issue Code is tied to one Business Account and one serial and serves one Return Request, yet FR-58 takes a quantity, so a box of five faulty cameras needs five codes or an unstated multi-serial code, and nothing caps total returned quantity per Order Line or blocks two open requests for one unit.
- **guard_snippet:** FR-68: an Issue Code lists 1 to n serials (or an Order Line and quantity for non-serialised goods) and the 48-hour clock applies to the whole code. FR-58: open plus decided quantity per Order Line cannot exceed the delivered quantity, there is one open Return Request per serial, and a serial already Resolved cannot be raised again.
- **potential_consequence:** Support issues repeated codes by hand under time pressure, or the same unit is returned twice for two Credit Notes.

### F-44: Remedy money rules are loose: "reasonable freight", the restocking base, no-fault-found costs, and replacement shipping and warranty restart

- **location:** prd.md FR-60 (L692); FR-61 (L698-703); FR-62 (L705-709); FR-64 (L717)
- **trigger_condition:** Freight is reimbursed if "reasonable" with no cap, receipt or courier standard; the 15% restocking fee applies to the line's "paid price" without saying whether that includes VAT, delivery or a discount; a unit that tests fine is "returned to the Integrator at their cost" but the platform has no way to charge that cost; a replacement "creates no new charge" yet needs a Shipment (does delivery apply?), a serial record and a Warranty Period start.
- **guard_snippet:** FR-62: reimbursement = actual courier receipt up to the Zone Rate for the same route, one claim per return. FR-61: restocking base = pre-VAT goods price of the line at the paid price, with VAT credited in full. No-fault-found return freight is deducted from an open Credit Note or added to the Order's next Amount Due, and shown in the decision. Replacement Shipments sit under the original Order at zero charge with the serial recorded and delivery borne by the platform for dead-on-arrival, counterfeit and platform faults. The replacement inherits the remaining Warranty Period of the original, measured from the original dispatch.
- **potential_consequence:** Inflated freight claims, disputes about what the 15% applies to, unrecoverable return costs, and replacements that restart warranties.

### F-45: The counterfeit procedure is a one-way switch with a large blast radius and an unbounded time limit

- **location:** prd.md FR-59 (L685); FR-61 (L698); FR-66 (L729-735); FR-51 (L599)
- **trigger_condition:** Operations or Admin can mark a Product or "batch" suspected counterfeit, stopping sale and quarantining stock, but there is no clearing path, no evidence threshold, no defined "batch", no notice to other Business Accounts that bought the units, any Level 3 account (including a competitor) can trigger it, and reports are accepted "at any time" with a cash refund for "confirmed" counterfeits without saying who confirms or on what evidence.
- **guard_snippet:** FR-66: states Suspected, Confirmed (manufacturer serial check or a two-person Operations and Admin decision with photos attached) and Cleared. Marking Suspected requires attached evidence and a second approver for catalogue-wide holds; hold only the affected batch (serial range or receiving record) when batch data exists; notify affected Business Accounts (add a recall notice to FR-51); Cleared restores sale and lifts flags; decide or escalate within 14 days. Cash refund only from Confirmed, and reports later than Warranty Period plus 90 days need Admin approval.
- **potential_consequence:** Either the platform keeps selling suspected fakes that integrators install in client buildings, or a malicious claim halts a profitable line with no way to undo it; cash refunds on years-old claims are an unbounded liability.

### F-46: Support runs off-platform with no caller authentication and no case record

- **location:** prd.md FR-67 (L659-664); FR-68 (L666-673); UJ-7 (L94); addendum.md "Support duties" (L121); Open Question 14 (L838)
- **trigger_condition:** Remote assistance, serial lookup and Issue Code requests happen by phone and WhatsApp outside the platform, with no requirement to authenticate the caller as a User of that Business Account, no ticket for cases closed remotely or refused (notes exist only once an Issue Code is generated), and no rule for personal and order data shared in WhatsApp chats. Adds to OQ14: "what Support logs" is only part of this.
- **guard_snippet:** FR-67 and FR-68: Support verifies the caller by an in-app one-time code or a callback to the verified phone before disclosing any Order or serial detail. Every contact opens a Case (channel, caller, serial or Order Line, outcome, time) in the staff console, including "resolved remotely" and "refused". WhatsApp is used only through the business account with retention and no personal phones. The Case timestamp feeds the reporting window (F-40).
- **potential_consequence:** Social engineering reveals who bought which serial and what the platform will accept, and refused or remotely resolved cases leave no trace, so Support quality, abuse patterns and disputes cannot be reviewed.

### F-47: NIN is stored with no lifecycle, minimisation, key management or NDPA scaffolding around it

- **location:** prd.md NFR-3 (L741); FR-3 and FR-4 (L172-184); FR-11 note (L244); Open Question 12 (L836); addendum.md "Integrations" (L19) and sources (L95)
- **trigger_condition:** The Owner's NIN is stored encrypted and Admin-visible, but nothing says why the full NIN is kept after verification, who holds the keys, what happens in backups, logs, error reports (the cost sources name hosted monitoring and storage vendors) and the staff UI, which third parties receive it, or the controller duties NDPA 2023 brings (privacy notice, lawful basis, breach notification, access and deletion requests, closing a Business Account, DPO or impact-assessment decision). OQ12 asks only about retention and visibility.
- **guard_snippet:** NFR-3: store the provider's verification reference, a keyed hash (for uniqueness) and the last four digits; keep the full NIN only for accounts in manual review and delete it 30 days after the decision. Mask NIN in the staff UI with a reason-coded "reveal" that logs. Exclude NIN from logs, analytics, error reports and exports. Keys are held outside the database and cover backups. List processors and hosting locations in the Privacy Notice with data processing agreements and a cross-border transfer review. Add FRs for data-subject access and deletion, Business Account closure (retained financial records versus erased personal data) and a breach runbook with the 72-hour notification step.
- **potential_consequence:** One database dump or error-report payload exposes Owners' NINs, the platform cannot answer an access or erasure request, and one Admin holds unreviewed access to every NIN.

### F-48: The NFRs omit availability, backup and restore targets, device and browser support, upload rules and scheduled-job behaviour

- **location:** prd.md Cross-Cutting NFRs (L737-744); FR-54 (L641-645); FR-55 (L647-651); FR-21 (L321); FR-38 (L467)
- **trigger_condition:** There is no availability target, RPO or RTO, device and browser matrix (low-end Android, iOS Safari PWA limits), upload limits and types (HEIC, PDF, screenshots) with malware scanning and EXIF stripping for Receipt and Return photos, definition of the 3G test (throttle profile, device class, payload budget), or requirement for the scheduled jobs the product depends on (monthly tier recalculation, 24-hour and 48-hour timers, Proposal and Credit Note expiry, the Business Hours clock) or for monitoring them.
- **guard_snippet:** Add NFRs: 99.5% monthly availability excluding announced maintenance; RPO 1 hour, RTO 4 hours, quarterly restore drill; supported matrix (for example Android 9 and later on Chrome, iOS 16.4 and later for the installed PWA); uploads accept JPEG, PNG, HEIC and PDF up to 10 MB, resized client-side to about 2 MB, EXIF GPS stripped and scanned; performance profile "Slow 3G, 400 kbps, 400 ms RTT, 2 GB RAM Android" with a first-load budget for the Catalogue list; timers store UTC, display WAT, run idempotently and alert the founder when a job misses.
- **potential_consequence:** Receipt photos from 12 MP phones time out on mobile data, a restore after an incident has no target and may lose Receipts that are legal evidence of payment, and timers misfire silently around deployments and restarts.

### F-49: Capacity targets and growth assumptions contradict each other across the PRD and addendum

- **location:** prd.md NFR-1 (L739); SM-1 (L806); SM-2 (L810); SM-3 (L811); addendum.md "Scale reconciliation" (L45-47)
- **trigger_condition:** NFR-1 sets 100 Orders a month as a capacity floor and the addendum calls it "a low floor", but SM-3 reaches 98 at month 48 and 157 at month 60; the addendum still says "the 20% year-on-year growth assumption yields about 12 Integrators after year one" while SM-2 assumes 16 (60% growth); SM-2 and SM-3 imply about 2.0 Orders per active buyer a month from month 12 against the 1.5 launch rate (15 Orders from 10 Integrators), and SM-1's 60% repeat target fits neither (my arithmetic).
- **guard_snippet:** NFR-1: set the design target at 3 times the SM-3 month-60 figure (about 500 Orders a month) and restate the floor. Correct the addendum to the 60% path. State the per-account order frequency assumption and its source and reconcile SM-1 with it, or lower SM-3. Define "onboarded" (Level 3 approved?) in the Glossary.
- **potential_consequence:** The architecture is sized below the PRD's own year-four plan, the founder reads two growth rates in one document, and the metrics cannot all hold at once, so the breakeven path rests on unstated behaviour.

### F-50: SM-C1's floor is below the contribution the breakeven assumes, and "month 11" is a transient crossing

- **location:** prd.md SM-6 (L807); SM-C1 (L816); section 7 note (L821); addendum.md per-order economics (L53) and "Reading it" (L89)
- **trigger_condition:** SM-C1's 20% realised-markup floor means USD 80 gross profit per USD 400 of cost, which after the USD 7.50 handling the model deducts is USD 72.50 contribution, not the USD 80 behind month-11 breakeven (the model's own USD 87.50 gross is a 21.9% markup); and if year-two fixed costs (USD 2,300, about 29 Orders) step up at month 13, Orders (about 25) fall below them again until about month 17 (my arithmetic).
- **guard_snippet:** SM-C1: set the floor at 21.9% or restate SM-6's contribution at 20%; at USD 72.50 the first crossing slips from about month 11 to about month 13 (my arithmetic). SM-6: define "by month 11" as three consecutive months of positive contribution after fixed-cost step-ups, and model fixed costs as the step function the 2,300, 3,200, 4,300 and 5,500 series implies.
- **potential_consequence:** The counter-metric can read "green" while the business is behind the path it was built to protect, and the founder believes breakeven was reached in month 11 and then watches losses return in year two.

### F-51: The breakeven model leaves out float, returns, warranty, shrinkage, courier loss and FX spread, and does not report peak funding

- **location:** prd.md SM-6 (L807); section 7 note (L821); Open Question 13 (L837); addendum.md "Results" and "Reading it" (L71-89); "Not in the model" (L93)
- **trigger_condition:** The recommended 60% path shows +USD 61,000 at month 60 while the excluded float is about USD 63,000 at that volume (net about -USD 2,000); returns, warranty, counterfeit, courier loss, shrinkage and FX spread are absent although the PRD now commits the platform to replacements, RMA freight and credits; and the model reports month-60 cash but not the peak cumulative funding need. Adds to OQ13: float is not only a financing question, it takes the recommended path to about -USD 2,000 at month 60.
- **guard_snippet:** Add model lines for float, a returns and warranty provision (start at 2% of revenue), shrinkage, FX spread (F-17) and courier loss. Report the peak cumulative funding need (build + operating deficit + float) and the month it occurs. Cap purchasing against available cash until the float is financed.
- **potential_consequence:** The founder funds the build on a path that also needs about USD 63,000 of stock and meets return and shrinkage costs in the first quarter with no provision.

### F-52: Every metric says "Validates FR-x" but no requirement produces the numbers, and Order Lines do not snapshot cost, markup or supplier

- **location:** prd.md section 7 (L806-819); NFR-4 (L742); FR-12 (L252); FR-53 (L629)
- **trigger_condition:** SM-C1 needs the platform's landed cost of the units sold (Supplier Cost is only the current price, with no freight-in, duty, clearing or FX at purchase), SM-C3 needs the promised Lead Time per line, SM-C4 needs confirmation timestamps by Business Hours, SM-2 needs "onboarded" defined, NFR-4 snapshots only rate, tier and VAT (not Supplier Cost, Platform Markup or Active Supplier), and no FR exists for finance exports (VAT output, bank reconciliation, Credit Note ageing) or a founder dashboard.
- **guard_snippet:** NFR-4: each Order Line stores unit USD cost, markup %, discount type and %, VAT rate, Platform Rate, Active Supplier and promised lead time. Add an FR for dashboards covering SM-1 to SM-5 and SM-C1 to SM-C4 with definitions and data sources on each tile, and CSV exports for orders, Payments, Credit Notes (with ageing), monthly VAT output and bank reconciliation. Add landed-cost fields (freight, duty, clearing) to inbound receiving so realised markup is computable.
- **potential_consequence:** The success metrics are decorative, and Finance builds the monthly VAT return and the realised-markup figure in spreadsheets, which is the situation the product exists to remove.

### F-53: Unconfirmed assistant proposals are written as numbered, testable requirements with no owner, deadline or blocking flag

- **location:** prd.md FR-34 (L438); FR-41 (L503); FR-43 (L518); FR-57 (L484); FR-58 to FR-68 (L653-735); section 8 (L823-838); section 9 (L866-868)
- **trigger_condition:** Large parts of the money and returns design (repeat Orders, Receipt re-uploads, Credit Note mechanics, cancellation, all return terms) are tagged "assistant's proposal, not yet confirmed" or "no objection yet", yet appear as FRs with testable consequences, and none of the 14 open questions has an owner, due date or the epics it blocks.
- **guard_snippet:** Add a decision log to section 8: for each open question and unconfirmed assumption record `decision_owner`, `needed_by`, `blocks` (FR ids or epics), `default_if_silent` and `status` (Proposed, Confirmed, Rejected). Tag unconfirmed FR headings (for example "FR-57 [PROPOSED]"). Epics that depend on unconfirmed FRs are not scheduled until confirmed, or are built behind a feature flag.
- **potential_consequence:** Story writers build proposals as if decided, and reversing cancellation, credit or returns terms after build means rework in the highest-risk flows.

## Coverage map

This maps the requested focus areas to findings. It is an index, not a ranking.

- **Money correctness (VAT, rounding, FX and NGN 5, price lock, repricing, credit notes, partial and over payments):** F-15, F-16, F-17, F-18, F-20, F-22, F-23, F-24, F-25, F-26, F-27, F-28, F-29, F-30, F-31, F-32, F-33, F-44, F-50, F-51
- **Abuse and fraud (vouching, fake receipts, returns, Issue Codes, serials, counterfeit claims, staff misuse):** F-01, F-02, F-07, F-08, F-09, F-15, F-25, F-31, F-37, F-41, F-42, F-43, F-45, F-46
- **Staff bottlenecks and single points of failure:** F-03, F-04, F-36, F-37, F-46
- **Contradictions (requirements, journeys, glossary, success metrics, breakeven):** F-01, F-05, F-10, F-33, F-39, F-49, F-50, F-51
- **Data protection (NIN) and audit trail:** F-06, F-07, F-11, F-19, F-38, F-46, F-47
- **Nigerian operating reality (connectivity, courier delivery, one Store with nationwide ambition):** F-19, F-24, F-34, F-35, F-40, F-48
- **Not buildable without guessing (UX, architecture, stories):** F-04, F-10, F-12, F-13, F-14, F-16, F-21, F-24, F-30, F-36, F-48, F-52, F-53
