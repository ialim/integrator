---
title: Reconciliation of UX change requests against the PRD
checked: 2026-10-09
input: ../../ux-designs/ux-integrator-2026-10-07/prd-change-requests.md
target: prd.md (working-tree diff against dbb8b28)
method: independent check; every item read against the diff and against the unchanged PRD text around it
---

# Reconciliation: UX change requests vs PRD

**Verdict.** Nearly everything landed faithfully. All 13 Open items are in §8.3 (Q6 to Q18) or are closed in an FR. The real problems are a few older sentences that now contradict the new paragraphs: FR-4's in-app alternative verification, FR-37's "never rises after payment" against FR-39's paid split shipment, FR-7's re-confirmation actor, and FR-28's cache purge against the held queued edits. The "Waiting on others" counsel items, including the voice and tone wording, did not reach §8.2.

Status key: **Landed** = stated faithfully. **Landed (pre-existing)** = the PRD already said it, so no new text was needed. **Partial** = present but with a detail missing or softened. **Contradicted** = new text is present, but older PRD text elsewhere says otherwise. **Missing** = not present.

## 1. Item table

| # | Item (tag) | Where in PRD | Status | Note |
|---|---|---|---|---|
| 1 | FR-1 caps and lockout (Open) | §8.3 Q6 | Landed | |
| 2 | FR-4 retries exhausted to manual review; 3 rejections then call/WhatsApp (Filled) | FR-4 UX para | Landed | |
| 3 | FR-4 optional NIN slip photo; no-NIN handled by Admin phone check, no in-app route (Added) | FR-4 UX para | **Contradicted** | The older FR-4 sentence still says "An Owner without a NIN can ask for Admin-reviewed alternative verification (a passport or residence permit plus … CAC)". The photo step is also placed "among the Level 3 steps", but NIN is Level 2 (FR-3), and the UX calls the flow "Get approved". |
| 4 | FR-6/7 re-request after rejection (Open) | §8.3 Q7 | Landed | |
| 5 | FR-6/7 voucher suspension: applicant re-confirms with an explicit tick | FR-7 base text | **Contradicted** | FR-7 says "flagged for explicit **Admin** re-confirmation"; the UX has the **applicant** tick. Not in Q7 either. |
| 6 | FR-8 Create a business screen, Pending Activation list, platform vouch codes for a named prospect, creator segregation, four steps with a different opening line (Added) | FR-8 UX para; FR-52 segregation | Landed | "Four Level 3 steps": the same labelling issue as #3. |
| 7 | FR-69 one Terms document, key points at Level 3, what-changed list, preview, counsel date/ref, interrupts Owner only, blocks only Order creation (Added) | FR-69 UX para | Landed | The base text says "Terms and Privacy Notice", and the acceptance point is "Level 3 approval" (the UX says "request"; FR-6 says request). Low. |
| 8 | FR-71 recovery: phone ID check, 72 h from the check passing, auto-open, all contacts told; no Manager/Tech recovery (Filled) | FR-71 UX para | Landed | |
| 9 | FR-71 recovery queue/target (Open) | §8.3 Q8 | Landed | |
| 10 | FR-71 code per action, 10 min, passkey/WhatsApp/invite Manager/apply CN; sessions list; 30-day idle logout (Added) | FR-71 UX para | Landed | Consistent with the base "30 days idle". |
| 11 | FR-72 neutral refusal; Finance hold ratified in 1 BD in Approvals; revoke returns to Get approved, prices hidden, orders processed; blocklisted payment in Unmatched marked blocked (Filled) | FR-72 UX para | Landed | "Neutral refusal" kept. |
| 12 | FR-72 credit-only balance blocks closing up to 12 months; "call us" exception (Open) | §8.3 Q9 | Landed | |
| 13 | FR-25 Room delete, confirm, 30-day recovery (Added) | FR-25 UX para | Landed | |
| 14 | FR-26/27 Technician keeps lead time and warnings, no back-order discount (Changed) | FR-27 rewritten + consequence | Landed | Consistent with the FR-11 table row 2, FR-30 consequence, and FR-26. |
| 15 | FR-26 fractional units (Open) | §8.3 Q10 | Landed | |
| 16 | FR-28 check-in definition, day 14 lock, day 11 brass line, day 13 banner, nothing deleted, logout keeps changes locked, Technician sees an unnamed later change, offline prices >1 day brass, no Orders/Payments/CNs on phone (Filled) | FR-28 UX para; FR-27 (brass) | **Partial / tension** | "Brass line on day 11" became "warning line" (fine). The base FR-28 still says the cache "refuses to open after 14 days" (the UX says Projects lock and nothing is deleted) and "is purged at the next connection after a User is removed or changes role", while the new text holds that User's queued edits for an Owner or Manager. Order of operations is unstated. "in v1" was dropped from "not kept on the phone". |
| 17 | FR-28 Parts list PDF needs a connection (Open, closed by architecture) | FR-28 UX para | Landed | |
| 18 | FR-29/30 custom lines VAT-included, only client name required, no platform branding, plain-words validity sentence that adjusts >₦5, VAT line only for VAT-registered (Added) | FR-29 and FR-30 UX paras | Landed | The base FR-30 still quotes the old validity sentence verbatim; acceptable as the meaning, but the "plain words" wording now lives in EXPERIENCE.md. Low. |
| 19 | FR-35 margin at today's cost (Added) | FR-35 UX para | Landed | |
| 20 | FR-70 VAT-registered without TIN, with prompt (Filled) | FR-70 UX para | Landed | |
| 21 | FR-22 projection from spend so far; thresholds move with the rate (Filled) | FR-22 UX para | Landed | |
| 22 | FR-48 guide editor, removal reason, placeholders, withdrawn manual "Open anyway" (Added) | FR-48 UX para | Landed | |
| 23 | FR-50 server records version per phone (Open, architecture) | §8.3 Q11 | Landed | Kept as open rather than closed; the request says it waits on architecture, so this is acceptable. |
| 24 | FR-33/34 optional Cart order name / "Direct buy" + count; 2 unpaid per Project; unorderable lines at Review or Cart top (Filled) | FR-33 and FR-34 UX paras; FR-34 base | Landed | "Per Project" pre-existing. |
| 25 | FR-20 one message hiding the cause; calendar 48 h; Saturday/Monday warning; 36/44 h placeholders (Filled) | FR-20 UX para | Landed | "Saturday and Monday warning" became "warns before a weekend"; close enough. |
| 26 | FR-37 delivery price requests: ~1 BD, staff-only courier quote, VAT-incl price valid to end of next BD, reason if refused, never up after payment, actual charge for reporting, placeholders (Added) | FR-37 UX para | **Landed, but contradicts FR-39** | See #27. |
| 27 | FR-39 business pays extra delivery at the Zone Rate in the 14-day offer; absorbing is the alternative (Filled) | FR-39 UX para | **Partial / contradicted** | "Absorbing it is the alternative" dropped. The new FR-37 text says "A delivery price never rises after payment", and the base FR-37 says extra courier cost after payment is absorbed. FR-39 now charges more after payment. Needs a carve-out for an Integrator-chosen split. |
| 28 | FR-45 uncollected: remind 7, Uncollected 14, cancel and restock 30 with credit-only CN less fee (Filled) | FR-45 base text; decision 9 | Landed (pre-existing) | The base says Operations "may cancel" after 30; the UX says it is cancelled. Low. |
| 29 | FR-45 receiver one-time code has no generator/message (Open) | §8.3 Q12 | Landed | |
| 30 | FR-57 auto CN for confirmed payments; unchecked receipts to Finance; consent for under-review; reason optional before Approval, required after (Filled) | FR-57 UX para | Landed | |
| 31 | FR-75 change request answered within 4 business hours (Filled) | FR-75 UX para | Landed | |
| 32 | FR-38/55 receipt time (Open, closed by architecture) | FR-38 UX para | Landed | FR-55's resume consequence agrees. |
| 33 | FR-19 phone adds in whole kobo, matches server and test vectors (Open, closed by architecture) | FR-19 UX para | Landed | Stated as display-only. |
| 34 | FR-41 statement age/coverage; credit-before-receipt written check; 3rd Unconfirmed to Admin "Needs review"; distinct second reviewer; daily unmatched look; unknown sender held (Filled) | FR-41 UX para | Landed | The base says "a staff review is required"; the new text names Admin. Consistent. |
| 35 | FR-43 whole balance only, recorded sending account, fresh code, bank portal then reference before "Mark as refunded", banner not status, rejection reason, judged as at request (Filled) | FR-43 UX para + base Refunding + consequence | Landed | |
| 36 | FR-78 owner + deputy, soft lock and takeover reason, 75% escalation notice (Added) | FR-78 UX para | Landed | |
| 37 | FR-78 missing queues; FR-66 counterfeit clock card (Open) | §8.3 Q8 | Landed | |
| 38 | FR-52 one Approvals page, decline reason, no self-approval, VAT rate second approver, ₦100,000 placeholder and Admin setting, know creator/approver/voucher, written reason when blocked (Added) | FR-52 UX para | Partial (minor) | The FR-52 segregation bullet list of second-approver items was not updated with the VAT rate change or the >10% Back-order Discount (FR-17); they live only in the paragraph. |
| 39 | FR-52/53 founder is an Admin with founder flag; weekly review list; sign-off blocked until each is reviewed (Added) | FR-52 and FR-53 UX paras | Landed | |
| 40 | FR-53 NIN read reason codes; identifiers not names; "Look up" Admin only (Filled) | FR-53 UX para | Landed | |
| 41 | FR-53 unusual-pattern baseline (Open) | §8.3 Q13 | Landed | |
| 42 | FR-71 staff two-step method (Open) | §8.3 Q14 | Landed | |
| 43 | FR-76 closed-day calendar pauses clocks; app reads Business Hours (Added) | FR-76 UX para | Landed | |
| 44 | FR-80 Reports and exports page; plan start date; SM-6 cost inputs (Open) | §8.3 Q15 | Partial | The "Finance exports sit on a Reports and exports page" part is not mentioned. |
| 45 | FR-68 Issue Code reaches the Integrator in the Case; Technician pause up to 7 days (Filled) | FR-68 UX para | Landed | |
| 46 | FR-79 "Give Support a code", 6 digits, console field, callback; Support desk is a console area (Added) | FR-79 UX para | Landed | |
| 47 | FR-58 Owners/Managers see all Cases; Technician own (Filled) | FR-58 UX para | Landed | The FR-11 role table has no Cases row. Low. |
| 48 | FR-59 never blocks a report (Filled) | FR-59 UX para | Landed | |
| 49 | FR-60 "Held" (Changed) | FR-60 consequence | Landed | |
| 50 | FR-62 courier receipt photo (Filled) | FR-62 UX para | Landed | |
| 51 | FR-64 "Warranty until"; Problems segment (Added) | FR-64 UX para | Landed | |
| 52 | FR-44/18 shelf check per line; Approve off until each in-stock line is found or credited (Filled) | FR-44 UX para; FR-18 base | Landed | |
| 53 | FR-17/74 no Supplier order entity (Open) | §8.3 Q16 | Landed | |
| 54 | §8.3 Q5 far-zone window (Open) | §8.3 Q5 | Landed (pre-existing) | |
| 55 | FR-12 range NCC/SONCAP flag; Yes/No returnability without default; serial format (Filled) | FR-12 UX para | Landed | |
| 56 | FR-16 safety-stock release is a ledger line with reason; sign-off size an Admin setting (Filled) | FR-16 UX para | Landed | |
| 57 | FR-74 USD or NGN landed cost; serials at goods-in, verified at dispatch; Suspected per batch or product (Filled) | FR-74 UX para | Landed | |
| 58 | FR-15 >20 products or >15% cost waits as a whole file; one-click rollback (Filled) | FR-15 UX para | Landed (minor tension) | The base still says "Valid rows are applied", with an assumption to match; state that a gated file applies none of its rows until approval. Low. |
| 59 | FR-17 back-order discount >10% to Approvals (Filled) | FR-17 and FR-23 base ("needs Admin approval") | Landed (pre-existing) | The routing through the Approvals page is implicit only (see #38). |
| 60 | FR-14 Approve off until terms recorded; "Approve with a reason"; shorter DOA window; stopping a supplier with a preview and replacements (Filled) | FR-14 UX para | Landed | |
| 61 | FR-47/56 zone checks, LGA list, placeholder rates (Open) | §8.3 Q17 | Landed | |
| 62 | Product Ranges lifecycle (Open) | §8.3 Q18 | Landed | |
| 63 | FR-51 matrix proposal; immediate vs 9 am; recall stays until "I have seen this"; no inbox, no push in v1 (Filled) | FR-51 UX para | Landed | "12 launch events" not mentioned. Low. |
| 64 | Waiting on Counsel: nine Voice and Tone wording items; §8.2 items; use/cookies/analytics measurement | §8.2 Counsel | **Missing** (partial) | §8.2 covers the Credit Note ledger, returns and data protection. It does not cover the nine wording items in EXPERIENCE.md Voice and Tone, or analytics and cookies. |
| 65 | Waiting on Accountant: FR-77 layouts, VAT on delivery and CNs, e-invoicing | §8.2 Accountant | Landed (pre-existing) | "Tax-invoice fields and numbering" is close enough to layouts. |
| 66 | Waiting on Architecture items | FR-19, FR-28, FR-38; §8.3 Q11 | Landed | |
| 67 | Waiting on Owner: placeholders in EXPERIENCE.md Open items | Scattered "placeholder" mentions | Partial | No single pointer from the PRD to the EXPERIENCE.md placeholder list. Low. |

**Counts (67 rows, each counted once under its worst status):** Landed 59 (5 of them pre-existing), Partial 4 (#16, #38, #44, #67), Contradicted 3 (#3, #5, #27), Missing 1 (#64). Rows #26 and #58 landed but carry a tension, recorded under F2 and F8.

## 2. Qualitative content

- **Neutral refusal**: kept (FR-72).
- **Plain words**: kept for the validity sentence (FR-30) and the Terms what-changed list (FR-69).
- **Tone and voice**: no PRD pointer to EXPERIENCE.md Voice and Tone, and its nine counsel wording items are absent from §8.2 (finding F5).
- **Brass** colour cue: FR-27 keeps it. FR-28 renders the day-11 line as "warning line", which is acceptable for a PRD.

## 3. Findings

| ID | Severity | Location | Finding | Fix |
|---|---|---|---|---|
| F1 | High | FR-4 base text (line ~224) | The old sentence "An Owner without a NIN can ask for Admin-reviewed alternative verification (a passport or residence permit plus the business's CAC registration number)" sits beside the new "A person without a NIN has no in-app route; Admin records a phone or WhatsApp check." A builder could read the old sentence as an in-app flow, which the owner removed. | Rewrite the old sentence: "An Owner without a NIN contacts the platform; Admin verifies by phone or WhatsApp (passport or residence permit plus CAC number) and records the check. There is no in-app route." |
| F2 | High | FR-37 UX para and base vs FR-39 UX para | FR-37 now says "A delivery price never rises after payment", and the base says extra courier cost after payment is absorbed. FR-39 has the business pay extra delivery at the Zone Rate, before dispatch, after payment. The UX's "absorbing it is the alternative" was dropped. | Add to FR-37: "except a Shipment the Integrator chooses to add through the FR-39 send-what-is-ready offer". Restore in FR-39: "or the platform absorbs it" (or state who chooses). |
| F3 | Medium | FR-7 base text | "If the voucher is suspended before the decision, the request is flagged for explicit Admin re-confirmation." The UX (Step 15) has the applicant re-confirm with an explicit tick. Either both are needed or the actor changed. §8.3 Q7 does not mention it. | Decide, then state both if both apply ("the applicant re-confirms with an explicit tick and Admin re-confirms"), or add this to Q7. |
| F4 | Medium | FR-28 base vs FR-28 UX para | The base says the cache "refuses to open after 14 days" and "is purged at the next connection after a User is removed or changes role". The new text says Projects lock (nothing deleted, waiting changes kept), and that a removed User's queued edits are held for an Owner or Manager. Purge-then-hold ordering is undefined. | Reword the base: "locks (FR-28 below) after 14 days"; "on the next connection, queued edits are uploaded and held (below) before the cache is purged". |
| F5 | Medium | §8.2 Counsel | Missing: the nine Voice and Tone wording items in EXPERIENCE.md, and what is measured about use, cookies and analytics. | Add both to the §8.2 Counsel list, with a pointer to EXPERIENCE.md. |
| F6 | Low | FR-4, FR-8, FR-72 UX paras | "Level 3 steps" names the UX's "Get approved" flow. NIN verification is Level 2, so "an optional photo step among the Level 3 steps" is mislabelled. | Use "the Get approved steps (contact, NIN, Trade Name, vouch and Terms)" or define it once in the glossary. |
| F7 | Low | FR-52 segregation bullets | The second-approver list omits the VAT rate change and the >10% Back-order Discount; both are stated only in prose (FR-52 UX para, FR-17). The new paragraph also drops "placeholder" for ₦100,000; the bullet still has it. | Add both to the bullet list. |
| F8 | Low | FR-15 base | "Valid rows are applied" sits next to "waits for Admin approval as a whole file". | Add: "for a file over the threshold, no row applies until Admin approves". |
| F9 | Low | FR-45 base | "Operations **may** cancel and restock after 30 days"; the UX has it cancelled after 30. | Decide whether it is automatic or an Operations action; reword or leave. |
| F10 | Low | §8.3 Q15 | Omits "Finance exports sit on a Reports and exports page". | Add it to Q15. |
| F11 | Low | FR-11 role table | No row for Case visibility (FR-58: Owner and Manager see all; Technician sees own). | Add a row, or a cross-reference. |
| F12 | Low | FR-28 UX para | "Orders, Payments, and Credit Notes are not kept on the phone" lost "in v1". | Add "in v1" if it is a phase limit rather than a rule. |
| F13 | Low | FR-51 UX para | Does not say the matrix covers the 12 launch events. | Optional: "the 12 launch events". |
| F14 | Info | Header | "Status at this revision (7 October 2026)" is kept with a separate "Update (9 October 2026)" note; the count of 41 UX paragraphs matches the diff. | None. |
