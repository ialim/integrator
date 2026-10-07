# Input reconciliation: product brief vs PRD

**Input reconciled:** integrator product brief (`brief.md`, final, 2026-09-27) and its `addendum.md`.
**Checked against:** `prd.md` (draft, updated 2026-10-05) and the PRD `addendum.md`.
**Method:** All four files were read in full. Each brief idea, promise, risk and scope statement was traced into the PRD, and every "absent" finding below was confirmed by searching the PRD text for the concept.

**Type key:** (a) brief idea, promise, risk or scope statement omitted or contradicted without saying so; (b) qualitative idea dropped by the requirement structure; (c) wording drift that changes meaning.
**Severity key:** High = a risk or mechanism the brief names outright is carried too thinly to act on. Medium = a brief promise or measure is softened or only half-carried. Low = wording or a small omission.

**Excluded as deliberate, documented changes:** Nigeria-only launch; staff roles and the Support split; VAT; the USD pricing formula, 25% Platform Markup, Platform Rate and NGN 5 tolerance; returns, warranty and cancellation policy; success-metric targets; Stores, Pick-up and Delivery Zones; three-level access, NIN and vouching; Business Account roles; Credit Notes; packaged "solutions" deferred to v2 (§5, §6.2); installable offline web app; Proposal as a one-month PDF with only the latest kept; Cart not convertible to a Project.
**Excluded as documented deferral:** PRD §0 says the problem statement, competitive grounding and regional research live in the brief, so their absence from the PRD body is not reported. Risks, Vision and Success Metrics are PRD content and are reported below.

## Summary

| ID | Sev | Type | Gap | PRD home |
|---|---|---|---|---|
| R-01 | High | b, c | FX: "actively managed" pricing is undefined; SM-C1 cannot be computed; platform's own FX exposure is not in Risks | FR-20, NFR-4, SM-C1, Risks |
| R-02 | High | b | Trust and counterfeit: "a platform that stands behind what it sells" is reduced to a returns procedure; no authenticity guardrail, no supplier or batch traceability | Risks, Constraints, FR-12, FR-14, FR-66 |
| R-03 | Medium | b | Brief's own "not a moat" risk, founder-network dependence and the loyalty flywheel are absent | Risks, §1, FR-21 |
| R-04 | Medium | b, c | Vision's later phases (marketplace, trade credit from trading history, second act, West Africa) are not carried; Non-Goals read as permanent | §1, §5, §6.2 |
| R-05 | Medium | a, c | Success measures redefined (value became count, suppliers dropped) and the user-outcome measures are missing | §7 SM-2, SM-4, SM-5 |
| R-06 | Medium | a | "No supplier order or stock commitment before payment" is enforced for stock only; client payment being off-platform is not stated | FR-18, FR-44, §5 |
| R-07 | Medium | a | Stock availability display and Catalogue search are not required | FR-2, FR-16 |
| R-08 | Medium | c | Documentation softened from "every product carries installation and configuration documentation" | Glossary, §4.9, FR-49 |
| R-09 | Medium | c | FR-30 never-show list omits the Integrator Margin and the Integrator's cost | FR-30 |
| R-10 | Low | c | "Residential + light commercial" lost; "complex commercial" became "large commercial" | §6.1, §6.2, §2.2 |
| R-11 | Low | c | Non-goal "Not a design ... tool for the field" contradicts field use; no responsive NFR | §5, §4.12 |
| R-12 | Low | a | Non-price tier benefits question silently settled as discount-only | FR-21, §8 Q10 |
| R-13 | Low | a | Supply-side regional risks (customs, SON, NCC, CBN FX access, courier fragility) are absent | Risks |
| R-14 | Low | b | Suppliers as a secondary beneficiary ("route to market") not carried | §2, §6.2 |
| R-15 | Low | c | "Bill of materials" never named; no consolidated Project parts list | FR-25 to FR-27 |
| R-16 | Low | c | Single project-wide margin not recorded as a decision | FR-29, §6.2 |

## Gaps in detail

### R-01 (High, b/c) FX: "actively managed" pricing is not operationalised, and its success measure cannot be computed

- **Brief:** Solution: "pricing is actively managed rather than fixed — repriced deliberately against currency movement instead of eroding margin silently." Scope: "Actively-managed, currency-aware pricing". Success Criteria: "Realized margin held against currency movement ... not just a stated intention". Problem: "a supplier quoting in naira today can be underwater tomorrow if they don't reprice deliberately."
- **PRD today:** FR-20 lets Finance change the Platform Rate "only when it has moved meaningfully", with no definition, review cadence, reference rate or rate-age indicator. NFR-4 stores only the customer-side rate, discount and VAT, so SM-C1 (realized markup after FX moves) has no cost-side FX data to compute from. The Risks entry lists only customer-side mitigations. Supplier Cost is USD-only (Glossary) with no statement on naira-quoted suppliers, and the platform's own exposure between Price Lock (FR-38) and supplier payment, longest for Back-order Items, is not named.
- **PRD should have it in:** FR-20, NFR-4, SM-C1, Risks "Naira volatility", Glossary "Supplier Cost".
- **Fix:** Define "meaningfully" (or a Finance review cadence plus a rate-age indicator), record the actual cost-side rate per Order so SM-C1 is computable, and add the lock-to-purchase exposure and naira-quoted suppliers to Risks.

### R-02 (High, b) Trust and counterfeit: "stands behind what it sells" is reduced to a returns procedure

- **Brief:** Problem: "no trusted, verified sourcing channel serving this trade — buyers currently rely on personal relationships and reputation rather than a platform that stands behind what it sells." Addendum: counterfeit is "a real argument for provenance/verified-sourcing as a differentiator", with electrical and component categories flagged as safety-critical.
- **PRD today:** Risks mitigates counterfeit with founder-supplier curation and FR-66, and says "A provenance feature is not in v1" (a documented deferral, but the differentiator and safety framing are gone). The "Trust" guardrail in Constraints is about vetting buyers (Levels 2 and 3), not assurance of what is sold, so the brief's meaning of trust is flipped. FR-64 disclaims any platform warranty beyond dead on arrival. FR-66 acts on a "batch" and "notifies the Supplier", but FR-12 has no batch or goods-in Supplier record, and FR-14 lets Admin switch the Active Supplier with no vetting criterion.
- **PRD should have it in:** Risks "Counterfeit product"; Constraints (an authenticity guardrail); FR-12, FR-14, FR-66; the "Terms" guardrail.
- **Fix:** Add an authenticity guardrail stating what the platform stands behind (authorised-channel sourcing, the FR-61 refund for fakes), have Operations record Supplier and batch at goods-in so FR-66 can act, and require a vetting check before a Supplier becomes Active.

### R-03 (Medium, b) Brief's "not a moat" risk, founder-network dependence and the loyalty flywheel are absent

- **Brief:** What Makes This Different: "That's an advantage of trust and access, not a defensible technology moat, and it should be treated as such: it buys time and a real starting position, not permanent protection once the model is proven and others notice the gap." Also: "the more of their volume runs through integrator, the better their pricing gets, which is the flywheel the business actually depends on."
- **PRD today:** Risks has no competitive-response or founder-dependence entry; the founder's network appears only as the Cold start mitigation. The loyalty flywheel (consolidating volume to earn pricing) appears nowhere in §1 or in FR-21's description, and no measure tests it.
- **PRD should have it in:** Risks and Mitigations; §1 Vision; FR-21 description.
- **Fix:** Add a Risk "No technical moat; heavy reliance on the founder's network" with a mitigation tied to loyalty consolidation, and state the flywheel as FR-21's design intent.

### R-04 (Medium, b/c) Vision's later phases are not carried, and Non-Goals read as permanent

- **Brief:** Vision: the catalogue grows "from the founder's own initial contacts into a genuinely broad multi-supplier marketplace"; "the platform's own trading history becomes the trust and credit signal the region currently lacks — the foundation for extending real trade credit or financing to integrators down the line"; industrial and complex commercial as "a natural second act, built once the core distribution and design model is proven"; West Africa as "the next phase"; B2C hire-an-integrator as "the natural next surface once there's a real base of trusted, verified integrators".
- **PRD today:** §1 has no path beyond v1. §6.2 lists industrial ("a later phase"), B2C ("v2 roadmap") and West Africa as out of scope, but the marketplace and trade-credit phases appear only as §5 non-goals worded as identity ("Not an open marketplace"; "Not a payments company: ... no financing or trade credit"). The brief marks these "out for v1" and treats the later steps as the point of the Vision.
- **PRD should have it in:** §1 Vision (or a "Beyond v1" note in §6.2); the §5 heading.
- **Fix:** Add a short sequenced "Beyond v1" paragraph with the brief's gating conditions, retitle §5 "Non-Goals (v1)", and note that per-account Order and Payment history is retained as the future credit signal.

### R-05 (Medium, a/c) Success measures redefined, and user-outcome measures missing

- **Brief:** Success Criteria: "Order value mix between designed-project orders and direct-buy orders"; "Catalogue breadth (number of suppliers and SKUs live)"; "Active buying integrators (placed at least one order in the trailing window)". User outcome (Who This Serves): "less time spent chasing suppliers, fewer pricing mistakes", with the Problem pain "clients who wait days for a proposal that should take an hour".
- **PRD today:** SM-4 measures the share of Orders, not Order value, so it cannot show whether the designer drives the money. SM-5 counts active Products only and drops suppliers, although multi-supplier aggregation is the core promise. SM-2 requires a Delivered Order rather than a placed one, which lags Back-order and delivery time. No measure in §7 reflects speed or fewer pricing mistakes.
- **PRD should have it in:** SM-2, SM-4, SM-5; a new secondary user-outcome metric.
- **Fix:** Report SM-4 by Order value as well as count, add active Suppliers to SM-5, note the SM-2 "Delivered" choice as intentional, and add a user-outcome measure such as median time from Project creation to Proposal generation.

### R-06 (Medium, a) "No supplier order or stock commitment before payment" is enforced for stock only

- **Brief:** Scope: "Order confirmation gated on integrator payment — no supplier order or stock commitment before that." Solution: the gate "protects the platform from committing stock or placing supplier orders against business that hasn't actually closed"; "Client-side payment happens entirely off-platform".
- **PRD today:** FR-18 gates stock reservation on Paid but is silent on when the platform places Supplier orders. The only trace is FR-57 ("a Back-order Item the platform has already ordered from a Supplier"), so the trigger for Back-order purchasing is unspecified. Nothing states that the client's payment to the integrator is off-platform.
- **PRD should have it in:** FR-18 or FR-44 (and FR-36 for Back-order Items); §5 Non-Goals.
- **Fix:** Add "the platform places no Supplier order for any Order Line before its Order is Paid", and add a non-goal that client-to-integrator payment is off-platform.

### R-07 (Medium, a) Stock availability display and Catalogue search are not required

- **Brief:** Problem: "There is no single source of truth for what's in stock, what it costs wholesale, or what it should cost the client." Solution: "one place to see what's available and at what price".
- **PRD today:** FR-16 defines In Stock and Out of Stock and FR-17 shows Lead Time for Back-order Items, but no FR says Integrators see stock status in the Catalogue, and nothing requires searching or filtering a Catalogue of 500+ Products (FR-2 says only "browse").
- **PRD should have it in:** FR-2 and FR-16, or a new Catalogue discovery FR in §4.2.
- **Fix:** Add "Integrators see each Product's stock status (In Stock, Out of Stock, Back-order with Lead Time) wherever it is priced" and a search-and-filter requirement.

### R-08 (Medium, c) Documentation softened from "every product carries installation and configuration documentation"

- **Brief:** Solution: "Every product and packaged solution carries the installation and configuration documentation an integrator needs to do the job right the first time." Scope: "Installation/configuration documentation attached to catalogue products."
- **PRD today:** §1 keeps "attached to every product", but §4.9 says "Every Product can carry Documentation", FR-49 accepts "Documentation coming soon" with no limit, the Glossary says "installation material", and "configuration" appears nowhere (only "Back-order configuration"). No coverage target or Operations flag exists. (The packaged-solution part is a documented v2 deferral and is not reported.)
- **PRD should have it in:** Glossary "Documentation"; §4.9; FR-48, FR-49; §7.
- **Fix:** Restore "installation and configuration" in the Glossary and §4.9, and add a launch-readiness target for Documentation coverage with an Operations list of Products that have none.

### R-09 (Medium, c) FR-30 never-show list omits the Integrator Margin and the Integrator's cost

- **Brief:** Solution: "the client sees a clean, priced proposal; the integrator's cost and margin stay theirs."
- **PRD today:** FR-30 never shows "Wholesale Prices, Tier Discount, or Platform Markup". It omits the Integrator Margin and the Integrator's own cost (the Selling Price they pay). The test "No cost-side figure can be derived from a text search of the PDF" covers cost only by inference.
- **PRD should have it in:** FR-30 and its consequences.
- **Fix:** Add Integrator Margin and the Integrator's cost to the never-show list, with a testable consequence for each.

### R-10 (Low, c) Scope boundary wording: "residential + light commercial" lost

- **Brief:** Scope: "scoped to IT, AV, security, and home-automation products (residential + light commercial)". Out: "Industrial automation and complex commercial projects (multi-resident buildings, hotels, office complexes)".
- **PRD today:** "residential" and "light commercial" appear nowhere. The exclusion is shortened to "large commercial projects" (§2.2, §6.2) with the three examples dropped, so there is no working line for what Operations loads or which jobs the designer is for.
- **PRD should have it in:** §6.1 Catalogue bullet; §6.2; §2.2.
- **Fix:** Restore "residential and light commercial" in §6.1 and the three examples in §6.2.

### R-11 (Low, c) Non-goal contradicts field use, and there is no responsive requirement

- **Brief:** Addendum, Form Factor: "Mobile/field access matters ... but on-site room-measurement tooling (e.g. AR-based measurement) is explicitly not required for v1. This should inform the architecture/PRD's platform and responsive-design decisions".
- **PRD today:** §5 says "Not a design or measurement tool for the field", which read literally excludes the field design that FR-28 and UJ-5 require. No requirement covers responsive layouts, and reaching existing Proposals and Orders on site is not addressed (offline scope is Catalogue, Documentation and Projects only).
- **PRD should have it in:** §5 Non-Goals; §4.12 or Cross-Cutting NFRs.
- **Fix:** Reword the non-goal to "no on-site room-measurement tooling (AR or otherwise)", add a responsive, phone-first NFR, and say whether the last Proposal PDF can be saved offline.

### R-12 (Low, a) Non-price tier benefits question silently settled

- **Brief:** Addendum open question: "Whether tier benefits include anything beyond price (e.g. shipping, support priority — as Snap One's program does) or are purely a discount percentage."
- **PRD today:** FR-21 answers window, tier count and spread, but the third question is settled as discount-only without saying so, and Open Question 10 covers only thresholds and discounts.
- **PRD should have it in:** FR-21 description or §8 Q10.
- **Fix:** State "tier benefits are price-only in v1" in FR-21, or add the question to Open Question 10.

### R-13 (Low, a) Supply-side regional risks are absent from Risks

- **Brief:** Addendum regional context: "documentation inconsistency is the #1 clearance-delay driver at Nigerian ports; mandatory SON conformity assessment applies to electronics/machinery; FX access constraints from CBN controls compound import friction. Freight-tech is workable but fragile".
- **PRD today:** Regional research is deliberately left in the brief, but the Risks list carries none of its supply-side items. SON and NCC rules appear only in the returns research, and delivery depends on one courier partner (FR-37, PRD addendum).
- **PRD should have it in:** Risks and Mitigations; optionally FR-12 (compliance status per Product).
- **Fix:** Add a Risk "Import, compliance and courier-partner fragility" with Lead Time and Zone Rate as the named mitigations.

### R-14 (Low, b) Suppliers as a secondary beneficiary are not carried

- **Brief:** Who This Serves: suppliers are "a secondary beneficiary ... integrator becomes their route to market."
- **PRD today:** §2.2 lists Suppliers only as non-users; their benefit and any supplier-facing output (for example sell-through reporting) is not mentioned.
- **PRD should have it in:** §2 Target User; §6.2.
- **Fix:** Name suppliers as a secondary beneficiary in §2 and list supplier-facing reporting among the §6.2 deferred items.

### R-15 (Low, c) "Bill of materials" never named

- **Brief:** "a room-by-room project designer that builds a bill of materials directly from that catalogue".
- **PRD today:** "bill of materials" appears nowhere. Projects hold Rooms and Project Items, with Project totals for Owners and Managers and items and quantities for Technicians, but no consolidated parts list across Rooms.
- **PRD should have it in:** FR-25 to FR-27; Glossary "Project".
- **Fix:** State that a Project yields a consolidated parts list across Rooms (the Technician view can serve as it) and say whether it can be exported.

### R-16 (Low, c) Single project-wide margin not recorded as a decision

- **Brief:** Scope: "integrator sets margin; client sees a clean priced quote". Addendum cites iPoint's "configurable margins".
- **PRD today:** FR-29 fixes one Integrator Margin for the whole Project with no rationale, and per-Room or per-line margins are not listed in §6.2.
- **PRD should have it in:** FR-29; §6.2.
- **Fix:** Record the single margin as a deliberate v1 simplification and list per-Room or per-line margin under §6.2.

## Adjacent findings (inside the PRD, not brief-vs-PRD)

- **X-1 (Medium) Level 2 shows wholesale prices, contradicting the stated purpose of the Level 3 gate.** §2.2 says "Retail and non-integrator buyers are kept out of wholesale pricing by the Level 3 vouching gate", and the PRD addendum says the gate's job is "mainly to keep wholesale prices from non-integrators". But FR-3 and the Glossary make prices visible at Level 2, which needs only a NIN-verified profile. The brief's premise is wholesale to integrators only. Fix: either gate price visibility at Level 3, or correct the §2.2 and addendum wording and accept the exposure.
- **X-2 (Low) "Markup on goods is the only revenue" versus the restocking fee.** §1 and §5 state it absolutely, while FR-61 adds a 15% restocking fee. Fix: note the exception in §1 and §5, or accept it as cost recovery.

## Brief items verified as carried (no action)

- Revenue model: markup on goods only, no platform fee or subscription (§1, §5).
- Direct buy without a design pass (UJ-3, FR-33).
- Room-by-room Project design from the Catalogue (FR-25 to FR-28).
- Margin-aware client Proposal that is a quote, not a commitment (§4.5, FR-29 to FR-32, FR-18).
- Loyalty on trailing-window spend that can fall as well as rise (FR-21, FR-22).
- Discounted back-order for items not in stock (FR-17, FR-26, FR-36).
- Stock commitment gated on payment (FR-18, FR-42, FR-44).
- Repeat-purchase rate as the primary success signal (SM-1, closing line of §1).
- Realized margin under FX moves (SM-C1), active buyers (SM-2) and Catalogue breadth (SM-5), with the changes noted in R-05.
- Nigeria only; no end-client accounts or B2C; no AR; no BNPL or trade credit; no self-service supplier onboarding; industrial and complex commercial out (§5, §6.2), with wording notes in R-04, R-10 and R-11.
- Cold-start seeding from the founder's network (Risks, FR-8); counterfeit risk and procedure (Risks, FR-66), with the depth notes in R-02.
- Form factor: web first, installable, usable in the field (§4.12, FR-28, FR-50, FR-55).
- Loyalty window and tier count answered (FR-21); alternatives to vouching documented in the PRD addendum.

## Considered and not reported

- Proposal delivered as a downloadable PDF rather than a share link: documented in UJ-1 and §2.2, and "shareable" is satisfied.
- Order created before payment (Awaiting Payment): the PRD defines Order as a request and gates reservation and approval at Paid, which keeps the brief's intent. Only the supplier-order gap (R-06) remains.
- Integrator Margin applied over the VAT-inclusive Selling Price instead of "on top of the wholesale price": a documented pricing-formula change (Glossary, FR-30 assumption).
- Loyalty counted on Delivered spend only: documented in FR-21.
- "Built and curated directly by the founder" became "curated by the platform team" (Operations): a documented staff-role change.
