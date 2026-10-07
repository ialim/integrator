# Source digest: integrator (UX extraction, no design choices)

Sources: PRD; PRD addendum (Ad#n = edge-case row); brief (Br); brief addendum (BrA). Abbreviations: BA Business Account; Own/Mgr/Tech; Fin/Ops/Adm; L1-L3 Access Levels; P1-P3 build priority (§6.1). Device tags: [P] phone-critical per sources (Tech field use; Receipt photo FR-55; BrA); [P?] phone-likely, [D?] desktop-leaning, both inferred; [B] both. Product name is a "working title" (PRD title note, Br).

## 1. Product and users

integrator is a B2B wholesale and project-design platform, an installable offline-capable web app plus a staff console, built "by a systems integrator for systems integrators" and launching in Nigeria for IT, AV, security and home-automation equipment (§1, FR-54). Integrators design jobs room by room, send clients a Proposal PDF with one Integrator Margin, and order from one place or direct-buy via a Cart (FR-25,29,33). Prices are naira-only, USD-linked through one Platform Rate and a 24-hour Price Lock, and staff confirm bank-transfer payment (FR-38).

- Visitor: cannot browse until a contact is verified (FR-1). L1 (verified email/phone; signer becomes Owner of a new BA): browse, read Documentation, no prices (FR-2). L2 (NIN-verified): same; may submit a Vouch Code to request L3 (FR-3,6).
- Own (L3): alone requests L3, generates Vouch Codes (Established only), changes roles/Owner, closes BA; bank refunds need Own (FR-5,11,43,71). Mgr (office): same otherwise, manages Technicians, approves Tech Return Requests (FR-10,11,58). Tech (phone, on site, weak signal, UJ-5,7): browse, design Projects, read Documentation, raise Return Requests, see Order/Shipment status; no amounts, Cart, Proposal or Order (FR-11). Own/Mgr device unstated.
- Staff (devices unstated; MFA, FR-71): Fin (Receipts queue main screen): payments, Credit Notes, refunds, Platform Rate. Ops: goods, stock, Documentation, shipments, plus Support duties (caller checks, Cases, serial lookup, Issue Codes, investigations). Adm: NIN and L3 approvals, pricing parameters, Suppliers, bank details, holds, staff (FR-52). Founder: dashboard (FR-80).
- End client never logs in; receives only the Proposal PDF. Suppliers have no accounts (§2.2).

Money visibility: Selling Prices and all money fields (prices, totals, discounts, margin, tier, spend, Payments, Amount Due, delivery cost, Credit Notes, bank details) go to Own and Mgr at L3 only (FR-3,11,27,73); Visitor, L1, L2, Tech get none in any screen, notification, sort order, export or cache (FR-11). Integrators never see USD, Supplier Cost or Platform Markup (§5.1).

## 2. Surface inventory

Integrator app: 32 screens/flows (A) plus 6 system outputs (X).
- Access: A1 sign-up, contact code (FR-1)[P?]; A2 login, step-up prompt, recovery (FR-71)[P?]; A3 Terms acceptance, re-acceptance gate, WhatsApp opt-in (FR-69); A4 profile, NIN status (FR-3,4); A5 L3 request, status (FR-6,7); A6 Vouch Code generator (FR-5)[D?]; A7 Pending Activation, invitee login (FR-8,10); A8 team management (FR-10)[D?]; A9 business profile (FR-70)[D?]; A10 account settings, closure, Hold (FR-71,72)[D?].
- Catalogue: A11 browse, search, filters (FR-73)[P]; A12 Product page, L1/L2 price-slot message (FR-73)[P]; A13 Documentation viewer (FR-48-50,UJ-5)[P].
- Projects: A14 list (inf); A15 editor (FR-25-27,UJ-1)[P]; A16 Parts List, no-price download (FR-25)[P]; A17 "Make this Project available offline" (FR-50)[P]; A18 offline/sync/conflict-restore, install and eviction prompts (FR-28,54)[P].
- Proposals: A19 builder (FR-29,30,70)[B]; A20 view, regenerate, expired, stale (FR-31,32,35)[B].
- Buying: A21 Cart (FR-33)[B]; A22 order confirmation: Order Quote, diff vs Proposal, Back-order opt-in, Pick-up/Delivery, parts or full (FR-34-39,47,UJ-4)[B]; A23 payment, Receipt upload (FR-38,40,55)[P]; A24 Orders list (inf); A25 Order detail, Tech amount-free view (FR-11,46,64,65)[P]; A26 cancel, amend (FR-57,75); A27 Credit Notes, apply/refund request (FR-43)[D?]; A28 Loyalty Tier (FR-21,22).
- Support: A29 "Report a problem" (FR-79)[P]; A30 raise Return Request, incl. non-delivery claim (FR-58,59,UJ-7)[P]; A31 Mgr/Own approval (FR-58)[B]; A32 return status (FR-60-62).
- System: X1 email/WhatsApp templates (FR-51); X2 Proposal PDF (FR-30); X3 tax invoice, credit-note PDFs (FR-77); X4 banners/blocks: bank-details-changed, "prices are being updated", Hold, stale rate, offline-unavailable (FR-20,28,52,72); X5 error/empty states: upload failure, refused files, zone "coming soon", unavailable items, 2-unpaid-Orders cap, code lockout (FR-1,34,40,47,55); X6 hand-over proof (FR-45).

Staff console: 29 surfaces, [D?] unless marked. S1 login, MFA, deputy (FR-71,76); S2 queue hub, six queues (FR-78); S3 Receipts, payment confirmation (FR-41,UJ-6); S4 statement import, unmatched credits (FR-41); S5 Platform Rate (FR-20); S6 Credit Notes (FR-43); S7 Order approval, credit line (FR-44); S8 Shipments, serials [B?] (FR-45,65); S9 cancel/amend requests (FR-57,75); S10 Product editor (FR-12,17); S11 stock ledger, goods-in (FR-16,74); S12 bulk upload (FR-15); S13 pricing and settings (FR-13,52); S14 Suppliers (FR-14); S15 Zones, Stores (FR-47,56); S16 Documentation manager (FR-48); S17 L3 review (FR-7); S18 NIN review (FR-4); S19 account admin, Holds, blocklist (FR-8,9,72); S20 staff admin, calendar (FR-52,76); S21 bank-details change (FR-52); S22 Support desk, serial lookup [B?] (FR-67,79,UJ-7); S23 Issue Code (FR-68); S24 returns workbench [B?] (FR-60-62); S25 counterfeit procedure (FR-66); S26 audit trail (FR-53); S27 founder dashboard (FR-80); S28 Finance exports (FR-80); S29 alerts (FR-78,NFR-9). P3: A30-A32, S23-S25; P2: A13, A17, A18, A28, A29, X1, S16, S22, S27, S28; rest P1.

## 3. Named journeys

- UJ-1 "Tunde designs a Project, quotes his client, and places the Order." Tunde, Own L3, installed app. Beats: Project, Rooms, Products; Proposal PDF (one margin); whole-Project Order showing changes since Proposal; pay, upload Receipt. Climax: Finance confirm, Paid, Ops approve, Shipments Delivered or Signed For. Edge: rate band exceeded, difference split exchange rate/tier/items, delivery separate; client withdraws, cancel for Credit Note.
- UJ-2 "Hammed joins the platform and unlocks full access." Hammed, no account; Segun vouches. Beats: sign up, verify (L1); profile, NIN (L2); Vouch Code, Trade Name, Terms; Adm approves. Climax: L3 unlocks prices, Projects, Carts, Proposals, Orders (then invites two technicians). Edge: definitive NIN mismatch makes later checks manual; code expires at 48 hours.
- UJ-3 "Funmi restocks parts for Tunde with a direct buy." Funmi, Mgr. Beats: Cart, pick-up or delivery, Order, pay. Climax: Paid, approved same day, collected. Edge: item sells out in Cart (unavailable or Back-order Item); unopened return within 7 days, Credit Note less restocking fee.
- UJ-4 "Tunde orders a Project that includes a Back-order Item." Entry: saved Project with an Out of Stock Product at a discount. Beats: discount, Lead Time, no-cancel/no-return warning; explicit opt-in; parts or full. Climax: in-stock ships first, Back-order Item follows. Edge: item sells out between Receipt upload and confirmation, Finance credits line; Back-order Item 7+ days late, cancel for Credit Note.
- UJ-5 "Chidi finds the installation guide on site." Chidi, Tech, phone, poor signal; saved Project offline the evening before. Beats: Project, Product, guide, manufacturer PDF. Climax: install proceeds without calling the office; no pricing in view. Edge: "Documentation coming soon."
- UJ-6 "Ngozi confirms a payment." Ngozi, Fin; Receipts queue, oldest first with age. Beats: match statement credit (amount, sender, reference), enter confirmed amount, confirm; Order Paid, joins Ops queue. Climax: confirmed within two Business Hours, Integrator told (then Bola approves; Kunle runs NIN and L3 queues). Edge: no match, Unconfirmed; overpayment, draft Credit Note.
- UJ-7 "Chidi reports a faulty camera and the device goes back." Chidi, Tech; Amaka, Ops with Support duties. Beats: phone/WhatsApp Support; Amaka verifies caller, opens Case, checks serial, Issue Code; Chidi raises Return Request; Funmi approves; device to Store or courier in 48 hours; Amaka checks it. Climax: Adm decide replacement, repair, Credit Note or rejection; Resolved. Edge: no dispatch record needs Ops and Adm serial exception with photos; lapse needs new code, window runs from first contact. In-app part is P3.

## 4. State and status vocabularies

- Order (FR-45): Awaiting Payment, Paid, Approved, Completed, Expired, Cancelled. Own/Mgr see amounts, Tech status only (FR-11,46). Reversed Payment reopens it; no Completed while a Shipment is Lost or Delivery Failed; after Approval cancel is a request (FR-41,57).
- Shipment (FR-45): Processing; Ready for Pickup, Signed For; In-transit, Delivered; Delivery Failed, Returned to Store, Lost, Uncollected (reminder day 7, Uncollected day 14, restock day 30). Staff-set; integrators see timestamps, serials (FR-46,65).
- Payment (FR-41): Submitted, Confirmed, Unconfirmed, Reversed.
- Credit Note (FR-43): Issued, Partially applied, Applied, Refunded, Expired; "draft" appears (UJ-6), unlisted. Own/Mgr only; 12 months, reminders at 60 and 14 days.
- Return Request (FR-60): Raised, Approved by the Integrator, Item Received, Under Investigation, Decided, Awaiting Finance, Awaiting RMA, Resolved, Rejected, Lapsed, Held. Tech-raised hidden from staff until Mgr/Own approves; Tech sees no amounts. Issue Code: 48 hours, paused up to 7 days, hour-36 notice.
- Counterfeit (FR-66): Suspected, Confirmed, Cleared (14 days). Suspected blocks adds and shipment, warns on Project Items, stales Proposals, triggers recall notice; cash refund only when Confirmed.
- Proposal (FR-32,35): valid; Expired (still downloadable); stale "Project edited since Proposal"; latest only.
- Order timers (FR-38,51): hour 0 Amount Due fixed; hour 20 notice; hour 24 in-time Receipt cutoff; payment at 24-48 reprices once; hour 40 warning; hour 48 Expired if no Receipt, else 48 hours after last upload or Unconfirmed decision (7 days maximum). Lock holds only if Finance decides in-time Receipts cover Amount Due.
- Stock (FR-16,17,73): In Stock, Out of Stock, Back-order with Lead Time (discount up to 10%). L1+ see status; Own/Mgr see discount inside price; PDF shows Lead Time only. Also discontinued, quarantined, Unavailable after sync.
- Access (FR-1-9,69-72): unverified, L1, L2, L3, Pending Activation; NIN verified, manual review, Rejected; Vouch Code active, used, expired, locked, voided; Hold, revoked; Terms current or re-acceptance required.
- Offline (FR-27,28,50): online/offline; prices carry "as of" time (Own/Mgr); conflicts, restore 30 days; Documentation version date, Superseded, Withdrawn; cache locks after 14 days without check-in.
- Loyalty (FR-21,22): Base, Tier 2-4 (0/2/4/6%), trailing six months, monthly, can fall, change shown 7 days ahead; Own/Mgr only; per BA.

## 5. Behavioural rules

- Offline (FR-28,50,55): browse and search synced Catalogue, read saved Documentation, create and edit Projects. Proposal, Order and Receipt upload are blocked and must "say why"; offline prices never feed them. Receipt upload resumes without re-entry.
- Price visibility: per §1; no price sort or filter for L1/L2/Tech (FR-73); Proposal PDF never shows cost, markup, discounts or margin (FR-30).
- Step-up fresh one-time code before: Vouch Code, refund, Credit Note application, role, Owner or contact change (FR-71).
- Order Quote (FR-34,35): Order binds to the confirmed quote; if rate, tier, Proposal validity, Back-order terms or stock change while open, refuse and show a new quote; double submit makes one Order; unorderable lines listed, never dropped; Project ordered whole. Goods total shown beside the Proposal's at Integrator's cost, difference split exchange rate, tier, items, availability; delivery separate.
- Back-order opt-in (FR-36): explicit before creation; discount and Lead Time per item; states no cancel once Supplier-ordered (unless 7+ days late) and no change-of-mind return.
- Receipt and partial payment (FR-38,40,42): bank details, Amount Due, unique Order reference; declared amount, sender name and bank, bank reference, transfer date/time; one Receipt per transfer. Awaiting Payment until confirmed Payments plus applied Credit Note cover Amount Due; re-upload within 24 hours of Unconfirmed keeps original time if full amount matches; repricing notice gives new Amount Due and balance.
- Credit Note (FR-43): Own/Mgr request, Fin applies, counts as payment from request; refunds only to a recorded sending account. Return approval (FR-58): Tech request waits for Mgr/Own to confirm the suggested Order Line, approve or decline with reason; Mgr/Own requests approve at creation.
- Irreversible or confirmed: margin over 100% asks confirmation (FR-29); regenerating overwrites (FR-31); cancel gives credit-only Credit Note (FR-43,57); closure erases or pseudonymises personal data (FR-72); removal or role change revokes sessions, purges cache (FR-10); change of mind costs 15% restocking, exclusions refused with reason (FR-61,63); Hold or un-accepted new Terms block Orders (FR-69,72). Staff: every decision records a reason; Reversals and amounts above thresholds need a second approver (FR-41,52).

## 6. Notifications and messaging

12 launch events (FR-51): Payment Unconfirmed; Order repriced (new Amount Due); Price Lock deadline (hour 20); Order expiry warning (hour 40); Order Paid and Approved; Shipment dispatched or Ready for Pickup (with tracking); Return Request awaiting approval (reminder at half the Issue Code window); Issue Code issued and expiring (hour 36); Credit Note issued and expiring (60 and 14 days); Back-order running late; Level 3 and NIN decisions; Counterfeit recall notice.
Email and WhatsApp only; time-sensitive events by WhatsApp, email fallback after 15 minutes; per-number opt-in/out (FR-69); amounts only to Own/Mgr, Tech text carries none; removed Users get nothing; each names its Order, Proposal or Case. Staff alerts also go by email and WhatsApp (FR-78). Recipient per event and the "time-sensitive" set are unspecified.
Literal microcopy: "Prices are shown once your business is approved." (L1/L2, FR-73); "Payment unconfirmed: please upload your receipt again or pay the full amount." (FR-41); "Documentation coming soon." (FR-49); "Project edited since Proposal" (FR-35); "Make this Project available offline" (FR-50); "Report a problem" (FR-79); "prices are being updated" (FR-20); "bank details changed on [date]" (7-day banner, FR-52); Proposal PDF sentence "prices hold while the exchange rate stays within NGN 5 of the quoted rate, otherwise they adjust" (FR-30); "coming soon" for a zone without rate (FR-47).

## 7. Accessibility, device and performance constraints

- Devices (NFR-6): Android 9+ on Chrome; iOS 16.4+ installed app, with install guidance, eviction warning, manual sync; no address bar (FR-54).
- Performance (FR-55, NFR-7): slow 3G (400 kbps, 400 ms), 2 GB Android, Catalogue list about 5 seconds; Project open, Proposal generation, Receipt upload budgets "set during UX design"; 99.5% availability (NFR-5).
- Uploads: Receipt JPEG/PNG/HEIC/PDF to 10 MB, resized to about 2 MB, location stripped, malware scan (FR-40); logo PNG/JPG to 2 MB (FR-70); videos offline on request (FR-50); return photo limits unstated.
- Time and money: stored UTC, shown WAT; Business Hours 8am-6pm Mon-Sat plus holidays (NFR-9); naira only, kobo, no USD (FR-19); "NGN" in PRD vs "₦" in Br, UI form unstated.
- Accessibility: no requirement in any of the four documents.
- Regulated data (NFR-3, FR-52,53): NIN kept as reference, hash and last four digits; full NIN only in manual review, deleted 30 days after; staff see it masked, reason-coded reveal logged; never in logs or exports. Bank details Own/Mgr only; changes need a second approver and 24-hour delay; NIN and bank-detail reads audited.

## 8. Voice, tone, brand and trust cues

- Visual identity: the documents say nothing on colour, type, logo, imagery, layout or mood; the only logo is each integrator's own on Proposals (FR-70). Product written lowercase "integrator"; "Integrator" is the customer term.
- Positioning and trust: practitioner to practitioner ("a practicing integrator, not an outside entrant", Br); users need "speed and reliability" (Br); earned, trade-only access ("a prospect, an end client, or a competitor sees no price", FR-3); "stands behind the authenticity of what it sells" (§5.1), counterfeit near 40% in some categories (BrA); approved Suppliers, serials, warranty expiry, numbered tax invoices (FR-14,64,65,77); payments "confirmed by people" (§1); "see exactly what changed since I quoted" (§2.1).
- Tone and language: terms "avoid 'no refunds' wording" (§5.1); quoted microcopy is plain, polite, directive; English implied, British spelling (Catalogue, authorisation); no local languages; Glossary terms must be used exactly (§3).
- Nigerian context: naira, VAT 7.5%, WAT, NIN, CAC, TIN, SON/NCC, NDPA 2023; State and local-government-area addresses, no postcodes (Ad); Lagos first; WhatsApp and phone; official and parallel rates NGN 45+ apart (FR-35); seminar and vouch introductions.

## 9. Open items and tensions

Source: §8.3 (heavy goods vs flat Zone Rate; no warehouse-to-Store step; far-zone return window); counsel/accountant review pending on FR-43,59,61,63,66,77, so related copy is provisional.
1. Hidden prices (FR-3 note: may slow sign-up): L1/L2 see Catalogue, stock status, Lead Time, Documentation and one sentence where a price would be; home, entry points and level progress are undefined. Tech in an approved BA sees no price either, and that sentence is wrong for them. A cold L2 without a voucher has no route (no request, waitlist or contact surface); Adm seeding or platform-issued codes (FR-8) are the only other paths.
2. Timers: after hour 24 an in-time Receipt awaiting Finance has no named state (Ad#43); the hour-40 warning conflicts with expiry re-based on each Receipt (FR-38 vs 51); clock hours vs Business Hours (FR-78).
3. USD hidden, USD rules: Established needs Orders of USD 100+, tiers are USD 1,500/5,000/12,000; FR-22 shows progress in naira at the current rate, so it drifts without purchases.
4. Notifications: email/WhatsApp only, no in-app inbox; P2 while P1 flows say "Integrator is told". Told events outside the 12: credited line (FR-44), Pick-up reminder (FR-45), tier change (FR-22), recovery (FR-71), Hold (FR-72), full-shipment offer (FR-39), Mgr code request (FR-5). Phone OTP channel unstated.
5. Offline: Documentation is offline only after explicit save (UJ-5 relied on the evening before); 14-day lock, eviction, role-change purge and held edits (Ad#9) have no screens; offline Cart unspecified.
6. Proposal: platform attribution on the PDF undefined; validity sentence puts "NGN 5" FX wording before end clients; overwrite warning unstated; sharing is a download (UJ-1) vs Br "shareable"; FR-35 compares at Tunde's cost, so margin impact is not shown.
7. Conflicts and holes: FR-11 table lets Mgr "request application or refund" but its note and FR-43 say refunds need Own; receiver's one-time code (FR-45) delivery; staff devices, statement matching, global lookup; Support contact entry (returns queue P1 in FR-78, Cases P2, workflow P3); Terms at sign-up (FR-69) vs only at L3 request (UJ-2).

## 10. Surface-closure check

No obvious surface: public landing page; home and navigation; in-app notification centre; L1-to-L3 progress help; voucher request; Support contact, Case history; Established-eligibility explainer; WhatsApp preferences; Terms/Privacy pages; Mgr review of held edits; per-Payment status; request status for cancel, amend, Credit Note application; Superseded/Withdrawn indicators; change-of-mind return authorisation (Support-issued); staff global lookup.
No journey lands on: Proposal regenerate/expiry; Credit Note apply/refund; Loyalty Tier; team management; business profile; recovery, closure; cancel/amend; hour-20/40 timers; Receipt re-upload; all staff work except UJ-6/UJ-7.
