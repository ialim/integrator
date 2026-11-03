---
title: Sprint Change Proposal: staff-side Supplier onboarding
date: 2026-11-02
author: ialim (with the bmad-correct-course workflow)
status: approved
mode: incremental
scope: moderate
---

# Sprint Change Proposal: staff-side Supplier onboarding

## 1. Issue summary

**Trigger.** A new requirement from the owner (ialim), raised while Epic 1 is being built: the platform needs a proper **Supplier onboarding** step. It is not tied to a story that failed. The issue type is "new requirement emerged from stakeholders".

**Meaning confirmed.** This is **staff-side** onboarding. The platform team records and checks a Supplier before approving it, and Suppliers still never sign in. A Supplier self-service portal was considered and **not** chosen, so `EXPERIENCE.md` ("Suppliers never sign in"), AD-18 (exactly two authentication surfaces) and PRD §5.3 ("Suppliers do not onboard themselves or log in") all stand.

**Problem statement.** Story 2.2 and FR-14 record a Supplier only as a name, an approval flag and three agreement terms (returns, dead-on-arrival, counterfeit chargeback). The owner needs the onboarding record to also hold:

- the signed agreement as a file;
- company and compliance documents (CAC, TIN, SON or NCC certificates, authorised-distributor letters), with expiry dates;
- business details and contacts (sales, and returns and RMA);
- the Supplier's bank details;

and wants approval gated by a checklist, plus warnings (only warnings) when documents near expiry.

**Evidence and decisions captured (owner's answers, 2 November 2026).**

| Question | Owner's decision |
|---|---|
| Staff-side or supplier portal | Staff-side |
| Record contents | Signed agreement file; company and compliance documents; contacts and business details; Supplier bank details |
| Gating | The checklist gates "Approve the supplier"; Admin decides which items are required; "Approve with a reason" stays |
| Expiry | Warn only: Needs attention plus an Admin alert; nothing comes off sale |

**Timing.** Epic 1 is in progress (1.1 and 1.2 in review, 1.3 in progress on `story-1-3-staff-sign-in`). Every Epic 2 story is `backlog` and none has a story file, so Epic 2 can be changed now at almost no cost.

## 2. Impact analysis

### Checklist record

| Item | Status | Note |
|---|---|---|
| 1.1 Triggering story | [N/A] | New requirement; closest existing story is 2.2 (FR-14) |
| 1.2 Core problem | [x] | New stakeholder requirement, staff-side only |
| 1.3 Evidence | [x] | Owner's answers above |
| 2.1 Current epic (Epic 1) | [x] | Unaffected; completes as planned |
| 2.2 Epic-level changes | [x] | Epic 2: one story amended, three added, renumbered |
| 2.3 Remaining epics | [x] | No changes needed (see below) |
| 2.4 Obsolete or new epics | [x] | None obsolete, no new epic |
| 2.5 Epic order | [x] | Unchanged |
| 3.1 PRD | [!] | New FR-81, FR-14 pointer, role matrix, FR-52 and FR-53 lists, §5.3 line |
| 3.2 Architecture | [!] | New AD-27; AD-5 and AD-14 binds; module map; entity diagram; open question |
| 3.3 UX | [!] | `EXPERIENCE.md` Suppliers and Approvals rows; mockup refresh optional |
| 3.4 Other artifacts | [!] | `sprint-status.yaml` regenerated; no code, CI or infra change now |
| 4.1 Direct adjustment | Viable | Effort medium-low, risk low |
| 4.2 Rollback | Not viable | Nothing built touches Suppliers |
| 4.3 MVP review | Not needed | MVP intact; the feature is added to P1 |
| 4.4 Path | [x] | Option 1, direct adjustment |
| 5.x Proposal components | [x] | This document |
| 6.1 to 6.3 Review and approval | [x] | Approved in incremental review, P1 to P7 |
| 6.4 Sprint status | [!] | Handed off: rerun `bmad-sprint-planning` (Section 5) |
| 6.5 Handoff | [x] | Section 5 |

### Epic impact

- **Epic 1** is not affected. No Epic 1 story, branch or status changes.
  - Story 1.5's capability registry gets the new Supplier capabilities when Epic 2 builds them, as every module does.
  - Story 1.6's audit trail gets new personal-data columns from Catalogue.
- **Epic 2** is the only epic edited:
  - Story 2.2 is amended.
  - Three new stories are added. Each one sits directly after the story that delivers the kernel part it depends on:
    - documents need the **upload pipeline** (Product images);
    - bank details need the **Approvals page** (the rate second-person story);
    - expiry warnings need the **timer sweep** (rate staleness).
  - Epic 2 is renumbered from 18 to 21 stories.
- **Epics 9, 13 and 16** (back-orders, Support, returns and counterfeit) now have a Supplier returns and RMA contact to use. Their acceptance criteria do not change.
- **Epic 15** (SM-5 active Suppliers) is unchanged.

### Story impact: Epic 2 renumbering

| New | Story | Was |
|---|---|---|
| 2.1 | Admin sets up Product Ranges and their markup | 2.1 |
| 2.2 | **Admin records Suppliers and approves them against an onboarding checklist** (amended) | 2.2 |
| 2.3 | Operations creates and edits a Product | 2.3 |
| 2.4 | A Product goes live, and leaves | 2.4 |
| 2.5 | Product images | 2.5 |
| 2.6 | **A Supplier's agreement and compliance documents** (new) | — |
| 2.7 | Finance sets and reconfirms the Platform Rate | 2.6 |
| 2.8 | A rate change above 3% needs a second person | 2.7 |
| 2.9 | **Supplier bank details, with Finance's agreement** (new) | — |
| 2.10 | Selling Prices computed to the kobo | 2.8 |
| 2.11 | The remaining Pricing formulas, proven | 2.9 |
| 2.12 | Admin changes a markup and sees what it will do | 2.10 |
| 2.13 | Stock quantities, thresholds and the ledger | 2.11 |
| 2.14 | Operations records goods-in | 2.12 |
| 2.15 | Back-order terms and the discount rule | 2.13 |
| 2.16 | Admin switches the Active Supplier, or stops a Supplier | 2.14 |
| 2.17 | Bulk upload with a dry run | 2.15 |
| 2.18 | A bulk price file waits for Admin and can be rolled back | 2.16 |
| 2.19 | The rate goes stale, and Finance can pause orders | 2.17 |
| 2.20 | **Supplier documents nearing expiry** (new) | — |
| 2.21 | Acting as another role, and the founder's weekly review | 2.18 |

The only cross-reference to an Epic 2 story number outside its own heading is in Epic 4 (Business Hours story): "the Platform Rate page from Story 2.17" becomes "**Story 2.19**". Within Epic 2, "Story 2.7 adds the approval route" (in the reconfirm-rate story) becomes "**Story 2.8**".

### Artifact conflicts

- **PRD:** no conflict with the goals or non-goals. The requirements are additive (FR-81) and the MVP stays achievable.
- **Architecture:** no conflict with AD-18. There is one gap: how Supplier bank details are protected (the new AD-27). The rest fits AD-5, AD-13, AD-14, AD-21, AD-23 and AD-24 as written.
- **UX:** two `EXPERIENCE.md` rows change. `DESIGN.md` is unchanged, because the onboarding checklist reuses the "Before it can go live" pattern and the masked field reuses the reveal pattern.

### Technical impact

No code changes now. When Epic 2 is built, the Catalogue module gains:
- `supplier_contacts`, `supplier_documents` (versioned) and `supplier_bank_accounts` (encrypted) tables;
- a Supplier onboarding settings row (the required checklist items);
- two deadline-column pairs, swept by the AD-5 sweep;
- a new secret: the Supplier bank-detail key.

The key must be added to the host secret store, the second escrow store and the quarterly restore drill (Story 1.2's runbook) before Story 2.9 ships.

## 3. Recommended approach

**Option 1, direct adjustment**: amend Story 2.2 and add three stories inside Epic 2.

- **Why:**
  - Epic 2 has not started, so this costs a renumber rather than any rework.
  - The feature fits existing mechanisms: the staged upload pipeline, deadline columns and the sweep, the Approvals page, work items, and the field-visibility registry.
  - It needs only one new architecture decision.
- **Rejected:**
  - A rollback, because nothing that has been built touches Suppliers.
  - An MVP cut, because the feature strengthens the PRD's trust pillar ("Products come from Suppliers the platform has approved") rather than competing with it.
  - The supplier portal, which the owner did not choose.
- **Effort:** about three stories' worth (one amended, three new, two of them small).
- **Risk:** low. The main risk is that gating approval on documents could slow loading the launch Catalogue. Two things mitigate it: Admin controls which items are required, and "Approve with a reason" remains.
- **Timeline:** Epic 2 grows by three stories. Epic 1 and the epic order are unchanged.

## 4. Detailed change proposals

All of P1 to P7 were approved by the owner on 2 November 2026 in incremental review.

### 4.1 Stories (`epics.md`)

#### P1. Story 2.2 (amended)

**OLD:** "Story 2.2: Admin records and approves Suppliers", as at `epics.md` lines 1013–1047 (the three terms, Approve gated on them, the dead-on-arrival window, the list, refusal for Finance and Operations).

**NEW:**

```markdown
### Story 2.2: Admin records Suppliers and approves them against an onboarding checklist

As an Admin,
I want each Supplier recorded with its business details, contacts and agreement terms, and approved only when its onboarding checklist is clear,
So that only Suppliers we know, and can hold to their terms, supply the Catalogue.

**Requirements:** FR-14, FR-81

**Acceptance Criteria:**

**Given** an Operations or Admin account on the Suppliers page
**When** they add a Supplier
**Then** it is saved as not approved, with Business details (legal name, trade name, CAC RC number, TIN, address, the currency it invoices in, USD or NGN, and payment terms), Contacts (sales, and returns and RMA, each with a name, phone and email) and its returns terms, dead-on-arrival terms and window in days, and counterfeit-chargeback terms, each shown as stated or missing.

**Given** a Supplier
**When** anyone views it
**Then** an "Onboarding checklist" shows each required item as Clear or Needs action in words, in the same pattern as "Before it can go live".

**Given** an Admin on the Supplier onboarding settings
**When** they mark checklist items required or optional
**Then** the three agreement terms are always required and cannot be made optional, the change is audited, and it applies to approvals from then on without un-approving any Supplier already approved.

**Given** a Supplier with any required item at Needs action
**When** an Admin views it
**Then** "Approve the supplier" is visible, dashed and off, with the reason beside it naming the items
**And** "Approve with a reason…" is offered, which requires a written reason that is stored and shown on the Supplier.

**Given** a Supplier whose required items are all Clear
**When** an Admin approves it
**Then** it becomes approved and the decision is audited with who and when.

**Given** a Supplier whose dead-on-arrival window is shorter than 30 days
**When** it is saved
**Then** the shorter window is stored so it can shorten the window given to businesses (FR-59).

**Given** the Suppliers list
**When** a staff member views it
**Then** each row shows the Supplier, whether it is approved, its Product count and its checklist progress ("6 of 8 clear"), with filter chips for All, Needs attention, Approved and Not approved.

**Given** a Finance or Operations account
**When** it tries to approve a Supplier
**Then** the server refuses.

**Given** the Supplier contact names, phones and emails
**When** they change
**Then** they are registered as Catalogue personal-data columns, so the audit diff records "changed" and never the value (AD-21).

**Given** a keyboard, screen-reader or 200% zoom user
**When** they use the Supplier page
**Then** each checklist item is announced with its status in words, the reason beside the off "Approve the supplier" button is reachable and read, and nothing is cut off or needs sideways scrolling.
```

**Rationale:** the owner chose a checklist that gates approval and is configured by Admin, with business details and contacts in the record. Recording is opened to Operations, who already load the Catalogue (FR-12, FR-74). Approval stays Admin-only per the FR-52 matrix.

#### P2. New Story 2.6: A Supplier's agreement and compliance documents

```markdown
### Story 2.6: A Supplier's agreement and compliance documents

As Operations,
I want to attach a Supplier's signed agreement and compliance documents, with their expiry dates,
So that Admin approves a Supplier against its papers, not a promise.

**Requirements:** FR-81; AD-13, AD-14

**Acceptance Criteria:**

**Given** an Operations or Admin account on a Supplier
**When** they add a document
**Then** they choose its type (signed supplier agreement, CAC certificate, TIN certificate, SON or NCC certificate, authorised-distributor letter, or Other with a name) and upload a PDF, JPEG, PNG or HEIC through the staged upload pipeline from Story 2.5, and the document is usable only once the scan promotes it; a failed scan says so in words and keeps nothing.

**Given** a Supplier document
**When** anyone opens it
**Then** it is served as private evidence through a short-lived signed URL and is never overwritten.

**Given** a document of a type already on file
**When** a new one is uploaded
**Then** it becomes the current version and earlier versions stay listed as Replaced, with who and when.

**Given** a document type of SON or NCC certificate or authorised-distributor letter
**When** it is saved
**Then** an expiry date is required; other types take an optional expiry date, and the signed agreement also records its signed date.

**Given** a required document type on the onboarding checklist (Story 2.2)
**When** the checklist is evaluated
**Then** it is Clear only when a current, scanned, unexpired version exists.

**Given** a staff member removing a document
**When** they confirm
**Then** a reason is required, the file is kept and marked Withdrawn, and the removal is audited.

**Given** a Finance account
**When** it views a Supplier
**Then** it can open the documents but cannot add, replace or remove them.

**Given** any Integrator-facing surface
**When** the AD-13 visibility suite runs
**Then** no Supplier document, contact or business detail is ever returned.

**Given** a keyboard, screen-reader or 200% zoom user
**When** they upload a document
**Then** progress, scanning and the result are announced, and the document list reads type, version, status and expiry in words.
```

**Rationale:** private, versioned evidence is what AD-14 already defines. The story sits after Product images, which build the upload pipeline.

#### P3. New Story 2.9: Supplier bank details, with Finance's agreement

```markdown
### Story 2.9: Supplier bank details, with Finance's agreement

As an Admin,
I want a Supplier's bank details recorded only with a Finance member agreeing,
So that nobody can quietly redirect what we pay a Supplier.

**Requirements:** FR-81, FR-52, FR-53; AD-23, AD-27

**Acceptance Criteria:**

**Given** an Admin on a Supplier
**When** they add or change a bank account (bank name, account name, account number as a 10-digit NUBAN for NGN, or account number, SWIFT or BIC and bank address for USD)
**Then** the change is a request on the Approvals page that says what it will do and why two people are needed, and the Supplier shows "Bank details waiting for Finance".

**Given** a pending Supplier bank-detail request
**When** a Finance member who did not make the request approves it
**Then** the new details take effect inside the approval, the change is audited, and an event is emitted with its facts so Admin and Finance are emailed (the email channel lands with Epic 11)
**And** a decline needs a reason that goes back to the requester.

**Given** the requester
**When** they try to approve their own request
**Then** Approve is off and the server refuses.

**Given** Supplier bank details anywhere in the console
**When** they are shown
**Then** they are masked to the last four digits; "Show full details" is offered to Admin and Finance only, needs a reason code and is an audited read (FR-53); Operations sees only "Bank details on file".

**Given** Supplier bank details
**When** they are stored, logged, queued, exported or audited
**Then** they are encrypted under the Supplier bank-detail key and never appear in logs, exception reports, queue or outbox payloads or exports, and audit diffs hold a keyed hash (AD-27).

**Given** the onboarding checklist
**When** "Bank details" is a required item
**Then** it is Clear only once a bank account has been approved.

**Given** any Integrator-facing surface
**When** the AD-13 visibility suite runs
**Then** no Supplier bank detail is ever returned.

**Given** a keyboard, screen-reader or 200% zoom user
**When** they use the bank details panel
**Then** the masked value is announced as "ending in" plus four digits, and the request state is read in words.
```

**Rationale:** a changed bank account is the classic supplier-fraud vector. The 24-hour delay and the 7-day banner used for platform bank details (Story 8.1) are not copied, because no Integrator pays into these accounts and v1 pays no Supplier through the platform. The story sits after the rate second-person story, which builds the Approvals page.

#### P4. New Story 2.20: Supplier documents nearing expiry

```markdown
### Story 2.20: Supplier documents nearing expiry

As an Admin,
I want to be told before a Supplier's document expires,
So that papers are renewed before they lapse, without taking goods off sale.

**Requirements:** FR-81; AD-5, AD-24

**Acceptance Criteria:**

**Given** a Supplier document with an expiry date
**When** it is saved
**Then** it stores `expires_at` and an `expiry_warning_due_at` 30 days earlier, with an `expiry_warning_sent_at` column, and the sweep drives both.

**Given** a document whose warning is due
**When** the sweep runs
**Then** the Supplier appears under Needs attention with "[Document] expires on [date]", and one work item opens in the Admin queue, once.

**Given** a document whose expiry passes
**When** the sweep runs
**Then** it shows Expired, its checklist item shows Needs action, the Supplier stays approved and its Products stay on sale, and nothing else changes.

**Given** a current version that replaces an expiring or expired document
**When** it is promoted
**Then** the work item closes and the Needs attention flag clears.

**Given** a changed expiry date
**When** it is saved
**Then** both deadlines are recomputed, and the warning Action re-reads its deadline under the Supplier's lock before acting.

**Given** a keyboard, screen-reader or 200% zoom user
**When** they open the Needs attention chip
**Then** each expiring or expired document is read with its date and status in words.
```

**Rationale:** warn only, as the owner chose. The story sits after rate staleness, which builds the timer sweep.

#### P4b. Renumbering and cross-references

As in the table in Section 2. Also fix the cross-references: "Story 2.17" becomes "Story 2.19" (Epic 4), and "Story 2.7 adds the approval route" becomes "Story 2.8".

#### P5 (epics part). Inventory, coverage map and Epic 2 summary

- **Requirements Inventory**, after FR-80, add:

  > FR-81: [P1] Each Supplier has an onboarding record: business details (legal name, trade name, CAC RC number, TIN, address, invoicing currency, payment terms), contacts (sales; returns and RMA), versioned private documents (signed agreement, CAC, TIN, SON or NCC certificates, authorised-distributor letters, other) with expiry dates, and bank details. An Admin-set checklist of required items (the three FR-14 terms always required) gates approval, with Admin able to approve with a written reason. Bank-detail changes need a Finance second approver, are shown masked, and every full read is audited. A document nearing expiry (30 days) or expired puts the Supplier under Needs attention and alerts Admin; nothing comes off sale.

- **FR Coverage Map**, add: `FR-81: Epic 2 - Supplier onboarding record`.
- **Epic 2 goal** (both the Epic List entry and the Epic 2 heading):
  - **OLD:** "Operations loads Products, Suppliers, stock and goods-in, singly or by bulk upload; …"
  - **NEW:** "Operations loads Products, Suppliers (with their onboarding record and documents), stock and goods-in, singly or by bulk upload; …"
- **Epic 2 notes**, append: "Supplier onboarding (FR-81) reuses the upload pipeline (Story 2.6), the Approvals page (Story 2.9) and the timer sweep (Story 2.20), so each of its stories follows the story that delivers its kernel part."

### 4.2 PRD (`prds/prd-integrator-2026-09-27/prd.md`)

#### P5. PRD edits

1. **New FR-81** in §4.2, after FR-14:

   ```markdown
   #### FR-81: Supplier onboarding record
   *Status: Confirmed (change proposal, 2 November 2026). Priority 1.*

   Each Supplier has an onboarding record kept by Staff (Suppliers do not log in): business details (legal name, trade name, CAC RC number, TIN, address, invoicing currency, payment terms), contacts (sales; returns and RMA), documents (signed agreement, CAC certificate, TIN certificate, SON or NCC certificates, authorised-distributor letters, other), each versioned, private, and carrying an expiry date where one applies, and bank details. Admin sets which checklist items are required; the three FR-14 agreement terms are always required. "Approve" stays off until every required item is clear, and Admin can instead approve with a written reason. Supplier bank-detail changes need a Finance second approver, are shown masked, and every full read is audited. A document within 30 days of expiry, or expired, puts the Supplier under Needs attention and alerts Admin; the Supplier stays approved and nothing comes off sale. Realizes UJ-6.

   **Consequences (testable):**
   - A Supplier with a required checklist item not clear cannot be approved without a written reason.
   - No Supplier document, contact or bank detail reaches any Integrator surface.
   - A Supplier bank-detail change takes effect only on a Finance member's approval, never the requester's.
   - An expired document never changes a Supplier's approval or a Product's sellability.
   ```

2. **FR-14**, append: "The Supplier's onboarding record and checklist are defined in FR-81."
3. **§8 role matrix (FR-52)**, add rows after "Switch Active Suppliers; approve Suppliers":

   ```markdown
   | Record Supplier details, contacts and documents (Finance can view) | No | Yes | Yes |
   | Edit Supplier bank details (a second approver from Finance) | No | No | Yes |
   | Reveal full Supplier bank details (audited) | Yes | No | Yes |
   ```

4. **Segregation of duties, second-approver bullet:**
   - **OLD:** "… Active Supplier switches, bulk price changes, and any change to the underpayment tolerance need a second approver."
   - **NEW:** "… Active Supplier switches, Supplier bank-detail changes (a Finance approver), bulk price changes, and any change to the underpayment tolerance need a second approver."

   Mirror the same change in the FR-52 summary line in `epics.md`.
5. **FR-53 audited events:**
   - **OLD:** "- Active Supplier changes."
   - **NEW:** "- Active Supplier changes, Supplier approvals, and Supplier bank-detail changes."
6. **§5.3 Non-Goals**, first bullet, append: "Supplier bank details are reference records; v1 pays no Supplier through the platform."

**MVP impact:** additive P1 requirement; the MVP is still achievable.

### 4.3 Architecture (`architecture/architecture-integrator-2026-10-09/ARCHITECTURE-SPINE.md`)

#### P6. Architecture edits

1. **New AD-27**, after AD-26:

   ```markdown
   ### AD-27 — Supplier bank details are sealed, masked and revealed on purpose [ADOPTED]

   - **Binds:** Catalogue (FR-81, FR-52, FR-53), backups (AD-20)
   - **Prevents:** a Supplier's account number readable with the app key, leaking into logs or exports, or changed by one person
   - **Rule:** Supplier bank details are encrypted with a dedicated key (never `APP_KEY`) held in the host's secret store and escrowed with the AD-16 keys, and are tested in the quarterly restore drill. They are shown masked to the last four digits; the full value is read only through an audited reveal Action with a reason code, for Admin and Finance. Changes are an AD-23 Request and Approve pair whose approver must hold the Finance role. They are excluded from logs, exception reports, queue and outbox payloads, analytics and exports, and audit diffs record a keyed hash. Platform bank details (Orders) are not covered: Integrators must see them.
   ```

2. **AD-14 Binds:** add "Supplier documents (FR-81)". In the **Evidence and private documents** list, add "Supplier documents (versioned; replacing keeps earlier versions)".
3. **AD-5 Binds:** add "FR-81 Supplier document expiry and warning".
4. **Module table, Catalogue row:**
   - "Products, Product Ranges, Suppliers and the Active Supplier, …" becomes "Products, Product Ranges, Suppliers, their onboarding records, documents and bank details, and the Active Supplier, …"
   - Its FRs gain FR-81.
5. **Capability → Architecture Map**, the Catalogue row becomes "Catalogue, Suppliers and onboarding, stock, goods-in (FR-12–18, 73, 74, 81)", with AD-5 and AD-27 added.
6. **Entity diagram**, add: `SUPPLIER ||--o{ SUPPLIER_DOCUMENT : "evidenced by"` and `SUPPLIER ||--o{ SUPPLIER_BANK_ACCOUNT : "paid to"`.
7. **Open Questions**, add: "Retention and erasure of Supplier contact personal data under NDPA. AD-26 is keyed by Business Account and does not cover Supplier contacts. Owner: Counsel. Does not block the build."

### 4.4 UX (`ux-designs/ux-integrator-2026-10-07/EXPERIENCE.md`)

#### P7. UX edits

1. **Admin console, Suppliers row:**
   - **OLD:** "Who the goods come from and whether approved, product counts, and the three agreement terms (returns, dead on arrival, counterfeit chargeback) stated or missing. "Approve the supplier" stays off until all three are written down, with an "Approve with a reason" route. …"
   - **NEW:** "Who the goods come from and whether approved, product counts and checklist progress, with a Needs attention chip for expiring or expired papers. One Supplier shows Business details, Contacts (sales; returns and RMA), the three agreement terms, Documents (type, version, Replaced or Withdrawn, expiry date) and Bank details masked to the last four digits with "Show full details" for Admin and Finance. An Onboarding checklist shows each required item as Clear or Needs action; "Approve the supplier" stays off until it is clear, with an "Approve with a reason" route. Admin chooses which items are required; the three terms always are. …" (the existing text from "A shorter supplier dead-on-arrival window…" onwards is kept).
2. **Approvals row:** add "a supplier's bank details" to the list of changes.
3. **"A second person is needed" list:** after "new bank details (…)", add "a supplier's bank details (Admin edits, Finance approves, they take effect on approval)".
4. **Mockup** `mockups/console/flow-uj6-step18-console-content-suppliers-zones.html` is now behind the spec. An optional `bmad-ux` refresh is recommended before Story 2.2. It is not a blocker: stories build from `EXPERIENCE.md`.

`DESIGN.md` is unchanged.

## 5. Implementation handoff

**Scope classification: Moderate.** This is a backlog reorganisation plus documentation edits. No fundamental replan is needed.

| Step | Who | What | Done when |
|---|---|---|---|
| 1 | PM (`bmad-prd`, update) | Apply the P5 PRD edits | FR-81 is in §4.2 and the matrix, FR-52, FR-53 and §5.3 are updated |
| 2 | Architect (`bmad-architecture`, update) | Apply the P6 edits | AD-27 is adopted, the binds and maps are updated, the open question is listed |
| 3 | UX (`bmad-ux`, update) | Apply the P7 edits; optionally refresh the step-18 mockup | The Suppliers and Approvals rows match this proposal |
| 4 | PO/Dev (`bmad-create-epics-and-stories`, or a direct edit) | Apply P1 to P4b in `epics.md` | Epic 2 has 21 stories numbered as in Section 2, FR-81 is in the inventory and coverage map, and the cross-references are fixed |
| 5 | PO/Dev (`bmad-sprint-planning`) | **Rerun sprint planning** to regenerate `sprint-status.yaml` from the edited epics, **preserving every existing status** | Epic 1 statuses are unchanged; Epic 2 lists 21 `backlog` keys with the new numbers |
| 6 | Founder | Before Story 2.9 ships: create the Supplier bank-detail key in the host secret store and the escrow store, and add it to the restore-drill runbook (Story 1.2) | The key exists in both stores and is drill-tested |

**Progress (2 November 2026):** steps 1 to 3 are applied as direct edits to `prd.md`, `ARCHITECTURE-SPINE.md` (with a `.memlog.md` entry) and `EXPERIENCE.md`. The step-18 mockup refresh is not done. Step 4 is applied to `epics.md`: Epic 2 now has 21 stories, 175 in total. In the Epic 2 notes, the kernel stories are named by their new numbers (2.5, 2.8 and 2.19). Steps 5 and 6 are still open.

**Sequencing and constraints**

- Do not interrupt Epic 1. Steps 1 to 5 change planning documents only.
- Step 5 touches `sprint-status.yaml`, which `story-1-3-staff-sign-in` also has modified in the main checkout. Run step 5 on the branch that holds Story 1.3's latest status, or after it merges, so neither side overwrites the other.
- Steps 1 to 3 can run in any order. Step 4 follows them (it quotes FR-81 and AD-27). Step 5 follows step 4.

**Success criteria**

- Every planning artifact describes the same Supplier onboarding: PRD FR-81, AD-27, `EXPERIENCE.md` and Epic 2 Stories 2.2, 2.6, 2.9 and 2.20.
- `sprint-status.yaml` lists the renumbered Epic 2, and Epic 1 statuses are unchanged.
- No code, branch or Epic 1 story changed as a result of this proposal.

## 6. Approval

Approved by ialim on 2 November 2026: proposals P1 to P7 in incremental review, with no amendments.

## 7. Amendment, 3 November 2026: loading a Supplier's product list

**Trigger.** The owner asked whether onboarding includes the Supplier's products. It did not. Products were linked only through Supplier Costs entered per Product (Story 2.3) or in a general bulk file (Story 2.17). The owner asked to add a Supplier-scoped product upload.

**Change (applied, no renumbering).**

- **Story 2.17** is retitled "Bulk upload with a dry run, and a Supplier's product list" (requirements now FR-15 and FR-81) and gains these criteria:
  - "Upload this supplier's products" is opened from the Supplier's page. The template has no Supplier column, and every cost in the file is that Supplier's.
  - An existing SKU gets that Supplier's cost added or updated, and its Active Supplier never changes. A new SKU becomes a draft Product.
  - A file for a Supplier that is not yet approved applies as drafts and costs, and none of them can go live until approval.
  - Once the Supplier is approved, Admin can use "Make this the Active Supplier for its [n] new drafts" in one audited action.
  - A stopped Supplier's file is refused.
  - The price-change thresholds still send a large file to Admin (Story 2.18).
  - An accessibility criterion is added.
- **PRD:** FR-15 gains the Supplier-scoped file, and FR-81 points to it.
- **`epics.md` inventory:** the FR-15 and FR-81 lines are updated the same way.
- **`EXPERIENCE.md`:** the Bulk upload and Suppliers rows describe the scoped upload and the "Make this the Active Supplier" action.

**Sprint status impact.** Story 2.17's key slug changes with its title. That is picked up by the pending `bmad-sprint-planning` rerun (step 5).

Approved by ialim on 3 November 2026.
