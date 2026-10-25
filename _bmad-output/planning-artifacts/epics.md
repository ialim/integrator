---
stepsCompleted: [1, 2, 3, 4]
inputDocuments:
  - _bmad-output/planning-artifacts/prds/prd-integrator-2026-09-27/prd.md
  - _bmad-output/planning-artifacts/prds/prd-integrator-2026-09-27/addendum.md
  - _bmad-output/planning-artifacts/architecture/architecture-integrator-2026-10-09/ARCHITECTURE-SPINE.md
  - _bmad-output/planning-artifacts/ux-designs/ux-integrator-2026-10-07/DESIGN.md
  - _bmad-output/planning-artifacts/ux-designs/ux-integrator-2026-10-07/EXPERIENCE.md
  - _bmad-output/specs/spec-integrator/SPEC.md
---

# integrator - Epic Breakdown

## Overview

This document provides the complete epic and story breakdown for integrator, decomposing the requirements from the PRD, UX Design if it exists, and Architecture requirements into implementable stories.

## Requirements Inventory

Conventions used in this inventory:

- FR and NFR numbers are the PRD's own stable numbers (FR-1 to FR-80, NFR-1 to NFR-10). They are listed in PRD section order, so the numbers are not sequential.
- `[P1]`, `[P2]` and `[P3]` are the PRD's build priorities (§6.1): first-Order critical, launch week, and fast-follow about three months after launch.
- Each entry is a summary. The PRD's "Consequences (testable)" under each FR, the addendum's edge-case register (rows 1 to 82) and its test vectors remain the source for acceptance criteria.
- Glossary terms are used exactly as the PRD defines them. Where the PRD and the spec kernel differ, the PRD wins.

### Functional Requirements

**Accounts, Access Levels and teams**

FR-1: [P1] A visitor creates an account with an email or phone number, verified by a code that expires after 10 minutes. The verified person becomes Owner of a new Business Account at Level 1. An already-registered contact goes to login or its pending verification. Resends and wrong attempts are capped per contact, device and IP, and sign-up has bot checks.

FR-2: [P1] Level 1 Users can search and browse the Catalogue and read Documentation without prices, and cannot create Projects, Carts, Proposals or Orders. No price, discount, markup or price-derived ordering appears in any Level 1 response, cache or export.

FR-3: [P1] An Owner completes a profile (name, date of birth, address and NIN required; business address optional). On successful NIN verification the Business Account becomes Level 2, which adds only eligibility to redeem a Vouch Code and request Level 3. Level 2 sees no money field and has no creation actions.

FR-4: [P1] NIN verification passes when the NIN exists and the normalised name and date of birth match. One automatic attempt; provider timeouts retry with backoff up to 3 times over 24 hours without consuming it, then go to manual review. A definitive mismatch moves the profile to Admin manual review, and all later checks for that account are manual. A NIN belongs to one Business Account; a duplicate goes to manual review flagged. Admin can reject with a reason, the Owner can resubmit up to 3 times, then is asked to call or WhatsApp. Admin can request an optional photo of the NIN slip or ID. An Owner without a NIN is verified by Admin off-app and the check recorded. Lookups are throttled and a daily spend alert fires.

FR-5: [P1] Only the Owner of an Established Integrator can generate a Vouch Code, after a fresh one-time code. A code is at least 10 random characters, bound to one named prospect (name plus phone or email), single-use, valid 48 hours, redeemable only by a profile whose NIN-verified name matches, and locked after 5 wrong attempts. At most 2 active codes and 6 prospects per rolling 90 days per Business Account. Each code records the issuing Business Account and User. A Manager can ask the Owner for one.

FR-6: [P1] A Level 2 Owner submits a Vouch Code, confirms the Trade Name and accepts the Terms to request Level 3. A valid code places the request in Admin's review queue; an invalid, expired, used or locked code is rejected with a reason.

FR-7: [P1] Admin reviews each Level 3 request on a screen showing the voucher (Business Account, User, history, open claims), the Trade Name and a checklist (online presence, proof of an installed job, a phone call), and records a reason on every decision. Approval makes the Business Account Level 3 and names the voucher. If the voucher is suspended before the decision, the applicant re-confirms and Admin must explicitly re-confirm. A vouchee later suspended for fraud suspends the voucher's vouching rights pending review.

FR-8: [P1] Admin can create a Business Account for the first cohort ("Create a business", with a Pending Activation list) and issue platform Vouch Codes for named prospects. A seeded account is Pending Activation until its Owner verifies a contact, passes NIN verification, supplies the Trade Name and accepts the Terms; it cannot create an Order before then. The creating Admin is audited and cannot confirm that business's Payments or issue its Credit Notes.

FR-9: [P1] Admin can suspend and restore a Business Account's vouching rights and grant them manually to an account not yet established. Suspension voids that account's active Vouch Codes immediately.

FR-10: [P1] Once Level 3, an Owner invites Managers and Technicians by email or phone; a Manager can invite and remove Technicians. Only the Owner can invite, remove or demote a Manager. Invitations are bound to the invited contact, expire after 7 days and can be revoked. Invitees verify the contact and accept the Terms and Privacy Notice at first login. A contact already in another Business Account cannot accept. Removing a User or changing a role revokes sessions and purges that device's cache at its next connection.

FR-11: [P1] Role permissions (Owner, Manager, Technician) are enforced server-side per the PRD matrix. Technicians and Level 1 and Level 2 Users receive no money field in any response, notification, search ordering, export or cache. A published "Technician-safe" response schema lists the only fields a Technician response may contain, and an automated test fails when a money field or price-derived ordering is reachable by a Technician, Level 1 or Level 2 session.

FR-69: [P1] Sign-up and Level 3 approval require acceptance of the current Terms and Privacy Notice, recorded with version, time, User and IP. The Terms are one document, with key points shown at the Level 3 request. Admin publishes each version with a plain what-changed list, a preview of the Owner's screen and counsel's approval date and reference. A new version interrupts only the Owner and blocks only Order creation until accepted. WhatsApp needs an explicit per-number opt-in and an opt-out.

FR-70: [P1] An Owner or Manager maintains the Business profile: Trade Name, logo (PNG or JPG up to 2 MB), address, phone, email, optional TIN, and a VAT-registered declaration (allowed without a TIN, with a prompt to add one). A Trade Name is required before the first Proposal. The declaration controls the Proposal VAT line and tax invoices.

FR-71: [P1] Users log in with a one-time code to a verified contact, or a passkey where supported. A fresh one-time code (step-up, valid 10 minutes, one action) is required before generating a Vouch Code, requesting a refund or Credit Note application, changing a role, the Owner or a contact, adding or removing a passkey, adding a WhatsApp number, and inviting a Manager. Integrator sessions last 30 idle days; Staff sessions 12 hours with multi-factor authentication. A User sees where they are logged in and can log out everywhere else. Owner recovery is an ID check by phone plus a 72-hour cooling period announced to every contact on record. Managers and Technicians have no recovery route. A change of Owner requires the incoming Owner to verify a contact and pass NIN verification.

FR-72: [P1] Admin (or Finance for payment fraud, ratified by Admin within one Business Day) can put a Business Account on Hold (no new Orders or refunds; Payments still processed), revoke Level 3 (back to Get approved, prices hidden, existing Orders still processed), disable a User, and add a NIN hash, contact or sending bank account to a blocklist checked at sign-up and Payment confirmation. Each action needs a reason, is audited and notifies the Owner unless flagged as a fraud review. A blocked person sees a neutral refusal. An Owner can close a Business Account only with no open Order, Return Request or Case and no unrefunded Credit Note balance; personal data is then erased or pseudonymised except records the law requires.

**Catalogue, Suppliers and stock**

FR-12: [P1] Operations creates and edits Products with identity (unique, unchangeable SKU, brand, model, name, description, images, Product Range), measures (unit of measure, pack size, weight, dimensions), returns and compliance (serialised flag, serial-format field, Warranty Period, dead-on-arrival window, four returnability flags each answered Yes or No with no default, compliance reference), lifecycle and cost (draft, active, discontinued; quarantine flag; dated Supplier Costs), and stock and content. A Product cannot become active without the fields later requirements rely on. Each Product Range carries an Admin-set NCC or SONCAP flag. Every change is audited.

FR-13: [P1] Admin sets a default Platform Markup per Product Range (initially 25%), a per-Product override, and a margin floor (initially 8% over Supplier Cost). Price computation never takes the pre-VAT price below Supplier Cost × (1 + floor); a save that would breach it is refused and names the Product. A changed markup updates Wholesale Prices immediately and never alters existing Orders.

FR-14: [P1] A Product holds Supplier Costs from several Suppliers with exactly one Active Supplier. A Supplier must be approved by Admin, and "Approve" stays off until its returns, dead-on-arrival and counterfeit-chargeback terms are recorded (or Admin approves with a written reason). Admin switches the Active Supplier (second approver, with a preview of affected Carts and Projects); a switch to a Supplier with no cost or a cost of zero or less is refused. Stopping a Supplier takes its Products off sale after a preview, with a replacement named per Product.

FR-15: [P1] Operations bulk-uploads Products from a downloadable spreadsheet template, matched on SKU. A dry run shows creates, updates, price changes and failed rows with row number and reason. Valid rows apply and failed rows are reported; a repeated SKU is a failed row. Admin-only columns are ignored for Operations with a note per row. A file changing prices on more than 20 Products, or a Supplier Cost by more than 15%, waits for Admin approval as a whole file, with one-click rollback. A 500+ row launch file imports in one upload.

FR-16: [P1] Each Product has on-hand and reserved quantities; available = on-hand − reserved. A Product is In Stock when available is above its Stock Threshold, otherwise Out of Stock. Operations set thresholds, cannot set on-hand below reserved without an Admin override, and adjustments above an Admin-set size need Admin sign-off. Releasing safety stock to a Back-order Item is a ledger line with a reason.

FR-17: [P1] Operations enables back-order per Product with a Back-order Discount (up to 10%; more needs Admin approval) and a Lead Time, shown wherever the Product is priced or added. At Order creation both are captured on the Order Line, and the promised ship-by date is the payment confirmation date plus the Lead Time. If back-order is disabled before the Order is Paid, the line is credited with notice.

FR-18: [P1] Stock is reserved only when payment confirmation brings an Order to Paid, atomically. A partial shortage reserves what exists and credits only the shortfall. No Supplier order is placed for a line before its Order is Paid. Adding to a Project or Cart, creating an Order or uploading a Receipt reserves nothing.

FR-73: [P1] Users from Level 1 search by name, brand, model and SKU with typo tolerance, browse a category tree built from Product Ranges, filter by brand, Product Range and stock status, and sort by name. Level 3 Owners and Managers can also sort by price and see the Selling Price; others see "Prices are shown once your business is approved." Price endpoints are rate-limited per account and per IP, and there is no price export or bulk listing. Search works offline on the synced Catalogue. Empty and no-result states are defined.

FR-74: [P1] Operations record each stock receipt (goods-in) with Supplier, date, quantity, batch or lot reference, landed cost (invoice, freight, duty and clearing in USD or NGN, with the rate used) and serial numbers for serialised Products. Every stock change (receipt, reservation, release, adjustment, quarantine) is a ledger entry with a reason and reference, and stock on hand equals the sum of ledger entries. Back-order lines carry "ordered from Supplier" and "received" markers; a line cannot be marked received without a goods-in record.

**Pricing, Platform Rate and loyalty**

FR-19: [P1] Selling Price per unit = Supplier Cost × (1 + Platform Markup) × (1 − applicable discount) × (1 + VAT rate), converted to naira at the Platform Rate, never below the margin floor pre-VAT. Decimal arithmetic only; the unit price is computed at full precision and rounded half-up to the kobo once, a line total is unit × quantity, and an Order total is the sum of lines. Integrators see and pay naira only. The app may add displayed prices in whole kobo for display but never computes a price used for a Proposal or Order. The addendum's test vectors pass.

FR-20: [P1] Finance sets the Platform Rate with its reference source and spread, and reconfirms it each Business Day. A change above 3% needs a second approver and a price preview with counts of affected Carts, Projects and unlocked Orders; a change above 10% is refused unless Admin overrides with a reason; zero or below is refused. A rate older than 48 calendar hours blocks Order creation, and Finance or Admin can freeze Order creation; both show one "prices are being updated" message. A change takes effect immediately on displayed prices and never affects Orders past their Price Lock. Every change is recorded.

FR-21: [P2] Loyalty Tiers are recalculated monthly at 00:00 WAT on the 1st from trailing six-month spend: pre-VAT, pre-delivery goods value of Order Lines whose Shipment is Delivered or Signed For, in USD at each Order's stored rate, net of returned and credited lines. Bands are half-open: Base under 1,500 (0%), Tier 2 from 1,500 (2%), Tier 3 from 5,000 (4%), Tier 4 from 12,000 (6%). The tier belongs to the Business Account and can fall as well as rise.

FR-22: [P2] Owners and Managers see their current tier, spend toward the next tier in naira at the current Platform Rate, the next recalculation date, and an expected tier change projected from spend 7 days before it takes effect.

FR-23: [P1] Where a Tier Discount and a Back-order Discount both apply, the larger applies and they do not stack. The Back-order Discount is capped at 10% unless Admin approves more, and no discount takes a price below the margin floor.

FR-24: [P1] Displayed prices use the Business Account's current Loyalty Tier, and an Order uses the tier in effect at Order creation, not the tier at any earlier Proposal.

**Project Designer**

FR-25: [P1] A Level 3 User creates a named Project that opens with one default Room, renames Rooms, adds Rooms and saves. A Room can be deleted (confirmation when it holds items) and restored for 30 days. Each Project has a Parts List totalling each Product across Rooms, shown on screen and downloadable without prices.

FR-26: [P1] A User adds Products to a Room with whole-number quantities of at least 1 (fractional where the unit of measure allows), changes quantities and removes items. Out of Stock Products with back-order can be added, flagged with Lead Time; those without cannot. Discontinued and quarantined Products show a warning. Zero or below is refused.

FR-27: [P1] Owners and Managers see line prices, Room totals and Project totals. Technicians see items, quantities, Lead Times and warnings, with no money field and no Back-order Discount. Offline prices carry an "as of" time and turn brass after a day; offline, Owners and Managers hold prices only for Products in the Business Account's Projects and those recently viewed.

FR-28: [P2] Users browse and search the last-synced Catalogue and create and edit Projects offline; changes sync on reconnection. Merge is per Room and per Project Item, last writer wins per field by server receipt time; deletions are recorded; conflicts are listed with a 30-day restore; items are checked against the live Catalogue and flagged Unavailable, never discarded. The cache is partitioned by User and role. Edits queued by a removed or re-roled User are held for an Owner or Manager to accept or discard, then the cache is purged. Saved Projects lock without deletion after 14 days with no check-in (warning on day 11, banner on day 13). Logging out with changes waiting keeps them locked on the phone. Proposals, Orders and Receipt uploads are unavailable offline and say why; offline prices are never used for a Proposal or Order.

**Proposals**

FR-29: [P1] An Owner or Manager generates a Proposal for a Project with the client's details (only the name required) and one Integrator Margin for the whole Project. The platform snapshots contents, Selling Prices and Tier Discount, records the Proposal Rate and sets the expiry. It needs at least one Project Item, a Trade Name and a connection. Custom lines (labour, travel, commissioning) with description, quantity and VAT-included price appear on the Proposal only. A margin below zero is refused; above 100% asks for confirmation.

FR-30: [P1] The Proposal PDF shows the Integrator's Trade Name and logo, client details, line prices and Room totals at client prices, custom lines, Lead Time on Back-order lines, the expiry date and a plain validity sentence about the NGN 5 exchange-rate band. It carries no platform branding and never shows Supplier Cost, Wholesale Price, Platform Markup, Tier Discount, Back-order Discount, Integrator Margin or the Integrator's Selling Price in visible text or metadata. A VAT-registered business chooses per Proposal whether to show a VAT line; the VAT line is the grand total minus the sum of pre-VAT lines, so the grand total is identical either way.

FR-31: [P1] Regenerating a Proposal replaces the previous one. Only the latest Proposal per Project is kept, and no earlier version is held.

FR-32: [P1] A Proposal expires at the end of the day WAT, one calendar month after generation, clamped to a shorter month's end. An expired Proposal is marked expired and still downloadable, and ordering from its Project uses current prices.

**Cart and Orders**

FR-33: [P1] An Owner or Manager adds Products to their own Cart and orders from it without a Project. A Cart belongs to one User, is not shared, and cannot be saved as or converted into a Project. A Cart Order has an optional name, otherwise "Direct buy" and the item count. Unorderable lines are listed at the top of the Cart.

FR-34: [P1] An Owner or Manager creates an Order from a whole Project or a Cart. A Project is ordered whole and can be ordered more than once, each Order at its own prices. Unorderable lines are listed for the user to remove or replace; nothing is dropped silently. An Order needs at least one line with quantity of at least 1. At most 2 unpaid Orders per Project. The Order is bound to the confirmed Order Quote: if the Platform Rate, Loyalty Tier, Proposal validity, Back-order terms or availability changed, the platform refuses and shows the new Order Quote. Creation is idempotent.

FR-35: [P1] An Order from a Project with an unexpired Proposal, identical contents and a current Platform Rate within NGN 5 (an Admin setting) of the Proposal Rate uses the Proposal Rate; otherwise the current rate. The Proposal Rate applies only to the first Order from a Proposal. Before committing, the user sees the goods total beside the Proposal's goods total at the Integrator's cost, the difference split into exchange rate, tier, items added or removed and availability, delivery on its own line, and their margin at today's cost.

FR-36: [P1] An Order including Back-order Items requires an explicit opt-in showing each item's Back-order Discount and Lead Time, and stating that it cannot be cancelled once ordered from the Supplier (unless more than 7 days past its promised ship-by date) and is not returnable for change of mind.

FR-37: [P1] At Order creation the Owner or Manager chooses Pick-up (and a Store) or Delivery with a structured address (State, local government area, area, landmark, receiver name and phone). The Delivery Zone is looked up from State and local government area and a flat Zone Rate per Shipment is added to the Amount Due. An unresolved zone or one with no rate blocks Order creation with an offer of a manual zone pick or a delivery price from Operations. A business can request a delivery price; Operations answers within about 1 Business Day with a VAT-inclusive price valid to the end of the next Business Day, or a reason. A delivery charge never rises after payment except for a Shipment the business requests under FR-39.

FR-38: [P1] Price Lock and expiry. The Amount Due is fixed at creation. Receipts whose upload details are received within 24 hours are in time; the lock holds when confirmed in-time Payments cover the Amount Due within Finance's underpayment tolerance, and the matched bank credit's value time precedes the Receipt's upload time. A corrected re-upload within 24 hours of an Unconfirmed notice keeps the original time if the full amount matches. Otherwise the Order is repriced once, changing only the exchange rate by the FR-35 rule. An Order with no Receipt expires 48 hours after creation; with a Receipt, 48 hours after the last upload or Unconfirmed decision, at most 7 days after creation. At expiry confirmed Payments become a Credit Note and later bank credits go to the unmatched queue.

FR-39: [P1] The Owner or Manager chooses shipping in parts (in-stock first, Back-order Items follow) or in full. Shipping in full holds in-stock lines up to 14 days after Approval, then the Integrator is offered sending what is ready, paying the extra delivery at the Zone Rate before it goes, or declining and waiting. Each Shipment is charged its own delivery cost.

FR-57: [P1] An Owner or Manager can cancel an Order before Approval (optional reason); confirmed Payments become a credit-only Credit Note automatically, unchecked Receipts go to Finance, and an Order with a Payment under review needs Finance's consent. After Approval, cancellation is a request with a required reason: Shipments freeze until Operations decides within one Business Day, it applies per Shipment, and a Back-order Item already ordered from a Supplier cannot be cancelled. A Back-order Item more than 7 days past its promised ship-by date can be cancelled for a Credit Note regardless. A cancel that races Approval or expiry receives the current status.

FR-75: [P1] An Owner or Manager can change the delivery address, receiver, Store or the parts-or-full choice on an Order that is Awaiting Payment, or by request to Operations on a Paid Order before Approval (answered within the 4 Business Hour approval target). Amending changes only the delivery charge and never resets the Price Lock window.

**Payments, Credit Notes and tax documents**

FR-40: [P1] An Owner or Manager sees the platform's bank details and the Amount Due with a unique Order reference, and uploads a Receipt per transfer (JPEG, PNG, HEIC or PDF up to 10 MB, resized on the device to about 2 MB, location stripped, malware-scanned; corrupt, blank or unsupported files refused) with declared amount, sender name, sender bank, bank reference and transfer date and time. Each Order stores the bank details it showed at creation.

FR-41: [P1] Finance confirms a Payment only when it matches a bank-statement credit (statement upload or CSV import) for amount, sender and reference, recording the confirmed amount. A bank reference is used once. A sender not matching the Owner or Trade Name needs a second, different reviewer. Unmatched bank credits are listed daily; one credit can be split across Orders by allocation but never beyond its amount. Each confirmation writes an immutable ledger line. A Payment can be Reversed with a reason and a second approver, returning the Order to Awaiting Payment. Receipts or credits for Expired, Cancelled or Paid Orders go to the unmatched queue. An Unconfirmed Payment shows the literal message "Payment unconfirmed: please upload your receipt again or pay the full amount."; a third Unconfirmed upload on one Order goes to Admin as "Needs review".

FR-42: [P1] An Order stays Awaiting Payment until confirmed Payments plus any Credit Note applied cover its Amount Due within the underpayment tolerance, then becomes Paid automatically.

FR-43: [P1] Finance issues naira Credit Notes for the Glossary's eight reasons, recording source Order Line, VAT component, amount, balance, reason, issuer, issue and expiry dates, with states Issued, Partially applied, Applied, Refunded and Expired. Owners and Managers ask Finance to apply one to an Order; the application counts as a Payment from the request, and returns to the original note with its original expiry if the Order is cancelled or expires. Refundable reasons can be refunded (whole balance only) on the Owner's request with a fresh code, to a sending account recorded on a confirmed Payment; Finance sends the transfer in the bank portal and records the bank reference. Notes are non-transferable, applicable for 12 months with reminders at 60 and 14 days, and refundable on request after expiry (counsel to confirm).

FR-77: [P1] The platform issues one numbered, sequential tax invoice per Paid Order (buyer Trade Name, TIN if given, line VAT, delivery VAT, serial numbers where recorded) and a credit-note document per Credit Note referencing the invoice it adjusts, downloadable as PDFs by Owners and Managers. Fields are to be settled with the accountant; the design is e-invoicing ready.

**Fulfilment and tracking**

FR-44: [P1] Operations approves a Paid Order after a shelf check per line; Approve stays off until every in-stock line is found or credited. Approval moves the Order to Approved and its Shipments to Processing. Crediting a line cancels it, releases its reservation, requests a Credit Note from Finance and notifies the Integrator. An Order whose every line is credited becomes Cancelled with a full Credit Note.

FR-45: [P1] Each Shipment moves through Processing, then Ready for Pickup and Signed For (Pick-up) or In-transit and Delivered (Delivery), with exceptions Delivery Failed, Returned to Store, Lost and Uncollected. Only listed transitions are allowed; reversals are Admin-only with a reason. In-transit requires courier name and tracking reference; Delivered requires a proof reference or the receiver's one-time code; Signed For records the collector's name and ID type and last four digits. Order statuses are Awaiting Payment, Paid, Approved, Completed, Expired and Cancelled. Lost or Delivery Failed blocks Completed and opens a non-delivery Case. An uncollected Pick-up is reminded at 7 days, marked Uncollected at 14, and cancelled and restocked after 30 with a credit-only Credit Note less the restocking fee. Operations updates statuses by hand.

FR-65: [P1] For serialised Products, Operations records serial numbers at dispatch or Ready for Pickup by barcode scan or pasted list. A serial is unique across active dispatches, the count equals the shipped quantity, and a model-specific format is checked where defined. A correction needs a reason and a second person. The Integrator sees the serials on the Order.

FR-46: [P1] Integrators see an Order's status and each Shipment's status with timestamps. A Technician sees status with no amounts.

FR-47: [P1] Operations defines Delivery Zones as lists of local government areas and updates each Zone Rate. Each active zone has a flat Zone Rate per Shipment and a delivery estimate. A zone with no rate is "coming soon" and Delivery to it is blocked. A rate change affects only later Orders, and any address resolves to exactly one zone.

FR-56: [P1] Operations and Admin create, edit and deactivate Stores (name, address, opening hours). All Stores draw on one central stock pool. Launch has one Store, the Main Store, from which Delivery Shipments dispatch unless another is specified. A Store cannot be deactivated while open Shipments reference it unless they are reassigned with notice, and the Main Store needs a replacement first.

**Installation Documentation**

FR-48: [P2] Operations attaches, replaces and removes Documentation on a Product (PDF manual, video, written step-by-step guide), and sees a list of Products with none. Guides are written in a console editor with steps (one optional photo and one optional warning each), a version note and a live phone preview. Documentation is versioned; replacing or removing it (removal needs a reason) marks saved offline copies Superseded or Withdrawn at their next sync.

FR-49: [P2] Anyone who can browse the Catalogue reads Documentation on the Product page, and Users reach it from a Project or Completed Order. A Product with none shows "Documentation coming soon."

FR-50: [P2] A User chooses "Make this Project available offline", which shows the size and downloads the PDFs and written guides of every Product in the Project, with videos on request. Saved copies show their version date and the app warns when device storage may evict them.

**Notifications**

FR-51: [P2] The platform sends email and WhatsApp notifications through a matrix of event, recipient role, channel and template for 12 launch events: Payment Unconfirmed; Order repriced; Price Lock deadline (hour 20); Order expiry warning (hour 40); Order Paid and Approved; Shipment dispatched or Ready for Pickup; Return Request awaiting approval; Issue Code issued and expiring (hour 36); Credit Note issued and expiring (60 and 14 days); Back-order running late; Level 3 and NIN decisions; counterfeit recall notice. Amounts go only to Owners and Managers. Money and deadline events send at once, others wait until 9 am. WhatsApp falls back to email after 15 minutes undelivered and respects opt-in. Removed Users receive nothing. Each message names the Order, Proposal or Case. There is no in-app inbox and no push.

**Staff console, queues and audit**

FR-52: [P1] Staff have Finance, Operations or Admin roles with capabilities per the PRD matrix; Support duties are a separate permission group held by Operations. Segregation of duties: no Staff member confirms a Payment or issues or pays a Credit Note for a Business Account they created, approved or vouched for; an investigator never decides a return. A second approver is needed for refunds and Credit Notes above an Admin-set amount (placeholder NGN 100,000), Platform Rate changes above 3%, VAT rate changes, Back-order Discounts above 10%, Active Supplier switches, bulk price changes and underpayment-tolerance changes. Everything needing a second person sits on one Approvals page; nobody approves their own request. Bank-detail changes need a Finance second approver, take effect after 24 hours, are emailed to Admin and Finance, and show Integrators a banner for 7 days. Acting as another role is flagged for the founder's weekly review.

FR-53: [P1] An append-only, hash-chained audit trail covers every state change to money, stock, access, identity data, pricing parameters, Cases and Return Requests, and every read of a NIN or bank detail (with a reason code). Entries hold identifiers and field differences, never a NIN or name. No application role can edit or delete entries. The founder's weekly review lists override and acting-as events and cannot be signed off until each is reviewed. An alert fires on unusual patterns.

FR-76: [P1] Every staff role has a named deputy; an out-of-office switch moves the queue to the deputy within a minute with an "acting for" flag. Deactivating staff revokes sessions and reassigns queue items; the last active Admin cannot be deactivated. Admin maintains the Business Hours holiday calendar, which pauses target clocks and feeds the app's "open now" lines.

FR-78: [P1] The console has queues for Receipts, Level 3 requests, NIN reviews, Order approvals, Credit Note requests and returns, each showing count, age of the oldest item and owner (role holder and deputy). Staff are alerted on arrival; items escalate to Admin at 75% of target; on-time rate is reported. Targets: Receipts 2 Business Hours; Level 3 and NIN 1 Business Day; Order approval 4 Business Hours; Credit Note requests 1 Business Day; returns 2 Business Days from arrival. An open item is soft-locked, and takeover needs a reason.

**Installable, low-bandwidth app**

FR-54: [P1] A User installs the platform to their device from the browser, and the installed app opens without a browser address bar.

FR-55: [P1] The platform stays usable on slow or patchy mobile data. On slow 3G (400 kbps, 400 ms) on a 2 GB Android phone the Catalogue list loads within about 5 seconds. A Receipt upload that loses its connection resumes or retries without re-entering data.

**Returns and warranty**

FR-79: [P2] Every contact with Support opens a Case, from the app ("Report a problem") or opened by Support for a call or WhatsApp message, recording channel, caller, serial or Order Line, outcome and time. Support verifies the caller by an in-app one-time code ("Give Support a code") or a callback before disclosing any Order or serial detail. The first Case time starts the reporting window.

FR-67: [P2] Support looks up a serial number and sees the Shipment, Order Line, Business Account and dispatch date. A serial with no record can get a serial exception (Order Line plus label and box photos) that Operations and Admin must both approve, never the person who opened it.

FR-68: [P3] If remote help fails, Support generates an Issue Code for the Case, tied to the Business Account and listing serials (or an Order Line and quantity). It is valid 48 hours, in which the device must reach a Store or be handed to a courier with a tracking reference; the clock pauses up to 7 days while a Technician-raised request awaits approval. Past the window the Return Request lapses and Support generates a new code without resetting the reporting window. A code works once.

FR-58: [P3] A Technician, Manager or Owner raises a Return Request for a device reason with the Issue Code, reason, quantity, photos and serial. A Technician's request waits for a Manager or Owner to match it to the Order Line and approve or decline with a reason; it is invisible to Support until approved. Change of mind needs a return authorisation from Support. An Owner or Manager opens a non-delivery claim, without an Issue Code, when a Shipment is Lost or overdue. Quantity cannot exceed delivered, and one open request per serial.

FR-59: [P3] Reporting windows, from the courier-confirmed delivery or collection date: 5 days for transit damage or wrong item, 30 days (or the Supplier's shorter window) for dead on arrival, 7 days for change of mind, the Warranty Period for warranty, and Warranty Period plus 90 days for suspected counterfeit. The window is met by the first logged Case. Windows are Admin-editable and snapshotted on the Case. The app never blocks a report.

FR-60: [P3] On arrival Support photographs the unit, label and packaging, records the physical serial and investigates. A serial mismatch rejects the return and flags the account. Admin or Operations decide the remedy; the investigator never decides. A unit that tests fine goes back at the Integrator's cost. Support may reclassify the reason with a note. States: Raised, Approved by the Integrator, Item Received, Under Investigation, Decided, Awaiting Finance, Awaiting RMA, Resolved, Rejected, Lapsed and Held.

FR-61: [P3] Remedies by reason: transit damage or wrong item, replacement then Credit Note; dead on arrival, replacement, manufacturer repair, then Credit Note; warranty, manufacturer RMA only; confirmed counterfeit, cash refund; change of mind, Credit Note less a 15% restocking fee on the pre-VAT price with VAT credited in full, for sealed stocked items; non-delivery, re-dispatch or cash refund. Replacements are zero-value Shipments under the original Order with serials recorded, inheriting the remaining Warranty Period.

FR-62: [P3] The Integrator brings the device to a Store or hands it to a courier with a tracking reference and a photo of the courier's receipt. Where the fault is the platform's, actual courier cost is reimbursed by Credit Note, capped at the Zone Rate, one claim per return. Each Return Request records who pays freight and the amount reimbursed.

FR-63: [P3] Not returnable for change of mind: Back-order Items, special orders, software and licences, opened or installed goods, end-of-life, refurbished or demonstration items, and items missing serials or original packaging. Warranty excludes misuse, power-surge damage and installation labour. The Product's returnability flags drive the refusal, which shows its reason.

FR-64: [P3] Each delivered Order Line shows "Warranty until", computed from the dispatch date (Ready for Pickup for Pick-up) or the manufacturer's earlier date. A warranty claim is a Return Request resolved through the manufacturer's RMA, with no labour cover. A Product with no Warranty Period has no warranty.

FR-66: [P3] Support flags a suspected counterfeit with evidence. Operations or Admin mark a Product or batch Suspected (a whole-Product hold needs a second approver), which stops sale, quarantines stock, flags affected Order Lines and Proposals, blocks payment confirmation and shipment of affected lines, and notifies affected Business Accounts. It moves to Confirmed or Cleared within 14 days. Quarantined stock is never resold. A cash refund is available only once Confirmed.

**Reporting and exports**

FR-80: [P2] The founder has a dashboard with a tile per success measure (SM-1 to SM-9, SM-C1 to SM-C5) showing definition and data source. Finance exports CSVs of Orders, Payments, Credit Notes with ageing, monthly VAT output and bank reconciliation. SM-C1 is computable for any Completed Order from its Order Lines alone, and the VAT export reconciles to the tax invoices issued.

### NonFunctional Requirements

NFR-1: Scale. Launch at 10 Integrators, 500+ Products and about 15 Orders a month; designed for 500 Integrators, 5,000 Products and 500 Orders a month.

NFR-2: Operational SLA. Staff queue targets as in FR-78; the headline commitment is a Receipt confirmed within two Business Hours of arriving inside Business Hours.

NFR-3: Security and privacy. NIN is stored as the provider's verification reference, a keyed hash and the last four digits; the full NIN is kept only during manual review and deleted 30 days after the decision. Staff see it masked with a logged, reason-coded reveal. NIN is excluded from logs, analytics, error reports and exports, with keys held outside the database and covering backups. The platform handles data-subject access and deletion requests and has a breach runbook with the 72-hour notification step.

NFR-4: Money accuracy. Decimal arithmetic and the FR-19 rounding rule everywhere. Every Proposal, Order and Order Line stores the Platform Rate, Tier Discount, Back-order Discount, VAT rate, unit USD cost, Platform Markup and Active Supplier used; an Order Line also stores the discount type and percentage and its promised Lead Time.

NFR-5: Availability and recovery. 99.5% monthly availability excluding announced maintenance; recovery point 1 hour, recovery time 4 hours; a restore drill each quarter.

NFR-6: Devices and offline. Android 9+ on Chrome and iOS 16.4+ for the installed app. Install guidance on iOS, a storage-eviction warning, and manual sync where background sync is unavailable.

NFR-7: Performance budgets. The Catalogue list loads within about 5 seconds on the FR-55 test profile. Project open, Proposal generation and Receipt upload each have a budget (not yet set; see open items).

NFR-8: Retention. Retention periods for Receipts, client details on Proposals, delivery addresses, Terms acceptances and the audit trail are set with counsel and the accountant (financial records expected at about 6 years).

NFR-9: Scheduled jobs and timers. The monthly tier recalculation, the 24-, 40- and 48-hour Order timers, Proposal and Credit Note expiry and the Business Hours clock store UTC, display WAT, run idempotently, and alert the founder on a miss.

NFR-10: Consistency. Every money-affecting transition (Payment confirmation, stock reservation, Order creation, cancellation, approval, expiry, Credit Note application, Vouch Code redemption) is atomic and idempotent; when two actions race the first valid one wins and the other receives the current state.

### Additional Requirements

From `ARCHITECTURE-SPINE.md` (decisions AD-1 to AD-26 are binding on every epic and story), the PRD addendum and the spec kernel.

**Project setup (affects Epic 1, Story 1)**

- **No starter template is named.** The project is greenfield and is scaffolded from the stack table: PHP 8.4, Laravel 13.x, Filament 5.x, Sanctum 4.x, PostgreSQL 17, React 19.x, TypeScript 6.0.x, Vite 8.x, vite-plugin-pwa 2.x (Workbox), Dexie 4.x, TanStack Query 5.x, TanStack Router 1.x and Tailwind CSS 4.x.
- The first story creates the structural seed: `app/Modules/{Shared, Identity, Catalogue, Pricing, Projects, Orders, CreditNotes, Fulfilment, Documentation, Notifications, Returns, Staff, Reporting}`, each with `Actions/`, `Queries/`, `Locks/`, `Models/`, `Events/`, `Http/`, `Filament/`, `Contracts/`, `StatusTables/`, `Database/Migrations/`, `Database/Views/` and `Tests/`; `resources/app/` for the React PWA; `routes/api.php` under `/api/v1`; `tests/Architecture/` and `tests/Flows/`.
- Architecture tests that fail the build on a model write outside `Actions/` (AD-1), a cross-module model import or table query (AD-2), and a migration breaking the foreign-key rule.
- CI pipeline and deploy gates are defined with the first epic; Laravel Cloud deploys from the repository.
- Three environments: local, staging (full stack, fake providers, synthetic data only) and production.

**Shared kernel (needed before domain modules)**

- `Shared` provides the Money type, Clock (UTC store, WAT display), Business Hours arithmetic, UUIDv7, the Action base, lock registry, transition-table helper, idempotency store, outbox, field-visibility registry and the `PdfRenderer` contract.
- AD-4 Action contract: lock every aggregate root up front in the fixed global order; check a declared transition table; refuse with a typed `TransitionRefused` (HTTP `409` with the current resource); require an `Idempotency-Key` on client-retryable operations (same key with a different payload returns `422`); audit in the transaction; emit events through the outbox with per-aggregate sequence numbers and idempotent listeners.
- AD-5 timers: every business deadline is a UTC column on its owning entity, swept every minute on one server; timer Actions re-read the deadline under the root lock; reminders are `*_due_at` and `*_sent_at` pairs; pausable clocks store `paused_at` and `remaining_seconds`; a holiday-calendar change recomputes open Business-Hours deadlines; no per-entity delayed jobs; a missed sweep heartbeat alerts the founder.
- AD-6 money: PostgreSQL `numeric` (NGN 2 dp, USD 4 dp, rate `numeric(12,4)`, percentages `numeric(7,4)`); store inputs and final rounded naira only; JSON money is `{"amount": "<decimal string>", "currency": ...}`; the PWA has one TypeScript money module on `BigInt` kobo for display sums only.
- AD-11: every primary key is a UUIDv7 (the PWA uses a UUIDv7 library, never `crypto.randomUUID()`); human-facing references are separate columns.
- AD-13: one field-visibility registry (money, cost, USD, staff identity, staff note) consulted by API resources, sync payloads, notification renderers, PDF builders, exports and sort and filter parameters; staff text is always `reason_public` and `note_internal`; one test suite runs every Technician, Level 1 and Level 2 surface against it.

**Module ownership rules**

- AD-1 and AD-2: Actions are the only writers; modules use each other only through public Actions, Queries and Lock actions; Queries return DTOs; Reporting reads only versioned reporting views published by owners.
- AD-3: each module registers its own Filament resources; every console action calls an Action.
- AD-7: `Pricing` alone does money arithmetic and exposes `QuoteLines` (price snapshot plus Order Quote with fingerprint), `RepriceSnapshot`, `PriceDelivery`, `SplitVat`, `ApplyIntegratorMargin`, `RestockingFee` and the credit-amount formulas. The addendum's test vectors live in Pricing's test suite.
- AD-8: only `Catalogue` changes stock, reservations, the ledger, sellability flags, batches and `serial_units`; blocking is pull-based through `AssertSellable` under the stock-row lock; Fulfilment owns dispatch serials with a partial unique constraint.
- AD-9: Credit Notes are an append-only ledger (`issue`, `hold`, `release`, `apply`, `restore`, `refund`, `expire`); tax invoices are structured records with gapless numbers from a locked counter row; one invoice per Order by unique constraint.
- AD-10: statement lines carry a natural-key unique constraint (account, value date, amount, bank reference); allocations are append-only with a database trigger capping the sum at the line amount; a split across Orders needs a second approver.
- AD-22: `Projects` owns a monotonic Project `revision`; a Proposal stores its revision; Order creation sends the `quote_id` and gets `409` with the new quote on any fingerprint mismatch; `ConsumeProposalRate` runs under the Project lock.
- AD-23: `Staff` owns `PendingApproval` with a database check that approver differs from requester, plus `StaffRelationshipQuery` for segregation of duties.
- AD-24: `Staff` owns the single `work_items` table (queue, subject, owner, `due_at`, `escalate_at`, `closed_at`, `acting_for`).
- AD-25: only `Orders` writes Order and Order Line status; Shipments have `kind` normal or replacement; `NetDeliveredGoodsQuery` is the only source of spend.
- AD-26: every module holding personal data implements `ErasePersonalData` and `ExportPersonalData`, orchestrated by `Identity`.

**Offline, client and files**

- AD-12 sync protocol: push is an idempotent batch of operations merged per Room and per Project Item, last writer wins per field by server receipt time, with tombstones kept 30 days and held conflicts for removed or re-roled Users; pull uses `change_seq` and `xid8` with a snapshot-xmin guard; scope excludes Carts, Orders, Payments, Receipts and Credit Notes; the price sheet covers only Products in the Business Account's Projects and recently viewed. Server and PWA implement the merge from one shared specification with test vectors.
- AD-12 local store: one IndexedDB database per User and role, purged at the next connection after a role, Access Level, Hold or membership change; a 14-day lock using stored server time; `navigator.storage.persist()` and `estimate()` on launch and before saving; sync on demand and on focus or reconnect.
- AD-19: the Integrator app is one React and TypeScript SPA served by Laravel; offline screens use the local store, online-only screens use the JSON API through TanStack Query; no Inertia, Livewire or Blade pages beyond the shell.
- AD-17: server search on PostgreSQL `pg_trgm`; the PWA builds its own offline index; no external search engine.
- AD-14 files: every upload starts with an upload-intent `POST` that stamps `upload_started_at`, then presigned multipart resumable upload, then completion; files are quarantined until scanned for malware, EXIF-stripped, type-sniffed and HEIC-converted. Evidence is private, immutable and served by short-lived signed URLs. Catalogue media uses content-hashed keys with 320, 640 and 1280 px WebP derivatives. Documents render on the queue from a snapshot row written in the issuing transaction.

**Security, identity and audit**

- AD-16: each full NIN is encrypted with its own data key wrapped by a dedicated master key (never `APP_KEY`); deleting the wrapped key crypto-shreds it in backups; a separate HMAC key for the uniqueness hash; keys escrowed in a second secret store and tested in the restore drill.
- AD-18: the PWA uses Sanctum SPA cookie mode; Staff use a separate guard with mandatory two-factor authentication; database session driver so sessions can be revoked; step-up is an Identity-owned `StepUpGrant` consumed inside the gated Action's transaction.
- AD-21: audit rows are inserted in the Action's transaction; a single-writer sealing job computes the hash chain; a database trigger rejects `UPDATE` and `DELETE`; audit diffs record personal-data fields as "changed" or a keyed hash.

**Infrastructure and operations**

- AD-20: production on Laravel Cloud (Growth plan, EU West London) with Serverless Postgres 17, managed queue, scheduler and R2 object storage; only standard PostgreSQL, S3 API and Laravel features; database driver for cache, locks and rate limits.
- An encrypted logical database dump and an object-storage copy go to a separate provider every hour; the quarterly restore drill restores from that copy and recreates the audit trigger and database roles.
- The founder receives alerts for the sweep heartbeat, outbox backlog, failed jobs, backup and restore-drill failures, and spend.
- AD-15: every external provider (NIN check, SMS one-time codes, bot check, email, WhatsApp, bank-statement CSV import, file scanning) sits behind a module-owned interface with a production adapter and a fake.
- Conventions: `/api/v1`, `snake_case` JSON, `{message, errors}` plus a machine `code`, every response carries `server_time`, ISO 8601 UTC dates, staff-tunable settings are database rows changed through Actions, structured JSON logs carrying the request id and never NIN, bank details or file contents.
- Tests: every NFR-10 transition has a two-racer concurrency test and an idempotent-replay test.

**Deferred choices to make inside an epic**

- NIN, SMS, bot-check, WhatsApp, email and malware-scan providers (before the Identity, Notifications and Payments epics).
- PDF engine, passkey (WebAuthn) library, offline search library, error tracking and uptime monitoring.

**From the addendum and the spec kernel**

- The addendum's status model is the source for every transition table; entity and status names are used word for word.
- The edge-case register (rows 1 to 82) lists guards each story must cover for its FR.
- Build order is Priority 1, then 2, then 3. Until the Priority 3 returns workflow ships, returns run manually by phone and WhatsApp, with Cases, serial lookup and Finance-issued Credit Notes available from Priority 2.
- Return, warranty, cancellation and Credit Note terms ship in counsel-vetted Terms without "no refunds" wording.
- Model Stores so they can be added without a release, and keep stock quantities behind one interface.

**Open items that block or shape specific epics**

- Builder: whether Laravel Cloud Serverless Postgres allows a least-privilege application role (AD-21); spike before the Staff epic.
- Accountant: tax-invoice and credit-note fields and numbering, serials on the invoice or a delivery note, the reversal and re-invoice flow, VAT on delivery and Credit Notes (blocks the CreditNotes documents; FR-77 layouts are download rows only until then).
- Counsel: NDPA transfer basis for London hosting and R2; the nine wording items in `EXPERIENCE.md`; Credit Note expiry and refund terms.
- Founder: PRD §8.3 questions 1 to 18, notably sign-in limits (FR-1), re-requesting after a Level 3 rejection (FR-6, FR-7), queues without targets, fractional units (FR-26), per-phone Documentation versions (FR-50), the receiver's one-time code (FR-45), alert baselines (FR-53), staff sign-in method (FR-71), dashboard inputs (FR-80), zone data (FR-47) and Product Range management.

**Discrepancies between the inputs, raised rather than resolved**

1. FR-37 offers "a manual zone pick or a delivery price from Operations" for an unresolved zone; `EXPERIENCE.md` offers "Collect from the Main Store or Ask us for a delivery price" and draws no manual zone pick. **Resolved 13 October 2026:** the UX version is built (Story 7.7), because a manual pick would let a business choose a cheaper zone than its address earns; FR-37 needs that correction in the PRD.
2. FR-4 lists five Get approved steps (contact, NIN, Trade Name, vouch, Terms); `EXPERIENCE.md` draws a four-step checklist (verify your contact, confirm who you are, enter a vouch code, we review).
3. FR-45 says an uncollected Pick-up "is cancelled and restocked after 30 days"; `EXPERIENCE.md` says it "may be cancelled". **Built as the PRD says** (Story 9.9).
4. FR-48 and the addendum call a replaced saved manual "Superseded"; `EXPERIENCE.md` shows the business the word "Replaced". The word list does not record this mapping. **Resolved 14 October 2026:** "Superseded" is the stored state and "Replaced" is the word a business reads (Story 12.10); the word list in `EXPERIENCE.md` should gain this row.
5. NFR-7 says budgets for Project open, Proposal generation and Receipt upload are "set during UX design"; `EXPERIENCE.md` sets only the Catalogue list budget.
6. FR-78 names six queues; `EXPERIENCE.md` also draws Unmatched credits, Support cases, Shipments, Back-orders and Delivery price requests as queues (PRD §8.3 question 8 covers the missing targets).

### UX Design Requirements

From `DESIGN.md` (look) and `EXPERIENCE.md` (behaviour). The spines win over the mockups. Screen-level essentials that restate an FR are not repeated here; these are the design-system, cross-cutting and screen-specific work items the FRs do not carry.

**Design tokens and foundations**

UX-DR1: Implement the Ledger colour tokens as the single source for the PWA and the Filament console theme: surface-paper `#FAF6EC`, surface-sheet `#FFFFFF`, surface-aged `#F0E9D6`, ink `#1A1812`, ink-secondary `#4A4434`, ledger-green `#153D2E`, brass `#6B4E0C`, caution `#6E4400`, caution-tint `#F6EACB`, good `#1B5E3A`, error `#8A1C14`, error-tint `#F6E4DE`, control-edge `#7A7258`, hairline `#D9CFB5`, on-green `#FFFFFF`, on-green-soft `#C9DCCF`, scrim `#1A1812` at 62%, focus-ring and focus-ring-on-green. Light mode only.

UX-DR2: Implement the typography tokens on system font stacks only (no bundled font): serif titles (title-console 26px, title-screen 21px, heading 19px, heading-small 16px), sans body 15px, small 13px, label 12px (the floor), eyebrow 12px capitals with 0.14em spacing in brass, button 15px (console 14px), input 16px, amount-total 24px and amount-line 15px with tabular figures, table-console 13px, and monospace code 13px.

UX-DR3: Implement the spacing and shape tokens: scale 4, 8, 12, 16, 24, 32, 48px; screen margin 16px; targets 44px (52px phone main button, 40px and 34px console); phone row 56px; checkbox row 48px; field 48px; tab bar 68px; console rail 224px; content max 1100px; radius 2px on controls, 14px on sheet tops, full only for radios and progress dots. No shadows, gradients, blurs or pill shapes; depth by tone and rule (hairline, 2px ink rule, 3px double rule around a grand total).

UX-DR4: Apply the colour-meaning rules: in-progress states are neutral outlined labels; brass and caution only for "needs a person or a clock"; red only for error, refusal and the one irreversible button; green "good" only for a passed check and in stock; ledger green never means good. One filled button per screen.

UX-DR5: Icons are 2px line icons at 18 to 20px, always paired with words; the only icon-only controls are the cart and Back, both named. The wordmark is "integrator" in bold italic serif. Photos only on a product page and in a guide step, never in a list row.

**Components** (visual spec in `DESIGN.md`, behaviour in `EXPERIENCE.md`, same names)

UX-DR6: Button: primary, secondary, danger, danger-outline and disabled variants; the disabled variant stays visible, dashed, with its reason beside it; shows "Sending…" while working and cannot be pressed twice; a button that opens a dialog ends in an ellipsis.

UX-DR7: Status label (neutral, needs, failed, good) and Banner (information, needs, failure) with a 4px left rule, the action inside the banner, one banner per screen except account-wide ones (Hold, stale rate, prices being updated) which stack at the very top.

UX-DR8: Form field with an always-visible label above, "Optional" and "needed" in words, error as a 2px outline plus a sentence under the field, correct keyboard type, paste and autofill working, values kept after any failure; a masked NIN field with a Show control; a money field that cleans pasted text and reads the amount back.

UX-DR9: Code entry: six 52px boxes over one real input so paste and auto-fill work, with a resend countdown in words that becomes a button. Used for sign-in, step-up and the WhatsApp number check.

UX-DR10: Choice (radio card, checkbox row, switch with the word On or Off; nothing preselected for a legal or money commitment) and Quantity stepper (46px high, 44px minus and plus, typable number, unit after the number, zero or below refused with a sentence, works offline).

UX-DR11: Row and table: phone rows of at least 56px with the whole row a target and amounts right-aligned; console tables with a fixed header, sortable columns that say so, a selected row outlined in green with a 4px bar, a late row tinted red with the reason in words, and J, K, Enter and / keyboard movement.

UX-DR12: Sheet and dialog: phone bottom sheet and console centred dialog over the scrim, focus trapped and returned, Esc or Cancel closes, title as a question, body listing what will happen, red only for the irreversible; a sheet never holds a long form.

UX-DR13: Tabs and navigation: the five-tab phone bar (two before approval), a segmented control, the green top bar with wordmark and cart, and the grouped console rail with counts that turn brass when late.

UX-DR14: Timeline and steps (vertical timeline with done and now marked and dated; step pips with "Step 3 of 6" in words; checklist verdicts Clear or Needs action), Clock (time left plus exact end time, updates each minute, announced only at start, 1 hour, 15 minutes and the end, never rests at 0 h 0 min) and Meter (12px, always with its figure in words, no animation).

UX-DR15: Amount (naira sign, commas, two decimals, tabular figures, true minus sign, "VAT included" in eyebrow style, double rules around a grand total), Card (queue card and tile with a double top rule; tinted when it needs action) and Check list (sentence, detail, verdict in words).

UX-DR16: Copy and share (44px Copy button saying "Copied" for two seconds; monospace codes; share sheet with Copy fallback) and Upload ("Take a photo" and "Choose a file", camera permission asked only on press, on-device resize to about 2 MB with location stripped, file row with thumbnail, name, size, percentage and Remove, resume after signal loss, bad file refused in a sentence before sending).

UX-DR17: Preview panel (what a change will do, beside the Save button), Two-person approval (needs banner naming who asked and who agreed; Approve disabled with a reason for the requester; decline needs a reason) and Guide step (one step to a screen, large type, text-size control, step strip, big Back and Next, one described photo, optional warning box).

**State, interaction and accessibility**

UX-DR18: Implement the sixteen generic state patterns consistently: loading (nothing for the first second, then words, a bar for anything long, never a spinner alone), empty, fixable error, error needing us, no signal, stale with an as-of time, waiting on us, needs you, blocked by a rule, not for your role, time ran out, on hold or prices being updated, under review, done, locked by someone else, and service down.

UX-DR19: Cover the per-surface state matrix in `EXPERIENCE.md` State Patterns for every surface built (sign-up and code, Get approved, Home, Catalogue and product, Project, Proposal, Cart, Create order, Payment and receipt, Order, Returns, Credit notes, Account, Saved on this phone, console queue, console review, Platform Rate, bank statements, Support desk, bulk upload, guide editor, Approvals, dashboard); a state that does not apply is skipped on purpose.

UX-DR20: Interaction primitives: no swipe, long press, pinch, drag, shake or tilt is ever required; reordering uses arrows; pull to refresh also has a button; actions fire on release; motion at most 150ms and reduce-motion obeyed; feedback within one second; "Are you sure?" only before irreversible actions, money moving, or sending to someone else; 30-day undo for deleted rooms and earlier versions; forms keep values after failure and long console forms save a draft.

UX-DR21: Accessibility floor: WCAG 2.2 AA on every app and console screen and on the PDFs, emails and WhatsApp messages. A 3px focus ring with a 2px gap that is never hidden by a sticky bar, focus order following reading order, a skip link, full keyboard operation, landmarks and ordered headings, named icon buttons, polite and assertive announcements (including going offline and online), 200% zoom with no loss, reflow to 320px, system larger-text honoured, forced-colours support through real borders.

UX-DR22: Accessibility proof: an automated accessibility check on every screen and state on every change that stops the release on a new failure; every story's acceptance criteria say what a keyboard user, a screen-reader user and someone at 200% zoom can do; a pre-release manual pass (keyboard, TalkBack, VoiceOver, NVDA, 200% and 400%, reduced motion, forced colours); a published accessibility statement.

UX-DR23: Documents produced are tagged PDFs (real text, title, language, headings, true tables, a described logo) that print cleanly; WhatsApp and email are plain text first with links that say where they go.

**Responsive and platform**

UX-DR24: Three layouts: phone up to 599px (one column, bottom tab bar, bottom sheets, sticky action bar with the total); tablet 600 to 1023px (phone layout centred up to 720px, list and detail side by side from 900px); laptop from 1024px (tab bar becomes a left rail, cart top right, content capped at 1100px, list and detail for Orders, Projects and Credit notes, a persistent Project summary panel, sheets become centred dialogs, card lists with more than four facts become tables). Reading order never changes; nothing needs hover; both orientations work.

UX-DR25: Console layout built for 1280px with a top bar (brand, "Staff console", clock, open-until time, role and person) and the 224px rail; below 1024px, including zoom to 400%, the rail becomes a menu button, panes stack and wide tables scroll in their own box. A phone opening the console gets a notice that it is for a desktop.

UX-DR26: Platform behaviour: install offered after the second visit or after the first "Save for offline" and always under Account, with iPhone steps in words (on iPhone, Save for offline asks to install first); a new version is fetched quietly with an "Update when you finish" banner; pictures load small first, lists load 20 at a time, Data Saver holds pictures until tapped and never auto-loads video; "Slow connection. Still trying." after 8 seconds.

**Navigation and information architecture**

UX-DR27: App shell: five tabs in fixed order (Home, Catalogue, Projects, Orders, Account), two before approval (Catalogue, Get approved); the cart is a top-bar icon with a count for Owners and Managers only; "Call or WhatsApp us" is the last block of every screen with an open-now line, plus a Help sheet with "Give Support a code"; each thing lives in one place and others link to it; no in-app inbox.

UX-DR28: Home: "Needs your attention" first (payment with its price-hold countdown, Return Request awaiting approval, Credit Note about to expire, a Manager's vouch-code request, a counterfeit recall notice until "I have seen this"), then recent projects, then "New project" and "Buy parts without a project". A Technician's Home has no cart and no money.

UX-DR29: Console shell: a role-aware rail grouped as Queues, Work, Money, Catalogue, People, Rules and Watch, with Today first; the common queue pattern (oldest first, count, oldest age on a meter against target, over-target count, on-time rate, list with a preview panel and a full page, soft lock with reasoned takeover); the deputy note and out-of-office switch; "You are acting as Finance" before using another role's power; a "Stay signed in" warning 5 minutes before the 12-hour session ends; a History panel on each record.

UX-DR30: Text pages on one template: Terms of use, Privacy Notice, accessibility statement, Install the app, signed out, service down and not found.

**Voice, formats and words**

UX-DR31: Apply the six voice rules and the tone-by-moment table to all copy in the app, console, WhatsApp, email and PDFs: plain words; say what happened, why and what to do; buttons say what they do (never Submit, OK, Confirm, Proceed); calm about clocks and money; never blame or joke; one name for one thing. No exclamation marks, no emoji, sentence case, British spelling, about Grade 8 reading level. PRD literals are never reworded.

UX-DR32: Implement shared formatters for every format in the Formats table: money to a business and in the console, reductions, percent, dates and ranges, 12-hour WAT times, time left, phone numbers grouped 4-3-4 with masking, references (INT-, CN-, C-, RR-, IC-), read-aloud codes in groups of four without look-alike letters, serials, people's names by context and measures. Staff-only references (BA-, P-, L-) never reach a business.

UX-DR33: Apply the word list everywhere a business reads: "approved" and never Level 1, 2 or 3; "price hold" for Price Lock; "credit note" and never wallet or balance; "our team", "our finance team" and "Support" and never staff role names; "exchange rate" for Platform Rate; and the rest of the table.

UX-DR34: Carry placeholders, clearly marked, for the nine wording items awaiting counsel or the accountant (Terms key points, Credit Note expiry and refund terms, NIN privacy lines, VAT wording on documents, the Proposal validity sentence, recall and counterfeit notices, hold, revoke and blocklist messages, the account-closure retention line, and the bank-details-changed banner).

UX-DR35: Write the twelve launch messages as a table to the voice rules: the Order, Proposal or Case named first, then what happened, then what to do; one link; under 300 characters on WhatsApp; signed "integrator"; no amount in a Technician's message.

**Product rules on screen**

UX-DR36: Money rules: "VAT included" beside the first price on a screen and under every total; three numbers where money is owed in the order Amount due, Confirmed so far, Still to pay; "paid" only for a confirmed figure; a money action names every amount before its last button; a Project whose total moved after a sync says so once with the old and new totals; a role that may not see money gets a layout drawn without the money area (no dash, blank or padlock).

UX-DR37: Time rules: calendar clocks say "until" and "left", Business-time promises say "by" with the exact time computed by the server, and one sentence never mixes the two; every countdown uses server time and shows WAT (adding "Nigeria time" on a phone in another zone); a clock ends in a result card; a typed transfer time is read back and cannot be in the future or before the Order; a note when a slow upload is close to a deadline.

UX-DR38: Visibility treatments: "not shown" (layout closes up), "shown, and who can" (names the role and offers a request, such as "Orders are for Owners and Managers. Ask Tunde."), and "shown, but not now" (dashed button with its reason); never "Access denied". In the console every free-text box says who will read it, and nothing is greyed out.

UX-DR39: Trust rules on screen: step-up asked at the moment of the action in a bottom sheet, sent to a partly hidden contact, finishing the action in the same step; a sent receipt cannot be edited or deleted; money records have no Edit or Delete; lost races show the current state with who and when ("Already approved by Bola A. at 2:14 pm"), never an error; segregation of duties blocks are explained in words; a line explaining why client and receiver details are kept.

UX-DR40: Offline rules on screen: a "changes waiting" count and an always-available "Check now"; the 14-day check-in sequence (brass line on day 11, banner on day 13, lock on day 14); conflicts in "Needs a look" with Keep latest and Use mine (names for Owners and Managers only); the no-signal sentence stating what works and how fresh it is; "We have not heard back. Your order may have been made…" after a dropped money action; each online-only action says why it needs a connection.

**Screen-specific design work not carried by an FR**

UX-DR41: Get approved as a checklist tab with no Level wording, the Approved welcome with three next steps and no tour, the invitation acceptance states (expired, cancelled, contact already on another business, first-time welcome), and the separate per-number WhatsApp question screen.

UX-DR42: Catalogue rows without photos (name, brand, model, SKU, stock status, price); the product page's context-dependent add buttons ("Add to <room>" from a room; "Add to cart" then "Add to a project" from the Catalogue); the full-screen product picker with a "Done · n items in <room>" bar; the three Documentation slots with their states (saved date and version, Replaced, Withdrawn with "Open anyway", "Needs signal").

UX-DR43: The Proposal screen's private "Only you see this" panel and the single Proposal card states (Valid until, Out of date, Expired, Replace confirmation "The old PDF cannot be recovered"); the Proposal PDF layout on the integrator's letterhead.

UX-DR44: Create order as Review, Delivery, Confirm with a step indicator and sticky bar (two steps from a Cart); the "Compared with your Proposal" block; the changed-price sheet with "Confirm new price and create order" or "Back to review"; the "Checks before an order" blocks, each saying what is wrong, why and what to do.

UX-DR45: The Orders list segments (Open, Done, Problems); the one-order page ordered as Deliveries, Payments, Documents and guides, then actions; the order-state cards with their brass thresholds (4 hours before the price hold ends, 8 hours before cancellation); and the action-by-order-state table for Owners and Managers.

UX-DR46: The Account home grouped as Your business, Money and You, role-filtered; Vouch codes showing limits first and earlier codes by last 4 characters and outcome only; the Established progress list with no dollar figure; Credit notes with friendly status words (Ready to use, Partly used, Fully used, Refunded, Expired); the Loyalty tier ladder with a meter; the Close the business checklist; Saved on this phone.

UX-DR47: Finance console screens: Today with the rate card (brass from 36 hours, red from 44); the three-column Receipt review with five checks written in words and a line saying what happens next; Unmatched credits with a likely answer per row; the bank-statement import check step with remembered column layout; the Platform Rate page with the exact stop time, weekend warning and append-only history; the two-step refund panel.

UX-DR48: Operations console screens: order approval with a per-line shelf check; the one-shipment timeline that never offers a wrong jump; the Support desk with details hidden until the caller is verified; the four-step returned-device arrival; Products with filter chips and a "Before it can go live" checklist; the stock ledger with on-hand-after; the guide editor with a live phone preview and a required "Describe the photo" field.

UX-DR49: Admin console screens: the Level 3 review layout (who is asking, who vouches, three checks with who, when and a note); NIN review with the number hidden until a reason code is picked; the one-business-account tools; the single Approvals page with a preview per change; Pricing and rules with a what-it-will-do preview; the weekly review sign-off; the dashboard tiles with neutral status chips and "Not enough data yet"; Holds and blocklist with "Check an item"; Terms publishing with an Owner-screen preview.

UX-DR50: Enforce the version 1 banned list as a review checklist: no step-by-step tour, in-app inbox, push notifications, adverts, streaks or badge counts, autoplay video, spinner on its own, "Access denied", lock icon on something a role can never use, dark mode, or any dollar figure shown to a business.

### FR Coverage Map

Each FR is mapped to one epic. Where part of an FR is delivered by a story in another epic, both are named.

FR-1: Epic 3 - Sign up and verify a contact
FR-2: Epic 3 - Level 1 browsing without prices
FR-3: Epic 4 - Complete the profile for Level 2
FR-4: Epic 4 - NIN verification and manual review
FR-5: Epic 4 - Generate a Vouch Code (the Manager's request to the Owner is a story in Epic 5)
FR-6: Epic 4 - Request Level 3
FR-7: Epic 4 - Admin approves Level 3
FR-8: Epic 4 - Seed the first cohort
FR-9: Epic 4 - Suspend and grant vouching rights
FR-10: Epic 5 - Invite and manage team members
FR-11: Epic 5 - Role permissions and the Technician-safe schema
FR-12: Epic 2 - Maintain Products
FR-13: Epic 2 - Platform Markup and the margin floor
FR-14: Epic 2 - Suppliers and the Active Supplier
FR-15: Epic 2 - Bulk upload of Products
FR-16: Epic 2 - Stock quantities and status
FR-17: Epic 2 - Back-order configuration
FR-18: Epic 8 - Stock reservation at Paid and shortages
FR-19: Epic 2 - Price computation and rounding
FR-20: Epic 2 - Set and guard the Platform Rate (the 48-hour block on Order creation is enforced in Epic 7)
FR-21: Epic 14 - Loyalty Tier calculation
FR-22: Epic 14 - Tier visibility
FR-23: Epic 2 - Discount rule and caps
FR-24: Epic 4 - Prices at the current tier, shown once approved
FR-25: Epic 6 - Projects, Rooms and the Parts List
FR-26: Epic 6 - Project Items
FR-27: Epic 6 - Price visibility in Projects
FR-28: Epic 12 - Offline design (the sync core is built in Epic 6)
FR-29: Epic 6 - Generate a Proposal
FR-30: Epic 6 - Proposal PDF
FR-31: Epic 6 - Regenerate overwrites
FR-32: Epic 6 - Proposal expiry
FR-33: Epic 7 - Cart for direct buy
FR-34: Epic 7 - Create an Order
FR-35: Epic 7 - Order pricing against the Proposal
FR-36: Epic 7 - Back-order opt-in
FR-37: Epic 7 - Fulfilment Method and delivery cost
FR-38: Epic 8 - Price Lock, repricing and expiry (expiry with no Receipt is a story in Epic 7)
FR-39: Epic 7 - Ship in parts or in full (the 14-day offer to send what is ready is a story in Epic 9)
FR-40: Epic 8 - Pay by bank transfer and upload a Receipt
FR-41: Epic 8 - Confirm Payments against the bank statement
FR-42: Epic 8 - Order payment progress to Paid
FR-43: Epic 8 - Credit Notes
FR-44: Epic 9 - Approve Paid Orders
FR-45: Epic 9 - Shipments and statuses (the automatic non-delivery Case is a story in Epic 13)
FR-46: Epic 9 - Track Orders
FR-47: Epic 7 - Delivery Zones and Zone Rates
FR-48: Epic 12 - Manage Documentation
FR-49: Epic 12 - Access Documentation
FR-50: Epic 12 - Documentation offline
FR-51: Epic 11 - Email and WhatsApp notifications (the three returns messages ship in Epic 16)
FR-52: Epic 1 - Staff accounts and roles (the Approvals page and "acting as" are stories in Epic 2; bank-details editing is a story in Epic 8)
FR-53: Epic 1 - Audit trail (the founder's weekly review is a story in Epic 2; unusual-pattern alerts arrive in Epic 4)
FR-54: Epic 1 - Installable app shell
FR-55: Epic 3 - Low-bandwidth use (Receipt upload resume is a story in Epic 8)
FR-56: Epic 7 - Stores
FR-57: Epic 8 - Cancel an Order (cancelling with nothing paid is a story in Epic 7; requests after Approval are a story in Epic 9)
FR-58: Epic 16 - Raise a Return Request
FR-59: Epic 16 - Reporting windows
FR-60: Epic 16 - Investigation and decision
FR-61: Epic 16 - Remedies
FR-62: Epic 16 - Return freight and handover
FR-63: Epic 16 - Exclusions
FR-64: Epic 16 - Warranty pass-through
FR-65: Epic 9 - Serial numbers at dispatch
FR-66: Epic 16 - Counterfeit procedure
FR-67: Epic 13 - Serial number lookup
FR-68: Epic 16 - Issue Codes
FR-69: Epic 3 - Terms and privacy acceptance (Level 3 acceptance and version publishing are stories in Epic 4; the Order block on a new version is enforced in Epic 7)
FR-70: Epic 5 - Business profile
FR-71: Epic 3 - Authentication and recovery (staff two-step sign-in is in Epic 1; change of Owner is a story in Epic 5)
FR-72: Epic 5 - Holds, revocation, blocklist and closure (the Hold is enforced at Order creation in Epic 7 and the blocklist at Payment confirmation in Epic 8; closure checks grow with each module)
FR-73: Epic 3 - Catalogue search, filters and stock display (price display and price sort arrive in Epic 4; offline search in Epic 12)
FR-74: Epic 2 - Goods-in record and stock ledger
FR-75: Epic 7 - Amend an Order before Approval (the change request on a Paid Order is a story in Epic 9)
FR-76: Epic 4 - Staff continuity, deputies and the holiday calendar
FR-77: Epic 10 - Tax invoices and credit-note documents
FR-78: Epic 4 - Staff work queues
FR-79: Epic 13 - Cases
FR-80: Epic 15 - Dashboard and exports

## Epic List

Sixteen epics, in build order. Epics 1 to 10 are Priority 1 (first-Order critical), 11 to 15 are Priority 2 (launch week) and 16 is Priority 3 (about three months after launch). The UJ-1 demonstration, from first Room to delivered equipment, completes at the end of Epic 9.

### Epic 1: Staff console, roles, audit trail and the app shell
Admin, Finance and Operations sign in to the console with two steps and see only their own work; every action writes to an audit trail nobody can edit; and the installable Integrator app opens on a phone.
**FRs covered:** FR-52, FR-53, FR-54
**Notes:** Carries the project scaffold, the 13-module layout, architecture tests, CI, the three environments, hourly offsite backups and founder alerts. Kernel parts built here: Action base, lock registry, transition helper, outbox, audit, UUIDv7 and Clock; the epic closes only when a real staff Action exercises each. Includes the spike on a least-privilege database role (AD-21). Console theme and design tokens land here, with the app shell (tokens, service worker, one screen) proven on a low-end Android, and the automated accessibility check running in CI from this epic.

### Epic 2: Operations builds the Catalogue and Finance sets the rate
Operations loads Products, Suppliers, stock and goods-in, singly or by bulk upload; Admin sets markups and the margin floor; Finance sets and reconfirms the Platform Rate; every Selling Price is computed to the kobo; and any change that needs a second person waits on one Approvals page.
**FRs covered:** FR-12, FR-13, FR-14, FR-15, FR-16, FR-17, FR-19, FR-20, FR-23, FR-74
**Notes:** Console screens only, so the launch Catalogue can be loaded while the app is built. Kernel parts landing here: the Money type, the timer sweep (rate staleness), the upload pipeline (Product images), and the Approvals page with its first real two-person approvals (rate change, Active Supplier switch, bulk price file). "Acting as" another role and the founder's weekly review are built here, where the first overrides exist. Every Pricing formula and addendum test vector lands here, including repricing, the VAT split and the Integrator Margin, so the price snapshot shape is proven before anything is stored; delivery pricing waits for Epic 7. The snapshot carries tier and discount fields from day one. Product Range management is assumed to be Admin's (PRD §8.3 question 18).

### Epic 3: Sign up and browse the Catalogue
A visitor signs up with an email or phone, accepts the Terms, logs in with a one-time code or passkey, and searches and browses the Catalogue and its stock status without prices, on a slow connection.
**FRs covered:** FR-1, FR-2, FR-55, FR-69, FR-71, FR-73
**Notes:** The first full phone epic: the five-tab shell in its two-tab pre-approval form, the core component set, and the Catalogue list meeting its budget on slow 3G. The field-visibility registry lands here with the first no-price response, and the idempotency store with the first retryable request from the phone. Needs the SMS and email providers (fakes until contracted) and Terms text from counsel (placeholder version until then).

### Epic 4: Get approved and see prices
An Owner confirms who they are with a NIN, redeems a Vouch Code from an Established Integrator, and is approved by Admin, after which Selling Prices appear; Admin can also seed the first businesses directly.
**FRs covered:** FR-3, FR-4, FR-5, FR-6, FR-7, FR-8, FR-9, FR-24, FR-76, FR-78
**Notes:** The first real staff queues (NIN reviews, Level 3 requests) arrive here, so Business Hours arithmetic, the holiday calendar, work items, escalation, deputies and the out-of-office switch are built here; every later epic adds its own queue to the same table. Staff arrival alerts go by email from this epic. Until Epic 9, an Established Integrator exists only by Admin grant or platform Vouch Code; the Completed Order rule switches on in Epic 9. Needs the NIN provider (fake until contracted) and the NIN key handling of AD-16.

### Epic 5: Teams, business profile and account control
An Owner invites Managers and Technicians, each seeing only what their role allows; the business keeps its profile and logo; and Admin can hold, revoke, block or close an account.
**FRs covered:** FR-10, FR-11, FR-70, FR-72
**Notes:** The Technician-safe test suite runs in CI from this epic and every later epic adds its surfaces to it. Includes the Manager's vouch-code request and the change of Owner. Personal-data erasure and export orchestration (AD-26) starts here.

### Epic 6: Design a Project and send a Proposal
An Owner, Manager or Technician builds a job room by room with a Parts List, Technicians seeing no prices; an Owner or Manager turns it into a client-ready Proposal PDF with one Integrator Margin.
**FRs covered:** FR-25, FR-26, FR-27, FR-29, FR-30, FR-31, FR-32
**Notes:** Project screens read and write the phone's local store from the start (AD-19). This epic carries the sync core: the store per User and role, UUIDv7 ids, Project push and pull, the Project revision, and the purge on a role change. The PDF renderer lands here.

### Epic 7: Order a Project or a Cart
An Owner or Manager creates an Order from a whole Project or a Cart at exactly the price confirmed, sees what changed since the Proposal, opts in to back-orders, chooses delivery or pick-up and parts or full, and can change delivery details; Operations sets up Delivery Zones and Stores.
**FRs covered:** FR-33, FR-34, FR-35, FR-36, FR-37, FR-39, FR-47, FR-56, FR-75
**Notes:** The Order lifecycle closes inside this epic: expiry at 48 hours with no Receipt, and cancelling with nothing paid. The gates defined earlier are enforced here: a Hold, unaccepted new Terms, a stale or frozen Platform Rate, and Pending Activation. Delivery pricing lands here. Zones and Stores need only Epic 1 and can be built earlier.

### Epic 8: Pay by bank transfer and hold the price
The Integrator pays by transfer and uploads a Receipt for each; Finance imports the bank statement and confirms each Payment; the Price Lock, repricing and expiry apply; stock is reserved at Paid; and Credit Notes are issued, applied and refunded.
**FRs covered:** FR-18, FR-38, FR-40, FR-41, FR-42, FR-43, FR-57
**Notes:** The densest logic in the product (edge-case rows 40 to 58); every transition gets a two-racer concurrency test and a replay test. Includes bank-details editing, Receipt upload resume and the blocklist check at confirmation. WhatsApp template submission to Meta starts during this epic.

### Epic 9: Approve, dispatch and track deliveries
Operations approves Paid Orders after a shelf check and ships them in Shipments with serial numbers recorded; the Integrator follows each to Delivered or Signed For, and the Order completes.
**FRs covered:** FR-44, FR-45, FR-46, FR-65
**Notes:** Includes cancellation requests after Approval. A Lost or Delivery Failed Shipment blocks Completed and is flagged for Operations; the automatic non-delivery Case arrives in Epic 13. `NetDeliveredGoodsQuery` is published here and the Completed Order rule for Established Integrators switches on.

### Epic 10: Tax invoices and credit-note documents
Every Paid Order has one numbered tax invoice and every Credit Note a document, downloadable by Owners and Managers.
**FRs covered:** FR-77
**Notes:** Separated so the accountant's open questions (fields, numbering, serials on the invoice, VAT on delivery and Credit Notes) cannot hold Epic 8 open. Invoices are structured records with gapless numbers (AD-9).

### Epic 11: WhatsApp and email notifications
Integrators and staff are told about the launch events by WhatsApp and email, with amounts only for Owners and Managers.
**FRs covered:** FR-51
**Notes:** First in Priority 2 because several messages protect money (Payment Unconfirmed, the Price Lock deadline, the expiry warning). Earlier epics already emit the events; this epic adds templates, channels, opt-in handling, the 15-minute fallback and quiet hours. The three returns messages ship with Epic 16.

### Epic 12: Work on site with no signal
A Technician saves a Project and its guides to the phone and designs, searches the Catalogue and reads Documentation offline; Operations writes and versions the guides.
**FRs covered:** FR-28, FR-48, FR-49, FR-50
**Notes:** Adds the rest of offline to Epic 6's sync core: offline Catalogue search, the conflict screen, held edits, the 14-day check-in lock, restore, and saved Documentation. Needs only Epics 3 to 6, so it can run alongside Epics 7 to 10.

### Epic 13: Support Cases and serial lookup
An Integrator reports a problem from a delivery; Support verifies the caller, looks up the serial number and records a Case for every contact.
**FRs covered:** FR-67, FR-79
**Notes:** Makes the manual returns process workable from launch. Includes the automatic non-delivery Case and the serial exception.

### Epic 14: Loyalty Tiers
A business earns a better price from its trailing six-month spend and sees its tier, its progress and any coming change.
**FRs covered:** FR-21, FR-22

### Epic 15: Dashboard and finance exports
The founder sees every success measure with its definition, and Finance exports its monthly files.
**FRs covered:** FR-80

### Epic 16: Returns, warranty and counterfeit
A faulty device goes from a Case to an Issue Code, a Return Request approved by the business, a checked arrival and a decided remedy; change-of-mind returns, non-delivery claims, warranty and the counterfeit procedure work in the app.
**FRs covered:** FR-58, FR-59, FR-60, FR-61, FR-62, FR-63, FR-64, FR-66, FR-68

### Rules that apply to every epic

- Every epic emits its outbox events with their facts from the start, so Epic 11 adds only templates and channels.
- Each gate (Hold, new Terms, blocklist, stale rate) is enforced in the epic that builds the gated action.
- Components beyond the core set are built in the epic that first uses them, to the `DESIGN.md` and `EXPERIENCE.md` specs.
- Every story names the requirements it implements in a **Requirements** line under its user statement.
- Every story's acceptance criteria say what a keyboard user, a screen-reader user and someone at 200% zoom can do (UX-DR22).
- Every screen a story builds covers the states listed for its surface in `EXPERIENCE.md` State Patterns, and skips one only on purpose (UX-DR18, UX-DR19).
- All copy follows the voice rules, formats and word list, carries marked placeholders for the counsel wording items, and is checked against the version 1 banned list at review (UX-DR31, UX-DR33, UX-DR34, UX-DR50).
- Each module publishes its reporting views and stores its pricing record as it is built, so Epic 15 reads what already exists.

### Outside inputs and when they are needed

| Input | Owner | Needed before | Until then |
|---|---|---|---|
| Terms and Privacy Notice text | Counsel | Epic 3 | A placeholder version; the real text is a publish, not a release |
| SMS and email providers | Founder | Epic 3 | Fakes behind the interfaces |
| NIN provider | Founder | Epic 4 | A fake behind the interface |
| Tax-invoice and credit-note fields, numbering, VAT on delivery | Accountant | Epic 10 | Epic 10 does not start |
| WhatsApp templates approved by Meta | Founder | Epic 11 | Submission starts during Epic 8 |
| The nine wording items | Counsel | Launch | Marked placeholders (UX-DR34) |
| NDPA transfer basis for London hosting and R2 | Counsel | Launch | Build proceeds |

Each item gates launch even where the build can proceed without it.

## Epic 1: Staff console, roles, audit trail and the app shell

Admin, Finance and Operations sign in to the console with two steps and see only their own work; every action writes to an audit trail nobody can edit; and the installable Integrator app opens on a phone.

### Story 1.1: Scaffold the project with its rules enforced

As the builder,
I want the project scaffolded from the architecture's stack with its structural rules checked automatically,
So that every later story is built on the same layout and cannot quietly break the single write path.

**Requirements:** AD-1, AD-2, AD-11; architecture Consistency Conventions; test design R-11; TC-1, TC-2, TC-4

**Acceptance Criteria:**

**Given** an empty repository
**When** the scaffold is complete
**Then** the application runs locally on PHP 8.4, Laravel 13.x, Filament 5.x, Sanctum 4.x and PostgreSQL 17, with a React 19 and TypeScript app built by Vite 8 from `resources/app/` into Laravel's public assets
**And** `app/Modules/` contains the thirteen modules (Shared, Identity, Catalogue, Pricing, Projects, Orders, CreditNotes, Fulfilment, Documentation, Notifications, Returns, Staff, Reporting), each with the directory layout in the architecture's Consistency Conventions
**And** no domain table exists yet.

**Given** a model `save()`, `update()`, `delete()` or `create()` call outside a module's `Actions/` directory
**When** the test suite runs
**Then** an architecture test fails and names the file (AD-1).

**Given** a class that imports another module's Eloquent model or queries another module's table
**When** the test suite runs
**Then** an architecture test fails and names the file (AD-2).

**Given** a migration with a cross-module foreign key that is not `ON DELETE RESTRICT`
**When** the test suite runs
**Then** an architecture test fails.

**Given** a request to `/api/v1/health`
**When** it responds
**Then** the body uses `snake_case` keys and carries `server_time` as an ISO 8601 UTC string
**And** an error response has the shape `{message, errors}` plus a machine `code`.

**Given** any log line the application writes
**When** it is inspected
**Then** it is structured JSON and carries the request id.

**Given** a push to the repository
**When** CI runs
**Then** it runs the unit, feature and architecture tests and the linters, and a failure blocks the merge.

**Given** the staging environment
**When** the application boots there
**Then** every external provider interface is bound to its fake and the environment holds synthetic data only.

**Given** a new table's primary key
**When** a row is created through the `Shared` id helper
**Then** the key is a UUIDv7.

**Given** the test suite
**When** it connects to a database
**Then** it uses PostgreSQL 17, and CI fails if any test connection is anything else (test design R-11).

**Given** the `Shared` Clock
**When** tests or staging run
**Then** the current time can be set and advanced without waiting, and every module reads time only through it (TC-2).

**Given** local and test environments
**When** a test needs a starting state
**Then** factories and a seeding command create it by calling Actions, and neither is available in production (TC-4).

### Story 1.2: Production hosting with an hourly offsite copy and founder alerts

As the founder,
I want production running on managed hosting with an hourly copy held by a second provider and alerts sent to me,
So that Receipts and money records survive a failure of the host and I know when something has stopped.

**Requirements:** NFR-5; AD-20

**Acceptance Criteria:**

**Given** the production environment
**When** it is provisioned
**Then** it runs on Laravel Cloud (EU West, London) with Serverless Postgres 17, a managed queue, the scheduler and object storage
**And** cache, locks, rate limits and sessions use the database driver
**And** the code uses only standard PostgreSQL, the S3 API and standard Laravel features.

**Given** production is running
**When** each hour passes
**Then** an encrypted logical database dump and a copy of object storage are written to a separate provider outside Laravel Cloud (NFR-5 recovery point of 1 hour).

**Given** the most recent offsite copy
**When** the restore runbook is followed into a clean environment
**Then** the application starts and serves the restored data
**And** the time taken is recorded against the 4-hour recovery target
**And** the runbook states that the drill repeats each quarter.

**Given** a failed queue job, a failed or missing hourly backup, or spend passing its budget
**When** it happens
**Then** the founder receives an alert naming what failed and when.

**Given** secrets the application needs
**When** they are stored
**Then** they live in the host's secret store and never in the repository.

### Story 1.3: Staff sign in to the console with two steps

As a member of staff,
I want to sign in to a console that is separate from the Integrator app and asks for a second factor,
So that only the platform team can reach the screens that move money, stock and access.

**Requirements:** FR-52, FR-71; AD-18; UX-DR1 to UX-DR5, UX-DR25, UX-DR29

**Acceptance Criteria:**

**Given** no staff account exists
**When** the builder runs the first-Admin console command with a name and email
**Then** an Admin account with the founder flag is created through a `Staff` Action
**And** an audit row records the change with `system` as the actor.

**Given** a staff member with an active account
**When** they sign in at `/console`
**Then** they must pass a first step and a second, different factor before any console page loads
**And** a staff member who has not yet set up a second factor is made to set one up before continuing.

**Given** a staff session
**When** it is used against the Integrator app's `/api/v1` routes
**Then** it is refused, because staff use a separate guard (AD-18).

**Given** a signed-in staff member
**When** 11 hours 55 minutes have passed since sign-in
**Then** the console warns that the session ends in 5 minutes and offers "Stay signed in"
**And** at 12 hours the session ends and unsaved form values are kept as a draft where the form supports it.

**Given** the console on a desktop at 1280px
**When** any page loads
**Then** it shows a green top bar with the wordmark, "Staff console", the current West Africa Time, and the person's name and role, and a 224px left rail on the aged surface
**And** colours, type, spacing and shapes come from the Ledger tokens (UX-DR1 to UX-DR5), in light mode only.

**Given** the console below 1024px wide, or zoomed to 400%
**When** a page loads
**Then** the rail becomes a menu button, side-by-side panes stack and a wide table scrolls inside its own box.

**Given** a phone
**When** it opens the console
**Then** it shows a notice that the console is for a desktop.

**Given** a keyboard-only user on the sign-in page
**When** they tab through it
**Then** every control is reachable in reading order with a visible 3px focus ring, a skip link passes the menu, and errors are announced in words beside the field
**And** a screen reader hears one top heading and labelled fields, and the page works at 200% zoom.

*Open: the second-factor method for staff is PRD §8.3 question 14. This story assumes an authenticator-app code as the second factor until the founder decides.*

### Story 1.4: Admin creates and deactivates staff accounts

As an Admin,
I want to create Finance, Operations and Admin accounts and deactivate them when someone leaves,
So that each person has their own audited sign-in and a departed colleague loses access at once.

**Requirements:** FR-52, FR-76; AD-4, AD-15; edge-case row 14; UX-DR11; test design TC-3

**Acceptance Criteria:**

**Given** an Admin on the Staff page
**When** they create an account with a name, email and role
**Then** the account is created through a `Staff` Action that locks its root, checks the declared transition, and writes an audit row naming the Admin, in one transaction (AD-4)
**And** a `StaffAccountCreated` event is written to the outbox in the same transaction.

**Given** a `StaffAccountCreated` event in the outbox
**When** the queue worker relays it
**Then** the invitee is emailed a sign-in invitation through the email provider interface
**And** relaying the same event twice sends one email, because the listener records a delivery key before calling the provider.

**Given** an invited staff member
**When** they follow the invitation
**Then** they set up their sign-in and second factor before reaching any console page.

**Given** an Admin deactivating a staff account
**When** they confirm the dialog, whose title is a question naming the person and whose body lists what will happen
**Then** the account moves to Deactivated, every session of that person is revoked at once, and an audit row is written.

**Given** only one active Admin remains
**When** anyone tries to deactivate that account
**Then** the Action refuses and the screen says why in words.

**Given** two Admins deactivating the same account at the same moment
**When** both submit
**Then** one succeeds and the other sees the current state with who did it and when, never an error.

**Given** the Staff page
**When** an Admin views it
**Then** each row shows name and initial, role, duties and status in a console table with a fixed header
**And** J, K, Enter and / move around the list, and a screen reader hears the table caption and column headings.

**Given** a Finance or Operations account
**When** it requests the Staff page or its Actions
**Then** the server refuses.

**Given** the email provider's fake
**When** a test configures it
**Then** it can script success, definitive failure, timeout or error for each call, and every later provider fake offers the same (TC-3).

### Story 1.5: Each role sees only its own work

As a member of staff,
I want the console to show only what my role can use,
So that I am never offered a screen or an action that is not mine.

**Requirements:** FR-52; UX-DR29, UX-DR38

**Acceptance Criteria:**

**Given** the FR-52 capability matrix
**When** it is implemented
**Then** it is one registry in the `Staff` module that maps each capability to Finance, Operations and Admin, and every console resource and Action declares the capability it needs.

**Given** a console resource or Action with no declared capability
**When** the test suite runs
**Then** a test fails and names it.

**Given** a signed-in staff member
**When** the rail renders
**Then** it shows only the groups and links their role can use, with nothing greyed out and no padlock.

**Given** a staff member who follows a link to a page outside their role
**When** it loads
**Then** the server refuses and the page names who can do it, never "Access denied".

**Given** an Operations account
**When** an Admin turns its Support duties switch on or off
**Then** the Support permission group is added or removed without a software release, the switch shows the word On or Off, and an audit row is written.

**Given** an Admin
**When** they view the rail
**Then** they see every group.

**Given** a keyboard or screen-reader user
**When** they move through the rail
**Then** the current place is marked and announced, and groups are announced as lists.

### Story 1.6: An audit trail nobody can edit

As the founder,
I want every change recorded in a trail that cannot be altered and can be checked,
So that I can trust the record when money, stock or access is questioned.

**Requirements:** FR-53; NFR-5; AD-21

**Acceptance Criteria:**

**Given** the audit table
**When** any database session attempts an `UPDATE` or `DELETE` on it
**Then** a database trigger rejects it.

**Given** the least-privilege database role spike (architecture Open Question 1)
**When** it concludes
**Then** either the application runs under a role without `UPDATE` and `DELETE` on the audit table, or the fallback (the hash chain plus the offsite copy) is recorded as the decision in the repository.

**Given** unsealed audit rows
**When** the scheduled sealing job runs
**Then** a single writer computes each row's hash from the previous row's hash in sequence order
**And** a failed or missed run alerts the founder.

**Given** the Audit trail page
**When** an Admin runs the chain check
**Then** it reports that the chain is intact, or names the first sequence number where it breaks.

**Given** an Action that changes a personal-data field such as a staff email
**When** its audit row is written
**Then** the row holds entity identifiers and records the field as "changed" or a keyed hash, never the value
**And** each module keeps a list of its personal-data columns that this rule reads.

**Given** an audit row
**When** it is written by a deputy or under another role
**Then** it can carry `acting_as` and `acting_for`.

**Given** a staff record in the console
**When** its History panel is opened
**Then** it lists who did what and when from the audit trail, read-only, with no Edit or Delete.

**Given** the Audit trail page
**When** a Finance or Operations account requests it, or anyone but an Admin uses "Look up" on an identifier
**Then** the server refuses.

**Given** the restore runbook from Story 1.2
**When** this story is complete
**Then** the runbook recreates the audit trigger and database roles before any write.

### Story 1.7: The Integrator app installs on a phone

As an Integrator,
I want to install the app to my phone's home screen from the browser,
So that I can open it like any other app.

**Requirements:** FR-54; NFR-6; AD-19; UX-DR1 to UX-DR3, UX-DR21, UX-DR22, UX-DR24, UX-DR26, UX-DR30, UX-DR32

**Acceptance Criteria:**

**Given** Chrome on Android 9 or later, or Safari on iOS 16.4 or later
**When** a person installs the app and opens it from the home screen
**Then** it opens without a browser address bar (FR-54).

**Given** the app shell
**When** it loads
**Then** it is one React and TypeScript single-page app served by Laravel, with a manifest and a service worker, and no Inertia, Livewire or Blade page beyond the shell (AD-19)
**And** colours, type, spacing and shapes come from the same Ledger tokens as the console, with system fonts only and no bundled font file.

**Given** a person who is not signed in
**When** the app opens
**Then** it shows a holding screen with the wordmark and the text pages built on one template: Install the app (with iPhone steps written in words), the accessibility statement, not found, signed out and service down.

**Given** a new version of the app has been fetched
**When** a person is using the app
**Then** it never reloads under them and an "Update when you finish" banner is shown.

**Given** a second visit from a browser where the app is not installed
**When** the screen loads
**Then** installing is offered once, and it is always available from the Install page.

**Given** money, dates, ranges, times, time left, phone numbers, references, read-aloud codes, serials, names and measures
**When** the app or the console shows them
**Then** they come from one set of shared formatters that follow the Formats table in `EXPERIENCE.md` (UX-DR32), each with tests.

**Given** a phone 320px wide
**When** any shell screen loads
**Then** it shows in one column with a 16px margin and no sideways scrolling, and works in both orientations.

**Given** the low-end test phone (Android, 2 GB) on slow 3G (400 kbps, 400 ms)
**When** the shell is opened cold
**Then** the time to the first usable screen is measured and recorded as the baseline for later performance budgets.

**Given** any screen of the app or console on any change
**When** CI runs
**Then** an automated accessibility check runs against it and a new failure blocks the merge (UX-DR22).

**Given** a keyboard-only user, a screen-reader user, and a user at 200% zoom
**When** each uses the shell screens
**Then** every control is reachable with a visible focus ring, each screen has landmarks and one top heading, and nothing is lost at 200%
**And** the system's larger-text and reduce-motion settings are honoured.

## Epic 2: Operations builds the Catalogue and Finance sets the rate

Operations loads Products, Suppliers, stock and goods-in, singly or by bulk upload; Admin sets markups and the margin floor; Finance sets and reconfirms the Platform Rate; every Selling Price is computed to the kobo; and any change that needs a second person waits on one Approvals page.

### Story 2.1: Admin sets up Product Ranges and their markup

As an Admin,
I want to create Product Ranges with a default Platform Markup and set the margin floor,
So that every Product has a markup to price from and no price can fall below a safe margin.

**Requirements:** FR-13; edge-case row 20

**Acceptance Criteria:**

**Given** an Admin on the Pricing and rules page
**When** they create a Product Range with a name
**Then** it is saved with a default Platform Markup of 25% and a compliance flag of NCC type approval, SONCAP or none
**And** the change is audited.

**Given** an existing Product Range
**When** an Admin renames it or changes its markup or compliance flag
**Then** the change is saved with a required reason and appears in the range's history.

**Given** a markup below zero
**When** an Admin tries to save it
**Then** the server refuses with a sentence saying why (edge-case row 20).

**Given** the margin floor setting
**When** the system is first set up
**Then** it is 8% over Supplier Cost, stored as a database row and changed only through an Action.

**Given** a Finance or Operations account
**When** it tries to create or edit a Product Range, a markup or the floor
**Then** the server refuses, and Operations sees the values shown and locked as Admin's.

**Given** a Product Range that still has Products in it
**When** an Admin tries to retire it
**Then** it is refused and the screen says how many Products must be moved first.

*Assumption: Product Range management belongs to Admin and a range with Products cannot be retired (PRD §8.3 question 18).*

### Story 2.2: Admin records and approves Suppliers

As an Admin,
I want to record each Supplier with its agreement terms and approve it,
So that only Suppliers whose return and counterfeit terms are known can supply the Catalogue.

**Requirements:** FR-14

**Acceptance Criteria:**

**Given** an Admin on the Suppliers page
**When** they add a Supplier
**Then** it is saved as not approved, with fields for its returns terms, its dead-on-arrival terms and window in days, and its counterfeit-chargeback terms, each shown as stated or missing.

**Given** a Supplier with any of the three terms missing
**When** an Admin views it
**Then** "Approve the supplier" is visible, dashed and off, with the reason beside it
**And** "Approve with a reason…" is offered, which requires a written reason.

**Given** a Supplier with all three terms recorded
**When** an Admin approves it
**Then** it becomes approved and the decision is audited with who and when.

**Given** a Supplier whose dead-on-arrival window is shorter than 30 days
**When** it is saved
**Then** the shorter window is stored so it can shorten the window given to businesses (FR-59).

**Given** the Suppliers list
**When** an Admin views it
**Then** each row shows the Supplier, whether it is approved, its Product count and which terms are stated or missing.

**Given** a Finance or Operations account
**When** it tries to approve a Supplier
**Then** the server refuses.

### Story 2.3: Operations creates and edits a Product

As an Operations officer,
I want to create a Product and fill in its details and Supplier Costs,
So that it can be priced, stocked and sold.

**Requirements:** FR-12

**Acceptance Criteria:**

**Given** an Operations officer on the Products page
**When** they create a Product
**Then** it is saved as a draft with five sections: Identity, Measures, Returns and compliance, Lifecycle and cost, and Stock and content.

**Given** the Identity section
**When** a SKU already in use is entered
**Then** the save is refused, and a saved SKU can never be changed.

**Given** the Returns and compliance section
**When** it is shown
**Then** each of the four returnability flags (special order, end-of-life, refurbished or demonstration, software or licence) must be answered Yes or No with nothing preselected
**And** it holds a serialised flag, an optional serial-format, a Warranty Period or an explicit "None", a dead-on-arrival window, and a compliance reference that is marked needed when the Product Range carries a compliance flag.

**Given** the Measures section
**When** it is filled in
**Then** it holds a unit of measure, a pack size, weight and dimensions.

**Given** the Lifecycle and cost section
**When** Operations enters a Supplier Cost for a Supplier
**Then** it is stored in USD to four decimal places with the date it was set
**And** a cost of zero or below is refused.

**Given** an Operations account
**When** it views a Product
**Then** the Platform Markup and the Active Supplier are shown and locked as Admin's, and the server refuses any attempt to change them (FR-12).

**Given** a long Product form
**When** a save fails or the session nears its end
**Then** nothing typed is lost and a draft is kept.

**Given** any Product change
**When** it is saved
**Then** an audit row records it.

**Given** a keyboard or screen-reader user on the Product form
**When** they move through it
**Then** every field has a visible label, "Optional" and "needed" are in words, and errors are listed at the top and beside each field.

### Story 2.4: A Product goes live, and leaves

As an Operations officer,
I want a checklist that says what a Product still needs before it can be sold, and a way to retire it,
So that nothing reaches Integrators half-described.

**Requirements:** FR-12, FR-14; AD-17

**Acceptance Criteria:**

**Given** a draft Product
**When** Operations opens it
**Then** a "Before it can go live" checklist shows each requirement as Clear or Needs action in words: a Product Range, a serialised answer, a Warranty Period or "None", all four returnability answers, a compliance reference where the range needs one, and an Active Supplier with a cost above zero.

**Given** a draft Product with no Active Supplier
**When** an Admin sets its first Active Supplier
**Then** only an approved Supplier with a Supplier Cost above zero can be chosen.

**Given** any checklist line that says Needs action
**When** Operations views the Product
**Then** "Make it active" is visible, dashed and off, with the reason beside it.

**Given** a clear checklist
**When** Operations makes the Product active
**Then** its lifecycle moves from draft to active through the declared transition table and the change is audited
**And** Documentation is not required to go live.

**Given** an active Product
**When** Operations discontinues it or sets its quarantine flag
**Then** the change is saved with a reason and audited, and only transitions in the table are offered.

**Given** the Products list
**When** Operations views it
**Then** it has filter chips for All, Needs attention, Drafts and Discontinued, and a row shows SKU, name, brand, model, lifecycle and the Active Supplier.

**Given** the search box
**When** a SKU or model number is typed, including with a small typing error
**Then** that Product is returned first, using PostgreSQL `pg_trgm` (AD-17).

### Story 2.5: Product images

As an Operations officer,
I want to add photos to a Product,
So that Integrators can recognise what they are buying.

**Requirements:** FR-12; AD-14, AD-15; UX-DR16

**Acceptance Criteria:**

**Given** Operations adding an image to a Product
**When** the upload starts
**Then** an upload-intent request records `upload_started_at`, presigned multipart URLs are returned, and a completion call finishes it (AD-14).

**Given** an uploaded file
**When** it arrives
**Then** it sits under a quarantine prefix until a queued step has scanned it for malware, stripped EXIF data, checked its real type and converted HEIC, and only then is it promoted.

**Given** a promoted image
**When** it is stored
**Then** derivatives at 320, 640 and 1280 px in WebP are created under immutable content-hashed keys.

**Given** a file that is corrupt, blank, not an image, or fails the scan
**When** it is processed
**Then** it is refused with a sentence saying why and is never shown.

**Given** an upload in progress
**When** Operations watches it
**Then** a file row shows the name, size and percentage with a meter and a Remove button, and the meter's figure is also in words.

**Given** the malware scanner
**When** the application runs in staging or tests
**Then** it is reached through an interface bound to a fake (AD-15).

### Story 2.6: Finance sets and reconfirms the Platform Rate

As a Finance officer,
I want to set the Platform Rate and reconfirm it each Business Day,
So that every naira price reflects what a dollar costs us today.

**Requirements:** FR-20; edge-case row 19

**Acceptance Criteria:**

**Given** a Finance or Admin account on the Platform Rate page
**When** it sets a rate with its reference source and spread
**Then** a new rate version is stored as `numeric(12,4)` with who, when, the old and new values, the source and the spread
**And** it takes effect at once.

**Given** a rate of zero or below
**When** it is submitted
**Then** it is refused.

**Given** a new rate that differs from the rate in force by more than 10%
**When** Finance submits it
**Then** it is refused as a probable typing error
**And** an Admin can override the refusal with a required reason, which is audited.

**Given** a new rate that differs by more than 3% and no more than 10%
**When** it is submitted in this story
**Then** it is refused with a sentence saying a second person is needed (Story 2.7 adds the approval route).

**Given** the rate in force
**When** Finance presses "Reconfirm the rate"
**Then** the reconfirmation is recorded with the time, and the button is off for the rest of that day with the reason beside it.

**Given** the Platform Rate page
**When** it is viewed
**Then** it shows the rate in force as "₦1,368.00 to the dollar", its source, spread and age, and an append-only history of every change and reconfirmation with no Edit or Delete.

**Given** an Operations account
**When** it tries to set or reconfirm the rate
**Then** the server refuses.

### Story 2.7: A rate change above 3% needs a second person

As an Admin or Finance officer,
I want every change that needs a second person to wait on one Approvals page,
So that no single person can move prices or money alone.

**Requirements:** FR-20, FR-52; AD-23; UX-DR17

**Acceptance Criteria:**

**Given** a rate change of more than 3% and no more than 10%
**When** Finance submits it
**Then** a pending approval is created with the requester, the reason, a reference to the change and an expiry, and the rate does not change.

**Given** the Approvals page
**When** a staff member with the capability opens a pending rate change
**Then** it says what the change will do, why two people are needed and who asked, with a price preview for real Products and the number of open Carts, Projects and Orders outside their Price Lock it touches.

**Given** the person who asked for a change
**When** they view it on the Approvals page
**Then** Approve is visible, dashed and off, with the reason beside it
**And** the database itself refuses an approval whose approver equals its requester (AD-23).

**Given** a different authorised person
**When** they approve
**Then** the change takes effect inside the Approve Action, and the audit row names both people.

**Given** a decline
**When** the approver submits it
**Then** a reason is required and is shown to the requester.

**Given** two people deciding the same pending approval at once
**When** both submit
**Then** the first valid decision wins and the other sees "Already approved by [name and initial] at [time]", never an error.

**Given** a pending approval past its expiry
**When** anyone tries to approve it
**Then** it is refused and shown as lapsed.

**Given** any staff member with something waiting for them
**When** the rail renders
**Then** "Waiting for a second person" shows a count.

### Story 2.8: Selling Prices computed to the kobo

As a Finance officer,
I want every Selling Price computed by one formula with one rounding rule,
So that the price on a screen, a document and a ledger is always the same number.

**Requirements:** FR-13, FR-19; NFR-4; AD-6, AD-7; edge-case rows 20, 21, 30

**Acceptance Criteria:**

**Given** the `Shared` Money type
**When** it is used
**Then** naira is held to 2 decimal places and USD to 4 as PostgreSQL `numeric`, arithmetic is decimal at full precision, and rounding is half-up to the kobo exactly once
**And** money in JSON is `{"amount": "<decimal string>", "currency": "NGN"|"USD"}` (AD-6).

**Given** a Supplier Cost of USD 100, a Platform Markup of 25%, no discount, VAT of 7.5% and a Platform Rate of NGN 1,330
**When** Pricing computes the unit price
**Then** it is NGN 166,250.00 before VAT, NGN 12,468.75 VAT, and a Selling Price of NGN 178,718.75
**And** a quantity of 1,000 gives a line total of NGN 178,718,750.00.

**Given** a unit of NGN 12.345 before VAT
**When** Pricing computes it
**Then** the unit is NGN 13.27 and a quantity of 7 is NGN 92.89, because rounding happens once per unit.

**Given** a Product with no markup override
**When** it is priced
**Then** it uses its Product Range's markup.

**Given** any combination of inputs
**When** Pricing computes a price
**Then** the pre-VAT price is never below Supplier Cost × (1 + margin floor).

**Given** the VAT rate setting
**When** an Admin changes it
**Then** a value outside 0 to 100 is refused, and a valid change goes to the Approvals page for a second person.

**Given** a new Platform Rate version
**When** it takes effect
**Then** displayed prices change at once.

**Given** a Product page in the console
**When** staff view it
**Then** it shows the Wholesale Price labelled USD and the base-tier Selling Price in naira with "VAT included".

**Given** any module other than Pricing
**When** it multiplies, divides or applies a percentage to money
**Then** an architecture test fails (AD-7).

### Story 2.9: The remaining Pricing formulas, proven

As the builder,
I want every Pricing formula and the price snapshot proven against the addendum's test vectors before anything stores a price,
So that Proposals, Orders, invoices and Credit Notes never need their stored prices migrated.

**Requirements:** FR-19, FR-23, FR-30, FR-35, FR-38, FR-61; NFR-4; AD-7; addendum test vectors; test design R-01

**Acceptance Criteria:**

**Given** a Supplier Cost of USD 45.00, a markup of 25%, a Tier Discount of 2%, VAT of 7.5% and a rate of NGN 1,330
**When** `QuoteLines` runs
**Then** the unit Selling Price is NGN 78,814.97.

**Given** a discount of 100% or more
**When** it is passed to Pricing
**Then** it is refused.

**Given** three client lines of NGN 10.10 before VAT
**When** `SplitVat` runs
**Then** each line is NGN 10.86 with VAT, the grand total is NGN 32.58 and the VAT line is NGN 2.28
**And** the grand total is identical with and without the VAT line.

**Given** an Order of USD 500 of goods priced at NGN 1,330
**When** `RepriceSnapshot` is called with a rate of NGN 1,400
**Then** the Amount Due moves from NGN 714,875.00 to NGN 752,500.00, and with NGN 600,000.00 confirmed the balance is NGN 152,500.00
**And** only the exchange rate changed; tier, markup and VAT stay as captured.

**Given** a Proposal Rate of NGN 1,330 and the NGN 5 tolerance setting
**When** the current rate is 1,334
**Then** 1,330 is kept, and when it is 1,336 then 1,336 is used.

**Given** a line price and the restocking fee
**When** `RestockingFee` runs
**Then** the credit equals the pre-VAT line price less 15%, plus the VAT on it.

**Given** a call to `QuoteLines`
**When** it returns
**Then** it returns a versioned price snapshot carrying, per line, the Platform Rate version, Tier Discount, Back-order Discount and type, VAT rate, unit USD cost, Platform Markup, Active Supplier and Lead Time (NFR-4)
**And** an Order Quote with a `quote_id` and a fingerprint over its inputs.

**Given** an Integrator Margin
**When** `ApplyIntegratorMargin` runs on a snapshot
**Then** the margin is applied to the VAT-inclusive Selling Price and a margin below zero is refused.

**Given** these formulas
**When** the Pricing test suite runs
**Then** every addendum test vector is a named test.

**Given** the addendum vectors and the other pricing cases
**When** they are stored
**Then** they live in one shared file in the repository that the app's money tests also read from Story 6.5 (test design R-01).

**Given** generated carts of random products, quantities, discounts and rates
**When** a property test prices them
**Then** unit price times quantity always equals the line, the lines always sum to the total, and no pre-VAT price falls below the margin floor.

### Story 2.10: Admin changes a markup and sees what it will do

As an Admin,
I want to see what a markup or floor change will do before I save it,
So that I never move hundreds of prices by accident.

**Requirements:** FR-13; UX-DR17; edge-case row 21

**Acceptance Criteria:**

**Given** an Admin editing a Product Range's markup
**When** they enter a new value
**Then** a preview panel beside the Save button shows the prices that move now, the Products with their own markup that are left alone, a sample price, that existing Orders are unchanged, and any Products that would fall below the margin floor.

**Given** a markup or floor change that would take any Product's pre-VAT price below the floor
**When** the Admin saves
**Then** the save is refused and names the Products.

**Given** a single Product
**When** an Admin sets or clears a markup override
**Then** that Product uses the override, or its range's markup once cleared.

**Given** any markup, override or floor change
**When** it is saved
**Then** a reason is required, the Wholesale Prices affected update at once, and the change appears in the rule's history and the audit trail.

**Given** the Pricing and rules page
**When** an Admin views it
**Then** rules are grouped (markups and the floor, VAT, tolerances, approval amounts, rate warnings) with a markup-by-range table.

### Story 2.11: Stock quantities, thresholds and the ledger

As an Operations officer,
I want each Product's stock tracked through a ledger with a threshold,
So that what the Catalogue says is in stock matches the shelf.

**Requirements:** FR-16, FR-52, FR-74; AD-8; edge-case row 17

**Acceptance Criteria:**

**Given** a Product
**When** its stock is viewed
**Then** it shows on hand, reserved, available (on hand minus reserved), its Stock Threshold and its status.

**Given** a Product whose available quantity is above its threshold
**When** its status is computed
**Then** it is In Stock, and at or below the threshold it is Out of Stock.

**Given** Operations adjusting stock
**When** the adjustment is saved
**Then** a ledger entry is written with a reason, a reference and on-hand-after, and on hand always equals the sum of ledger entries.

**Given** an adjustment larger than the Admin-set sign-off size
**When** Operations submits it
**Then** it waits on the Approvals page for Admin and stock does not change until approved.

**Given** an edit that would put on hand below reserved
**When** Operations submits it
**Then** it is refused unless an Admin overrides with a reason (edge-case row 17).

**Given** the daily ledger check
**When** on hand differs from the ledger sum for any Product
**Then** the founder is alerted with the Product named.

**Given** only `Catalogue` Actions
**When** stock quantities or the ledger change
**Then** no other module has written them (AD-8).

**Given** the Products list
**When** Operations views it
**Then** it gains the filter chips "At or below threshold" and "Out of stock", and each row shows available and on hand.

### Story 2.12: Operations records goods-in

As an Operations officer,
I want to record each stock receipt with its cost and serial numbers,
So that stock, landed cost and the origin of every unit are known.

**Requirements:** FR-65, FR-74; AD-8

**Acceptance Criteria:**

**Given** Operations recording goods-in
**When** they save it
**Then** it holds the Supplier, date, quantity, a batch or lot reference, and landed cost made of invoice cost, freight, duty and clearing
**And** each cost component is entered in USD or NGN with the rate used.

**Given** a saved goods-in record
**When** it is committed
**Then** a receipt ledger entry increases on hand by its quantity, and the record is the batch that later requirements refer to.

**Given** a serialised Product
**When** goods-in is recorded
**Then** serial numbers are captured by scan or pasted list, their count must equal the quantity, and each is normalised for case and separators and stored once in `serial_units` with its Product and batch.

**Given** a serial number already recorded
**When** it is entered again
**Then** it is refused and the duplicate is named.

**Given** a Product that is not serialised
**When** goods-in is recorded
**Then** no serial numbers are asked for.

### Story 2.13: Back-order terms and the discount rule

As an Operations officer,
I want to offer an Out of Stock Product on back-order with a discount and a Lead Time,
So that Integrators can still order it knowing when it will ship.

**Requirements:** FR-16, FR-17, FR-23; edge-case row 20

**Acceptance Criteria:**

**Given** a Product
**When** Operations enables back-order
**Then** a Lead Time is required and a Back-order Discount of up to 10% can be set, stored on that Product and not as a global value.

**Given** a Back-order Discount above 10%
**When** Operations submits it
**Then** it waits on the Approvals page for Admin.

**Given** a Back-order Discount that would take the pre-VAT price below the margin floor
**When** it is submitted
**Then** it is refused.

**Given** an item with a 4% Tier Discount and an 8% Back-order Discount
**When** Pricing computes its price
**Then** it is priced at 8% off Wholesale Price, because the larger applies and they do not stack (FR-23).

**Given** an Out of Stock Product with back-order enabled
**When** its status is shown
**Then** it reads Back-order with its Lead Time.

**Given** safety stock at or below the threshold
**When** Operations releases it to a Back-order Item
**Then** a ledger line is written with a reason.

**Given** the Products list
**When** Operations views it
**Then** it gains the filter chip "Back-order on".

### Story 2.14: Admin switches the Active Supplier, or stops a Supplier

As an Admin,
I want to change which Supplier a Product is priced from, with a second person agreeing,
So that a cost change is deliberate and its effect is seen first.

**Requirements:** FR-14; edge-case row 18

**Acceptance Criteria:**

**Given** an Admin choosing a new Active Supplier for a Product
**When** they submit
**Then** a preview shows the new cost, the new base-tier price and the open Carts and Projects touched
**And** the switch waits on the Approvals page for a second person.

**Given** a Supplier that is not approved, or has no Supplier Cost or a cost of zero or below for that Product
**When** it is chosen
**Then** the switch is refused (edge-case row 18).

**Given** an approved switch
**When** it takes effect
**Then** the Wholesale Price is recalculated from the new Supplier's cost and the change is audited.

**Given** a Product's Active Supplier
**When** anyone tries to remove it without naming a replacement
**Then** it is refused.

**Given** an Admin stopping a Supplier
**When** they open the action
**Then** a preview lists its Products, each needing a replacement Supplier named or a choice to take it off sale.

**Given** the stop is confirmed
**When** it takes effect
**Then** Products with a replacement switch to it, the rest come off sale, and each change is audited.

### Story 2.15: Bulk upload with a dry run

As an Operations officer,
I want to create and update many Products from a spreadsheet and see what it will do first,
So that the launch Catalogue can be loaded in one sitting without surprises.

**Requirements:** FR-15; edge-case rows 15, 16

**Acceptance Criteria:**

**Given** the Bulk upload page
**When** Operations downloads the template
**Then** it has a column for every bulk-editable Product field, with SKU as the match key.

**Given** an uploaded file
**When** the dry run completes
**Then** a report shows the rows that would be created, updated and price-changed, and the failed rows each with a row number and a reason
**And** nothing has been applied.

**Given** a row with a missing or ambiguous SKU, or a SKU that repeats one earlier in the file
**When** the file is checked
**Then** that row fails and no duplicate Product is created (edge-case row 16).

**Given** a file from an Operations account that carries the Platform Markup or Active Supplier columns
**When** it is applied
**Then** those columns are ignored with a note on each row (edge-case row 15).

**Given** a checked file under the price-change thresholds
**When** Operations applies it
**Then** valid rows are applied, failed rows are reported, and every change is audited.

**Given** a launch file of 500 or more rows
**When** it is uploaded
**Then** it imports in a single upload.

### Story 2.16: A bulk price file waits for Admin and can be rolled back

As an Admin,
I want a file that moves many prices to wait for my approval and be reversible,
So that one bad spreadsheet cannot reprice the Catalogue.

**Requirements:** FR-15, FR-52

**Acceptance Criteria:**

**Given** a file that changes prices on more than 20 Products, or moves any Supplier Cost by more than 15%
**When** Operations applies it
**Then** no row applies and the whole file waits on the Approvals page.

**Given** a waiting file
**When** an Admin opens it
**Then** the dry-run report is shown as the preview, and Approve is off for the person who uploaded it.

**Given** an approved file
**When** it applies
**Then** the previous state of every changed row is kept.

**Given** an applied file
**When** an Admin presses rollback
**Then** every row returns to its previous state in one action, and the rollback is audited.

**Given** a declined file
**When** the Admin declines
**Then** a reason is required and is shown to the uploader.

### Story 2.17: The rate goes stale, and Finance can pause orders

As a Finance officer,
I want warnings as the Platform Rate ages and a way to pause new orders,
So that nobody orders at a rate we can no longer honour.

**Requirements:** FR-20; NFR-9; AD-5; test design R-08; TC-2

**Acceptance Criteria:**

**Given** a reconfirmed rate
**When** it is stored
**Then** a UTC deadline 48 calendar hours later is stored on it (AD-5).

**Given** the timer sweep
**When** it runs every minute on one server
**Then** it finds due deadlines and calls the same Actions a person would, each re-reading its deadline under lock and refusing unless it has passed
**And** a missed sweep heartbeat or a failed run alerts the founder.

**Given** the rate's age
**When** it reaches the warning settings (placeholders of 36 and 44 hours)
**Then** the rate card on Finance's Today page turns brass and then red, with the age in words.

**Given** the rate's 48-hour deadline passes without reconfirmation
**When** the sweep acts
**Then** new Order creation is marked stopped, exposed to other modules through one Pricing Query, and it clears when the rate is reconfirmed.

**Given** the Platform Rate page
**When** the stop time falls before the team next opens, as after a Saturday reconfirmation
**Then** a warning says so and gives the exact stop time.

**Given** a Finance or Admin account
**When** it pauses or resumes new orders
**Then** a reason is required, the state is audited, and the same Pricing Query reports it.

**Given** a rate change waiting for a second person
**When** the requester submits it
**Then** they can choose to pause new orders while it waits, off by default.

**Given** a test or staging environment
**When** someone runs the sweep-tick command
**Then** one sweep runs at the Clock's current time, so timers can be exercised without waiting (TC-2).

**Given** any timer Action
**When** it is tested
**Then** it is run just before, exactly at and just after its deadline, and twice in a row, and does the right thing each time (test design R-08).

**Given** two schedulers running at once
**When** the sweep is due
**Then** it runs on one server only.

*Note: nothing creates Orders yet. Epic 7 reads this Query and shows the single "Prices are being updated" message.*

### Story 2.18: Acting as another role, and the founder's weekly review

As the founder,
I want every use of another role's power and every override listed for my review each week,
So that the few people who can bend a rule know it is looked at.

**Requirements:** FR-52, FR-53

**Acceptance Criteria:**

**Given** an Admin about to use a Finance or Operations capability
**When** they start the action
**Then** the console first shows "You are acting as Finance" (or Operations), and the audit row carries `acting_as`.

**Given** the weekly review page
**When** the founder opens it
**Then** it lists that week's acting-as uses, rate overrides above 10% and stock adjustments above the set size, each with who, when and the reason.

**Given** an item on the review
**When** the founder marks it reviewed
**Then** a note can be added and the mark is audited.

**Given** any item not yet reviewed
**When** the founder views the page
**Then** "Sign off the week" is visible, dashed and off, with the count remaining beside it.

**Given** an account without the founder flag
**When** it requests the weekly review
**Then** the server refuses.

*Note: later epics add their own override types to this review (a Payment reversal, a ratified Hold, a serial correction, a second-approver Credit Note, a late counterfeit report).*

## Epic 3: Sign up and browse the Catalogue

A visitor signs up with an email or phone, accepts the Terms, logs in with a one-time code or passkey, and searches and browses the Catalogue and its stock status without prices, on a slow connection.

### Story 3.1: Sign up with a phone or email

As a visitor,
I want to create an account with my phone number or email and a code,
So that I can start browsing the Catalogue.

**Requirements:** FR-1, FR-69; AD-4, AD-18; UX-DR9, UX-DR6

**Acceptance Criteria:**

**Given** a visitor on the sign-up screen
**When** it loads
**Then** it has one field for an email or phone number and an unticked box for the Terms and Privacy Notice, each linked to its text page.

**Given** the Terms box is not ticked
**When** the visitor looks at "Send my code"
**Then** it is visible, dashed and off, with the reason beside it.

**Given** a valid contact and a ticked box
**When** the visitor asks for a code
**Then** a 6-digit code valid for 10 minutes is sent by SMS or email through a provider interface, and the screen says how long the code has left in words.

**Given** the code screen
**When** it is shown
**Then** it has six boxes over one real input, so paste and auto-fill fill all six, with a resend countdown in words that becomes a button.

**Given** a wrong code
**When** it is entered
**Then** the screen says "That code is not right. Check the 6 digits and try again." and keeps what was typed.

**Given** an expired code
**When** it is entered
**Then** the screen says it has expired, that codes last 10 minutes, and offers a new one.

**Given** the right code
**When** it is submitted
**Then** one Action creates the User as Owner of a new Business Account at Level 1 and records the Terms and Privacy acceptance with version, time, User and IP (FR-69)
**And** a signed-in session starts in Sanctum's cookie mode.

**Given** the verification request carries an `Idempotency-Key`
**When** the same request is replayed
**Then** the original result is returned and no second account is created, and the same key with a different payload returns `422` (AD-4).

**Given** a contact that has not been verified
**When** it requests the Catalogue
**Then** the server refuses.

**Given** no Terms version has been published
**When** the system is set up
**Then** a placeholder version is seeded, marked as awaiting counsel.

**Given** a keyboard or screen-reader user
**When** they sign up
**Then** the code boxes are announced as one labelled group, errors are announced at once in words, and the screen works at 200% zoom and 320px wide.

### Story 3.2: Sign-up is protected from abuse

As the founder,
I want sign-up and code requests limited and checked for bots,
So that nobody can flood the service or guess a code.

**Requirements:** FR-1

**Acceptance Criteria:**

**Given** the sign-in limit settings
**When** the system is first set up
**Then** they hold placeholders of 5 wrong codes, 3 resends and a 15-minute lockout, stored as database rows an Admin can change (PRD §8.3 question 6).

**Given** a contact, a device or an IP address that passes a limit
**When** another code is requested or entered
**Then** it is refused, and the screen says when to try again.

**Given** a contact that is already registered and verified
**When** someone tries to sign up with it
**Then** they are sent to log in and no second account is created.

**Given** a contact with a verification still pending
**When** someone tries to sign up with it again
**Then** they continue that verification.

**Given** a sign-up attempt
**When** it is submitted
**Then** an invisible bot check runs through a provider interface, with no puzzle that tests memory or reading.

**Given** a sign-up the bot check refuses
**When** the visitor sees the result
**Then** it is a neutral refusal with the "Call or WhatsApp us" line.

**Given** staging and tests
**When** they run
**Then** the SMS, email and bot-check interfaces are bound to fakes (AD-15).

### Story 3.3: Log in and see where you are logged in

As an Owner,
I want to log in with a code and see every device I am logged in on,
So that I can get back in easily and shut out a device I no longer trust.

**Requirements:** FR-71; AD-18

**Acceptance Criteria:**

**Given** a registered contact
**When** its owner asks to log in
**Then** the same single field and 6-digit code are used as at sign-up.

**Given** a signed-in session
**When** it has been idle for 30 days
**Then** it ends, and the next visit shows the signed-out page.

**Given** the "Sign-in and security" screen
**When** it is opened
**Then** "Where you are logged in" lists each session with its device and when it was last used.

**Given** that list
**When** the person presses "Log out" on one row, or "Log out everywhere else"
**Then** those sessions end at once, because sessions use the database driver (AD-18).

**Given** an Integrator session
**When** it requests any `/console` page
**Then** it is refused.

**Given** a person who presses "Sign out"
**When** it completes
**Then** they see the signed-out page with a way to log in again.

### Story 3.4: The WhatsApp question

As an Owner,
I want to be asked plainly whether I want updates on WhatsApp,
So that nothing is sent to my number without my say-so.

**Requirements:** FR-69

**Acceptance Criteria:**

**Given** a person who has just verified a phone number
**When** sign-up completes
**Then** a separate screen asks whether to send updates to that number on WhatsApp, with nothing preselected.

**Given** that screen
**When** the person chooses yes
**Then** the `Notifications` module records the opt-in with the number, the User and the time.

**Given** that screen
**When** the person chooses "No thanks"
**Then** no opt-in is recorded and the screen says updates stay in the app.

**Given** a person who signed up with an email
**When** sign-up completes
**Then** the question is not shown.

**Given** a recorded opt-in
**When** the person turns the WhatsApp switch off
**Then** the opt-out is recorded at once and the switch says "Off" in words.

**Given** any attempt to send a WhatsApp message
**When** the number has no recorded opt-in
**Then** the `Notifications` module refuses to send it (FR-69).

### Story 3.5: The app before approval

As an Owner who is not yet approved,
I want a simple app with a clear way round and a way to reach help,
So that I can find the Catalogue and my own settings without guessing.

**Requirements:** NFR-6; UX-DR18, UX-DR24, UX-DR27, UX-DR7, UX-DR13, UX-DR20

**Acceptance Criteria:**

**Given** a signed-in Owner whose business is not approved
**When** the app opens
**Then** a bottom tab bar shows two tabs with words, Catalogue and Get approved, with the current one marked by a bar and announced to a screen reader
**And** there is no cart icon.

**Given** the Get approved tab in this epic
**When** it is opened
**Then** it shows "Verify your contact" as Clear, and the rows Sign-in and security, WhatsApp updates, Terms and Privacy, Help and Sign out.

**Given** a screen 1024px wide or more
**When** the app loads
**Then** the tab bar becomes a left rail and content stops at 1100px, centred
**And** between 600 and 1023px the phone layout is centred in a column up to 720px with the tab bar kept.

**Given** any screen of the app
**When** it is scrolled to the end
**Then** the last block is "Call or WhatsApp us" with our hours, 8 am to 6 pm, Monday to Saturday.

**Given** a screen that is loading
**When** less than a second has passed
**Then** nothing is shown, and after that words are shown, never a spinner alone.

**Given** a request that has waited 8 seconds
**When** it is still waiting
**Then** the screen says "Slow connection. Still trying." and keeps trying.

**Given** the phone loses its connection
**When** it happens, and when it returns
**Then** a banner says so and a screen reader announces it.

**Given** a keyboard user
**When** they tab through any screen
**Then** a skip link passes the navigation, the focus ring is never hidden behind the tab bar, and Back returns one screen and keeps what was typed.

### Story 3.6: Browse the Catalogue without prices

As an Owner who is not yet approved,
I want to browse the Catalogue and see what is in stock,
So that I can judge whether the platform carries what I install.

**Requirements:** FR-2, FR-55, FR-73; NFR-7; AD-13; UX-DR42; test design R-03

**Acceptance Criteria:**

**Given** a Level 1 Owner on the Catalogue tab
**When** the list loads
**Then** each row shows name, brand, model, SKU and stock status, with no photo and no price
**And** only active Products that are not quarantined are listed.

**Given** the list
**When** it is first shown
**Then** one line reads "Prices are shown once your business is approved." and links to the Get approved tab.

**Given** more than 20 Products
**When** the person scrolls
**Then** the list loads 20 at a time.

**Given** an Out of Stock Product with back-order enabled
**When** its row is shown
**Then** it reads Back-order with its Lead Time, and its Back-order Discount is not sent.

**Given** the field-visibility registry in `Shared`
**When** a Level 1 session requests any Catalogue response
**Then** the response holds no price, discount, markup or cost field (FR-2, AD-13)
**And** a test suite that runs every Level 1 surface against the registry fails if one appears.

**Given** a Level 1 session
**When** it sends a sort or filter parameter that depends on price
**Then** the server refuses it.

**Given** the low-end test phone on slow 3G (400 kbps, 400 ms)
**When** the Catalogue list is opened
**Then** it is usable within about 5 seconds (FR-55, NFR-7).

**Given** a Catalogue with no active Products
**When** the list loads
**Then** it says why it is empty.

**Given** a screen-reader user
**When** they reach the list
**Then** it is announced as a list, each row is one target at least 56px high, and stock status is in words.

**Given** a new API resource or sync payload
**When** it is added without an entry in the field-visibility registry
**Then** CI fails and names it (test design R-03).

### Story 3.7: Search, filter and sort

As an Owner,
I want to search the Catalogue by what I know of a product and narrow the list,
So that I find a part in seconds.

**Requirements:** FR-73; AD-17

**Acceptance Criteria:**

**Given** the search box
**When** a name, brand, model or SKU is typed, including with a small typing error
**Then** matching Products are returned using PostgreSQL `pg_trgm` (AD-17).

**Given** a search for an exact model number or SKU
**When** results are shown
**Then** that Product is first (FR-73).

**Given** the Catalogue
**When** the person opens the categories
**Then** a tree built from Product Ranges is shown.

**Given** the filters
**When** the person chooses a brand, a Product Range or a stock status
**Then** the list narrows, the active filters are shown in words, and one control clears them.

**Given** the sort control for a Level 1 session
**When** it is opened
**Then** it offers sorting by name only, and no price sort exists for that session.

**Given** a search or filter with no matches
**When** the result is shown
**Then** it says nothing matched and offers one next step.

**Given** a keyboard user
**When** they search and filter
**Then** every control is reachable and the number of results is announced.

### Story 3.8: The Product page

As an Owner,
I want to open a product and see its details and availability,
So that I know exactly what it is before I plan a job around it.

**Requirements:** FR-2, FR-49, FR-73; AD-14

**Acceptance Criteria:**

**Given** a Product row
**When** it is tapped
**Then** the Product page shows one photo, the name, brand, model and SKU, specifications, and the stock status with Lead Time for a Back-order Item.

**Given** the photo
**When** the page loads
**Then** the 320px version loads first and a larger one after, from an authenticated route that the browser can cache long-term (AD-14)
**And** the image is named for the Product.

**Given** the phone's Data Saver is on
**When** the page loads
**Then** the picture is held until tapped.

**Given** a Level 1 session
**When** the page is shown
**Then** the price area is replaced by "Prices are shown once your business is approved." and no add controls are drawn.

**Given** a Product with no Documentation
**When** its guides area is shown
**Then** it says "Documentation coming soon." and "Need it now? Call or WhatsApp us."

**Given** a Product that has been discontinued or quarantined since the list loaded
**When** its page is opened
**Then** the page says it is no longer available.

### Story 3.9: Passkeys and the fresh code

As an Owner,
I want to log in with a passkey and be asked for a fresh code before a sensitive change,
So that logging in is quick and nobody who picks up my phone can change my security.

**Requirements:** FR-71; AD-18; UX-DR12

**Acceptance Criteria:**

**Given** a phone or browser that supports passkeys
**When** the person opens "Sign-in and security"
**Then** adding a passkey is offered, and where it is not supported the row is not shown.

**Given** a person adding or removing a passkey
**When** they start
**Then** a bottom sheet (a centred dialog on a laptop) asks for a fresh 6-digit code sent to a contact on record, shown partly hidden
**And** the code is valid for 10 minutes and for that one action.

**Given** the right fresh code
**When** it is submitted with the action
**Then** Identity's `StepUpGrant` is consumed inside the same transaction as the change, and the change finishes in the same step (AD-18).

**Given** a fresh code issued for one action
**When** it is presented for a different action or a second time
**Then** it is refused.

**Given** a registered passkey
**When** the person logs in
**Then** they can use it in place of a code, and the one-time code is always still available.

**Given** the sheet
**When** it opens and closes
**Then** it takes the focus, closes with Esc or Cancel, and gives the focus back.

### Story 3.10: Change your phone or email

As an Owner,
I want to change the phone number or email I log in with,
So that I keep access when my contact details change.

**Requirements:** FR-71

**Acceptance Criteria:**

**Given** an Owner on "Sign-in and security"
**When** they start to change a contact
**Then** a fresh code sent to a contact already on record is required first.

**Given** the fresh code is accepted
**When** the Owner enters the new contact
**Then** a code is sent to it, and the change applies only once that code is verified.

**Given** a new contact that already belongs to another account
**When** it is entered
**Then** the change is refused.

**Given** a completed change
**When** it applies
**Then** the previous contact is told, and an audit row records that the contact changed without holding either value.

**Given** an abandoned change
**When** the new contact is never verified
**Then** the old contact keeps working.

### Story 3.11: An Owner gets back in after losing their contact

As an Owner who has lost my phone or email,
I want a way back into my account that checks it is really me,
So that I am not locked out and nobody can take the account from me.

**Requirements:** FR-71; edge-case row 6

**Acceptance Criteria:**

**Given** a person on the log-in screen
**When** they choose "I cannot get my code"
**Then** a form asks for the business name, their full name and a phone number to call.

**Given** a submitted request
**When** the person waits
**Then** a waiting screen shows four steps: request received, our team calls to check ID, a 72-hour wait that starts when the check passes, and log in with the new contact.

**Given** an Admin viewing recovery requests in the console
**When** they record that the ID check by phone passed, with a staff-only note
**Then** a UTC deadline 72 hours later is stored on the request and the change is audited.

**Given** the check has passed
**When** the wait starts
**Then** every contact on file is told at once, with a "call us if this was not you" line and a cancel link (FR-71).

**Given** the cancel link
**When** it is followed, or an Admin cancels the request
**Then** the request ends and access does not change.

**Given** the 72-hour deadline passes
**When** the sweep acts
**Then** the new contact can verify and log in without further staff action, and every other session of that Owner is revoked.

**Given** a failed ID check
**When** the Admin records it
**Then** the request is closed with a reason and access does not change.

**Given** a Manager or Technician who has lost their contact
**When** they choose "I cannot get my code"
**Then** they are told to ask the Owner to remove and re-invite them, and no recovery request is created (FR-71).

**Given** a Finance or Operations account
**When** it requests the recovery list
**Then** the server refuses.

*Note: recovery requests are a plain list in this story; they have no queue or target in the PRD (§8.3 question 8).*

## Epic 4: Get approved and see prices

An Owner confirms who they are with a NIN, redeems a Vouch Code from an Established Integrator, and is approved by Admin, after which Selling Prices appear; Admin can also seed the first businesses directly.

### Story 4.1: Confirm who you are

As an Owner,
I want to see the steps to approval and give my identity details safely,
So that I know what is left to do and trust that my NIN is protected.

**Requirements:** FR-3, FR-4; NFR-3; AD-16; UX-DR41, UX-DR8

**Acceptance Criteria:**

**Given** an Owner who is not yet approved
**When** they open the Get approved tab
**Then** a four-step checklist shows: verify your contact, confirm who you are, enter a vouch code, and we review and approve within 1 business day
**And** each step says Clear, Needs action or Waiting in words, and nothing on the screen says Level 1, 2 or 3.

**Given** the "Confirm who you are" form
**When** it is shown
**Then** name, date of birth, address and NIN are needed and a business address is optional, each said in words.

**Given** the NIN field
**When** the Owner has typed the number
**Then** it is masked with a Show control, a line says only the verification team sees it, and it is spelled out once as National Identification Number (NIN)
**And** the NIN never appears in a link, a page title or the browser's autofill.

**Given** a submitted profile
**When** the NIN is stored
**Then** the full NIN is encrypted with its own data key wrapped by a dedicated master key that is not `APP_KEY`, a keyed hash made with a separate HMAC key is stored for uniqueness, and the last four digits are stored (AD-16)
**And** the NIN is absent from logs, exception reports, queue payloads, outbox payloads and exports.

**Given** a submitted profile
**When** the Owner returns to the checklist
**Then** step 2 reads Waiting and says they need do nothing.

**Given** a failed submission
**When** the form is shown again
**Then** everything typed is kept except that the NIN stays masked.

**Given** a keyboard or screen-reader user
**When** they complete the form
**Then** the checklist is announced as an ordered list, every field has a visible label and the right keyboard, and errors are listed at the top and beside each field.

### Story 4.2: The automatic NIN check

As an Owner,
I want my NIN checked automatically where possible,
So that I am not kept waiting for a person when my details simply match.

**Requirements:** FR-3, FR-4; NFR-3; edge-case rows 2, 3

**Acceptance Criteria:**

**Given** a submitted profile on an account with no earlier definitive mismatch
**When** the check runs through the NIN provider interface
**Then** it passes only when the NIN exists, the registered name matches the profile name once normalised, and the date of birth matches.

**Given** a pass
**When** it is recorded
**Then** the Business Account becomes Level 2, the provider's verification reference is stored, the full NIN's wrapped key is deleted, and step 2 reads Clear.

**Given** a provider timeout or error
**When** it happens
**Then** the attempt is not used up, the check is retried with backoff up to 3 times over 24 hours, and the Owner reads "We could not reach the NIN service. We will keep trying for up to 24 hours and tell you the result. You can close the app."

**Given** the retries run out
**When** 24 hours have passed
**Then** the profile goes to manual review and the Owner is told.

**Given** a definitive mismatch
**When** it is returned
**Then** the profile goes to manual review, no further automatic attempt is ever made for that account, and the Owner reads that it was sent to our team.

**Given** a NIN whose keyed hash already belongs to another Business Account
**When** the profile is submitted
**Then** it goes to manual review flagged as a duplicate, enforced by a unique index (edge-case row 2).

**Given** NIN lookups
**When** they pass a limit per contact, device or IP
**Then** they are throttled, and an alert reaches the founder when the day's verification spend passes its budget.

**Given** a Level 2 session
**When** it requests any response
**Then** no money field is returned and no Project, Cart, Proposal or Order can be created (FR-3).

### Story 4.3: Business Hours and the closed-day calendar

As an Admin,
I want to keep a calendar of closed days that every "by" time is worked out from,
So that the promises we make to businesses and to ourselves are honest about when we are open.

**Requirements:** FR-76, FR-78; AD-5

**Acceptance Criteria:**

**Given** an Admin on the Staff page
**When** they add or remove a closed day
**Then** the calendar is saved and audited.

**Given** Business Hours of 8 am to 6 pm, Monday to Saturday, West Africa Time
**When** `Shared` computes a deadline of 2 Business Hours from 5 pm on a Saturday
**Then** the result is 9 am on Monday, or the next open day if Monday is closed.

**Given** open Business-Hours deadlines
**When** the calendar changes
**Then** every open one is recomputed.

**Given** any deadline sent by the API
**When** a client receives it
**Then** it is a server-computed UTC `*_at` value, and the app does no Business-Hours arithmetic of its own (AD-5).

**Given** the "Call or WhatsApp us" block in the app
**When** it is shown
**Then** it says "Open until 6 pm" or "Opens at 8 am on Monday", read from the calendar.

**Given** the console top bar
**When** it is shown
**Then** it shows the time the team is open until.

**Given** the Platform Rate page from Story 2.17
**When** it works out whether the stop time falls before the team opens
**Then** it honours closed days.

### Story 4.4: The NIN reviews queue

As an Admin,
I want profiles awaiting a manual check listed in a queue with a target,
So that nobody waits unseen for a decision on their identity.

**Requirements:** FR-78; NFR-2; AD-24; UX-DR29

**Acceptance Criteria:**

**Given** a profile sent to manual review
**When** that state change commits
**Then** a work item is opened in the same transaction through a `Staff` Action, with its queue, subject, owner, `due_at` of 1 Business Day and `escalate_at` at 75% of that (AD-24).

**Given** the NIN reviews queue
**When** an Admin opens it
**Then** items are listed oldest first with a count, the age of the oldest on a meter against its target with the figure in words, the number over target, the on-time rate and the queue's owner.

**Given** a new item
**When** it arrives
**Then** the queue's owner is alerted by email.

**Given** an item reaches 75% of its target
**When** the sweep acts
**Then** it escalates to Admin and an escalation notice appears on Admin's Today page naming the owner.

**Given** an item past its target
**When** it is listed
**Then** its row is tinted with a red bar and says "Target exceeded" in words.

**Given** time outside Business Hours
**When** an item's age is counted
**Then** that time does not count against the target.

**Given** an Admin who has an item open
**When** another Admin views it
**Then** it shows "Being reviewed by [name and initial] since [time]", and taking it over needs a reason.

**Given** the queue
**When** a keyboard user works in it
**Then** J, K, Enter and / move and open, the list has a preview panel beside it and a full page behind it, and an empty queue says why it is empty.

**Given** Admin's Today page
**When** it loads
**Then** it shows a card for this queue with its count and oldest age.

### Story 4.5: Admin reviews a NIN by hand

As an Admin,
I want to check an identity by hand without exposing the NIN more than I must,
So that honest people get verified and every look at a NIN is on record.

**Requirements:** FR-4, FR-53; NFR-3; AD-16; edge-case row 4; test design R-07

**Acceptance Criteria:**

**Given** an Admin opening a manual review
**When** the screen loads
**Then** it shows what the Owner sent and what the automatic check said, with the NIN masked to its last four digits.

**Given** the masked NIN
**When** the Admin picks a reason code (checking by hand, checking for a duplicate, a dispute, or other with a note) and presses Show
**Then** the full NIN is shown through an audited Action, and the audit row records who, when and the reason code (FR-53).

**Given** the review
**When** the Admin decides
**Then** the outcomes are Verified by hand, Not verified with a reason, or Duplicate, each with a required reason kept as a public reason and a separate staff-only note.

**Given** Verified by hand
**When** it is saved
**Then** the Business Account becomes Level 2, the work item closes in the same transaction, and a deadline 30 days later is stored for deleting the full NIN.

**Given** Not verified
**When** the Owner opens Get approved
**Then** they see the reason and can resubmit, up to 3 times, after which they are asked to call or WhatsApp us (edge-case row 4).

**Given** an Admin who presses "Ask for a photo of the NIN slip or ID card"
**When** the Owner next opens Get approved
**Then** an optional step to send a photo appears, uploaded through the staged pipeline and stored as private evidence served only by short-lived signed links.

**Given** a person with no NIN who has contacted us
**When** an Admin records an off-app check with a passport or residence permit and the business's CAC registration number
**Then** the profile is verified with that record, and there is no in-app route for the Owner (FR-4).

**Given** the 30-day deadline after a decision
**When** the sweep acts
**Then** the NIN's wrapped data key is deleted, which removes the full NIN from every backup as well (NFR-3).

**Given** one staff member reveals more NINs in a day than the alert threshold (a placeholder setting)
**When** the threshold is passed
**Then** the founder is alerted (PRD §8.3 question 13).

**Given** a canary NIN driven through sign-up, the automatic check, manual review, reveal and export
**When** the logs, exception reports, queue payloads, outbox payloads and exports are searched
**Then** the NIN appears in none of them (test design R-07).

**Given** a backup taken before a NIN's 30-day deletion
**When** it is restored into a clean environment after the deletion has run
**Then** the NIN cannot be decrypted.

### Story 4.6: Deputies and out of office

As a member of staff,
I want a named deputy who takes my queue when I am away,
So that work keeps moving on a three-person team.

**Requirements:** FR-76; AD-24; edge-case row 14

**Acceptance Criteria:**

**Given** the Staff page
**When** an Admin edits a staff account
**Then** a named deputy can be set, and each queue shows its owner and that deputy.

**Given** a staff member who turns the out-of-office switch on
**When** it is saved
**Then** their open and new queue items move to the deputy within a minute, by changing only the work-items table.

**Given** a deputy acting on a moved item
**When** the Action writes its audit row
**Then** the row carries `acting_for`, and the console marks the action "acting for".

**Given** the switch is turned off
**When** new items arrive
**Then** they go to the owner again.

**Given** a staff account is deactivated
**When** the deactivation commits
**Then** that person's open queue items are reassigned to their deputy.

**Given** an escalation notice
**When** it is shown on Admin's Today page
**Then** it names both the owner and the deputy.

**Given** the rail
**When** a staff member views it
**Then** a note names their deputy and shows the out-of-office switch with the word On or Off.

### Story 4.7: Admin creates a business and issues a platform Vouch Code

As an Admin,
I want to set up the first businesses myself and issue Vouch Codes on the platform's behalf,
So that the launch cohort can start before any Established Integrator exists.

**Requirements:** FR-8, FR-52; AD-23

**Acceptance Criteria:**

**Given** an Admin on "Create a business"
**When** they enter the Owner's name as on the NIN, a phone or email, an optional business name, how the link is sent and a staff-only note
**Then** a Business Account is created as Pending Activation, the creating Admin is recorded against it, and the change is audited.

**Given** the invited Owner
**When** they follow the link and verify the contact
**Then** they see "Finish setting up your business" with the same steps as Get approved, without a vouch code: confirm who you are, give the Trade Name and accept the Terms.

**Given** a Pending Activation Owner who has passed NIN verification, supplied the Trade Name and accepted the Terms
**When** the last step completes
**Then** the Business Account becomes Level 3 by direct Admin grant, and the audit trail names the Admin who created it.

**Given** a Pending Activation account
**When** another module asks whether it may create an Order
**Then** one Identity Query answers no (FR-8).

**Given** the Pending Activation list
**When** an Admin views it
**Then** each business shows its four-step progress.

**Given** an Admin issuing a platform Vouch Code
**When** they enter a named prospect and their phone or email
**Then** a code is created that works once, for 48 hours, and locks after five wrong tries.

**Given** any business
**When** a segregation check is needed later
**Then** `StaffRelationshipQuery` can report which staff member created it (AD-23).

**Given** a Finance or Operations account
**When** it tries to create a business or a platform code
**Then** the server refuses.

### Story 4.8: Ask for approval with a Vouch Code

As an Owner whose identity is confirmed,
I want to enter my Vouch Code and ask for approval,
So that my business can be reviewed and unlocked.

**Requirements:** FR-5, FR-6, FR-69; NFR-10; edge-case row 1

**Acceptance Criteria:**

**Given** a Level 2 Owner on "Ask for approval"
**When** the screen loads
**Then** it asks for the vouch code and the trade name, shows the key points of the Terms in plain words with an unticked box, and has a "No code? Call or WhatsApp us" line.

**Given** a valid, unused, unexpired code whose named prospect matches the Owner's NIN-verified name
**When** the Owner submits with the box ticked
**Then** the code is consumed, the Terms acceptance is recorded with version, time, User and IP, and a Level 3 request is created for review.

**Given** two sessions submitting the same code at the same moment
**When** both arrive
**Then** the code is consumed exactly once and the other receives the current state (edge-case row 1, NFR-10).

**Given** a wrong code
**When** it is submitted
**Then** the screen says it is not right, and after 5 wrong attempts the code is locked.

**Given** an expired code
**When** it is submitted
**Then** the screen says "This code has expired. Codes work for 48 hours. Ask the person who gave it to you for a new one."

**Given** a used or locked code, or one made for a different person
**When** it is submitted
**Then** it is refused with a reason in words.

**Given** an accepted request
**When** the Owner returns to the checklist
**Then** step 4 says we will decide by an exact time, worked out by the server from Business Hours.

**Given** a Level 1 Owner
**When** they open "Ask for approval"
**Then** the button is visible, dashed and off, with "Confirm who you are first" beside it.

### Story 4.9: Admin reviews a Level 3 request

As an Admin,
I want to see who is asking and who vouches before I approve a business,
So that only real integrators reach prices and ordering.

**Requirements:** FR-7, FR-78

**Acceptance Criteria:**

**Given** a new Level 3 request
**When** it is created
**Then** it joins a Level 3 requests queue with a 1 Business Day target, built on the same work items, alerts and escalation as Story 4.4.

**Given** an Admin opening a request
**When** the review loads
**Then** it shows who is asking (trade name, Owner, NIN verified, Terms version and time, duplicate checks and any earlier rejection with its reason)
**And** who vouches (the business, the person, whether established, vouching rights, how many of 6 in 90 days, earlier vouchees and the code used), or that a platform code was issued and by whom.

**Given** the three checks (online presence, proof of an installed job, a phone call)
**When** an Admin ticks one
**Then** it records who, when and a note.

**Given** any decision
**When** the Admin submits it
**Then** a reason is required, approvals included.

**Given** an approval
**When** it commits
**Then** the Business Account becomes Level 3, the approval record names the voucher and the approving Admin, and the work item closes in the same transaction.

**Given** a rejection
**When** the Owner opens Get approved
**Then** they see the reason and can ask again with a new Vouch Code.

**Given** two Admins deciding the same request at once
**When** both submit
**Then** the first valid decision wins and the other sees who decided and when.

**Given** every path to Level 3
**When** the test suite runs
**Then** no account reaches Level 3 without an Admin approval or a direct Admin grant (FR-7).

### Story 4.10: Approved: prices appear

As an approved Owner,
I want to see what each product costs me,
So that I can start planning jobs against real prices.

**Requirements:** FR-11, FR-24, FR-73; AD-13

**Acceptance Criteria:**

**Given** a business that has just been approved
**When** the Owner next opens the app
**Then** a welcome shows three next steps with no tour, and the Get approved tab is replaced by Account.

**Given** a Level 3 Owner on the Catalogue
**When** the list loads
**Then** each row shows its Selling Price in naira with commas and two decimals, and "VAT included" stands beside the first price on the screen.

**Given** a Level 3 Owner on a Product page
**When** it loads
**Then** the price reads "each, VAT included", computed by Pricing at the Business Account's current Loyalty Tier (FR-24).

**Given** a Level 3 Owner
**When** they open the sort control
**Then** sorting by price is offered.

**Given** any response to a business
**When** it is inspected
**Then** it never carries Supplier Cost, Platform Markup, Wholesale Price or a USD amount (AD-13).

**Given** a price-bearing endpoint
**When** an account or an IP address passes its rate limit
**Then** further requests are refused for a time, and no price export or bulk price listing exists (FR-73).

**Given** the Platform Rate changes
**When** the Owner next loads a price
**Then** the new price is shown.

**Given** a Level 1 or Level 2 session
**When** the registry test suite from Story 3.6 runs
**Then** it still finds no money field on any surface.

*Note: after approval the tab bar shows only the tabs that exist so far, Catalogue and Account. Projects, Orders and Home appear as Epics 6 and 7 build them. Prices are at the Base tier until Epic 14.*

### Story 4.11: An Owner makes a Vouch Code

As the Owner of an Established Integrator,
I want to make a Vouch Code for someone I trust,
So that a fellow integrator can join on my word.

**Requirements:** FR-5, FR-71; edge-case rows 1, 13

**Acceptance Criteria:**

**Given** an Owner on Account, Vouch codes
**When** the screen loads
**Then** the limits come first: codes waiting out of 2, and people vouched for in the last 90 days out of 6.

**Given** a business that is not an Established Integrator
**When** the Owner opens Vouch codes
**Then** "Make a vouch code" is visible, dashed and off, with the reason beside it.

**Given** an established Owner making a code
**When** they enter the person's full name as on their NIN and the phone or email they signed up with
**Then** a note explains that if that person is later suspended for fraud the Owner's own vouching can be paused
**And** a fresh one-time code is asked for in a bottom sheet before the Vouch Code is made (FR-71).

**Given** the fresh code is accepted
**When** the Vouch Code is made
**Then** it is at least 10 random characters shown in groups of four capitals with no look-alike letters, bound to that prospect, single-use and valid for 48 hours
**And** it records the issuing Business Account and User.

**Given** a made code
**When** it is shown
**Then** it is large, in the monospace face, with "Share on WhatsApp" first, Copy (which says "Copied" for two seconds), its last hour of validity, and three next steps for the prospect.

**Given** a business with 2 active codes, or 6 prospects in the rolling 90 days
**When** the Owner tries to make another
**Then** it is refused with the reason, and the count is taken under a lock so two requests cannot both pass (edge-case row 1).

**Given** earlier codes
**When** they are listed
**Then** waiting codes can be shown or shared again in full, and ended codes show only their last 4 characters and how they ended (Used, Expired, Cancelled by us), never whether the person was approved.

*Note: until Epic 9, a business is an Established Integrator only by Admin grant (Story 4.12).*

### Story 4.12: Admin pauses or grants vouching rights

As an Admin,
I want to pause, restore or grant a business's right to vouch,
So that a voucher who proves unreliable cannot bring in more accounts.

**Requirements:** FR-7, FR-9; edge-case row 12

**Acceptance Criteria:**

**Given** an Admin on a business account
**When** they pause its vouching rights with a reason
**Then** the business cannot make a Vouch Code and its active codes stop working at once (FR-9).

**Given** paused rights
**When** an Admin restores them with a reason
**Then** the business can make codes again; voided codes stay void.

**Given** a business that is not yet established
**When** an Admin grants vouching rights with a reason
**Then** it is treated as an Established Integrator.

**Given** a Level 3 request whose voucher was paused after the code was submitted
**When** the applicant opens Get approved
**Then** they are asked to confirm again with an explicit tick
**And** the review screen flags it, and Approve needs the Admin's own explicit tick (edge-case row 12).

**Given** an Owner whose vouching is paused
**When** they open Vouch codes
**Then** the screen says vouching is paused and offers "Call or WhatsApp us", without naming a fraud review.

**Given** each of these changes
**When** it is saved
**Then** it is audited.

### Story 4.13: Admin publishes a new Terms version

As an Admin,
I want to publish a new version of the Terms with a plain list of what changed,
So that every business agrees to the terms in force before it orders again.

**Requirements:** FR-69

**Acceptance Criteria:**

**Given** an Admin on Terms and policies
**When** they prepare a version
**Then** they provide the documents, a required what-changed list of short lines, and counsel's approval date and reference
**And** Publish is visible, dashed and off until counsel's approval is entered.

**Given** a prepared version
**When** the Admin previews it
**Then** they see the exact screen an Owner will see.

**Given** a published version
**When** an Owner next opens the app
**Then** a "New Terms" screen is shown once with the what-changed list, a tick to accept and "Not now".

**Given** an Owner who accepts
**When** it is saved
**Then** the acceptance is recorded with version, time, User and IP.

**Given** an Owner who has not accepted the current version
**When** another module asks whether the business may create an Order
**Then** one Identity Query answers no, and nothing else in the app is blocked (FR-69).

**Given** a published version
**When** the Admin views it
**Then** acceptance progress is shown, and the version can be withdrawn only before its first acceptance.

**Given** a new sign-up
**When** the Terms box is ticked
**Then** the acceptance records the version current at that moment, which replaces the placeholder from Story 3.1.

## Epic 5: Teams, business profile and account control

An Owner invites Managers and Technicians, each seeing only what their role allows; the business keeps its profile and logo; and Admin can hold, revoke, block or close an account.

### Story 5.1: The business profile

As an Owner,
I want to keep my business's name, logo and tax details in one place,
So that my Proposals and invoices carry the right identity.

**Requirements:** FR-70

**Acceptance Criteria:**

**Given** an Owner or Manager on Account, Business profile
**When** the screen loads
**Then** it holds the trade name (filled from the approval request), a logo, address, phone, email, an optional Tax Identification Number (TIN) and a declaration of whether the business is VAT-registered.

**Given** a logo upload
**When** the file is a PNG or JPG of up to 2 MB
**Then** it is accepted through the staged upload pipeline, and any other file is refused in a sentence before it is sent.

**Given** a business with no logo
**When** another module asks for its header
**Then** a name-only header is returned.

**Given** a business that declares itself VAT-registered without a TIN
**When** it saves
**Then** the declaration is accepted and the screen prompts for a TIN (FR-70).

**Given** the profile
**When** other modules need it
**Then** Identity Queries report whether a Trade Name is set and whether the business is VAT-registered.

**Given** a staff member with the capability
**When** they mark a TIN as verified in the console
**Then** the mark is saved and audited.

**Given** any profile change
**When** it is saved
**Then** it is audited, with personal-data fields recorded as changed and not by value.

### Story 5.2: The Owner invites a Manager or Technician

As an Owner,
I want to invite the people I work with,
So that my office and my technicians can use the platform under my business.

**Requirements:** FR-10, FR-71

**Acceptance Criteria:**

**Given** a Level 3 Owner on Account, Team
**When** the screen loads
**Then** it lists people by role and then invitations still waiting, each with its last day.

**Given** the Owner inviting someone
**When** they enter a phone or email, an optional name and a role
**Then** the role is chosen by what the person can do, in plain words.

**Given** an invitation to a Manager
**When** the Owner sends it
**Then** a fresh one-time code is required first.

**Given** any invitation
**When** the Owner presses send
**Then** a confirmation names the contact and the role before anything is sent.

**Given** a sent invitation
**When** it is created
**Then** it is bound to that contact, a deadline 7 days later is stored, and the link is sent by SMS or email.

**Given** a waiting invitation
**When** the Owner cancels it
**Then** its link stops working at once.

**Given** a business that is not Level 3
**When** its Owner opens Account
**Then** the Team row is not shown.

### Story 5.3: Accept an invitation

As an invited Manager or Technician,
I want to join my employer's business from a link,
So that I can start work without proving my identity the way an Owner must.

**Requirements:** FR-10, FR-69; edge-case row 11

**Acceptance Criteria:**

**Given** an invited person
**When** they open the link
**Then** the contact is locked to the one invited, and they verify it with a 6-digit code.

**Given** a verified invitee at first login
**When** they continue
**Then** they accept the Terms and Privacy Notice, recorded with version, time, User and IP, and are not asked for NIN verification (FR-10).

**Given** an accepted invitation
**When** it completes
**Then** the User joins the Business Account in the invited role, and a three-line welcome is shown once.

**Given** a link that has expired or been cancelled
**When** it is opened
**Then** the screen says which, and to ask the Owner for a new one.

**Given** a contact that already belongs to another Business Account
**When** it tries to accept
**Then** it is refused with the reason.

**Given** a person using a different contact from the one invited
**When** they try to accept
**Then** it is refused, because the token is bound to the invited contact (edge-case row 11).

**Given** an invitee who verified a phone number
**When** they finish
**Then** the WhatsApp question from Story 3.4 is asked.

### Story 5.4: Each role sees only what it may

As an Owner,
I want Managers and Technicians to see only what their role allows,
So that my prices and margins stay with the people I trust with them.

**Requirements:** FR-11; AD-13; UX-DR38, UX-DR46

**Acceptance Criteria:**

**Given** the FR-11 permission matrix
**When** it is implemented
**Then** it is enforced on the server for every request, not only in the interface.

**Given** a Manager session
**When** it browses the Catalogue
**Then** it sees Selling Prices and can sort by price, as an Owner can.

**Given** a Technician session
**When** it requests any response
**Then** the response contains no amount, price, discount, margin, total, balance or bank detail, and no ordering derived from price.

**Given** the Technician-safe response schema
**When** it is published
**Then** it lists the only fields a Technician response may contain
**And** an automated test fails when any money field, or any price-derived ordering, is reachable by a Technician, Level 1 or Level 2 session.

**Given** that test suite
**When** CI runs on any later change
**Then** it runs, and each later epic adds its own surfaces to it.

**Given** a screen for a role that may not see money
**When** it is drawn
**Then** the layout has no money area at all: no dash, no blank and no padlock.

**Given** the Account home
**When** each role opens it
**Then** an Owner sees every group, a Manager sees the profile and a team list limited to Technicians, and a Technician sees only the You rows
**And** a row a role cannot use is not shown.

**Given** a person who follows a link to something their role cannot do
**When** it loads
**Then** it names who can, such as "This is for the Owner and Managers. Ask Tunde.", and never says "Access denied".

### Story 5.5: Change a role or remove a person

As an Owner,
I want to change someone's role or remove them,
So that access always matches who works for me today.

**Requirements:** FR-10, FR-71; edge-case row 9

**Acceptance Criteria:**

**Given** an Owner on Team
**When** they change a person's role
**Then** a fresh one-time code is required, and the change is audited.

**Given** a Manager on Team
**When** they view it
**Then** they can invite and remove Technicians only.

**Given** a Manager
**When** they try to remove or demote the Owner or another Manager
**Then** the server refuses.

**Given** a Technician
**When** they try to invite or remove anyone
**Then** the server refuses, and the Team row is not shown to them.

**Given** someone removing a person
**When** the dialog opens
**Then** its title is a question naming the person and its body lists what will happen.

**Given** a removal or a role change
**When** it commits
**Then** every session of that User is revoked at once, and a removed User receives no further messages.

*Note: clearing that person's saved work from their phone is added in Epic 6, when the phone first holds any.*

### Story 5.6: A Manager asks the Owner for a Vouch Code

As a Manager,
I want to ask the Owner to vouch for someone I know,
So that I can bring in a good integrator without holding the Owner's powers.

**Requirements:** FR-5

**Acceptance Criteria:**

**Given** a Manager on Account
**When** they open Vouch codes
**Then** they see "Ask the Owner" and not "Make a vouch code".

**Given** a Manager's request
**When** they enter the person's name and contact and send it
**Then** the Owner sees it in Vouch codes with the form pre-filled.

**Given** the Owner opens the request
**When** they confirm with a fresh one-time code
**Then** the Vouch Code is made exactly as in Story 4.11 and counts toward the same limits.

**Given** the Owner makes the code or declines the request
**When** the Manager next opens Vouch codes
**Then** they are told the outcome.

**Given** a Technician
**When** they open Account
**Then** the Vouch codes row is not shown.

### Story 5.7: Change the Owner

As an Owner,
I want to hand my business to another person safely,
So that ownership can change without anyone taking it by trickery.

**Requirements:** FR-71; edge-case rows 5, 6

**Acceptance Criteria:**

**Given** an Owner on Sign-in and security
**When** they start a change of Owner
**Then** a fresh one-time code is required.

**Given** a started change
**When** the Owner names the incoming person
**Then** that person must verify a contact and pass NIN verification, through the flow of Stories 4.1 to 4.5, before the transfer completes.

**Given** an incoming person without a verified NIN
**When** the transfer is attempted
**Then** it is refused (FR-71).

**Given** a completed transfer
**When** it commits
**Then** the Business Account has exactly one Owner, every contact on record is told, and the change is audited.

**Given** a change of Owner begun through Admin-assisted recovery
**When** it completes
**Then** the same NIN rule applies.

*Assumption: the outgoing Owner becomes a Manager unless they choose to leave; the PRD does not say.*

### Story 5.8: Put a business on hold

As an Admin,
I want to put a business on hold while something is checked,
So that a doubtful account cannot order or be paid a refund in the meantime.

**Requirements:** FR-72; UX-DR49

**Acceptance Criteria:**

**Given** an Admin in the console
**When** they search by business, owner, phone or email
**Then** the business account page opens with its facts, flags and tools, and every tool needs a reason.

**Given** an Admin pressing "Put on hold…"
**When** the dialog opens
**Then** it says what a Hold does (no new orders, no refunds, payments for orders already made still processed), and asks whether to tell the Owner or mark a fraud review.

**Given** a confirmed Hold
**When** it commits
**Then** the business is on Hold, one Identity Query reports it to other modules, and the change is audited with a public reason and a separate staff-only note.

**Given** a Hold marked as a fraud review
**When** it commits
**Then** the Owner is not told the reason, and the Hold is listed in the founder's weekly review.

**Given** a Finance officer placing a Hold for payment fraud
**When** it commits
**Then** it takes effect at once and a work item asks Admin to ratify or lift it within 1 Business Day, shown on Admin's Today page.

**Given** a business on Hold
**When** anyone in it opens the app
**Then** an account-wide banner stands at the very top of every screen until the Hold is lifted, using placeholder wording until counsel approves it.

**Given** the Holds page
**When** an Admin views it
**Then** each Hold shows who started it, the staff-only reason and whether the business was told.

### Story 5.9: Revoke approval or disable a person

As an Admin,
I want to withdraw a business's approval or shut out one of its people,
So that access to prices can be taken back when trust is lost.

**Requirements:** FR-7, FR-72

**Acceptance Criteria:**

**Given** an Admin revoking a business's approval with a reason
**When** it commits
**Then** the business returns to the Get approved steps, prices are hidden on every surface, and the change is audited.

**Given** an Admin disabling a User with a reason
**When** it commits
**Then** every session of that User is revoked and they cannot log in.

**Given** a business suspended for fraud that was vouched for by another business
**When** the suspension commits
**Then** the voucher's vouching rights are paused pending review and flagged for Admin (FR-7).

**Given** either action
**When** it commits
**Then** the Owner is told, unless Admin marked a fraud review.

**Given** a revoked business or a disabled User
**When** an Admin restores it with a reason
**Then** access returns and the change is audited.

### Story 5.10: The blocklist

As an Admin,
I want to block an identity, a contact or a bank account from the platform,
So that someone removed for fraud cannot simply sign up again.

**Requirements:** FR-72

**Acceptance Criteria:**

**Given** an Admin on Holds and blocklist
**When** they add a NIN, a contact or a sending bank account with a reason
**Then** the entry is saved, a NIN being kept only as its keyed hash, and the change is audited.

**Given** a Finance officer adding an entry for payment fraud
**When** it is saved
**Then** it takes effect and waits for Admin to ratify it within 1 Business Day.

**Given** a blocklisted contact
**When** someone signs up with it
**Then** they see a neutral refusal that never says why (FR-72).

**Given** a blocklisted NIN hash
**When** a profile with that NIN is submitted
**Then** it is refused in the same neutral way.

**Given** the blocklist
**When** an Admin views it
**Then** each entry shows who added it and how many attempts it has refused.

**Given** "Check an item"
**When** an Admin enters a value
**Then** the only answers are "On the blocklist since [date]" or "Not on the blocklist".

**Given** another module
**When** it needs to check a sending bank account
**Then** one Identity Query answers.

### Story 5.11: The Owner closes the business

As an Owner,
I want to close my business account and have my personal data removed,
So that I can leave cleanly when I no longer need the platform.

**Requirements:** FR-72; NFR-3, NFR-8; AD-26; edge-case rows 7, 8

**Acceptance Criteria:**

**Given** an Owner on Account
**When** they follow "Close this business account"
**Then** a checklist shows three conditions, each Clear or Needs action in words with a link to fix it: no open orders, no open return requests or cases, and no credit notes with a balance.

**Given** the closure checks
**When** they are evaluated
**Then** Identity asks each module that has registered a check, so later modules add theirs without changing this screen.

**Given** any condition that reads Needs action
**When** the Owner tries to continue
**Then** closure is refused, and a "call us" line covers a credit-only balance (PRD §8.3 question 9).

**Given** all conditions Clear
**When** the Owner continues
**Then** a last-check screen lists what is erased and what the law makes us keep, with an unticked "I understand" box, a fresh one-time code, and a red "Close the account" button.

**Given** a confirmed closure
**When** it commits
**Then** Identity calls every registered `ErasePersonalData` Action, personal data is erased or pseudonymised except records under a retention hold, and every session of the business is revoked (AD-26).

**Given** the audit trail after a closure
**When** it is read
**Then** it still holds identifiers and field differences and no personal value (edge-case row 8).

**Given** a Manager or Technician
**When** they open Account
**Then** the closing link is not shown.

### Story 5.12: Admin answers a personal-data request

As an Admin,
I want to gather everything we hold about a business's people in one export,
So that we can answer a data-subject request properly.

**Requirements:** NFR-3; AD-26

**Acceptance Criteria:**

**Given** an Admin on a business account page
**When** they request a personal-data export with a reason
**Then** Identity calls every registered `ExportPersonalData` Action and assembles one file.

**Given** the export file
**When** it is ready
**Then** it is stored as a private document and offered through a short-lived signed link.

**Given** the export
**When** it is produced
**Then** a NIN appears only as its last four digits, and the read is recorded in the audit trail.

**Given** a module that declares personal-data columns
**When** the test suite runs
**Then** a test fails if that module has not registered both its erase and its export Action.

**Given** a personal-data breach
**When** the breach runbook kept in the repository is followed
**Then** it covers containing the breach, working out whose data is affected, notifying the Nigeria Data Protection Commission within 72 hours, telling the people affected, and recording the incident (NFR-3)
**And** the runbook is reviewed at each quarterly restore drill.

## Epic 6: Design a Project and send a Proposal

An Owner, Manager or Technician builds a job room by room with a Parts List, Technicians seeing no prices; an Owner or Manager turns it into a client-ready Proposal PDF with one Integrator Margin.

### Story 6.1: Create a Project

As an Owner, Manager or Technician,
I want to start a Project by giving it a name,
So that I can begin laying out a job in seconds, even with no signal.

**Requirements:** FR-25, FR-28; AD-11, AD-12, AD-19

**Acceptance Criteria:**

**Given** a User of a Level 3 Business Account
**When** the app opens
**Then** a Projects tab is shown, in its place in the fixed tab order.

**Given** the Projects tab with nothing in it
**When** it loads
**Then** it reads "No projects yet. Make your first one: a name is all we ask."

**Given** "New project"
**When** the User types a name and saves
**Then** a Project is created with one Room already in it, which can be renamed.

**Given** a new Project
**When** it is saved
**Then** it is written at once to a local store on the phone that belongs to that User and role, with a UUIDv7 id made on the phone by a UUIDv7 library (AD-11, AD-12).

**Given** a phone with no signal
**When** the User creates a Project
**Then** it works, and a line says how many changes are waiting.

**Given** the app launching
**When** it starts
**Then** it asks the phone to keep its storage, records the answer, and warns when the phone will not promise to.

**Given** the Projects list
**When** it shows a Project
**Then** its status is a plain outlined label, and only a clock or a problem is tinted.

**Given** a Level 1 or Level 2 session
**When** it opens the app
**Then** no Projects tab is shown and the server refuses any attempt to create one.

### Story 6.2: Projects sync to the server and other devices

As an Owner,
I want a Project made on one phone to appear on my team's devices,
So that the office and the site are looking at the same job.

**Requirements:** FR-28; AD-12, AD-22; edge-case row 34; test design R-05

**Acceptance Criteria:**

**Given** changes waiting on a phone
**When** it has a connection
**Then** it pushes them as one batch of operations under one `Idempotency-Key`, and a replayed batch is applied once.

**Given** a pushed operation
**When** its acting User is not the session User
**Then** the server refuses it.

**Given** two devices that changed the same field
**When** both sync
**Then** the later one by the server's receipt time wins, the device clock is never used, and the earlier version is kept.

**Given** two people who edited different Rooms of one Project offline
**When** both sync
**Then** no edit is lost (edge-case row 34).

**Given** any accepted merge
**When** it commits
**Then** the Project's `revision` goes up by one (AD-22).

**Given** a phone pulling changes
**When** it asks for rows above its cursor
**Then** it receives rows whose `change_seq` is higher and whose writing transaction is older than the snapshot's oldest open transaction, so a commit that lands out of order is never skipped.

**Given** a Project created offline
**When** it has synced
**Then** it appears on the Business Account's other devices.

**Given** the app
**When** it comes to the front, regains a connection, or the User presses "Check now"
**Then** it syncs.

**Given** the merge rules
**When** they are implemented
**Then** the server and the app both run the same specification's test vectors.

**Given** two writing transactions that commit in the opposite order to their `change_seq`
**When** a phone pulls changes
**Then** neither row is skipped, proven by a test against PostgreSQL (test design R-05).

### Story 6.3: Add, rename, delete and restore Rooms

As an Owner, Manager or Technician,
I want to shape a Project into the rooms of the job,
So that the design reads the way the building does.

**Requirements:** FR-25, FR-28

**Acceptance Criteria:**

**Given** a Project
**When** the User taps the pencil on a Room and changes its name
**Then** the Room is renamed and its items are untouched.

**Given** a Project
**When** the User presses "Add room"
**Then** a new Room is added.

**Given** a Room that holds items
**When** the User deletes it
**Then** a confirmation names the Room and says how many items it holds.

**Given** an empty Room
**When** the User deletes it
**Then** it is deleted without a confirmation, because it can be undone.

**Given** a deleted Room
**When** up to 30 days have passed
**Then** it can be restored with its items (FR-25).

**Given** an edit to a Room that reaches the server after its deletion
**When** the merge runs
**Then** the Room is brought back and a conflict is recorded.

**Given** a phone with no signal
**When** the User renames, adds or deletes a Room
**Then** it works and is counted in the changes waiting.

### Story 6.4: Add products to a Room

As an Owner, Manager or Technician,
I want to pick products into a room with their quantities,
So that the Project becomes a real bill of materials.

**Requirements:** FR-17, FR-26, FR-66; AD-8; edge-case row 35; UX-DR10

**Acceptance Criteria:**

**Given** a Room
**When** the User presses "Add product"
**Then** a full-screen picker opens for that Room with the Catalogue's search and filters, and a bottom bar reading "Done · n items in [room]".

**Given** a Product in the picker
**When** the User presses Add
**Then** the button becomes a quantity stepper with minus and plus each at least 44px wide and a number that can be typed.

**Given** a quantity
**When** it is entered
**Then** it must be a whole number of at least 1, or a fraction only where Operations has switched on "allows fractions" for that unit of measure, with the unit shown after the number.

**Given** a quantity of zero or below
**When** it is entered
**Then** it is refused with a sentence.

**Given** an Out of Stock Product without back-order
**When** it appears in the picker
**Then** it is shown but cannot be added, with its reason.

**Given** an Out of Stock Product with back-order
**When** it is added
**Then** the Project Item is flagged with its Lead Time.

**Given** a Project Item whose Product has since been discontinued or quarantined
**When** the Project is shown
**Then** the item stays and carries a warning.

**Given** an added item reaching the server
**When** the merge runs
**Then** `Catalogue`'s `AssertSellable` is called, and a Product that cannot be sold is kept and flagged, never discarded.

**Given** a Product page opened from a Room
**When** it loads for a Level 3 User
**Then** its add button reads "Add to [room]", and opened from the Catalogue tab it offers "Add to a project".

**Given** an existing Project Item
**When** the User changes its quantity or presses Remove
**Then** the change is saved on the phone first.

*Note: in this epic the picker needs a connection; changing quantities and Rooms works offline. Browsing the Catalogue offline arrives in Epic 12.*

### Story 6.5: Prices and totals in a Project

As an Owner or Manager,
I want to see what each line, room and the whole Project costs me,
So that I can judge the job before I quote it.

**Requirements:** FR-27, FR-73; AD-6, AD-12; UX-DR36, UX-DR15

**Acceptance Criteria:**

**Given** an Owner or Manager viewing a Project
**When** it loads
**Then** each item shows its line price, each Room its total and the Project its total, with "VAT included" under the total.

**Given** the app's money module
**When** it adds up a Project
**Then** it parses each decimal string into whole kobo as a `BigInt`, multiplies a unit price by a quantity and sums the lines
**And** it never works out a rate, tier, discount, markup or VAT, and a test fails if money passes through floating point (AD-6).

**Given** an Owner or Manager's phone
**When** it syncs
**Then** it receives a price sheet covering only the Products in the business's Projects and the last 50 Products that person viewed (a placeholder setting), with its own "as of" time (FR-73).

**Given** a rate, tier or markup change
**When** the phone next syncs
**Then** the price sheet is refreshed.

**Given** a price shown from the phone with no signal
**When** it is displayed
**Then** it says "as of" and the time, and after a day it turns brass with "Prices may have changed".

**Given** a sync that moves a Project's total
**When** the Project is next opened
**Then** it says once "Prices were updated. The total went from [old] to [new]."

**Given** a back-order item
**When** an Owner or Manager sees it
**Then** it shows its tag, Lead Time and discount.

**Given** every amount on the screen
**When** it is shown
**Then** it has the naira sign, commas and two decimals, lines up on the right in tabular figures, and unit price times quantity equals the line to the kobo.

### Story 6.6: A Technician's Project

As a Technician,
I want to open and edit the same Project as my Owner without any prices,
So that I can plan and adjust the install without seeing what is not mine to see.

**Requirements:** FR-11, FR-27

**Acceptance Criteria:**

**Given** a Technician viewing a Project
**When** it loads
**Then** it shows the same Rooms, items and quantities as the Owner sees, and no price area is drawn at all.

**Given** a back-order item
**When** a Technician sees it
**Then** it shows the back-order flag and Lead Time and not the Back-order Discount.

**Given** a discontinued or quarantined Product in the Project
**When** a Technician sees it
**Then** the warning is shown.

**Given** a Technician's phone
**When** it syncs
**Then** no price sheet is sent, and its local store holds no price field (FR-11).

**Given** the Technician-safe test suite
**When** CI runs
**Then** it covers the Projects API and the sync payloads and fails on any money field.

**Given** a Technician
**When** they create a Project, add Rooms and add products
**Then** all of it works as for an Owner, without amounts.

### Story 6.7: A removed or re-roled person's phone is cleared

As an Owner,
I want a person's saved work cleared from their phone when they leave or change role,
So that prices and projects do not linger on a device I no longer control.

**Requirements:** FR-10, FR-28; AD-12; edge-case rows 9, 10; test design R-06

**Acceptance Criteria:**

**Given** a User whose role, Access Level, Hold or membership has changed
**When** their phone next connects
**Then** the local store for that User and role is cleared, and the screen says why (AD-12).

**Given** a Manager who becomes a Technician
**When** their phone next connects
**Then** no price field remains on it (edge-case row 10).

**Given** edits that User queued before the change
**When** they reach the server
**Then** they are stored as held conflicts and nothing is applied automatically (edge-case row 9).

**Given** every sync request
**When** the server receives it
**Then** it re-checks the User's role and membership.

**Given** a removed User
**When** their store is cleared
**Then** the Projects themselves stay with the business.

**Given** a Manager demoted to Technician on a phone that holds a price sheet
**When** the phone reconnects
**Then** a browser test that inspects the local IndexedDB store finds no price field (test design R-06).

*Note: the screen where an Owner or Manager accepts or discards held edits is built in Epic 12.*

### Story 6.8: The Parts List

As an Owner, Manager or Technician,
I want one list of everything a Project needs,
So that I can check stock, load the van or hand a list to anyone.

**Requirements:** FR-25; AD-14; UX-DR23

**Acceptance Criteria:**

**Given** a Project
**When** the User opens its Parts List
**Then** each Product appears once with its total quantity across Rooms and the split by Room.

**Given** the Parts List
**When** anyone views or downloads it
**Then** it shows no prices, whatever their role.

**Given** a phone with no signal
**When** the User opens the Parts List on screen
**Then** it works.

**Given** "Save PDF" or "Share"
**When** the phone has no signal
**Then** the button is visible, dashed and off, and says the PDF needs a connection.

**Given** a requested Parts List PDF
**When** it is made
**Then** it is rendered on the queue through `Shared`'s `PdfRenderer` contract from a snapshot row, with the PDF engine chosen behind that contract (AD-14).

**Given** the PDF
**When** it is inspected
**Then** it is tagged: real text, a title, the language, headings and a true table, and it prints cleanly (UX-DR23).

**Given** Share
**When** it is pressed
**Then** the phone's share sheet opens, with Copy as the fallback.

### Story 6.9: Home

As an Owner, Manager or Technician,
I want the app to open on what needs me and what I was working on,
So that I never have to hunt for the next thing to do.

**Requirements:** UX-DR28

**Acceptance Criteria:**

**Given** a User of a Level 3 Business Account
**When** the app opens
**Then** Home is the first tab and opens first.

**Given** Home for an Owner or Manager
**When** it loads
**Then** "Needs your attention" comes first, then recent projects, then "New project".

**Given** a Manager's request for a Vouch Code from Story 5.6
**When** the Owner opens Home
**Then** it appears under "Needs your attention", tinted, with its one button.

**Given** nothing needs the person
**When** Home loads
**Then** the section says so in one line.

**Given** a Technician
**When** Home loads
**Then** it has the same shape with no money anywhere, and holds only what concerns them.

**Given** a screen 1024px wide or more
**When** Projects is opened
**Then** the list and the chosen Project sit side by side, and the Project keeps a summary panel in view with its total and actions.

**Given** a screen-reader user
**When** Home loads
**Then** it has one top heading, each section is a labelled region, and a tinted card's meaning is also in words.

### Story 6.10: Generate a Proposal

As an Owner or Manager,
I want to turn a Project into a Proposal with my own margin,
So that I can send my client a professional quote in minutes.

**Requirements:** FR-29, FR-70; AD-7, AD-22; edge-case rows 36, 39; UX-DR43

**Acceptance Criteria:**

**Given** an Owner or Manager on a Project with at least one item
**When** they press "Generate proposal"
**Then** one screen asks for the client (only the name is needed), one margin as a percentage that starts at the last one used, and any extra lines.

**Given** the client fields
**When** they are shown
**Then** one line says why we keep a client's details and who sees them, using placeholder wording until counsel approves it.

**Given** the margin field
**When** a value is typed
**Then** it takes up to two decimals, refuses anything below zero, and asks "Is that right?" above 100% (FR-29).

**Given** an extra line such as labour
**When** it is added
**Then** it has a description, a quantity and a VAT-included price, appears on the Proposal only, and is never ordered.

**Given** the screen
**When** values change
**Then** a private "Only you see this" panel shows the cost, the margin, the extra lines and the client total.

**Given** a business that has declared itself VAT-registered
**When** the screen loads
**Then** a "Show a VAT line on the PDF" switch is shown, and for any other business it is not.

**Given** a phone with no signal
**When** the User tries to generate
**Then** the button is off and reads "Prices must be current. Connect to make a Proposal."

**Given** a business with no Trade Name, or a Project with no items
**When** generation is attempted
**Then** the server refuses with the reason (edge-case rows 36 and 39).

**Given** a valid request
**When** the server generates the Proposal
**Then** it prices the Project afresh through `Pricing`, and stores the price snapshot, the Tier Discount in force, the Proposal Rate, the Project revision and the expiry
**And** no price held on the phone is used.

**Given** a generated Proposal
**When** it is ready
**Then** "Ready" offers Share and Save PDF.

**Given** a Technician
**When** they view a Project
**Then** "Generate proposal" is not shown.

### Story 6.11: The Proposal PDF

As an Owner or Manager,
I want the Proposal to look like my own document and reveal nothing about my costs,
So that my client sees my business and my price, and nothing else.

**Requirements:** FR-30; edge-case row 30; UX-DR23, UX-DR43; test design R-10

**Acceptance Criteria:**

**Given** a generated Proposal
**When** its PDF is rendered
**Then** it carries the Integrator's Trade Name and logo, or a name-only header without a logo, and no platform branding.

**Given** the PDF
**When** it is read
**Then** it shows client and project blocks, items by Room with quantity, unit price and amount at client prices, Room totals, extra lines, the total with "VAT included", the expiry date and the validity sentence about the exchange rate.

**Given** a Back-order line
**When** it is shown
**Then** it is marked with its delivery time and not its Back-order Discount.

**Given** the "Show a VAT line" switch is on
**When** the PDF is rendered
**Then** each line amount is its VAT-inclusive total divided by (1 + the VAT rate) and rounded to the kobo, the VAT line is the grand total minus the sum of those lines, and the grand total is identical to the PDF without a VAT line.

**Given** three client lines of NGN 10.10 before VAT
**When** the PDF is rendered with a VAT line
**Then** the grand total is NGN 32.58 and the VAT line is NGN 2.28.

**Given** any rendered Proposal PDF
**When** the forbidden-field test searches its visible text and its metadata
**Then** it finds no Supplier Cost, Wholesale Price, Platform Markup, Tier Discount, Back-order Discount, Integrator Margin or the Selling Price the Integrator pays (FR-30).

**Given** the PDF file
**When** it is stored and served
**Then** it is private, served only through a short-lived signed link, and tagged with real text, a title, the language, headings, true tables and a described logo.

**Given** distinctive sentinel values planted for the Supplier Cost, Wholesale Price, Platform Markup, Tier Discount, Back-order Discount, Integrator Margin and the Integrator's Selling Price
**When** the forbidden-field test renders the PDF and searches its extracted text and its metadata
**Then** no sentinel is found (test design R-10).

### Story 6.12: Replace and expire a Proposal

As an Owner or Manager,
I want to replace a Proposal when the client asks for changes, and know when it has lapsed,
So that only one current quote is ever out there.

**Requirements:** FR-31, FR-32; edge-case rows 37, 38

**Acceptance Criteria:**

**Given** a Project that already has a Proposal
**When** the User generates again
**Then** a confirmation reads "The old PDF cannot be recovered" before anything is replaced.

**Given** a confirmed regeneration
**When** it completes
**Then** the old PDF is hard-replaced, only the latest Proposal can be downloaded, and the platform holds no earlier version (FR-31).

**Given** a Proposal
**When** it is generated
**Then** it expires at the end of the day, West Africa Time, one calendar month later, clamped to the end of a shorter month
**And** one generated on 31 January expires at the end of 28 February, or 29 in a leap year.

**Given** the expiry deadline passes
**When** the sweep acts
**Then** the Proposal is marked Expired, judged by server time.

**Given** a Project
**When** it is viewed
**Then** it carries one Proposal card that reads "Valid until [date]", or "Out of date" when the Project's revision has moved since the Proposal, or "Expired".

**Given** an Expired Proposal
**When** the User opens it
**Then** it can still be downloaded, and the card says ordering will use today's prices.

## Epic 7: Order a Project or a Cart

An Owner or Manager creates an Order from a whole Project or a Cart at exactly the price confirmed, sees what changed since the Proposal, opts in to back-orders, chooses delivery or pick-up and parts or full, and can change delivery details; Operations sets up Delivery Zones and Stores.

### Story 7.1: Operations sets up Stores

As an Operations officer,
I want to keep the list of Stores where orders are collected,
So that Integrators can choose where to pick up and we can add a Store without a software release.

**Requirements:** FR-56; edge-case row 63

**Acceptance Criteria:**

**Given** an Operations or Admin account on Stores and zones
**When** they add or edit a Store
**Then** it holds a name, an address and opening hours, and the change is audited.

**Given** the system at launch
**When** Stores are set up
**Then** exactly one Store is the Main Store.

**Given** the Main Store
**When** someone tries to deactivate it
**Then** it is refused until another Store is named as its replacement.

**Given** a Store that open Shipments refer to
**When** someone tries to deactivate it
**Then** it is refused unless Operations reassigns those Shipments with notice (edge-case row 63).

**Given** Stores
**When** stock is read
**Then** quantities exist once for the whole Catalogue and not per Store (FR-56).

**Given** a Finance account
**When** it tries to edit a Store
**Then** the server refuses.

### Story 7.2: Operations sets up Delivery Zones and Zone Rates

As an Operations officer,
I want to define where we deliver and what each zone costs,
So that every delivery is priced before an Integrator pays.

**Requirements:** FR-37, FR-47; AD-7

**Acceptance Criteria:**

**Given** the reference list of Nigerian States and local government areas
**When** the system is set up
**Then** it is seeded, and a zone is defined as a name plus a list of those areas.

**Given** a zone
**When** Operations sets it up
**Then** it has a flat Zone Rate in naira per Shipment, a delivery estimate and a status of active or coming soon.

**Given** a zone with no rate or no estimate
**When** Operations tries to activate it
**Then** it is refused.

**Given** an area already in one zone
**When** Operations adds it to another
**Then** the save is refused, and areas in no zone are listed as unplaced.

**Given** "Test an address"
**When** Operations enters a State and local government area
**Then** it resolves to exactly one zone, or says none.

**Given** a Zone Rate change
**When** it is saved
**Then** it affects only Orders created afterwards, and the rate's history is kept (FR-47).

**Given** a delivery charge
**When** it is worked out
**Then** `Pricing`'s `PriceDelivery` computes it, and no other module does.

**Given** a Finance account
**When** it tries to edit a zone or a rate
**Then** the server refuses.

### Story 7.3: The Cart

As an Owner or Manager,
I want a cart for parts I simply need to buy,
So that I can restock without building a Project.

**Requirements:** FR-33

**Acceptance Criteria:**

**Given** an Owner or Manager of a Level 3 business
**When** any screen loads
**Then** a cart icon with a count sits in the top bar (top right on a laptop), named for a screen reader as "Cart, 2 items"
**And** a Technician has no cart icon.

**Given** a Product page opened from the Catalogue tab
**When** it loads for an Owner or Manager
**Then** "Add to cart" is offered first and "Add to a project" second.

**Given** an item added
**When** it is confirmed
**Then** the page says "[n] added to your cart" with the count and total.

**Given** Home
**When** an Owner or Manager opens it
**Then** "Buy parts without a project" opens the Catalogue with Add to cart.

**Given** the Cart screen
**When** it loads
**Then** it shows whole-number steppers, Remove in words, a total with "VAT included", and a line saying these are today's prices, the price is fixed when the order is created, and nothing is kept until payment is confirmed.

**Given** a Cart
**When** it is stored
**Then** it lives on the server only, belongs to one User, is never shared, and no action turns it into a Project (FR-33).

**Given** an item that has sold out since it was added
**When** the Cart loads
**Then** it is listed at the top as Sold out with only Remove, and "Choose delivery or pick-up" stays off until it is removed.

**Given** an item that has become a Back-order Item
**When** the Cart loads
**Then** it shows the back-order tag and the old and new price.

**Given** a phone with no signal
**When** the Cart is opened
**Then** it says "An order is made at today's price." and that a connection is needed.

### Story 7.4: Review an Order from a Project

As an Owner or Manager,
I want to check exactly what I am about to order from a Project,
So that I commit to the right items at a price I have seen.

**Requirements:** FR-24, FR-34; edge-case rows 28, 36; UX-DR44

**Acceptance Criteria:**

**Given** an Owner or Manager on a Project with items
**When** they press "Create order"
**Then** step 1 of 3, Review, opens with a step indicator in words and a sticky bar holding the total and the one main action.

**Given** Review loading
**When** the server prices the Project
**Then** `Pricing` returns an Order Quote with a `quote_id`, at the tier in effect now (FR-24).

**Given** a Project line that is Out of Stock without back-order, discontinued or quarantined
**When** Review loads
**Then** it is listed as unable to be ordered with Remove and Find another, nothing is removed for the person, and Continue stays off until each is resolved (edge-case row 28).

**Given** a Project with no items, or only zero quantities
**When** an Order is attempted
**Then** it is refused (edge-case row 36).

**Given** a Project that has been ordered before
**When** it is ordered again
**Then** the new Order is priced afresh and the first is unchanged.

**Given** a phone with no signal
**When** "Create order" is pressed
**Then** it is off and says a connection is needed.

**Given** a Technician
**When** they view a Project
**Then** "Create order" is not shown, and a link to ordering says to ask the Owner.

### Story 7.5: Compared with your Proposal

As an Owner or Manager,
I want to see what has changed since I quoted my client,
So that I can decide whether to pass a difference on or absorb it from my margin.

**Requirements:** FR-35; AD-22; edge-case row 37

**Acceptance Criteria:**

**Given** a Project with an unexpired Proposal, contents identical to it, and a current Platform Rate within NGN 5 of the Proposal Rate
**When** the Order is priced
**Then** the Proposal Rate is used.

**Given** a Proposal Rate of NGN 1,330
**When** the current rate is 1,334
**Then** 1,330 is used, and when it is 1,336 then 1,336 is used (FR-35).

**Given** a Project whose revision has moved since its Proposal
**When** Review loads
**Then** it says "Project edited since Proposal" and uses the current rate.

**Given** an expired Proposal
**When** the Order is priced
**Then** the current rate applies.

**Given** a Proposal whose rate has already been used by an earlier Order
**When** a second Order is priced
**Then** the current rate applies, because `ConsumeProposalRate` runs once under the Project lock.

**Given** Review with a Proposal
**When** it loads
**Then** a "Compared with your Proposal" block shows the cost at the Proposal, the exchange-rate change, the tier change, items and stock changes, and the cost today, with delivery on its own line.

**Given** that block
**When** it is read
**Then** it says plainly that the client's Proposal does not change on its own, and shows the margin at today's cost.

**Given** the exchange-rate line
**When** it is shown
**Then** the rate appears only as "₦1,330 then, ₦1,368 now", and no dollar amount is shown.

**Given** a Project with no Proposal
**When** Review loads
**Then** the block is not shown.

### Story 7.6: Agree to a back-order item

As an Owner or Manager,
I want the terms of a back-order item spelled out before I order it,
So that I am never surprised that it cannot be cancelled or returned.

**Requirements:** FR-17, FR-36; edge-case row 24

**Acceptance Criteria:**

**Given** an Order that includes a Back-order Item
**When** Review loads
**Then** a block names each such item with its Lead Time and Back-order Discount.

**Given** that block
**When** it is read
**Then** it gives two disclosures: the item cannot be cancelled once we have ordered it from the supplier unless it is more than 7 days late, and it cannot be returned for a change of mind.

**Given** the box "I understand, and I want to order this item"
**When** Review loads
**Then** it is unticked, and Continue is off with its reason until it is ticked.

**Given** an Order request that includes a Back-order Item without the opt-in
**When** it reaches the server
**Then** it is refused (FR-36).

**Given** an Order created with a Back-order Item
**When** its Order Line is stored
**Then** the discount and Lead Time are captured on the line, and later edits to the Product's terms never change it (edge-case row 24).

### Story 7.7: Choose delivery or pick-up

As an Owner or Manager,
I want to say how the order reaches me and see the delivery cost at once,
So that the amount I agree to already includes getting the goods to site.

**Requirements:** FR-37, FR-39; edge-case row 49

**Acceptance Criteria:**

**Given** step 2, Delivery
**When** it loads
**Then** a switch offers Delivery or Pick-up.

**Given** Pick-up
**When** it is chosen
**Then** the person picks a Store, sees its address and opening hours, and no delivery cost is added.

**Given** Delivery
**When** it is chosen
**Then** a structured address asks for State and local government area from lists, an area, an optional landmark, and a receiver name and phone
**And** one line says why we keep a receiver's details and who sees them.

**Given** a State and local government area
**When** they are chosen
**Then** the zone, the price and the estimate appear at once, looked up from those two fields and never from free text.

**Given** the shipping choice
**When** it is shown
**Then** the person chooses one delivery when everything is ready, with the 14-day offer explained, or to send what is ready first.

**Given** that choice
**When** the charge is shown
**Then** it covers each Shipment the choice implies at its own Zone Rate, and the Shipments are fixed when the Order is created.

**Given** an address in a zone that is coming soon, or in no zone
**When** it is entered
**Then** Delivery is blocked and the screen offers "Collect from the Main Store" or "Ask us for a delivery price".

**Given** an Order from a Project with a Proposal
**When** the Delivery step opens
**Then** the client's details from the Proposal fill the receiver fields, and can be changed.

*Note: the PRD's FR-37 also mentions a manual zone pick. It is not built, by decision of 13 October 2026.*

### Story 7.8: Confirm and create the Order

As an Owner or Manager,
I want to confirm one clear amount and create the order once,
So that what I owe is exactly what I saw.

**Requirements:** FR-34; NFR-4, NFR-10; AD-4, AD-22; edge-case rows 40, 41

**Acceptance Criteria:**

**Given** step 3, Confirm
**When** it loads
**Then** it shows the Amount Due with goods and delivery written out in full, the 24-hour price-hold explanation, that an unpaid order is cancelled after 48 hours, the Terms line and "Create order".

**Given** "Create order"
**When** it is pressed
**Then** the request carries the `quote_id` the person saw and an `Idempotency-Key`.

**Given** the server receiving the request
**When** it re-quotes
**Then** if the Platform Rate, Loyalty Tier, Proposal validity, Back-order terms or availability has changed, it creates nothing and returns `409` with the new Order Quote (edge-case row 41).

**Given** a `409`
**When** the app receives it
**Then** a sheet shows the old and new amounts with "Confirm new price and create order" and "Back to review".

**Given** a valid request
**When** the Order is created
**Then** the Business Account is locked first and then the Project, and the Order and its Order Lines store the price snapshot exactly as `Pricing` returned it (NFR-4)
**And** the Order has a human reference such as INT-0042 in its own column, the status Awaiting Payment, and UTC deadlines for the price hold and for expiry.

**Given** a double press, a retry or a replay of the same key
**When** it reaches the server
**Then** exactly one Order exists.

**Given** the signal drops after the button is pressed
**When** no answer arrives
**Then** the screen says "We have not heard back. Your order may have been made. Check Orders, or try again: you cannot make two."

**Given** a created Order
**When** it commits
**Then** no stock is reserved, and an `OrderCreated` event is written to the outbox.

**Given** the Order-creation Action
**When** the test suite runs
**Then** it has a test with two racing calls and one winner, and an idempotent-replay test (NFR-10).

### Story 7.9: Order from a Cart

As a Manager,
I want to order what is in my cart in two steps,
So that buying a few parts is quick.

**Requirements:** FR-33, FR-34; edge-case row 40

**Acceptance Criteria:**

**Given** a Cart with orderable items
**When** the person presses "Choose delivery or pick-up"
**Then** two steps follow, Delivery or pick-up and Confirm, with no Proposal comparison.

**Given** Confirm
**When** it loads
**Then** an optional order name is offered, and without one the Order is named "Direct buy" with its item count.

**Given** a Cart that includes a Back-order Item
**When** the person continues
**Then** the agreement from Story 7.6 is asked on the next screen.

**Given** a created Order
**When** it commits
**Then** the Cart is emptied in the same transaction.

**Given** a double submit
**When** it reaches the server
**Then** one Order is created (edge-case row 40).

### Story 7.10: Checks before an order

As an Owner or Manager,
I want to be told plainly when I cannot order and what to do about it,
So that I am never left at a dead end.

**Requirements:** FR-8, FR-20, FR-34, FR-69, FR-72

**Acceptance Criteria:**

**Given** a business on Hold
**When** anyone tries to create an Order
**Then** the server refuses, and the screen says what is wrong and offers "Call or WhatsApp us".

**Given** an Owner who has not accepted the current Terms
**When** they try to create an Order
**Then** they are taken to accept them, and a Manager sees a notice above "Create order" to ask the Owner.

**Given** a paused or stale Platform Rate
**When** anyone tries to create an Order
**Then** one message, "Prices are being updated", stops ordering without naming the cause (FR-20).

**Given** a Project that already has 2 unpaid Orders
**When** a third is attempted
**Then** it is refused and the two are linked (FR-34).

**Given** a Pending Activation business
**When** an Order is attempted
**Then** it is refused.

**Given** each of these checks
**When** the test suite runs
**Then** the server enforces it whether or not the app shows the block.

**Given** an account-wide condition (a Hold, or prices being updated)
**When** any screen loads
**Then** its banner stands at the very top, and account-wide banners stack.

**Given** each block
**When** it is shown
**Then** it says what is wrong, why, and what to do, and a screen reader hears it at once.

### Story 7.11: The Orders tab and one order

As an Owner, Manager or Technician,
I want to see my orders and where each stands,
So that I always know what is open and what is done.

**Requirements:** FR-46; UX-DR45

**Acceptance Criteria:**

**Given** a User of a Level 3 business
**When** the app loads
**Then** an Orders tab is shown, completing the five tabs in their fixed order.

**Given** the Orders tab
**When** it loads
**Then** a segmented control splits it into Open and Done, and an order that needs action is tinted and listed first.

**Given** one order
**When** an Owner or Manager opens it
**Then** it shows its reference in the monospace face with Copy, its status with a timestamp, its items, its delivery choice and its Amount Due.

**Given** a Technician
**When** they open the Orders tab or an order
**Then** they see status only, with no amount anywhere (FR-46).

**Given** a phone with no signal
**When** Orders is opened
**Then** it says "We check these against the bank." and that a connection is needed, because Orders are never kept on the phone.

**Given** an Order Awaiting Payment
**When** an Owner or Manager opens Home
**Then** it appears under "Needs your attention" with its clock.

**Given** a screen 1024px wide or more
**When** Orders is opened
**Then** the list and the chosen order sit side by side.

### Story 7.12: An unpaid Order expires

As an Owner or Manager,
I want to see how long my price is held and what happens if I do not pay,
So that I can plan my transfer and am not surprised by a cancelled order.

**Requirements:** FR-38; NFR-9; edge-case row 51; UX-DR37, UX-DR14

**Acceptance Criteria:**

**Given** an Order Awaiting Payment
**When** its page loads
**Then** a card shows the time left and the exact end time, such as "Price held until Thu 8 Oct, 4:12 pm, 19 h 42 min left."

**Given** 4 hours remain on the price hold
**When** the card is shown
**Then** it turns brass and says "After that the price may change with the exchange rate".

**Given** 8 hours remain before expiry
**When** the card is shown
**Then** it turns brass and says "This order is cancelled in 8 hours".

**Given** any countdown
**When** it is shown
**Then** it reads from the server's clock in West Africa Time, adds "Nigeria time" on a phone set to another zone, updates each minute, and is announced only at the start, at 1 hour, at 15 minutes and at the end.

**Given** an Order with no Receipt 48 hours after creation
**When** the sweep acts
**Then** the Order moves to Expired through an Action that re-reads the deadline under the Order lock (FR-38).

**Given** an expiry racing a cancellation
**When** both arrive
**Then** one transition happens and the other receives the current status (edge-case row 51).

**Given** an Expired Order
**When** its page loads
**Then** the card says what lapsed and offers "Make a new order from the Project", or for a cart order "Put the [n] items back in my cart".

**Given** a clock that has ended
**When** the card is shown
**Then** it shows its result and never sits at 0 h 0 min.

### Story 7.13: Cancel an unpaid Order

As an Owner or Manager,
I want to cancel an order I no longer need,
So that it stops counting against me at once.

**Requirements:** FR-57; edge-case row 51

**Acceptance Criteria:**

**Given** an Order Awaiting Payment with nothing paid
**When** an Owner or Manager presses "Cancel order"
**Then** a dialog's title is a question naming the order, its body lists what will happen, and a reason is optional.

**Given** a confirmed cancellation
**When** it commits
**Then** the Order moves to Cancelled at once and its Amount Due is cleared.

**Given** a Cancelled Order
**When** spend or Completed Orders are counted anywhere
**Then** it never counts (FR-57).

**Given** a cancel request pressed twice
**When** it reaches the server
**Then** it does what one press does.

**Given** a Cancelled Order
**When** its page loads
**Then** the Project is untouched, and a cart order offers to put its items back in the cart.

**Given** a Technician
**When** they open an order
**Then** no cancel action is shown.

### Story 7.14: Change delivery details before paying

As an Owner or Manager,
I want to correct where an order is going before I pay,
So that a wrong address does not cost me a new order.

**Requirements:** FR-75; edge-case row 48

**Acceptance Criteria:**

**Given** an Order Awaiting Payment
**When** an Owner or Manager presses "Change delivery details"
**Then** they can change the address, the receiver, the Store, between Delivery and Pick-up, or the parts-or-full choice.

**Given** a change that moves the Delivery Zone or the Fulfilment Method
**When** it is previewed
**Then** only the delivery charge moves, and the old and new Amount Due are written out before the person confirms.

**Given** a confirmed change
**When** it commits
**Then** no goods price has changed and the price-hold and expiry deadlines are exactly as before (FR-75).

**Given** a new address in a zone we do not serve
**When** it is entered
**Then** the change is blocked with the same offers as Story 7.7.

**Given** a change request retried
**When** it reaches the server with the same `Idempotency-Key`
**Then** it is applied once.

### Story 7.15: Ask for a delivery price

As an Owner or Manager,
I want to ask for a delivery price to a place you do not yet serve,
So that I can still order for a site outside your usual zones.

**Requirements:** FR-37

**Acceptance Criteria:**

**Given** an address we do not serve
**When** the person presses "Ask us for a delivery price"
**Then** a request is created with the address and the receiver, and the screen says by what exact time we will answer.

**Given** a new request
**When** it is created
**Then** it joins a Delivery price requests queue for Operations with a target of 1 Business Day (a placeholder), built on the shared work items.

**Given** Operations opening a request
**When** the panel loads
**Then** it shows the address, the receiver, the goods' weight and boxes from the Product records, and the nearest area we serve for comparison.

**Given** Operations answering
**When** they press "Send the price"
**Then** they enter a courier quote that only staff see and a price to the business with VAT included, which is good until the end of the next Business Day (a placeholder).

**Given** Operations cannot deliver there
**When** they press "We cannot deliver there yet…"
**Then** a reason the business will read is required.

**Given** an answered request
**When** the person opens Home
**Then** it appears under "Needs your attention", and they can continue the order at that price until it lapses.

**Given** a quoted price that has lapsed
**When** the person tries to use it
**Then** it is refused and they can ask again.

**Given** any response to a business
**When** it is inspected
**Then** the courier quote is never in it.

## Epic 8: Pay by bank transfer and hold the price

The Integrator pays by transfer and uploads a Receipt for each; Finance imports the bank statement and confirms each Payment; the Price Lock, repricing and expiry apply; stock is reserved at Paid; and Credit Notes are issued, applied and refunded.

### Story 8.1: Admin sets the platform's bank details

As an Admin,
I want a change to our bank details to need Finance's agreement and a day's delay,
So that nobody can quietly redirect Integrators' payments.

**Requirements:** FR-40, FR-52

**Acceptance Criteria:**

**Given** an Admin editing the bank, account number and account name
**When** they submit with a reason
**Then** the change waits on the Approvals page for a Finance officer, who must be a different person.

**Given** an approved change
**When** it is approved
**Then** it takes effect 24 hours later by a stored deadline, and Admin and Finance are emailed at the request and at the approval (FR-52).

**Given** a change that has taken effect
**When** an Owner or Manager opens the app during the next 7 days
**Then** a banner reads "Bank details changed on [date]", using placeholder wording until counsel approves it.

**Given** an Order being created
**When** it commits
**Then** it stores the bank details in force at that moment (FR-40).

**Given** a staff member viewing the bank details
**When** they are read
**Then** the read goes through an audited Action.

**Given** a Technician session
**When** it requests any response
**Then** no bank detail is in it.

### Story 8.2: The pay-by-transfer screen

As an Owner or Manager,
I want one screen that tells me exactly what to transfer and where,
So that my payment is matched first time.

**Requirements:** FR-40; UX-DR36

**Acceptance Criteria:**

**Given** an Order Awaiting Payment
**When** an Owner or Manager opens "Pay by bank transfer"
**Then** it shows Amount due, Confirmed so far and Still to pay, in that order, with the price-hold countdown and its exact deadline.

**Given** the payment details
**When** they are shown
**Then** the bank, account number, account name and the order reference for the narration each have a Copy button that says "Copied" for two seconds
**And** they are the details stored on that Order.

**Given** the screen
**When** it is read
**Then** a note says to transfer the exact amount and that several transfers each need their own receipt.

**Given** an amount the person has declared on a Receipt that Finance has not confirmed
**When** the three numbers are shown
**Then** it does not reduce Still to pay, and the word "paid" is used only for a confirmed figure.

**Given** "I will pay later"
**When** it is pressed
**Then** the person returns to the order, which keeps its clock.

**Given** a Technician
**When** they open an order
**Then** no payment screen, amount or bank detail is shown.

### Story 8.3: Upload a Receipt

As an Owner or Manager,
I want to send proof of my transfer from my phone, even on a poor connection,
So that my price is held from the moment I send it.

**Requirements:** FR-38, FR-40, FR-55; AD-14; edge-case row 44; UX-DR39

**Acceptance Criteria:**

**Given** "Upload receipt"
**When** it opens
**Then** "Take a photo" and "Choose a file" are both offered, and the camera permission is asked for only when "Take a photo" is pressed.

**Given** a chosen file
**When** it is a JPEG, PNG, HEIC or PDF of up to 10 MB
**Then** it is accepted, a photo is resized on the phone to about 2 MB with its location removed, and any other file is refused in a sentence before it is sent.

**Given** the details form
**When** it is shown
**Then** it asks for the amount (prefilled), sender name, sender bank, the transfer date and time, and the bank reference, and remembers the sender name and bank for next time.

**Given** the amount field
**When** text is pasted into it
**Then** a naira sign, "NGN" or spaces are dropped, two decimals are kept, a minus is refused, and the cleaned amount is read back under the field.

**Given** the transfer time
**When** it is entered
**Then** it is read back in words and cannot be in the future or before the Order was made.

**Given** "Send receipt"
**When** it is pressed
**Then** an upload-intent request carries the details and an `Idempotency-Key`, and the server stamps `upload_started_at`, which is the time that counts for the price hold even if the image finishes later (FR-38).

**Given** the signal drops during the upload
**When** it returns, or the app is reopened
**Then** the upload resumes by itself with the photo and details kept, and the screen says so (FR-55).

**Given** a completed upload
**When** the file arrives
**Then** it is quarantined, scanned, stripped and promoted as private evidence that is never overwritten, and a corrupt or blank file is refused with a reason.

**Given** a sent Receipt
**When** the order is shown
**Then** it says "Receipt under review" and "Price held", gives the exact time we will check it by, shows a status timeline, and offers "Send another receipt".

**Given** a sent Receipt
**When** the business views it
**Then** it cannot be edited or deleted; a mistake is fixed by sending a corrected one beside it.

**Given** no signal at all
**When** the screen loads
**Then** "Send receipt" is visible, dashed and off, and says why.

**Given** a slow upload close to the price-hold deadline
**When** it is in progress
**Then** the screen says what counts is when the details reached us.

**Given** a Receipt's details arriving
**When** the Payment is created as Submitted
**Then** a work item joins Finance's Receipts queue in the same transaction.

### Story 8.4: Finance imports the bank statement

As a Finance officer,
I want to load the bank statement into the console,
So that every Receipt can be checked against money that really arrived.

**Requirements:** FR-41; AD-10, AD-15

**Acceptance Criteria:**

**Given** Finance on Bank statements
**When** they choose a CSV file
**Then** a check step shows the rows read, the new rows, the rows already imported and skipped, the columns recognised and the date the file covers up to, before anything is imported.

**Given** a checked file
**When** Finance imports it
**Then** each new row becomes a statement line owned by the `Orders` module.

**Given** a row whose account, value date, amount and bank reference match a line already stored
**When** the file is imported
**Then** it is skipped, so importing the same file twice changes nothing (AD-10).

**Given** a bank's column layout
**When** a file from that bank is imported again
**Then** the layout is remembered.

**Given** the latest statement is old
**When** Finance opens Today
**Then** a notice says when it was last imported and offers "Import the latest".

**Given** the import
**When** it runs in staging or tests
**Then** it sits behind an interface with a fake (AD-15).

### Story 8.5: Finance confirms a Payment

As a Finance officer,
I want to check each Receipt against the bank statement and confirm it,
So that an order is only ever marked paid for money we hold.

**Requirements:** FR-41, FR-52; NFR-2; AD-10; edge-case row 55; UX-DR47

**Acceptance Criteria:**

**Given** the Receipts queue
**When** Finance opens it
**Then** items are oldest first against a 2 Business Hour target, with filters for All, Mine, Over target, Sent back before and Second review, and a preview panel showing the receipt and the three amounts.

**Given** a Receipt under review
**When** the review opens
**Then** three columns in reading order show what the business sent, the order (amount due, confirmed so far, credit applied, still to pay, price-hold timing and "sent back so far: 0 of 3"), and the bank statement.

**Given** the statement column
**When** it loads
**Then** it shows the best-matching credit and five checks written in words: amount, sender, bank reference unused, money arrived before the receipt, and your own accounts clear
**And** it shows the statement's age and coverage and suggests no match beyond it.

**Given** the amount to confirm
**When** Finance changes it from the declared amount
**Then** a reason is required and recorded.

**Given** "Confirm payment", the only primary button
**When** it is pressed
**Then** the Payment moves from Submitted to Confirmed by compare-and-set, an allocation row links the statement line to the Payment, an immutable ledger line is written, and the work item closes, all in one transaction.

**Given** a statement line
**When** allocations against it would exceed its amount
**Then** a database trigger refuses.

**Given** a bank reference already used on another Payment
**When** confirmation is attempted
**Then** it is refused (FR-41).

**Given** a confirmed Payment
**When** it is stored
**Then** its sending bank account is recorded against the Business Account.

**Given** a Finance officer who created, approved or vouched for the business
**When** they try to confirm
**Then** the Action refuses and the screen says so in words (FR-52).

**Given** two reviewers deciding the same Payment at once
**When** both submit
**Then** one wins and the other sees who confirmed it and when.

**Given** a confirmation
**When** it completes
**Then** the screen says what happens next, and no money action in the console is ever a single key.

### Story 8.6: A Payment is unconfirmed, and corrected

As an Owner or Manager,
I want to be told plainly when my payment could not be matched and how to put it right,
So that I can fix it and keep my price.

**Requirements:** FR-38, FR-41; edge-case row 46

**Acceptance Criteria:**

**Given** Finance finding no matching credit
**When** they press "Payment unconfirmed…" and give a reason
**Then** the Payment moves to Unconfirmed and an event is written to the outbox.

**Given** an Unconfirmed Payment
**When** the business opens the order
**Then** a red card reads exactly "Payment unconfirmed: please upload your receipt again or pay the full amount.", with the unmatched amount, "Upload a corrected receipt within 24 hours and we keep your price", an "Upload receipt again" button and a call or WhatsApp line.

**Given** a corrected Receipt sent within 24 hours of that notice
**When** Finance confirms it for the full amount
**Then** it keeps the original upload time for the price hold (edge-case row 46).

**Given** a third Unconfirmed upload on one Order
**When** Finance marks it
**Then** it goes to Admin as "Needs review".

**Given** an Unconfirmed decision
**When** it commits
**Then** the Order's expiry deadline is reset to 48 hours from that decision, within the 7-day limit.

### Story 8.7: Finance issues a Credit Note

As a Finance officer,
I want to record money we owe a business as a Credit Note,
So that it is held safely and can only be spent or refunded under our control.

**Requirements:** FR-43, FR-52, FR-53; AD-9

**Acceptance Criteria:**

**Given** the Credit Note ledger
**When** it is built
**Then** a note's life is made only of immutable entries: issue, hold, release, apply, restore, refund and expire (AD-9).

**Given** any request against a Credit Note
**When** it is checked
**Then** the available balance is issue minus holds minus applies plus restores minus refunds minus expires, read under the Credit Note lock.

**Given** Finance on "Issue a credit note"
**When** they choose one of the eight reasons and a source Order Line
**Then** the goods and VAT split is worked out by `Pricing`, the note is issued in naira with a reference such as CN-0012 and an expiry 12 months later, and the issuer is recorded.

**Given** an amount above the second-approver setting (a placeholder of ₦100,000.00)
**When** Finance issues it
**Then** it waits on the Approvals page.

**Given** a Finance officer who created, approved or vouched for the business
**When** they try to issue
**Then** it is refused with the reason in words.

**Given** the console's Credit notes page
**When** Finance opens it
**Then** it shows ageing tiles (open, expiring in 60 days, expiring in 14 days, expired with a balance), drafts, and whether each note can be refunded or is credit only.

**Given** an Owner or Manager on Account, Credit notes
**When** it loads
**Then** "Available to use" is at the top, with tabs for Available and Used or expired, the note that expires first tinted and listed first, and the status words Ready to use, Partly used, Fully used, Refunded and Expired.

**Given** one Credit Note
**When** an Owner or Manager opens it
**Then** it shows the reason in plain words, the amount issued, used and the balance, and its history, and is never called a wallet or a balance.

**Given** a Credit Note
**When** anyone tries to move it to another Business Account
**Then** no such action exists.

**Given** one staff member issuing more Credit Notes in a day than the alert threshold (a placeholder setting)
**When** the threshold is passed
**Then** the founder is alerted (FR-53).

*For the accountant: whether VAT applies to a Credit Note is unconfirmed; the split is stored as computed.*

### Story 8.8: An Order becomes Paid and its stock is reserved

As an Owner or Manager,
I want my order to become paid the moment my payments cover it and my goods to be set aside,
So that what I paid for cannot be sold to someone else.

**Requirements:** FR-17, FR-18, FR-42; NFR-10; AD-8; edge-case rows 22, 23

**Acceptance Criteria:**

**Given** an Order whose confirmed Payments, with any Credit Note applied, cover its Amount Due
**When** the last confirmation commits
**Then** the Order becomes Paid in the same transaction (FR-42).

**Given** confirmed Payments that do not cover the Amount Due
**When** one is confirmed
**Then** the Order stays Awaiting Payment and shows part paid.

**Given** an Order becoming Paid
**When** stock is reserved
**Then** `Catalogue` reserves each in-stock line under the stock-row lock, only if reserved plus the quantity does not exceed on hand, after `AssertSellable` passes.

**Given** two Orders confirmed together for the last unit
**When** both commit
**Then** one holds the reservation and the other's line is credited (edge-case row 23).

**Given** stock that covers only part of a line
**When** the Order becomes Paid
**Then** what exists is reserved, only the missing quantity is credited through a draft Credit Note for Finance, and the Action returns the partial outcome explicitly (FR-18).

**Given** a Back-order line
**When** the Order becomes Paid
**Then** nothing is reserved for it and its promised ship-by date is the confirmation date plus its captured Lead Time (FR-17).

**Given** a Back-order line whose Product had back-order switched off before the Order was Paid
**When** the Order becomes Paid
**Then** that line is credited and the business is told.

**Given** any Order Line
**When** its Order is not Paid
**Then** it cannot be marked as ordered from the Supplier.

**Given** this transition
**When** the test suite runs
**Then** it has a two-racer test and a replay test.

### Story 8.9: Overpayments and the underpayment tolerance

As a Finance officer,
I want small shortfalls absorbed and overpayments returned as credit,
So that bank charges do not hold up an order and nobody loses money they sent.

**Requirements:** FR-38, FR-42; edge-case row 47

**Acceptance Criteria:**

**Given** a confirmed transfer larger than what is still to pay
**When** it is confirmed
**Then** a draft Credit Note for the overpayment is created for Finance to issue, with a refundable reason.

**Given** the underpayment tolerance setting
**When** the system is set up
**Then** it is a placeholder of ₦200.00 that Finance can set.

**Given** a shortfall inside the tolerance
**When** the Payment is confirmed
**Then** the Order counts as covered, and the shortfall is written off and audited (edge-case row 47).

**Given** a change to the tolerance
**When** Finance submits it
**Then** it waits on the Approvals page for a second person.

### Story 8.10: The Price Lock and repricing

As an Owner or Manager,
I want my price kept when I pay in time, and a clear new amount when I do not,
So that a move in the naira never surprises me.

**Requirements:** FR-35, FR-38; AD-7; edge-case rows 42, 43, 44; test design R-04

**Acceptance Criteria:**

**Given** a Receipt
**When** the server recorded its upload start within 24 hours of the Order's creation
**Then** it is in time, judged by the server's clock and never the phone's (edge-case row 44).

**Given** every in-time Receipt has been decided by Finance
**When** the confirmed in-time Payments cover the Amount Due within the tolerance, and each matched bank credit's value time is earlier than its Receipt's upload time
**Then** the Price Lock holds.

**Given** a correct Receipt uploaded at hour 20 and confirmed at hour 30
**When** it is confirmed
**Then** the Order keeps its locked price (edge-case row 43).

**Given** the lock test fails, or payment arrives only between hours 24 and 48
**When** every in-time Receipt has been decided, or the first Payment is confirmed where none was in time
**Then** the Order is repriced once through `RepriceSnapshot`, changing only the exchange rate by the FR-35 rule, with tier, markup, VAT and delivery as captured.

**Given** an Order of USD 500 of goods at NGN 1,330 with NGN 600,000.00 confirmed in time
**When** the remainder is paid at hour 30 with the rate at NGN 1,400
**Then** the Amount Due becomes NGN 752,500.00 and the balance NGN 152,500.00.

**Given** a repriced Order
**When** it is stored
**Then** it keeps both its original and its repriced snapshot, each stamped with the instant used, and is never repriced again.

**Given** repricing leaves the business overpaid
**When** it commits
**Then** a draft Credit Note is created for the difference.

**Given** a repriced Order
**When** the business opens it
**Then** a card says "Price no longer held" with the new amount due, confirmed so far and still to pay.

**Given** Finance reviewing a Receipt that is late for the price hold
**When** the review loads
**Then** it shows a repricing preview.

**Given** the FR-38 rules
**When** they are tested
**Then** they are written as one decision table covering in time, late, straddling a boundary, a corrected re-upload, the underpayment tolerance and a bank credit whose value time is after the upload, and each row is driven by the controlled Clock and the fake statement import (test design R-04).

### Story 8.11: An Order with Receipts expires

As an Owner or Manager,
I want an order I never finished paying to end cleanly with my money kept as credit,
So that nothing I sent is lost.

**Requirements:** FR-38, FR-43; edge-case rows 45, 54

**Acceptance Criteria:**

**Given** an Order with at least one Receipt
**When** its expiry deadline is set
**Then** it is 48 hours after the last Receipt upload or Unconfirmed decision, and never later than 7 days after creation (edge-case row 45).

**Given** that deadline passes
**When** the sweep acts
**Then** the Order moves to Expired.

**Given** an Expired Order with confirmed Payments
**When** it expires
**Then** a Credit Note is issued for the confirmed amount in the same transaction.

**Given** a Credit Note that had been applied to the Order
**When** the Order expires
**Then** the amount returns to the original note with its original expiry.

**Given** a Receipt still Submitted at expiry, or a bank credit that arrives later
**When** it is found
**Then** it goes to Finance's unmatched list, and the Expired Order never changes state by itself (edge-case row 54).

**Given** an Order fully covered by a Credit Note application
**When** its clock runs
**Then** it does not expire for lack of a Receipt.

**Given** an Expired Order
**When** the business opens it
**Then** the card says the money received is kept as a credit note.

### Story 8.12: A second reviewer, and one transfer for two Orders

As a Finance officer,
I want a second pair of eyes on a payment from an unexpected sender, and a way to split one transfer,
So that unusual payments are handled correctly and safely.

**Requirements:** FR-41; AD-10; edge-case row 55

**Acceptance Criteria:**

**Given** a Receipt whose sender name does not match the Owner or the Trade Name
**When** Finance reviews it
**Then** "Send to a second reviewer…" is required before it can be confirmed.

**Given** a Payment sent for second review
**When** the second reviewer opens it
**Then** they must be a different person, enforced by the approval mechanism.

**Given** one bank credit that covers two Orders
**When** Finance splits it
**Then** each part is an allocation record, the split needs a second approver, and the parts can never exceed the credit's amount.

**Given** the same transfer evidenced on a second Receipt
**When** confirmation is attempted
**Then** it is refused because the bank reference is already used (edge-case row 55).

### Story 8.13: Unmatched credits

As a Finance officer,
I want a daily list of money and Receipts that match nothing,
So that no payment sits unexplained.

**Requirements:** FR-41, FR-72; edge-case row 54

**Acceptance Criteria:**

**Given** Unmatched credits
**When** Finance opens it
**Then** it lists bank credits with no Payment, and Receipts for Orders that are Expired, Cancelled or already Paid, each with its likely answer.

**Given** any item on the list
**When** time passes
**Then** nothing is applied automatically.

**Given** an unmatched credit resolved with a Credit Note
**When** Finance issues it
**Then** the statement line is allocated to the Credit Note, and a second approver is needed above the set amount.

**Given** a credit from an unknown sender
**When** Finance presses "Hold and ask the bank"
**Then** it is held with a note.

**Given** a bank credit or a Receipt from a blocklisted sending account
**When** it is listed or its confirmation is attempted
**Then** it is held in Unmatched credits marked blocked, and confirmation is refused (FR-72).

**Given** Finance's Today page
**When** it loads
**Then** it has a card for Unmatched credits.

### Story 8.14: Finance reverses a Payment

As a Finance officer,
I want to reverse a Payment that was confirmed in error,
So that the records are corrected without rewriting them.

**Requirements:** FR-41, FR-53; edge-case row 56

**Acceptance Criteria:**

**Given** a Confirmed Payment
**When** Finance asks to reverse it with a reason
**Then** it waits on the Approvals page for a second person.

**Given** an approved reversal
**When** it commits
**Then** the Payment moves to Reversed, and the correction is made of compensating ledger and allocation rows that point at the originals.

**Given** a Paid Order that has not been Approved
**When** one of its Payments is reversed
**Then** the Order returns to Awaiting Payment, its reservations are released, and Approval is blocked until it is reviewed (FR-41).

**Given** a reversal
**When** the week's review is opened
**Then** it is listed for the founder.

**Given** a reversal racing another decision on the same Payment
**When** both arrive
**Then** one wins by compare-and-set (edge-case row 56).

### Story 8.15: Cancel an Order that holds money

As an Owner or Manager,
I want to cancel an order I have already paid toward and keep what I paid as credit,
So that a client changing their mind does not cost me.

**Requirements:** FR-57; edge-case rows 50, 51, 57

**Acceptance Criteria:**

**Given** an Order with confirmed Payments that has not been Approved
**When** an Owner or Manager cancels it
**Then** the dialog lists the consequences, including that the amount becomes a credit note and is not paid out to the bank.

**Given** a confirmed cancellation
**When** it commits
**Then** the Order moves to Cancelled, a credit-only Credit Note for the confirmed amount is issued by itself, and any reservations are released.

**Given** an Order with a Payment still under review
**When** the person opens the actions
**Then** the action reads "Ask finance to cancel", and the Order is cancelled only when Finance consents.

**Given** Receipts not yet checked at cancellation
**When** the Order is cancelled
**Then** they go to Finance.

**Given** a Credit Note that had been applied to the Order
**When** it is cancelled
**Then** the amount returns to the original note with its original expiry (edge-case row 57).

**Given** a cancellation racing a final confirmation
**When** both arrive
**Then** one transition happens and the other receives the current status.

### Story 8.16: Use a Credit Note on an Order

As an Owner or Manager,
I want to put my credit toward a new order,
So that money you owe me reduces what I must transfer.

**Requirements:** FR-43, FR-71; edge-case row 57

**Acceptance Criteria:**

**Given** a business with available credit
**When** an Owner or Manager opens an order's payment screen
**Then** a "Use some on this order" row is shown, and without credit it is not.

**Given** the request screen
**When** it loads
**Then** it suggests the note that expires first, shows what will still be owed by transfer, and takes one note per request.

**Given** the person confirming
**When** every amount has been written out in full
**Then** a fresh one-time code is required.

**Given** an accepted request
**When** it commits
**Then** a hold entry is written under the Credit Note lock, and it counts as a Payment of its amount from that moment for the price hold and for expiry (FR-43).

**Given** two requests against one note at the same moment
**When** both arrive
**Then** together they never exceed the note's balance.

**Given** Finance's Credit note requests queue
**When** a request arrives
**Then** it has a 1 Business Day target, and the review shows written checks: belongs to the business, the balance covers it, not expired on the day asked, the order still awaiting payment, your own accounts, and second person.

**Given** Finance pressing Apply
**When** it commits
**Then** an apply entry completes the hold, judged as the request stood when it was made.

**Given** Finance declining
**When** they give a reason
**Then** a release entry frees the hold and the business reads the reason.

### Story 8.17: The Owner asks for a refund

As an Owner,
I want to ask for refundable credit to be sent back to my bank,
So that money that is truly mine returns to me.

**Requirements:** FR-43, FR-71, FR-72; edge-case row 58

**Acceptance Criteria:**

**Given** a Credit Note whose reason is an overpayment, an unfulfillable line, a platform-fault return, freight reimbursement, a counterfeit or a non-delivery
**When** the Owner opens it
**Then** "Ask for a refund" is shown.

**Given** a Credit Note for a cancelled Order, a change-of-mind return or an uncollected Pick-up
**When** anyone opens it
**Then** it reads credit only and no refund action is shown.

**Given** a Manager
**When** they open a refundable note
**Then** it says "Only the Owner can ask for a refund."

**Given** the refund request
**When** the Owner makes it
**Then** it is for the whole balance only, to a sending account picked from those recorded on the business's confirmed Payments and never typed, with a fresh one-time code.

**Given** an accepted request
**When** it waits
**Then** the balance is held and the note shows a banner, not a new status.

**Given** Finance's review
**When** it loads
**Then** it shows the checks (the reason allows a refund, the account has paid us, a fresh code, no hold or blocklist, your own accounts, second person) and a two-step panel.

**Given** the two-step panel
**When** Finance has sent the money in the bank's own portal
**Then** "Mark as refunded" stays off until the bank reference is entered, and the platform itself moves no money.

**Given** a business on Hold
**When** a refund is requested or processed
**Then** it is refused.

**Given** an application and a refund requested on one note at the same moment
**When** both arrive
**Then** the balance is reduced in one transaction and never overspent (edge-case row 58).

### Story 8.18: Credit Notes expire, with warning

As an Owner,
I want to be warned before my credit lapses,
So that I use it or ask for it back in time.

**Requirements:** FR-43; NFR-9

**Acceptance Criteria:**

**Given** a Credit Note
**When** it is issued
**Then** reminder deadlines 60 and 14 days before its expiry are stored as due-and-sent pairs.

**Given** a reminder falls due
**When** the sweep acts
**Then** an event is written for the Owner, and the note appears on Home under "Needs your attention".

**Given** 12 months have passed since issue
**When** the sweep acts
**Then** an expire entry is written and the note can no longer be applied.

**Given** an expired note with a balance
**When** the Owner opens it
**Then** it says the balance can be refunded on request, using placeholder wording until counsel confirms.

**Given** an application made before expiry
**When** Finance completes it after expiry
**Then** it is honoured, because validity is judged at the time of the request.

## Epic 9: Approve, dispatch and track deliveries

Operations approves Paid Orders after a shelf check and ships them in Shipments with serial numbers recorded; the Integrator follows each to Delivered or Signed For, and the Order completes.

### Story 9.1: Operations approves a Paid Order

As an Operations officer,
I want to check the shelf and approve each Paid Order,
So that we only promise what we can physically send.

**Requirements:** FR-44, FR-78; AD-25; UX-DR48

**Acceptance Criteria:**

**Given** an Order becoming Paid
**When** that transition commits
**Then** a work item joins the Order approvals queue with a 4 Business Hour target.

**Given** Operations opening the queue
**When** an order is selected
**Then** the list is on the left and the order on the right with its delivery, receiver and parts-or-full choice, and a lines table of ordered, reserved and left after.

**Given** each in-stock line
**When** Operations checks the shelf
**Then** they record "Found on the shelf" or "Not found: credit this line"; back-order lines are not shelf-checked here (Story 9.3 marks them).

**Given** any in-stock line neither found nor credited
**When** Operations looks at "Approve order"
**Then** it is the only primary button, visible, dashed and off, with its reason.

**Given** every in-stock line found or credited
**When** Operations approves
**Then** `Orders`' `ApproveOrder` runs inside the Fulfilment Action, the Order becomes Approved, and its Shipments move to Processing (AD-25)
**And** the screen says what happens next and an `OrderApproved` event is written.

**Given** an Order that is not Paid, or whose Payment reversal is awaiting review
**When** approval is attempted
**Then** it is refused (FR-44).

**Given** an approval racing a cancellation
**When** both arrive
**Then** one transition happens and the other receives the current status.

**Given** Operations' Today page
**When** it loads
**Then** it has a card for Order approvals with its count and oldest age.

### Story 9.2: Credit a line that is not on the shelf

As an Operations officer,
I want to credit a line we cannot find and still approve the rest,
So that one missing item does not hold up a whole order.

**Requirements:** FR-44, FR-57; edge-case rows 27, 49

**Acceptance Criteria:**

**Given** a line marked "Not found: credit this line"
**When** Operations confirms
**Then** `Orders`' `CreditOrderLine` cancels the line, `Catalogue` releases its reservation, and a Credit Note request for an unfulfillable line goes to Finance with the amount worked out by `Pricing`.

**Given** a credited line
**When** it commits
**Then** the business is told and the rest of the Order can be approved (edge-case row 27).

**Given** an Order whose every line has been credited
**When** the last is credited
**Then** the Order becomes Cancelled with a Credit Note for the full confirmed amount, and is never left Paid with no Shipments.

**Given** a credit that empties a Shipment
**When** it commits
**Then** that Shipment's delivery charge is returned in the credit (edge-case row 49).

### Story 9.3: Back-order lines: ordered from the Supplier, and received

As an Operations officer,
I want to mark when a back-order line has been ordered and when it has arrived,
So that everyone knows where a waiting item stands.

**Requirements:** FR-17, FR-74; edge-case row 25

**Acceptance Criteria:**

**Given** a back-order line on a Paid Order
**When** Operations marks it "ordered from Supplier"
**Then** the marker is set with who and when, through an `Orders` Action.

**Given** a back-order line
**When** Operations marks it received
**Then** `Catalogue`'s `AssertGoodsIn` must find a goods-in record, or the mark is refused (FR-74).

**Given** arriving stock that is less than the Paid demand for that Product
**When** it is allocated
**Then** it goes to Orders first-paid first-served, a partial Shipment is made where the Order ships in parts, and the rest are told (edge-case row 25).

**Given** the Back-orders list
**When** Operations opens it
**Then** each line shows its promised ship-by date, its two markers and a "Running late" flag once that date has passed, with the business's 7-day cancel right noted.

*Note: there is no Supplier order record in version 1 (PRD §8.3 question 16).*

### Story 9.4: Dispatch a delivery

As an Operations officer,
I want to record what leaves the Store, with its serial numbers and courier,
So that we can prove every device we sent and the Integrator can track it.

**Requirements:** FR-45, FR-56, FR-65; AD-8; edge-case row 64

**Acceptance Criteria:**

**Given** a Delivery Shipment in Processing
**When** Operations opens it
**Then** it shows its contents, its dispatch Store (the Main Store unless another is chosen) and a status timeline that offers only the next allowed move.

**Given** a serialised Product in the Shipment
**When** Operations scans or pastes its serial numbers
**Then** the count must equal the shipped quantity, each is normalised for case and separators and must match a serial recorded at goods-in for that Product, and a model-specific format is checked where the Product defines one (FR-65).

**Given** a serial already on another active dispatch
**When** it is entered
**Then** it is refused by a partial unique constraint (edge-case row 64).

**Given** a Shipment with a serialised Product and no serial numbers
**When** dispatch is attempted
**Then** it is refused.

**Given** dispatch
**When** Operations moves the Shipment to In-transit
**Then** the courier's name and a tracking reference are required, `AssertSellable` passes for each line, and `Catalogue` commits the reserved stock with a ledger entry.

**Given** a dispatched Shipment
**When** it commits
**Then** the dispatch date is stored for the warranty, what the courier charged us can be recorded for reporting only, and a `ShipmentDispatched` event is written.

**Given** a Finance account, or any Integrator
**When** it tries to change a Shipment's status
**Then** the server refuses.

### Story 9.5: A delivery is delivered

As an Operations officer,
I want to mark a delivery as delivered only with proof,
So that "Delivered" always means the goods reached the receiver.

**Requirements:** FR-45

**Acceptance Criteria:**

**Given** a Shipment going In-transit
**When** it commits
**Then** a one-time receiver's code is made and shown on the business's order page for the receiver to give the courier.

**Given** a Shipment In-transit
**When** Operations marks it Delivered
**Then** either the courier's proof reference or the receiver's code is required.

**Given** a Shipment in Processing
**When** anyone tries to move it straight to Delivered
**Then** the move is not offered and the server refuses it (FR-45).

**Given** a Delivered Shipment
**When** it commits
**Then** the courier-confirmed delivery date is stored, and `Orders`' `EvaluateCompletion` is called in the same Action.

*Default: how the receiver's code is made and sent is open (PRD §8.3 question 12); this story shows it on the order page.*

### Story 9.6: Pick-up: ready and signed for

As a Manager,
I want to know when my order is ready and collect it with my ID,
So that I do not make a wasted trip.

**Requirements:** FR-45, FR-64, FR-65

**Acceptance Criteria:**

**Given** a Pick-up Shipment in Processing
**When** Operations marks it Ready for Pickup
**Then** they confirm the goods are physically at the Store, and serial numbers for serialised Products are recorded under the same rules as Story 9.4.

**Given** a Shipment Ready for Pickup
**When** the business opens the order
**Then** a card shows the Store's address and hours and says the collector needs their name and ID.

**Given** a collection
**When** Operations marks the Shipment Signed For
**Then** the collector's name and the type and last four digits of their ID are recorded, and stock is committed.

**Given** a Pick-up Shipment
**When** it becomes Ready for Pickup
**Then** that date starts the warranty, and the reminder deadlines of Story 9.9 are stored.

**Given** a Signed For Shipment
**When** it commits
**Then** `EvaluateCompletion` is called.

### Story 9.7: Follow an order's deliveries

As an Owner, Manager or Technician,
I want to see each delivery of an order and where it is,
So that I can plan the install around what has arrived.

**Requirements:** FR-46

**Acceptance Criteria:**

**Given** an Approved Order
**When** an Owner or Manager opens it
**Then** Deliveries come first, each with its own neutral status label and timestamp, its items, the courier and tracking reference with Copy, and a serial-numbers row.

**Given** a delivery's history
**When** it is shown
**Then** it is a vertical timeline with done and now marked and the date of each stage, announced as an ordered list.

**Given** a Delivered delivery
**When** its card is shown
**Then** it says to report damage or a wrong item within 5 days.

**Given** a Technician
**When** they open the order
**Then** they see status, deliveries and serial numbers, and no amounts, payments, bank details or documents (FR-46).

**Given** an order whose deliveries are all finished
**When** the Orders list loads
**Then** it is under Done.

### Story 9.8: When a delivery goes wrong

As an Owner or Manager,
I want to know when a delivery has failed or gone missing and what happens next,
So that I can help put it right.

**Requirements:** FR-45; edge-case row 60

**Acceptance Criteria:**

**Given** a Shipment In-transit
**When** Operations marks it Delivery Failed, Returned to Store or Lost
**Then** only those listed moves are offered, and each is recorded with who and when.

**Given** a Delivery Failed Shipment
**When** the business opens the order
**Then** a card offers "Update receiver details", after which the Shipment returns to Processing and is sent again.

**Given** any Shipment that is Lost or Delivery Failed
**When** completion is evaluated
**Then** the Order cannot become Completed.

**Given** a Lost Shipment, or one In-transit longer than a set number of days (a placeholder setting)
**When** it is detected
**Then** a flagged item is raised for Operations.

**Given** goods carried by our courier partner
**When** they are lost before Delivered
**Then** the platform bears the loss.

*Note: the automatic non-delivery Case replaces the flagged item in Epic 13.*

### Story 9.9: A pick-up nobody collects

As an Operations officer,
I want uncollected orders chased and, in the end, put back on the shelf,
So that stock is not tied up indefinitely.

**Requirements:** FR-45; edge-case row 61

**Acceptance Criteria:**

**Given** a Shipment Ready for Pickup for 7 days
**When** the sweep acts
**Then** a reminder event is written for the business, once.

**Given** 14 days
**When** the sweep acts
**Then** the Shipment is marked Uncollected and the business's order shows it.

**Given** 30 days
**When** the sweep acts
**Then** the Shipment is cancelled, `Catalogue` restocks the goods with a ledger entry, and a credit-only Credit Note is issued for the price paid less the restocking fee worked out by `Pricing` (FR-45).

**Given** each of these
**When** the timer Action runs
**Then** it re-reads its deadline under lock and does nothing if the goods were collected first.

*Note: this follows the PRD, where the 30-day cancellation is automatic. `EXPERIENCE.md` says it "may be" cancelled (discrepancy 3).*

### Story 9.10: An Order completes

As an Owner,
I want a fully delivered order to count toward my standing,
So that buying through the platform earns me vouching rights and, later, better prices.

**Requirements:** FR-5, FR-21, FR-45; AD-25; edge-case rows 13, 62

**Acceptance Criteria:**

**Given** an Order
**When** `EvaluateCompletion` runs
**Then** it becomes Completed only if every line that has not been credited sits in a Shipment that is Delivered or Signed For, and at least one such Shipment exists (edge-case row 62).

**Given** replacement Shipments
**When** completion, spend or counts are worked out
**Then** they are excluded.

**Given** `Orders`' `NetDeliveredGoodsQuery`
**When** it is published
**Then** it returns the pre-VAT, pre-delivery goods value of delivered lines in USD at each Order's stored rate, net of credited lines, and is the only source of spend for any module (AD-25).

**Given** a business with at least 3 Completed Orders, each with goods value of at least USD 100 (a setting), none with an open Return Request, and vouching rights not paused
**When** Established status is checked
**Then** it is an Established Integrator, checked again when a Vouch Code is made and when it is submitted (edge-case row 13).

**Given** a business with 2 such Orders
**When** its Owner opens Vouch codes
**Then** it is not established, and its progress is a list of orders marked counted, too small to count, or has an open return, with no dollar figure.

**Given** a business granted vouching rights by Admin
**When** Established status is checked
**Then** it is still established.

### Story 9.11: Ask to cancel after Approval

As an Owner or Manager,
I want to ask to cancel a delivery that has not left yet,
So that I am not sent goods my client no longer wants.

**Requirements:** FR-57; edge-case rows 26, 52, 53

**Acceptance Criteria:**

**Given** an Approved Order with a delivery not yet sent
**When** the person presses "Ask to cancel"
**Then** the request is for one delivery at a time and a reason is required.

**Given** a cancellation request
**When** it is made
**Then** that delivery's status moves are frozen until Operations decides, and a work item with a 1 Business Day target is opened (edge-case row 52).

**Given** a delivery that holds a back-order item already ordered from the Supplier
**When** the person looks at it
**Then** it says that item cannot be cancelled, and the rest can.

**Given** Operations agreeing
**When** it commits
**Then** the delivery is cancelled, its lines are credited, reservations are released and a credit-only Credit Note is issued.

**Given** Operations declining
**When** they give a reason
**Then** the business reads it and the delivery is unfrozen.

**Given** a back-order line more than 7 days past its promised ship-by date
**When** the person presses "Cancel this item"
**Then** it is cancelled at once for a Credit Note, whether or not it has been ordered from the Supplier (FR-57).

**Given** a delivery that is In-transit or Delivered
**When** the person opens it
**Then** cancelling is not offered.

### Story 9.12: Ask to change a Paid Order's delivery

As an Owner or Manager,
I want to ask for a delivery change after I have paid,
So that a late change of site or receiver can still be handled.

**Requirements:** FR-37, FR-75

**Acceptance Criteria:**

**Given** a Paid Order that has not been Approved
**When** the person presses "Change delivery details"
**Then** it is sent as a request to Operations and says by what exact time it will be answered.

**Given** a change request
**When** it is opened
**Then** it joins Operations' work with the 4 Business Hour approval target (FR-75).

**Given** Operations agreeing to a change that lowers or keeps the delivery charge
**When** it commits
**Then** only the delivery charge changes, any difference is returned in a Credit Note, and no goods price or price-hold deadline moves.

**Given** a change that would raise the delivery charge
**When** Operations answers
**Then** the business is shown the extra amount to pay before the change is agreed.

**Given** Operations declining
**When** they give a reason
**Then** the business reads it.

*Assumption: the PRD does not say what happens when a change on a Paid Order costs more; this story asks the business to pay the difference first.*

### Story 9.13: Shipping in full has waited 14 days

As an Owner or Manager,
I want the option to take what is ready when a full order has waited two weeks,
So that one late item does not hold up my whole job.

**Requirements:** FR-39

**Acceptance Criteria:**

**Given** an Order set to ship in full
**When** it is Approved
**Then** a deadline 14 days later is stored for its in-stock lines.

**Given** that deadline passes with lines still waiting
**When** the sweep acts
**Then** the business is offered "Send what is ready now?" on the order and on Home.

**Given** the offer
**When** the business accepts
**Then** it pays the extra delivery at the Zone Rate before anything is sent, and a new Shipment is made for what is ready (FR-39).

**Given** the offer
**When** the business declines
**Then** the Order waits for everything, and no charge changes.

### Story 9.14: Correct a serial number or reverse a status

As an Operations officer,
I want mistakes in serial numbers and statuses corrected under control,
So that the record stays trustworthy.

**Requirements:** FR-45, FR-53, FR-65; edge-case rows 59, 64

**Acceptance Criteria:**

**Given** a recorded serial number that is wrong
**When** Operations asks to correct it with a reason
**Then** it waits on the Approvals page for a second person.

**Given** an approved correction
**When** it commits
**Then** the old and new values are kept, and it is listed in the founder's weekly review.

**Given** a Shipment status that must be undone
**When** an Admin reverses it with a reason
**Then** the reversal is recorded and audited, and no other role can do it (edge-case row 59).

**Given** a status move that is not in the transition table
**When** anyone attempts it
**Then** it is refused.

## Epic 10: Tax invoices and credit-note documents

Every Paid Order has one numbered tax invoice and every Credit Note a document, downloadable by Owners and Managers.

*All four stories carry the PRD's stated fields. The accountant's answers on fields, numbering format, serial numbers and VAT on delivery and Credit Notes change these acceptance criteria, not the structure.*

### Story 10.1: A tax invoice for every Paid Order

As an Owner or Manager,
I want a numbered tax invoice for every order I pay for,
So that my business can account for its purchases and reclaim VAT.

**Requirements:** FR-77; AD-9

**Acceptance Criteria:**

**Given** an Order becoming Paid
**When** that transition commits
**Then** a tax invoice is written in the same transaction as a structured record holding the buyer's Trade Name, the TIN if given, each line with its quantity, unit price and VAT, the delivery charge with its VAT, the Order reference and the issue date.

**Given** a tax invoice
**When** its number is taken
**Then** it comes from a counter row for the invoice series locked last in the global lock order, so numbers are sequential with no gaps and no duplicates (AD-9).

**Given** an Order that already has an invoice
**When** a second one is attempted
**Then** a unique constraint refuses it (FR-77).

**Given** the VAT figures on an invoice
**When** they are worked out
**Then** `Pricing` computes them, and the lines and VAT add up to the Amount Due to the kobo.

**Given** an issued invoice
**When** its PDF is rendered
**Then** it is rendered on the queue through `PdfRenderer` from the invoice record, stored as a private document that is never overwritten, and tagged for accessibility.

**Given** an Owner or Manager on the order page
**When** Documents is shown
**Then** the tax invoice can be downloaded.

**Given** a Technician
**When** they open the order
**Then** no document is shown.

**Given** an invoice record
**When** it is stored
**Then** it is a structured record from which an e-invoicing feed can later be produced without changing it.

### Story 10.2: A document for every Credit Note

As an Owner or Manager,
I want a document for every Credit Note,
So that each credit can be matched to the invoice it adjusts.

**Requirements:** FR-43, FR-77; AD-9

**Acceptance Criteria:**

**Given** a Credit Note being issued
**When** the issue entry commits
**Then** a credit-note document record is written with a number from its own gapless series, its reason, and its goods and VAT split.

**Given** a Credit Note whose source Order has a tax invoice
**When** its document is written
**Then** it names that invoice, and otherwise it names the Order reference (FR-77).

**Given** a credit-note document
**When** its PDF is rendered
**Then** it follows the Proposal PDF's style, is private, and is tagged for accessibility.

**Given** an Owner or Manager on a Credit Note
**When** it loads
**Then** its PDF can be downloaded, and Finance can download it from the console.

### Story 10.3: Invoices after a reversal

As a Finance officer,
I want a reversed payment to leave a clean document trail,
So that our invoices always reconcile with what was actually paid.

**Requirements:** FR-41, FR-77

**Acceptance Criteria:**

**Given** a Payment reversed on an Order that has a tax invoice
**When** the reversal commits
**Then** a credit-note document is issued that names that invoice.

**Given** that Order becoming Paid again
**When** it commits
**Then** a new tax invoice is issued with a new number.

**Given** any issued invoice or credit-note document
**When** anyone tries to edit or delete it
**Then** no such action exists, and corrections are always new documents.

### Story 10.4: A delivery note with serial numbers

As an Owner or Manager,
I want a note of what each delivery contained, with serial numbers,
So that I can check the goods and register warranties.

**Requirements:** FR-65, FR-77

**Acceptance Criteria:**

**Given** a Shipment moving to In-transit or Ready for Pickup
**When** that commits
**Then** a delivery note record is written with a number from its own series, listing each item, its quantity and the serial numbers recorded.

**Given** a delivery note
**When** its PDF is rendered
**Then** it carries no amounts, is private and is tagged for accessibility.

**Given** an Owner or Manager on the order page
**When** Documents is shown
**Then** each delivery's note can be downloaded.

*Assumption: serial numbers go on a delivery note, not the invoice. If the accountant wants them on the invoice, this story folds into Story 10.1.*

## Epic 11: WhatsApp and email notifications

Integrators and staff are told about the launch events by WhatsApp and email, with amounts only for Owners and Managers.

### Story 11.1: Notification settings

As an Owner, Manager or Technician,
I want to choose whether my updates come on WhatsApp,
So that I hear about my orders where I already look.

**Requirements:** FR-51, FR-69, FR-71

**Acceptance Criteria:**

**Given** any User on Account, Notifications
**When** it loads
**Then** it shows switches per channel only: WhatsApp on or off per number in words, and email always on, with no per-message switches and no adverts.

**Given** the WhatsApp switch
**When** it is shown
**Then** a note says an undelivered message is emailed after 15 minutes.

**Given** a User adding a WhatsApp number
**When** they start
**Then** a fresh one-time code is required, then the number is verified with its own code, then the opt-in question is asked on its own screen.

**Given** a User turning WhatsApp off
**When** they do
**Then** the screen warns that time-sensitive messages may be slower, and the opt-out is recorded at once.

**Given** a Technician
**When** they open Notifications
**Then** the kinds of message are listed with no amounts.

**Given** any message the platform sends
**When** it is read
**Then** its footer links to these settings.

### Story 11.2: Messages by email

As an Owner,
I want clear emails about the things that matter on my orders,
So that I can act without opening the app to check.

**Requirements:** FR-51; AD-4, AD-13, AD-15; edge-case row 66; UX-DR35

**Acceptance Criteria:**

**Given** the notification matrix
**When** it is set up
**Then** it is data in the `Notifications` module mapping each event to its recipient roles, channels and template.

**Given** an event in the outbox
**When** its listener runs
**Then** it is idempotent on the event id, and records a delivery key of event, channel and recipient under a unique constraint before calling the provider (AD-4).

**Given** an event older than the last one sent for the same Order, Proposal or Case
**When** it arrives
**Then** it is dropped.

**Given** a message for a recipient
**When** it is rendered
**Then** it is rendered for that person through the field-visibility registry, using the facts captured when the event was emitted, so a Technician's message has no amount (FR-51).

**Given** a removed or disabled User
**When** an event would reach them
**Then** nothing is sent.

**Given** any message
**When** it is read
**Then** it names the Order, Proposal or Case first, then what happened, then what to do, with one link, plain text first, signed "integrator", with no exclamation marks and the PRD's own literals unchanged.

**Given** every send
**When** it completes or fails
**Then** it is recorded in a delivery log.

**Given** the email provider
**When** the application runs in staging or tests
**Then** it is reached through an interface bound to a fake.

### Story 11.3: Messages by WhatsApp, with an email fallback

As an Owner,
I want urgent updates on WhatsApp, and by email if WhatsApp fails,
So that I never miss a deadline because one channel is down.

**Requirements:** FR-51, FR-69; edge-case row 66

**Acceptance Criteria:**

**Given** a time-sensitive event for a User with a recorded opt-in
**When** it is sent
**Then** it goes by WhatsApp first, using a template approved for that event.

**Given** a number with no recorded opt-in
**When** a WhatsApp message would be sent to it
**Then** it is not sent (FR-69).

**Given** a WhatsApp message
**When** it is rendered
**Then** it is under 300 characters and a Technician's carries no amount.

**Given** a WhatsApp message not delivered within 15 minutes, or a failed send
**When** the deadline stored on it passes
**Then** the sweep sends the same message by email.

**Given** the WhatsApp provider
**When** the application runs in staging or tests
**Then** it is reached through an interface bound to a fake.

### Story 11.4: At once, or at 9 am

As an Owner,
I want to be woken only for things that protect my money,
So that routine news waits until morning.

**Requirements:** FR-51; AD-5

**Acceptance Criteria:**

**Given** an event that protects money or a deadline (Payment unconfirmed, the price hold at hour 20, expiry at hour 40, an Issue Code at hour 36)
**When** it is emitted at any hour
**Then** it is sent at once.

**Given** any other event
**When** it is emitted outside 9 am to 9 pm West Africa Time
**Then** a send time of the next 9 am is stored and the sweep sends it then.

**Given** a held message whose subject has since changed
**When** it is due
**Then** it is dropped if a later message for the same subject has been sent.

### Story 11.5: Order and payment messages

As an Owner or Manager,
I want to be told when my payment, price or delivery changes,
So that I can act in time.

**Requirements:** FR-38, FR-41, FR-51

**Acceptance Criteria:**

**Given** an Order being created
**When** it commits
**Then** reminder deadlines at hour 20 and hour 40 are stored on it as due-and-sent pairs.

**Given** hour 20 arrives on an Order whose price hold is not yet secured
**When** the sweep acts
**Then** the Owner and Managers are told the price hold ends in 4 hours, with the exact time, and nothing is sent if the Order is already Paid, Cancelled or Expired.

**Given** hour 40 arrives on an Order still unpaid
**When** the sweep acts
**Then** the Owner and Managers are told it will be cancelled at hour 48.

**Given** a Payment marked Unconfirmed
**When** the event is sent
**Then** it carries the PRD's literal sentence, "Payment unconfirmed: please upload your receipt again or pay the full amount."

**Given** a repriced Order
**When** the event is sent
**Then** it states the new Amount Due and the balance, or the draft Credit Note for an overpayment.

**Given** an Order Paid and Approved, or a Shipment dispatched or Ready for Pickup
**When** the event is sent
**Then** Owners and Managers are told with the amounts and tracking, and a Technician is told the status and tracking only.

### Story 11.6: Account, credit and back-order messages

As an Owner,
I want to hear about decisions on my business and my credit,
So that I am never left waiting without knowing.

**Requirements:** FR-4, FR-7, FR-43, FR-51

**Acceptance Criteria:**

**Given** a NIN or Level 3 decision
**When** it is made
**Then** the Owner is told the outcome and any public reason, with no amount.

**Given** a Credit Note issued, or its 60-day or 14-day reminder falling due
**When** the event is sent
**Then** the Owner and Managers are told the amount, the balance and the last day.

**Given** a back-order line that passes its promised ship-by date
**When** it is detected
**Then** the Owner and Managers are told it is running late and that they can cancel it for a Credit Note after 7 more days.

### Story 11.7: Staff alerts on WhatsApp

As a Finance officer,
I want to be alerted on WhatsApp when work arrives in my queue,
So that a Receipt never waits because I was away from my desk.

**Requirements:** FR-76, FR-78

**Acceptance Criteria:**

**Given** a staff member with a verified WhatsApp number and a recorded opt-in
**When** an item arrives in a queue they own
**Then** they are alerted by WhatsApp as well as email.

**Given** a staff member who is out of office
**When** an item arrives
**Then** the alert goes to their deputy.

**Given** an item escalated at 75% of its target
**When** the escalation happens
**Then** Admin is alerted on the same channels.

**Given** an alert
**When** it is rendered
**Then** it names the queue and the item and never includes a NIN or full bank details.

## Epic 12: Work on site with no signal

A Technician saves a Project and its guides to the phone and designs, searches the Catalogue and reads Documentation offline; Operations writes and versions the guides.

### Story 12.1: The Catalogue on the phone

As a Technician,
I want to search the Catalogue and add products to a room with no signal,
So that I can adjust a design on site.

**Requirements:** FR-28, FR-73; AD-12, AD-17

**Acceptance Criteria:**

**Given** any signed-in User's phone
**When** it syncs
**Then** it pulls the active Catalogue without prices, filtered by the field-visibility registry, using the same cursor protocol as Projects, and Products that leave the Catalogue arrive as markers that remove them.

**Given** the synced Catalogue
**When** the phone has no signal
**Then** the Catalogue can be browsed and searched on an index the app builds itself, and a search for an exact model number or SKU still returns that Product first.

**Given** the product picker in a Room
**When** the phone has no signal
**Then** products can be added from the synced Catalogue.

**Given** a phone with no signal
**When** the Catalogue is shown
**Then** it says "No signal. You are working from what is saved on this phone, checked today [time]. Changes sync when you are back online."

**Given** an Owner or Manager offline
**When** they browse
**Then** prices appear only for Products in their price sheet, with "as of", and other rows show no price until the phone is back online (FR-73).

**Given** a Technician's phone
**When** its local Catalogue is inspected
**Then** it holds no price field.

### Story 12.2: Saved on this phone

As a Technician,
I want one place that shows what my phone holds and whether it is up to date,
So that I trust what I am looking at on site.

**Requirements:** FR-28; NFR-6; AD-12; UX-DR40

**Acceptance Criteria:**

**Given** Account, Saved on this phone
**When** it loads
**Then** it shows the connection status, the time of the last check, the changes waiting, a "Check now" button, the saved Projects with their sizes and Remove, and whether the phone has promised to keep its storage.

**Given** the phone has not promised to keep storage
**When** the screen loads
**Then** it warns that the phone may remove saved files when space runs short.

**Given** a person logging out with changes waiting
**When** they log out
**Then** the changes stay on the phone, locked, and sync at their next login.

**Given** no changes are waiting
**When** the person logs out
**Then** "Log out and clear this phone" is offered.

**Given** an expired session with unsynced changes
**When** it ends
**Then** the changes are locked and never purged.

### Story 12.3: The 14-day check-in

As an Owner,
I want saved Projects to lock if a phone stops checking in,
So that a lost or forgotten phone does not keep my work open forever.

**Requirements:** FR-28; AD-12

**Acceptance Criteria:**

**Given** a successful sync
**When** it completes
**Then** the phone stores the server's time of that sync, and any successful connection counts as a check-in.

**Given** 11 days since the last check-in
**When** Saved on this phone is shown
**Then** the line under "Check now" turns brass: "Check in by [date]."

**Given** 13 days
**When** any screen loads
**Then** a banner reads "Connect by tomorrow to keep working offline."

**Given** 14 days, or a phone clock that reads earlier than the last check-in
**When** a saved Project is opened
**Then** it is locked with "Connect to open your saved projects.", and nothing is deleted.

**Given** a locked phone that reconnects
**When** it checks in
**Then** it sends the waiting changes and everything opens.

**Given** a saved copy
**When** it is shown offline
**Then** it never shows a ticking clock.

### Story 12.4: Conflicts that need a look

As an Owner or Manager,
I want to see where two people changed the same thing and choose which to keep,
So that nobody's work disappears without a decision.

**Requirements:** FR-28; edge-case rows 34, 35

**Acceptance Criteria:**

**Given** a merge where two people changed the same item
**When** the later change wins by server receipt time
**Then** the other version waits in "Needs a look" on Saved on this phone.

**Given** an item in "Needs a look"
**When** the person chooses Keep latest or Use mine
**Then** the choice is sent as a new change.

**Given** an Owner or Manager
**When** they view a conflict
**Then** they see who made each change, and a Technician sees "Two people changed the same item" with no names.

**Given** a Project
**When** an earlier version from the last 30 days is chosen
**Then** it can be restored.

**Given** a synced item whose Product has become unorderable
**When** it is merged
**Then** it is kept, flagged Unavailable with a warning, and never discarded (edge-case row 35).

### Story 12.5: Edits held from a removed person

As an Owner,
I want to decide what happens to changes made by someone who has since left or changed role,
So that nothing they did is applied without my say.

**Requirements:** FR-28; edge-case row 9

**Acceptance Criteria:**

**Given** held edits from Story 6.7
**When** an Owner or Manager opens Saved on this phone
**Then** each held edit is listed with who made it, when and what it changes.

**Given** a held edit
**When** the Owner or Manager accepts it
**Then** it is applied as a merge, the Project revision goes up, and the decision is audited.

**Given** a held edit
**When** the Owner or Manager discards it
**Then** it is dropped and the decision is audited.

**Given** a held edit
**When** nobody decides
**Then** it is never applied.

**Given** a Technician
**When** they open Saved on this phone
**Then** held edits are not shown.

### Story 12.6: Operations attaches Documentation

As an Operations officer,
I want to attach manuals and videos to Products and see which have none,
So that the Products Integrators order most come with the help they need.

**Requirements:** FR-48

**Acceptance Criteria:**

**Given** a Product in the console
**When** Operations opens its Documentation
**Then** it has three slots: a written guide, a manufacturer manual as a PDF, and a video.

**Given** a manual or video upload
**When** it is sent
**Then** it goes through the staged upload pipeline and is stored under a content-hashed key, with format and size limits held as placeholder settings.

**Given** the Documentation page
**When** it loads
**Then** a meter shows coverage of Products ordered in the last 90 days that have Documentation, against goals of 80% by month 6 and 100% by month 12, and the list is ordered by how often each was ordered.

**Given** the Products list
**When** Operations views it
**Then** it gains the filter chip "No documentation".

**Given** a Product
**When** Operations chooses "Copy from another product"
**Then** that Product's Documentation is attached as a new version.

**Given** a slot that already holds something
**When** Operations replaces it
**Then** a new version is stored and the earlier one kept.

**Given** a slot
**When** Operations removes its content
**Then** a reason is required.

### Story 12.7: The guide editor

As an Operations officer,
I want to write a step-by-step installation guide and see exactly how it will look on a phone,
So that a technician on a ladder can follow it.

**Requirements:** FR-48

**Acceptance Criteria:**

**Given** the guide editor
**When** it opens
**Then** steps are listed on the left with reorder arrows, one step is edited in the middle, and a live phone preview on the right shows exactly what a Technician will see.

**Given** a step
**When** it is edited
**Then** it has a title, short action sentences, an optional photo and an optional warning box.

**Given** a step with a photo and no "Describe the photo" text
**When** Publish is attempted
**Then** it is refused with the step named.

**Given** Save draft or Publish
**When** it is pressed
**Then** a new version is stored, and Publish requires a version note shown to people who saved the old version.

**Given** a published guide that has replaced an older one
**When** the editor shows it
**Then** it shows how many phones still hold the old version.

*Assumption: the server records which version each phone downloaded, which this count needs (PRD §8.3 question 11).*

### Story 12.8: Read Documentation

As a Technician,
I want to open a product's guide and manual from the job I am on,
So that I install it right the first time.

**Requirements:** FR-49

**Acceptance Criteria:**

**Given** anyone who can browse the Catalogue
**When** they open a Product page
**Then** its Documentation shows in three slots, with no purchase and no approval needed (FR-49).

**Given** the written guide slot
**When** it is shown
**Then** it gives the step count, the saved date and the version.

**Given** the manual
**When** it is opened
**Then** it opens in the phone's own PDF viewer.

**Given** a video that has not been saved
**When** the phone has no signal
**Then** it says "Needs signal".

**Given** a Product with nothing yet
**When** its page loads
**Then** it says "Documentation coming soon." and "Need it now? Call or WhatsApp us."

**Given** a Project row or a Completed Order
**When** it is shown
**Then** it links to that Product's Documentation, and a Project row says whether its guide and manual are saved on this phone.

**Given** a written guide
**When** it is opened
**Then** it shows one step to a screen in large type, with a text-size control, a step strip, big Back and Next buttons, the described photo and any warning box.

**Given** a screen-reader user
**When** they read a guide step
**Then** the step number and total are announced, and the photo's description is read.

### Story 12.9: Make this Project available offline

As a Technician,
I want to save a Project and its guides to my phone the evening before a job,
So that I have everything on site with no signal.

**Requirements:** FR-50; NFR-6

**Acceptance Criteria:**

**Given** a Project
**When** the person presses "Save for offline"
**Then** a bottom sheet titled "Make this Project available offline" lists what will be saved (the Project, its product pages, PDFs and written guides) with the size.

**Given** the sheet
**When** it is shown
**Then** videos are a separate choice, off by default, and Data Saver never loads a video by itself (FR-50).

**Given** the save
**When** it starts
**Then** the app checks the phone's free storage first, shows progress with a figure in words, and warns that the phone may remove saved files when space runs short.

**Given** an iPhone with the app not installed
**When** Save for offline is pressed
**Then** the person is asked to install the app first, because a browser may clear saved files after about a week unused.

**Given** saved copies
**When** they are listed
**Then** each shows its version date, and saved PDFs and guides open with no connection.

**Given** a saved Project
**When** a Technician's Home loads
**Then** its recent projects show "Saved on this phone".

### Story 12.10: Replaced and withdrawn copies

As a Technician,
I want to be warned when a guide I saved has been replaced or withdrawn,
So that I do not install from out-of-date instructions.

**Requirements:** FR-48; edge-case row 65

**Acceptance Criteria:**

**Given** Documentation that Operations replaces
**When** a phone holding the old copy next syncs
**Then** the copy's state becomes Superseded and the business reads "Replaced", and it still opens with that warning and the version note.

**Given** Documentation that Operations removes
**When** a phone holding it next syncs
**Then** the copy becomes Withdrawn and opens only after "Open anyway".

**Given** either change
**When** it is made
**Then** saved copies are marked at each phone's next sync and never at once (edge-case row 65).

**Given** a replaced manual on a Technician's phone
**When** their Home loads
**Then** it appears under "Needs your attention".

## Epic 13: Support Cases and serial lookup

An Integrator reports a problem from a delivery; Support verifies the caller, looks up the serial number and records a Case for every contact.

### Story 13.1: Give Support a code

As a Technician calling Support,
I want a quick way to prove who I am,
So that Support can look at my order without a long interrogation.

**Requirements:** FR-79

**Acceptance Criteria:**

**Given** the Help sheet
**When** a signed-in User presses "Give Support a code"
**Then** a 6-digit code is shown in large figures with its time left in words, valid for a placeholder of 10 minutes.

**Given** Support on a call
**When** they enter the caller's code in the console
**Then** the caller is verified as that User and the verification is recorded on the Case.

**Given** a caller who cannot use the app
**When** Support calls them back on their verified phone
**Then** the callback is recorded as the verification.

**Given** a Case with no recorded caller verification
**When** Support tries to see an Order or serial-number detail for it
**Then** the server refuses (FR-79).

**Given** WhatsApp contact with Support
**When** it happens
**Then** it is only through the platform's WhatsApp Business account.

### Story 13.2: Support opens a Case for every contact

As an Operations officer with Support duties,
I want every call and message recorded as a Case,
So that we can show what was asked, what we did and when.

**Requirements:** FR-79

**Acceptance Criteria:**

**Given** the Support desk
**When** a call or WhatsApp message arrives
**Then** Support opens a Case recording the channel, the caller, the serial number or Order Line, and the time, with a reference such as C-0231.

**Given** an open Case
**When** Support works it
**Then** a timeline and remote-fix notes are kept, and it can be closed as fixed remotely or as refused with a reason.

**Given** any contact, including one closed remotely
**When** the day's Cases are listed
**Then** it has a Case (FR-79).

**Given** a Case linked to an Order Line
**When** it is the first for that line
**Then** its time is stored as the first Case time, which later judges the reporting window.

**Given** an account without Support duties
**When** it requests the Support desk
**Then** the server refuses.

### Story 13.3: Report a problem in the app

As a Technician,
I want to report a fault from the delivery it came in,
So that the clock for my claim starts the moment I tell you.

**Requirements:** FR-58, FR-59, FR-79; UX-DR45

**Acceptance Criteria:**

**Given** a delivery on an order
**When** any role presses "Report a problem"
**Then** a Case is opened at once and the reporting clock starts from that moment.

**Given** the report form
**When** it loads
**Then** a Technician chooses "Faulty or damaged" or "Wrong item", and an Owner or Manager also sees "It did not arrive" and "I changed my mind".

**Given** the form
**When** it is filled
**Then** it asks which item (with its serial number), what happened, photos and a callback phone.

**Given** a report past the usual window
**When** it is sent
**Then** it is still accepted as a Case, and a call or WhatsApp line is offered (FR-59).

**Given** the Case page
**When** it is opened
**Then** it shows what Support has done: report received, caller checked, remote fix tried.

**Given** the Orders tab
**When** it loads
**Then** a Problems segment lists what needs the person first, then an Earlier group.

**Given** Cases
**When** they are listed
**Then** Owners and Managers see all of the business's Cases and a Technician sees their own, with no amounts.

### Story 13.4: Look up a serial number

As an Operations officer with Support duties,
I want to confirm from a serial number that a device left our Stores,
So that I know a claim is about something we actually sold.

**Requirements:** FR-67

**Acceptance Criteria:**

**Given** a verified caller's Case
**When** Support enters a serial number
**Then** it is normalised and looked up, and a match shows the Product, the Order Line, the Business Account, the Shipment and the dispatch and delivery dates (FR-67).

**Given** a serial number the platform never dispatched
**When** it is looked up
**Then** it returns no match.

**Given** any lookup
**When** it runs
**Then** it is recorded in the audit trail.

### Story 13.5: A serial exception

As an Operations officer with Support duties,
I want a controlled way to handle a genuine device whose serial we cannot find,
So that a recording mistake does not deny a customer their remedy.

**Requirements:** FR-67

**Acceptance Criteria:**

**Given** a serialised device with no dispatch record
**When** Support opens a serial exception
**Then** it requires the Order Line and photos of the label and the box.

**Given** a serial exception
**When** it is decided
**Then** it needs approval from both an Operations officer and an Admin, neither of them the person who opened it (FR-67).

**Given** an approved exception
**When** it commits
**Then** it is audited and lets the device be treated as dispatched on that Order Line.

**Given** an exception waiting
**When** Support views it
**Then** it shows which approvals are still needed.

### Story 13.6: A non-delivery Case opens itself

As an Owner,
I want a lost delivery to start a claim without me having to chase it,
So that I am not out of pocket for goods that never came.

**Requirements:** FR-45, FR-79; edge-case row 60

**Acceptance Criteria:**

**Given** a Shipment marked Lost or Delivery Failed, or In-transit longer than the set number of days
**When** that is recorded
**Then** a non-delivery Case is opened and linked to the Shipment, replacing the flagged item from Story 9.8.

**Given** such a Case
**When** the business opens the order
**Then** the delivery card says a case has been opened.

**Given** a Shipment with an open non-delivery Case
**When** completion is evaluated
**Then** the Order cannot become Completed.

### Story 13.7: The Support cases list

As an Operations officer with Support duties,
I want all open Cases in one list,
So that no caller waits for a call-back we forgot.

**Requirements:** FR-78, FR-79

**Acceptance Criteria:**

**Given** the Support cases list
**When** it loads
**Then** it follows the list-and-preview pattern with open Cases oldest first, filters for open and closed, and the soft lock.

**Given** Cases with a call-back due
**When** Operations' Today page loads
**Then** a card shows how many.

**Given** the list
**When** it is shown
**Then** it has no target or escalation, because the PRD sets none (§8.3 question 8).

## Epic 14: Loyalty Tiers

A business earns a better price from its trailing six-month spend and sees its tier, its progress and any coming change.

### Story 14.1: Tiers are recalculated each month

As an Owner,
I want my tier worked out fairly from what I have actually received,
So that more volume through the platform earns me a better price.

**Requirements:** FR-21; NFR-9; AD-25; edge-case rows 31, 32

**Acceptance Criteria:**

**Given** 00:00 West Africa Time on the 1st of a month
**When** the sweep acts
**Then** every Business Account's tier is recalculated from `NetDeliveredGoodsQuery` over the trailing six months (FR-21).

**Given** spend
**When** it is counted
**Then** it is the pre-VAT, pre-delivery goods value of lines whose Shipment is Delivered or Signed For, in USD at each Order's stored rate, net of returned and credited lines.

**Given** the bands
**When** spend is placed
**Then** under 1,500 is Base (0%), 1,500 to under 5,000 is Tier 2 (2%), 5,000 to under 12,000 is Tier 3 (4%), and 12,000 and above is Tier 4 (6%)
**And** 4,999.99 is Tier 2 and 5,000.00 is Tier 3 (edge-case row 31).

**Given** a recalculation
**When** it runs
**Then** a tier can fall as well as rise, belongs to the Business Account, and is stored with the spend snapshotted at that date.

**Given** a recalculation that is retried
**When** it runs again
**Then** the result is the same and nothing is applied twice, and a missed run alerts the founder (NFR-9).

### Story 14.2: Prices follow the tier

As an Owner,
I want every price I see to include my tier discount,
So that I never have to work it out.

**Requirements:** FR-23, FR-24; edge-case row 33

**Acceptance Criteria:**

**Given** a business's current tier
**When** `Pricing` quotes a price
**Then** the Tier Discount is applied, and where a Back-order Discount also applies the larger one is used.

**Given** a tier change
**When** it takes effect
**Then** the phone's price sheet is refreshed at the next sync.

**Given** an Order
**When** it is created
**Then** it uses the tier in effect at that moment, not the tier at any earlier Proposal (edge-case row 33).

**Given** a Tier 4 business with no back-order item
**When** its realized markup is computed
**Then** it is 17.5% on cost.

### Story 14.3: See your tier

As an Owner or Manager,
I want to see my tier and how close I am to the next,
So that I can decide where to place my next order.

**Requirements:** FR-22

**Acceptance Criteria:**

**Given** an Owner or Manager on Account, Loyalty tier
**When** it loads
**Then** it shows the tier and what it is worth, saying prices already include the discount.

**Given** counted spend
**When** it is shown
**Then** it is in naira at today's rate with a meter toward the next tier and its figure in words, with how spend is counted.

**Given** the screen
**When** it loads
**Then** it shows the next review date and the whole ladder with naira thresholds at today's rate, which move as the rate moves (FR-22).

**Given** the screen
**When** it is read
**Then** a line says tier and back-order discounts do not add up, and no dollar figure appears.

**Given** a Technician
**When** they open Account
**Then** the Loyalty tier row is not shown.

### Story 14.4: A coming change, 7 days ahead

As an Owner,
I want to know a week ahead if my tier is likely to change,
So that I am not surprised by a new price.

**Requirements:** FR-22

**Acceptance Criteria:**

**Given** 7 days before a recalculation
**When** the sweep projects each business's tier from spend so far
**Then** a business whose projected tier differs from its current one is flagged.

**Given** a flagged business
**When** its Owner or Manager opens Loyalty tier
**Then** it shows the expected change and the date it would take effect, worded as expected.

**Given** a flagged business
**When** the next 9 am arrives
**Then** the Owner and Managers are sent a message about it.

**Given** the actual recalculation
**When** it runs
**Then** it may differ from the projection, and the result is what applies.

## Epic 15: Dashboard and finance exports

The founder sees every success measure with its definition, and Finance exports its monthly files.

### Story 15.1: The founder's dashboard

As the founder,
I want every success measure on one page with its definition,
So that I can see whether the business is on its path without a spreadsheet.

**Requirements:** FR-80; AD-2

**Acceptance Criteria:**

**Given** the `Reporting` module
**When** it reads data
**Then** it reads only versioned reporting views that each owning module publishes in its own migrations, each with a contract test in the owner's suite, and it never writes (AD-2).

**Given** the dashboard
**When** the founder opens it
**Then** it shows one tile per measure, SM-1 to SM-9 and SM-C1 to SM-C5, grouped as aiming for, how it is going, and must not trade away.

**Given** a tile
**When** it is shown
**Then** it has a value with counts beside every percentage, a target, a neutral status chip (Early days, On track, Behind or Below target) and a "Definition and source" disclosure.

**Given** a measure that cannot yet be worked out
**When** its tile loads
**Then** it says "Not enough data yet".

**Given** SM-C1
**When** it is computed for any Completed Order
**Then** it is computed from that Order's lines alone (FR-80).

**Given** an account without the founder flag
**When** it requests the dashboard
**Then** the server refuses.

### Story 15.2: Inputs the dashboard cannot work out

As the founder,
I want to enter the plan figures the platform cannot know,
So that the breakeven and growth tiles can be measured against my plan.

**Requirements:** FR-80

**Acceptance Criteria:**

**Given** the dashboard's settings
**When** the founder opens them
**Then** they hold a plan start date and the cost inputs SM-6 needs, each kept with its history.

**Given** a tile that needs an input not yet set
**When** it loads
**Then** it says "Not enough data yet" and names the input.

*Open: the full list of inputs is PRD §8.3 question 15.*

### Story 15.3: Finance exports

As a Finance officer,
I want to export orders, payments and credit notes for a period,
So that I can do monthly work from the platform's own records.

**Requirements:** FR-80; AD-21

**Acceptance Criteria:**

**Given** a Finance or Admin account on Reports and exports
**When** it chooses a date range
**Then** it can export CSV files of Orders, Payments, Credit Notes with their ageing, and bank reconciliation.

**Given** an export
**When** it is generated
**Then** it is built on the queue and offered through a short-lived signed link.

**Given** an export
**When** it is read
**Then** money is in decimal strings, times are in UTC with the zone named, and no NIN appears.

**Given** an export that includes bank details
**When** it is generated
**Then** the read is recorded in the audit trail.

### Story 15.4: The monthly VAT export

As a Finance officer,
I want a monthly VAT output file that matches our documents,
So that VAT returns can be filed with confidence.

**Requirements:** FR-77, FR-80

**Acceptance Criteria:**

**Given** a month
**When** Finance exports VAT output
**Then** it lists the VAT on each tax invoice and credit-note document issued that month, with totals.

**Given** the export
**When** it is produced
**Then** its totals are checked against the documents issued, and any difference is listed (FR-80).

*For the accountant: VAT on delivery and on Credit Notes is unconfirmed; the export follows the documents as issued.*

### Story 15.5: The platform holds at its design scale

As the founder,
I want proof before launch that the platform copes with the volumes it was designed for,
So that growth does not break it in the field.

**Requirements:** NFR-1, NFR-5, NFR-7

**Acceptance Criteria:**

**Given** staging loaded with synthetic data at the design target of 500 Integrators, 5,000 Products and 500 Orders a month
**When** a load test runs the main journeys (browse, Project sync, Proposal, Order, Receipt upload, Payment confirmation)
**Then** every journey completes without error, and the results are recorded (NFR-1).

**Given** the low-end test phone on slow 3G
**When** Project open, Proposal generation and Receipt upload are measured
**Then** each time is recorded and adopted as that action's performance budget, and CI warns when a later change exceeds it (NFR-7).

**Given** the Catalogue list at design scale
**When** it is opened on the test phone
**Then** it still loads within about 5 seconds.

**Given** the restore drill
**When** it is run again at design scale
**Then** recovery meets the 1-hour recovery point and the 4-hour recovery time (NFR-5).

## Epic 16: Returns, warranty and counterfeit

A faulty device goes from a Case to an Issue Code, a Return Request approved by the business, a checked arrival and a decided remedy; change-of-mind returns, non-delivery claims, warranty and the counterfeit procedure work in the app.

*Returns terms are counsel's to vet. Anything a business reads about windows, remedies, the restocking fee or exclusions carries placeholder wording until counsel approves it.*

### Story 16.1: Return windows

As an Owner,
I want to know exactly how long I have to report each kind of problem,
So that I never lose a claim because I misjudged a deadline.

**Requirements:** FR-59; edge-case rows 70, 71, 82

**Acceptance Criteria:**

**Given** an Admin on Pricing and rules
**When** the return windows are set up
**Then** they are 5 days for transit damage or a wrong item, 30 days for dead on arrival (or the Supplier's shorter window), 7 days for a change of mind, the Warranty Period for a warranty claim, and the Warranty Period plus 90 days for a suspected counterfeit.

**Given** a Case
**When** its first contact is logged
**Then** the windows in force are snapshotted on it, and a later change to the settings applies only to Cases opened afterwards (edge-case row 82).

**Given** window arithmetic
**When** a deadline is worked out
**Then** it counts calendar days in West Africa Time, ending at 23:59:59, from the courier-confirmed delivery date or the collection date (edge-case row 71).

**Given** a Return Request
**When** it is judged against its window
**Then** the first Case time is used, not the time the request was raised (FR-59).

**Given** "Report a problem" on a delivery
**When** it opens
**Then** it says, in dates for that delivery, how long the person has for each reason.

**Given** a suspected counterfeit reported after the Warranty Period plus 90 days
**When** it is raised
**Then** it needs Admin's approval, and that approval is listed in the founder's weekly review.

### Story 16.2: Warranty until

As a Technician,
I want to see when a device's warranty ends,
So that I know whether a fault is covered before I call.

**Requirements:** FR-64; edge-case row 79

**Acceptance Criteria:**

**Given** a delivered or collected Order Line
**When** it is shown
**Then** it reads "Warranty until [date]", computed from the dispatch date, or from Ready for Pickup for a pick-up, or the manufacturer's earlier date where recorded (FR-64).

**Given** a Product with no Warranty Period
**When** a warranty claim is raised for it
**Then** it is refused with the reason (edge-case row 79).

**Given** a warranty claim
**When** it is handled
**Then** it goes through the manufacturer's RMA only, with no labour cover, and never ends in a cash refund.

### Story 16.3: Support makes an Issue Code

As an Operations officer with Support duties,
I want to issue a code that lets the business send a device back,
So that only devices we have checked on a call come back to us.

**Requirements:** FR-67, FR-68; edge-case row 72

**Acceptance Criteria:**

**Given** a Case whose remote fix failed
**When** Support makes an Issue Code
**Then** it lists one or more serial numbers, or an Order Line and quantity for a Product without serials that Support verifies against the line.

**Given** a serial number with no dispatch record to that Business Account
**When** Support tries to include it
**Then** it is refused unless a serial exception has been approved (FR-68).

**Given** a new Issue Code
**When** it is made
**Then** it is valid for 48 hours by a stored deadline, has a reference such as IC-4829-QK7M, works once, and only for that Business Account and those serials.

**Given** an Order Line that already has 3 Issue Codes
**When** a fourth is attempted
**Then** it is refused (edge-case row 72).

**Given** a new code made after a lapse
**When** it is made
**Then** it keeps the original first Case time.

**Given** a made code
**When** the business opens the Case
**Then** the code appears there with "Raise the return request", which fills it in.

### Story 16.4: Raise a return request

As an Owner, Manager or Technician,
I want to raise a return request with the code Support gave me,
So that the faulty device can come back and be put right.

**Requirements:** FR-58, FR-60; edge-case row 74

**Acceptance Criteria:**

**Given** "Raise the return request"
**When** it opens
**Then** the Issue Code is filled in with its time left, and the form asks for a reason (damaged on the way, wrong item, does not work out of the box, stopped working under warranty, I think it is fake), photos of the device, its label and the box, and a description of the fault.

**Given** a device reason
**When** no valid Issue Code is entered
**Then** the request cannot be raised (FR-58).

**Given** a request
**When** its quantity, added to open and decided quantities on that Order Line, would exceed the delivered quantity
**Then** it is refused.

**Given** a serial that already has an open request, or has been Resolved
**When** another request is raised for it
**Then** it is refused (edge-case row 74).

**Given** a request raised by an Owner or Manager
**When** it is created
**Then** it is approved at once, with a reference such as RR-0087.

**Given** any Return Request
**When** it moves
**Then** it follows the states Raised, Approved by the Integrator, Item Received, Under Investigation, Decided, Awaiting Finance, Awaiting RMA, Resolved, Rejected, Lapsed and Held, each change recorded with who and when.

**Given** a Technician
**When** they view a Return Request
**Then** no amount appears anywhere on it.

### Story 16.5: A Technician's request waits for approval

As a Manager,
I want to approve or decline returns my technicians raise,
So that only claims I stand behind reach the platform.

**Requirements:** FR-58, FR-68; edge-case rows 68, 69, 70

**Acceptance Criteria:**

**Given** a Technician raising a request
**When** they submit
**Then** the button reads "Send for approval", and the Issue Code's 48-hour clock pauses for up to 7 days.

**Given** a request waiting for approval
**When** Support or Operations look for it
**Then** it is not visible to them.

**Given** an Owner or Manager
**When** they open Home
**Then** the request is under "Needs your attention", with the Order Line suggested from the serial number.

**Given** the approver correcting the Order Line
**When** they choose another
**Then** it must contain the serial or Product bound to the code, belong to the same Business Account, and have been delivered (edge-case row 69).

**Given** an approval
**When** it is given
**Then** no amounts are needed, the clock resumes, and the request reaches the platform.

**Given** a decline
**When** it is given
**Then** a reason is required and the Technician sees it (edge-case row 68).

**Given** a request still waiting when the pause ends
**When** the Issue Code lapses
**Then** the request lapses with it and both people are told.

**Given** a Case logged inside its window
**When** the approval comes after the window has closed
**Then** the request is still valid while its Issue Code is (edge-case row 70).

### Story 16.6: Bring the device back

As a Technician,
I want clear instructions and a deadline for returning the device,
So that the return is not lost on a technicality.

**Requirements:** FR-62, FR-68

**Acceptance Criteria:**

**Given** an approved request
**When** the person opens it
**Then** a countdown shows the deadline, already allowing for any paused time, with the exact end time.

**Given** the return options
**When** they are shown
**Then** the person can take the device to a Store, with its hours shown, or hand it to a courier and enter the courier's name, a tracking number and a photo of the courier's receipt.

**Given** a handover recorded before the deadline
**When** it is saved
**Then** the device counts as returned in time.

**Given** the deadline passes with no handover
**When** the sweep acts
**Then** the request moves to Lapsed, Support can make a new code, and the reporting window is not reset (FR-68).

**Given** the screen
**When** it is read
**Then** a plain note explains who pays freight.

### Story 16.7: A returned device arrives

As an Operations officer with Support duties,
I want a fixed procedure for checking a returned device,
So that every decision rests on recorded evidence.

**Requirements:** FR-60, FR-78; edge-case row 73

**Acceptance Criteria:**

**Given** a device arriving
**When** Support records it
**Then** the request moves to Item Received and a work item joins the returns queue with a target of 2 Business Days from arrival.

**Given** the arrival screen
**When** it is worked
**Then** it takes four ordered steps: scan the serial against the Issue Code, photograph the unit, label and packaging, test and record findings, and hand over to decide.

**Given** a physical serial that matches none on the Issue Code
**When** it is scanned
**Then** the return is rejected automatically and the account is flagged (FR-60).

**Given** a Product without serials
**When** it arrives
**Then** photos taken before shipping and a sealed-packaging check are required.

**Given** a device arriving after its request lapsed
**When** it is recorded
**Then** the request moves to Held for Support to triage (edge-case row 73).

**Given** a device under investigation
**When** the business opens the request
**Then** it reads "We are checking it".

### Story 16.8: Admin decides the remedy

As an Admin,
I want to decide each return from the findings, following a fixed order of remedies,
So that similar faults are treated the same way.

**Requirements:** FR-52, FR-60, FR-61; edge-case rows 75, 77

**Acceptance Criteria:**

**Given** a finished investigation
**When** the decision screen opens
**Then** it shows the findings, the window check, and a list of remedies in the PRD's order for the reason, with the first possible one chosen.

**Given** a decider choosing a different remedy
**When** they save
**Then** a reason is required.

**Given** the person who investigated the device
**When** they try to decide it
**Then** the Action refuses (FR-52).

**Given** Support finding a different cause
**When** they reclassify the reason with a note
**Then** the window, exclusions and remedy are re-evaluated, and the business sees the change (edge-case row 75).

**Given** a unit that tests fine
**When** it is decided
**Then** it is returned at the business's cost, added to its next Amount Due or deducted from an open Credit Note, and the decision shows the amount (edge-case row 77).

**Given** a rejection
**When** it is saved
**Then** the business reads the public reason.

### Story 16.9: The remedy is carried out

As an Owner,
I want to see the remedy delivered, not just decided,
So that I know when the matter is truly closed.

**Requirements:** FR-61; AD-25; edge-case rows 76, 78

**Acceptance Criteria:**

**Given** a replacement decision
**When** it is carried out
**Then** a zero-value Shipment of kind replacement is made under the original Order, draws on stock, records the new serial, and inherits the original unit's remaining warranty (FR-61).

**Given** a replacement Shipment
**When** completion, spend, invoices or Established status are worked out
**Then** it is excluded (edge-case row 78).

**Given** a manufacturer repair
**When** it is decided
**Then** the request moves to Awaiting RMA with a named owner.

**Given** a Credit Note remedy
**When** it is decided
**Then** the request moves to Awaiting Finance and a Credit Note request is sent with the amount worked out by `Pricing`.

**Given** any remedy
**When** the credit, replacement or repair has been delivered
**Then** and only then the request moves to Resolved (edge-case row 76).

**Given** the outcome page
**When** the business opens it
**Then** every step is listed with its date and the decision in plain words, and a Technician's view describes money without showing it.

### Story 16.10: Freight reimbursement

As an Owner,
I want my courier cost repaid when the fault was yours,
So that sending back a bad unit does not cost me.

**Requirements:** FR-62

**Acceptance Criteria:**

**Given** a return whose fault is the platform's (its error, transit damage, a verified dead-on-arrival unit or a counterfeit)
**When** the business submits the courier's receipt
**Then** the actual cost is reimbursed as a Credit Note request to Finance, capped at the Zone Rate for the same route (FR-62).

**Given** a return
**When** a second freight claim is made on it
**Then** it is refused, because there is one claim per return.

**Given** any other return
**When** freight is recorded
**Then** the business pays it.

**Given** every Return Request
**When** it is stored
**Then** it records who pays freight and the amount reimbursed.

### Story 16.11: Change-of-mind returns

As an Owner,
I want to return a sealed item I no longer need,
So that a change of plan does not leave me with dead stock.

**Requirements:** FR-61, FR-63

**Acceptance Criteria:**

**Given** an Owner or Manager reporting "I changed my mind"
**When** Support agrees
**Then** Support issues a return authorisation without a fault check.

**Given** a change-of-mind request
**When** it is judged
**Then** it must start within 7 days and the item must be sealed and stocked.

**Given** a Back-order Item, a special order, software or a licence, opened or installed goods, an end-of-life, refurbished or demonstration item, or an item missing its serials or original packaging
**When** a change-of-mind return is asked for
**Then** it is refused with the reason shown, driven by the Product's returnability flags (FR-63).

**Given** an accepted change-of-mind return
**When** the item is received and checked
**Then** a credit-only Credit Note is issued for the pre-VAT line price less 15%, plus the VAT on it, and the item is restocked.

### Story 16.12: A non-delivery claim

As an Owner,
I want to claim for a delivery that never reached me,
So that I am refunded or sent the goods again.

**Requirements:** FR-58, FR-61; edge-case row 81

**Acceptance Criteria:**

**Given** a Shipment that is Lost or overdue
**When** an Owner or Manager opens a non-delivery claim
**Then** it needs no Issue Code and is linked to the non-delivery Case.

**Given** a Shipment marked Delivered that the business says never arrived
**When** they claim within the non-delivery window (a placeholder setting)
**Then** the claim is accepted (edge-case row 81).

**Given** a claim
**When** Operations investigates
**Then** they check it against the courier's proof and record the finding.

**Given** an upheld claim
**When** it is decided
**Then** the remedy is a re-dispatch at no charge or a cash refund through a refundable Credit Note.

### Story 16.13: A suspected counterfeit

As an Operations officer,
I want to stop the sale of a suspected fake the moment it is reported,
So that no more businesses receive it while we check.

**Requirements:** FR-66; AD-8; edge-case row 29

**Acceptance Criteria:**

**Given** Support with evidence (photos of the unit, label, serials and packaging, and the supplier)
**When** they flag a suspected counterfeit
**Then** a counterfeit case is opened as Suspected.

**Given** a suspected case
**When** Operations or Admin mark a batch or a whole Product as Suspected
**Then** `Returns` calls `Catalogue`'s `SetSuspected`, and a whole-Product hold waits on the Approvals page for a second person (FR-66).

**Given** a Product or batch marked Suspected
**When** it takes effect
**Then** it cannot be added to a Project or Cart, existing items show a warning, its stock is quarantined with a ledger entry, affected Order Lines are flagged, and affected Proposals are marked out of date.

**Given** an affected Order Line
**When** a Payment confirmation or a shipment touches it
**Then** `AssertSellable` blocks it, and lines already Paid are credited (edge-case row 29).

**Given** every affected Business Account
**When** the hold takes effect
**Then** a recall notice appears on Home under "Needs your attention" until someone taps "I have seen this".

### Story 16.14: Confirm or clear a counterfeit

As an Admin,
I want each suspected counterfeit settled within 14 days,
So that a cleared product returns to sale and a confirmed fake is refunded in cash.

**Requirements:** FR-61, FR-66; edge-case row 80

**Acceptance Criteria:**

**Given** a Suspected case
**When** it is opened
**Then** a 14-day deadline is stored and a card on Admin's Today page shows its time left, escalating when it passes.

**Given** a manufacturer serial check that confirms a fake, or a two-person decision with photos through the Approvals page
**When** it is recorded
**Then** the case moves to Confirmed with who and when, and affected businesses can receive a cash refund through a refundable Credit Note.

**Given** a Confirmed case
**When** its stock is handled
**Then** it is never restocked or resold.

**Given** a case found genuine
**When** it is Cleared
**Then** the hold lifts, the Product returns to sale, and the unit goes back to the Integrator.

*Note: Operations tells the Supplier, and Admin reports to the regulators, outside the platform.*

### Story 16.15: The three returns messages

As a Technician,
I want to be told when my return needs a decision or my code is running out,
So that a claim does not lapse while I am on another job.

**Requirements:** FR-51

**Acceptance Criteria:**

**Given** a Technician's request waiting for approval
**When** it is sent for approval, and again at half the Issue Code window
**Then** the Owner and Managers are messaged.

**Given** an Issue Code
**When** it is made, and at hour 36
**Then** the person who will raise the request is messaged, and the hour-36 message is sent at once at any hour.

**Given** a counterfeit hold
**When** it takes effect
**Then** every affected business is messaged, with amounts only to Owners and Managers.

**Given** these three events
**When** they are added
**Then** they join the notification matrix from Epic 11 and follow its channel, timing and opt-in rules.
