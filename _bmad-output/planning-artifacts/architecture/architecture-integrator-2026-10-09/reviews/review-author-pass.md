# Review gate: author's inline pass (2026-10-09)

Run inline, not by independent subagents, at the owner's standing preference. Labelled as the author's pass.

## Lint
lint_spine.py: 0 findings.

## Reconcile against inputs (PRD, addendum, EXPERIENCE)
- FR-28 per-field last-writer-wins, actor per op, Unavailable flagging, cache purge on role change, 14-day lock on device: were missing from AD-12. FIXED.
- FR-53 append-only, hash-chained audit with separate credentials: missing. FIXED as AD-21 (trigger always; separate role where host allows).
- NFR-6 iOS lacks Background Sync: manual and focus sync now explicit in AD-12. FIXED.
- AD-5 wrongly bound the 14-day check-in (device-side, not sweep). FIXED.

## Adversarial (two compliant units that still clash)
- Multi-root Actions could deadlock (Returns locks Credit Note then Order; Orders the reverse). FIXED: fixed lock order in AD-4.
- Order creation racing a Hold. FIXED: Order creation locks Business Account first.
- Nested cross-module Actions opening their own transactions. FIXED: join caller's transaction; events after outermost commit.
- Price snapshot stored in differing shapes. FIXED: versioned DTO defined only by Pricing.
- Invariants enforced only in code could diverge across modules. FIXED: DB-constraint convention.
- Sweep audit actor undefined. FIXED: `system` actor.

## Verification
- Versions checked against npm and Packagist on 2026-10-09; Postgres 17 and regions from Laravel Cloud pricing/FAQ.
- OPEN (low): TypeScript 7 (native compiler) is recent; confirm lint/tooling support at scaffold, fall back to 6.x if it lags.
- OPEN (low): whether Laravel Cloud Serverless Postgres allows a second role; AD-21 now works either way.

## Rubric
- Operational envelope covered (hosting, environments, alerting, PITR). OPEN (medium): no backup copy outside Laravel Cloud; NFR-5 RTO 4h during a provider or region outage, and 6-year financial retention, are not covered by 7-day PITR. Raised to the owner.
