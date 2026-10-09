# Spine Pair Review — integrator

> Run on 2026-10-09 by the author, in the same session that wrote the spines, after the owner declined a separate reviewer subagent. It is the full rubric walk (Pass 1 and Pass 2) but it is not an independent review. The accessibility lens and the money, trust and who-sees-what lens were offered and not picked. Findings marked **Fixed** were corrected in the spines before this report was written; the counts at the end say how many.

## Overall verdict

A downstream consumer can extract cleanly. All seven PRD journeys are named as the PRD names them with steps, a climax and a failure path; every colour token has a hex value; the 21 component names are identical in both spines and each has a token; every one of the 29 mockups is linked and named. The one real weakness was inheritance: `EXPERIENCE.md` carried no `{token}` references at all and restated pixel sizes and colour names that `DESIGN.md` owns, which is now fixed. What remains is size (about 124 KB), which is justified by a regulated, three-surface, 90-screen product; the structure and prose polish that followed found it to be mostly decision content, not repetition.

## 1. Flow coverage — strong

Checked the seven `UJ-n` titles in PRD section 2.3 against the headings in Key Flows (verbatim), then each flow for a named protagonist, numbered steps, a climax beat and a failure path. Also traced each PRD edge case into a flow.

### Findings
- **[low]** UJ-1 step 5 left out the PRD's edge case that the difference since the Proposal is split into exchange rate, tier and item changes with delivery on its own line, and the Owner decides whether to pass it on or absorb it (EXPERIENCE.md, Key Flows, UJ-1). *Fix:* added. **Fixed.**
- **[low]** UJ-2 step 3 omitted the optional business address in the PRD path (UJ-2). *Fix:* added. **Fixed.**

## 2. Token completeness — strong

Extracted 19 colours, 16 type roles, 3 radii, 20 spacing values and 30 component tokens. Every colour has a hex value, every `{...}` reference in both files resolves, and contrast is stated for 15 load-bearing pairs with the weakest text pair at 6.37 to 1.

### Findings
- **[low]** The Colors section said "Ten colours do all the work" while the frontmatter defines 19 colour tokens (DESIGN.md, Colors). *Fix:* reworded. **Fixed.**
- **[low]** "Avoid ... green for success" contradicted the `good` green used for a passed check and for stock (DESIGN.md, Colors). *Fix:* "green for anything but a passed check or stock". **Fixed.**
- **[low]** Two component tokens carry raw heights (`quantity-stepper` 46px, `meter` 12px) rather than named spacing tokens (DESIGN.md frontmatter). *Fix:* none needed; they are used once each.

## 3. Component coverage — strong

Extracted the names from DESIGN.md.Components (21) and EXPERIENCE.md.Component Patterns (21): the lists are identical and in the same order, and each name has at least one component token. Each Component Patterns row has behavioural rules, not a one-word description.

### Findings
- **[medium]** The Quantity stepper said minus and plus are 46px in EXPERIENCE.md while DESIGN.md has a 46px box with 44px buttons (EXPERIENCE.md, Component Patterns; DESIGN.md, Components). *Fix:* both now refer to `{spacing.target-min}`. **Fixed.**
- **[low]** DESIGN.md said the patterns sheet shows "each of the sixteen core components" while 21 are named (DESIGN.md, Components, last line). *Fix:* the sentence now says which sixteen the sheet draws and links where the other five (Meter, Amount, Card, Check list, Guide step) are drawn; EXPERIENCE.md says the same. **Fixed.**

## 4. State coverage — adequate

Walked the IA tables (app by place, console by role) against State Patterns: 16 generic states and a per-surface table of 16 rows (now 23).

### Findings
- **[medium]** Console tools other than queues and reviews had no row in the per-surface state table (Platform Rate, Bank statements, Support desk, Bulk upload, Guide editor, Approvals, Dashboard), so a consumer had to infer their states from the Essentials column (EXPERIENCE.md, State Patterns). *Fix:* added one row per tool (seven rows) using only states already confirmed in Steps 13 to 18. **Fixed.**
- **[low]** Pure text pages (terms, privacy, accessibility statement, install, signed out, service down, not found) and the Help sheet have no state rows; they are marked spine only (EXPERIENCE.md, The app by place, Before sign-in and approval). *Fix:* none; "service is down" is a generic state and the rest have none that differ.

## 5. Visual reference coverage — strong

Listed all 29 files under `mockups/system`, `mockups/app` and `mockups/console`. Each is linked inline at the section it illustrates and named in the Mockups index; "the spines win on any conflict with a mockup" is stated once, in the opening note of EXPERIENCE.md. No orphans. `imports/` is empty.

### Findings
- **[low]** The Mockups index at the end repeats the inline links. It is the one place that says what each sheet shows, so it was kept (EXPERIENCE.md, Mockups).

## 6. Bloat and overspecification — adequate

### Findings
- **[high]** EXPERIENCE.md restated pixel sizes and colour names that DESIGN.md owns as tokens (44, 52, 56, 48, 46, 40 and 34px targets; 12, 13, 15 and 16px type; 1100, 1280 and 1024px layout; "brass" and "red") with no reference to the tokens (EXPERIENCE.md, Component Patterns, Interaction Primitives, Accessibility Floor, Responsive & Platform, The staff console). Two numbers had already drifted (the stepper). *Fix:* replaced with 30 `{token}` references (20 distinct); remaining raw sizes are the layout breakpoints, which are not tokens. **Fixed.**
- **[medium]** EXPERIENCE.md is about 124 KB across 17 sections; DESIGN.md is about 29 KB. A consumer must load all of it. Most of it is decision content that is not in the PRD, but some of it repeats screen detail between the "by place" tables, the Key Flows and the mockup descriptions (EXPERIENCE.md, whole file). *Fix:* the structure and prose polish pass that followed found little true repetition. It merged the two tone and message tables, moved the banned list into Inspiration & Anti-patterns, dropped three rows that repeated other sections, and split overlong table cells; the net change is +69 words because it also added a short "Where to look" paragraph. The size stands: it is decision content.
- **[low]** The three layout sizes (599, 600 to 1023, 1024 and up) and their behaviour are given in both DESIGN.md (Layout & Spacing) and EXPERIENCE.md (Responsive & Platform). DESIGN.md owns the numbers; keep them in step (both files).

## 7. Inheritance discipline — adequate

`sources` resolves (all four files exist). Journey names are verbatim. Component names are identical in both files. The glossary words in Foundation match the PRD's; the Voice and Tone word list maps what a business reads to each PRD term.

### Findings
- **[high]** EXPERIENCE.md had no `{path.to.token}` references, so "references resolve" held only because there were none (EXPERIENCE.md, whole file). *Fix:* see category 6. **Fixed.**
- **[medium]** The same state was called "brass" in EXPERIENCE.md and "caution" in DESIGN.md (for example rail counts "turn brass" against "turn caution"). *Fix:* DESIGN.md Colors says *brass* is short for the needs-a-person pair and *red* for the error pair; State Patterns in EXPERIENCE.md defines both with their tokens. **Fixed.**
- **[low]** Voice and Tone said "Never dollars" for money shown to a business while Money and numbers allows the rate to appear as "₦1,368 to the dollar" where a change is explained (EXPERIENCE.md, Voice and Tone, Formats). *Fix:* "Never a price in dollars". **Fixed.**
- **[low]** Wording that needed no inheritance fix but read oddly: "the one tinted state" for a payment problem, a cryptic list of timer values in the order states, and "the 48-hour cancel" (EXPERIENCE.md, Orders). *Fix:* reworded. **Fixed.**

## 8. Shape fit — strong

DESIGN.md sections are in the canonical order with none out of place. EXPERIENCE.md has every default (Foundation, Information Architecture, Voice and Tone, Component Patterns, State Patterns, Interaction Primitives, Accessibility Floor, Key Flows) and both triggered sections (Inspiration & Anti-patterns because the record shows rejected references; Responsive & Platform because it is multi-surface). The invented sections earn their place for a regulated, multi-role, offline-capable product: Money and numbers, Time and clocks, Who sees what, Trust and verification, Offline and weak signal, Open items and assumptions, Mockups.

### Findings
- **[low]** DESIGN.md had no `status` or `updated` key, which the skill uses to tell a finished run from one in progress (DESIGN.md frontmatter). *Fix:* added; set to final at the end of Finalize. **Fixed.**

## Mechanical notes

- A script run checks frontmatter, token references, relative links, mockup links, component names, journey titles, section order and the single "spines win" statement. It passes.
- The Offline table did not say whether the Parts list PDF works offline; the PRD is silent and the decision record leaves it to architecture. The table now says it needs a connection for now (EXPERIENCE.md, Offline and weak signal). **Fixed.**
- Numbers checked against the PRD by hand: the 3 Unconfirmed uploads (FR-41), 48-hour issue code with a pause of up to 7 days (FR-68), 15% restocking fee (FR-61), 24-hour corrected receipt (FR-38), cancel rules (FR-57), a larger transfer making a draft credit note, the seven-day change of mind window. No contradictions found.
- Not done: no independent read of the 29 sheets against the spines beyond about a dozen spot checks; no accessibility or adversarial lens.
- Counts: 18 entries (the missing token references appear under two categories): 0 critical, 2 high, 4 medium, 12 low. 13 fixed before this report; 5 left as they are or for the polish pass.
