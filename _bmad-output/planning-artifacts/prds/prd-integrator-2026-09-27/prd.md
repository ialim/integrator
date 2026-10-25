---
title: "PRD: integrator"
status: final
created: 2026-09-27
updated: 2026-10-09
---

# PRD: integrator
*Working title — confirm.*

## 0. Document Purpose

This PRD turns the integrator product brief into buildable requirements. It is written for the founder and product owner, for any investor or partner who needs the detail behind the brief, and for the downstream owners of UX design, architecture, and epics and stories. It builds on the finalized brief and its addendum at `_bmad-output/planning-artifacts/briefs/brief-integrator-2026-09-27/` and does not repeat them: the problem statement, competitive grounding, and regional research live there. Vocabulary is anchored in the Glossary (§3) and used verbatim everywhere else. Features are grouped in §4 with functional requirements (FRs) numbered globally and nested beneath them; FR numbers are stable references and are not always in document order.

Every FR carries a status and a build priority. **Confirmed** means the founder decided it. **Default** means a guard added during review that builds as written unless the founder overrides it. **Proposed** is reserved for a rule the founder has not yet approved; none is Proposed at this revision. Build priority 1, 2, or 3 follows §6.1. Inferences made without confirmation are tagged `[ASSUMPTION]` inline and indexed in §9. Technical-how, the edge-case register for architecture, rejected alternatives, the breakeven model, and regulatory research are in `addendum.md`.

**Status at this revision (7 October 2026).** Every FR is Confirmed or Default, so all can go to epics now, though FR-43, FR-59, FR-61, FR-63, FR-66, and FR-77 are marked for counsel or accountant review. All 15 founder decisions in §8.1 are Confirmed. Other open items are in §8.2 and §8.3. Build order is in §6.1.

**Update (9 October 2026).** The settled positions from the UX design (`prd-change-requests.md` in the UX folder) and the architecture spine's product-facing decisions are folded into 41 FRs, each marked "From UX design and architecture". FR-27 and FR-60 change existing text. The UX's still-open items are §8.3 questions 6 to 18.

## 1. Vision

integrator is a B2B wholesale and project-design platform built by a systems integrator for systems integrators, launching in Nigeria. An integrator builds a job room by room as a bill of materials with live wholesale pricing, turns it into a client-ready Proposal with their own margin applied, and orders the equipment from one place. The platform does this with the realities of its market built in: prices tied to the dollar, a deliberate rule for what happens when the naira moves, and payment by bank transfer confirmed by people.

The product exists because integrators today stitch a single job together across spreadsheets, supplier phone calls, hand-calculated markups, and whatever installation guidance came in the box. integrator replaces that with one flow, from first room to delivered equipment, plus installation and configuration documentation attached to every product. Revenue is the markup on goods: there is no platform fee and no subscription. Restocking fees on change-of-mind returns exist only to recover cost. The platform's pull on an Integrator is a flywheel: the more of their volume runs through integrator, the better their pricing gets.

Version 1 is deliberately narrow in category and market: IT, AV, security, and home-automation equipment, sold to verified integrators in Nigeria, with a Catalogue curated by the platform team. It succeeds if integrators come back to buy again.

Beyond v1, in the order the brief sets and only once the earlier step is proven:
1. The Catalogue grows from the founder's own supplier contacts into a broad multi-supplier marketplace.
2. The platform's per-account order and payment history, which is retained from day one, becomes the trust signal that could support trade credit.
3. Industrial automation and complex commercial projects (multi-resident buildings, hotels, office complexes) become a second act.
4. West Africa follows Nigeria.
5. End clients could one day hire an integrator through the platform.

## 2. Target User

### 2.1 Jobs To Be Done

- When I'm quoting a job, I want to build it room by room against real wholesale prices and send my client a professional Proposal quickly, so I don't lose the job while I'm chasing suppliers for prices.
- When my client says yes, I want to order everything for the job in one step and see exactly what changed since I quoted, so a move in the naira never squeezes my margin without warning.
- When I just need parts, I want to buy them directly without building a project.
- When my team installs, I want the right installation and configuration guide at hand, even on site with no signal, so the install goes right the first time.
- When I buy most of my equipment from one supplier, I want that volume to earn me better pricing, so I have a reason to stop splitting orders across suppliers.
- When the platform team confirms payments, approves Orders, and keeps prices and stock accurate, we want one console with queues instead of spreadsheets, so no paid Order waits unseen.

### 2.2 Non-Users (v1)

- **End clients** of integrators never log in. They only receive the Proposal PDF the integrator sends them, and they pay the integrator off-platform.
- **Suppliers** have no accounts. The platform team maintains their Products, costs, and Documentation. Suppliers benefit because the platform is their route to market; supplier-facing reporting is deferred (§6.2).
- **Retail and non-integrator buyers** cannot see Selling Prices or order: both need Level 3, which needs a vouch and Admin approval.
- Integrators outside Nigeria, and industrial-automation or complex commercial projects (multi-resident buildings, hotels, office complexes), are outside v1 (see §5 and §6).

### 2.3 Key User Journeys

- **UJ-1. Tunde designs a Project, quotes his client, and places the Order.**
  - **Persona + context:** Tunde owns a three-person AV and security integration business in Lagos and quotes several jobs a month.
  - **Entry state:** Level 3 Owner, logged in on the installed app.
  - **Path:** Tunde creates a Project and names it; it opens with one default Room, which he renames. He adds Products to that Room from the Catalogue, adds more Rooms, and saves. He generates a Proposal: he enters the client's details and sets one Integrator Margin for the whole Project, and downloads the PDF to send his client however he likes. When the client agrees, he creates an Order for the whole Project, reviews the price (and what changed since the Proposal), chooses delivery or pick-up, and pays by bank transfer, uploading the Receipt.
  - **Climax:** Finance confirm his payment against the bank statement, the Order becomes Paid, Operations approve it, and he follows its Shipments to Delivered or Signed For.
  - **Resolution:** Tunde's equipment arrives, and the Completed Order counts toward his Loyalty Tier.
  - **Edge cases:** Because the NGN 5 band is tight, an Order placed weeks after a Proposal will usually differ from it; Tunde sees the difference split into exchange rate, tier, and item changes, with delivery on its own line, and decides whether to pass it to his client or absorb it from his margin. If his client withdraws before Approval, Tunde cancels the Order and receives a Credit Note for the amount confirmed.

- **UJ-2. Hammed joins the platform and unlocks full access.**
  - **Persona + context:** Hammed is an integrator in Lagos who heard about integrator at a training seminar. Segun, an Established Integrator Hammed met there, is willing to vouch for him.
  - **Entry state:** No account.
  - **Path:** Hammed signs up with his email or phone and verifies it (Level 1: browse the Catalogue without prices). He completes his profile with name, date of birth, address, NIN, and optionally a business address; the NIN is checked (Level 2: identity verified, still no prices). Segun generates a Vouch Code for Hammed from his Owner account. Hammed submits it with his Trade Name and accepts the Terms; Admin sees Segun as the voucher, reviews the request, and approves it (Level 3).
  - **Climax:** Hammed's account reaches Level 3 and Selling Prices, Projects, Carts, Proposals, and Orders become available.
  - **Resolution:** He invites his two technicians to the Business Account.
  - **Edge case:** The automatic NIN check returns a definitive mismatch. Hammed's verification goes to manual review by Admin and every later NIN verification for that account is manual. If his Vouch Code expires before he submits it (codes last 48 hours), Segun generates a new one.

- **UJ-3. Funmi restocks parts for Tunde with a direct buy.**
  - **Persona + context:** Funmi runs the office at Tunde's business and holds the Manager role. Tunde needs cabling and connectors for a small job and doesn't need a Proposal.
  - **Entry state:** Level 3 Manager.
  - **Path:** Funmi adds Products to her Cart, chooses pick-up or delivery, creates the Order, and pays by bank transfer exactly as in UJ-1.
  - **Climax:** The Order is Paid and approved the same day.
  - **Resolution:** She collects the goods; the Order counts toward the Business Account's Loyalty Tier.
  - **Edge cases:** An item sells out while it sits in her Cart; the Cart shows it as unavailable, or as a Back-order Item if back-order is enabled for it. Within seven days of collection, Tunde can change his mind about an unopened item: Support issues a return authorisation, and the return earns a Credit Note less the restocking fee.

- **UJ-4. Tunde orders a Project that includes a Back-order Item.**
  - **Persona + context:** One Product in Tunde's Project is Out of Stock but available as a Back-order Item at a discount.
  - **Entry state:** A saved Project containing that Product.
  - **Path:** At order creation, the Back-order Item is flagged with its Back-order Discount, its Lead Time, and a warning that it cannot be cancelled once ordered from the Supplier and is not returnable for change of mind. Tunde explicitly opts in before paying. He chooses whether the Order ships in parts or in full.
  - **Climax:** The in-stock items ship first; the Back-order Item follows once it arrives.
  - **Resolution:** Each Shipment reaches Delivered or Signed For independently.
  - **Edge cases:** An item Tunde thought was in stock sells out between his Receipt upload and its confirmation; Finance credits that Order Line. If the Back-order Item is more than seven days past its promised ship-by date, Tunde can cancel that line for a Credit Note.

- **UJ-5. Chidi finds the installation guide on site.**
  - **Persona + context:** Chidi is a technician on Tunde's team, at a client's building with poor signal, about to install cameras.
  - **Entry state:** Technician logged in on the installed app; the evening before, Chidi used "Make this Project available offline" on his phone, which saved the Project and its Documentation to the device.
  - **Path:** From the Project, Chidi opens the Product and reads its installation and configuration guide, then opens the manufacturer's PDF manual.
  - **Climax:** The install proceeds without a phone call to the office.
  - **Resolution:** Chidi finishes the job; nothing in his view showed any pricing.
  - **Edge case:** A Product has no Documentation yet; the page says "Documentation coming soon."

- **UJ-6. Ngozi confirms a payment.**
  - **Persona + context:** Ngozi is Finance at the platform, working 8am to 6pm, Monday to Saturday, with the Receipts queue as her main screen.
  - **Entry state:** Staff logged in with the Finance role; the queue lists uploaded Receipts, oldest first, with each item's age.
  - **Path:** Ngozi opens the oldest Receipt, finds the matching credit on the day's bank statement by amount, sender, and Order reference, enters the confirmed amount, and confirms. When confirmed Payments cover the Amount Due, the Order becomes Paid and appears in Operations' approval queue.
  - **Climax:** A Receipt uploaded in the morning is confirmed within two Business Hours and the Integrator is told.
  - **Resolution:** Bola, in Operations, approves the Paid Order after a stock check, and Kunle, in Admin, handles the NIN review and Level 3 request queues the same way.
  - **Edge cases:** No matching credit appears on the statement: Ngozi marks the Payment Unconfirmed and the Integrator is told to re-upload or pay the full amount. The transfer is larger than the Amount Due: a draft Credit Note for the overpayment is created for her to issue.

- **UJ-7. Chidi reports a faulty camera and the device goes back.**
  - **Persona + context:** Chidi is a technician on Tunde's team. During an install, Chidi finds that a camera delivered under one of Tunde's Orders is faulty. Amaka, in Operations, holds the Support duties.
  - **Entry state:** Chidi is logged in as a Technician; the device was dispatched to Tunde's Business Account with its serial number recorded.
  - **Path:** Chidi contacts Support by phone or WhatsApp, and Amaka confirms who is calling, opens a Case, and checks the device's serial number to confirm it left the platform's Stores, then tries to fix the fault remotely. If that fails, Amaka generates an Issue Code. Chidi raises a Return Request with the Issue Code and logs the fault. Tunde's Manager, Funmi, matches the request to the specific Order Line and approves it. The device reaches a collection point or the Main Store, or is handed to a courier with a tracking reference, within 48 hours of the Issue Code, and Amaka checks it against its record on receipt.
  - **Climax:** Amaka's findings reach Admin, who decide the remedy: a replacement, a manufacturer repair, a Credit Note, or a rejection.
  - **Resolution:** Tunde's business receives the outcome and the Return Request closes as Resolved.
  - **Edge cases:** The serial number has no dispatch record: Amaka cannot generate an Issue Code, unless Operations and Admin approve a serial exception backed by label and box photos. If the device does not reach a Store, or a courier, within 48 hours of the Issue Code, the Return Request lapses and Support must generate a new code; the reporting window is judged from the first logged contact, so a lapse does not cost Tunde his right to a remedy.

## 3. Glossary

Downstream workflows and readers must use these terms exactly.

**Accounts and access**

- **Integrator** — a business (a systems integrator) that holds a Business Account and buys on the platform.
- **Business Account** — the unit that holds Level 3 access, a Loyalty Tier, Projects, Orders, Credit Notes, and Users. It has exactly one Owner. Access Levels belong to the Business Account.
- **User** — a person who logs in under a Business Account, in one of three roles: **Owner**, **Manager**, or **Technician**. A verified contact belongs to one Business Account in v1.
- **Staff** — platform-team members with a role: **Finance**, **Operations**, or **Admin**.
- **Support** — the customer-case duties of verifying callers, looking up serial numbers, opening Cases, generating Issue Codes, and investigating returned devices. Operations holds these duties at launch; they are a separate permission group, not a role, so a Support role can be split out later if volumes grow.
- **Access Level** — **Level 1** (verified email or phone; browse without prices), **Level 2** (verified identity including NIN; still browse without prices, and eligible to redeem a Vouch Code), **Level 3** (trade-verified by vouching; Selling Prices and full features). A Business Account that reaches Level 3 is **onboarded**.
- **NIN** — the National Identification Number a Nigerian resident holds.
- **Trade Name** — the business name an Integrator trades under; it appears on Proposals and tax invoices.
- **Vouch Code** — a single-use code generated by an Established Integrator's Owner for one named prospect, valid for 48 hours.
- **Established Integrator** — a Business Account whose vouching rights are not suspended and that either has at least 3 Completed Orders, each with goods value of at least USD 100 (a setting) and none with an open Return Request, or has been granted vouching rights manually by Admin.

**Catalogue, pricing, and stock**

- **Catalogue** — the set of Products Integrators can browse and buy.
- **Product** — a sellable item with a Supplier Cost, stock, Lead Time, and Documentation. It is identified by a unique SKU.
- **Product Range** — a group of Products sharing a default Platform Markup.
- **Supplier** — a company the platform buys Products from. A Product may have several Suppliers, with one **Active Supplier** at a time.
- **Supplier Cost** — the Active Supplier's price for a Product, in USD. It is never shown to Integrators.
- **Platform Markup** — the percentage added to Supplier Cost, a markup on cost. Default 25%, set per Product Range, overridable per Product, editable by Admin only.
- **Wholesale Price** — Supplier Cost × (1 + Platform Markup), in USD, before any discount and before VAT. It is an internal figure; Integrators see Selling Prices.
- **VAT** — value-added tax at 7.5%, included in every Integrator-facing price. The rate is a setting that only Admin can change.
- **Selling Price** — what an Integrator pays per unit, in naira: Wholesale Price less any applicable Tier Discount or Back-order Discount, plus VAT, converted at the Platform Rate. Integrators see and pay naira only.
- **Platform Rate** — the single NGN-per-USD exchange rate, set by Finance.
- **Loyalty Tier** — a Business Account's discount tier, recalculated monthly from trailing six-month spend.
- **Tier Discount** — the percentage off Wholesale Price given by a Loyalty Tier.
- **Stock Threshold** — a per-Product quantity of safety stock: a Product is Out of Stock when its available quantity is at or below it.
- **Back-order Item** — an Out of Stock Product that is orderable because back-order is enabled for it.
- **Back-order Discount** — a per-Product percentage off Wholesale Price for a Back-order Item.
- **Lead Time** — the estimated time until a Back-order Item can ship, shown to Integrators.

**Projects, Proposals, and Carts**

- **Project** — a named, saved design owned by a Business Account, containing Rooms.
- **Room** — a named section of a Project containing Project Items.
- **Project Item** — a Product with a quantity placed in a Room.
- **Parts List** — the consolidated bill of materials of a Project across its Rooms.
- **Integrator Margin** — the single percentage an Owner or Manager adds over their cost (the Selling Price they pay) to price a Proposal. `[ASSUMPTION: a markup on the Integrator's cost, consistent with Platform Markup.]`
- **Proposal** — a client-facing PDF generated from a Project, valid for one month. Only the latest Proposal per Project is kept.
- **Proposal Rate** — the Platform Rate recorded when a Proposal is generated.
- **Cart** — a basket for a direct buy, owned by one User. It cannot be saved as, or converted into, a Project.

**Orders, payments, and fulfilment**

- **Order** — a request to buy, created from a whole Project or from a Cart. Its **Amount Due** is the total in naira including delivery.
- **Order Line** — one Product and quantity in an Order.
- **Order Quote** — the price shown to a User on the order-confirmation screen, to which the created Order is bound.
- **Price Lock** — the price captured at Order creation, kept only if the payment conditions in FR-38 are met.
- **Completed Order** — an Order in which every Order Line that has not been credited sits in a Shipment that is Delivered or Signed For, with at least one such Shipment.
- **Shipment** — a group of Order Lines sent or collected together; an Order may ship in parts.
- **Fulfilment Method** — **Delivery** (carried by a courier partner to an address, cost added) or **Pick-up** (collection at a Store).
- **Delivery Zone** and **Zone Rate** — a geographic zone and its flat delivery cost in naira per Shipment, editable by Operations.
- **Store** — a location where Pick-up Orders are collected, supplied from the central warehouse, which holds all stock as one pool. There can be several Stores; launch opens with one. A **collection point** is any Store other than the Main Store.
- **Main Store** — the Store that Delivery Shipments dispatch from by default.
- **Payment** — one bank transfer an Integrator makes toward an Order, evidenced by a **Receipt** (an uploaded photo or file). Its states are Submitted, Confirmed, Unconfirmed, and Reversed.
- **Credit Note** — a naira record issued by Finance of money owed back to a Business Account. Its reasons are: overpayment, an unfulfillable Order Line, a cancelled Order, an approved return, freight reimbursement, a counterfeit credit, a non-delivery credit, and an uncollected Pick-up.
- **Business Hours** — 8am to 6pm Monday to Saturday, West Africa Time, excluding days on Admin's holiday calendar.

**Documentation, support cases, and returns**

- **Documentation** — installation and configuration material attached to a Product: PDF manuals, video tutorials, and step-by-step guides.
- **Case** — a record opened for every contact with Support, from the app or by phone or WhatsApp, capturing channel, caller, serial number or Order Line, outcome, and time.
- **Return Request** — a request about delivered goods. Device reasons are transit damage, a wrong item, a dead-on-arrival unit, a warranty claim, and a suspected counterfeit; a change of mind is a separate reason with a return authorisation; a non-delivery claim is a separate path for goods that never arrived.
- **Issue Code** — a code generated by Support for one Case, listing one or more serial numbers (or an Order Line and quantity), valid for 48 hours, which a User must enter to raise a Return Request for a device reason.
- **Warranty Period** — the manufacturer's warranty length for a Product, running from the date the platform dispatches the goods.

- **Abbreviations** — CAC: Corporate Affairs Commission. CBN: Central Bank of Nigeria. FCCPC: Federal Competition and Consumer Protection Commission. FX: foreign exchange. NCC: Nigerian Communications Commission, whose type approval covers communications equipment. RMA: return merchandise authorisation, the manufacturer's repair or replacement process. SON: Standards Organisation of Nigeria. SONCAP: the SON Conformity Assessment Programme for imported electrical and electronic goods. TIN: tax identification number.

## 4. Features

### 4.1 Accounts, Access Levels, and Teams

**Description:** Access to the platform is earned in three steps. Level 1 (verified contact) lets anyone browse the Catalogue without prices. Level 2 (a verified profile including NIN) confirms who the Owner is and makes the account eligible to redeem a Vouch Code; it still shows no prices. Level 3 (vouched by an Established Integrator and approved by Admin) unlocks Selling Prices, Projects, Carts, Proposals, and Orders. A Business Account has an Owner and can add Managers and Technicians, who need only a verified contact. Realizes UJ-2, UJ-3, UJ-5, UJ-6.

**Functional Requirements:**

#### FR-1: Sign up and verify a contact
*Status: Confirmed, with Default guards. Priority 1.*
A visitor can create an account with an email address or phone number, which the platform verifies with a code. The verified person becomes the Owner of a new Business Account at Level 1. `[ASSUMPTION: the first sign-up for a business creates its Business Account and Owner.]` Realizes UJ-2.

**Consequences (testable):**
- An unverified contact cannot browse the Catalogue.
- A contact that is already registered is sent to login or to its pending verification, not to a second account.
- Verification codes expire after 10 minutes, and resends and wrong attempts are capped per contact, device, and IP; sign-up has bot checks.
- Verified accounts are Level 1 and see no prices anywhere.

#### FR-2: Level 1 access
*Status: Confirmed. Priority 1.*
Level 1 Users can search and browse the Catalogue (FR-73) and read Documentation, without prices, and cannot create Projects, Carts, Proposals, or Orders. Realizes UJ-2.

**Consequences (testable):**
- No price, discount, or markup value, and no price-derived ordering, appears in any Level 1 response, cache, or export.

#### FR-3: Complete the profile for Level 2
*Status: Confirmed (decision 1: Selling Prices from Level 3 only). Priority 1.*
An Owner can complete a profile of name, date of birth, address, NIN, and an optional business address; the optional TIN and VAT-registration declaration are part of the Business profile (FR-70). On successful NIN verification (FR-4) the Business Account becomes Level 2. Level 2 confirms who the Owner is and makes the account eligible to redeem a Vouch Code (FR-5) and request Level 3 (FR-6); it adds no visible capability. Selling Prices are visible only from Level 3, so a prospect, an end client, or a competitor sees no price until a voucher and Admin have approved the business. Level 2 cannot create Projects, Carts, Proposals, or Orders. Realizes UJ-2.

**Consequences (testable):**
- The business address is optional; name, date of birth, address, and NIN are required.
- No price, discount, or markup value, and no price-derived ordering, appears in any Level 2 response, cache, or export.
- Every creation action for Projects, Carts, Proposals, and Orders is unavailable at Level 2.
- A Level 2 Owner can submit a Vouch Code.

**Notes:** `[NOTE FOR PM]` Level 2 now adds nothing a prospect can see except eligibility to redeem a Vouch Code. It stays a separate level because the Vouch Code binds to a NIN-verified name (FR-5) and because it keeps unverified sign-ups out of Admin's review queue. Hidden prices may slow sign-up completion; if the month-3 checkpoint (SM-1) shows that, consider "price on approval" messaging or merging Levels 1 and 2.

#### FR-4: NIN verification
*Status: Confirmed (one automatic attempt, then manual), with Default guards. Priority 1.*
Verification passes when the NIN exists, the registered name matches the profile name once normalised, and the date of birth matches. The platform makes one automatic attempt; provider timeouts and errors retry with backoff up to 3 times over 24 hours without using the attempt up, and only a definitive mismatch moves the profile to manual review by Admin, after which all later NIN verification for that account is manual. A NIN can belong to one Business Account only; a duplicate goes to manual review flagged as a duplicate. Admin can reject with a reason, and the Owner can resubmit up to 3 times. An Owner without a NIN contacts the platform; Admin verifies them by phone or WhatsApp (a passport or residence permit plus the business's CAC registration number) and records the check. There is no in-app route. Realizes UJ-2, UJ-6.

**From UX design and architecture (9 October 2026).** When the NIN service's retries run out after 24 hours, the profile goes to manual review and the Owner is told; after 3 rejected resubmissions the Owner is asked to call or WhatsApp the platform. When Admin asks, an optional step to send a photo of the NIN slip or ID card appears among the Get approved steps (contact, NIN, Trade Name, vouch, and Terms). A person without a NIN is handled as above.

**Consequences (testable):**
- After a definitive mismatch no further automatic attempt is made for that account.
- A provider timeout does not consume the attempt.
- Admin sees a queue of profiles awaiting manual verification, and the Owner sees the verification status and any rejection reason.
- NIN lookups are throttled per contact, device, and IP, and an alert fires when daily verification spend passes a budget.

#### FR-5: Generate a Vouch Code
*Status: Confirmed (48 hours, 2 active, Established only), with Default guards. Priority 1.*
Only the Owner of an Established Integrator can generate a Vouch Code, after a fresh one-time code (FR-71); a Manager can ask the Owner for one. A code is at least 10 random characters, bound to one named prospect (name plus phone or email), single-use, valid for 48 hours, redeemable only by a profile whose NIN-verified name matches the prospect, and locked after 5 wrong attempts. A Business Account can have at most 2 active codes at once and can vouch for at most 6 prospects in a rolling 90 days. Realizes UJ-2.

**Consequences (testable):**
- A Business Account that is not an Established Integrator cannot generate a code.
- An account with 2 Completed Orders of at least USD 100 each is not Established and cannot generate a code.
- A third active code, or a seventh prospect in 90 days, is refused.
- An expired, used, or locked code is rejected on submission.
- Each code records the issuing Business Account and User.

#### FR-6: Request Level 3
*Status: Confirmed. Priority 1.*
A Level 2 Owner can submit a Vouch Code, confirm the Trade Name, and accept the Terms (FR-69) to request Level 3. Realizes UJ-2.

**Consequences (testable):**
- A valid code places the request in an Admin review queue; an invalid, expired, used, or locked code is rejected with a reason.

#### FR-7: Approve Level 3
*Status: Confirmed, with Default guards. Priority 1.*
Admin reviews each Level 3 request on a screen that shows the voucher (Business Account, User, history, and open claims), the Trade Name, and a checklist (online presence, proof of an installed job, a phone call), and records a reason on every decision. On approval the Business Account becomes Level 3 and Selling Prices, Projects, Carts, Proposals, and Orders unlock for its Owner and Managers (Technicians per FR-11). If the voucher is suspended before the decision, the applicant re-confirms with an explicit tick and the request is flagged for explicit Admin re-confirmation; a vouchee later suspended for fraud suspends the voucher's vouching rights pending review. Realizes UJ-2, UJ-6.

**Consequences (testable):**
- No account reaches Level 3 without an Admin approval or a direct Admin grant (FR-8).
- A rejection shows the Owner a reason.
- The approval record names the voucher.

#### FR-8: Seed the first cohort
*Status: Confirmed, with Default guards. Priority 1.*
Admin can create a Business Account for the first Integrators from the founder's network, and generate Vouch Codes on behalf of the platform, before any Established Integrator exists. A directly created account is Pending Activation: its Owner verifies a contact, completes NIN verification (FR-4), supplies the Trade Name, and accepts the Terms before Level 3 features unlock. Realizes UJ-6.

**From UX design and architecture (9 October 2026).** Admin creates a seeded business from a "Create a business" screen with a Pending Activation list, and can issue a platform Vouch Code for a named prospect. Staff who created a business cannot confirm its Payments or issue its Credit Notes (FR-52). A Pending Activation Owner sees the same Get approved steps with a different opening line.

**Consequences (testable):**
- Directly created accounts are recorded in the audit trail with the Admin who created them.
- A Pending Activation account cannot create an Order.

#### FR-9: Suspend vouching rights
*Status: Confirmed. Priority 1.*
Admin can suspend and restore a Business Account's ability to generate Vouch Codes, and can grant it manually to an account that is not yet established. Suspension voids that account's active Vouch Codes. Realizes UJ-6.

**Consequences (testable):**
- A suspended account cannot generate a code, and its active codes stop working immediately.

#### FR-10: Invite and manage team members
*Status: Confirmed (roles), with Default guards. Priority 1.*
Once a Business Account is Level 3, its Owner can invite people by email or phone as Managers or Technicians, and a Manager can invite and remove Technicians. Only the Owner can invite, remove, or demote a Manager, and nobody but the Owner can remove or demote the Owner. An invitation is bound to the invited contact, expires after 7 days, and can be revoked; the invitee verifies the contact and accepts the Terms and Privacy Notice at first login, and does not repeat Level 1 and Level 2 verification. Removing a User or changing a role revokes their sessions and purges that device's cache at its next connection. Realizes UJ-2.

**Consequences (testable):**
- The Business Account's Level 3 applies to an invitee within their role.
- A Manager cannot remove or demote the Owner or another Manager.
- A Technician cannot invite or remove Users.
- A contact already belonging to another Business Account cannot accept an invitation.

#### FR-11: Role permissions
*Status: Confirmed, with Default completions. Priority 1.*
The platform enforces role permissions server-side, not only in the interface.

| Capability | Owner | Manager | Technician |
|---|---|---|---|
| Browse and search the Catalogue and Documentation | Yes | Yes | Yes |
| See Selling Prices, line prices and totals, Tier Discount, Back-order Discount, Loyalty Tier and spend, Integrator Margin, Payments, Amount Due, delivery cost, Credit Notes, platform bank details | Yes | Yes | No |
| Create and edit Projects; use the Parts List | Yes | Yes | Yes (no prices) |
| Generate Proposals, use a Cart, create, amend, and cancel Orders, pay | Yes | Yes | No |
| Request Level 3 | Yes | No | No |
| Generate Vouch Codes | Yes | Request only | No |
| See Credit Notes and request their application to an Order | Yes | Yes | No |
| Request a Credit Note refund by bank transfer (with a fresh one-time code) | Yes | No | No |
| See the business's Cases | All | All | Their own |
| Raise Return Requests (a Technician's request needs Manager or Owner approval) | Yes | Yes | Yes |
| Approve a Technician's Return Request | Yes | Yes | No |
| Manage Users | All roles | Technicians only | No |
| Change Owner, close the Business Account | Yes | No | No |
| See Order and Shipment status | Yes | Yes | Yes, with no amounts `[ASSUMPTION: Technicians see status without amounts.]` |

"Money fields" means every amount, price, discount, margin, total, balance, and bank detail in the table's second row. Technicians and Level 1 and Level 2 Users receive no money field in any response, notification, search ordering, export, or cache.

**Consequences (testable):**
- A published "Technician-safe" response schema lists the only fields a Technician response may contain, and an automated test fails when any money field, or any ordering derived from price, is reachable by a Technician, Level 1, or Level 2 session.
- A Technician's offline cache holds no price data, and notification text sent to a Technician carries no amounts.

**Notes:** `[NOTE FOR PM]` The Owner's NIN is the only personal identity record in v1; Managers and Technicians are not NIN-verified. A Manager can place Orders, so the Owner should treat that role accordingly. Refunds to a bank account need the Owner (FR-43).

#### FR-69: Terms and privacy acceptance
*Status: Default. Priority 1.*
Sign-up and Level 3 approval require acceptance of the current Terms and Privacy Notice, recorded with version, time, User, and IP. Invitees accept at first login, and Admin-seeded Owners accept before activation. A new Terms version blocks Orders until the Owner re-accepts. WhatsApp messaging needs an explicit per-number opt-in and an opt-out. The Privacy Notice covers third-party data Integrators enter, such as client details on Proposals and delivery receivers.

**From UX design and architecture (9 October 2026).** The Terms are one document, accepted at sign-up and again at the Level 3 request, where its key points are shown. Admin publishes each version with a plain list of what changed, a preview of the Owner's screen, and counsel's approval date and reference. A new version interrupts only the Owner, on next open, and blocks only Order creation until accepted.

**Consequences (testable):**
- An Order cannot be created by a Business Account whose Owner has not accepted the current Terms.
- A WhatsApp message is never sent to a number without a recorded opt-in.
- Acceptance records are kept for the life of the account plus the legal retention period (NFR-8).

#### FR-70: Business profile
*Status: Default. Priority 1.*
An Owner or Manager can maintain the Trade Name, a logo (PNG or JPG up to 2 MB), address, phone, email, an optional TIN, and a declaration of whether the business is VAT-registered. The Trade Name is required before the first Proposal; a missing logo falls back to a name-only header. The VAT-registered declaration controls the Proposal VAT line (FR-30) and tax invoices (FR-77), and Staff may verify the TIN.

**From UX design and architecture (9 October 2026).** A business can declare itself VAT-registered without a TIN and is prompted to add one.

**Consequences (testable):**
- A Proposal cannot be generated without a Trade Name.
- A Business Account that has not declared itself VAT-registered cannot switch on the Proposal VAT line.

#### FR-71: Authentication and recovery
*Status: Default. Priority 1.*
Users log in with a one-time code sent to their verified contact, or a passkey where the device supports it. A fresh one-time code (step-up) is required before generating a Vouch Code, requesting a refund or the application of a Credit Note, changing a role, changing the Owner, or changing a contact. Integrator sessions last 30 days idle and Staff sessions 12 hours, and Staff must use multi-factor authentication. An Owner who loses their contact recovers access through Admin with an ID check and a 72-hour cooling period, announced to every contact on record. A change of Owner is started by the Owner (or by Admin-assisted recovery), and the incoming Owner must verify a contact and pass NIN verification before the transfer completes.

**From UX design and architecture (9 October 2026).** Recovery for an Owner who lost their contact is an ID check by phone; the 72-hour wait starts when the check passes, access then opens by itself, and every contact on file is told, with a "call us if this was not you" line. Managers and Technicians have no recovery route: the Owner removes and re-invites them. Each sensitive action needs its own fresh one-time code, valid 10 minutes, including adding or removing a passkey, adding a WhatsApp number, inviting a Manager, and applying a Credit Note. A User sees where they are logged in and can log out everywhere else, and is logged out after 30 idle days.

**Consequences (testable):**
- A refund request cannot be submitted without a fresh one-time code.
- A change of Owner to a person without a verified NIN is refused.
- A recovery notice reaches every contact on record before the cooling period ends.

#### FR-72: Holds, revocation, blocklist, and closure
*Status: Default. Priority 1.*
Admin, or Finance for payment fraud with Admin ratifying within one Business Day, can put a Business Account on Hold (no new Orders and no refunds, while Payments are still processed), revoke Level 3, disable a User, and add a NIN hash, contact, or sending bank account to a blocklist checked at sign-up and at Payment confirmation. Each action needs a reason, is audited, and notifies the Owner unless Admin flags a fraud review. An Owner can close a Business Account only when no Order is open (not Completed, Expired, or Cancelled), no Return Request or Case is open, and no Credit Note has an unrefunded balance; personal data is then erased or pseudonymised, except records the law requires to be kept.

**From UX design and architecture (9 October 2026).** A blocked person sees a neutral refusal. A Hold placed by Finance is ratified or lifted by Admin within 1 Business Day in Approvals. Revoking Level 3 returns the business to the Get approved steps with prices hidden, and Orders already made are still processed. A bank credit from a blocklisted sender sits among unmatched credits, marked blocked.

**Consequences (testable):**
- A Business Account on Hold cannot create an Order or receive a refund.
- A blocklisted NIN, contact, or sender account is refused at sign-up and at Payment confirmation.
- Closure is refused while any of the listed items is open.

### 4.2 Catalogue, Suppliers, and Stock

**Description:** Operations maintains the Catalogue, including more than 500 Products at launch. Each Product has an Active Supplier and a Supplier Cost in USD. Stock is one pool at the central warehouse, tracked per Product with a Stock Threshold, and Out of Stock Products can be offered on back-order. Realizes UJ-3, UJ-4, UJ-6.

**Functional Requirements:**

#### FR-12: Maintain Products
*Status: Confirmed, with Default fields. Priority 1.*
Operations can create and edit Products with these fields:
- Identity: SKU (unique and unchangeable), brand, model, name, description, images, and Product Range.
- Measures: unit of measure and pack size (each, metre, box of N), weight and dimensions.
- Returns and compliance: a serialised flag, Warranty Period, a dead-on-arrival window, returnability flags (special order, end-of-life, refurbished or demonstration, software or licence), and a compliance reference (NCC type approval or SONCAP where the category needs one).
- Lifecycle and cost: a lifecycle (draft, active, discontinued), a quarantine flag, and Supplier Costs with the date each was set.
- Stock and content: stock quantities, Stock Threshold, back-order setting, Lead Time, and Documentation.

A Product cannot become active without the fields later requirements rely on. Discontinued Products stay on existing Projects with a warning. Realizes UJ-6.

**From UX design and architecture (9 October 2026).** Each Product Range carries an Admin-set flag for NCC type approval or SONCAP. Each of the four returnability flags must be answered Yes or No, with no default. A Product has a serial-format field.

**Consequences (testable):**
- Operations cannot edit Platform Markup (see FR-13).
- A Product missing its Warranty Period (or an explicit "none"), serialised flag, returnability flags, or required compliance reference cannot be activated.
- SKUs are unique.
- Every Product change is recorded in the audit trail.

#### FR-13: Platform Markup by Product Range, and the margin floor
*Status: Confirmed (markup), with a Default floor. Priority 1.*
Admin can set a default Platform Markup per Product Range (initially 25%), override it for a single Product, and set a margin floor (initially 8% over Supplier Cost). Price computation never takes the pre-VAT price below Supplier Cost × (1 + floor), whatever discounts apply; a save that would breach the floor is refused and names the Product. Realizes UJ-6.

**Consequences (testable):**
- Only Admin can edit a Platform Markup or the floor.
- A Product with no override uses its Product Range's markup.
- A changed markup updates affected Wholesale Prices immediately and does not alter existing Orders.
- A Back-order Discount that would take a price below the floor is refused.

#### FR-14: Multiple Suppliers, one active
*Status: Confirmed, with Default guards. Priority 1.*
A Product can hold Supplier Costs from several Suppliers, with exactly one Active Supplier. A Supplier must be approved by Admin before it can become the Active Supplier, and each Supplier agreement should state return, dead-on-arrival, and counterfeit-chargeback terms before its Products are listed. Admin switches the Active Supplier; Operations enters the Supplier Costs. Realizes UJ-6.

**From UX design and architecture (9 October 2026).** "Approve" on a Supplier stays off until its returns, dead-on-arrival, and counterfeit-chargeback terms are recorded; Admin can instead approve with a written reason. The shorter of the Supplier's and the 30-day dead-on-arrival window applies. Stopping a Supplier takes its Products off sale after a preview, with a replacement named per Product.

**Consequences (testable):**
- Wholesale Price always derives from the Active Supplier's cost.
- A switch to a Supplier with no Supplier Cost, or a cost of zero or less, is refused, and the Active Supplier cannot be removed without a replacement.
- Switching recalculates the Wholesale Price, is audited, and shows how many open Carts and Projects it affects.

#### FR-15: Bulk upload of Products
*Status: Confirmed, with Default guards. Priority 1.*
Operations can upload a spreadsheet from a downloadable template to create and update many Products at once. SKU is the match key; a missing, ambiguous, or repeated key is a failed row. Valid rows are applied and failed rows are reported with their row number and reason; for a file over the price-change threshold below, no row applies until Admin approves. Admin-only columns (Platform Markup and Active Supplier) are ignored for Operations with a note per row. A dry-run report shows creates, updates, and price changes before anything is applied. A Supplier Cost change above 15%, or any file that changes prices on more than 20 Products, lands as a pending change for Admin approval, and the previous state is kept for one-click rollback. `[ASSUMPTION: valid rows are applied and invalid rows are reported, not rejected as a whole file.]` Realizes UJ-6.

**From UX design and architecture (9 October 2026).** A file that changes prices on more than 20 Products, or moves a cost by more than 15%, waits for Admin approval as a whole file, with a one-click rollback afterwards.

**Consequences (testable):**
- A launch file of 500 or more rows imports in a single upload.
- Each failed row shows its row number and the reason.
- A row that repeats a SKU already in the file is failed, not applied twice.

#### FR-16: Stock quantities and status
*Status: Confirmed (threshold as safety buffer), with Default ledger rules. Priority 1.*
Each Product has an on-hand quantity and a reserved quantity, and available = on-hand − reserved. A Product is In Stock when its available quantity is above its Stock Threshold, and otherwise Out of Stock; units at or below the threshold are held as safety stock, which Operations may release to a Back-order Item. Operations set the threshold per Product `[ASSUMPTION: Operations owns Stock Thresholds.]`, record stock changes through the ledger in FR-74, and cannot set on-hand below reserved without an Admin override; adjustments above a set size need Admin sign-off (FR-52).

**From UX design and architecture (9 October 2026).** Releasing safety stock to a Back-order Item is a stock-ledger line with a reason. The stock-adjustment size that needs Admin sign-off is an Admin setting (placeholder).

**Consequences (testable):**
- A Product at or below its threshold cannot be ordered unless back-order is enabled for it.
- An on-hand edit below the reserved quantity is refused without an override.

#### FR-17: Back-order configuration
*Status: Confirmed (per-Product discount, Lead Time, 10% cap). Priority 1.*
Operations can enable back-order for a Product and set its Back-order Discount (up to 10%; a higher discount needs Admin approval) and Lead Time. Integrators see stock status and Lead Time wherever the Product is priced or added. At Order creation the discount and Lead Time are captured on the Order Line, and the line's promised ship-by date is the payment confirmation date plus the Lead Time. If back-order is disabled for a Product before an Order that includes it is Paid, the line is credited with notice. Realizes UJ-4.

**Consequences (testable):**
- The Back-order Discount is stored per Product, not as one global value.
- Changing a Back-order Discount or Lead Time never alters an Order Line already created.

#### FR-18: Stock reservation and shortages
*Status: Confirmed (reserve at payment), with Default guards. Priority 1.*
Stock is reserved only when payment confirmation brings an Order to Paid, and reservation is atomic, so two Orders cannot reserve the same unit. If available stock covers only part of an Order Line's quantity at confirmation, the platform reserves what exists and Finance credits only the shortfall quantity (FR-43). The platform places no Supplier order for any Order Line before its Order is Paid. Adding to a Project or Cart, creating an Order, or uploading a Receipt reserves nothing. Realizes UJ-4.

**Consequences (testable):**
- Two Orders confirmed together for the last unit leave one reserved and the other credited.
- A partial shortage credits only the missing quantity.
- No Supplier order exists for an Order Line whose Order is not Paid.

#### FR-73: Catalogue search, filters, and stock display
*Status: Default. Priority 1.*
Users from Level 1 up can search by name, brand, model, and SKU with typo tolerance, browse a category tree built from Product Ranges, filter by brand, Product Range, and stock status, and sort by name; Owners and Managers of Level 3 Business Accounts can also sort by price. Each Product page shows images, specifications, Documentation, stock status (In Stock, Out of Stock, or Back-order with Lead Time) and, to those Owners and Managers, the Selling Price; Level 1 and Level 2 Users see "Prices are shown once your business is approved." Price-bearing endpoints are rate-limited per account and per IP, and there is no price export or bulk listing. Search works offline on the synced Catalogue (FR-28), and empty and no-result states are defined. Realizes UJ-1, UJ-3.

**Consequences (testable):**
- A search for a model number or SKU returns that Product first.
- A Technician, Level 1, or Level 2 session cannot sort or filter by price and sees no price on any Product page.
- Price endpoints are rate-limited per account and per IP, and no price export or bulk listing exists.

#### FR-74: Goods-in record and stock ledger
*Status: Default (adds build scope; supports FR-66 and SM-C1). Priority 1.*
Operations record each stock receipt with the Supplier, date, quantity, batch or lot reference, landed cost (the Supplier's invoice cost plus freight, duty, and clearing, with the rate used), and serial numbers for serialised Products (FR-65). Every stock change (receipt, reservation, release, adjustment, or quarantine) is a ledger entry with a reason and reference. Back-order Order Lines carry "ordered from Supplier" and "received" markers set by Operations. A batch, as FR-66 uses the word, is a goods-in record. Realizes UJ-6.

**From UX design and architecture (9 October 2026).** Landed-cost components accept USD or NGN. Goods-in records serial numbers, and dispatch verifies them. A batch is a goods-in record, so a suspected counterfeit can be marked per batch or per Product.

**Consequences (testable):**
- Stock on hand equals the sum of ledger entries.
- A back-order line cannot be marked received without a goods-in record.

### 4.3 Pricing, Platform Rate, and Loyalty

**Description:** All Products are priced in USD and shown to Integrators in naira at one Platform Rate set by Finance. Loyalty discounts come from a trailing six-month spend tier. Back-order Discounts are per Product. Realizes UJ-1, UJ-6.

**Functional Requirements:**

#### FR-19: Price computation
*Status: Confirmed (formula, VAT inclusion), with Default rounding rules. Priority 1.*
The platform computes each unit's Selling Price as Supplier Cost × (1 + Platform Markup) × (1 − the applicable discount, FR-23) × (1 + VAT rate), converted to naira at the Platform Rate, with the pre-VAT amount never below the margin floor (FR-13). Integrators see and pay naira only; USD is never shown to them. Arithmetic is decimal, never binary floating point: the naira Selling Price of a unit is computed at full precision and rounded half-up to the kobo once, a line total is the unit price times the quantity, and an Order total is the sum of its lines. Realizes UJ-1.

**From UX design and architecture (9 October 2026).** The app may add displayed prices in whole kobo for display only; it never computes a price used for a Proposal or Order, and its rounding matches this rule and the addendum's test vectors.

**Consequences (testable):**
- At NGN 1,330 per USD, a USD 100 Supplier Cost with a 25% Platform Markup and no discount is NGN 166,250.00 before VAT, plus NGN 12,468.75 VAT, for a Selling Price of NGN 178,718.75 per unit.
- The test vectors in the addendum (quantity 1,000, three lines of 10.10, and a discounted line) pass.
- Level 1 and Level 2 Users and Technicians never receive these values.

#### FR-20: Set the Platform Rate
*Status: Confirmed (including the guards, decision 7). Priority 1.*
Finance sets the Platform Rate to the rate at which the platform can actually buy dollars that day, recording the reference source and any spread, and reconfirms it each Business Day. Finance changes it when the market rate has moved more than NGN 5 from the rate in force (a trigger separate from the NGN 5 Proposal tolerance in FR-35). A change above 3% needs a second approver and shows a price preview with the number of open Carts, Projects, and unlocked Orders it affects; a change above 10% is refused as a probable typing error unless Admin overrides with a reason. A rate older than 48 hours blocks Order creation until it is reconfirmed, and Finance or Admin can freeze Order creation with a "prices are being updated" notice. A change takes effect immediately on displayed prices. Realizes UJ-6.

**From UX design and architecture (9 October 2026).** One message covers both frozen Order creation and a rate older than 48 hours, without naming the cause. The 48 hours are calendar hours; the Platform Rate page warns before a weekend; warnings at hours 36 and 44 are placeholders.

**Consequences (testable):**
- A rate of zero or below is refused.
- Each change records who, when, the old and new values, the reference source, and the spread.
- A rate older than 48 hours blocks Order creation.
- Orders already past their Price Lock are unaffected.

#### FR-21: Loyalty Tiers
*Status: Confirmed (decision 6). Priority 2.*
The platform calculates each Business Account's Loyalty Tier monthly, at 00:00 West Africa Time on the 1st. Spend is the pre-VAT, pre-delivery goods value of Order Lines whose Shipment is Delivered or Signed For, in USD at each Order's stored Platform Rate, over the trailing six months, net of returned and credited lines. Tier benefits are price-only in v1. The design intent is a flywheel: more volume through the platform earns a better price.

| Tier | Spend, trailing 6 months (USD equivalent) | Tier Discount | Realized markup on cost at this tier |
|---|---|---|---|
| Base | under 1,500 | 0% | 25.0% |
| Tier 2 | at least 1,500 and under 5,000 | 2% | 22.5% |
| Tier 3 | at least 5,000 and under 12,000 | 4% | 20.0% |
| Tier 4 | 12,000 and above | 6% | 17.5% |

**Consequences (testable):**
- Spend of 4,999.99 is Tier 2 and 5,000.00 is Tier 3.
- Cancelled, credited, and returned lines do not count.
- A tier can fall as well as rise at a monthly recalculation.
- The tier belongs to the Business Account, not to a User.

**Notes:** `[NOTE FOR PM]` The discount comes out of the markup, which is the platform's only revenue. A Tier 4 Order alone yields 17.5%, below the SM-C1 blended floor; SM-C1 therefore tracks the blend, not each Order.

#### FR-22: Tier visibility
*Status: Confirmed. Priority 2.*
Owners and Managers can see their current tier, their spend toward the next tier (in naira at the current Platform Rate), and the date of the next recalculation; a tier change is shown 7 days before it takes effect. Realizes UJ-1.

**From UX design and architecture (9 October 2026).** The expected tier change is projected from spend so far, and the naira thresholds shown move with the Platform Rate.

**Consequences (testable):**
- A tier change is visible before it takes effect.

#### FR-23: Discount rule and caps
*Status: Confirmed (larger wins, no stacking; 10% Back-order cap). Priority 1.*
Where both a Tier Discount and a Back-order Discount apply to an item, the larger applies; they do not stack. A Back-order Discount is capped at 10% unless Admin approves more, and no discount takes a price below the margin floor (FR-13).

**Consequences (testable):**
- An item with a 4% Tier Discount and an 8% Back-order Discount is priced at 8% off Wholesale Price.

#### FR-24: Tier timing
*Status: Confirmed. Priority 1.*
Prices shown use the Business Account's current Loyalty Tier, and an Order uses the tier in effect when the Order is created, not the tier at the time of any earlier Proposal. Realizes UJ-1.

**Consequences (testable):**
- If the tier changes between Proposal and Order, the Order shows the order-time price.

### 4.4 Project Designer

**Description:** An Owner, Manager, or Technician builds a job room by room from the Catalogue. Technicians design without seeing prices. Designing works offline using the last-synced Catalogue. Realizes UJ-1, UJ-5.

**Functional Requirements:**

#### FR-25: Projects, Rooms, and the Parts List
*Status: Confirmed, with a Default Parts List. Priority 1.*
A Level 3 User can create a named Project that opens with one default Room, rename any Room at any time, add further Rooms, and save the Project. Each Project provides a Parts List across its Rooms, shown on screen and downloadable without prices. Realizes UJ-1.

**From UX design and architecture (9 October 2026).** A Room can be deleted, with a confirmation when it holds items, and restored for 30 days as in FR-28.

**Consequences (testable):**
- A new Project always contains one Room.
- Renaming a Room never affects its items.
- The Parts List totals each Product's quantity across Rooms and shows no prices to a Technician.

#### FR-26: Project Items
*Status: Confirmed. Priority 1.*
A User can add Products with whole-number quantities of at least 1 (or fractional quantities where the unit of measure allows) to a Room, change quantities, and remove them. Out of Stock Products with back-order can be added and are flagged with their Lead Time; discontinued and quarantined Products show a warning. Realizes UJ-1, UJ-4.

**Consequences (testable):**
- Out of Stock Products without back-order cannot be added.
- A quantity of zero or below is refused.

#### FR-27: Price visibility in Projects
*Status: Confirmed. Priority 1.*
Owners and Managers see line prices, Room totals, and Project totals. Technicians see no prices and no Back-order Discount, but do see Lead Time and the discontinued and quarantined warnings. Prices shown offline carry an "as of" time and turn brass after a day; offline, Owners and Managers hold prices only for Products in the Business Account's Projects and those they recently viewed (FR-73). Realizes UJ-1, UJ-5.

**Consequences (testable):**
- A Technician's view of the same Project shows items, quantities, Lead Times, and warnings, and no money field or Back-order Discount.

#### FR-28: Offline design
*Status: Confirmed (offline scope), with Default sync rules. Priority 2.*
A User can browse and search the last-synced Catalogue and create and edit Projects without a connection, and changes sync when the connection returns. Sync merges per Room and per Project Item, last writer wins per field by the server's receipt time (never the device clock), deletions are recorded and not lost, Owners and Managers can see who changed what, and a sync screen lists conflicts and restores earlier versions for 30 days. At sync each item is checked against the live Catalogue and flagged Unavailable with a warning, never discarded. A cache is partitioned by User and role. At the next connection after a User is removed or changes role, their queued edits are uploaded and held (below) and then the cache is purged. Saved Projects lock, without deletion, after 14 days without a check-in. Realizes UJ-5.

**From UX design and architecture (9 October 2026).** A check-in is any successful connection. Saved Projects lock on day 14 without one, with a warning line on day 11 and a banner on day 13; nothing is deleted and waiting changes are kept. Logging out with changes waiting keeps them on the phone, locked, until the next login. A Technician sees a later change without the other person's name and can keep or restore their own version. Edits queued by a User who is removed or changes role before they sync are held for an Owner or Manager to accept or discard, never applied automatically. The on-screen Parts List works offline; its PDF needs a connection. Orders, Payments, and Credit Notes are not kept on the phone in v1.

**Consequences (testable):**
- Two Technicians editing different Rooms offline lose no edits.
- Offline-created Projects appear on the Business Account's other devices after sync.
- Generating a Proposal, creating an Order, and uploading a Receipt are unavailable offline and say why.
- Offline prices are never used to create a Proposal or Order.

### 4.5 Proposals

**Description:** An Owner or Manager turns a saved Project into a client-ready PDF with one Integrator Margin applied. The Proposal is a quote, not a commitment: it reserves no stock and creates no Order. Realizes UJ-1.

**Functional Requirements:**

#### FR-29: Generate a Proposal
*Status: Confirmed (one margin, one-month validity, custom lines), with Default guards. Priority 1.*
An Owner or Manager can generate a Proposal for a Project by entering the client's details (name, company, address, email, phone, site address) and one Integrator Margin for the whole Project. The platform snapshots the Project's contents with the Selling Prices and Tier Discount in force (the Integrator's cost), records the Proposal Rate, and sets an expiry one month later. A Proposal needs at least one Project Item with a quantity of at least 1 and a Trade Name, and requires a connection. The Integrator can also add custom lines (labour, travel, commissioning) with a description, quantity, and price; they appear on the Proposal only and are never ordered through the platform. Realizes UJ-1.

**From UX design and architecture (9 October 2026).** Of the client's details, only the name is required. Custom lines are entered VAT-included.

**Consequences (testable):**
- Only one Integrator Margin applies to the whole Project, and a margin below zero is refused; one above 100% asks for confirmation.
- The Proposal stores the Project snapshot, the Integrator's cost at that moment, and the Platform Rate used at generation.
- A Project with no items cannot produce a Proposal.

#### FR-30: Proposal PDF
*Status: Confirmed (line prices and Room totals, Trade Name and logo), with Default guards. Priority 1.*
The Proposal PDF shows the Integrator's Trade Name and logo, the client's details, line-item prices and Room totals at client prices, any custom lines, Lead Time on Back-order lines, the expiry date, and a validity sentence ("prices hold while the exchange rate stays within NGN 5 of the quoted rate, otherwise they adjust"). It never shows Supplier Cost, Wholesale Price, Platform Markup, Tier Discount, Back-order Discount, Integrator Margin, or the Selling Price the Integrator pays, in visible text or metadata. A Business Account that has declared itself VAT-registered (FR-70) can choose per Proposal whether the PDF shows a VAT line: with it, line items and Room totals appear before VAT, followed by a VAT line and a VAT-inclusive grand total; without it, every price is VAT-inclusive. The VAT line is the grand total minus the sum of the line amounts shown before VAT, so the lines and the VAT line add up exactly to the grand total, with any kobo difference falling on the VAT line; each pre-VAT line amount is its VAT-inclusive line total divided by (1 + the VAT rate), rounded to the kobo. `[ASSUMPTION: the Integrator Margin applies to the VAT-inclusive Selling Price, which gives the same client total as applying margin before VAT and adding VAT after.]` Realizes UJ-1.

**From UX design and architecture (9 October 2026).** The PDF carries no platform branding. The validity sentence is in plain words and adjusts if the rate moves by more than NGN 5. The VAT line is offered only to VAT-registered businesses.

**Consequences (testable):**
- A forbidden-field test searches the PDF's visible text and metadata for each of the figures listed above and finds none.
- Switching the VAT line on or off changes the layout only; the grand total is identical, with any kobo difference on the VAT line.
- A Back-order line shows its Lead Time but not its Back-order Discount.

#### FR-31: Regenerate overwrites
*Status: Confirmed. Priority 1.*
If the client asks for changes, the Owner or Manager edits the same Project and regenerates the Proposal, which replaces the previous one. Only the latest Proposal is kept. Realizes UJ-1.

**Consequences (testable):**
- After regeneration only the latest Proposal can be downloaded, and the platform holds no earlier version.

**Notes:** `[NOTE FOR PM]` Overwriting means the platform cannot reproduce what an earlier PDF quoted if a client disputes it. This is a deliberate v1 trade-off.

#### FR-32: Proposal expiry
*Status: Confirmed, with a Default date rule. Priority 1.*
A Proposal expires at the end of the day, West Africa Time, one calendar month after it is generated (clamped to the end of a shorter month). The PDF shows that date, an expired Proposal is marked expired and can still be downloaded, and ordering from an expired Proposal's Project uses current prices. Realizes UJ-1.

**Consequences (testable):**
- A Proposal generated on 31 January expires at the end of 28 February (29 in a leap year).

### 4.6 Cart and Orders

**Description:** An Owner or Manager orders either a whole Project or a Cart. Pricing follows the Proposal where the exchange rate has stayed close; otherwise current prices apply and the difference is shown before commitment. An Order is created at exactly the price the user confirmed. An unpaid Order expires, and an Order paid in time keeps its price. Realizes UJ-1, UJ-3, UJ-4.

**Functional Requirements:**

#### FR-33: Cart for direct buy
*Status: Confirmed, with a Default scope. Priority 1.*
An Owner or Manager can add Products to their own Cart and order from it without a Project. A Cart cannot be saved as, or converted into, a Project. Realizes UJ-3.

**From UX design and architecture (9 October 2026).** A Cart Order has an optional name, otherwise "Direct buy" and the item count. Lines that cannot be ordered are listed at the top of the Cart.

**Consequences (testable):**
- There is no action that turns a Cart into a Project.
- A Cart belongs to one User and is not shared.

#### FR-34: Create an Order
*Status: Confirmed (whole Project, Technicians excluded, repeat Orders allowed); guards Default. Priority 1.*
An Owner or Manager can create an Order from a whole Project or from a Cart. A Project is ordered whole, not by Room or item, and a Project can be ordered more than once, with each Order captured at its own prices. At creation the platform checks every line: lines that are Out of Stock without back-order, discontinued, quarantined, or otherwise unorderable are listed, and the user removes or replaces them in the Project or Cart; nothing is dropped silently. An Order needs at least one line with a quantity of at least 1. A Business Account can hold at most 2 unpaid Orders per Project. The Order is bound to the Order Quote the user confirmed: if the Platform Rate, Loyalty Tier, Proposal validity, Back-order terms, or stock availability changes while the confirmation screen is open, the platform refuses and shows the new Order Quote for re-confirmation. Creating an Order is idempotent, so a double submit creates one Order. Realizes UJ-1, UJ-3.

**From UX design and architecture (9 October 2026).** Lines that cannot be ordered are listed at review.

**Consequences (testable):**
- An Order created from a Project contains every orderable Project Item, or the user has first resolved the unorderable ones.
- A second Order from the same Project is priced afresh and does not alter the first.
- A double-clicked confirm creates one Order.
- A Technician sees no order-creation action.

#### FR-35: Order pricing against the Proposal
*Status: Confirmed (NGN 5 tolerance, Proposal Rate); refinements Default. Priority 1.*
When an Order is created from a Project with an unexpired Proposal, and the Project's contents are identical to the Proposal's snapshot, and the current Platform Rate is within NGN 5 of the Proposal Rate, the Order uses the Proposal Rate; otherwise it uses the current Platform Rate. The Proposal Rate applies only to the first Order created from a Proposal, and the NGN 5 tolerance is an Admin setting. The Tier Discount is always the one in effect at order creation (FR-24). Before committing, the user sees the goods total next to the Proposal's goods total at the Integrator's cost (before Integrator Margin), with the difference split into exchange rate, tier, items added or removed, and availability, and delivery shown on its own line. `[ASSUMPTION: the tolerance protects the exchange rate only, while the Tier Discount is applied fresh.]` Realizes UJ-1.

**From UX design and architecture (9 October 2026).** Beside the breakdown by cause, the Owner or Manager also sees their margin at today's cost.

**Consequences (testable):**
- With a Proposal Rate of NGN 1,330, a current rate of 1,334 keeps 1,330 and a current rate of 1,336 uses 1,336.
- A Project edited since its Proposal shows "Project edited since Proposal" and uses the current rate.
- After the Proposal expires, the current rate always applies.

**Notes:** `[NOTE FOR PM]` NGN 5 is about 0.4% of the rate and the platform's own research shows official and parallel rates NGN 45 or more apart, so most Orders placed weeks after a Proposal will reprice. The 24-hour Price Lock is the protection that matters. A percentage band would be a one-setting change.

#### FR-36: Back-order opt-in
*Status: Confirmed, with Default disclosures. Priority 1.*
Where an Order includes Back-order Items, the Owner or Manager must explicitly opt in to them before the Order can be created. The opt-in screen shows each item's Back-order Discount and Lead Time and states that the item cannot be cancelled once the platform has ordered it from the Supplier (unless it is more than 7 days past its promised ship-by date) and is not returnable for change of mind. The discount and Lead Time are captured on the Order Line. Realizes UJ-4.

**Consequences (testable):**
- An Order with a Back-order Item cannot be created without the opt-in.
- The opt-in screen shows both the cancellation and the return restriction.

#### FR-37: Fulfilment Method and delivery cost
*Status: Confirmed (zone-based cost, courier), with Default guards. Priority 1.*
At order creation the Owner or Manager chooses Pick-up or Delivery. For Pick-up they choose a Store. For Delivery they enter a structured address (State, local government area, area, landmark, receiver name, and receiver phone), the Delivery Zone is looked up from State and local government area, and a flat Zone Rate per Shipment is added to the Amount Due, with the charge shown for the Shipments that the chosen ship-in-parts or ship-in-full option (FR-39) implies. If the zone cannot be resolved or has no rate, Order creation is blocked with an offer of a manual zone pick or a delivery price from Operations. A courier partner carries the delivery. Operations cannot add a Shipment that raises the Integrator's charge after payment; extra courier cost is absorbed and reported. The one exception is a Shipment the business itself requests through the FR-39 send-what-is-ready offer. Realizes UJ-1.

**From UX design and architecture (9 October 2026).** A business can request a delivery price from the delivery screen. Operations answers within about 1 Business Day with a VAT-inclusive price, based on a staff-only courier quote, valid to the end of the next Business Day, or with a reason when the platform cannot deliver. A delivery price never rises after payment, except as in FR-39. The courier's actual charge is recorded for reporting only. Both times are placeholders.

**Consequences (testable):**
- The Amount Due includes the Zone Rate before payment.
- Pick-up adds no delivery cost.
- A Pick-up Shipment names its Store.
- An address that resolves to no active zone blocks Delivery.

#### FR-38: Price Lock and expiry
*Status: Confirmed (24-hour lock, 48-hour expiry, repricing after the window, and the timing details in decisions 2, 3, and 4). Priority 1.*
**Lock test.** The Order's Amount Due is fixed at creation. Receipts uploaded within 24 hours of creation are in time. The Price Lock holds when the confirmed in-time Payments, once Finance has decided them all, cover the Amount Due within Finance's underpayment tolerance (a setting, placeholder NGN 200, with any write-off audited); a Receipt earns the lock only if the matched bank credit's value time is earlier than the Receipt's upload time. A corrected re-upload made within 24 hours of an Unconfirmed notice keeps the original upload time if the full amount matches.

**Repricing.** If the lock test fails, or payment arrives only between 24 and 48 hours, the Order is repriced once, at the first moment after the window at which Finance has decided every in-time Receipt (or, with no in-time Receipt, when the first Payment is confirmed). Only the exchange rate changes, chosen by the FR-35 rule at that moment (an Order still within NGN 5 of its Proposal Rate keeps it). Tier, markup, VAT, and delivery stay as captured. Confirmed Payments are credited in naira, and the Integrator is notified of the new Amount Due and the balance, or of a draft Credit Note if they overpaid.

**Expiry.** An Order with no Receipt uploaded expires 48 hours after creation; an Order with a Receipt expires 48 hours after the last Receipt upload or Unconfirmed decision, and never later than 7 days after creation. At expiry, confirmed Payments become a Credit Note and later bank credits go to Finance's unmatched queue. A Credit Note applied to an Order counts as a Payment for the lock and for expiry (FR-43). Realizes UJ-1.

**From UX design and architecture (9 October 2026).** The time that counts for a Receipt is when the platform receives the upload's details; the image may finish uploading later.

**Consequences (testable):**
- A correct Receipt uploaded at hour 20 and confirmed at hour 30 keeps its locked price.
- At NGN 1,330 an Order of USD 500 of goods has an Amount Due of NGN 714,875. If NGN 600,000 is confirmed in time and the remainder is paid at hour 30 when the rate is NGN 1,400, the Order reprices to NGN 752,500 and the balance due is NGN 152,500.
- An Order with no Receipt moves to Expired at hour 48, and one with only Unconfirmed Receipts expires 48 hours after its last decision.
- A Payment that arrives for an Expired Order is not applied automatically.

**Out of Scope:** An Admin override of repricing is deferred to v2.

#### FR-39: Ship in parts or in full
*Status: Confirmed (parts or full, in-stock first), with a Default hold limit. Priority 1.*
The Owner or Manager chooses whether an Order ships in parts or in full. In parts, in-stock items ship first and Back-order Items follow. Shipping in full holds in-stock lines for up to 14 days after Approval, after which the Integrator is offered shipping in parts. Realizes UJ-4.

**From UX design and architecture (9 October 2026).** In the 14-day offer to send what is ready, the business pays the extra delivery at the Zone Rate before it goes, or declines and waits for the full Order.

**Consequences (testable):**
- Each Shipment is charged its own delivery cost.
- A line held for a full shipment is offered in parts after 14 days.

#### FR-57: Cancel an Order
*Status: Confirmed (decisions 5 and 10). Priority 1.*
An Owner or Manager can cancel an Order that has not been Approved. Cancelling or expiring an Order that holds confirmed Payments creates a Credit Note for the confirmed amount (credit-only), pending Receipts go to Finance, and an Order with a Payment under review cannot be cancelled without Finance's consent. After Approval, cancellation is a request: Shipments are frozen until Operations decides within one Business Day, a Back-order Item the platform has already ordered from a Supplier cannot be cancelled, and cancellation applies per Shipment, with unshipped Shipments cancellable and shipped goods following the returns rules (FR-58 to FR-68). A Back-order Item more than 7 days past its promised ship-by date can be cancelled by the Owner or Manager for a Credit Note whether or not it has been ordered from the Supplier, and Operations credits a line the Supplier cannot supply. Realizes UJ-1, UJ-4.

**From UX design and architecture (9 October 2026).** Cancelling creates the Credit Note by itself for confirmed Payments; Receipts not yet checked go to Finance, and a Payment under review needs Finance's consent. A reason is optional before Approval and required for a cancellation request after it.

**Consequences (testable):**
- A cancelled Order moves to Cancelled and its Amount Due is cleared.
- A Cancelled Order never counts toward Loyalty Tier spend or vouching eligibility.
- A cancel request that arrives at the same moment as Approval or expiry gets the Order's current status, not a second transition.
- A Back-order line more than 7 days past its promised ship-by date can be cancelled for a Credit Note.

#### FR-75: Amend an Order before Approval
*Status: Default. Priority 1.*
An Owner or Manager can change the delivery address, receiver, Store, or the parts-or-full choice on an Order that is Awaiting Payment, or on a Paid Order before Approval through a request to Operations. Amending never changes goods prices; a change of Delivery Zone or Fulfilment Method updates only the delivery charge. Realizes UJ-1.

**From UX design and architecture (9 October 2026).** Operations answers a change request on a Paid Order within the approval target (4 Business Hours).

**Consequences (testable):**
- An amendment changes no goods price and never resets the Price Lock window.

### 4.7 Payments and Credit Notes

**Description:** Payment is by manual bank transfer. The Integrator uploads a Receipt for each transfer, and Finance confirms each one against the platform's bank statement. Partial payments are allowed across several transfers. Realizes UJ-1, UJ-3, UJ-6.

**Functional Requirements:**

#### FR-40: Pay by bank transfer
*Status: Confirmed, with Default fields. Priority 1.*
An Owner or Manager can see the platform's bank details (managed under FR-52) and the Amount Due in naira with a unique Order reference to put in the transfer narration, and can upload a Receipt for each transfer: a JPEG, PNG, HEIC, or PDF up to 10 MB, resized on the device to about 2 MB, with location data stripped and a malware scan; corrupt, blank, or unsupported files are refused. They enter the declared amount, sender name, sender bank, bank reference, and transfer date and time. Each Order stores the bank details it showed at creation. Realizes UJ-1.

**Consequences (testable):**
- An Order can have several Payments, each with its own Receipt.
- An upload that is not a valid image or PDF is refused.

#### FR-41: Confirm payments
*Status: Confirmed (per-Payment confirmation and decisions 3 and 4), with Default statement matching. Priority 1.*
Finance confirms a Payment only when it matches a credit on the platform's bank statement for amount, sender, and reference (statement upload or CSV import in v1), and records the confirmed amount, which may differ from the declared amount. A bank reference can be used once. A sender whose name does not match the Owner or Trade Name needs a second reviewer. A daily queue lists bank credits that have no Payment. Each confirmation writes an immutable ledger line, and corrections are compensating lines. A Payment can be Reversed by Finance with a reason and a second approver; that returns the Order to Awaiting Payment and blocks Approval and shipping until reviewed. A Receipt or bank credit for an Expired, Cancelled, or already Paid Order goes to the unmatched queue for Finance to resolve with a Credit Note. An Unconfirmed Payment tells the Integrator to re-upload a Receipt or pay the full amount; after 3 Unconfirmed uploads on one Order, a staff review is required. Finance aims to confirm within two Business Hours (FR-78). Realizes UJ-6.

**From UX design and architecture (9 October 2026).** The review shows the bank statement's age and coverage and suggests no matches beyond it. A matched credit dated before the Receipt upload is a written check. A third Unconfirmed upload goes to Admin as "Needs review". A second reviewer must be a different person. Unmatched bank credits are reviewed daily, and a credit from an unknown sender is held while the bank is asked. One bank credit can be split across Orders by allocation, but never allocated beyond its amount.

**Consequences (testable):**
- A Payment whose bank reference was already used is refused.
- Every Payment has its own confirmation outcome, confirmed amount, and reviewer.
- A Reversed Payment returns the Order to Awaiting Payment and blocks Approval.
- An Unconfirmed Payment shows the literal message "Payment unconfirmed: please upload your receipt again or pay the full amount."

#### FR-42: Order payment progress
*Status: Confirmed. Priority 1.*
An Order stays in Awaiting Payment until the confirmed Payments in naira, together with any Credit Note applied, cover its Amount Due within Finance's underpayment tolerance, and then becomes Paid automatically. Realizes UJ-1.

**Consequences (testable):**
- Partial confirmed Payments leave the Order Awaiting Payment.
- No Order becomes Paid without the full Amount Due being covered.

#### FR-43: Credit Notes
*Status: Confirmed (decision 5); counsel to vet the expiry and refund terms. Priority 1.*
**Issuing.** Finance issues a Credit Note, in naira, to a Business Account for any reason in the Glossary. A Credit Note records the source Order Line, the VAT component, the amount and balance, the reason, the issuer, and its issue and expiry dates, and moves through Issued, Partially applied, Applied, Refunded, and Expired.

**Applying.** Owners and Managers can see their Credit Notes and ask Finance to apply one against a later Order's Amount Due; an application counts as a Payment of its amount from the moment of the request, and an Order fully covered by credit does not expire for lack of a Receipt. If the Order a note funded is cancelled or expires, the amount returns to the original note with its original expiry.

**Refunding.** Credit Notes for an overpayment, an unfulfillable line, a counterfeit, a non-delivery, freight reimbursement, or an approved return caused by the platform's fault are refundable by bank transfer on the Owner's request, with a fresh one-time code, to a sending account recorded on a confirmed Payment of that Business Account; Credit Notes for cancellations, change-of-mind returns, and uncollected Pick-ups are credit-only.

**Limits.** A Credit Note cannot be transferred to another Business Account and can be used only to buy from the platform. It can be applied for 12 months after issue, Owners are reminded 60 and 14 days before it expires, and a balance left after expiry can be refunded on request (counsel to confirm). Realizes UJ-4, UJ-6.

**From UX design and architecture (9 October 2026).** A refund is for the whole balance only. Finance sends the transfer in the bank portal and records the bank reference before marking the Credit Note Refunded. A pending refund shows as a banner, not a status. Finance's rejection of an application shows its reason.

**Consequences (testable):**
- A Credit Note cannot be spent without Finance applying it, and cannot be applied above its balance.
- An application is evaluated at the time of the request, even if Finance completes it later.
- A refund goes only to a sending account recorded on a confirmed Payment of that Business Account.
- An expired Credit Note can no longer be applied but can be refunded on request.

#### FR-77: Tax invoices and credit-note documents
*Status: Confirmed (decision 12); fields to be settled with the accountant. Priority 1.*
The platform issues a numbered tax invoice for each Paid Order (buyer Trade Name, TIN if given, line VAT, delivery VAT, and serial numbers where recorded) and a credit-note document for each Credit Note, downloadable as PDFs by Owners and Managers. The architecture plans for e-invoicing readiness. Realizes UJ-1.

**Consequences (testable):**
- A Paid Order has exactly one tax invoice with a unique, sequential number.
- Each Credit Note has a document that references the invoice it adjusts.

### 4.8 Fulfilment and Order Tracking

**Description:** After payment, Operations approves the Order, then the Order is fulfilled through one or more Shipments, each with its own status. Integrators can follow progress. Realizes UJ-1, UJ-6.

**Functional Requirements:**

#### FR-44: Approve Paid Orders
*Status: Confirmed, with a Default credit-line action. Priority 1.*
Operations can approve a Paid Order after a stock check, which moves it to Approved and its Shipments to Processing. If the check finds an Order Line physically unavailable, Operations can credit that line: the line is cancelled, its reservation released, a Credit Note requested from Finance, and the Integrator notified, and the rest of the Order is approved. An Order whose every line is credited becomes Cancelled with a Credit Note for the full confirmed amount. Realizes UJ-6.

**From UX design and architecture (9 October 2026).** Because stock is reserved at Paid, Approve needs a shelf check per line and stays off until every in-stock line is found or credited.

**Consequences (testable):**
- Only a Paid Order can be approved.
- An Order with every line credited is never left Paid with no Shipments.

#### FR-45: Shipments and statuses
*Status: Confirmed (named statuses), with Default exception handling; the uncollected Pick-up rule is Confirmed (decision 9). Priority 1.*
**Shipment statuses.** Each Shipment moves through Processing, then either Ready for Pickup and Signed For (Pick-up) or In-transit and Delivered (Delivery). Exceptions are Delivery Failed (which returns to Processing for another dispatch), Returned to Store, Lost, and Uncollected (a pick-up not collected after 14 days). Only these transitions are allowed, and a reversal can be made only by Admin, with a recorded reason.

**Evidence required.** In-transit requires the courier's name and tracking reference; Delivered requires a proof reference from the courier or the receiver's one-time code; Signed For records the collector's name and the type and last four digits of their ID. A Pick-up Shipment becomes Ready for Pickup only when the goods are physically at the Store. The platform bears loss until Delivered for goods carried by its courier partner.

**Order statuses.** Order-level statuses are Awaiting Payment, Paid, Approved, Completed, Expired, and Cancelled, and an Order cannot become Completed while any Shipment is Lost or Delivery Failed; those states open a non-delivery Case.

**Uncollected Pick-ups and courier tracking.** For an uncollected Pick-up, Integrators are reminded at 7 days, the Shipment is marked Uncollected at 14, and it is cancelled and restocked after 30 days, issuing a credit-only Credit Note less the restocking fee. v1 has no automated courier integration: Operations updates statuses by hand using the courier's tracking. Realizes UJ-1, UJ-4.

**Consequences (testable):**
- Operations changes Shipment statuses; Integrators cannot.
- A Shipment cannot jump from Processing to Delivered or Signed For, and a Delivery Shipment cannot reach In-transit without a courier name and tracking reference.
- A Completed Order feeds Loyalty Tier spend and vouching eligibility.
- A Shipment In-transit for a long time or marked Lost opens a non-delivery Case rather than staying In-transit forever.

#### FR-65: Serial numbers
*Status: Confirmed. Priority 1.*
For Products flagged as serialised, Operations records serial numbers when a Shipment is dispatched or marked Ready for Pickup, by barcode scan or pasted list, and the Integrator can see them on the Order. A recorded serial is unique across all active dispatches, the count must equal the shipped quantity, and a model-specific format is checked where the Product defines one. A correction needs a reason and a second person. The serial numbers let Support confirm a device left the platform's Stores (FR-67). Realizes UJ-7.

**Consequences (testable):**
- A serialised Product cannot be marked dispatched or Ready for Pickup without its serial numbers.
- A duplicate serial, or a count that differs from the quantity, is refused.

#### FR-46: Track orders
*Status: Confirmed. Priority 1.*
Integrators can see an Order's status and each Shipment's status, with timestamps. Realizes UJ-1.

**Consequences (testable):**
- A Technician sees status but no amounts (FR-11).

#### FR-47: Delivery Zones and Zone Rates
*Status: Confirmed (zone structure, editable rates), with Default guards; the launch service-area rule is Confirmed (decision 14). Priority 1.*
Operations can define Delivery Zones and update each Zone Rate over time. The starting structure is Lagos split into two or three areas (defined as lists of local government areas), the other South-West states, Abuja (FCT), and the rest of Nigeria by region. Each active zone has a flat Zone Rate per Shipment and a delivery estimate shown at Order creation. A zone with no rate is "coming soon" and Delivery Orders to it are blocked, which is how the launch service area is set. Realizes UJ-6.

**Consequences (testable):**
- A Zone Rate change affects only Orders created afterwards.
- Any delivery address resolves to exactly one Delivery Zone.
- A zone without a rate or estimate cannot be activated.

**Notes:** `[NOTE FOR PM]` A flat rate per Shipment loses money on heavy or bulky items such as racks, UPS units, and displays; weight bands are a v2 candidate.

#### FR-56: Stores
*Status: Confirmed, with Default guards. Priority 1.*
Operations and Admin can create, edit, and deactivate Stores (name, address, and opening hours) where Pick-up Orders are collected, and Integrators choose a Store for Pick-up. Every Store is supplied from the central warehouse, which holds all stock as one pool. Launch opens with one Store, the Main Store. Delivery Shipments dispatch from the Main Store unless Operations or Admin specifies another Store on the Shipment. A Store cannot be deactivated while open Shipments reference it unless Operations reassigns them with notice, and the Main Store needs a replacement first. `[ASSUMPTION: Operations and Admin both maintain Stores and choose a Shipment's dispatch Store; the founder's phrase "Admin and or manager" is read as Admin and Operations, since the platform has no staff role called manager.]` Realizes UJ-1, UJ-3.

**Consequences (testable):**
- A Store can be added or deactivated without changing existing Orders.
- A Pick-up Order shows its Store's address and opening hours.
- Stock quantities exist once for the whole Catalogue, not per Store.
- A Delivery Shipment with no specified Store dispatches from the Main Store.

**Notes:** Moving goods from the central warehouse to a Store happens outside the platform in v1 (see §8).

### 4.9 Installation Documentation

**Description:** Every Product can carry Documentation: PDF manuals, video tutorials, and step-by-step installation and configuration guides, written by the platform team or supplied by Suppliers and uploaded by Staff. It is reachable from each Product's page and from Projects and Completed Orders. The launch target is full coverage of the Products Integrators actually order (SM-7). Realizes UJ-5.

**Functional Requirements:**

#### FR-48: Manage Documentation
*Status: Confirmed, with Default versioning. Priority 2.*
Operations can attach, replace, and remove Documentation on a Product, and see a list of Products that have none. Suppliers do not log in. Documentation is versioned; replacing or removing it marks copies that Users saved offline as Superseded or Withdrawn at their next sync. Realizes UJ-6.

**From UX design and architecture (9 October 2026).** Staff write guides in a console editor: steps, each with one optional photo and one optional warning, a version note, and a live phone preview. Removing Documentation needs a reason. A withdrawn manual saved offline opens only after "Open anyway". Format and size limits are placeholders.

**Consequences (testable):**
- Supported formats are PDF, video, and written step-by-step guides.
- A saved copy of a replaced manual shows a Superseded warning after sync.

#### FR-49: Access Documentation
*Status: Confirmed. Priority 2.*
Anyone who can browse the Catalogue (Level 1 and above) can read Documentation on the Product page, and Users can reach it from a Project or Completed Order. A Product with none shows "Documentation coming soon." Realizes UJ-5.

**Consequences (testable):**
- Documentation requires no purchase and no Level above 1.

#### FR-50: Documentation offline
*Status: Default. Priority 2.*
A User can choose "Make this Project available offline", which downloads the PDFs and written guides of every Product in the Project (videos on request) and shows the size first. Saved copies show their version date, and the app warns when device storage may evict them. `[ASSUMPTION: large videos are saved offline only on request.]` Realizes UJ-5.

**Consequences (testable):**
- Saved PDFs and written guides open with no connection.

### 4.10 Notifications

**Description:** The platform notifies Integrators and Staff by email and WhatsApp.

**Functional Requirements:**

#### FR-51: Email and WhatsApp notifications
*Status: Confirmed (channels), with a Default launch event set. Priority 2.*
The platform sends notifications through a matrix of event, recipient role, channel, and template, and the launch set is these 12 events:
- Payment Unconfirmed.
- Order repriced (with the new Amount Due).
- Price Lock deadline (hour 20).
- Order expiry warning (hour 40).
- Order Paid and Approved.
- Shipment dispatched or Ready for Pickup (with tracking).
- Return Request awaiting approval (with a reminder at half the Issue Code window).
- Issue Code issued and expiring (hour 36).
- Credit Note issued and expiring (60 and 14 days).
- Back-order running late.
- Level 3 and NIN decisions.
- Counterfeit recall notice.

Amounts go only to Owners and Managers, and Technician messages carry none. Time-sensitive events go by WhatsApp with an email fallback if undelivered after 15 minutes, and removed Users receive nothing. WhatsApp respects each number's opt-in (FR-69). Staff alerts are defined in FR-78. Realizes UJ-1, UJ-7.

**From UX design and architecture (9 October 2026).** Money and deadline events send at once; others wait until 9 am. A counterfeit recall notice stays on Home until someone taps "I have seen this". There is no in-app inbox and no push notification in v1. The every-message table in EXPERIENCE.md is the proposed notification matrix for the 12 launch events.

**Consequences (testable):**
- A notification names the Order, Proposal, or Case it concerns.
- A Technician's notification text contains no amount.
- A failed WhatsApp delivery falls back to email after 15 minutes.

### 4.11 Staff Roles, Work Queues, and Audit

**Description:** Staff work in a console with three roles. Admin can do everything, Finance handles money, and Operations handles goods, content, and customer cases such as faulty devices (the Support duties). The console is organised around work queues. Realizes UJ-6, UJ-7.

**Functional Requirements:**

#### FR-52: Staff accounts and roles
*Status: Confirmed matrix, with Default segregation rules. Priority 1.*

| Capability | Finance | Operations | Admin |
|---|---|---|---|
| Confirm Payments, issue and apply Credit Notes, pay refunds | Yes | No | Yes |
| Set the Platform Rate and the underpayment tolerance | Yes | No | Yes |
| Approve Paid Orders, update Shipment statuses, record serial numbers at dispatch | No | Yes | Yes |
| Maintain Products, stock, Lead Times, Back-order Discounts, Documentation | No | Yes | Yes |
| Edit Zone Rates and Stores | No | Yes | Yes |
| Consent to cancelling Approved Orders, decide Return Request remedies, run manufacturer RMAs | No | Yes | Yes |
| Support duties: verify callers, look up serial numbers, open Cases, generate Issue Codes, record device arrival and investigation findings | No | Yes | Yes |
| Mark a Product or batch as a suspected counterfeit | No | Yes | Yes |
| Edit Platform Markups, the margin floor, the VAT rate, return windows, and the NGN 5 tolerance | No | No | Yes |
| Switch Active Suppliers; approve Suppliers | No | No | Yes |
| Edit the platform's bank details (a second approver from Finance; effective after 24 hours; emailed to Admin and Finance) | No | No | Yes |
| Manual NIN verification, Level 3 approval, vouching rights, direct account creation, Holds and revocations, create and deactivate staff accounts | No | No | Yes |

Segregation of duties:
- No Staff member confirms a Payment, or issues or pays a Credit Note, for a Business Account they created, approved, or vouched for.
- Whoever investigated a return does not decide it (Admin decides when Operations investigated).
- Refunds and Credit Notes above a set amount (placeholder NGN 100,000, an Admin setting), Platform Rate changes above 3%, VAT rate changes, Back-order Discounts above 10%, Active Supplier switches, bulk price changes, and any change to the underpayment tolerance need a second approver.
- Stock adjustments above a set size need Admin sign-off.
- Any use of Admin powers "acting as" another role is flagged for the founder's weekly review.

Integrators see a "bank details changed on [date]" banner for 7 days after any change.

**From UX design and architecture (9 October 2026).** Everything needing a second person sits on one Approvals page; a decline reason goes back to the requester, and no one can approve their own request. A VAT rate change also needs a second approver. The platform records who created, approved, or vouched for each business, and shows a written reason when segregation blocks an action. The founder is an Admin account with a founder flag.

**Consequences (testable):**
- A Staff member can use only the capabilities their role holds.
- Support duties are a separate permission group, so Admin can later give them to a new Support role without a software release.
- A Finance user cannot confirm a Payment for an account they created, approved, or vouched for.

#### FR-53: Audit trail
*Status: Default. Priority 1.*
The platform records an append-only audit trail with hash chaining and credentials separate from application administration. The trail covers every state change to money, stock, access, identity data, pricing parameters, Cases, and Return Requests, and every read of a NIN or bank detail. The audited events are:
- Payment confirmations and reversals.
- Credit Notes.
- Rate and markup changes.
- Active Supplier changes.
- Role and access changes.
- Level 3 decisions.
- Order approvals and cancellations.
- Stock adjustments.
- Serial entries and corrections.
- Shipment status changes.
- VAT rate and window changes.
- Bank-detail changes.
- Issue Codes.
- Return Request decisions.

Entries hold entity identifiers and field differences, not copies of a NIN or full name, so personal data can be erased elsewhere. The founder reviews Admin and Finance override events weekly, and an alert fires on unusual patterns such as many NIN reads or many Credit Notes by one user. Financial entries are kept at least 6 years `[ASSUMPTION: confirm the period with the accountant]`.

**From UX design and architecture (9 October 2026).** The founder's weekly review lists acting-as use, an accepted late counterfeit report, a stock adjustment above the set size, a Payment reversal, a ratified Hold, a second-person serial correction, a Credit Note with a second approver, and any rate override above 10%, and cannot be signed off until each is reviewed. A NIN read needs a reason code (manual check, duplicate check, dispute, or other with a note). Audit entries hold identifiers, not names, and "Look up" is Admin only.

**Consequences (testable):**
- Audit entries cannot be edited or deleted by any application role.
- A NIN read appears in the audit trail with who, when, and the reason code.

#### FR-76: Staff continuity
*Status: Default. Priority 1.*
Every staff role has a named deputy, and an out-of-office switch moves that person's queue to the deputy with an "acting for" flag in the audit trail. Staff accounts are disabled on departure, which revokes their sessions and reassigns their queue items, and the last active Admin cannot be deactivated. Business Hours are kept in West Africa Time with a holiday calendar maintained by Admin.

**From UX design and architecture (9 October 2026).** A closed-day calendar pauses target clocks, and the app reads the Business Hours calendar for "open now" lines and by-times.

**Consequences (testable):**
- An out-of-office switch moves the queue within a minute and flags the deputy's actions.
- Deactivating the last Admin is refused.

#### FR-78: Staff work queues
*Status: Default. Priority 1.*
The console has a queue for each of Receipts, Level 3 requests, NIN reviews, Order approvals, Credit Note requests, and returns. Each shows the count, the age of the oldest item, and its owner; staff are alerted on arrival by email and WhatsApp; items escalate to Admin at 75% of their target; and the on-time rate per queue is reported. Targets: Receipts within two Business Hours; Level 3 and NIN reviews within one Business Day; Order approval within four Business Hours; Credit Note requests within one Business Day; returns investigated within two Business Days of arrival. Business Hours count only inside Business Hours (a Receipt uploaded at 5pm Saturday is due by 9am Monday).

**From UX design and architecture (9 October 2026).** Each queue shows its owner: the role holder and a named deputy. An item is soft-locked while someone has it open, and taking it over needs a reason. Any queue past 75% of its target raises an escalation notice.

**Consequences (testable):**
- A queue item older than 75% of its target is escalated to Admin.
- A Receipt uploaded outside Business Hours does not count against the two-hour target until the next opening.

### 4.12 Installable, Offline-Capable App

**Description:** The platform is a web application that installs to a phone's home screen, works on weak connections, and supports offline design. Native app-store apps are out of scope for v1.

**Functional Requirements:**

#### FR-54: Installable app
*Status: Confirmed. Priority 1.*
A User can install the platform to their device from the browser and use it like an app.

**Consequences (testable):**
- The installed app opens without a browser address bar.

#### FR-55: Low-bandwidth use
*Status: Confirmed (usable on weak data), with a Default definition. Priority 1.*
The platform stays usable on slow or patchy mobile data, including browsing, large images, and uploading a Receipt photo from a phone. The test profile is a slow 3G connection (400 kbps, 400 ms round trip) on a low-end Android phone with 2 GB of memory, and the Catalogue list must load within about 5 seconds on it.

**Consequences (testable):**
- A Receipt upload that loses its connection resumes or retries without re-entering data.
- The Catalogue list meets its load budget on the test profile.

### 4.13 Returns and Warranty

**Description:** Integrators report problems with delivered goods through a Case and, for a device fault, a Return Request, and Support comes first. A Technician who finds a fault contacts Support, who verify the caller and the device's serial number against dispatch records and try to fix the fault remotely. If that fails, Support generates an Issue Code, a Technician or Manager raises the Return Request, a Manager or Owner matches it to the Order Line and approves it, the device goes back to a Store, Support checks it on receipt, and Admin or Operations decide the remedy. Other claims have their own paths: a change of mind needs a return authorisation, and goods that never arrived are a non-delivery claim. Realizes UJ-7. Until the in-app workflow ships (§6.1), this runs manually by phone and WhatsApp with serial capture and Cases from day one. The windows, remedies, fees, and exclusions follow distributor norms researched in October 2026 and are proposed terms that counsel should vet before they appear in sign-up terms.

**Functional Requirements:**

#### FR-79: Cases
*Status: Default. Priority 2.*
Every contact with Support opens a Case: the Integrator can open one in the app ("Report a problem"), and Support opens one when a call or WhatsApp message arrives, recording channel, caller, serial number or Order Line, outcome, and time, including Cases resolved remotely or refused. Before disclosing any Order or serial-number detail, Support verifies the caller with an in-app one-time code or a callback to the verified phone. WhatsApp is used only through the platform's WhatsApp Business account. The first Case time starts the reporting window (FR-59). Realizes UJ-7.

**From UX design and architecture (9 October 2026).** A "Give Support a code" button on the Help sheet shows a 6-digit code the caller reads aloud; Support enters it in the console to verify the caller and records a callback. The Support desk is a console area.

**Consequences (testable):**
- A Case exists for every contact, including those closed remotely.
- Support cannot view a serial's Order details without a recorded caller verification.

#### FR-67: Serial number lookup
*Status: Confirmed. Priority 2.*
Support can look up a device's serial number and see the Shipment, Order Line, and Business Account it was dispatched to, which confirms the device left the platform's Stores. If a serialised device's serial finds no record, Support can open a serial exception (the Order Line plus photos of the label and box), which Operations and Admin must both approve, and which is audited. Realizes UJ-7.

**Consequences (testable):**
- A serial number the platform never dispatched returns no match.
- A match shows the dispatch date, which sets the Warranty Period start (FR-64).
- A serial exception cannot be approved by the person who opened it.

#### FR-68: Issue Codes
*Status: Confirmed (48-hour window, Support first), with Default rules. Priority 3.*
If remote assistance does not fix a device fault, Support can generate an Issue Code for the Case, tied to the Business Account and listing one or more serial numbers (or an Order Line and quantity where a Product has no serial number, which Support verifies against the Order Line). A Return Request for a device reason cannot be raised without a valid Issue Code. An Issue Code is valid for 48 hours from generation, and the device must reach a Store, or be handed to a courier with a tracking reference, within that window; the clock is paused for up to 7 days while a Technician-raised request awaits a Manager's approval. If the window passes, the Return Request lapses and Support must generate a new Issue Code, and the reporting window (FR-59) is not reset. Remote assistance happens by phone and WhatsApp, outside the platform. `[ASSUMPTION: each Issue Code serves one Return Request, and handing a device to a courier with a tracking reference counts as returned within the window.]` Realizes UJ-7.

**From UX design and architecture (9 October 2026).** The Issue Code reaches the Integrator in the Case and fills the return form. A Technician-raised Return Request pauses the 48 hours for up to 7 days while it awaits approval.

**Consequences (testable):**
- An Issue Code works once, for the Business Account and serial numbers it was generated for.
- Support cannot generate an Issue Code for a serial number with no dispatch record unless a serial exception is approved.
- A Return Request whose device has not reached a Store or courier within 48 hours of its Issue Code, excluding the approval pause, moves to Lapsed.

#### FR-58: Raise a Return Request
*Status: Confirmed (Technicians raise, Managers approve), with Default structure. Priority 3.*
A Technician, Manager, or Owner can raise a Return Request for a device reason by entering the Issue Code, the reason (transit damage, wrong item, dead on arrival, warranty claim, or suspected counterfeit), the quantity, photos, and the device's serial number where the Product has one. A change of mind needs a return authorisation from Support instead of a fault check. A non-delivery claim has no Issue Code: an Owner or Manager opens it when a Shipment is Lost or overdue. A request raised by a Technician waits for a Manager or Owner to match it to the specific Order Line and approve or decline it before it reaches the platform, and a request raised by a Manager or Owner is approved at creation. `[ASSUMPTION: the platform suggests the matching Order Line from the serial number and the approver confirms or corrects it, and the corrected line must contain the serial or Product bound to the code, belong to the same Business Account, and be delivered.]` Realizes UJ-7.

**From UX design and architecture (9 October 2026).** Owners and Managers see all of a business's Cases; a Technician sees their own.

**Consequences (testable):**
- A Return Request for a device reason cannot be raised without a valid Issue Code.
- A Technician-raised request is invisible to Support and Operations until a Manager or Owner approves it, and a decline gives the Technician a reason.
- A Technician sees no amounts anywhere on a Return Request.
- Open plus decided quantity per Order Line cannot exceed the delivered quantity, there is one open Return Request per serial, and a serial already Resolved cannot be raised again.

#### FR-59: Reporting windows
*Status: Confirmed (decision 8); counsel to vet. Priority 3.*
A Return Request must start within these windows, measured from the courier-confirmed delivery date or the collection date: 5 days for transit damage or a wrong item, 30 days for a dead-on-arrival unit (or the Supplier's window where it is shorter), 7 days for a change of mind, and the Warranty Period for a warranty claim. A suspected counterfeit can be reported until the Warranty Period plus 90 days, and later with Admin approval. The window is met by the time of the first logged Case, not by the time the Return Request is raised. Admin can change the windows, and a change applies only to Cases opened afterwards.

**From UX design and architecture (9 October 2026).** The app never blocks a report: one past the window still opens a Case.

**Consequences (testable):**
- A request is judged against the window by the first Case time.
- A window change never affects a Case already open.

#### FR-60: Investigation and decision
*Status: Confirmed (Support records arrival, Admin or Operations decide), with Default checks. Priority 3.*
When a device arrives, Support photographs the unit, label, and packaging, records the physical serial number, and investigates it. A physical serial that does not match a serial listed on the Issue Code rejects the return automatically and flags the account. A non-serialised return needs photos taken before shipping and a sealed-packaging check. Admin or Operations then decide the remedy (FR-61), following the Supplier's or manufacturer's RMA process where one exists; whoever investigated does not decide. A unit that tests fine is returned at the Integrator's cost, which is added to their next Amount Due or deducted from an open Credit Note and shown in the decision. A Support member may reclassify the reason with a note, which re-evaluates the window, exclusions, and remedy, and the Integrator sees the change. Realizes UJ-7.

**Consequences (testable):**
- A Return Request moves through Raised, Approved by the Integrator, Item Received, Under Investigation, Decided, Awaiting Finance or Awaiting RMA, and Resolved, or is Rejected or Lapsed (a device arriving after a lapse is Held), with each change recorded with who and when; Decided means a remedy is chosen and Resolved means it has been delivered.
- A mismatched serial rejects the return and flags the account.
- The Integrator sees the status and any rejection reason.

#### FR-61: Remedies
*Status: Confirmed (decisions 5 and 8); counsel to vet. Priority 3.*
The remedy depends on the reason. Transit damage and wrong item: a replacement from stock, otherwise a Credit Note. Dead on arrival: a replacement, then a manufacturer repair, then a Credit Note. Warranty claim: the manufacturer's RMA only, rejected with the manufacturer's reason if it declines. Suspected counterfeit: once Confirmed, a cash refund by bank transfer; if Cleared, the unit goes back to the Integrator. Change of mind: a Credit Note less a 15% restocking fee, only for sealed, stocked items. Non-delivery: a re-dispatch or a cash refund. The restocking fee applies to the pre-VAT goods price at the price paid, and VAT is credited in full. Replacements are zero-value Shipments under the original Order with their serials recorded, with delivery borne by the platform where the fault is its own, and they inherit the original unit's remaining Warranty Period. Realizes UJ-7.

**Consequences (testable):**
- A change-of-mind Credit Note equals the pre-VAT line price less 15%, plus the VAT on it.
- A replacement draws on stock and creates no new charge and no new Completed Order.
- A warranty claim never produces a cash refund.

#### FR-62: Return freight and handover
*Status: Confirmed (decision 8). Priority 3.*
The Integrator sends or brings the device to a Store, either a collection point or the Main Store, or hands it to a courier with a tracking reference. Where the fault is the platform's own (its error, transit damage, a verified dead-on-arrival unit, or a counterfeit), the platform reimburses actual courier cost, evidenced by the courier's receipt and capped at the Zone Rate for the same route, by Credit Note, one claim per return; otherwise the Integrator pays. Realizes UJ-7.

**From UX design and architecture (9 October 2026).** The courier handover form asks for a photo of the courier's receipt.

**Consequences (testable):**
- Each Return Request records who pays freight and the amount reimbursed.

#### FR-63: Exclusions
*Status: Confirmed (decision 8); counsel to vet. Priority 3.*
These are not returnable for change of mind: Back-order Items, special orders, software and licences, opened or installed goods, end-of-life, refurbished, or demonstration items, and items missing their serial numbers or original packaging. Warranty does not cover misuse, power-surge damage, or installation labour. The terms accepted at sign-up (FR-69) state these exclusions, and the Product's returnability flags (FR-12) drive them.

**Consequences (testable):**
- A change-of-mind request on an excluded item is refused with the reason shown.

#### FR-64: Warranty pass-through
*Status: Confirmed (decision 8). Priority 3.*
Each Product records the manufacturer's Warranty Period. The platform shows each delivered Order Line's warranty expiry, computed from the dispatch date, or the manufacturer's earlier date where known, and treats a warranty claim as a Return Request resolved through the manufacturer's RMA, with no labour cover and no platform warranty beyond dead on arrival. A Product with no Warranty Period has no warranty and warranty claims for it are refused with a reason. `[ASSUMPTION: the warranty clock starts when the platform dispatches the goods, because manufacturers typically start it at distributor shipment, so the platform passes on a shorter effective warranty than the manufacturer's headline period.]` Realizes UJ-7.

**From UX design and architecture (9 October 2026).** Delivered Order Lines show "Warranty until", and the Orders list has a Problems segment.

**Consequences (testable):**
- A delivered Order Line shows its warranty expiry date.
- A Pick-up Shipment's warranty starts when the goods are marked Ready for Pickup.

#### FR-66: Counterfeit procedure
*Status: Confirmed (decision 8); counsel to vet. Priority 3.*
Support can flag a suspected counterfeit with evidence attached. Operations or Admin can then mark a Product or batch (a goods-in record, FR-74) as Suspected, which stops its sale, quarantines the stock, flags affected Order Lines and Proposals, blocks payment confirmation and shipment of affected lines, and notifies every affected Business Account; a hold on a whole Product needs a second approver. A suspected counterfeit moves to Confirmed (by a manufacturer serial check or a two-person decision with photos) or Cleared, which lifts the hold, and is decided or escalated within 14 days. Quarantined stock is never restocked or resold. Operations notifies the Supplier and Finance credits affected Integrators; any report to the regulators (SON, FCCPC) is made by Admin outside the platform.

**Consequences (testable):**
- A Product marked Suspected cannot be added to a Project or Cart, and existing Project Items show a warning.
- A cash refund is available only once the suspected counterfeit is Confirmed.
- Clearing a suspected counterfeit restores the Product to sale.

**Notes:** `[NOTE FOR PM]` Final terms belong in the sign-up terms and should avoid "no refunds" wording.

### 4.14 Reporting and Finance Exports

**Description:** The platform's success measures and Finance's monthly work come from the platform, not from spreadsheets.

**Functional Requirements:**

#### FR-80: Dashboard and exports
*Status: Default. Priority 2.*
The founder has a dashboard covering SM-1 to SM-9 and SM-C1 to SM-C5, with the definition and data source shown on each tile. Finance can export CSV files of Orders, Payments, Credit Notes with their ageing, monthly VAT output, and bank reconciliation. Each Order Line carries the pricing record defined in NFR-4. Realizes UJ-6.

**Consequences (testable):**
- Realized markup (SM-C1) can be computed for any Completed Order from its Order Lines alone.
- A monthly VAT output export reconciles to the tax invoices issued (FR-77).

### 4.15 Cross-Cutting NFRs

- **NFR-1 Scale.** At launch: 10 Integrators, 500 or more Products, about 15 Orders a month. The system is designed for 500 Integrators, 5,000 Products, and 500 Orders a month (about three times the month-60 target in SM-3).
- **NFR-2 Operational SLA.** Staff queue targets are in FR-78; the headline commitment is that a Receipt is confirmed within two Business Hours of arriving inside Business Hours.
- **NFR-3 Security and privacy.** NIN is stored as the provider's verification reference, a keyed hash for uniqueness, and the last four digits; the full NIN is kept only while an account is in manual review and deleted 30 days after the decision. Staff see it masked, with a logged, reason-coded reveal. NIN is excluded from logs, analytics, error reports, and exports, and its keys are held outside the database and cover backups. The Privacy Notice lists processors and hosting locations; the platform handles data-subject access and deletion requests, closes accounts per FR-72, and has a breach runbook with the 72-hour notification step. `[NOTE FOR PM: confirm obligations under the Nigeria Data Protection Act 2023 with counsel.]`
- **NFR-4 Money accuracy.** Decimal arithmetic and the rounding rule in FR-19 apply everywhere. Every priced artifact (Proposal, Order, Order Line) stores the Platform Rate, Tier Discount, Back-order Discount, VAT rate, unit USD cost, Platform Markup, and Active Supplier used; an Order Line also stores the discount type and percentage applied and its promised Lead Time.
- **NFR-5 Availability and recovery.** 99.5% monthly availability, excluding announced maintenance; recovery point 1 hour, recovery time 4 hours, and a restore drill each quarter, because Receipts are evidence of payment.
- **NFR-6 Devices and offline behaviour.** Android 9 and later on Chrome, and iOS 16.4 and later for the installed app. The app shows install guidance on iOS, warns about storage eviction, and offers manual sync where background sync is unavailable.
- **NFR-7 Performance budgets.** The Catalogue list loads within about 5 seconds on the FR-55 test profile; Project open, Proposal generation, and Receipt upload each have a budget set during UX design.
- **NFR-8 Retention.** Retention periods for Receipts, client details on Proposals, delivery addresses, Terms acceptances, and the audit trail are set with counsel and the accountant. `[NOTE FOR PM: financial records are expected to need about 6 years.]`
- **NFR-9 Scheduled jobs and timers.** The monthly tier recalculation, the 24-, 40-, and 48-hour order timers, Proposal and Credit Note expiry, and the Business Hours clock store time in UTC, display West Africa Time, run idempotently, and alert the founder when a job misses.
- **NFR-10 Consistency.** Every money-affecting transition (Payment confirmation, stock reservation, Order creation, cancellation, approval, expiry, Credit Note application, Vouch Code redemption) is atomic and idempotent, and when two actions race the first valid one wins and the other receives the current state. The edge-case register in the addendum lists the guards.

## 5. Boundaries and Risks

### 5.1 Constraints and Guardrails

- **Payments.** v1 uses manual bank transfer with no payment gateway and no top-up wallet. The only balance the platform holds is a Credit Note arising from an overpayment, stock-out, cancellation, or approved return, applied by Finance. Whether a closed-loop balance triggers licensing is unaddressed in Nigeria (see addendum), so counsel should review Credit Notes with the wallet questions, and a spendable wallet is parked to v2.
- **Pricing and restricted data.** Technicians and Level 1 and Level 2 Users never receive any money field (FR-11), and no Integrator ever sees Supplier Cost, Platform Markup, or USD amounts.
- **Trust.** Selling Prices and ordering reach only vouched Level 3 Business Accounts (FR-3, FR-11). The platform also stands behind the authenticity of what it sells: Products come from Suppliers the platform has approved (FR-14), serial numbers are recorded at dispatch (FR-65), and a confirmed counterfeit earns a cash refund (FR-61, FR-66).
- **Terms.** Return, warranty, cancellation, and Credit Note terms go in the terms accepted at sign-up (FR-69) and avoid "no refunds" wording. Counsel should vet them: business buyers probably fall outside consumer-protection law, but implied sale-of-goods conditions likely apply.
- **Suppliers.** Each Supplier agreement should state return, dead-on-arrival, and counterfeit-chargeback terms before the Supplier's Products are listed.

### 5.2 Risks and Mitigations

- **Naira volatility.** Mitigated by USD pricing, a single Platform Rate set from the platform's actual dollar-buying rate, daily reconfirmation and typo guards (FR-19, FR-20), the Price Lock (FR-38), and a tolerance setting (FR-35). Residual exposures: an Integrator can choose when to pay inside the window and so holds a small free option on the rate, the platform bears the rate between payment and buying stock (longest for Back-order Items), and NGN 5 is a thin band. The monthly rate gain or loss on locked Orders is reported through SM-C1.
- **Counterfeit product.** Mitigated by curating the Catalogue from the founder's existing Suppliers, approving Suppliers before they are active, recording serial numbers and goods-in batches, and the counterfeit procedure (FR-14, FR-65, FR-66, FR-74).
- **Receipt fraud and money-out abuse.** A forged or reused Receipt, a fake buyer, or a bad-faith claim can take goods or money. Mitigated by matching every Payment to a bank-statement credit, unique bank references, a second reviewer for sender mismatches, refunds only to a sender account on record, a second approver above a threshold, extra care on the first Orders of a new Level 3 account, and Holds and blocklists (FR-41, FR-43, FR-52, FR-72).
- **Finance and Operations bottleneck.** The Price Lock and every queue depend on a small team. Mitigated by upload-time locks (FR-38), queue targets with alerts and escalation, named deputies, and a holiday calendar (FR-76, FR-78). One sick day on a three-person team still breaks the two-hour target.
- **Vouching abuse.** Mitigated by established-only vouching, Owner-only generation, caps, 48-hour expiry, a named voucher on every request, staff approval, and suspension (FR-5 to FR-9).
- **Cold start.** Mitigated by seeding from the founder's own Suppliers and Integrator network (FR-8).
- **No technical moat and reliance on the founder's network.** The advantage is trust and access, which buys time but is not permanent protection. Mitigated by the loyalty flywheel (FR-21), which makes consolidating volume the rational choice, and by moving fast on the supplier network and documentation depth.
- **Import, compliance, and courier fragility.** Customs documentation delays, SON and NCC conformity, CBN foreign-exchange access, and dependence on one courier partner can all stretch Lead Times. Mitigated by Lead Times and delivery estimates shown before payment, Zone Rates tied to the courier tariff, compliance references on Products, and Supplier agreements (FR-12, FR-14, FR-17, FR-47).
- **Scope against build cost.** The USD 30,000 build estimate does not price returns, Credit Note ledger, bulk upload, WhatsApp, or loyalty tiers; the staged build in §6.1 protects the breakeven path.

### 5.3 Non-Goals (v1)

- Not an open marketplace: Suppliers do not onboard themselves or log in, and v1 has no purchase-order, inbound-receiving, or supplier-chargeback workflow beyond the goods-in record (FR-74).
- Not a consumer product: no end-client accounts, no B2C, and no hiring of an integrator through the platform.
- Not a payments company: no top-up wallet, no payment gateway, and no financing or trade credit. The only balance held is a Credit Note. Client-to-integrator payment is off-platform.
- No on-site room-measurement tooling (AR or otherwise); field use means working offline and reaching Documentation.
- No platform fee, commission, or subscription: markup on goods is the only revenue, and restocking fees only recover cost.
- No packaged "solution" or bundle entity; Documentation is per Product only.
- No per-Room or per-line Integrator Margin, and no labour pricing engine; Proposals carry one margin and simple custom lines.
- Not a multi-country product in v1.

## 6. MVP Scope

### 6.1 In Scope, by build priority

**Priority 1: first-Order critical**
- Three-level access with NIN verification, Level 3 by vouching with staff approval, Business Accounts with Owner, Manager, and Technician roles, authentication and recovery, terms acceptance, and holds (FR-1 to FR-11, FR-69 to FR-72)
- A Catalogue of IT, AV, security, and home-automation Products for residential and light commercial jobs, with USD pricing, a Platform Rate, per-Product Range markup, a margin floor, multiple Suppliers, bulk upload, search, stock quantities, and the goods-in record (FR-12 to FR-18, FR-73, FR-74)
- Pricing, VAT, rounding, and the Platform Rate (FR-19, FR-20, FR-23, FR-24)
- Project Designer by Room with a Parts List, Proposals as PDFs with Integrator Margin (FR-25 to FR-27, FR-29 to FR-32)
- Cart and Orders, back-order with opt-in, pick-up at Stores (one at launch) and courier delivery with Zone Rates, the Price Lock, cancellation, and amendment (FR-33 to FR-39, FR-57, FR-75)
- Payment by bank transfer with Receipt upload, bank-statement confirmation, Credit Notes, and tax invoices (FR-40 to FR-43, FR-77)
- Approval, Shipments with tracked statuses and exceptions, Delivery Zones, and Stores (FR-44 to FR-47, FR-56)
- A staff console with Finance, Operations, and Admin roles (Support duties sit with Operations at launch), queues, continuity, and an audit trail (FR-52, FR-53, FR-76, FR-78)
- Serial-number capture at dispatch (FR-65) and the installable app (FR-54, FR-55)
- Nigeria as the only launch market

**Priority 2: launch week**
- Offline design, Documentation and its offline copies (FR-28, FR-48 to FR-50)
- Loyalty Tiers on trailing six-month spend (FR-21, FR-22)
- Notifications (FR-51), Cases and serial lookup (FR-67, FR-79), and the dashboard and exports (FR-80)

**Priority 3: fast-follow, about three months after launch**
- The in-app returns workflow: Issue Codes, Return Requests, investigation states, remedies, and the counterfeit procedure (FR-58 to FR-64, FR-66, FR-68). Until then returns run manually by phone and WhatsApp, with Support recording Cases, serial lookup, and Credit Notes by Finance.

### 6.2 Out of Scope for MVP

- Industrial automation and complex commercial projects such as multi-resident buildings, hotels, and office complexes (a later phase)
- End-client accounts, B2C, and the hire-an-integrator idea (v2 roadmap, see brief addendum)
- A spendable account balance (wallet) for Integrators (v2, pending legal advice) `[NOTE FOR PM: the founder would build it sooner if a regulatory ceiling short of licensing exists; research found none.]`
- Per-Integrator virtual bank accounts for automatic payment matching (a v2 candidate)
- Bundled "solutions" with their own guides (v2)
- Admin override of repricing between hour 24 and hour 48 (v2)
- Partial ordering of a Project by Room, and per-Room or per-line Integrator Margin (v2 considerations)
- Supplier-facing reporting, such as sell-through
- Weight-based delivery rates and automated courier tracking
- Expansion beyond Nigeria to the rest of West Africa

## 7. Success Metrics

*Targets form a breakeven path rather than a straight 20% growth line. They start from a launch cohort of 10 Integrators and 15 Orders a month at a typical USD 500 per Order before VAT, and assume Orders grow about 60% a year. The cost inputs behind the path come from published benchmarks researched in October 2026 and are listed in the addendum; they are benchmarks, not quotes. All targets are `[ASSUMPTION]` for the founder to confirm. Counts are shown beside percentages because the cohort is small: at month 12 one account moves a rate by about six points.*

**Primary**
- **SM-1: Repeat-purchase rate** — the share of onboarded Business Accounts that place a second Completed Order within 90 days of their first. Target 60% or more by month 12. Checkpoints at month 3 and month 6 track Level 3 activation, first Order, first Proposal, and second Order. Validates FR-34, FR-45, FR-21.
- **SM-6: Breakeven** — net contribution of about USD 80 per Order after discounts, FX movement, and handling covers monthly fixed costs. At 60% annual Order growth the first positive month is about month 11, and every month from about month 26 onward covers its costs after the fixed-cost steps; cumulative operating profit recovers the assumed USD 30,000 build cost by about month 47. Funding need peaks at about USD 34,000 before inventory (around month 16) and about USD 48,000 including inventory float (around month 30); counted net of float, cash is about breakeven at month 60. Validates FR-19 to FR-24, FR-34, FR-45.

**Secondary**
- **SM-2: Active buying Integrators** — onboarded Business Accounts with at least one Completed Order in the trailing 90 days. Targets: 12 of 16 onboarded at month 12, 20 of 26 at month 24, 31 of 41 at month 36, 50 of 66 at month 48, 79 of 105 at month 60. The Completed requirement is intentional: it lags the order. Validates FR-3, FR-7.
- **SM-3: Monthly Orders reaching Completed** — targets: 24 at month 12, 38 at month 24, 61 at month 36, 98 at month 48, 157 at month 60. Validates FR-34, FR-45.
- **SM-4: Project-led Orders** — the share of Orders, by count and by value, created from a Project rather than a Cart. Target 40% or more by value, which shows the designer is driving money. Validates FR-25 to FR-34.
- **SM-5: Catalogue breadth** — active Products and active Suppliers. Product targets: 800 at month 12, 1,300 at month 24, 2,000 at month 36, 3,000 at month 48, 4,500 at month 60; the Supplier target is set from the launch Supplier list. Validates FR-12, FR-15.
- **SM-7: Documentation coverage** — the share of Products ordered in the trailing 90 days that have Documentation. Target 80% by month 6 and 100% by month 12. Validates FR-48 to FR-50.
- **SM-8: Time to first Proposal** — the median time from creating a Project to generating its first Proposal. Target under 60 minutes. Validates FR-25 to FR-30.
- **SM-9: Proposal-to-Order conversion** — the share of Proposals followed by an Order from the same Project within 30 days. Starting hypothesis 30%, to be reset after month 6. Validates FR-29 to FR-35.

**Counter-metrics (do not optimize)**
- **SM-C1: Realized markup after discounts and FX moves** — the blended average realized markup on Completed Orders, computed from Order Line records, target 22% or more against the 25% nominal (a Tier 4 Order alone is 17.5%, so this tracks the mix). Do not buy volume with discounts or silent rate absorption. Counterbalances SM-1 and SM-3.
- **SM-C2: Credit Notes for post-payment stock-outs** — fewer than 2% of Order Lines. Do not grow Catalogue breadth by listing items that cannot be supplied. Counterbalances SM-5.
- **SM-C3: Back-order lateness** — at least 90% of Back-order Items delivered by their promised ship-by date plus 7 days, where the promise starts at payment confirmation. Do not promise optimistic Lead Times to win orders. Counterbalances SM-3.
- **SM-C4: Confirmation on time** — at least 95% of Payments confirmed within two Business Hours, excluding those waiting for bank-statement evidence. Do not rush confirmation at the cost of checking the full amount. Counterbalances SM-3.
- **SM-C5: Returns and refund cost** — Credit Notes and refunds issued for returns and claims at 2% of revenue or less, the provision the model assumes. Do not make remedies so generous that returns erode contribution. Counterbalances SM-1.

`[NOTE FOR PM]` The path needs about 60% annual Order growth, far above the 20% first assumed. At 20% the model never reaches operating breakeven: 18 Orders a month yield about USD 1,440 against about USD 1,800 of monthly fixed costs. The path is sensitive: if net contribution is USD 72.50 (the former SM-C1 floor of 20%; the current target is 22%) the sustained crossing slips to about month 28 and the build is recovered around month 53, and with a 2% revenue provision for returns, warranty, and shrinkage it is about month 55, with funding peaking near USD 60,000. At 70% growth the path is comfortable: sustained from about month 15 and about USD 46,000 positive net of float at month 60. Returns, warranty, shrinkage, courier loss, and FX spread are not in the base model.

## 8. Decision Log and Open Questions

### 8.1 Founder decisions

All 15 decisions were confirmed by the founder on 7 October 2026; decision 1 was changed from the proposed default so that Selling Prices show only from Level 3. The table records each decision as confirmed and the FRs it governs. Changing one later means updating those FRs.

| # | Decision | Rule as confirmed | FRs | Status |
| --- | --- | --- | --- | --- |
| 1 | Selling Prices visible from Level 3 only | Hidden at Levels 1 and 2; Level 2 only confirms identity | FR-2, FR-3, FR-73 | Confirmed |
| 2 | Expiry after a Receipt is uploaded | 48 hours after the last upload or decision, 7-day maximum; confirmed money becomes a Credit Note | FR-38, FR-41 | Confirmed |
| 3 | Re-upload after a rejected in-time Receipt | Keeps the original upload time within 24 hours if the full amount matches | FR-38, FR-41 | Confirmed |
| 4 | Underpayment tolerance | Finance-set, placeholder NGN 200, audited write-off | FR-38, FR-42 | Confirmed |
| 5 | Credit Notes and cancellation | Cash-refundable for overpayment, counterfeit, non-delivery, and platform fault; credit-only for cancellation, change of mind, and uncollected Pick-ups; 12-month application window with reminders; counsel to vet | FR-43, FR-57, FR-61 | Confirmed |
| 6 | Loyalty tiers against the markup floor | Keep 0/2/4/6%, cap Back-order Discount at 10%, SM-C1 as a blend | FR-21, FR-23 | Confirmed |
| 7 | Exchange-rate guardrails | Daily reconfirmation, NGN 5 trigger, second approver above 3%, typo guard above 10%, 48-hour staleness block | FR-20 | Confirmed |
| 8 | Returns terms | Windows of 5, 30, and 7 days from first logged contact, 15% restocking, remedy matrix, freight reimbursement up to the Zone Rate, courier handover counts as returned; counsel to vet | FR-58 to FR-64, FR-66, FR-68 | Confirmed |
| 9 | Uncollected Pick-ups | Reminder at 7 days, Uncollected at 14, cancel and restock after 30 days with a credit-only Credit Note less 15% | FR-45 | Confirmed |
| 10 | Overdue back-orders | At promised ship-by plus 7 days, offer cancellation for a Credit Note; Operations credits a line the Supplier cannot supply | FR-17, FR-36, FR-57 | Confirmed |
| 11 | Repeat Orders per Project, and custom Proposal lines | Both allowed | FR-29, FR-34 | Confirmed |
| 12 | Tax invoices | Issued per Paid Order and per Credit Note; fields from the accountant | FR-77 | Confirmed |
| 13 | Staging | Priority 1, 2, and 3 as in §6.1; returns manual-first | §6.1 | Confirmed |
| 14 | Launch service area | Lagos zones active at launch; other zones enabled as rates and estimates are set | FR-47 | Confirmed |
| 15 | Support duties inside Operations | Confirmed; revisit if volumes grow | FR-52, FR-79 | Confirmed |

### 8.2 Counsel and accountant confirmations

- **Accountant:** VAT registration at this scale, tax-invoice fields and numbering, VAT on delivery charges and Credit Notes, e-invoicing timing (reported for smaller businesses from July 2027), and the retention period for financial records.
- **Counsel:** whether the Credit Note ledger counts as deposit-taking, VAT on prepayments, insolvency treatment of balances, the 12-month expiry and refund terms, the returns terms and exclusions, consumer-law exposure for business buyers, obligations under the Nigeria Data Protection Act 2023 (retention, breach notification, access and erasure), NCC type approval and SONCAP obligations for listed Products, whether the wallet is permissible, the nine wording items under Voice and Tone in the UX `EXPERIENCE.md`, and what is measured about use, cookies, and analytics.

### 8.3 Open questions

1. **Delivery cost realism.** A flat Zone Rate per Shipment may lose money on heavy items; confirm the first Product categories and whether weight bands are needed at launch.
2. **Warehouse to Store.** Moving goods from the central warehouse to a Store happens outside the platform, so there is no transfer step between Processing and Ready for Pickup. When a second Store opens, does the Zone Rate depend on the dispatching Store?
3. **Supplier lead times and Supplier orders.** The platform places Supplier orders only after payment (FR-18); confirm Suppliers can meet the promised Lead Times on that basis.
4. **Breakeven inputs.** Published benchmarks stand in for real quotes. Replace them with actual agency quotes, salaries, and rent, and decide how inventory float (about USD 48,000 peak funding including float) is financed.
5. **Return window for distant Integrators.** The 48-hour Issue Code is judged against the courier handover (FR-68); confirm whether a longer window is needed for far zones once courier times are known.

Raised by the UX design (9 October 2026), for the founder unless marked:

6. **Sign-in limits (FR-1).** Wrong-code and resend caps, and the lockout time.
7. **Rejected Level 3 requests (FR-6, FR-7).** Whether a rejected request can be made again, and with what.
8. **Queues without targets (FR-71, FR-78).** Owner recovery requests, Operations' cancel and change requests, delivery-price requests, and call-back-due Cases have no queue or target; the UX uses 1 Business Day for cancel requests and delivery prices and the approval target for changes. FR-66's 14-day counterfeit clock has no card on the Admin home.
9. **Closing with a credit-only balance (FR-72).** Such an account cannot close for up to 12 months; the UX draws a "call us" exception.
10. **Fractional quantities (FR-26).** Which units of measure allow them.
11. **Documentation versions per phone (FR-50).** Whether the server records which version each phone downloaded, to show how many phones hold an old one.
12. **Receiver's one-time code (FR-45).** How the Pick-up receiver's code is generated and sent.
13. **Unusual-pattern alerts (FR-53).** The baseline of usual counts per person.
14. **Staff sign-in (FR-71).** The method for staff two-step sign-in and 12-hour sessions (the UX shows a code or passkey, then a second check).
15. **Dashboard inputs (FR-80).** A plan start date setting, the cost inputs SM-6 needs that the console does not hold, and confirming that Finance exports sit on a Reports and exports page.
16. **Supplier orders (FR-17, FR-74).** With no Supplier order entity, back-orders show only promised ship-by dates and a late flag.
17. **Zones (FR-47, FR-56).** Checks that an area belongs to exactly one zone, the initial local government areas per zone, and real Zone Rates and estimates.
18. **Product Ranges.** Creating, renaming, and retiring a range is assumed to be Admin's.

## 9. Assumptions Index

- §3 Integrator Margin: a markup on the Integrator's cost, consistent with Platform Markup.
- FR-1: the first sign-up for a business creates its Business Account and Owner.
- FR-11: Technicians see Order and Shipment status without amounts.
- FR-15: bulk upload applies valid rows and reports invalid ones.
- FR-16: Operations owns Stock Thresholds.
- FR-30: the Integrator Margin applies to the VAT-inclusive Selling Price, which gives the same client total as margin before VAT and VAT after.
- FR-35: the NGN 5 tolerance protects the exchange rate only, while the Tier Discount is applied fresh.
- FR-50: large videos are saved offline only on request.
- FR-53: the 6-year retention period for financial entries is to be confirmed with the accountant.
- FR-56: Operations and Admin both maintain Stores and choose a Shipment's dispatch Store; "Admin and or manager" is read as Admin and Operations.
- FR-58: the platform suggests the matching Order Line from the serial number, and the approver's correction must stay within the same Business Account and serial or Product.
- FR-64: the warranty clock starts when the platform dispatches the goods.
- FR-68: each Issue Code serves one Return Request, and handing a device to a courier with a tracking reference counts as returned within the window.
- §7: all success-metric targets, including the breakeven path and the cost inputs behind it.
