# Validation Report — integrator

- **DESIGN.md:** `_bmad-output/planning-artifacts/ux-designs/ux-integrator-2026-10-07/DESIGN.md`
- **EXPERIENCE.md:** `_bmad-output/planning-artifacts/ux-designs/ux-integrator-2026-10-07/EXPERIENCE.md`
- **Run at:** 2026-10-09T20:50

## Overall verdict

A downstream consumer can extract cleanly. All seven PRD journeys are named as the PRD names them with steps, a climax and a failure path; every colour token has a hex value; the 21 component names are identical in both spines and each has a token; every one of the 29 mockups is linked and named. The one real weakness was inheritance: EXPERIENCE.md carried no token references and restated pixel sizes and colour names that DESIGN.md owns. That is fixed. What remains is size, about 124 KB for EXPERIENCE.md, which a regulated, three-surface, 90-screen product justifies and which the structure and prose polish confirmed is mostly decision content, not repetition.

This was the author's own pass, run in the same session that wrote the spines, after the owner declined a separate reviewer subagent. It is the full rubric walk but it is not an independent review. The accessibility lens and the money, trust and who-sees-what lens were offered and not picked.

## Category verdicts

- Flow coverage — strong
- Token completeness — strong
- Component coverage — strong
- State coverage — adequate
- Visual reference coverage — strong
- Bloat & overspecification — adequate
- Inheritance discipline — adequate
- Shape fit — strong

## Findings by severity

### Critical (0)
None.

### High (2)
**[Bloat & overspecification]** — Pixel sizes and colour names owned by DESIGN.md restated in EXPERIENCE.md (EXPERIENCE.md, Component Patterns, Interaction Primitives, Accessibility Floor, Responsive & Platform, staff console)
Target sizes (44, 52, 56, 48, 46, 40, 34px), type sizes (12, 13, 15, 16px), layout widths (1100, 1280, 1024px) and 'brass' and 'red' were written out with no reference to the tokens, and the stepper had already drifted. Fixed in the spines before this report.
Fix: Replaced with 30 token references (20 distinct); the remaining raw sizes are layout breakpoints, which are not tokens.

**[Inheritance discipline]** — EXPERIENCE.md had no {path.to.token} references (EXPERIENCE.md, whole file)
'References resolve' held only because there were none. Fixed in the spines before this report.
Fix: See Bloat & overspecification.

### Medium (4)
**[Component coverage]** — Quantity stepper size stated two ways (EXPERIENCE.md Component Patterns; DESIGN.md Components)
EXPERIENCE.md said minus and plus are 46px; DESIGN.md has a 46px box with 44px buttons. Fixed in the spines before this report.
Fix: Both now refer to the target-min token.

**[State coverage]** — Console tools had no per-surface state row (EXPERIENCE.md, State Patterns)
Platform Rate, Bank statements, Support desk, Bulk upload, Guide editor, Approvals and Dashboard had states only in the Essentials column. Fixed in the spines before this report.
Fix: Added one row per tool (seven rows) using only states confirmed in Steps 13 to 18.

**[Bloat & overspecification]** — EXPERIENCE.md is about 124 KB (EXPERIENCE.md, whole file)
A consumer must load all of it. Most is decision content that is not in the PRD, but some screen detail repeats between the 'by place' tables, the Key Flows and the mockup descriptions.
Fix: The structure and prose polish pass found little true repetition. It merged the two tone and message tables, moved the banned list into Inspiration & Anti-patterns, dropped three rows that repeated other sections, and split overlong table cells; the net change is +69 words because it also added a short 'Where to look' paragraph. The size stands: it is decision content.

**[Inheritance discipline]** — The same state was called 'brass' in one spine and 'caution' in the other (EXPERIENCE.md; DESIGN.md Colors)
For example the console rail counts 'turn brass' against 'turn caution'. Fixed in the spines before this report.
Fix: DESIGN.md Colors says brass is short for the needs-a-person pair and red for the error pair; State Patterns defines both with their tokens.

### Low (12)
**[Flow coverage]** — UJ-1 left out the PRD's delivery-on-its-own-line edge case (EXPERIENCE.md, Key Flows, UJ-1 step 5)
The PRD says the difference since the Proposal is split into exchange rate, tier and item changes with delivery on its own line, and Tunde decides whether to pass it to his client or absorb it. Fixed in the spines before this report.
Fix: Added to step 5.

**[Flow coverage]** — UJ-2 left out the optional business address (EXPERIENCE.md, Key Flows, UJ-2 step 3)
The PRD path includes an optional business address. Fixed in the spines before this report.
Fix: Added to step 3.

**[Token completeness]** — 'Ten colours do all the work' against 19 colour tokens (DESIGN.md, Colors)
The count in the prose did not match the frontmatter. Fixed in the spines before this report.
Fix: Reworded to 'a short list of colours'.

**[Token completeness]** — 'Avoid green for success' against the good green (DESIGN.md, Colors)
Good green is used for a passed check and for stock. Fixed in the spines before this report.
Fix: Now 'green for anything but a passed check or stock'.

**[Token completeness]** — Two component tokens carry raw heights (DESIGN.md frontmatter (quantity-stepper, meter))
46px and 12px are not named spacing tokens. Each is used once.
Fix: None needed.

**[Component coverage]** — 'Each of the sixteen core components' against 21 named (DESIGN.md, Components, last line)
The patterns sheet draws sixteen of the 21. Fixed in the spines before this report.
Fix: Says which sixteen and links where the other five are drawn, in both spines.

**[State coverage]** — Text pages and the Help sheet have no state rows (EXPERIENCE.md, The app by place)
Marked spine only; 'service is down' is a generic state and the rest have none that differ.
Fix: None needed.

**[Visual reference coverage]** — The Mockups index repeats the inline links (EXPERIENCE.md, Mockups)
It is the one place that says what each sheet shows, so it was kept.
Fix: None.

**[Bloat & overspecification]** — The three layout sizes are given in both spines (DESIGN.md Layout & Spacing; EXPERIENCE.md Responsive & Platform)
DESIGN.md owns the numbers.
Fix: Keep them in step.

**[Inheritance discipline]** — 'Never dollars' against the rate shown where a change is explained (EXPERIENCE.md, Voice and Tone, Formats)
Money and numbers allows '₦1,368 to the dollar' where a price change is explained. Fixed in the spines before this report.
Fix: Now 'Never a price in dollars'.

**[Inheritance discipline]** — Three oddly worded lines in the Orders tables (EXPERIENCE.md, The app by place, Orders)
'The one tinted state', a cryptic list of timer values, and 'the 48-hour cancel'. Fixed in the spines before this report.
Fix: Reworded.

**[Shape fit]** — DESIGN.md had no status or updated key (DESIGN.md frontmatter)
The skill uses status to tell a finished run from one in progress. Fixed in the spines before this report.
Fix: Added; set to final at the end of Finalize.

## Mechanical notes

- A script run checks frontmatter, token references, relative links, mockup links, component names, journey titles, section order and the single spines-win statement. It passes.
- The Offline table did not say whether the Parts list PDF works offline; the PRD is silent and the decision record leaves it to architecture. It now says it needs a connection for now.
- Numbers checked against the PRD by hand: 3 Unconfirmed uploads (FR-41), 48-hour issue code paused up to 7 days (FR-68), 15% restocking fee (FR-61), 24-hour corrected receipt (FR-38), cancel rules (FR-57). No contradictions found.
- Not done: no independent read of the 29 sheets against the spines beyond about a dozen spot checks; no accessibility or adversarial lens.
- 18 entries (the missing token references appear under two categories): 0 critical, 2 high, 4 medium, 12 low. 13 fixed before this report, 5 left as they are or for the polish pass.

## Reviewer files

- `review-rubric.md`
