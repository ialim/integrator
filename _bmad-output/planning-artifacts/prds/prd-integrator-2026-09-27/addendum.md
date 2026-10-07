---
title: "Addendum: integrator PRD"
status: final
created: 2026-10-05
updated: 2026-10-07
---

# Addendum: integrator PRD *(working title)*

Depth that belongs to the PRD's story but not its main narrative. Business context stays in the product brief addendum. Find your part:

| If you are | Start with |
|---|---|
| An architect | Delivery surface, Integrations to plan for, Status model, Fulfilment notes |
| A story writer or tester | Test vectors, then the Edge-case register (82 rows in eight groups) |
| The founder | Level 3 verification: alternatives considered, Loyalty tier history, Scale reconciliation, Breakeven model |
| Counsel or the accountant | Wallet research, Returns and warranty research, Compliance notes |

Abbreviations: CAC, Corporate Affairs Commission; CBN, Central Bank of Nigeria; FCCPC, Federal Competition and Consumer Protection Commission; FX, foreign exchange; KYC, know your customer; NCC, Nigerian Communications Commission; NDPC, Nigeria Data Protection Commission; NIN, National Identification Number; OTP, one-time password; RMA, return merchandise authorisation; SON, Standards Organisation of Nigeria; SONCAP, SON Conformity Assessment Programme; WAT, West Africa Time.

## Delivery surface

- **Installable web app, not native apps.** The founder chose an installable web app for v1 (no app-store distribution). This is a progressive web app: one codebase, installed to the home screen from the browser. Native apps were not chosen because they need separate builds and store approvals for a launch cohort of 10 Integrators.
- **Offline design.** Offline scope is browsing the last-synced Catalogue, reading saved Documentation, and creating and editing Projects. Money actions (Proposals, Orders, Receipt uploads) require a connection so the Platform Rate recorded is current. FR-28 specifies the sync rules: merge per Room and per Project Item by server receipt time, recorded deletions, 30-day restore, and cache purge on removal. The architecture needs a local store partitioned by User and role. On iOS, installed apps have tighter storage and background-sync limits, so the app offers manual sync and warns about eviction (NFR-6).

## Integrations to plan for

- **NIN verification.** One automatic attempt through an identity-verification provider, then manual Admin review. Provider choice, cost per check, and failure modes belong to architecture. NIN is sensitive personal data under the Nigeria Data Protection Act 2023; NFR-3 sets the storage lifecycle and counsel should confirm retention and access rules.
- **Notifications.** Email plus WhatsApp. WhatsApp business messaging typically requires pre-approved message templates, an opt-in per number, and per-message costs. Meta charges for service replies and in-window utility messages from 1 October 2026, so FR-51 freezes a launch set of 12 events.
- **Bank statement import.** Under FR-41, Finance matches every Payment to a credit on the platform's bank statement; in v1 the statement arrives by upload or CSV import.
- **Payments v2 candidate.** Dedicated virtual bank accounts per Integrator from a licensed provider would let transfers reconcile to the right Business Account automatically, replacing manual Receipt matching. Not in v1.

## Status model

The founder's original status list was Paid, Approved, Processing, Ready for Pickup, Signed For, In-transit, and Delivered. Because Orders can ship in parts, the statuses are split by entity:
- **Order:** Awaiting Payment, Paid, Approved, Completed, Expired, Cancelled. "Completed" replaces the earlier "Delivered Order". See FR-45.
- **Shipment:** Processing, Ready for Pickup, Signed For, In-transit, Delivered, Delivery Failed, Returned to Store, Lost, Uncollected. See FR-45.
- **Payment:** Submitted, Confirmed, Unconfirmed, Reversed. See FR-41.
- **Credit Note:** Issued, Partially applied, Applied, Refunded, Expired. See FR-43.
- **Return Request:** Raised, Approved by the Integrator, Item Received, Under Investigation, Decided, Awaiting Finance, Awaiting RMA, Resolved, Rejected, Lapsed (plus Held, for a device that arrives after a lapse). See FR-60.
- **Counterfeit case:** Suspected, Confirmed, Cleared. See FR-66.

## Fulfilment notes

- **Stores and stock.** The founder expects more Stores as the platform serves integrators across Nigeria, so model Stores as an entity that can be added without a release (FR-56). If Stores later hold their own stock, that is a data migration (stock per location, transfers, choosing the fulfilling Store), so keep stock quantities behind one interface now.
- **Zone lookup.** Nigerian addresses lack reliable postcodes, so the zone is looked up from State and local government area, never geocoded from free text (FR-37, FR-47).
- **Flat rate risk.** A flat Zone Rate per Shipment loses money on heavy or bulky items (racks, UPS units, displays). Weight bands or a declared-value surcharge are the v2 fix.

## Level 3 verification: alternatives considered

The founder chose vouching with staff approval. Others discussed and not chosen for v1:
- A timed technical quiz (scalable, but answers can be looked up).
- Business registration number (solid when present, but many small integrators are unregistered sole traders).
- Portfolio evidence with a client reference (hard to fake, but staff-intensive).
- Optional licences and vendor certifications as a fast-track (no single trade body covers all integrators).

The gate protects both ordering and price visibility: Selling Prices appear only at Level 3 (FR-3). Orders are prepaid, so a fake buyer cannot cost the platform money by not paying, but the platform's own money-out paths (refunds, Credit Notes, replacements, freight reimbursement) can be abused by a vouched account, which is why FR-43, FR-52, and FR-72 add controls.

## Loyalty tier history

The first strawman (USD 2,000, 8,000, and 20,000 thresholds) assumed larger Orders. With a typical Order of about USD 500, thresholds were recalibrated to USD 1,500, 5,000, and 12,000 of spend over a trailing six-month window. Thresholds are in dollar equivalent so inflation and devaluation do not push Integrators up tiers without real growth. Discounts come out of the 25% markup, which is the platform's only revenue: the realized markup on cost is 25.0% at Base, 22.5% at Tier 2, 20.0% at Tier 3, and 17.5% at Tier 4, and a 10% Back-order Discount alone gives 12.5%. A Tier 4 discount on a USD 500 Order is USD 30, against the USD 12.50 the breakeven model allows for all discounts and FX slippage, which is why SM-C1 tracks the blended average.

## Scale reconciliation

NFR-1 sets the design target at 500 Integrators, 5,000 Products, and 500 Orders a month, about three times the month-60 target in SM-3 (157). The launch cohort does about 1.5 Orders per Integrator per month, while SM-2 and SM-3 imply about 2.0 per active buyer from month 12, so SM-1's 60% repeat target and SM-3 both rest on order frequency rising; the month-3 and month-6 checkpoints test that. At the first-assumed 20% growth there would be about 12 onboarded Integrators after year one; the PRD's path assumes 60%, which gives about 16.

## Breakeven model (inputs behind SM-6)

Benchmarks were researched on 5 October 2026 at NGN 1,330 per USD (CBN official rate 1,329 on 2 October; parallel market about 1,375 to 1,390). Ranges are published figures; the Used column and anything marked estimate are judgment within them. Many of the cost guides are marketing content, so treat all of this as benchmarks, not quotes. Amounts are in USD and before VAT, which passes through to the tax authority and is not revenue.

### Per-order economics

A typical Order is USD 500 at Wholesale Price. A 25% markup on cost gives a gross profit of USD 100 (20% of price). Tier and Back-order Discounts and FX slippage average about USD 12.50 per Order, which leaves a realized markup of about 21.9% on cost (the reason SM-C1's floor is 22%). After about USD 7.50 of handling, net contribution is about USD 80 per Order. Bank transfer means no payment-gateway fee, and delivery is assumed to pass through at the courier's cost via the Zone Rate.

### Cost inputs

The "used" column feeds the base case.

| Item | Benchmark range | Used |
|---|---|---|
| One-time build | Nigerian freelance or small team about USD 4,000 to 22,000 (typical 7,500 to 15,000); Nigerian agency about 7,500 to 45,000 (typical 15,000 to 30,000, four to six people, four to six months); offshore about 30,000 to 250,000 and up | USD 30,000 (top of the agency typical range, because offline sync, multiple roles, and an audit trail make this a wide scope). Low case 15,000; high case 45,000 |
| Maintenance and developer retainer | 15% to 20% of build per year; part-time developer retainer about USD 150 to 375 a month | About USD 450 a month |
| Hosting, storage, email, monitoring | About USD 60 to 120 a month at launch; USD 150 to 450 at scale | USD 90 a month |
| WhatsApp, SMS, NIN checks | WhatsApp in Nigeria per delivered message: utility about USD 0.0067, marketing about 0.0516; SMS OTP USD 0.005 to 0.009; NIN lookup about USD 0.04 to 0.20 (selfie match about 0.50) | Negligible at launch volumes, inside the hosting line |
| Staff, three roles, Lagos | Finance officer about NGN 110k to 400k a month; operations officer 100k to 320k; admin or support officer 80k to 250k; statutory on-costs about 12% to 20% | About USD 450 a month for three roles including on-costs |
| Space | Warehouse in Ikeja about NGN 45k to 85k per square metre per year, so 50 square metres is about USD 140 to 265 a month | USD 200 a month |
| Accounting, company secretary, data-protection registration | Accounting about NGN 80k to 250k a month; company secretary NGN 2k to 18k a month; NDPC registration NGN 10k to 250k a year | About USD 130 a month |
| Seminars and training events | No benchmark found | USD 300 a month (estimate) |
| Contingency | n/a | 10% |

Year-one fixed costs come to about USD 1,800 a month. Fixed costs then step up at each year boundary as staff and Stores are added, to about USD 2,300, 3,200, 4,300, and 5,500 a month in years two to five; each extra Store is roughly USD 385 a month (rent, an operations officer, utilities).

### Growth needed to recover the build cost inside five years

Operating basis, with Orders growing smoothly from 15 a month.

| Scenario | Build cost | Fixed costs | Minimum annual Order growth |
|---|---|---|---|
| Low (freelance build, lean) | USD 15,000 | 85% of base | about 41% |
| Base | USD 30,000 | base | about 51% |
| High (agency high end) | USD 45,000 | 125% of base | about 62% |

### Base case at different growth rates

| Annual Order growth | First positive month | Sustained from | Build cost recovered | Net of inventory float at month 60 | Peak funding including float |
|---|---|---|---|---|---|
| 20% | never | never | never | about USD -132,000 | keeps rising |
| 50% | 12 | 38 | month 60 | about USD -43,000 | about USD 55,000 (month 43) |
| 60% (recommended) | 11 | 26 | month 47 | about USD -2,000 | about USD 48,000 (month 30) |
| 70% | 10 | 15 | month 40 | about USD +46,000 | about USD 45,000 (month 20) |

"First positive month" is the first month contribution covers fixed costs; "sustained from" is the month after which every later month also covers fixed costs. The two differ because fixed costs step up at months 13, 25, 37, and 49 and briefly outrun Orders. Peak funding is the lowest point of cumulative cash after the build cost, operating deficits, and about 30 days of cost of goods held as stock (USD 400 per Order).

### Sensitivity at 60% and 70% growth

| Case | First positive | Sustained from | Build recovered | Net of float at month 60 | Peak funding incl. float |
|---|---|---|---|---|---|
| 60%, contribution USD 80 | 11 | 26 | 47 | -USD 1,800 | USD 47,900 (month 30) |
| 60%, contribution USD 72.50 (at SM-C1's former 20% floor) | 20 | 28 | 53 | -USD 29,500 | USD 56,200 (month 41) |
| 60%, USD 80 less a 2% revenue provision | 21 | 29 | 55 | -USD 38,800 | USD 60,200 (month 42) |
| 70%, contribution USD 80 | 10 | 15 | 40 | +USD 45,800 | USD 45,100 (month 20) |
| 70%, contribution USD 72.50 | 12 | 17 | 45 | +USD 11,400 | USD 51,400 (month 30) |
| 70%, USD 80 less a 2% revenue provision | 18 | 26 | 46 | about USD 0 | USD 53,900 (month 31) |

Rows 1 and 4 of this table repeat the 60% and 70% rows of the base-case table at full precision.

### Reading it

Operating breakeven needs about 23 Orders a month in year one, 29 in year two, 40 in year three, 54 in year four, and 69 in year five. At 60% growth Orders cross the year-one requirement around month 11, then fall below the year-two requirement when costs step up at month 13, so costs are covered sustainably only from about month 26. At 60% growth, funding peaks at about USD 34,000 before inventory (around month 16) and at about USD 48,000 including inventory (around month 30). Net of the stock the platform must hold, the 60% path only reaches about breakeven in cash at month 60, and a 2% provision for returns, warranty, and shrinkage pushes it negative; 70% growth is the comfortable path. Contribution per Order matters as much as growth. The USD 80 contribution assumes a blended realized markup of about 22%; at SM-C1's former 20% floor it would be USD 72.50.

### Not in the model

Returns, warranty, shrinkage, courier loss, and FX spread (a 2% provision case is shown above), founder pay, taxes other than VAT, bad debt, and funding costs. The USD 30,000 build estimate also does not price the returns workflow, the Credit Note ledger, bulk upload, WhatsApp, or loyalty tiers, which is why §6.1 stages the build.

### Main sources

- Build: tranarc.com software-development-costs-nigeria-pricing-guide-2026, musskart.com/mvp-development-cost-nigeria, kanopylabs.com how-much-does-it-cost-to-build-a-b2b-ecommerce-platform, fullstack.com software-development-price-guide-hourly-rate-comparison.
- Run costs: digitalocean.com pricing, developers.cloudflare.com/r2/pricing, resend.com/pricing, sentry.io/pricing, developers.facebook.com WhatsApp pricing, dojah.io/pricing, didit.me/pricing.
- Staff: profolio.ng salary guides, hivedesk.com salary-guides/nigeria.
- Overheads: rentinlagos.ng warehouse cost guide, brands.ng GIG Logistics price list 2026, ebconsults.ng CAC annual returns, forvismazars.com Nigeria tax reform, vatupdate.com Nigeria e-invoicing, andersen.com NDPC registration.
- FX: vanguardngr.com dollar-to-naira rate, 2 October 2026.

## Wallet research (v2 decision input)

A per-Integrator spendable balance was considered for partial payments and overpayments. Research on Nigerian rules found no published CBN exemption, threshold, or ceiling for a merchant holding customer prepayments for its own sales, and no explicit prohibition either: it is an unaddressed area, not a safe harbour. Wallet creation and e-money issuance sit under a Mobile Money Operator licence (reported capital of NGN 2bn plus a NGN 2bn CBN escrow deposit). The only caps found are KYC balance tiers for licensed wallets. CBN primary documents could not be read, and rules may have changed in 2024 to 2026.

Counsel should confirm three questions:
1. Whether an advance-credit ledger counts as deposit-taking.
2. How VAT applies to prepayments.
3. How balances are treated if the operator becomes insolvent.

v1 uses Credit Notes instead. Because a Credit Note held for up to 12 months is itself a balance, counsel should apply the same three questions to Credit Notes (FR-43) and add the question of forfeiture on expiry and how it is booked.

## Returns and warranty research (inputs behind FR-57 to FR-68)

Researched October 2026. Not legal advice. [S] means a published source and [I] means inference.

**Distributor norms [S].**
- Credit returns of non-defective goods run 30 days from invoice for sealed, unmarked items at TD SYNNEX, ADI, and Westcon-Comstor, and 90 days at Snap One.
- Dead-on-arrival is replaced within 30 days at TD SYNNEX, and vendor windows run 30 to 90 days.
- Transit-damage notice is 24 hours at Almo, 5 days at TD SYNNEX, and 5 working days at Westcon.
- Buyers pay return freight unless the distributor is at fault.
- Restocking fees run 15% to 25%, with none for sealed stock within 30 days at TD SYNNEX.
- Special-order and non-stock items, end-of-life and refurbished items, and licences are normally non-returnable.
- No Nigerian B2B distributor policy was found; one Nigerian retailer offers 7-day dead-on-arrival replacement.

**Warranty pass-through [S].** Manufacturer periods run 1 to 5 years (Ubiquiti 1 year, Control4 2 years, Crestron 3 years without labour, Hikvision 12 to 60 months). Clocks usually start at factory or distributor shipment, resellers support their own customers, and stock bought outside authorised channels can lose its warranty.

**Nigerian law (grounding only).**
- [S] The Federal Competition and Consumer Protection Act 2018 defines a consumer as someone buying goods other than for resale, and gives consumers 3 months to return non-compliant goods.
- [I] Integrators buying for resale are probably outside that definition, but mixed use is uncertain, and no business-buyer minimum return period was found.
- [S] Sale-of-goods law implies conditions of description, merchantable quality, and fitness; [I] excluding them by contract may be difficult, so counsel should vet credit-note-only terms and avoid "no refunds" wording.
- [S] SONCAP covers electrical and electronic goods at import, and selling non-compliant goods risks fines or jail; supplying communications equipment without NCC type approval breaches the Communications Act.
- Reports go to SON, the FCCPC, the NCC, and the brand owner.

**Counterfeit practice [S, I].** Distributors quarantine suspect stock, segregate stock from unauthorised sources, report confirmed fakes, keep records for years, and never restock or return them. Evidence is photos of the unit, label, serials, and packaging, the invoice and supplier, and a manufacturer serial check. Supplier chargeback is contractual; no standard process was found.

**Proposed policy [I].** The requirements in §4.13 follow this research and the review that followed. The windows are 5 days for transit damage or a wrong item, 30 days for dead-on-arrival (or the Supplier's window if shorter), and 7 days for change of mind. All three are judged from the first logged Case rather than from when the Return Request is raised, because the 48-hour Issue Code and Support's own response time would otherwise eat into the window. Remedies depend on the reason: replacement, then manufacturer RMA, then Credit Note, with cash only for confirmed counterfeits and non-delivery. A change of mind earns a Credit Note less 15% on the pre-VAT price. Freight is reimbursed only when the fault is the platform's, and only up to the Zone Rate. A warranty is passed through from the manufacturer, with no labour cover.

**Courier handover.** For Integrators far from the single launch Store, the courier option is what makes the 48-hour Issue Code window workable. PRD §8.3 asks whether far zones need a longer window.

**Main sources.** adiglobal.com terms-and-conditions, tdsynnex.com GSA price list returns terms, westconcomstor.com returns policy, snapav.com product returns, vendorportal.almo.com terms, scansource.com manufacturer warranty and DOA, slot.ng warranty policy, Hikvision RMA policy v6.0, ui.com support warranty, order.crestron.com standard limited warranty, us.aqara.com limited warranty, Mondaq consumer-protection laws in Nigeria 2022, olaniwunajayi.net newsletter on no-refund policies under Nigerian law, lawnigeria.com Sale of Goods Law of Lagos State, Bureau Veritas SONCAP guide, lawpavilion.com SON Act 2015, ncc.gov.ng equipment standardisation release, digikey.co.uk counterfeit product policy.

## Compliance notes

Reported by the research; confirm with an accountant. Input VAT is recoverable on services and fixed assets from 1 January 2026. Small-business VAT relief thresholds are reported inconsistently (NGN 25m, 50m, and 100m). At 15 Orders a month averaging about NGN 665,000, annual sales pass NGN 100m, so the platform should plan as VAT-registered, with monthly returns due by the 21st. E-invoicing for businesses under NGN 1bn is reported to start in July 2027.

## Test vectors

Used by FR-19, FR-30, and FR-38. Platform Rate NGN 1,330, VAT 7.5%.
- **Plain unit.** Supplier Cost USD 100, markup 25%, no discount: NGN 166,250.00 before VAT, VAT NGN 12,468.75, Selling Price NGN 178,718.75. Quantity 1,000: line total NGN 178,718,750.00.
- **Discounted unit.** Supplier Cost USD 45.00, markup 25%, Tier 2 discount 2%: USD 45 × 1.25 × 0.98 × 1.075 = USD 59.259375, which is NGN 78,814.96875 at the rate, so the unit Selling Price is NGN 78,814.97.
- **Rounding once per unit.** A unit of NGN 12.345 before VAT is NGN 13.270875 with VAT, so NGN 13.27 per unit, and quantity 7 gives NGN 92.89.
- **Proposal VAT line.** Three client lines of NGN 10.10 before VAT are NGN 10.86 each with VAT (10.8575 rounded), so the grand total is NGN 32.58. The pre-VAT lines add up to NGN 30.30, so the VAT line is 32.58 minus 30.30, which is NGN 2.28, and the grand total is the same with and without the VAT line. Computing VAT once on the 30.30 sum would give NGN 32.57, which is the failure this rule prevents.
- **Repricing.** An Order of USD 500 of goods at NGN 1,330 has an Amount Due of NGN 714,875 (500 × 1.075 × 1,330). With NGN 600,000 confirmed in time and a rate of NGN 1,400 at the repricing moment, the new Amount Due is NGN 752,500 and the balance is NGN 152,500.

## Edge-case register

Technical guards from the review for architecture and story writers. Each row says what must be handled; the FR carries the business rule. Row numbers are stable references.

### Accounts and identity (rows 1 to 14)

| # | Situation | Rule | FR |
|---|---|---|---|
| 1 | Two sessions redeem one Vouch Code or race the 2-active cap | Consume atomically (mark used only if unused and unexpired); lock the account while counting active codes | FR-5 |
| 2 | Same NIN on a second Business Account | Keyed hash with a unique index; duplicate goes to the manual queue flagged | FR-4 |
| 3 | Identity provider times out | Keep the attempt unused; retry with backoff; consume it only on a definitive mismatch | FR-4 |
| 4 | Manual NIN rejection | Status Rejected with a reason; resubmission capped at 3 and logged | FR-4 |
| 5 | Owner edits a verified name or NIN, or ownership moves | Re-verify before the change applies; the incoming Owner must pass NIN verification | FR-71 |
| 6 | Owner unreachable | Admin-assisted Change Owner with an ID check and a 72-hour cooling period | FR-71 |
| 7 | Close an account with open items | Blocked until Orders, Cases, and Credit Notes are settled | FR-72 |
| 8 | Erasure request versus an append-only audit trail | Audit rows hold identifiers and field differences only; erase or pseudonymise the profile and NIN | FR-53 |
| 9 | A removed or demoted User has a live session or unsynced edits | Revoke tokens; re-check the role on every sync; wipe the cache; hold queued edits for a Manager | FR-10, FR-28 |
| 10 | A Technician uses a device whose cache holds Selling Prices saved for a Manager | Partition the cache by User and role; purge price fields at logout or role change; never cache prices for Technicians | FR-11, FR-28 |
| 11 | An invite is accepted by a different contact | Bind the token to the invited contact; one Business Account per verified contact | FR-10 |
| 12 | The voucher is suspended after the code is submitted | The review screen shows the voucher's standing; the request is flagged for re-confirmation | FR-7 |
| 13 | Trivial or later-returned Orders make an account Established | Count Completed Orders net of returns with a minimum goods value; re-check at generation and at submission | Glossary, FR-5 |
| 14 | Staff leave or the last Admin is deactivated | Block removing the last Admin; revoke sessions and reassign queue items | FR-76 |

### Catalogue and stock (rows 15 to 29)

| # | Situation | Rule | FR |
|---|---|---|---|
| 15 | A bulk row carries Admin-only columns | Ignore them for Operations and note it per row | FR-15 |
| 16 | A bulk row has no unique SKU | Failed row; no duplicate Products are created | FR-15 |
| 17 | On-hand edited below reserved | Refuse without Admin override; store on-hand and reserved separately | FR-16 |
| 18 | Switch to a Supplier with no cost, or removal of the Active Supplier | Refuse | FR-14 |
| 19 | Platform Rate of zero or below, or a change of more than 10% | Refuse (a change above 10% only with an Admin override); changes above 3% need a second approver and a preview | FR-20 |
| 20 | Discount at or above 100%, negative markup, VAT outside 0 to 100, quantity below 1 | Refuse on the server | FR-13, FR-17, FR-26 |
| 21 | Discounts exceed the markup headroom | Floor the price at Supplier Cost × (1 + floor); warn the editor | FR-13 |
| 22 | Quantity exceeds available stock | Reserve what exists; the remainder is back-order if enabled, otherwise blocked with a message | FR-18, FR-34 |
| 23 | Two Orders confirm for the last unit | Conditional reservation (reserved plus n not above on-hand); the loser is credited | FR-18 |
| 24 | Back-order terms edited after opt-in | Snapshot the discount and Lead Time on the Order Line | FR-17 |
| 25 | Inbound back-order stock short of Paid demand | Allocate first-in first-out by Paid time; partial Shipment; notify the rest | FR-17, FR-39 |
| 26 | Back-order more than 7 days past its ship-by date | Notify; offer cancellation with a Credit Note; Operations credits a line the Supplier cannot supply | FR-57 |
| 27 | The Approval stock check finds a line missing | Credit Line action; approve the rest | FR-44 |
| 28 | A Project or Cart line is unorderable at Order creation | Block with a list; never drop silently | FR-34 |
| 29 | A Product is flagged counterfeit while in open Orders, Carts, or Proposals | Block confirmation and shipment, credit Paid lines, mark Proposals stale | FR-66 |

### Pricing and spend (rows 30 to 33)

| # | Situation | Rule | FR |
|---|---|---|---|
| 30 | Per-line rounding differs from the total | Round each unit once to the kobo (half-up); total is the sum; the VAT line is the total minus the sum of the pre-VAT lines, so any kobo difference falls on it | FR-19, FR-30 |
| 31 | Spend lands exactly on a tier threshold | Half-open bands | FR-21 |
| 32 | Spend basis unclear | Goods only, excluding VAT and delivery, in USD at each Order's stored rate, net of returns and credits, counted when the line's Shipment is Delivered or Signed For | FR-21 |
| 33 | An Order is created between tier calculation and the shown effective date | Spend is snapshotted at the calculation date; the Order uses the tier effective at creation | FR-22, FR-24 |

### Projects and Proposals (rows 34 to 39)

| # | Situation | Rule | FR |
|---|---|---|---|
| 34 | Two devices edit different Rooms offline, or clocks disagree | Merge per Room and item using server receipt time; keep the prior version | FR-28 |
| 35 | An offline edit adds a Product later Out of Stock or flagged | Keep it flagged Unavailable; never discard | FR-28 |
| 36 | A Proposal or Order is requested for an empty Project or zero quantities | Refuse | FR-29, FR-34 |
| 37 | Project items change after the Proposal | Mark the Proposal stale; the Order uses the current rate | FR-35 |
| 38 | Proposal generated on the 31st, or Order placed on the expiry date | Expire at end of day WAT, one calendar month later, clamped to month end; compare using server time | FR-32 |
| 39 | Trade Name or logo missing | Trade Name is required; name-only header without a logo | FR-70 |

### Orders and payments (rows 40 to 58)

| # | Situation | Rule | FR |
|---|---|---|---|
| 40 | Order-create is retried, double-submitted, or run from one Cart by two Users | Idempotency key; consume the Cart in the same transaction; Carts belong to one User | FR-33, FR-34 |
| 41 | The rate, tier, or Proposal validity changes between display and confirm | The Order Quote carries the rate version, tier, Proposal, project revision, and Back-order terms; recompute on confirm and reject with the new Order Quote if anything changed | FR-34 |
| 42 | A late payment reprices an Order | Reprice once, at a fixed instant stamped with the rate used; balance equals the new Amount Due minus confirmed naira; a negative balance becomes a draft Credit Note; freeze until terminal; notify | FR-38 |
| 43 | Hour 24 passes while an in-time Receipt awaits review | Keep the creation price until Finance decides; reprice only if the lock test fails | FR-38 |
| 44 | An upload straddles hour 24 or 48 | Classify by the server-recorded upload start, not the device clock or retry time | FR-38 |
| 45 | Every Receipt is Unconfirmed or the balance never arrives | Expire 48 hours after the last upload or decision, at most 7 days after creation; confirmed money becomes a Credit Note | FR-38 |
| 46 | An in-time Receipt is rejected for a fixable reason | A corrected re-upload within 24 hours of the notice keeps the original time if the full amount matches | FR-38 |
| 47 | A transfer is short by bank charges | A shortfall within the Finance-set tolerance counts as covered; it is written off and audited | FR-38 |
| 48 | The shipping choice, address, or Store changes after creation | Amend before Approval; only the delivery charge changes | FR-75 |
| 49 | Shipment count changes after delivery cost was charged | Fix Shipments at creation; return a freed Shipment's delivery cost; absorb extra Shipments | FR-37, FR-39 |
| 50 | Cancel with confirmed or pending Payments | Credit Note for the confirmed amount; pending Receipts go to Finance | FR-57 |
| 51 | Cancel races Approve, final confirmation, or expiry | Transition by compare-and-set on status; the loser receives the current status | FR-57 |
| 52 | Cancellation requested while Shipments progress | Freeze Shipment transitions until Operations decides | FR-57 |
| 53 | An Approved Order mixes cancellable lines and a Supplier-ordered back-order | Cancel per line with a line Credit Note; Operations sets the Supplier-ordered marker | FR-57, FR-74 |
| 54 | A Receipt or bank credit arrives for an Expired, Cancelled, or Paid Order | Unmatched queue for Finance; terminal Orders never auto-transition | FR-41 |
| 55 | The same transfer is evidenced twice, or covers two Orders | Unique bank reference; Finance splits with allocation records | FR-41 |
| 56 | Finance reverses a confirmed Payment, or two reviewers decide at once | Compare-and-set from Submitted; Reversed state; a Paid Order returns to Awaiting Payment before Approval | FR-41 |
| 57 | A Credit Note is applied, then the Order is cancelled | Restore the amount to the original note with its original expiry; evaluate validity at the request time | FR-43 |
| 58 | Apply and refund requested together | Decrement the balance in one transaction; refund only to a sending account on record | FR-43 |

### Fulfilment (rows 59 to 65)

| # | Situation | Rule | FR |
|---|---|---|---|
| 59 | A Shipment status is set out of sequence | Allow only listed transitions; reversals need an Admin reason | FR-45 |
| 60 | Courier fails, recipient refuses, parcel lost | Delivery Failed, Returned to Store, Lost; Lost opens a non-delivery Case | FR-45 |
| 61 | Pick-up never collected | Reminder at 7 days, Uncollected at 14, cancel and restock after 30 | FR-45 |
| 62 | Completed evaluated over an empty or partly credited Shipment set | Completed only if every non-credited line is in a Delivered or Signed For Shipment and at least one exists; all lines credited means Cancelled | FR-44, FR-45 |
| 63 | A Store is deactivated while Shipments reference it | Block unless Operations reassigns with notice; require a replacement Main Store | FR-56 |
| 64 | Serials are mistyped, duplicated, or miscounted | Count equals quantity; unique across active dispatches; normalise case and separators; correction needs a reason and a second person | FR-65 |
| 65 | Documentation is replaced after being saved offline | Version it; saved copies show Superseded or Withdrawn | FR-48 |

### Notifications and audit (rows 66 and 67)

| # | Situation | Rule | FR |
|---|---|---|---|
| 66 | A notification goes to a Technician or a removed User, or WhatsApp fails | Route by role; amounts only to Owners and Managers; skip removed Users; fall back to email | FR-51 |
| 67 | Staff change the VAT rate, windows, Zone Rates, stock, or Shipment status | All are audited events with entity identifiers and field differences | FR-53 |

### Returns (rows 68 to 82)

| # | Situation | Rule | FR |
|---|---|---|---|
| 68 | A Manager declines a Technician's request | Rejected with a reason shown to the Technician; pending requests expire with the Issue Code and both are told | FR-58 |
| 69 | The approver corrects the suggested Order Line | The line must contain the serial or Product bound to the code, belong to the same Business Account, and be delivered | FR-58 |
| 70 | A Technician raises a Return Request inside the reporting window, but a Manager approves it after the window closes | The window is judged at the first Case time; approval stays valid until the Issue Code lapses | FR-59, FR-68 |
| 71 | Window arithmetic | Calendar days in West Africa Time, ending 23:59:59, counted from the courier-confirmed delivery date recorded by Operations | FR-59 |
| 72 | A new Issue Code is generated after a lapse | It keeps the original first-Case time; at most 3 codes per Order Line | FR-68 |
| 73 | A device reaches a Store after the Request lapsed | Held state for Support triage | FR-60 |
| 74 | A second request on one serial, or quantity above delivered | Blocked | FR-58 |
| 75 | Investigation shows a different cause | Support reclassifies with a note; re-evaluate window, exclusions, and remedy | FR-60 |
| 76 | Decided remedy needs Finance or a manufacturer RMA | Awaiting Finance and Awaiting RMA states with an owner; Resolved only when the credit, replacement, or repair is delivered | FR-60 |
| 77 | A unit tests fine or is rejected and never collected | Freight is charged on the next Amount Due or deducted from a Credit Note; unclaimed after 30 days is closed per the terms | FR-60 |
| 78 | A replacement ships against a Completed Order | Zero-value Replacement Shipment, excluded from Completed counts and spend, new serial recorded, original warranty inherited | FR-61 |
| 79 | A Pick-up Shipment has no dispatch event, or the Product has no Warranty Period | Warranty starts at Ready for Pickup; no Warranty Period means no warranty and the claim is refused with a reason | FR-64 |
| 80 | A suspected counterfeit is cleared or confirmed | Suspected, Confirmed, Cleared with who and when; Cleared lifts the hold; Confirmed enables the cash refund | FR-66 |
| 81 | A Shipment marked Delivered never arrived | Non-delivery claim with its own window, an Operations check against courier proof, and a Finance refund path | FR-58, FR-61 |
| 82 | An Admin edits a return window mid-case | Windows are snapshotted on the Case; changes apply only to later Cases | FR-59 |
