---
title: PRD changes and open questions from the UX work
status: for the PRD owner
updated: 2026-10-09
sources:
  - .memlog.md
  - DESIGN.md
  - EXPERIENCE.md
---

# PRD changes and open questions from the UX work

While the screens were walked step by step, the sheets needed things the PRD does not say, says differently, or leaves open. The owner confirmed each step's sheet, so the position taken on a sheet is the UX position. Nothing here has been written into the PRD. Use this as the input to a PRD update (`bmad-prd`, Update) and as a reading list for the architecture and story work. Each item's full reasoning is in `.memlog.md`, in the notes headed "PRD gaps and tensions found while drawing Step N".

Tags. **Added**: a behaviour the sheets show that the PRD lacks. **Changed**: the sheets differ from the PRD text. **Filled**: the PRD is silent and the sheet takes a position. **Open**: not settled by the sheets. The step is the walk-through step whose sheet shows it, in `mockups/`.

## 1. Joining, identity and accounts

- **FR-1** (Open, Step 7): wrong-code and resend caps have no numbers or lockout time.
- **FR-4** (Filled, Steps 7, 15): when the NIN service's retries run out after 24 hours the profile goes to manual review and the Owner is told; after 3 rejected resubmissions the Owner is asked to call or WhatsApp us.
- **FR-4** (Added, Step 15): when Admin asks, an optional "Send a photo of your NIN slip or ID card" step appears on Get approved. A person with no NIN is handled by an Admin-recorded phone or WhatsApp check with no in-app route; the owner removed the "I do not have a NIN" screen.
- **FR-6, FR-7** (Open, Step 7): whether a rejected Level 3 request can be asked again, and with what. A voucher's suspension asks the applicant to re-confirm with an explicit tick (Step 15).
- **FR-8** (Added, Steps 15, 25, 26): a "Create a business" screen with a Pending Activation list, platform vouch codes for a named prospect, and a creator who cannot confirm payments or issue credit notes for a business they created. A Pending Activation Owner sees the four Get approved steps with a different opening line.
- **FR-69** (Added, Steps 7, 10, 16, 26): the Terms are one document accepted at sign-up and again at the Level 3 request, where the screen shows its key points. Admin publishes each version with a plain what-changed list, a preview of the Owner screen, and counsel's approval date and reference; a new version interrupts only the Owner on open and blocks only order creation until accepted.
- **FR-71** (Filled, Step 10): recovery for an Owner who lost their contact is an ID check by phone; the 72-hour wait starts when the check passes; access then opens by itself; every contact on file is told, with a "call us if this was not you" line. A Manager or Technician has no recovery route: the Owner removes and re-invites them.
- **FR-71** (Open, Step 10): a recovery request has no queue or target in FR-78.
- **FR-71** (Added, Steps 8, 10, 23): one fresh code per action, valid 10 minutes, also for adding or removing a passkey, adding a WhatsApp number, inviting a Manager, and applying a credit note; a "Where you are logged in" list with "Log out everywhere else"; logged out after 30 idle days.
- **FR-72** (Filled, Steps 15, 26): a blocked person sees a neutral refusal; a Finance hold is ratified or lifted within 1 business day in Approvals; revoking approval returns the business to Get approved with prices hidden and orders already made still processed; a blocklisted payment sits in Unmatched credits marked blocked.
- **FR-72** (Open, Step 10): an account with a credit-only note balance cannot close for up to 12 months; a "call us" exception is drawn.

## 2. Catalogue, projects and Proposals

- **FR-25** (Added, Step 2): a Room can be deleted, with a confirmation when it holds items and a 30-day recovery as in FR-28.
- **FR-26, FR-27** (Changed, Step 11): a Technician keeps lead time and the discontinued and quarantined warnings, and does not see the back-order discount.
- **FR-26** (Open, Step 23): which units allow fractional quantities; the sheets show the unit after the number.
- **FR-28** (Filled, Steps 11, 23): a check-in is any successful connection; saved projects lock on day 14 with a brass line on day 11 and a banner on day 13, nothing is deleted, and waiting changes are kept. Logging out with changes waiting keeps them on the phone, locked. A Technician sees a later change without a name and can keep or restore their own version. Offline prices older than a day show in brass. Orders, payments and credit notes are not kept on the phone in v1.
- **FR-28** (Open, Step 23, architecture): only the on-screen Parts list is offline; its PDF needs a connection.
- **FR-29, FR-30** (Added, Step 3): custom Proposal lines are entered VAT-included; only the client name is required; the PDF carries no platform branding; the exchange-rate validity sentence is worded in plain words (and adjusts if the rate moves by more than ₦5); a VAT line shows only for VAT-registered businesses.
- **FR-35** (Added, Step 4): the Owner or Manager also sees the margin at today's cost beside the cost-by-cause breakdown.
- **FR-70** (Filled, Step 9): a VAT-registered declaration is allowed without a TIN, with a prompt.
- **FR-22** (Filled, Step 9): the expected tier change is projected from spend so far; the naira thresholds move with the Platform Rate.
- **FR-48** (Added, Steps 11, 18): a guide editor in the console (steps, one optional photo and one optional warning each, a version note, a live phone preview); removing documentation needs a reason; format and size limits are placeholders. A withdrawn manual saved offline opens only after "Open anyway".
- **FR-50** (Open, Step 18, architecture): the server must record which version each phone downloaded to show how many phones hold an old one.

## 3. Orders, delivery and payment

- **FR-33, FR-34** (Filled, Step 25): a Cart order has an optional name (otherwise "Direct buy" and the item count); the limit of 2 unpaid orders is per Project; lines that cannot be ordered are listed at Review or at the top of the Cart.
- **FR-20** (Filled, Steps 25, 26): one message covers frozen order creation and a rate older than 48 hours and hides the cause. The 48 hours stay calendar hours as the PRD has them, with a Saturday and Monday warning on the Platform Rate page; the 36 and 44 hour warnings are placeholders.
- **FR-37** (Added, Steps 14, 25, 26): delivery price requests. The business asks from the delivery screen; Operations answers in about 1 business day with a staff-only courier quote and a price (VAT included) good until the end of the next business day, or a reason when we cannot deliver; a price never goes up after payment. Both times are placeholders. The courier's actual charge is recorded for reporting only.
- **FR-39** (Filled, Step 25): in the 14-day "send what is ready" offer, the business pays the extra delivery at the zone rate before it goes. Absorbing it is the alternative.
- **FR-45** (Filled, Steps 14, 25): an uncollected pick-up is reminded at 7 days, marked not collected at 14, and cancelled and restocked after 30 with a credit-only credit note less the restocking fee. The receiver's one-time code has no generator or message in the PRD.
- **FR-57** (Filled, Step 25): cancelling makes the credit note by itself for confirmed payments; receipts not yet checked go to Finance; a payment under review needs Finance's consent; a reason is optional before approval and required for a request after it.
- **FR-75** (Filled, Step 25): Operations answers a change request on a paid order within the approval time (4 business hours).
- **FR-38, FR-55** (Open, Step 23, architecture): which time counts for a receipt whose upload is interrupted. The sheets use the time we receive the details, with the picture able to finish later.
- **FR-19** (Open, Step 23, architecture): the phone adds in whole kobo; its rounding and quantity rules must match the server and the addendum's test vectors.
- **FR-41** (Filled, Step 13): the review shows the statement's age and coverage and will not suggest matches beyond it; the matched credit arriving before the receipt is a written check; the third Unconfirmed upload goes to Admin as "Needs review"; a second reviewer is a different person. Unmatched bank credits get a daily look (FR-78 gives no target) and a credit from an unknown sender is held while we ask the bank.
- **FR-43** (Filled, Steps 9, 26): refunds are the whole balance only, to a recorded sending account, with a fresh code. Finance sends the transfer in the bank portal and records the bank reference before "Mark as refunded". A pending refund is a banner, not a status; Finance's rejection of an application shows its reason; an application is judged as it stood when asked.

## 4. Staff queues, approvals and controls

- **FR-78** (Added, Steps 13, 15): each queue shows its owner (the role holder plus a named deputy); an item takes a soft lock while someone has it open, and a takeover needs a reason; any queue past 75% of target raises an escalation notice.
- **FR-78** (Open, Steps 14, 25, 26): no queue or target for Operations' cancel and change requests, delivery price requests, call-back-due Support cases, and recovery requests. The sheets use 1 business day for cancel requests and delivery prices and the approval time for changes. FR-66's 14-day counterfeit clock has no card on Admin Today.
- **FR-52** (Added, Steps 13, 16): one Approvals page for everything that needs a second person, with a decline reason that goes back to the asker and Approve off for your own request. A VAT rate change also needs a second approver. The second-approver amount (₦100,000.00) is a placeholder and an Admin setting. The system must know who created, approved or vouched for each business, and shows a written reason when it blocks.
- **FR-52, FR-53** (Added, Steps 15, 16): the founder is an Admin account with a founder flag. The weekly review lists acting-as use, a late counterfeit report accepted, a stock adjustment above the set size, a payment reversal, a ratified hold, a second-person serial correction, a credit note with a second approver, and any rate override above 10%, and cannot be signed off until each is reviewed.
- **FR-53** (Filled, Steps 15, 16): a NIN read needs a reason code (checking by hand, checking for a duplicate, a dispute, other with a note). The audit trail holds identifiers, not names; "Look up" is Admin only.
- **FR-53** (Open, Step 16): alerts on unusual patterns need a baseline of usual counts per person.
- **FR-71** (Open, Step 13): staff two-step sign-in and 12-hour sessions name no method; the sheets show a code or passkey and then a second check.
- **FR-76** (Added, Steps 16, 23): a closed-day calendar pauses target clocks, and the app also reads the Business Hours calendar for "open now" lines and by-times.
- **FR-80** (Open, Steps 13, 16): Finance exports sit on a Reports and exports page; the dashboard needs a plan start date setting, and SM-6 needs cost inputs the console does not hold.

## 5. Returns and Support

- **FR-68** (Filled, Step 12): the issue code reaches the Integrator in the Case and fills the return form; a Technician-raised request pauses the 48 hours for up to 7 days.
- **FR-79** (Added, Steps 12, 14): a "Give Support a code" button on the Help sheet makes a 6-digit code the caller reads aloud; the console has a field for it and records a callback. The Support desk is a console area.
- **FR-58** (Filled, Step 12): Owners and Managers see all of a business's Cases; a Technician sees their own.
- **FR-59** (Filled, Step 12): the app never blocks a report; one past the window still opens a Case.
- **FR-60** (Changed, Step 14): "Held" for a device arriving after a lapse is in the addendum but not in FR-60.
- **FR-62** (Filled, Step 12): the courier handover form asks for a photo of the courier's receipt.
- **FR-64** (Added, Step 12): delivered order lines show "Warranty until"; the Orders list gets a Problems segment.
- **FR-44, FR-18** (Filled, Step 14): stock is reserved at Paid, so Approve needs a shelf check per line; Approve stays off until every in-stock line is found or credited.
- **FR-17, FR-74** (Open, Step 14): there is no Supplier order entity, so Back-orders lists promised ship-by dates and a late flag and nothing more.
- **PRD §8.3, question 5** (Open, Step 12): whether far zones need a longer window than 48 hours for courier handover is not answered.

## 6. Catalogue, stock and suppliers

- **FR-12** (Filled, Step 17): each Product Range carries an Admin-set flag for NCC type approval or SONCAP; each of the four returnability flags is answered Yes or No with no default; Products get a serial-format field.
- **FR-16** (Filled, Step 17): releasing safety stock to a back-order item is a ledger line with a reason; the stock adjustment sign-off size is an Admin setting (a placeholder).
- **FR-74** (Filled, Step 17): landed-cost components accept USD or NGN; goods in records serial numbers and dispatch verifies them; a batch is a goods-in record, so Suspected can be marked per batch or per product.
- **FR-15** (Filled, Step 17): a file that changes prices on more than 20 products, or moves a cost by more than 15%, waits for Admin as a whole file, with a one-click rollback after. Applying only the rows with no price change was offered and not chosen.
- **FR-17** (Filled, Step 17): a back-order discount above 10% goes to Approvals.
- **FR-14** (Filled, Step 18): "Approve" on a supplier stays off until its returns, dead-on-arrival and counterfeit-chargeback terms are written down, with an "Approve with a reason" route for Admin. The shorter of the supplier's and the 30-day dead-on-arrival window applies. Stopping a supplier takes its products off sale after a preview, with replacements named per Product; the PRD is silent on this.
- **FR-47, FR-56** (Open, Step 18): checks that an area belongs to exactly one zone; an initial list of local government areas per zone; the zone rates and estimates drawn are placeholders.
- **Product Ranges** (Open, Step 17): creating, renaming and retiring a range is assumed to be Admin's and is not drawn.

## 7. Notifications

- **FR-51** (Filled, Steps 10, 23): the every-message table of the 12 launch events is the proposed notification matrix; money or deadline events go at once and the rest at 9 am; a counterfeit recall notice stays on Home until someone taps "I have seen this"; there is no inbox and no push in v1.

## 8. Waiting on others

- **Counsel:** the nine wording items under Voice and Tone in `EXPERIENCE.md`; PRD §8.2 items (the credit note ledger and expiry, returns terms, data protection obligations); what is measured about use, cookies and analytics.
- **Accountant:** FR-77's tax invoice and credit-note document layouts, VAT on delivery and credit notes, and e-invoicing timing.
- **Architecture:** the items marked architecture above (receipt time, rounding, offline Parts list PDF, documentation versions per phone).
- **Owner, before launch:** the placeholders and defaults listed under Open items in `EXPERIENCE.md`.
