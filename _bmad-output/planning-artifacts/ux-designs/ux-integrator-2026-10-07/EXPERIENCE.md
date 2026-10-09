---
name: integrator
status: final
sources:
  - _bmad-output/planning-artifacts/prds/prd-integrator-2026-09-27/prd.md
  - _bmad-output/planning-artifacts/prds/prd-integrator-2026-09-27/addendum.md
  - _bmad-output/planning-artifacts/briefs/brief-integrator-2026-09-27/brief.md
  - _bmad-output/planning-artifacts/briefs/brief-integrator-2026-09-27/addendum.md
updated: 2026-10-09
---

# integrator — Experience Spine

> Multi-surface and regulated. An installed phone app for the field, the same app in a desktop browser for the office, and a desktop-only staff console. Paired with `DESIGN.md`, which owns how it looks. This spine owns how it works: where things live, what each screen does, what every state says, and the rules that only this product has (money, time, who sees what, trust, offline). Product requirements, personas and the glossary live in the PRD and are not restated here. integrator is a working title.

Every claim here was walked step by step with the owner and confirmed (the decision record is `.memlog.md`). The mockups in `mockups/` are wireframes in the Ledger look. **The spines win on any conflict with a mockup.** Where a mockup and this file or `DESIGN.md` disagree, the spines are the contract and the mockup is out of date.

**Where to look.** Building a screen: Information Architecture, then Component Patterns and State Patterns. Writing copy: Voice and Tone. Money, time, roles, trust and offline behaviour: the five product sections from Money and numbers to Offline and weak signal. Checking a journey: Key Flows. What is still open: Open items and assumptions.

## Foundation

**Surfaces.** Three. The **installed phone app** is the primary surface in the field: Technicians on site with poor signal, Owners and Managers ordering from a van. The **same app in a laptop or desktop browser** is for the office (Proposals, Orders, Credit notes). The **staff console** is for Finance, Operations and Admin and is desktop only. A phone that opens the console gets a notice that it is for a desktop. The app is an installable, offline-capable web app. There is no native app in version 1.

**People.** Integrator side: Owner, Manager, Technician (a business has one Owner; invitations start once it is approved). Staff: Finance, Operations (which holds the Support duties as a switchable group, so Support can become its own role later) and Admin (the founder is an Admin with a founder flag). The end client never signs in and only receives a Proposal PDF. Suppliers never sign in.

**Posture.** Regulated: the product handles money, bank-statement matching and identity data (the National Identification Number, NIN). The payment, verification, refund and hold screens are held to the higher rigour. Accessibility is WCAG 2.2 level AA on every screen, including the PDFs, emails and WhatsApp messages the product produces. English for Nigeria only. Light mode only. Naira only for a business, with VAT included on every price. Times in West Africa Time.

**No UI system.** No component library is named. The app inherits platform conventions for the share sheet, the camera, the keyboard type, the PDF viewer and system larger text, and nothing else. `DESIGN.md` is the visual identity.

**Platforms.** The last two versions of Chrome, Edge, Firefox, Safari and Samsung Internet. Android 9 or newer with Chrome. iOS 16.4 or newer for the installed app. The test phone is a low-end Android with 2 GB of memory on slow 3G (400 kbps, 400 ms round trip); the Catalogue list loads in about 5 seconds on it.

**Glossary.** PRD terms are used exactly (Business Account, Project, Room, Proposal, Order, Receipt, Price Lock, Credit Note, Loyalty Tier, Back-order Item, Issue Code, Return Request, Case). What a business reads is sometimes a plainer word for the same thing; the mapping is the word list under Voice and Tone. A business never sees the words Level 1, 2 or 3, or our role names.

## Information Architecture

### Navigation rules

| Rule | What it means |
|---|---|
| Five tabs, always in this order | Home, Catalogue, Projects, Orders, Account. Before approval there are two: Catalogue and Get approved. A Technician has the same five, and Orders shows status with no amounts. A role sees only the tabs it can use. |
| The cart is not a tab | A top-bar icon with a count for Owners and Managers only. A Technician and an Owner not yet approved have none. On a laptop it sits at the top right. |
| One home for each thing | A thing lives in one place. Other places link to it and never copy it. |
| Help is always in the same place | "Call or WhatsApp us" is the last block of every screen and a row in Account. The Help sheet is where a person gives Support a code. |
| The tab bar becomes a rail on a laptop | At 1024px and up. Lists with an item behind them become a list and a detail. The order of reading never changes with the layout. |
| The console is organised by work | A left rail in groups (Queues, Work, Money, Catalogue, People, Rules, Watch). Each role sees only its own groups. Today is first. A queue is a list with a preview, and the full page opens from it. |
| No in-app inbox | Each of the twelve launch events lives on the screen it is about. Home "Needs your attention" carries whatever needs the person, including a counterfeit recall notice until they tap "I have seen this". No push notifications in version 1. |

### Where each thing lives

| Thing | Lives in | Also reachable from |
|---|---|---|
| Project, rooms and items | Projects tab | Home (recent projects); the product page ("Add to" a room); the orders made from it |
| Parts list | Inside the Project | The Technician's view of the Project |
| Proposal | Inside the Project | The order made from it ("what changed since the Proposal") |
| Cart | Top-bar icon | Home ("Buy parts without a project"); the product page |
| Order | Orders tab | Home (needs your attention); the Project; every message about it |
| Payments and receipts | Inside the order | Home (a payment due, with its clock) |
| Credit notes | Account | The order's payment screen ("Use some on this order"); Home (a note about to expire) |
| Cases, return requests, issue codes | Orders, Problems | The order the device came with; Home; the Help sheet |
| Manuals and guides | The product page | Project rows; Saved on this phone |
| Vouch codes | Account | Get approved (where the new person enters one) |
| Team and invitations | Account | Home (a Manager's request for a vouch code) |
| Loyalty tier, business profile | Account | None |
| Notification settings | Account | The footer of every message |
| Saved on this phone | Account | A Project ("Save for offline"); the check-in banner |
| Terms and Privacy Notice | Account | Sign-up; the New Terms screen |
| Help | Last block of every screen | Account |

### The app, by place

Each row is a screen or a small group of screens. "Essentials" are the decisions that are not obvious from the PRD. Layout is in the linked mockups.

#### Before sign-in and approval

Mockups: [joining and verification](mockups/app/flow-uj2-step7-joining.html) (UJ-2), [invitation](mockups/app/flow-uj5-step11-technician.html), [new Terms and closing](mockups/app/flow-acct-step10-you-security.html).

| Screen | Who | Essentials |
|---|---|---|
| Sign up and log in | Visitor | One field for email or phone. Terms and Privacy Notice ticked up front. A 6-digit code valid 10 minutes with a resend countdown; paste and auto-fill work. Log in is the same field and a code, or a passkey where the phone supports it. A separate next screen asks about WhatsApp, per number; "No thanks" keeps updates in the app. |
| Get approved | Owner, not yet approved | One of two tabs. A four-step checklist: verify your contact, confirm who you are, enter a vouch code, we review and approve within 1 business day. No Level wording. |
| Catalogue before approval | Owner, not yet approved | The same Catalogue with stock status, lead time and documentation, and no prices, price sort or price filter. One line says "Prices are shown once your business is approved." and links to the checklist. |
| Confirm who you are | Owner | Name, date of birth, address and NIN required; business address optional. The NIN is masked after typing with a Show control, and a line says only the verification team sees it. When Admin asks, an optional step "Send a photo of your NIN slip or ID card" appears. |
| Confirm who you are: outcomes (FR-4) | Owner | Confirmed; sent to our team within 1 business day (a definitive mismatch, a duplicate, and every later check on that account); service busy, retried up to 24 hours and then to manual review; team rejection with a reason and up to 3 resubmissions. There is no in-app route for someone with no NIN: that is handled by phone or WhatsApp through "Call or WhatsApp us". |
| Ask for approval | Owner | A vouch code, the trade name, and the key points of the Terms in plain words with a tick (counsel wording). A "No code? Call or WhatsApp us" line, because Admin can issue platform codes. |
| Approved | Owner | A welcome with three next steps and the five-tab bar. No tour. |
| Accept an invitation | Manager, Technician | The link opens with the contact locked to the one invited, valid 7 days. Terms and Privacy Notice are accepted at first login, then a code as at sign-up. States: expired, cancelled, contact already on another business, first-time welcome (three lines, once). |
| A business set up by us | Owner | "Finish setting up your business": the same four steps as Get approved, without a code. No order is possible until done. |
| Text pages | Anyone | Terms of use, Privacy Notice, accessibility statement, Install the app (iPhone steps written in words), signed out, service down, not found. Spine only: the page template and the words under Voice and Tone. |

#### Home

Mockups: [shell, Home and Projects](mockups/app/flow-uj1-step1-shell.html), [a Technician's Home](mockups/app/flow-uj5-step11-technician.html).

| Screen | Who | Essentials |
|---|---|---|
| Home | Owner, Manager | Opens first. "Needs your attention" first: a payment with its price-hold countdown, a return request waiting for approval, a credit note about to expire, a Manager's request for a vouch code, a counterfeit recall notice. Then recent projects, then "New project" and "Buy parts without a project" (which opens the Catalogue with Add to cart). |
| Home | Technician | The same shell and five tabs with no cart and no money. "Needs your attention" holds only what concerns them, such as a manual that was replaced. Recent projects carry "Saved on this phone". |

#### Catalogue

Mockups: [product pages and the picker](mockups/app/flow-uj1-step2-build-project.html), [documentation and guides on site](mockups/app/flow-uj5-step11-technician.html).

| Screen | Who | Essentials |
|---|---|---|
| Browse, search, filters | Everyone from sign-in | Search by name, brand, model and SKU with typo tolerance. A category tree built from Product Ranges. Filters for brand, range and stock status. Sort by name; price sort only for Owner and Manager. A row shows name, brand, model, SKU, stock status and price, and no photo. An Out of stock item without back-order is shown but disabled with its reason. Lists load 20 at a time. Works offline on the last synced Catalogue; offline, an Owner or Manager sees prices only for products in the business's Projects and those they recently viewed, and other rows show no price until the phone is back online (FR-73: no bulk price listing on a phone). |
| Product page | Everyone | One photo. The price, "each, VAT included". Stock status with lead time for a back-order item. A quantity stepper and the add buttons, which depend on how the page was opened: from a room it says "Add to <room>"; from the Catalogue tab it offers "Add to cart" first and "Add to a project" second. Specifications and installation guides are open to everyone. A Technician sees no price and no add controls. |
| Guides and manuals | Everyone | Three slots per product: a written guide (step count, saved date, version), the manufacturer manual as a PDF (opens in the phone's own viewer), and a video (opt-in to save; "Needs signal" when not saved). A replaced manual still opens with a Replaced warning. A withdrawn one opens only after "Open anyway". With nothing yet: "Documentation coming soon." and "Need it now? Call or WhatsApp us." |
| Written guide | Everyone, built for the Technician | One step to a screen, large type, a text-size control, a step strip, and big Back and Next buttons for a hand on a ladder. |

#### Projects

Mockups: [shell](mockups/app/flow-uj1-step1-shell.html), [building a Project](mockups/app/flow-uj1-step2-build-project.html), [Proposal](mockups/app/flow-uj1-step3-proposal.html), [creating the Order](mockups/app/flow-uj1-step4-order.html), [the Cart and the checks](mockups/app/flow-uj3-step25a-cart-and-checks.html).

| Screen | Who | Essentials |
|---|---|---|
| Projects list | Owner, Manager, Technician | Every job in one list with the project total (VAT included, Owner and Manager only) and neutral outlined status labels; only a clock or a problem is tinted. |
| New project | All three | Asks for a name only. The first room is created for you and can be renamed. Works offline. |
| A Project | All three | Rooms with "Add product" inside each, a pencil to rename, Add room. A Room can be deleted: a confirmation if it holds items and a 30-day undo (a PRD addition to FR-25). Totals with "VAT included". Actions: Parts list, Save for offline, Generate proposal, Create order. A Technician sees the same rooms, items and quantities with no price area drawn at all, a back-order flag and lead time without the discount, each product row saying whether its guide and manual are saved, and an offline note with the last-checked time. |
| Add products | All three | A full-screen picker for one room, with a bottom bar "Done · n items in <room>" so several items go in one visit. Add turns into a quantity stepper. |
| Save for offline | All three | A bottom sheet, titled "Make this Project available offline", listing what will be saved (project, product pages, PDFs and written guides, with size). Videos are the person's choice. Warns that the phone may remove saved files when space runs short. |
| Parts list | All three | Each product with its total quantity and the room split. No prices for anyone, so the same list can be shared. Save PDF and Share. |
| Proposal | Owner, Manager | One screen: client (only the name is required), your margin as one percentage that starts at the last one used, a private "Only you see this" panel (cost, margin, extra lines, client total), extra lines such as labour entered VAT-included, and a "Show a VAT line on the PDF" switch shown only for VAT-registered businesses. Generating needs a connection. "Ready" offers Share (the phone's share sheet) and Save PDF. The Project carries one Proposal card: Valid until, Out of date (project edited since), Expired (still downloadable; ordering uses today's prices), and a Replace confirmation "The old PDF cannot be recovered". |
| Proposal PDF | The client | The integrator's letterhead only, no platform branding. Client and project blocks, items by room with quantity, unit price and amount, room totals, service lines, total with VAT included, back-order lines marked with a delivery time, and the validity sentence about the exchange rate (wording for counsel). Never any cost, markup, discount or margin, in the text or the file's hidden data. |
| Create order | Owner, Manager | Three steps with a step indicator and a sticky bar: Review, Delivery, Confirm. If the price moves while reviewing, a sheet shows the old and new amounts. |
| Create order: Review | Owner, Manager | Shows "Compared with your Proposal" when one exists (cost at the Proposal, exchange-rate change, tier, items and stock, cost today), a plain sentence that the client's Proposal does not change on its own, and the margin at today's cost. A back-order item needs two disclosures and an unticked box before Continue works. |
| Create order: Delivery | Owner, Manager | A Delivery or Pick-up switch, a structured address (state, local government, area, optional landmark, receiver name and phone) with the zone, price and estimate appearing at once, and a choice of one delivery when everything is ready (with the 14-day offer) or send what is ready first. |
| Create order: Confirm | Owner, Manager | Shows the Amount Due, the 24-hour price-hold explanation and that an unpaid order is cancelled after 48 hours, the Terms line and "Create order". |
| Cart | Owner, Manager | The top-bar icon. A cart belongs to one person and is never shared, and it cannot become a Project. Whole-number steppers, Remove in words, a total with VAT included, and a line that these are today's prices, the price is fixed when the order is created, and nothing is kept until payment is confirmed. An item that sold out is shown as Sold out with only Remove, and Choose delivery or pick-up stays off until she removes it. An item that became back-order shows its tag and the old and new price, and the back-order agreement is asked on the next screen. |
| Order from a Cart | Owner, Manager | Two steps, Delivery or pick-up and Confirm, because a Cart has no Proposal to compare with. An optional order name (otherwise "Direct buy" and the item count). The items leave the cart when the order is created. |
| Checks before an order | Owner, Manager | Each block says what is wrong, why, and what to do. Lines that cannot be ordered are listed with Remove and Find another, and nothing is removed for the person. An address in a zone that is coming soon offers Collect from the Main Store or Ask us for a delivery price. "Prices are being updated" stops ordering with one message for a freeze and for a rate waiting to be confirmed. Other blocks: two unpaid orders on one Project, a business on hold, new Terms not accepted, and no signal. |
| Delete or restore a room or project | Owner, Manager | Spine only. Undo for 30 days. |

#### Orders

Mockups: [pay and receipt](mockups/app/flow-uj1-step5-payment.html), [the Orders tab](mockups/app/flow-uj1-step6-orders.html), [using a credit note](mockups/app/flow-acct-step9-money-business.html), [cancel and change an order](mockups/app/flow-uj1-step25b-cancel-and-change-order.html), [returns](mockups/app/flow-uj7-step12-returns.html).

| Screen | Who | Essentials |
|---|---|---|
| Orders list | Owner, Manager, Technician | Three segments: Open, Done, Problems. An order that needs action is tinted and listed first. Problems lists what needs the person first, then an Earlier group. A Technician sees status only. |
| One order | Owner, Manager; Technician with no amounts | Deliveries first, each with its own neutral status label, its items, the courier and tracking reference with Copy, and a serial-numbers row. Then Payments (each transfer with Confirmed, Unconfirmed or under review), Documents and guides (the tax invoice PDF, installation guides), and the actions for where the order is (table below). Delivered lines show "Warranty until". An order shows its problems. A Technician sees status, deliveries, serial numbers and guides, and no amounts, payments, bank details or documents. |
| Pay by bank transfer | Owner, Manager | Amount due, Confirmed so far, Still to pay, in that order. The price-hold countdown with the exact deadline. The bank, account number, account name and the order reference to put in the narration, each with a Copy button. A note to transfer the exact amount and that several transfers each need their own receipt. A "Use some on this order" row appears only when credit exists. "I will pay later". |
| Upload receipt | Owner, Manager | A photo (camera) or a file (photo or PDF, up to 10 MB), and the amount (prefilled), sender name, sender bank, date and time, and bank reference. Sender name and bank are remembered. After sending: "Receipt under review" and "Price held", plain wording about working hours, a status timeline, and "Send another receipt". On a dropped signal the upload keeps the photo and details, shows progress, retries, and resumes if the app is reopened. With no signal at all the button stays off and says why. |
| A payment problem | Owner, Manager | Tinted red, with the PRD's own words: "Payment unconfirmed: please upload your receipt again or pay the full amount." The unmatched amount, "Upload a corrected receipt within 24 hours and we keep your price", an Upload receipt again button, and a call or WhatsApp line. |
| Order states | Owner, Manager | Cards at the top of the order for: awaiting payment (a countdown to the price-hold deadline; brass at 4 hours left, "After that the price may change with the exchange rate"; brass at 8 hours before it is cancelled, "This order is cancelled in 8 hours"), part paid, price no longer held (the new amount due, confirmed so far, still to pay), expired (money received kept as a credit note), cancelled, ready for pickup (hours, collector name and ID), delivery failed, delivery lost (a case opened), delivered (report within 5 days for damage or a wrong item; warranty per product). Only action or trouble is tinted. |
| Use a credit note | Owner, Manager | Reached from the order's payment screen or from "Use on an order" on a note. It suggests the note that expires first and shows what is still to pay by transfer. It counts as paid the moment it is asked for; Finance completes it within 1 business day; if the order is cancelled or expires the credit goes back to the note with its original expiry. One note per request. |
| Cancel and change an order | Owner, Manager | See the table below. Cancelling before approval is at once, the confirmed amount becomes a credit note that is not paid out to the bank, and the dialog lists the consequences with an optional reason. Changing the delivery moves only the delivery charge and never the price of the goods or the end of the price hold. After approval, cancelling is a request, per delivery, with a required reason. A back-order line more than 7 days late can be cancelled at once for a credit note. |
| Delivery exceptions | Owner, Manager | Delivery failed: update the receiver details and it is sent again. Shipping in full has waited 14 days: "Send what is ready now?", where the business pays the extra delivery at the zone rate before it is sent. A pick-up waiting: a reminder at 7 days, marked not collected at 14, and after 30 days it may be cancelled and put back on the shelf, with a credit note less the restocking fee. |
| Report a problem | Any role | Opened from a delivery on the order. It opens a Case and starts the reporting clock. Faulty or damaged, or wrong item (Owners and Managers also see "It did not arrive" and "I changed my mind"). Which item, with its serial and warranty-until date. What happened, photos, a callback phone, and how long they have, in dates for this delivery. A report is always accepted, even past the usual window, and a call or WhatsApp line is offered. |
| The Case | Any role | What Support has done (report received, caller checked, remote fix tried, issue code made). The issue code appears here with a "Raise the return request" button that fills it in. Owners and Managers see all of a business's Cases, a Technician sees their own. |
| Raise a return request | Any role | The issue code filled in with its time left; a reason (damaged on the way, wrong item, does not work out of the box, stopped working under warranty, I think it is fake); photos of the device, the label and the box; the fault. A Technician's request says "Send for approval", and the 48 hours pause for up to 7 days. |
| Approve a return request | Owner, Manager | Under Needs your attention. The order line is matched from the serial and can be corrected to another delivered line that includes the device. Approve, or Decline with a reason that goes to the Technician. No amounts are needed to decide. |
| Bring the device back | Any role | A countdown to a deadline that already counts the paused time. Take it to a store (hours shown) or send by courier with the courier's name, a tracking number and a photo of the receipt. A plain note on who pays freight (the platform pays actual courier cost, up to the delivery rate, as a credit note when the fault is its own). |
| The outcome | Any role | Every step with its date and the decision in plain words: a replacement as a zero-value delivery under the original order that keeps the remaining warranty, a repair, a credit note, or a rejection with its reason. Money is described and not shown to a Technician. |
| Tax invoice and credit-note documents | Owner, Manager | Download rows only. The layouts wait for the accountant to settle the fields (FR-77). They follow the Proposal PDF's style. |

**What a person can do at each point in an order's life** (Owners and Managers only; a Technician sees none of these actions):

| Where the order is | What they can do | What happens |
|---|---|---|
| Awaiting payment, nothing confirmed | Upload receipt. Change delivery details. Cancel order. | Change is at once and only the delivery charge moves. Cancel is at once with nothing to credit. A receipt sent but not yet checked goes to Finance. |
| Awaiting payment, a payment under review | Upload receipt. Change delivery details. Ask finance to cancel. | Finance has to agree before the order is cancelled. |
| Paid, waiting for approval | Change delivery details (a request). Cancel order. | Cancel is at once and what was confirmed becomes a credit note. Change is answered by Operations by the approval time. Neither restarts the price hold. |
| Approved, nothing sent yet | Ask to cancel (a request, one delivery at a time). | Deliveries are on hold. Operations decides within 1 business day. A back-order item already ordered from the supplier cannot be cancelled. |
| A back-order line more than 7 days late | Cancel this item. | At once, for a credit note, whether or not it has been ordered. |
| In transit or delivered | Report a problem. | Cancelling no longer applies. The returns rules apply. |
| Delivery failed | Update receiver details. | The delivery goes back to Processing and is sent again. |
| Expired or cancelled | Make a new order from the Project, or put the parts back in the cart. | Money received is already a credit note. |

#### Account

Mockups: [Account and vouching](mockups/app/flow-uj2-step8-account-vouching.html), [money and the business](mockups/app/flow-acct-step9-money-business.html), [the You rows](mockups/app/flow-acct-step10-you-security.html), [Saved on this phone](mockups/app/flow-uj5-step11-technician.html).

| Screen | Who | Essentials |
|---|---|---|
| Account home | All three | The Owner sees three groups: Your business (Business profile, Team, Vouch codes), Money (Credit notes, Loyalty tier), You (Notifications, Sign-in and security, Saved on this phone, Terms and Privacy, Help), then Sign out and a quiet "Close this business account" link. Each row says in one line what is behind it. A row a role cannot use is not shown. A Manager sees the profile, a team list limited to Technicians, "Ask the Owner" for a vouch code, credit notes and the tier. A Technician sees only the You rows. |
| Vouch codes | Owner | Limits first: codes waiting (1 of 2) and people in the last 90 days (3 of 6). Make a vouch code. Waiting codes can be shown or shared again in full. Earlier codes show only the last 4 characters and how they ended (Used, Expired, Cancelled by us), and never whether the person was approved. A business that is not yet established sees progress as a list of qualifying orders (counted, too small to count, has an open return), with no dollar figure. |
| Make a vouch code | Owner | The person's full name as on their NIN, and the phone or email they signed up with. A plain note that if they are later suspended for fraud the voucher's own vouching can be paused. Then a fresh one-time code in a bottom sheet. Then the code is shown large with Share on WhatsApp first, Copy, and three next steps for the prospect. |
| Ask the Owner for a code | Manager | The person's name and contact. The Owner sees it on Home under Needs your attention with the form pre-filled, confirms with a fresh code, and the Manager is told. |
| Team | Owner; Manager for Technicians | People by role, then invitations still waiting (7 days, bound to the contact, can be cancelled). Invite by phone or email with an optional name and a role chosen by what the person can do, in plain words. A fresh code is asked for changing a role and for inviting a Manager. Removing a person signs them out and clears their saved projects from their phone at its next connection, while the projects stay with the business. |
| Credit notes | Owner, Manager | "Available to use" total at the top. Tabs: Available, Used or expired. The note that expires first is tinted and listed first. Friendly status words: Ready to use, Partly used, Fully used, Refunded, Expired. It says credit lasts 12 months and that the Owner is reminded 60 and 14 days before. |
| One credit note | Owner, Manager | The reason in plain words, issued, used and balance, a history, the credit-note PDF. "Use on an order". "Ask for a refund" appears only for the Owner and only when the reason allows: refundable for an overpayment, an unfulfillable line, a platform-fault return, freight reimbursement, a counterfeit or a non-delivery; credit only for a cancelled order, a change-of-mind return or an uncollected pick-up. |
| Ask for a refund | Owner | The whole balance only, to a recorded sending account picked from a list and never typed, then a fresh one-time code. Finance sends it within 1 business day. The balance is held while it waits. An expired note can be refunded on request (counsel to confirm). |
| Loyalty tier | Owner, Manager | The tier and what it is worth (prices already include the discount). Counted spend in naira at today's rate with a meter toward the next tier, and how spend is counted. The next review date (the 1st of the month) and a 7-day notice of an expected change. The whole ladder with naira thresholds at today's rate. A line that tier and back-order discounts do not add up. |
| Business profile | Owner, Manager | Trade name, logo (PNG or JPG up to 2 MB, name-only header when missing), address, phone, email, an optional Tax Identification Number (TIN), and a declaration of whether the business is VAT-registered, which decides whether a Proposal can show a VAT line. |
| Notifications | All three | Switches per channel only. WhatsApp is on or off per number, in words, with a note that an undelivered message is emailed after 15 minutes. Email is always on. No adverts, no per-message switches. Adding a number needs a fresh code, then verification, then the opt-in question. Turning WhatsApp off warns that time-sensitive messages may be slower. A Technician sees only the kinds of message, with no amounts. |
| Sign-in and security | All three | The one-time code is always available. A passkey where the phone supports it, and a fresh code to add or remove one. Contacts change only with a fresh code and verification of the new one. A "Where you are logged in" list with Log out and Log out everywhere else. Logged out after 30 idle days. |
| Getting back in | Owner | For an Owner who has lost their phone or email: business name, full name and a phone to call. Then a waiting screen with four steps (request received, our team calls to check ID, a 72-hour wait that starts when the check passes, log in with the new contact). Every contact on file is told, with a "call us if this was not you" line, and there is a cancel link. A Manager or Technician is told to ask the Owner to remove and re-invite them. |
| New Terms | Owner | Shown once, when the Owner opens the app after a new version. A short list of what changed, written by staff, a tick to accept, and "Not now". Only creating an order is stopped until they accept. A Manager sees a notice above Create order. |
| Close the business | Owner | A three-condition checklist (no open orders, no open return requests or cases, no credit notes with a balance), each shown Clear or Needs action with a link to fix it. A last-check screen lists what is erased and what the law makes us keep, an "I understand" tick, a fresh code, and a red Close the account button. A "call us" exception covers credit-only balances. |
| Saved on this phone | All three | Connection status and the last check. The 14-day check-in with its date. Changes waiting. Conflicts needing a look, with Keep latest or Use mine (names shown to Owners and Managers only). Saved projects with their sizes and Remove. Restore an earlier version for 30 days. The storage-eviction warning. Logging out with changes waiting keeps them on the phone, locked, and they sync at the next login; "Log out and clear this phone" is offered only when nothing is waiting. |
| Install the app, Help | All three | Spine only. Installing is described under Responsive & Platform. The Help sheet carries "Call or WhatsApp us" and "Give Support a code" (a 6-digit code read aloud so Support can verify the caller). |

### The staff console, by role

Desktop only, built for {spacing.console-built-for} and reflowing below {spacing.console-reflow-below}. The same Ledger look, denser: {typography.table-console} tables, {spacing.target-console} controls. A top bar with the brand, "Staff console", the clock and the time the team is open until, then the role and the person. A left rail in groups, role-aware, so nobody sees a link they cannot use, with counts that turn brass when over target, and a note naming the person's deputy and an out-of-office switch (a deputy gets the queue within a minute and their actions are marked "acting for"). Staff sign in with two steps and a 12-hour session; the console warns 5 minutes before it ends with "Stay signed in".

How every queue works: oldest first; a count; the age of the oldest item against its target on a meter; the number over target and the on-time rate against a goal; past 75% of its target an item escalates to Admin and shows the owner and deputy. A list with a preview panel beside it, and a full page that opens from it. J, K, Enter and / move around; money actions are never a single key. A soft lock marks an item someone has open, and a takeover needs a reason. Every decision records a reason, and a decline's reason goes back to the person who asked. Any use of another role's power shows "You are acting as Finance" first and goes to the founder's weekly review.

#### Finance

Mockups: [payment confirmation, unmatched credits, statement import](mockups/console/flow-uj6-step13-console-finance.html) (UJ-6), [the Platform Rate, credit note requests, credit notes and refunds](mockups/console/flow-uj6-step26a-console-finance-rate-credit.html).

| Screen | Essentials |
|---|---|
| Today | A greeting, a notice when the bank statement is old ("Import the latest"), one card per queue (Receipts, Credit note requests, Unmatched credits), a Platform Rate card, and "Waiting for you as second approver". The rate card is brass from 36 hours old and red from 44, because new orders stop at 48. |
| Receipts | Oldest first. Target 2 business hours; time outside Business Hours does not count. Filters: All, Mine, Over target, Sent back before, Second review. A table of age, business and order, declared amount, sender and status. A late row is tinted with a red bar and "Target exceeded". A preview panel shows the receipt and the three amounts. |
| Review a receipt | Three columns in reading order: what the business sent, the order (amount due, confirmed so far, credit applied, still to pay, price-hold timing, "sent back so far: 0 of 3"), and the bank statement (the best-matching credit and five checks written in words: amount, sender, bank reference unused, money arrived before the receipt, your own accounts clear). |
| Review a receipt: deciding | An editable amount to confirm, with a recorded reason if it differs. "Confirm payment" is the only primary button. "Payment unconfirmed…" uses the PRD's literal message; after 3 Unconfirmed uploads on one order it needs a review by Admin. "Send to a second reviewer…" for a different sender. A line says what happens next: the order becomes Paid and goes to Operations, the business is told, a ledger line is written. |
| Unmatched credits | A daily list of bank credits with no payment, and receipts for orders that expired, were cancelled or are already paid, each with its likely answer. Nothing is applied automatically. Resolving one with a credit note needs a second approver above the set amount. "Hold and ask the bank" for an unknown sender. |
| Bank statements | Choose a file, check what it will do (rows read, new, already imported and skipped, columns recognised, covers up to), import. The column layout is remembered per bank. Importing the same file twice changes nothing. |
| Platform Rate | The rate in force, its source and spread, its age, and the exact time new orders would stop (48 calendar hours after the last reconfirmation), with a warning when that falls before the team opens, as it does after a Saturday reconfirmation. Reconfirm the rate, which is off once done today. Change the rate. Pause new orders. The five rules in words. An append-only history of every reconfirmation and change. |
| Change the rate | The new rate with its percentage move, the source with its time and spread, a why. A switch to pause new orders while it waits, off by default. A price preview for real products, what it touches (open carts, projects, orders past their hold; orders inside their hold are not changed), and checks. Above 3% it goes to a second person. Above 10% it is refused as a probable typing error unless Admin overrides with a reason. |
| Credit note requests | Oldest first, target 1 business day. Three kinds: use a note on an order, a refund, and a credit for a line Operations could not fill. A request is judged as it stood when it was made, even if Finance completes it later. |
| Review: use a note | What was asked and when it began to count as paid, the order after applying, the note, and written checks (belongs to the business, the balance covers it, not expired on the day asked, the order still awaiting payment, your own accounts, second person). Apply, or Decline with a reason. |
| Review: refund | Whole balance only. The fresh code shown as used. The sending account the Owner picked. Checks: the reason allows a refund, the account has paid us, a fresh code, no hold or blocklist, your own accounts, second person. A two-step panel: send the money in the bank's own portal, then record the bank reference. "Mark as refunded" stays off until the reference is entered. The platform does not move money. |
| Credit notes and refunds | Every credit note with ageing tiles (open, expiring in 60 days, expiring in 14 days, expired with a balance), drafts, status chips and whether each can be refunded or is credit only. "Issue a credit note" with the PRD's eight reasons, the goods and VAT split worked out, and the same checks. A note for a business you created, approved or vouched for is refused with the reason in words. |
| Orders and payments, Reports and exports | Spine only. A search, a table and the detail already drawn; and five CSV exports (orders, payments, credit notes with ageing, monthly VAT output, bank reconciliation) with a date range. |

#### Operations, with Support duties

Mockups: [approvals, shipments, support desk, returned devices](mockups/console/flow-uj6-step14-console-operations.html) (UJ-6, UJ-7), [products, stock, goods in, bulk upload](mockups/console/flow-uj6-step17-console-catalogue-stock.html), [documentation, guide editor, suppliers, stores and zones](mockups/console/flow-uj6-step18-console-content-suppliers-zones.html), [delivery price requests](mockups/console/flow-uj6-step26b-console-admin-ops-gaps.html).

| Screen | Essentials |
|---|---|
| Today | Cards for Order approvals (4 business hours), Returns to investigate (2 business days from arrival), Support cases (with a call-back-due count), Shipments, Back-orders (running late, with the 7-day cancel right noted) and Stock (at or below threshold). |
| Order approvals | A list on the left and the order on the right with delivery, receiver and ships-in-parts-or-full. A lines table with ordered, reserved and left after, and a shelf check per line: Found on the shelf, Not found: credit this line, or Mark ordered from supplier for a back-order line. |
| Order approvals: approving | "Approve order" is the only primary button, stays off until every in-stock line is found or credited, and says what happens (the order is Approved, delivery 1 goes to Processing, a back-order delivery waits for the supplier order, the business is told). Credit a line handles a missing item, and if every line is credited the order is cancelled with a full credit note. |
| One shipment | Contents and serial numbers, captured by scan or paste with a unique check per serial, a count check against the serialised units and a format check where the product defines one. A status timeline with the evidence each move needs: in transit needs the courier and a tracking reference, delivered needs a proof reference or the receiver's one-time code, signed for records the collector's name and the type and last four digits of their ID. A wrong jump is never offered. What the courier charged us is recorded for reporting only. |
| Support desk | The caller is verified first, by an in-app code read out or a callback, both recorded; serial and order details stay hidden until then. Serial lookup shows the product, order, delivery, dispatch and delivery dates, warranty until, and the windows snapshotted on the case. A timeline and remote-fix notes. Make the issue code (valid 48 hours, at most 3 per order line). Close fixed remotely. Close refused. A serial with no dispatch record needs a serial exception that both Operations and Admin approve, never the person who opened it. |
| A returned device arrives | Four ordered steps: scan the serial against the issue code, photograph the unit, label and packaging, test and record findings, hand over to decide. The investigator never decides, so it goes to Admin. A serial that does not match rejects the return and flags the account. A device arriving after a lapse is Held. No fault found goes back at the business's cost. The business sees "We are checking it" while this is open. |
| Returns, Support cases, Shipments, Back-orders, Orders | Spine only: the same list-and-preview pattern as the Receipts queue, with columns, filters, target and owner written per queue. |
| Delivery price requests | A queue and a panel: the address, the receiver, the goods' weight and boxes from the product records, and the nearest area we serve for comparison. The courier quote (staff only), the price to the business with VAT included, and "good until" the end of the next business day (placeholder). "Send the price", or "We cannot deliver there yet…" with a reason the business reads. After the business pays, the price can never go up; extra courier cost is ours and is reported. Target 1 business day. |
| Stores and zones | The Main Store with address, hours and open shipments, and Add a store. The delivery zones with covered areas, Zone Rate, estimate and status; a zone with no rate or estimate is Coming soon and delivery there is blocked. A rate change affects only later orders. Test an address resolves to exactly one zone; overlapping or unplaced areas are refused. Closing a store with open shipments needs them reassigned first. |
| Products and stock | One list with a needs-attention notice and filter chips (All, Needs attention, At or below threshold, Out of stock, Back-order on, No documentation, Drafts, Discontinued). Search by SKU, name, brand or model. A row shows stock now (available and on hand), back-order terms, documentation and the Active Supplier. |
| One product | Five sections (Identity, Measures, Returns and compliance, Lifecycle and cost, Stock and content) and a "Before it can go live" checklist. "Make it active" stays off until it is clear. Each of the four returnability answers is Yes or No with no default. A serialised answer, a Warranty Period or None, a compliance reference where the range needs one. Supplier costs with the date each was set. Platform Markup and Active Supplier are shown and locked as Admin's. Documentation is not needed to go live. |
| Stock and goods in | On hand, reserved, available, threshold and status above a ledger with a reason, a reference and on-hand-after on every change. Adjustments above the set size wait for Admin. On hand cannot go below reserved without an Admin override. "Release safety stock to back-order" is a ledger line with a reason. Record goods in with supplier, date, quantity, batch, landed cost (invoice, freight, duty, clearing, with the rate used) and serial numbers. A daily check that on hand equals the ledger. |
| Bulk upload | A template, a file, a dry run (new, updated, price changes, failed rows with row number and reason), then apply. Valid rows are applied and failed rows reported. Admin-only columns are ignored with a note per row. A file that changes prices on more than 20 products, or moves a cost by more than 15%, waits for Admin as a whole file, with a one-click rollback after. |
| Documentation | Coverage against the success measure (products ordered in the last 90 days that have documentation; goal 80% by month 6 and 100% by month 12) and a list ordered by how often each was ordered. Three slots per product (written guide, manual PDF, video) and Copy from another product. Replacing marks saved copies Replaced and removing marks them Withdrawn, both at each phone's next sync and never at once; removing needs a reason. Suppliers do not sign in, so Operations adds what they send. |
| Guide editor | Steps on the left with reorder arrows, one step in the middle (title, short action sentences, an optional photo with a required "Describe the photo" field, an optional warning box), a live phone preview of exactly what a technician sees, a version note shown to people who saved the old version, Save draft and Publish. Every save is a new version. The count of phones that hold the old version is shown. |

#### Admin

Mockups: [people, access and returns decisions](mockups/console/flow-uj6-step15-console-admin-people.html) (UJ-2, UJ-7), [approvals, rules, staff, audit, dashboard](mockups/console/flow-uj6-step16-console-admin-settings.html), [suppliers](mockups/console/flow-uj6-step18-console-content-suppliers-zones.html), [holds, Terms, first businesses](mockups/console/flow-uj6-step26b-console-admin-ops-gaps.html).

| Screen | Essentials |
|---|---|
| Today | An escalation notice for anything past 75% of its target in any queue, naming the owner and the deputy. Cards for Level 3 requests and NIN reviews (1 business day), Return decisions, Waiting for a second person, Holds and blocklist (a Finance hold needs ratifying within 1 business day), and the founder's weekly review. |
| Level 3 review | Who is asking (trade name, owner, NIN verified, Terms version and time, blocklist and duplicate checks), who vouches (the business, the person, established, vouching rights, 3 of 6 in 90 days, earlier vouchees, open claims, the code used), and the three PRD checks (online presence, proof of an installed job, a phone call), each ticked with who, when and a note. A required reason on every decision, approvals included. The approval record names the voucher. If the voucher is suspended before the decision, Approve needs an explicit tick. |
| NIN review by hand | What he sent and what the automatic check said. The NIN stays hidden until a reason code is picked (checking by hand, checking for a duplicate, a dispute, other with a note), and every show is a line in the record. Three outcomes: Verified by hand, Not verified with a reason (up to 3 tries), Duplicate. "Ask him for a photo of his NIN slip or ID card" adds an optional step to his Get approved screen. |
| One business account | Search by business, owner, phone, email, order or serial. Facts and flags, and tools that each need a reason: Put on hold, Revoke approval (back to Get approved with prices hidden; orders already made are still processed), Disable a person, Add to the blocklist, Pause or give vouching rights, Add a note. The Hold dialog says what it does and offers Tell the Owner, or a fraud review that is flagged for the weekly review. |
| Return decisions | Findings from Support, the window check, and a decision list that follows the PRD's remedy order for the reason, with the first possible remedy chosen and a reason needed to depart from it. Courier reimbursement is one claim to Finance as a credit note request. The investigator never decides. |
| Approvals | One page for every change that needs a second person: a Platform Rate change above 3%, a serial exception, a whole-product counterfeit hold, a bank-details change, an Active Supplier switch, a bulk price change, a tolerance change, a VAT rate change, and amounts above the set second-approver amount. Each says what it will do, why two people, and who already agreed, with a preview. Approve is off for the person who asked. A decline needs a reason that goes back to them. New bank details start after 24 hours, and Admin and Finance are emailed. |
| Pricing and rules | Grouped rules (markups and the floor, VAT, return windows, tolerances, approval amounts including the stock-adjustment sign-off size, vouching, order timing, rate warnings) with a markup-by-range table. Changing one shows what it will do before it is saved: prices that move now, products with their own markup left alone, a sample price, existing orders unchanged, and products that would fall below the 8% floor. A required reason, a history, and a save that breaches the floor is refused with the products named. |
| Staff | Everyone who can sign in, with role, duties, a named deputy and status. Support duties are a switch on an Operations account. Two-step sign-in for all and 12-hour sessions. Deactivating signs the person out at once and moves their queue items to the deputy. The last active Admin cannot be deactivated. The Business Hours calendar of closed days pauses target clocks. |
| Audit trail | Append-only, with a chain check. Entries hold identifiers and what changed, never a name or a NIN. A line for every NIN shown, with its reason. Acting-for is marked. Looking up an identifier is Admin only. |
| Weekly review | Every Admin and Finance override and every use of "acting as" for the week: a late counterfeit report accepted by Admin, a stock adjustment above the set size, a payment reversal, a hold ratified, a second-person serial correction, a credit note with a second approver, any rate override above 10%. Each is marked reviewed with a note. "Sign off the week" stays off until all are reviewed. |
| Dashboard | One tile per success measure (SM-1 to SM-9 and SM-C1 to SM-C5), grouped as aiming for, how it is going, and must not trade away. Each has a value with counts beside every percentage, a target, a neutral status chip (Early days, On track, Behind, Below target) and a "Definition and source" disclosure. A tile that cannot be worked out says "Not enough data yet". |
| Holds and blocklist | Accounts on hold with who started each, a staff-only reason, and whether the business was told; a Finance hold waits to be ratified or lifted. The blocklist of NIN (kept only as a hash), contact and sending bank account, with who added it and how many were refused. "Check an item" answers only "On the blocklist since …" or "Not on the blocklist". A blocked person sees a neutral message and never why. A blocklisted payment is held in Unmatched credits, marked blocked. |
| Terms and policies | Publish a new version: the documents, a required plain what-changed list (each line short), counsel's approval date and reference (Publish stays off until entered), and a preview of the exact Owner screen. Acceptance progress after publishing, with a reminder. A version can be withdrawn only before the first acceptance. |
| Create a business | For the first businesses. Owner name as on the NIN, phone or email (verified first), optional business name, how the link is sent, a staff-only note. The creator is recorded and cannot confirm payments or issue credit notes for it. A Pending activation list with four-step progress. Make a platform vouch code for a named prospect (works once, 48 hours, locked after five wrong tries). |
| Suppliers | Who the goods come from and whether approved, product counts, and the three agreement terms (returns, dead on arrival, counterfeit chargeback) stated or missing. "Approve the supplier" stays off until all three are written down, with an "Approve with a reason" route. A shorter supplier dead-on-arrival window shortens the window given to businesses. "Switch the Active Supplier" shows a preview (cost, base-tier price, carts and projects touched) and needs a second approver. Stopping a supplier takes its products off sale after a preview, with replacements named per product. |
| Business accounts, Escalated | Spine only: the list-and-preview pattern, and the detail already drawn. |

### Coverage of the PRD

All 80 functional requirements are traced to a screen, a pattern, or a named outside input: [the 80-line trace and the maps](mockups/system/ia-coverage-step24.html) (the trace predates the sheets for the Cart, cancel and change, the Platform Rate, credit notes, holds, Terms, first businesses and delivery prices). Four things stay **spine only**, by decision: the queue and lookup lists, the Finance exports page, the text pages and small sheets, and the twelve launch messages as a table. The **tax invoice and credit-note document layouts** (FR-77) wait for the accountant's fields. The **nine wording items** on the register under Voice and Tone wait for counsel.

## Voice and Tone

Microcopy. Brand voice and the look live in `DESIGN.md`. The reader is a working integrator in Nigeria, often on a phone, often in a hurry, sometimes on a ladder. The voice is plain, steady and respectful. It covers the app, the staff console, WhatsApp and email, and the PDFs. Reference: [the full voice and tone sheet](mockups/system/voice-tone-step19.html).

No exclamation marks and no emoji anywhere. Sentence case everywhere, with capitals only on the small brass label above a heading.

**Six rules.**

| Rule | Say | Do not say |
|---|---|---|
| Plain words, short sentences | We could not confirm your payment. | Payment verification unsuccessful. |
| Say what happened, why, and what to do next | This code has expired. Codes work for 48 hours. Ask the person who gave it to you for a new one. | Invalid code. |
| A button says what it does | Send receipt. Ask for a refund. Put on hold. | Submit. OK. Confirm. Proceed. |
| Stay calm about clocks and money | Price held for 3 h 12 min. Send your receipt before 8 Oct, 10:42 am. | Hurry! Offer ending soon! |
| Never blame, never joke | That code is not right. Check the 6 digits and try again. | Oops! You typed the wrong code. |
| One name for one thing | Credit note, every time, everywhere. | Credit, balance, wallet, store credit. |

**Tone and shape for each moment.**

| Moment | Tone and shape | Example |
|---|---|---|
| Status and information | Calm and factual. | "Delivery 1 is on its way. Courier partner, tracking CP-88213." |
| A button | A verb and, when it helps, the object. One filled button per screen. A button that opens a dialog ends in an ellipsis. A button that cannot be pressed stays visible, dashed, with the reason beside it. | Send receipt · Ask for a refund · Put on hold… |
| A success | Brief: one line with the result and one with what happens next. No celebration. | "Receipt sent. We check it against our bank statement, usually within 2 business hours." · "Vouch code made for Hammed Bello. It works once, only for him, until Fri 9 Oct, 4:10 pm." |
| Waiting on us, and loading | Honest about working hours. For loading: nothing for the first second, then words, and a bar with a percentage for anything long. Never a spinner alone. | "We work 8 am to 6 pm, Monday to Saturday. We will tell you on WhatsApp and email as soon as it is confirmed." · "Sending, 62%. Waiting for signal." |
| Needs the person | Direct. One action. | "Pay for order INT-0044. Price held for 3 h 12 min." |
| Something failed, an error | Plain and kind, never alarming. Four short parts in order: what happened, why if we know, what to do, where to get help. No code, no technical word. | "We could not reach the NIN service. We will keep trying for up to 24 hours and tell you the result. You can close the app." · "We could not confirm your payment. We could not match your transfer of ₦800,000.00 to our bank statement. Check the amount and the reference on your receipt. Need help? Call or send us a WhatsApp message." |
| A rule says no | Firm. Gives the reason. Offers a way on. Names who can; never "Access denied". A row or button a role can never use is not shown. | "Back-order items cannot be returned for a change of mind. Call or WhatsApp us to talk it through." · "Only the Owner can ask for a refund." |
| Money moves, or it cannot be undone | Serious and complete. In a confirmation dialog the title is the question and names the thing, the body lists what will happen, the buttons are the verb and Cancel, and red is only for what cannot be undone. | "Close Adeyemi Integrated Systems? This cannot be undone." · "Put Sade AV Systems on hold? They cannot make new orders. They cannot be paid a refund. Payments for orders already made are still processed." |
| An empty place | Why it is empty and the one next step. | "No projects yet. Make your first one: a name is all we ask." |
| No signal | What still works, how fresh it is, and that changes will send later. | "No signal. You are working from what is saved on this phone, checked today 7:10 am. Changes sync when you are back online." |
| WhatsApp or email | Names the order, proposal or case first, then what happened, then what to do. One link. Under 300 characters on WhatsApp. Signed "integrator". A Technician's message never carries an amount. The PRD's own literals are never reworded. | "Order INT-0042: payment of ₦731,729.94 confirmed. We are checking stock and will approve it today. integrator" |
| The staff console | Shortest. Imperative. The same nouns as the app. | "Confirm payment." "Reason (staff only, required, kept on record)." |
| Legal and regulated text | Verbatim, approved by counsel before launch. | The Terms key points, refund terms, privacy lines. |

**Formats**, the same on every screen, email and PDF.

| Thing | Format | Example |
|---|---|---|
| Money, to a business | Naira sign, commas, always two decimals, "VAT included" where it is a price. Never a price in dollars. | ₦731,729.94 |
| Money, in the console | Naira for orders and payments. Dollars only for supplier costs, the rate and the dashboard, always labelled. | USD 37.21 · ₦1,368.00 to the dollar |
| A reduction | A real minus sign before the naira sign | −₦38,250.00 |
| Percent | No space | 2% |
| A date | Day name, day, month. Add the year when it is not this year, or in a document. | Wed 7 Oct · 7 Oct 2026 |
| A range | "to", never a dash | Mon 5 Oct to Sun 11 Oct |
| A time | 12-hour, with am or pm, in West Africa Time. The zone is named only on settings and exports. | 11:24 am |
| Time left | Hours and minutes with short units. Business time says so. | 3 h 12 min · 47 h left · 2 business hours · 1 business day |
| A phone number | Local form, grouped 4-3-4, hidden part as dots | 0803 555 0142 · 0803 ••• 0142 |
| A reference | Letters, a dash, digits, in monospace | INT-0042 order · CN-0012 credit note · C-0231 case · RR-0087 return request · IC-4829-QK7M issue code |
| A code a person reads out or types | Groups of four, capitals, no look-alike letters | 7KQ4-M2XD-9PTA |
| A serial number | As the maker prints it | NG4P-77124 |
| Staff-only references | Never shown to a business | BA-0243 business · P-1187 profile · L-20644 audit entry |
| A person | First name inside a team, name and initial in the console, full name on documents | Chidi · Chidi O. · Chidi Okafor |
| A measure | A number, a space, the unit | 305 m · 2 TB · 4MP · 2.1 MB |
| Spelling | British | colour, licence, organise, cheque |

**Word list.** The PRD's terms are exact, and a business reads a friendlier word for some. The left column is what a business reads.

| A business reads | PRD term | We never say |
|---|---|---|
| business | Business Account | account holder, company |
| approved, not yet approved | Access Level 1, 2, 3 | Level 1, Level 2, Level 3 |
| trade name | Trade Name | brand name |
| vouch code | Vouch Code | referral code, invite code |
| established integrator | Established Integrator | premium, verified seller, partner |
| project, room | Project, Room | job, site |
| proposal | Proposal | quote, quotation, estimate |
| order, receipt | Order, Receipt | purchase, booking; proof of payment |
| price hold, price held | Price Lock | lock, locked price |
| credit note | Credit Note | wallet, credit, balance, store credit |
| refund | refund | cash back, reversal, and never "no refunds" wording |
| tier (Tier 2) | Loyalty Tier | level, status, membership, rank |
| delivery, pick-up, store | Delivery, Pick-up, Store | collection, shipping; branch, warehouse |
| back-order | Back-order Item | pre-order, made to order |
| issue code | Issue Code | RMA number, return code (the manufacturer's return authorisation, RMA, stays in the console) |
| return request, problem, case | Return Request, Case | claim, complaint, ticket |
| our team, our finance team, Support | Admin, Finance, Operations | the admin, the back office. A business never sees our role names. |
| Owner, Manager, Technician | the same | admin, staff, user. Capitalised, because they are role names. |
| National Identification Number (NIN) | NIN | ID number. Spelled out once on a screen, then NIN. |
| VAT included | VAT | tax, inclusive. Always beside a price. |
| exchange rate, the rate | Platform Rate | FX, our rate. Shown as "₦1,368 to the dollar" only where a change in it is explained. |

**Wording that counsel or the accountant must approve before launch** (the screens carry placeholders): the Terms key points (reporting windows 5, 30 and 7 days, the 15% restocking fee, back-order and exclusions, credit notes lasting 12 months, warranty limits); credit note expiry and refund terms including refunding what is left after expiry; the privacy lines about the NIN; the VAT wording on proposals, tax invoices and credit-note documents; the Proposal validity sentence about the exchange rate; recall and counterfeit notices; hold, revoke and blocklist messages to a business; the "we keep what the law says we must" line when an account is closed; and the "bank details changed on [date]" banner.

## Component Patterns

Behavioural. Visual specs live in `DESIGN.md.Components` under the same names. Reference: [sixteen of these as drawn](mockups/system/patterns-step20.html); the Meter, Card and Check list are drawn on [the Finance console sheet](mockups/console/flow-uj6-step13-console-finance.html), the Amount on [the money rules](mockups/system/product-rules-step23.html), and the Guide step on [the Technician sheet](mockups/app/flow-uj5-step11-technician.html).

| Component | Use | Behavioural rules |
|---|---|---|
| Button | Every action | One filled button per screen, the main thing to do. At least {spacing.target-min} high, {spacing.target-phone-button} in a phone form. Icon-only buttons are only the cart and Back, and both have a name. A button that cannot be pressed stays visible and dashed with its reason beside it. While it works it says "Sending…" and cannot be pressed twice. |
| Status label | Order, delivery, payment, request states | In progress is a plain outlined label. Colour is kept for what needs a person (brass: {colors.caution} on {colors.caution-tint}) or has failed (red: {colors.error} on {colors.error-tint}). Words always carry the meaning. One label, one meaning, the same word on every screen. |
| Banner | Information, a thing needing a person, a failure | Information is announced politely, a failure at once. One banner at a time per screen, except account-wide ones (a hold, a stale rate, "prices are being updated"), which stack at the very top. A banner that can be fixed carries its action. It never covers the thing it talks about. |
| Form field | Every input | The label sits above and is always visible; a placeholder is only an example. "Optional" and "needed" are in words. The error sits under the field, with words and an outline. The right keyboard for the field; paste and autofill always work; what was typed is kept after any failure. A NIN is masked after typing, with a Show control. A money field cleans pasted text and reads the amount back (see Money and numbers). |
| Code entry | Sign-in, the fresh code before a sensitive change, the WhatsApp number check | Six boxes to look at, one real input underneath so paste and auto-fill fill all six. The resend countdown is in words and becomes a button when it ends. The same screen every time. |
| Choice | Radio card, checkbox row, switch | The whole card or row is the target. A switch says "On" or "Off" in words. Nothing is preselected when the answer is a legal or money commitment. |
| Quantity stepper | Products in a room or cart | Minus and plus are each at least {spacing.target-min} wide; tapping the number lets the person type. Whole numbers of at least 1, or fractions where the unit allows it, with the unit after the number. Zero or below is refused with a sentence. Works offline. |
| Row and table | Lists on a phone; queues and tables in the console | A phone row is at least {spacing.row-phone-min} high with the whole row a target and amounts at the right. Console tables have a fixed header, {typography.table-console} text, sortable columns that say so, and the selected row outlined. J, K, Enter and / move and open. A late row is tinted and says why in words. |
| Sheet and dialog | A choice or a confirmation | A phone uses a bottom sheet, the console a centred dialog. Both dim the page, take the focus, close with Esc or Cancel, and give the focus back. The title is a question or the task; the body lists what will happen; red only for what cannot be undone. A sheet never holds a long form: that is a full screen. |
| Tabs and navigation | Five tabs, a segmented control, the console rail | Five tabs with words, two before approval. A role sees only the tabs it can use. The current place has a bar and is marked for screen readers. The console rail is grouped with counts that turn brass when something is late. Back goes back one screen and keeps what was typed. |
| Timeline and steps | Anything with stages | A vertical timeline with done and now clearly marked and the date of each. A strip of pips for "Step 3 of 6" with the words beside it. A checklist says "Clear" or "Needs action" in words. |
| Clock | Price hold, issue code, targets | Shows the exact end time beside the time left. Updates each minute, not each second. A screen reader hears it only at the start and when 1 hour, 15 minutes and the end are reached. A clock ends in a result and never sits at 0 h 0 min. |
| Meter | A target or an upload | Always has its figure in words next to it. Does not animate. |
| Amount | Every price, total and balance | One look everywhere: naira sign, commas, two decimals, tabular figures, never shortened or rounded for the screen. See Money and numbers. |
| Card | A queue, a tile, a delivery, a panel | Informational unless it needs action, when it is tinted and carries its one button. |
| Check list | Console reviews and the account-closing steps | Each line is a sentence with its detail underneath and a verdict in words at the right: Clear or Needs action. The check is never colour alone. |
| Copy and share | Bank details, references, codes | Anything a person must type elsewhere has a Copy button beside it that says "Copied" for two seconds. Codes and references use the monospace face. Share uses the phone's share sheet, with Copy as the fallback. |
| Upload | Receipts, return photos, logo, guide photos | "Take a photo" and "Choose a file" are both offered. The camera permission is asked for only when "Take a photo" is pressed; if refused, "Choose a file" is offered and the screen says so. Photos are resized on the phone (about 2 MB, location stripped). An upload that loses signal resumes by itself and says so. A bad file is refused in a sentence before it is sent. |
| Preview panel | Before a change that touches many things | A rate, a markup, a hold, a supplier switch. The screen says what it will do, next to the Save button, never behind a link. |
| Two-person approval | Any change needing a second person | It goes to Approvals and says who asked, who already agreed and why two. The Approve button is off for the person who asked and says why. A decline needs a reason and goes back to the person who asked. |
| Guide step | A written installation guide | One step to a screen in large type with a text-size control, a step strip, big Back and Next buttons, one photo with its description, and an optional warning box. |

## State Patterns

Every screen is checked against this list. A state that does not apply is skipped on purpose, not forgotten. In this file **brass** means the needs-a-person pair ({colors.caution} on {colors.caution-tint}, with a {colors.brass} rule), **red** means the error pair ({colors.error} on {colors.error-tint}), and **neutral** is a plain outlined label with no colour.

| State | Treatment |
|---|---|
| Loading | Nothing for the first second. Then words. A bar with a figure for anything long. |
| Empty | Says why it is empty and offers one next step. |
| Error you can fix | Inline, beside the thing to fix. Nothing typed is lost. |
| Error that needs us | Never a dead end: "Call or WhatsApp us" and a way on. |
| No signal | Says what still works, how fresh it is, and that changes will send later. A saved copy never shows a ticking clock. |
| Stale, with an as-of time | A price kept on the phone says "as of" and the time, and turns brass after a day: "Prices may have changed". Never used to make a Proposal or an Order. A Technician sees no prices, so nothing to date. |
| Waiting on us | Honest about working hours. The price hold keeps counting. |
| Needs you | Tinted, listed first, with the one button that does it. Also on Home. |
| Blocked by a rule | Visible, dashed, with the reason written beside it. |
| Not for your role | "This is for the Owner and Managers. Ask Tunde." A row or button a role can never use is not shown; if a link leads there, this is the message. |
| Time ran out | Says what lapsed, what is unchanged, and what to do. |
| On hold, or prices being updated | An account-wide banner at the very top of every screen until it is lifted. |
| Under review | Neutral. Says when the answer comes and that the person need do nothing. |
| Done | The result, then what happens next. No celebration. |
| Locked by someone else | A soft lock in the console, "Being reviewed by Tolu K. since 12:44 pm", that can be taken over with a reason. |
| The service is down | "We are making a change and are back by 6:00 pm. Your saved projects still work." Proposed: the PRD has no maintenance state. |

The states each surface needs, beyond the generic ones above:

| Surface | States it must cover |
|---|---|
| Sign-up and code | Wrong code, too many tries, already registered, sign-up refused, code expired, resend countdown |
| Get approved and Confirm who you are | Waiting, not approved with a reason, five identity outcomes (confirmed, sent to our team, service busy, rejected with a reason, three tries used), four vouch-code errors (wrong, expired, used, locked), a business set up by us |
| Home | Empty (no attention items), a Technician's version, no signal, a recall notice until acknowledged |
| Catalogue and product | No results, stale prices with an as-of time, out of stock with and without back-order, discontinued, quarantined or suspected, documentation coming soon, replaced, withdrawn, video not saved, no signal |
| Project | Empty room, a room with an unavailable item (flagged, never removed), offline, conflicts needing a look, proposal valid, out of date, expired |
| Proposal | Needs a connection, margin below zero refused, margin above 100% asks, replace confirmation, expired |
| Cart | Empty, sold out, now back-order, price changed, needs a connection |
| Create order | Lines that cannot be ordered, back-order not yet agreed, area not served yet, price moved while reviewing, prices being updated, two unpaid orders, on hold, new Terms not accepted, no signal |
| Payment and receipt | Awaiting payment (clock states), part paid, price no longer held, receipt sent, unconfirmed, upload interrupted, no signal, order covered by credit |
| Order | Expired, cancelled, ready for pickup, not collected, delivery failed, delivery lost, delivered, change requested, cancellation requested, agreed, not possible |
| Returns | Past the window, closed by Support, issue code not valid or lapsed, already raised, waiting for approval, declined, wrong order line, device arrived after it ran out (Held), serial does not match, tested fine, credit note outcome, waiting for the maker, rejected, suspected counterfeit with a recall notice, change of mind with exclusions, non-delivery |
| Credit notes | Expiring, credit only, expired (refund on request), refund requested (balance held), refunded, request too large, credit returned, order covered by credit |
| Account | Invitation waiting, expired, accepted, contact already used; role changed; removed; paused vouching; passkey; recovery waiting; closing blocked with what stands in the way |
| Saved on this phone | Online, no signal, locked after 14 days with a warning, phone cleared space, conflicts, logging out with changes waiting |
| Console queue | Empty, over target, over 75% escalated, outside Business Hours, sent back before, soft lock, out of office, statement old, rate stale |
| Console review | Segregation of duties blocking, a second person needed, your own request, a decline reason, after-confirm message with the ledger line |
| Platform Rate | Brass from 36 hours old, red from 44, stopped at 48; a warning when that falls before the team opens; a second person above 3%; refused above 10%; new orders paused |
| Bank statements | Statement old; a row already imported and skipped |
| Support desk | Caller not yet verified (details hidden); serial not found; a serial exception waiting for both approvers; the third issue code on a line |
| Bulk upload | Dry run; failed rows with the row number and the reason; waiting for Admin as a whole file; rolled back |
| Guide editor | Draft; published; a photo without a description cannot publish; phones still holding the old version |
| Approvals | Your own request (Approve off); already decided by someone else; declined with a reason |
| Dashboard | "Not enough data yet" |

## Interaction Primitives

Reference: [interaction rules as drawn](mockups/system/patterns-step20.html).

| Rule | What it says |
|---|---|
| Targets | At least {spacing.target-min} for anything pressed on a phone, with space between. 24px is the WCAG 2.2 minimum and never the goal. Rows with a checkbox are {spacing.checkbox-row}. Console controls are {spacing.target-console} ({spacing.target-console-small} small), for a pointer. |
| Focus | A 3px {colors.focus-ring} ring with a 2px gap on everything reachable, never removed; {colors.focus-ring-on-green} on a green bar. The order follows the reading order. A skip link jumps past the menu. Sheets and dialogs hold the focus and give it back. |
| Keyboard | Everything works without a mouse. Esc closes a sheet or dialog. Enter acts on the focused main button. In the console J, K, Enter and / move around lists. Money actions are never a single key. |
| Gestures | Nothing needs a swipe, a long press, a pinch or a drag. Reordering uses arrows. Pull to refresh also has a Refresh button. Actions fire on release. No shake or tilt. |
| When to ask "Are you sure?" | Before something that cannot be undone (closing an account, a hold, revoking approval), before money moves (confirm payment, a refund, a reversal, creating an order), and before something is sent to someone else (an invitation, a shared code). Not for edits that can be undone. |
| Undo | A deleted room can be brought back for 30 days. A saved change can be restored from an earlier version for 30 days. A confirmation is not a substitute for undo where undo is possible. |
| Keeping what was typed | After any failure the form keeps its values. A project saves itself and works offline. A receipt upload resumes. A long console form saves a draft. |
| Timeouts | Nothing times out without saying so. A business is signed out after 30 idle days. The console warns 5 minutes before its 12 hours end with "Stay signed in". A code shows the time it has left. |
| Proving it is you | No puzzle that tests memory or reading. Codes can be pasted. Passkeys are offered. Bot checks are invisible. A fresh code is asked for at the moment of a sensitive action (see Trust and verification). |
| Motion | Nothing moves unless it helps. No autoplay. Changes are at most 150ms. A bar does not animate. The system's reduce-motion setting is always obeyed. |
| Feedback | A pressed button changes at once. The result is said within one second, or a bar shows progress. Success and failure are always in words. |

## Accessibility Floor

Behavioural. Visual contrast lives in `DESIGN.md` with the computed ratios. Reference: [the accessibility floor with colour evidence](mockups/system/accessibility-step21.html).

**Standard.** WCAG 2.2 level AA on every screen of the app (phone and desktop browser), the staff console, and the PDFs, emails and WhatsApp messages the product produces. Where AAA is cheap it is taken: a 3px focus ring, plain language. The page language is English for Nigeria; there is no other language and no dark mode in version 1. Reading level is about Grade 8, checked at copy review.

| Area | What the product does |
|---|---|
| Text size and zoom | Body text ({typography.body}) and inputs ({typography.input}, so a phone never zooms in on its own), and nothing smaller than {typography.label}. Everything works at 200% with no loss. The phone app reflows down to 320px with no sideways scrolling. The system's larger-text setting is honoured. |
| Screen readers | The same landmarks (header, navigation, main) and one top heading on every screen, then headings in order. Lists, tables with captions and column headings, timelines as ordered lists. Icon-only buttons are named ("Cart, 2 items"). A progress bar has its figure in words. A switch is announced with its word. Code boxes are one labelled group. |
| Announcements | Information is announced politely, a failure at once. Going offline and back online is announced. A countdown is announced only at the start and when 1 hour, 15 minutes and the end are reached. |
| Images | A product photo is named for the product. A decorative image says nothing. A photo in a guide has a description written by the author; the guide editor will not publish a step with a photo and no description. |
| Forms | A visible label for every field. Errors are in words beside the field and listed at the top of a long form, with a suggestion. Anything legal or financial can be checked, corrected or undone before it is final. Nothing is asked twice in the same journey: the sender's details are remembered, the Proposal's client details fill the order, the voucher's details fill the request. |
| Signing in | No test of memory or reading. Codes can be pasted and auto-filled. Passkeys are offered. |
| Getting help | "Call or WhatsApp us" is always in the same place: the last block of a screen and under Account. |
| Movement and pointers | No drag, shake or tilt. An action happens on release, so it can be cancelled by moving away. A pointer-only gesture always has a plain button as well. |
| Focus is never hidden | A sticky bar (the tab bar, a Done bar, a console header) never covers what has focus. The page scrolls the focused control clear of it. |
| Time | Nothing times out unseen. The console warns before it ends a session. A code shows its time left. Offers that matter, like a price hold, say their exact end time. |
| Motion and flashing | Nothing flashes. Motion is minimal and the system's reduce-motion setting is obeyed. |
| Forced colours | Borders are real borders, not shadows, so a control is still visible when the system forces its own colours. |
| Documents we produce | Proposals, parts lists, tax invoices and credit-note documents are tagged PDFs: real text, a title, the language, headings, true tables and a described logo. WhatsApp and email are plain text first, with links that say where they go. |
| The console zoomed | Below {spacing.console-reflow-below}, which includes anyone zooming to 400%, the rail becomes a menu button, side-by-side panes stack, and a wide table scrolls inside its own box. |

**Proof.** An automated accessibility check runs on every screen and every state in this spine on every change, and a new failure stops the release. The acceptance criteria of every story say what a keyboard user, a screen-reader user and someone zoomed to 200% can do. Before each release a person goes through the main journeys with only a keyboard, then with TalkBack and VoiceOver for the app and NVDA for the console, then at 200% and 400% zoom, with reduced motion, and in forced colours. Before launch, real users test it, including people who use a screen reader and people with low vision, on a phone, on weak data. After launch a published accessibility statement carries the "Call or WhatsApp us" line, and every report is a case.

## Responsive & Platform

Reference: [the three sizes, four laptop-width frames, and the platform rules](mockups/system/responsive-platform-step22.html).

| Size | Layout |
|---|---|
| Phone, up to 599px | The confirmed phone screens: one column, the bottom tab bar, bottom sheets, a 16px margin, working down to 320px. |
| Tablet, 600 to 1023px | The phone layout centred in a column up to 720px, the tab bar kept. From 900px a list and a chosen item sit side by side. |
| Laptop and desktop, 1024px and up | The tab bar becomes a left rail and the cart moves to the top right. Content stops at {spacing.content-max}, centred. Lists with an item behind them become a list and a detail (Orders, Projects, Credit notes). The Project page keeps a summary panel in view (total with VAT included, Generate proposal, Create order, Parts list, Save for offline). Sheets become centred dialogs. A card list with more than four facts a row becomes a table. |

Every size works with touch, mouse and keyboard. Nothing needs a hover. Both orientations work and neither is locked. The reading order never changes with the layout. The console is built for {spacing.console-built-for} and a phone that opens it gets a notice that it is for a desktop.

| Platform rule | What it says |
|---|---|
| Browsers and devices | The last two versions of Chrome, Edge, Firefox, Safari and Samsung Internet. Android 9 or newer with Chrome. iOS 16.4 or newer for the installed app. Tested on the PRD's low-end Android on slow 3G. |
| Install | Offered after the second visit or right after the first "Save for offline", and always under Account, with iPhone steps in words. On an iPhone, Save for offline asks to install first, because a browser may clear saved files after about a week unused while an installed app is not; the app also asks the phone to keep its storage. |
| Updates | A new version is fetched quietly and never reloads under someone. An "Update when you finish" banner keeps what was typed. |
| Notifications | None from the browser in version 1. WhatsApp and email only. |
| Performance | The Catalogue list within about 5 seconds on the test phone. System fonts only. Pictures near the screen load small first. Lists load 20 at a time. Data Saver holds pictures until tapped and never auto-loads video. |
| Clocks and zone | Every countdown uses our clock and not the phone's, and times show in West Africa Time. |
| Sharing and documents | The phone's share sheet, with Copy as the fallback. Documents are PDFs that print cleanly. Parts lists carry no prices. |

## Money and numbers

Every price on this platform is a promise in naira. These rules keep the number on the screen the same as the number we store and the number on the document. Reference: [the product-specific rules](mockups/system/product-rules-step23.html), which also carry the worked examples.

| Rule | What the screen does |
|---|---|
| One look for every amount | The naira sign, commas and always two decimals (₦68,400.00). Figures line up in a column. Nothing is shortened or rounded for the screen. A reduction has a true minus sign (−₦9,500.00). |
| The phone adds, we price | The phone may multiply a stored unit price by a quantity and add up lines, counting in whole kobo and never in floating point. It never works out a rate, tier, discount, markup or VAT. Those always come from us, with the time they were worked out. |
| Everything adds up | Unit price × quantity is the line, lines make the room, rooms make the total, to the kobo, on screen, in the PDF and in the message. On a Proposal with a VAT line, any kobo of difference falls on the VAT line. |
| VAT said every time | "VAT included" beside the first price on a screen and under every total. The rate is read from settings and never typed into a screen. A VAT-registered business can choose a VAT line on a Proposal; our own prices always include VAT. |
| Naira only | No dollar in anything a business can see. The one place a rate appears is where a price change is being explained ("₦1,330 then, ₦1,368 now"). The console shows dollars only for supplier costs, the rate and the dashboard, always labelled USD. |
| Three numbers where money is owed | Amount due, Confirmed so far, Still to pay, in that order. What the person typed is "Receipt sent" and does not reduce Still to pay until Finance confirms it. The word "paid" is only for a confirmed figure. |
| Credit is not cash | A credit note shows its amount, balance, reason and last day. It is never called a wallet or a balance. Using one is a request, and it counts as paid from the moment it is made. A refund goes only to an account that already paid us, picked from a list and never typed, and only an Owner can ask. |
| Typing an amount | A money field takes digits, commas and one decimal point. A pasted ₦, "NGN" or space is dropped, only two decimals are kept, a minus is refused. The number pad with a decimal point opens on a phone. The cleaned amount is read back under the field before it can be sent. |
| Typing a quantity or a margin | A quantity is a whole number of at least 1, or a fraction only where the unit allows it, with the unit after it. A margin takes up to two decimals, refuses anything below zero, and asks "Is that right?" above 100%. |
| A number nobody may see leaves no hole | For a role that may not see money the screen is drawn without the money area: no dash, no blank, no padlock. Sorting and filtering by price do not exist for that role either. |
| Old prices say they are old | A price kept on the phone says "as of" and the time, and turns brass after a day. Old prices are never used to make a Proposal or an Order. After a sync, a Project whose total moved says so once: "Prices were updated. The total went from ₦702,168.00 to ₦706,512.00." |
| A money action names its amounts | Before the last button of an action that moves money (create an order, use a credit note, ask for a refund) every amount is written out in full. Pressing twice does what pressing once does. |

## Time and clocks

There are two kinds of clock and they behave differently. A clock that protects a business's money runs through the night and on Sunday. A promise we make to a business pauses when we are closed. Business Hours are Monday to Saturday, 8 am to 6 pm, minus the closed days on Admin's calendar.

| Kind | What belongs to it |
|---|---|
| Calendar clocks, which never pause | The price hold (24 hours; an unpaid order ends at 48); a Proposal (to the end of the day, one month on); a one-time code (10 minutes); a vouch code and an issue code (48 hours); an invitation (7 days); Owner recovery (a 72-hour wait); a credit note (12 months, reminders at 60 and 14 days); return windows (5, 30 and 7 days from delivery); the ship-in-full hold and an uncollected pick-up (14 days); the back-order cancel right (7 days after the promised date); saved projects without a check-in (14 days). |
| Business-time promises, which pause when we are closed | A receipt is checked (2 business hours); a paid order is approved (4 business hours); an approval or identity decision (1 business day); a credit note request, a cancel request after approval and a delivery price request (1 business day); a returned device is investigated (2 business days after it arrives). |

| Rule | What the screen does |
|---|---|
| Two clocks, two kinds of sentence | A calendar clock says "until" and "left": "Price held until Thu 8 Oct, 4:12 pm, 19 h 42 min left." A business-time promise says "by" and gives the exact time: "We will check it by 9:00 am on Monday." One sentence never mixes the two. |
| Say the time, not the rule | The screen works out the exact time using Business Hours and the closed days, so nobody has to count business hours. "2 business hours" may appear as a second line, never alone. |
| Our clock, not the phone's | Every countdown and deadline is read from our clock, and times show in West Africa Time whatever the phone is set to. On a phone set to another zone, the first clock on a screen adds "Nigeria time". |
| A clock ends in a result | At the end the card changes to its "time ran out" state and says what happened to the money. A clock never sits at 0 h 0 min. If the phone was offline, the card changes at the next connection. |
| What counts is when it reached us | A receipt counts from the moment we receive it, not the time on the phone or on the bank slip. If a slow connection is holding an upload close to a deadline, the screen says so and says what counts. |
| A time the person types | The transfer time on a receipt is read back in words. It cannot be in the future or before the order was made. |
| Opening hours where a promise depends on them | The "Call or WhatsApp us" block says whether we are open now ("Open until 6 pm" or "Opens at 8 am on Monday"). |
| Messages go when the clock needs them | Messages that protect money or a deadline go at once, at any hour: payment unconfirmed, the price hold at hour 20, expiry at hour 40, an issue code at hour 36. The rest wait until 9 am: a credit note at 60 and 14 days, a back-order running late, a tier change. WhatsApp first, email after 15 minutes. |
| A code shows its time | A one-time code says how long it has left in words. A vouch code, an issue code and an invitation show their last day or hour wherever they are listed. |
| The rate and the weekend | The rate stops new orders at 48 calendar hours. The Platform Rate page shows the exact stop time. Because nobody works on Sunday, a rate reconfirmed on Saturday morning stops orders on Monday morning, so a Saturday and Monday warning tells Finance to reconfirm early. |

## Who sees what

The server enforces every rule, so the screens are a convenience and never the lock. The screens choose one of three treatments, and a role is never told "Access denied".

| Treatment | When | What it looks like |
|---|---|---|
| Not shown | The thing is none of this person's business | The layout closes up as if it was never there: no dash, no blank, no padlock. |
| Shown, and who can | The person can see it exists but cannot do it | It names the person or the role who can, and offers a request when there is one: "Orders are for Owners and Managers. Ask Tunde." |
| Shown, but not now | The person may do it, but not yet or not here | The button stays visible and dashed with the reason beside it: "Add at least one product first." |

| In the app | Owner, before approval | Owner | Manager | Technician |
|---|---|---|---|---|
| Browse the Catalogue, read manuals and guides | Yes | Yes | Yes | Yes |
| Prices, totals, tier and discounts | One line instead: "Prices are shown once your business is approved." | Yes | Yes | Not shown |
| Sort or filter by price | Not shown | Yes | Yes | Not shown |
| Projects, rooms and the parts list | Not shown | Yes | Yes | Yes, with no prices |
| Proposals, cart, orders and paying | Not shown | Yes | Yes | Not shown; an order link says "Ask the Owner" |
| Order and delivery status | Not shown | Yes | Yes | Status only, no amounts |
| Credit notes | Not shown | Yes | See, and ask to use one | Not shown |
| The bank details to pay into | Not shown | Yes | Yes | Not shown |
| Ask for a refund | Not shown | Yes, with a fresh code | Only the Owner can | Not shown |
| Make a vouch code | Not shown | Yes, once established, with a fresh code | Ask the Owner for one | Not shown |
| Report a problem; raise a return request | Not shown | Yes | Yes | Yes; a Manager or the Owner approves it |
| Add and remove people | Not shown | Everyone; a fresh code to change a role | Technicians only | Not shown |
| Change the Owner, a contact, or close the business | Yes, with a fresh code | Yes, with a fresh code | Not shown | Not shown |
| Email and WhatsApp messages | Decisions only | With amounts | With amounts | Never an amount |
| Kept on the phone for offline use | No prices | Prices for products in the business's Projects and recently viewed, with "as of" | Prices for products in the business's Projects and recently viewed, with "as of" | No prices at all |

Before approval there is only the Owner, who sees two tabs. A business's cost, tier discount and margin appear only to the Owner and Managers working on the Project. The Proposal PDF, the parts list, a shared link and a share message carry none of them, in the visible text or in the file's hidden data. A published "Technician-safe" response schema lists the only fields a Technician response may contain, and an automated test fails when any money field, or any ordering derived from price, is reachable by a Technician or by anyone before approval.

**In the staff console.** The rail shows only what the role can use, and nothing is greyed out. Every free-text box says who will read it: "Reason (the business will read this)" or "Reason (staff only)" or "Internal note (staff only)", never the same box. Staff names, role names and the staff-only references BA-, P- and L- never appear in anything a business reads; it sees "our team", "Support" and "our finance team". A NIN stays masked to its last four digits, and showing the rest needs a reason code and is recorded. Support sees an order or serial number only after the caller is verified. Acting as another role is marked. A business learns nothing about other businesses: a person who vouched is never told whether the other was approved, and a fraud review is never named to the business.

## Trust and verification

Where a mistake or a bad actor costs real money, the screens slow down on purpose and say why. Three tools do it: a fresh code, a second person, and a record that is never rewritten.

**A fresh one-time code is asked for** before: making a vouch code, asking for a refund, asking to use a credit note, changing someone's role, changing the Owner, changing your own phone or email or adding a WhatsApp number, adding or removing a passkey, and inviting a Manager. It is asked at the moment of the action, never at sign-in. It goes to a contact on record, partly hidden. It is the same six boxes as sign-in. It is valid 10 minutes and for one action only. After the right code the action finishes in the same step.

**A second person is needed** for: a Platform Rate change above 3% (above 10% it is refused unless Admin overrides with a reason); a credit note or refund above the set amount (a placeholder of ₦100,000.00); reversing a payment; a receipt whose sender name does not match; switching the Active Supplier, a bulk price change, a tolerance change or a VAT rate change; new bank details (Admin edits, Finance approves, they start after 24 hours, and businesses see "Bank details changed on [date]" for 7 days); a serial exception (Operations and Admin both, never the person who opened it); a whole-product counterfeit hold; and a stock adjustment above the set size (Admin signs off). Approve is off for the person who asked, a decline needs a reason, and every approval is recorded with both names.

| Rule | What the screen does |
|---|---|
| A sent receipt stays | The business cannot delete or edit a receipt once sent. A mistake is fixed by sending a corrected one beside it. |
| Money records have no Edit and no Delete | A correction is a new line that points at the old one ("Reversed", "Credited"). |
| The audit trail is read-only | Each record in the console has a History panel showing who did what and when. Nobody can edit it, Admin included. |
| Documents keep their numbers | Tax invoices are numbered in sequence. A credit-note document names the invoice it adjusts. A replaced guide keeps its earlier versions. |
| A NIN is typed once and stays hidden | Masked with a Show control. Never in a link, a page title, a message, a notification or the browser's autofill. After the check only the last four digits show. The full number is deleted 30 days after a manual-review decision. |
| A client's or receiver's details say why | Wherever someone types a client's or a receiver's details, one line says why we keep them and who sees them. Counsel writes the wording. |
| WhatsApp is always asked | Per number, on its own screen. "No thanks" keeps updates in the app. Turning it off is one switch. |
| Two things at once | The first valid action wins. The other person sees the current state with who and when ("Already approved by Bola A. at 2:14 pm"), never an error. Pressing twice does what pressing once does. |
| Segregation of duties | Nobody confirms a payment, or issues or pays a credit note, for a business they created, approved or vouched for. When it blocks, the screen says so in words. |

## Offline and weak signal

The PRD gives offline a narrow job: design a Project on site and reach the guides. Anything that commits money needs a connection and says so. Reference: [the offline rules](mockups/system/product-rules-step23.html) and [Saved on this phone](mockups/app/flow-uj5-step11-technician.html).

| Works with no signal | Needs a connection, and says why |
|---|---|
| Browse and search the Catalogue as it was when the phone last synced. Make and change Projects, rooms, products and quantities. Show the parts list on screen. Open saved manuals, guides and PDFs (videos only if saved on request). See Saved on this phone. | **Make a Proposal**: "Prices must be current. Connect to make a Proposal." **Save or share a Parts list PDF**: it needs a connection in version 1 (confirmed by the architecture spine, AD-14). **Use the cart or create an order**: "An order is made at today's price." **Orders, payments, receipts and credit notes**: "We check these against the bank." They are not kept on the phone. **Sign in for the first time, any code, any change in Account.** |

**The 14-day check-in.** Saved projects lock after 14 days without a check-in, and any successful connection counts as one. Day 0, the phone checks in. Day 11, the line under "Check now" turns brass: "Check in by Wed 21 Oct." Day 13, a banner on every screen: "Connect by tomorrow to keep working offline." Day 14, saved projects lock and nothing is deleted: "Connect to open your saved projects." Back online, the phone checks in, sends the waiting changes, and everything opens.

| Rule | What the screen does |
|---|---|
| Changes live on the phone first | Every edit is saved on the phone at once and sent when there is signal. A line says "3 changes waiting". "Check now" is always in Saved on this phone, because some phones do not sync in the background. |
| The server's clock decides a conflict | When two people changed the same thing, the later one by our receipt time wins, never by the phone's clock. The other version waits in "Needs a look" with Keep latest and Use mine. Owners and Managers see who made each change. A Technician sees "Two people changed the same item". |
| Nothing is thrown away | A product that left the Catalogue stays in the Project, marked Unavailable with a warning. A deleted room and an earlier version can be restored for 30 days. |
| A Technician's phone holds no prices | Not on the screen, not in the saved data, not in a message. |
| One phone, several people | Each person's saved work sits in its own space, tied to their role. Signing out keeps it; "Remove" clears it. When someone is removed or changes role, the phone clears their space the next time it connects and says why. Their unsynced changes are not lost: they reach the server as held conflicts that an Owner or Manager accepts or discards in Saved on this phone, and nothing is applied without that review (architecture spine AD-12; PRD addendum row 9). |
| The phone may lose saved files | A phone can clear saved files when space runs short, or after about a week unused in a browser. The app asks the phone to keep them, warns when it cannot, and on an iPhone asks to install first. |
| Slow is not offline | After 8 seconds of waiting, a screen says "Slow connection. Still trying." and keeps trying. Text comes first, lists load 20 at a time, and video waits to be asked for. |
| An action that may have gone through | If the signal drops after a button that makes an order or moves money, the screen says what is known: "We have not heard back. Your order may have been made. Check Orders, or try again: you cannot make two." A receipt upload keeps its details and resumes. |

## Key Flows

Seven journeys from the PRD, named as the PRD names them. Each has a cast, numbered steps and a climax beat, and is followed by its failure and edge paths. The Cart, cancelling and changing an order, and the console gaps are folded into the flows they belong to.

### UJ-1. Tunde designs a Project, quotes his client, and places the Order.

**Cast:** Tunde, an Owner, on his phone in the van and then at his desk. Mockups: [shell](mockups/app/flow-uj1-step1-shell.html), [building the Project](mockups/app/flow-uj1-step2-build-project.html), [the Proposal](mockups/app/flow-uj1-step3-proposal.html), [creating the Order](mockups/app/flow-uj1-step4-order.html), [pay](mockups/app/flow-uj1-step5-payment.html), [the Orders tab](mockups/app/flow-uj1-step6-orders.html), [cancel and change](mockups/app/flow-uj1-step25b-cancel-and-change-order.html).

1. Tunde opens the app. Home shows "Needs your attention" first, then his recent projects. He taps "New project" and types a name. The first room is made for him and he renames it with the pencil.
2. In the room he taps "Add product", searches, filters, and adds products with the stepper. The bar says "Done · 7 items in Entrance and gate". A back-order item shows its tag, lead time and discount. He adds a second room. The Project total shows with "VAT included".
3. He taps "Save for offline" so his technician has it, and opens the Parts list, which has no prices.
4. He taps "Generate proposal", enters the client's name, sets one margin, checks the private "Only you see this" panel, and shares the PDF from the phone's share sheet.
5. Weeks later the client agrees. He taps "Create order". Review shows the difference since the Proposal split into exchange rate, tier and item changes, with delivery on its own line, a plain line that the client's Proposal does not change, and his margin at today's cost, so he can decide whether to pass the difference to his client or absorb it from his margin.
6. He chooses delivery or pick-up, and whether the order comes in one delivery or sends what is ready first, and confirms. Confirm shows the Amount due and the 24-hour price hold. A Project with a back-order item adds one more tick: see UJ-4.
7. He transfers the money from his bank, using Copy on the account and the order reference, and uploads the receipt. The order says "Receipt under review" and "Price held".
8. **Climax:** Finance confirms the payment against the bank statement, the order becomes Paid, Operations approve it, and Tunde follows each delivery to Delivered or Signed For. The completed order counts toward his Loyalty Tier.

**Failure and edge paths.**

- The rate moves while he reviews: a sheet shows the old and new amounts with "Confirm new price and create order" or "Back to review".
- Finance cannot match his transfer: a red banner reads "Payment unconfirmed: please upload your receipt again or pay the full amount." A corrected receipt sent within 24 hours of that notice keeps the original upload time, so the price hold still counts, when the full amount matches.
- The price hold passes before payment completes: "Price no longer held" with the new amount due, confirmed so far and still to pay.
- His client withdraws before approval: "Cancel order" is at once, the confirmed ₦731,729.94 becomes a credit note, and the Project is untouched. While a payment is still under review it is "Ask finance to cancel", and after approval "Ask to cancel", a request Operations answers within 1 business day.
- He has no signal at upload: the photo and details are kept and the upload resumes.

### UJ-2. Hammed joins the platform and unlocks full access.

**Cast:** Hammed, who has no account; Segun, an Established Integrator who vouches for him; Kunle in Admin. Mockups: [joining and verification](mockups/app/flow-uj2-step7-joining.html), [vouch codes and team](mockups/app/flow-uj2-step8-account-vouching.html), [Admin people](mockups/console/flow-uj6-step15-console-admin-people.html).

1. Hammed signs up with his phone or email, with the Terms and Privacy Notice ticked, enters the 6-digit code, and answers the separate WhatsApp question.
2. He sees two tabs, Catalogue and Get approved. The Catalogue has no prices and says why. Get approved is a four-step checklist.
3. He confirms who he is: name, date of birth, address and NIN, and optionally a business address. The NIN is masked and only the verification team sees it. It passes.
4. Segun opens Account, then Vouch codes, and makes a code for Hammed, using his name as on the NIN and the phone he signed up with. A fresh code goes to Segun's phone. The vouch code is shown large with "Share on WhatsApp" first.
5. Hammed enters the code, his trade name and ticks the Terms key points.
6. Kunle opens the request in Level 3 requests. The screen shows who is asking, who vouches (Segun, established, vouching rights, earlier vouchees) and the three checks ticked with who, when and a note. He writes a reason and approves. The record names Segun as voucher.
7. **Climax:** Hammed's business is approved. Prices, projects, the cart, proposals and orders appear, with the five-tab bar and three next steps, and no tour.
8. He invites his two technicians from Account, then Team, with a role chosen by what the person can do.

**Failure and edge paths.**

- A definitive NIN mismatch sends the profile to manual review and every later check on that account is manual: "sent to our team within 1 business day".
- The NIN service is busy: retried up to 24 hours, then manual review.
- Rejected with a reason: up to 3 resubmissions, then "Call or WhatsApp us".
- A vouch code expires after 48 hours: Segun makes a new one.
- No voucher at all: "No code? Call or WhatsApp us", because Admin can issue platform codes.

### UJ-3. Funmi restocks parts for Tunde with a direct buy.

**Cast:** Funmi, a Manager, who runs the office. Mockups: [the Cart and the checks](mockups/app/flow-uj3-step25a-cart-and-checks.html), [using a credit note](mockups/app/flow-acct-step9-money-business.html).

1. Funmi taps "Buy parts without a project" on Home, or opens a product page, and taps "Add to cart". The page says "2 added to your cart" with the count and total.
2. She opens the cart: three lines, her own list, today's prices, and a note that nothing is kept until payment is confirmed.
3. She taps "Choose delivery or pick-up" and picks pick-up at the Main Store. Confirm shows an Amount due of ₦48,600.00, an optional order name ("Cabling and connectors"), the 24-hour price hold, and "Create order".
4. She pays by bank transfer exactly as in UJ-1. If the business holds a credit note she first taps "Use some on this order": it counts as a payment from the moment she asks, Finance completes it, and ₦10,350.00 is left to pay by transfer.
5. **Climax:** the order is Paid and approved the same day, and she collects the goods at the Main Store with her name and ID.
6. The order counts toward the business's Loyalty Tier.

**Failure and edge paths.**

- An item sells out while it sits in the cart: it shows Sold out with only Remove, and "Choose delivery or pick-up" stays off until she removes it. If back-order is enabled it shows the back-order tag and the old and new price, and the agreement is asked at the next screen.
- The unpaid cart order expires: the money is a credit note and "Put the 3 items back in my cart" is offered.
- Within seven days of collection Tunde changes his mind about an unopened item: he reports it ("I changed my mind"), Support issues a return authorisation, and the return earns a credit note less the 15% restocking fee.

### UJ-4. Tunde orders a Project that includes a Back-order Item.

**Cast:** Tunde, an Owner. Mockups: [creating the Order](mockups/app/flow-uj1-step4-order.html), [the Orders tab](mockups/app/flow-uj1-step6-orders.html), [cancel and change](mockups/app/flow-uj1-step25b-cancel-and-change-order.html), [the Operations approval](mockups/console/flow-uj6-step14-console-operations.html).

1. In his saved Project one product is Out of stock but tagged "Back-order · about 3 weeks · 8% off".
2. At Review a block names the item, its lead time and discount, and gives two disclosures: it cannot be cancelled once we have ordered it from the supplier unless it is more than 7 days late, and it cannot be returned for a change of mind. He must tick "I understand, and I want to order this item" before Continue turns on.
3. At Delivery he chooses one delivery when everything is ready, or to send what is ready first.
4. He confirms and pays as in UJ-1.
5. **Climax:** the in-stock items ship first, Delivery 1 in transit with its tracking reference, and the back-order item follows once it arrives as Delivery 2.
6. Each delivery reaches Delivered or Signed For on its own.

**Failure and edge paths.**

- An item he thought was in stock sells out between his receipt upload and its confirmation: Finance credits that order line and the rest goes ahead.
- The back-order item is more than 7 days past its promised date: the card says "Running late" and he can cancel that line at once for a credit note.
- Shipping in full has waited 14 days: "Send what is ready now?", where he pays the extra delivery at the zone rate before it is sent.

### UJ-5. Chidi finds the installation guide on site.

**Cast:** Chidi, a Technician, on a phone with poor signal. Mockups: [the Technician's phone](mockups/app/flow-uj5-step11-technician.html), [saving for offline](mockups/app/flow-uj1-step2-build-project.html).

1. The evening before, Chidi opened the Project and used "Make this Project available offline". The sheet showed what would be saved and its size, and he chose to save the written guides and manuals but not the videos.
2. On site there is almost no signal. He opens the app and his project is listed with "Saved on this phone".
3. The Project shows the same rooms, items and quantities as Tunde sees, with no price areas drawn at all. Each row says whether its guide and manual are saved.
4. He opens the 4MP PoE dome camera and reads the written installation guide, then opens the manufacturer's PDF.
5. The guide is one step to a screen in large type with big Back and Next buttons, and a photo with its description.
6. **Climax:** the install goes ahead without a phone call to the office, and nothing in his view showed any pricing.

**Failure and edge paths.**

- A product with no documentation says "Documentation coming soon." with "Need it now? Call or WhatsApp us."
- A manual that was replaced still opens with a Replaced warning. A withdrawn manual opens only after "Open anyway". A video that was not saved says "Needs signal".
- After 14 days without a check-in the saved projects lock, with a warning 3 days before, and nothing is deleted.

### UJ-6. Ngozi confirms a payment.

**Cast:** Ngozi in Finance, with Bola in Operations and Kunle in Admin. Mockups: [Finance](mockups/console/flow-uj6-step13-console-finance.html), [Operations](mockups/console/flow-uj6-step14-console-operations.html), [Admin](mockups/console/flow-uj6-step15-console-admin-people.html), [credit notes and the Platform Rate](mockups/console/flow-uj6-step26a-console-finance-rate-credit.html).

1. Ngozi signs in with two steps. Today shows a card for each queue, and a notice that the bank statement was last imported at 12:41 pm. She imports the latest.
2. The Receipts queue lists the oldest first, with each item's age against its target of 2 business hours. She opens the oldest.
3. The review has three columns: what the business sent, the order, and the bank statement. Five checks are written in words: amount, sender, bank reference unused, money arrived before the receipt, your own accounts clear.
4. She confirms the amount and presses "Confirm payment". The screen says what happens: the order becomes Paid and goes to Operations, the business is told, and a ledger line is written.
5. **Climax:** a receipt uploaded in the morning is confirmed within two business hours and the business is told.
6. Bola, in Operations, checks the stock shelf by shelf and approves the Paid order. Kunle, in Admin, handles the NIN review and Level 3 queues the same way.

**Failure and edge paths.**

- No matching credit: she chooses "Payment unconfirmed…" and the business reads "Payment unconfirmed: please upload your receipt again or pay the full amount." After three unconfirmed uploads on one order a review by Admin is required.
- The transfer is larger than the amount due: a draft credit note for the overpayment is made for her to issue.
- A different sender needs a second reviewer.
- A reference already used is refused.
- A receipt late for the price hold shows a repricing preview.
- A reversal needs a reason and a second approver.
- A business she created, approved or vouched for is refused in words.

### UJ-7. Chidi reports a faulty camera and the device goes back.

**Cast:** Chidi, a Technician; Amaka in Operations holding the Support duties; Funmi, his Manager; Admin decides. Mockups: [Report a problem to the outcome](mockups/app/flow-uj7-step12-returns.html), [the Support desk and returned devices](mockups/console/flow-uj6-step14-console-operations.html), [the return decision](mockups/console/flow-uj6-step15-console-admin-people.html).

1. Chidi finds a faulty camera. He contacts Support by phone or WhatsApp, or opens the delivery in the app and taps "Report a problem". Either way a Case is opened, the reporting window is counted from that first contact, and the app shows how long he has, in dates.
2. Amaka verifies who is calling, by an in-app code he reads out or a callback, looks up the serial number to confirm it left our Stores, and tries a remote fix.
3. The fix fails. Amaka makes the issue code, valid 48 hours. It appears in his Case with a "Raise the return request" button that fills it in.
4. He raises the return request with the issue code and serial number filled in: the reason, photos of the device, its label and the box, and a few words about the fault. Because he is a Technician it goes to his Manager first, and the 48-hour clock pauses, for up to 7 days, while it waits.
5. Funmi sees it under Needs your attention. The platform suggests the order line from the serial number; she confirms or corrects it and approves, with no amounts needed to decide. A decline gives Chidi a reason.
6. Chidi takes the device to a Store, a collection point or the Main Store, or hands it to a courier and enters the tracking reference. The clock resumes on approval and the deadline on his screen already counts the time spent waiting.
7. Amaka checks it on arrival in four steps: scan, photograph, test and record, hand over.
8. **Climax:** Amaka's findings reach Admin, who decide the remedy in the PRD's order for the reason: a replacement, a manufacturer repair, a credit note, or a rejection.
9. Tunde's business receives the outcome, every step with its date, and the return request closes as Resolved. A Technician's view describes money and does not show it.

**Failure and edge paths.**

- A serial with no dispatch record cannot get an issue code unless Operations and Admin both approve a serial exception backed by label and box photos.
- The device does not arrive within 48 hours: the request lapses and Support makes a new code. The reporting window is judged from the first contact, so a lapse does not cost the right to a remedy.
- A device that arrives after a lapse is Held.
- A serial that does not match rejects the return and flags the account.
- A unit that tests fine goes back at the business's cost.

## Inspiration & Anti-patterns

- **Lifted: the private-bank ledger.** A printed statement is the one object these users already trust with money. Warm paper, serif headings, hairline rules, a double rule under a total, figures that line up. That is the whole visual idea of Direction A (see `DESIGN.md`).
- **Lifted: the trade counter.** Practitioner to practitioner. Plain words, the price and its stock status in one line, the guide one tap away from the product.
- **Rejected: a wallet.** A balance a person can spend or withdraw changes the legal nature of the product, so the product has credit notes, issued by Finance, lasting 12 months, used only to buy from us.
- **Rejected: levels.** A business sees "approved" and a four-step checklist and never Level 1, 2 or 3.
- **Rejected: urgency theatre.** No "Hurry", no flashing clock, no streaks, no adverts, no badges. A clock states its end time and stays calm.
- **Rejected: an inbox and push.** Every event lives on the screen it is about, and WhatsApp and email carry the messages.
- **Rejected: the other three directions.** Site Kit (heavy, roomy), Soft (lilac, large radii) and Spec Sheet (monospace, bracketed codes) were shown and set aside.
- **Rejected: gestures and tours.** Nothing needs a swipe or a long press. There is no walk-through of the app on first open.
- **Rejected: dollars.** A business never sees one.
- **Banned in version 1, as a checklist.** A step-by-step tour, an in-app inbox, push notifications, adverts, streaks and badge counts, autoplay video, a spinner on its own, "Access denied", a lock icon on something a role can never use, dark mode, and any dollar figure shown to a business.

## Open items and assumptions

None of these blocks the build. Each is also in the decision record.

| Item | Where it stands |
|---|---|
| Counsel and accountant | The nine wording items on the register (Voice and Tone) and the tax invoice and credit-note document layouts (FR-77) wait for them. Related copy is provisional until then. |
| What is measured about use | Proposed: nothing that follows a person, no advertising or tracking cookies, only what the Privacy Notice lists, written with counsel. The owner decides before launch. |
| Placeholders | The second-approver amount (₦100,000.00); the rate warnings at 36 and 44 hours; the stock-adjustment sign-off size; zone rates and estimates; document size limits; the delivery price answer time (1 business day) and its validity (the end of the next business day); the 8-second "slow connection" line. |
| Not yet on a screen | The FR-66 14-day clock for a suspected counterfeit has no card on Admin's Today. Product Range management (create, rename, retire) is assumed to be Admin's and is not drawn. A recovery request has no queue or target in FR-78. The twelve launch messages are a table to be written to the voice rules. |
| The PRD changes this work asks for | Collected in [`prd-change-requests.md`](prd-change-requests.md), grouped by PRD section, each with the step whose sheet shows it. Items tagged Open are not settled by the sheets. |

**Decided with a default.** Each was drawn on a sheet the owner confirmed, and each can still be changed.

- The extra delivery in the 14-day offer is paid by the business at the zone rate before it is sent. Absorbing it is the alternative.
- The 48-hour rate stop is counted in calendar hours, with a Saturday and Monday warning.
- FR-4: a person with no NIN is handled by phone or WhatsApp with no in-app screen.
- Bulk upload: a file that changes many prices waits for Admin as a whole file.
- Stopping a supplier takes its products off sale after a preview, with replacements named per product.
- Revoking approval returns a business to Get approved with prices hidden, and orders already made are still processed.

## Mockups

Wireframes in the Ledger look, kept in `mockups/`. Each illustrates the section it is linked from; this is the index.

| File | What it illustrates |
|---|---|
| [system/direction-ledger.html](mockups/system/direction-ledger.html) | The chosen visual direction on the Project, Technician, offline, payment and Receipts queue screens |
| [system/voice-tone-step19.html](mockups/system/voice-tone-step19.html) | Voice rules, tone, formats, word list, message patterns, the counsel register |
| [system/patterns-step20.html](mockups/system/patterns-step20.html) | The sixteen core components, the sixteen states, the interaction rules |
| [system/accessibility-step21.html](mockups/system/accessibility-step21.html) | The accessibility floor and the computed colour contrast |
| [system/responsive-platform-step22.html](mockups/system/responsive-platform-step22.html) | The three sizes, four laptop-width frames, platform and install rules |
| [system/product-rules-step23.html](mockups/system/product-rules-step23.html) | Money, time, who sees what, trust, offline |
| [system/ia-coverage-step24.html](mockups/system/ia-coverage-step24.html) | The app and console maps, where each thing lives, the 80-requirement trace |
| [app/flow-uj1-step1-shell.html](mockups/app/flow-uj1-step1-shell.html) | Home, Projects, New project |
| [app/flow-uj1-step2-build-project.html](mockups/app/flow-uj1-step2-build-project.html) | The Project, the picker, the product page, Save for offline |
| [app/flow-uj1-step3-proposal.html](mockups/app/flow-uj1-step3-proposal.html) | Parts list, Proposal builder, Proposal ready, the Proposal card, the PDF |
| [app/flow-uj1-step4-order.html](mockups/app/flow-uj1-step4-order.html) | Create order: Review, Delivery, Confirm, the changed-price sheet |
| [app/flow-uj1-step5-payment.html](mockups/app/flow-uj1-step5-payment.html) | Pay by bank transfer, upload a receipt, waiting, the signal drops |
| [app/flow-uj1-step6-orders.html](mockups/app/flow-uj1-step6-orders.html) | The Orders list, one order, a payment problem, the order states |
| [app/flow-uj2-step7-joining.html](mockups/app/flow-uj2-step7-joining.html) | Sign up, the code, Get approved, identity, the vouch-code request, Approved |
| [app/flow-uj2-step8-account-vouching.html](mockups/app/flow-uj2-step8-account-vouching.html) | Account home, vouch codes, making a code, Team, inviting |
| [app/flow-acct-step9-money-business.html](mockups/app/flow-acct-step9-money-business.html) | Credit notes, using one, a refund, Loyalty tier, Business profile |
| [app/flow-acct-step10-you-security.html](mockups/app/flow-acct-step10-you-security.html) | Notifications, sign-in and security, getting back in, new Terms, closing the business |
| [app/flow-uj5-step11-technician.html](mockups/app/flow-uj5-step11-technician.html) | The invitation, a Technician's Home and Project, documentation, the written guide, Saved on this phone |
| [app/flow-uj7-step12-returns.html](mockups/app/flow-uj7-step12-returns.html) | Report a problem, the Case, raising and approving a return, bringing it back, the outcome, Problems |
| [app/flow-uj3-step25a-cart-and-checks.html](mockups/app/flow-uj3-step25a-cart-and-checks.html) | The Cart, ordering from it, and every reason an order cannot be made |
| [app/flow-uj1-step25b-cancel-and-change-order.html](mockups/app/flow-uj1-step25b-cancel-and-change-order.html) | Cancel, change, ask to cancel, a late back-order line, the delivery exceptions |
| [console/flow-uj6-step13-console-finance.html](mockups/console/flow-uj6-step13-console-finance.html) | The console shell, Today, Receipts, review, unmatched credits, statement import |
| [console/flow-uj6-step14-console-operations.html](mockups/console/flow-uj6-step14-console-operations.html) | Operations Today, order approvals, a shipment, the Support desk, a returned device |
| [console/flow-uj6-step15-console-admin-people.html](mockups/console/flow-uj6-step15-console-admin-people.html) | Admin Today, Level 3 review, NIN review, a business account and holding it, deciding a return |
| [console/flow-uj6-step16-console-admin-settings.html](mockups/console/flow-uj6-step16-console-admin-settings.html) | Approvals, Pricing and rules, Staff, the audit trail, the weekly review, the dashboard |
| [console/flow-uj6-step17-console-catalogue-stock.html](mockups/console/flow-uj6-step17-console-catalogue-stock.html) | Products and stock, one product, goods in and the ledger, bulk upload |
| [console/flow-uj6-step18-console-content-suppliers-zones.html](mockups/console/flow-uj6-step18-console-content-suppliers-zones.html) | Documentation, the guide editor, Suppliers, Stores and delivery zones |
| [console/flow-uj6-step26a-console-finance-rate-credit.html](mockups/console/flow-uj6-step26a-console-finance-rate-credit.html) | The Platform Rate, credit note requests and reviews, credit notes and refunds |
| [console/flow-uj6-step26b-console-admin-ops-gaps.html](mockups/console/flow-uj6-step26b-console-admin-ops-gaps.html) | Holds and blocklist, publishing Terms, creating businesses, delivery price requests |

