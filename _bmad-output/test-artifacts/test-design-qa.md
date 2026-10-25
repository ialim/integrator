---
workflowStatus: 'completed'
totalSteps: 5
stepsCompleted: ['step-01-detect-mode', 'step-02-load-context', 'step-03-risk-and-testability', 'step-04-coverage-plan', 'step-05-generate-output']
lastStep: 'step-05-generate-output'
nextStep: ''
lastSaved: '2026-10-14'
workflowType: 'testarch-test-design'
inputDocuments:
  - _bmad-output/planning-artifacts/prds/prd-integrator-2026-09-27/prd.md
  - _bmad-output/planning-artifacts/prds/prd-integrator-2026-09-27/addendum.md
  - _bmad-output/planning-artifacts/architecture/architecture-integrator-2026-10-09/ARCHITECTURE-SPINE.md
  - _bmad-output/planning-artifacts/ux-designs/ux-integrator-2026-10-07/EXPERIENCE.md
  - _bmad-output/planning-artifacts/epics.md
---

# Test Design for QA: integrator v1

**Purpose:** What to test, at which level, in what order, and what the tests need before they can be written.

**Date:** 14 October 2026
**Author:** ialim, with the TEA test architect
**Status:** Draft
**Project:** integrator

**Related:** `test-design-architecture.md` holds the testability blockers and the full risk register.

---

## Executive Summary

**Scope:** All of integrator v1 (Epics 1 to 16): the API and its Actions, the staff console, the PWA including offline behaviour, generated documents and messages.

**Risk summary:**

- Total risks: 21 (11 high, 6 medium, 4 low).
- Categories with most high risks: DATA (money and sync integrity) and SEC (who may see what).

**Coverage summary:**

- P0: 31 scenarios (money, concurrency, visibility, identity, timers, the core journey).
- P1: 23 scenarios (journeys, queues, notifications, performance, accessibility).
- P2: 8 scenarios (offline edge cases, returns arithmetic, documents, provider sandboxes).
- P3: none.
- **Total:** 62 scenarios, about 160 to 260 hours including infrastructure (about 4 to 6.5 weeks for one person).

---

## Not in Scope

| Item | Reasoning | Mitigation |
|---|---|---|
| **Real provider behaviour in CI** (NIN, SMS, WhatsApp, scanner) | Costs money, rate-limited, and non-deterministic | Fakes with fault injection in CI; scheduled sandbox contract tests (S-59) |
| **iOS storage eviction** | Cannot be forced reliably by automation | Manual check before launch; automated check of the warning (S-39) |
| **Counsel and accountant wording** | Legal text, not behaviour | Placeholders are marked; counsel sign-off outside testing |
| **Bank portal transfers for refunds** | The platform moves no money (Story 8.17) | Test only that "Mark as refunded" needs a bank reference |
| **Courier tracking** | Updated by hand in v1 (FR-45) | Test the status transitions and evidence only |

---

## Dependencies and Test Blockers

### Builder dependencies (before integration tests)

1. **PostgreSQL 17 as the only test database (TC-1, R-11)** - builder - Story 1.1
   - Needed for locking, constraints, triggers and the sync snapshot guard.
   - Without it, every concurrency and integrity test is meaningless.
2. **Injectable clock and a sweep-tick command (TC-2)** - builder - Story 1.1
   - Needed for every deadline rule (24 and 48 hours, 7, 14 and 30 days, 12 months, Business Hours).
   - Without it, timer tests must wait in real time or cannot run.
3. **Fault-injecting provider fakes (TC-3)** - builder - with each interface
   - Needed for NIN retry, WhatsApp fallback, scan failure and SMS failure.
4. **Test-only seeding (TC-4)** - builder - Story 1.1, grown per epic
   - Needed to start E2E journeys at Level 3 with stock, a fresh rate and Paid Orders.

### Test infrastructure setup

1. **Factories**
   - Laravel model factories that create state through Actions (Business Account at each Access Level, Product with stock, Order in each status).
   - Unique data per test; each test cleans up through transactions or its own seeded tenant.
2. **Environments**
   - Local: Laravel with PostgreSQL 17 in a container; Vite dev server; Playwright against the local app.
   - CI: the same PostgreSQL image; parallel Pest workers; Playwright sharded.
   - Staging: the full stack with fakes and synthetic data only (AD-20); used for nightly E2E, k6 and the restore drill.

**Example pattern (API level):**

```typescript
import { test } from '@seontechnologies/playwright-utils/api-request/fixtures';
import { expect } from '@playwright/test';
import { faker } from '@faker-js/faker';

test('@P0 @API a replayed Order creation returns the same Order', async ({ apiRequest }) => {
  const key = faker.string.uuid();
  const body = { quote_id: 'seeded-quote-id', idempotency_key: key };

  const first = await apiRequest({ method: 'POST', path: '/api/v1/orders', body, headers: { 'Idempotency-Key': key } });
  const second = await apiRequest({ method: 'POST', path: '/api/v1/orders', body, headers: { 'Idempotency-Key': key } });

  expect(first.status).toBe(201);
  expect(second.body.id).toBe(first.body.id);
});
```

---

## Risk Assessment

### High-priority risks (score 6 or more)

| Risk ID | Category | Description | Score | QA test coverage |
|---|---|---|---|---|
| **R-01** | DATA | Kobo drift between server, app, PDF and ledger | **6** | S-01 to S-06, S-47 |
| **R-02** | DATA | Racing transitions both succeed | **6** | S-08 to S-10, S-13, S-15, S-17, S-28, S-43, S-44, S-46, S-49, S-52 |
| **R-03** | SEC | Money reaches restricted sessions | **6** | S-20, S-21, S-24, S-30, S-48, S-60 |
| **R-04** | BUS | Price Lock boundaries decided wrongly | **6** | S-03, S-11, S-12, S-47, S-53 |
| **R-05** | DATA | Offline sync loses or mis-merges edits | **6** | S-34 to S-38 |
| **R-06** | SEC | Prices left on a device | **6** | S-23, S-50 |
| **R-07** | SEC | NIN leakage | **6** | S-25, S-26 |
| **R-08** | OPS | Missed or double-fired deadlines | **6** | S-07, S-12, S-31, S-33 |
| **R-09** | DATA | Bank credit used twice | **6** | S-14, S-51 |
| **R-10** | SEC | Proposal PDF leakage | **6** | S-22 |
| **R-11** | TECH | Wrong test database | **6** | S-08, S-35, S-41 |

### Medium and low-priority risks

| Risk ID | Category | Description | Score | QA test coverage |
|---|---|---|---|---|
| R-12 | SEC | Audit trail alterable | 4 | S-40 |
| R-13 | PERF | Catalogue list too slow on 3G | 4 | S-54, S-57 |
| R-14 | BUS | Business Hours arithmetic wrong | 4 | S-32, S-45 |
| R-15 | TECH | Fakes diverge from providers | 4 | S-27, S-59 |
| R-16 | BUS | Accessibility regressions | 4 | S-55, S-56 |
| R-17 | OPS | iOS evicts saved work | 4 | S-39 |
| R-18 | OPS | Restore misses RPO or RTO | 3 | S-58 |
| R-19 | SEC | Segregation bypassed | 3 | S-16, S-21, S-42 |
| R-20 | SEC | Step-up code reused | 3 | S-18, S-29 |
| R-21 | DATA | Document number gaps | 3 | S-19, S-61 |

---

## NFR Test Coverage Plan

| NFR category | Requirement or threshold | Planned validation | Tool and level | Evidence artifact | Priority |
|---|---|---|---|---|---|
| Security | Server-side roles and Access Levels; staff MFA; NIN never logged; step-up per action | S-18, S-20 to S-22, S-25, S-26, S-29, S-30, S-42 | Pest integration; dependency audits | CI reports; Composer and npm audit output | P0 |
| Performance | Catalogue list about 5 s on slow 3G; three budgets by measurement | S-54, S-57 | Playwright throttled; k6 | Timing report; k6 summary JSON | P1 |
| Reliability | Atomic, idempotent transitions; upload resume; WhatsApp fallback in 15 minutes | S-08 to S-13, S-31, S-33, S-53, S-60 | Pest integration; Playwright | Concurrency and replay reports; fault-injection results | P0 |
| Scalability | 500 Integrators, 5,000 Products, 500 Orders a month | S-57 | k6 on staging | k6 summary | P1 |
| Recovery | RPO 1 h, RTO 4 h | S-58 | Drill | Drill record | P1 |
| Maintainability | AD-1, AD-2 and the foreign-key rule | S-41 | Architecture tests | CI report; coverage report | P0 |
| Compliance | VAT reconciles; NDPA handling | S-25, S-26, S-61 | Pest integration | CI reports; breach runbook | P0 to P1 |
| Accessibility | WCAG 2.2 AA on screens and PDFs | S-55, S-56 | axe-core; PDF/UA checker | axe and PDF/UA reports | P1 to P2 |

**Missing thresholds:** API latency P95/P99, peak concurrency and a coverage threshold are UNKNOWN. Until they are set, performance results are baselines, not passes.

---

## Entry Criteria

- [ ] Requirements and assumptions agreed (epics.md approved on 14 October 2026)
- [ ] Test blockers TC-1 to TC-4 in place
- [ ] Test environments provisioned (local, CI, staging)
- [ ] Factories and seeding available for the epic under test
- [ ] The epic's stories deployed to staging for E2E
- [ ] Provider fakes for the epic support the failure modes its stories specify

## Exit Criteria

- [ ] All P0 scenarios passing
- [ ] P1 scenarios at least 95% passing, with any failure triaged and accepted
- [ ] No open high-severity defect
- [ ] High risks R-01 to R-11 mitigated, with evidence linked
- [ ] No new axe violation
- [ ] Coverage of at least 80% of lines in Pricing, Orders and CreditNotes
- [ ] Performance baselines recorded (Story 15.5)

---

## Test Coverage Plan

P0 to P3 express priority and risk, not when the tests run. Test levels: **Unit** (Pest, Vitest), **Integration** (Action and API tests on PostgreSQL), **Component** (Livewire or React), **E2E** (Playwright).

### P0 (Critical)

| Test ID | Requirement | Test level | Risk link | Notes |
|---|---|---|---|---|
| **P0-001** | Addendum price vectors (plain, discounted, rounding once, quantity 1,000) (S-01) | Unit | R-01 | FR-19 |
| **P0-002** | Proposal VAT line vector; identical totals (S-02) | Unit | R-01 | FR-30 |
| **P0-003** | Repricing vector; only the rate changes (S-03) | Unit | R-01, R-04 | FR-38 |
| **P0-004** | App money module reproduces the server vectors (S-05) | Unit | R-01 | Vitest |
| **P0-005** | Discount rules and margin floor (S-06) | Unit | R-01 | FR-13, FR-23 |
| **P0-006** | Platform Rate guards and 48-hour stop (S-07) | Integration | R-08 | FR-20 |
| **P0-007** | Two-racer and replay tests for every NFR-10 transition (S-08) | Integration | R-02, R-11 | Parallel workers |
| **P0-008** | Last unit reserved once, loser credited (S-09) | Integration | R-02 | Edge row 23 |
| **P0-009** | Idempotency replay and 422 on a changed payload (S-10) | Integration | R-02 | API |
| **P0-010** | Price Lock decision table (S-11) | Integration | R-04 | Edge rows 42 to 46 |
| **P0-011** | Expiry rules and 7-day cap (S-12) | Integration | R-04, R-08 | FR-38 |
| **P0-012** | Order Quote mismatch returns 409 (S-13) | Integration | R-02 | Edge row 41 |
| **P0-013** | Statement re-import, allocation cap, reference used once (S-14) | Integration | R-09 | Database level |
| **P0-014** | Confirmation to Paid with reservation and shortfall credit (S-15) | Integration | R-02 | FR-18, FR-42 |
| **P0-015** | Credit Note ledger balance and concurrent holds (S-17) | Integration | R-02 | Edge rows 57, 58 |
| **P0-016** | Refund restrictions (S-18) | Integration | R-20 | FR-43 |
| **P0-017** | Registry suite on every outbound surface (S-20) | Integration | R-03 | Grows each epic |
| **P0-018** | Role matrices enforced on the server (S-21) | Integration | R-03, R-19 | FR-11, FR-52 |
| **P0-019** | Proposal PDF forbidden-field test (S-22) | Integration | R-10 | Planted values |
| **P0-020** | Technician device store holds no price; purge on demotion (S-23) | E2E | R-06 | IndexedDB inspection |
| **P0-021** | Canary NIN absent from logs, queues, outbox, exports (S-25) | Integration | R-07 | NFR-3 |
| **P0-022** | Crypto-shred defeats a restored older backup (S-26) | Integration | R-07 | AD-16 |
| **P0-023** | Step-up grant single use, single action (S-29) | Integration | R-20 | AD-18 |
| **P0-024** | Level 3 only by approval or grant (S-30) | Integration | R-03 | FR-7 |
| **P0-025** | Every timer before, at and after its deadline, run twice (S-31) | Integration | R-08 | AD-5 |
| **P0-026** | Merge specification vectors in both languages (S-34) | Unit | R-05 | AD-12 |
| **P0-027** | Pull never skips an out-of-order commit (S-35) | Integration | R-05, R-11 | PostgreSQL snapshot |
| **P0-028** | Architecture tests; CI fails on a non-PostgreSQL test database (S-41) | Unit | R-11 | Static checks |
| **P0-029** | Own-request approval and segregation refused (S-42) | Integration | R-19 | AD-23 |
| **P0-030** | UJ-1 end to end (S-47) | E2E | R-01, R-04 | Release smoke test |
| **P0-031** | Receipt upload resumes and keeps its arrival time (S-53) | E2E | R-04 | FR-55 |

**Total P0:** 31.

### P1 (High)

| Test ID | Requirement | Test level | Risk link | Notes |
|---|---|---|---|---|
| **P1-001** | Property tests over generated carts (S-04) | Unit | R-01 | |
| **P1-002** | Payment reversal with a second person (S-16) | Integration | R-19 | FR-41 |
| **P1-003** | Contiguous invoice and credit-note numbers (S-19) | Integration | R-21 | AD-9 |
| **P1-004** | Price endpoints rate-limited; no bulk listing (S-24) | Integration | R-03 | FR-73 |
| **P1-005** | NIN check outcomes with fault-injecting fake (S-27) | Integration | R-15 | FR-4 |
| **P1-006** | Vouch Code limits and lock (S-28) | Integration | R-02 | FR-5 |
| **P1-007** | Business Hours table and recompute (S-32) | Unit | R-14 | FR-78 example |
| **P1-008** | Sweep heartbeat alert; one server (S-33) | Integration | R-08 | NFR-9 |
| **P1-009** | Two devices offline lose nothing (S-36) | E2E | R-05 | Two contexts |
| **P1-010** | Audit triggers and chain check (S-40) | Integration | R-12 | AD-21 |
| **P1-011** | Shipment transitions, evidence and serials (S-43) | Integration | R-02 | FR-45, FR-65 |
| **P1-012** | Completion rule and spend query (S-44) | Integration | R-02 | AD-25 |
| **P1-013** | Suspected Product blocks confirmation and shipment (S-46) | Integration | R-02 | FR-66 |
| **P1-014** | UJ-2 to prices appearing (S-48) | E2E | R-03 | |
| **P1-015** | UJ-3 Cart pick-up order (S-49) | E2E | R-02 | |
| **P1-016** | UJ-5 Technician guide offline (S-50) | E2E | R-06 | |
| **P1-017** | UJ-6 Finance confirms in the console (S-51) | E2E | R-09 | |
| **P1-018** | Catalogue list about 5 s on slow 3G (S-54) | E2E | R-13 | Throttled |
| **P1-019** | axe-core on every screen and state (S-55) | E2E | R-16 | |
| **P1-020** | k6 at design scale (S-57) | Integration | R-13 | Story 15.5 |
| **P1-021** | Restore drill meets RPO and RTO (S-58) | Integration | R-18 | Operational |
| **P1-022** | Notification visibility, opt-in and fallback (S-60) | Integration | R-03 | FR-51 |
| **P1-023** | Monthly VAT export reconciles to documents (S-61) | Integration | R-21 | FR-80 |

**Total P1:** 23.

### P2 (Medium)

| Test ID | Requirement | Test level | Risk link | Notes |
|---|---|---|---|---|
| **P2-001** | Tombstone resurrection and 30-day restore (S-37) | Integration | R-05 | |
| **P2-002** | 14-day lock from server time (S-38) | Unit | R-05 | Vitest |
| **P2-003** | Eviction warning when persistence refused (S-39) | Component | R-17 | |
| **P2-004** | Return-window arithmetic (S-45) | Unit | R-14 | Epic 16 |
| **P2-005** | UJ-7 return to Resolved (S-52) | E2E | R-02 | Epic 16 |
| **P2-006** | PDF/UA check on generated documents (S-56) | Integration | R-16 | |
| **P2-007** | Provider sandbox contract tests (S-59) | Integration | R-15 | Scheduled |
| **P2-008** | State-pattern checks per surface (UX-DR19) (S-62) | Component | R-16 | Grows with each screen |

**Total P2:** 8.

### P3 (Low)

None. Exploratory sessions before each release are planned but not scripted.

---

## Execution Strategy

**Philosophy:** run every functional test on every pull request while the run stays under 15 minutes; defer only what is expensive or long. Playwright sharding and parallel Pest workers keep hundreds of tests inside that budget. Within a run, the order is the UJ-1 smoke first, then P0, then P1, then P2, so the most important failure surfaces first.

### Every pull request: Pest, Vitest and Playwright (about 10 to 15 minutes)

- Pest unit and integration suites, including every concurrency, replay and timer test, in parallel workers against PostgreSQL.
- Architecture tests and the registry suite.
- Vitest for the app, including the shared vector files.
- Playwright, sharded: journeys, two-context offline runs, upload interruption, the provider fault matrix against fakes, and axe on every screen.

### Nightly: timing and document checks (about 30 to 60 minutes)

- Throttled slow-3G timing of the Catalogue list and the three measured budgets.
- PDF/UA checks over every generated document type.

### Weekly: load and providers

- k6 at design scale on staging.
- Provider sandbox contract tests.

### Quarterly and before release: manual and operational

- The restore drill (quarterly).
- Keyboard, TalkBack, VoiceOver and NVDA passes, and the iOS eviction check (before each release).

---

## QA Effort Estimate

| Priority | Count | Effort range | Notes |
|---|---|---|---|
| Test setup | n/a | ~25 to 40 hours | Factories, seeding, Playwright and Vitest configuration, shared vector files |
| P0 | 31 | ~70 to 110 hours | Concurrency, time and visibility suites are the heaviest |
| P1 | 23 | ~50 to 80 hours | Journeys, queues, performance, accessibility |
| P2 | 8 | ~15 to 30 hours | Edge cases and scheduled checks |
| P3 | 0 | ~0 hours | None planned; exploratory sessions only |
| **Total** | **62** | **~160 to 260 hours** | **~4 to 6.5 weeks** for one person, spread across the epics |

**Assumptions:** includes design, implementation, debugging and CI wiring; excludes ongoing maintenance (about 10%); assumes the builder delivers the blockers in Epic 1. Owner of every scenario is the builder unless the risk register names another.

---

## Implementation Planning Handoff

| Work item | Owner | Target | Notes |
|---|---|---|---|
| PostgreSQL-only test database and CI check | Builder | Story 1.1 | TC-1, R-11 |
| Injectable clock and sweep-tick command | Builder | Story 1.1, used from Story 2.17 | TC-2 |
| Fault-injecting fakes | Builder | With each provider interface | TC-3 |
| Test-only factories and seeding command | Builder | Story 1.1, grown per epic | TC-4 |
| Shared vector files (pricing, merge) read by PHP and TypeScript | Builder | Stories 2.9 and 6.2 | R-01, R-05 |
| Registry completeness check in CI | Backend dev | Story 3.6 | R-03 |
| Tool choices: PDF text extractor, PDF/UA checker, axe-core, throttling | Builder | Epic 1 | TC-7 |

---

## Tooling and Access

| Tool or service | Purpose | Access required | Status |
|---|---|---|---|
| PostgreSQL 17 container image | Test database | CI runner | Pending |
| Playwright with `@seontechnologies/playwright-utils` | E2E and API tests | None | Pending |
| k6 | Load tests on staging | Staging access | Pending |
| axe-core | Accessibility | None | Pending |
| PDF/UA checker | Tagged PDFs | None | Pending |
| Provider sandboxes (NIN, SMS, WhatsApp) | Contract tests | Sandbox credentials | Pending provider choice |

---

## Interworking and Regression

| Component | Impact | Regression scope | Validation |
|---|---|---|---|
| **Pricing** | Every price, snapshot and credit | All vector and property tests | P0-001 to P0-005 on every pull request |
| **Orders and CreditNotes** | Money state | Concurrency, lock matrix, ledger suites | P0-007 to P0-016 on every pull request |
| **Visibility registry** | Every outbound surface | Registry suite | P0-017 on every pull request |
| **Sync protocol** | Every offline device | Merge vectors, pull test, offline E2E | P0-026, P0-027 on every pull request; P1-009 nightly |

**Regression strategy:** the P0 suite runs on every pull request, so any change to a shared module is checked against every money and visibility rule before merge.

---

## Appendix A: Tagging

```typescript
test('@P0 @API @Security a Technician never receives a price', async ({ apiRequest }) => {
  const { status, body } = await apiRequest({ method: 'GET', path: '/api/v1/catalogue' });
  expect(status).toBe(200);
  expect(JSON.stringify(body)).not.toContain('selling_price');
});
```

```bash
npx playwright test --grep @P0
```

```bash
npx playwright test --grep "@P0|@P1"
```

Pest groups mirror the same tags (`->group('p0', 'security')`).

---

## Appendix B: Knowledge Base References

- `risk-governance.md`: scoring and gates.
- `test-priorities-matrix.md`: P0 to P3 criteria.
- `test-levels-framework.md`: choosing the level.
- `test-quality.md`: definition of done (no hard waits, isolated, explicit assertions).
- `nfr-criteria.md` and `adr-quality-readiness-checklist.md`: NFR planning.

---

**Generated by:** BMad TEA Agent
**Workflow:** `bmad-testarch-test-design`
**Version:** 4.0 (BMad v6)
