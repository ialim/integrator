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

# Test Design for Architecture: integrator v1

**Purpose:** Architectural concerns, testability gaps and NFR requirements for the builder to address. It is the agreement on what must exist before test development can begin.

**Date:** 14 October 2026
**Author:** ialim, with the TEA test architect
**Status:** Architecture review pending
**Project:** integrator
**PRD reference:** `_bmad-output/planning-artifacts/prds/prd-integrator-2026-09-27/prd.md` and `addendum.md`
**ADR reference:** `_bmad-output/planning-artifacts/architecture/architecture-integrator-2026-10-09/ARCHITECTURE-SPINE.md` (AD-1 to AD-26)

---

## Executive Summary

**Scope:** The whole of integrator v1: the Laravel backend and JSON API, the Filament staff console, and the offline-capable React PWA, as broken into 16 epics and 172 stories in `epics.md`.

**Business context** (from the PRD):

- **Revenue and impact:** markup on goods is the only revenue; the breakeven path needs a realized markup of 22% or more (SM-C1), so pricing errors cost money directly.
- **Problem:** integrators quote and buy across spreadsheets, supplier calls and hand-calculated markups, with prices that move with the naira.
- **Launch:** Priority 1 and 2 epics (1 to 15) before launch; the returns workflow (Epic 16) about three months after.

**Architecture** (from the spine):

- **Single write path:** every state change is an Action that locks, checks a transition table, audits and emits an outbox event (AD-1, AD-4).
- **Money:** fixed-scale decimals, one rounding point, all formulas in the Pricing module (AD-6, AD-7).
- **Offline:** a local-first PWA with a server-receipt-time merge protocol (AD-12, AD-19).
- **Stack:** PHP 8.4, Laravel 13, Filament 5, PostgreSQL 17, React 19 and TypeScript, on Laravel Cloud (AD-20).

**Expected scale** (NFR-1): 500 Integrators, 5,000 Products and 500 Orders a month; launch at 10, 500+ and about 15.

**Risk summary:**

- **Total risks:** 21.
- **High priority (score 6 or more):** 11, all requiring mitigation before launch. No critical (score 9) risks.
- **Test effort:** 62 scenarios, roughly 160 to 260 hours including test infrastructure (about 4 to 6.5 weeks for one person, spread across the epics).

---

## Quick Guide

### BLOCKERS: must exist before integration tests can be written

1. **TC-1 / R-11: PostgreSQL 17 as the only test database.** Locking, partial unique indexes, the allocation trigger, the audit trigger and the sync snapshot guard exist only in PostgreSQL. (Recommended owner: builder, Story 1.1.)
2. **TC-2: an injectable clock and an on-demand sweep tick.** Dozens of rules turn on elapsed time; tests and staging must set and advance the `Shared` Clock and run one sweep tick deterministically. (Builder, Story 1.1, used from Story 2.17.)
3. **TC-3: provider fakes that inject faults.** Each fake must script success, definitive failure, timeout and error per call, because NIN retry, WhatsApp fallback and scan failure are specified behaviour. (Builder, with each provider interface.)
4. **TC-4: test-only seeding.** Factories and a seeding command, available only in local and test environments, that create Level 3 businesses, approved Suppliers, active Products with stock, a fresh rate and Paid Orders through Actions. (Builder, Story 1.1, grown per epic.)

**What we need:** these four in place in Epic 1, or the high-risk mitigations below cannot be built.

### HIGH PRIORITY: recommendations to approve

1. **R-01 Pricing drift:** all addendum vectors as named tests, plus property tests and a shared vector file for the app's money module. (Approve: builder.)
2. **R-02 Racing transitions:** a two-racer test and a replay test for every NFR-10 transition, run in parallel workers. (Already required by the spine; approve the parallel-worker condition.)
3. **R-03 / R-06 Money visibility:** extend the registry suite to device storage after a role change. (Approve: builder.)
4. **R-04 Price Lock boundaries:** a table-driven suite over the full lock matrix with the clock controlled. (Approve: builder.)
5. **R-07 NIN leakage:** a canary-NIN test across logs, queues and the outbox, and a crypto-shred test against a restored backup. (Approve: founder, since it touches the backup provider.)

### INFO ONLY: solutions provided

1. **Test strategy:** Unit for Pricing, Business Hours, merge rules and windows; Integration (Action and API tests on PostgreSQL) for every rule with persistence or concurrency; Component tests for Livewire and React states; E2E only for journeys, device storage, offline and uploads.
2. **Tooling:** Pest for PHP, Vitest for the app, Playwright for E2E and throttled timing, k6 for load, axe-core for accessibility, a PDF text extractor and a PDF/UA checker.
3. **Tiered CI:** every pull request under 15 minutes; nightly full E2E and timing; weekly k6 and provider sandboxes; quarterly restore drill.
4. **Coverage:** 62 scenarios (31 P0, 23 P1, 8 P2).
5. **Quality gates:** P0 100%, P1 at least 95%, every high risk mitigated before launch, no new accessibility violation.

---

## For the Builder: Open Topics

### Risk Assessment

**Total risks identified:** 21 (11 high, 6 medium, 4 low).

#### High-priority risks (score 6 or more)

| Risk ID | Category | Description | P | I | Score | Mitigation | Owner | Timeline |
|---|---|---|---|---|---|---|---|---|
| **R-01** | **DATA** | A price, VAT split or repriced amount differs by a kobo between server, app, PDF and ledger | 2 | 3 | **6** | Named vector tests; property tests; no money arithmetic outside Pricing; shared vectors for the app | Backend dev | Epic 2 |
| **R-02** | **DATA** | Two racing actions both succeed: stock oversold, Order completed or cancelled twice, Credit Note overspent, code redeemed twice | 2 | 3 | **6** | Two-racer and replay tests on PostgreSQL for every NFR-10 transition; lock-order assertion | Backend dev | Each epic from Epic 3 |
| **R-03** | **SEC** | A money field or price ordering reaches a Technician, Level 1 or Level 2 session | 2 | 3 | **6** | Registry suite over every outbound surface; check that every new resource is registered | Backend dev | Epic 3 on |
| **R-04** | **BUS** | The Price Lock, repricing or expiry decides wrongly at a time boundary | 2 | 3 | **6** | Table-driven lock-matrix suite with a controlled clock | Backend dev | Epic 8 |
| **R-05** | **DATA** | Offline sync loses an edit, resurrects wrongly, or skips an out-of-order commit | 3 | 2 | **6** | Shared merge vectors on both sides; out-of-order pull test; two-device offline E2E | PWA and backend devs | Epic 6 |
| **R-06** | **SEC** | Prices remain on a device after a role change, or are cached for a Technician | 2 | 3 | **6** | Device-store E2E after demotion; sync payload registry test | PWA dev | Epic 6 |
| **R-07** | **SEC** | A NIN reaches a log, queue or outbox payload, or survives in a backup after deletion | 2 | 3 | **6** | Canary-NIN test; crypto-shred test on a restored backup | Backend dev | Epic 4 |
| **R-08** | **OPS** | The sweep misses or double-fires a deadline | 2 | 3 | **6** | Timer Actions tested before, at and after each deadline and run twice; heartbeat alert test | Backend dev | Epic 2 |
| **R-09** | **DATA** | One bank credit confirms two Payments, or a re-import duplicates a credit | 2 | 3 | **6** | Database tests of the natural key and allocation trigger; re-import and split tests | Backend dev | Epic 8 |
| **R-10** | **SEC** | The Proposal PDF leaks cost, markup, discount or margin | 2 | 3 | **6** | Planted-value forbidden-field test on text and metadata | Backend dev | Epic 6 |
| **R-11** | **TECH** | Integration tests run on a database other than PostgreSQL | 2 | 3 | **6** | PostgreSQL-only test database; CI fails on any other connection | Builder | Epic 1 |

#### Medium-priority risks (score 3 to 5)

| Risk ID | Category | Description | P | I | Score | Mitigation | Owner |
|---|---|---|---|---|---|---|---|
| R-12 | SEC | Audit trail alterable if no least-privilege role is available | 2 | 2 | 4 | Spike in Story 1.6; trigger and chain-check tests | Builder |
| R-13 | PERF | Catalogue list misses about 5 s on slow 3G | 2 | 2 | 4 | Throttled timing in CI; real-device check | PWA dev |
| R-14 | BUS | Business Hours arithmetic or a calendar change sets a wrong time | 2 | 2 | 4 | Table-driven unit tests; recompute-on-change test | Backend dev |
| R-15 | TECH | Provider fakes diverge from real providers | 2 | 2 | 4 | Scheduled sandbox contract tests; fakes with the same failure modes | Backend dev |
| R-16 | BUS | Screens regress below WCAG 2.2 AA | 2 | 2 | 4 | axe-core in CI; manual pass before release | PWA dev |
| R-17 | OPS | iOS evicts saved Projects with unsynced changes | 2 | 2 | 4 | Manual iOS test; warning check | PWA dev |

#### Low-priority risks (score 1 to 2, and score 3 with impact 3 and probability 1)

| Risk ID | Category | Description | P | I | Score | Action |
|---|---|---|---|---|---|---|
| R-18 | OPS | A restore misses RPO 1 h or RTO 4 h | 1 | 3 | 3 | Quarterly drill; repeat at scale |
| R-19 | SEC | Segregation of duties or own-request approval bypassed | 1 | 3 | 3 | Constraint and segregation tests |
| R-20 | SEC | A step-up code reused for another action | 1 | 3 | 3 | Grant tests |
| R-21 | DATA | Invoice or credit-note numbers have gaps or repeats | 1 | 3 | 3 | Concurrent-issuance test |

Scores of 3 are listed with the low group because their probability is 1; each still has a test.

**Residual risk after mitigation:** low for money, visibility and NIN risks, because each is proven by an automated test on every pull request. Medium residual risk remains for R-05 (offline sync), because device and network variety exceeds what CI can reproduce, and for R-17 (iOS eviction), which depends on the operating system. Both are watched through the manual pre-release checks.

#### Risk category legend

- **TECH:** technical or architecture. **SEC:** security and data exposure. **PERF:** performance. **DATA:** data integrity. **BUS:** business logic and user impact. **OPS:** operations.

---

### NFR Testability Requirements

| NFR category | Threshold or requirement | Current design support | Gap or decision needed | Planned evidence |
|---|---|---|---|---|
| Security | Server-side role and Access Level enforcement (FR-11); staff MFA and 12-hour sessions; NIN never in logs (NFR-3); step-up per action | Supported (AD-13, AD-16, AD-18) | Whether a pre-launch penetration test is required | Registry suite, canary-NIN test, auth tests, dependency scans |
| Performance | Catalogue list about 5 s on the test profile (FR-55); three budgets set by measurement (Story 15.5) | Partial | **API latency P95/P99 UNKNOWN** | Throttled Playwright timing, k6 summary, Lighthouse |
| Reliability | Atomic, idempotent transitions (NFR-10); upload resume (FR-55); WhatsApp fallback in 15 minutes (FR-51); 99.5% availability (NFR-5) | Supported (AD-4, AD-14, AD-5) | Fault-injecting fakes (TC-3) | Concurrency and replay reports; fault-injection results; uptime record |
| Scalability | 500 Integrators, 5,000 Products, 500 Orders a month (NFR-1) | Partial | **Peak concurrent users UNKNOWN** | k6 at design scale |
| Recovery | RPO 1 h, RTO 4 h, quarterly drill (NFR-5) | Supported (AD-20) | None | Drill record |
| Maintainability | Architecture tests for AD-1, AD-2 and the foreign-key rule | Supported | **Coverage threshold UNKNOWN** | Architecture-test report; coverage report |

**Unknown thresholds:** API latency targets; peak concurrency; a coverage threshold; whether a penetration test is required; whether staging may hold an anonymised production-shaped data set.

**Assessment boundary:** final PASS, CONCERNS or FAIL belongs to `nfr-assess` once implementation evidence exists.

---

### Testability Concerns and Architectural Gaps

#### 1. Blockers to fast feedback

| Concern | Impact | What the architecture must provide | Owner | Timeline |
|---|---|---|---|---|
| **TC-1 Non-PostgreSQL tests** | Locking, constraints, triggers and the sync guard go untested | PostgreSQL 17 as the only test database, enforced in CI | Builder | Story 1.1 |
| **TC-2 No controllable clock** | Every deadline rule is untestable without waiting | Injectable Clock; a command that runs one sweep tick | Builder | Story 1.1 |
| **TC-3 Fakes without faults** | Retry, fallback and failure paths cannot be exercised | Scripted outcomes per call on every fake | Builder | With each interface |
| **TC-4 No seeding** | E2E setup through the UI is slow and brittle | Test-only factories and a seeding command that call Actions | Builder | Story 1.1, grown per epic |

#### 2. Architectural improvements needed

1. **Console testing path (TC-5)**
   - **Current problem:** staff behaviour is in Filament (Livewire), not reachable through `/api/v1`.
   - **Required change:** none to the design; test at the Action layer and with Livewire component tests, keeping console E2E to a few journeys.
   - **Impact if ignored:** a slow, brittle console E2E suite.
   - **Owner:** builder. **Timeline:** Epic 1.
2. **Multi-device offline harness (TC-6)**
   - **Current problem:** sync correctness depends on order of reconnection across devices.
   - **Required change:** Playwright with two browser contexts and `setOffline`; the merge specification's vectors in a file both suites read.
   - **Impact if ignored:** R-05 cannot be verified end to end.
   - **Owner:** PWA dev. **Timeline:** Epic 6.
3. **Named check tools (TC-7)**
   - **Current problem:** FR-30, UX-DR22, UX-DR23 and FR-55 need tools that are not chosen.
   - **Required change:** record a PDF text extractor, a PDF/UA checker, axe-core and a throttling method with the test framework.
   - **Owner:** builder. **Timeline:** Epic 1.
4. **API descriptions (TC-8)**
   - **Current problem:** no request or response schemas beyond the money and error shapes.
   - **Required change:** an OpenAPI description per module as endpoints are built, starting with the Technician-safe schema.
   - **Owner:** backend dev. **Timeline:** from Epic 3.

---

### Testability Assessment Summary

#### What works well

- A single write path enforced by architecture tests, so business logic is testable without HTTP or UI (AD-1).
- Every provider behind an interface with a fake in staging and tests (AD-15).
- Transition tables in code give a finite set of allowed and refused moves (AD-4).
- Required two-racer and replay tests for every NFR-10 transition, written into the conventions.
- One Pricing module holding every formula, with the addendum vectors as named tests (AD-7).
- One visibility registry with one test suite (AD-13).
- Server-computed UTC deadlines, so devices do no time arithmetic (AD-5).
- Synthetic data only in staging (AD-20).

#### Architecturally significant requirements

ACTIONABLE (each needs the tests named in the QA document): the Action contract (AD-4), money representation (AD-6), Pricing ownership (AD-7), deadlines and the sweep (AD-5), the sync protocol (AD-12), the visibility registry (AD-13), the Credit Note ledger and document numbering (AD-9), single allocation of bank credits (AD-10), the upload time stamp (AD-14), NIN protection (AD-16), the audit trail (AD-21) and two-person approval (AD-23).

FYI (verified by drill or by design): managed hosting with the offsite copy (AD-20), search on PostgreSQL and the device (AD-17), the single React SPA (AD-19).

#### Accepted trade-offs

- **No distributed tracing or metrics endpoint:** a monolith at launch volumes; structured logs with request ids and founder alerts suffice.
- **Single-region hosting without automated failover:** the hourly offsite copy and a 4-hour RTO are the stated recovery path.
- **No circuit breakers:** provider calls run in queued listeners, not inside requests, so a slow provider does not hang a user.

These are acceptable for v1 and should be revisited if volumes approach the design target.

ADR quality readiness: **19 of 29 criteria met** (testability 2/4, test data 3/3, scalability 2/4, disaster recovery 2/3, security 4/4, monitorability 2/4, QoS and QoE 3/4, deployability 1/3). The gaps are test enablers and operational targets, not design flaws.

---

### Risk Mitigation Plans (high-priority risks)

All eleven must be mitigated before launch. Owners and timelines are in the high-priority table above; the scenarios are in `test-design-qa.md`. Status for all: planned.

| Risk | Mitigation steps | Verification |
|---|---|---|
| R-01 Kobo drift | Every addendum vector as a named test; property tests over generated carts; a shared vector file for the app; no money arithmetic outside Pricing | Vector, property and architecture suites green |
| R-02 Racing transitions | A two-racer test and a replay test (including a changed payload returning 422) per NFR-10 transition, in parallel workers on PostgreSQL | Suites green under parallel execution |
| R-03 Money to restricted sessions | Registry suite from Story 3.6, extended each epic; CI fails on an unregistered resource or payload | Suite enumerates every surface and is green |
| R-04 Price Lock boundaries | FR-38 written as a decision table, each row driven by the controlled clock and the fake statement import | Every row and every FR-38 vector green |
| R-05 Offline sync | One merge specification run by both suites; an out-of-order commit test; two-context offline E2E | All three green |
| R-06 Prices on a device | Demote a Manager, reconnect, inspect IndexedDB; registry test over Technician sync payloads | No price field on the device |
| R-07 NIN leakage | Canary NIN through every path, searched in logs, exceptions, queue, outbox and exports; restore a pre-deletion backup after the 30-day deletion | Canary absent; restored NIN unreadable |
| R-08 Deadlines | Each timer tested before, at and after its deadline and twice; heartbeat alert; two schedulers | Timer suite green; alert observed |
| R-09 Bank credit reuse | Database tests of the natural key and allocation trigger; re-import and split tests | Suites green |
| R-10 PDF leakage | Planted sentinel values for every forbidden figure, searched in extracted text and metadata | No sentinel found |
| R-11 Wrong test database | PostgreSQL 17 as the only test connection; CI check | CI check present and green |

---

### Assumptions and Dependencies

#### Assumptions

1. One builder writes both the code and the tests; "owner" names the role, not a separate team.
2. Laravel Cloud supports running a PostgreSQL 17 instance for CI or allows a containerised one.
3. Provider sandboxes exist for the chosen NIN, SMS and WhatsApp providers.

#### Dependencies

1. Test blockers TC-1 to TC-4: required by the end of Epic 1.
2. Provider choices: required before Epics 3, 4 and 11 (as already deferred in the spine).
3. Clarifications on latency, concurrency and coverage: required before Story 15.5.

#### Risks to the plan

- **Risk:** the test infrastructure is squeezed out of Epic 1 to show features sooner.
  - **Impact:** every high-risk mitigation slips, and money rules ship without boundary tests.
  - **Contingency:** treat TC-1 to TC-4 as acceptance criteria of Story 1.1, not as optional tasks.

---

**End of Architecture Document.**

**Next steps for the builder:**

1. Fold TC-1 to TC-4 into Story 1.1 and Story 2.17's acceptance criteria.
2. Confirm the owners and timelines above.
3. Answer the five clarifications.

**Next steps for QA work:**

1. Use `test-design-qa.md` for the scenarios.
2. Build the test infrastructure with Epic 1.
