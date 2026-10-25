---
runScope: 'system-level'
runKey: 'system'
workflowStatus: 'completed'
totalSteps: 5
stepsCompleted: ['step-01-detect-mode', 'step-02-load-context', 'step-03-risk-and-testability', 'step-04-coverage-plan', 'step-05-generate-output']
lastStep: 'step-05-generate-output'
nextStep: ''
lastSaved: '2026-10-14'
inputDocuments:
  - _bmad-output/planning-artifacts/prds/prd-integrator-2026-09-27/prd.md
  - _bmad-output/planning-artifacts/prds/prd-integrator-2026-09-27/addendum.md
  - _bmad-output/planning-artifacts/architecture/architecture-integrator-2026-10-09/ARCHITECTURE-SPINE.md
  - _bmad-output/planning-artifacts/ux-designs/ux-integrator-2026-10-07/EXPERIENCE.md
  - _bmad-output/planning-artifacts/epics.md
  - _bmad/tea/config.yaml
  - knowledge/adr-quality-readiness-checklist.md
  - knowledge/nfr-criteria.md
  - knowledge/test-levels-framework.md
  - knowledge/risk-governance.md
  - knowledge/test-quality.md
  - knowledge/playwright-cli.md
---

# Test design progress: system

## Step 1: Mode and prerequisites

- **Mode:** System-level. The user asked for a system-level design, and both the PRD and architecture and a full epic breakdown exist, for which the step prefers system-level first. No `sprint-status.yaml` exists.
- **Prerequisites present:**
  - PRD with FR-1 to FR-80 and NFR-1 to NFR-10: `_bmad-output/planning-artifacts/prds/prd-integrator-2026-09-27/prd.md`
  - PRD addendum with the 82-row edge-case register and the pricing test vectors: `.../addendum.md`
  - Architecture decisions AD-1 to AD-26 (the ADR equivalent) and stack: `_bmad-output/planning-artifacts/architecture/architecture-integrator-2026-10-09/ARCHITECTURE-SPINE.md`
  - UX behaviour, including the accessibility floor: `_bmad-output/planning-artifacts/ux-designs/ux-integrator-2026-10-07/EXPERIENCE.md`
  - Epics and stories, 16 epics and 172 stories: `_bmad-output/planning-artifacts/epics.md`
- **State:** Greenfield. No application code exists yet.
- **Checkpoint:** None existed; fresh run.

## Step 2: Context loaded

**Config.** `tea_use_playwright_utils: true`, `tea_use_pactjs_utils: true` (no Pact dependency or contract-testing need in the repository, so no contract branch), `tea_pact_mcp: mcp` (not relevant), `tea_browser_automation: auto` (no running app to explore yet), `test_stack_type: auto`.

**Stack.** No code exists, so auto-detection finds nothing. The target stack from the architecture is **fullstack**: a PHP 8.4 / Laravel 13 backend with a Filament 5 staff console and PostgreSQL 17, and a React 19 / TypeScript PWA (Vite 8, Workbox, Dexie, TanStack Query and Router). Backend tests: Pest or PHPUnit with Laravel's test tools. Front-end tests: Vitest for units, Playwright for end-to-end and API.

**Knowledge loaded:** adr-quality-readiness-checklist, nfr-criteria, test-levels-framework, risk-governance, test-quality, playwright-cli.

**Integration points (all behind AD-15 interfaces with fakes):** NIN verification provider; SMS one-time codes; bot check; email; WhatsApp Business; bank-statement CSV import; malware scanner; object storage (S3 API, Cloudflare R2); PDF renderer; offsite backup provider; WebAuthn (passkeys); browser storage and service worker on the device.

**NFR thresholds stated:**
- NFR-1 scale: 500 Integrators, 5,000 Products, 500 Orders a month (launch 10, 500+, about 15).
- NFR-2 / FR-78 queue targets: Receipts 2 Business Hours; Level 3 and NIN 1 Business Day; approval 4 Business Hours; Credit Note requests 1 Business Day; returns 2 Business Days; escalation at 75%.
- NFR-4 / FR-19: decimal money, round half-up once per unit to the kobo; addendum test vectors.
- NFR-5: 99.5% monthly availability; RPO 1 hour; RTO 4 hours; quarterly restore drill.
- NFR-6: Android 9+ Chrome, iOS 16.4+ installed app.
- NFR-7 / FR-55: Catalogue list in about 5 seconds on slow 3G (400 kbps, 400 ms) on a 2 GB Android phone.
- NFR-9: UTC timers, idempotent, founder alerted on a miss.
- NFR-10: atomic, idempotent money transitions; first valid action wins.
- Sessions: Integrators 30 idle days; Staff 12 hours with MFA; one-time codes 10 minutes.
- Accessibility: WCAG 2.2 AA on every screen, PDF, email and WhatsApp message.

**Thresholds missing or open:**
- NFR-7 budgets for Project open, Proposal generation and Receipt upload (Story 15.5 sets them by measurement).
- No API latency targets (P95/P99) and no throughput target beyond NFR-1 volumes.
- Sign-in caps and lockout (PRD §8.3 question 6; placeholders 5, 3, 15 minutes).
- Alert baselines for unusual patterns (question 13).
- Retention periods (NFR-8, counsel and accountant).
- Whether the host allows a least-privilege database role (architecture Open Question 1).

Nothing required is missing for a system-level design.

## Step 3: Testability and risk

### Testability concerns (actionable first)

| # | Concern | Evidence | Action |
|---|---|---|---|
| TC-1 | Tests that use SQLite or an in-memory database cannot exercise the guarantees the design rests on: `SELECT ... FOR UPDATE`, partial unique indexes, the allocation-sum trigger, the audit `UPDATE`/`DELETE` trigger, and `xid8` with `pg_snapshot_xmin` in sync pull | AD-4, AD-10, AD-12, AD-21; Conventions: "Any invariant expressible as a constraint is also enforced in PostgreSQL" | Every integration and concurrency test runs against PostgreSQL 17, decided in Story 1.1 |
| TC-2 | No controllable clock is specified for tests or staging. Dozens of rules turn on elapsed time (24 and 48 hours, 7, 14 and 30 days, 12 months, Business Hours), and the sweep must be driven deterministically | AD-5 (deadline columns, sweep); the `Shared` Clock | The Clock is injectable; tests and staging can set and advance it; a test command runs one sweep tick on demand |
| TC-3 | Provider fakes are required, but nothing says they can inject faults. Specified behaviour depends on timeouts and failures: NIN retry with backoff, WhatsApp undelivered after 15 minutes, scan failure, SMS failure | AD-15; FR-4, FR-51 | Each fake supports scripted outcomes per call: success, definitive failure, timeout, error |
| TC-4 | No seeding mechanism for end-to-end tests. Journeys need businesses at Level 3, approved Suppliers, active Products with stock, a fresh Platform Rate and Paid Orders before the step under test | AD-20 (staging synthetic data only); ADR checklist 1.3 | Test-only factories and a seeding command, available in local and test environments only and never in production, that create these states through Actions |
| TC-5 | Staff console behaviour lives in Filament (Livewire), not the JSON API, so it is not reachable headless through `/api/v1` | AD-3, AD-19 | Test staff behaviour at the Action layer and with Livewire component tests; keep browser E2E for a few console journeys only |
| TC-6 | Offline behaviour needs a multi-device harness: two Users editing one Project offline, reconnecting in a set order, and a role change while edits are queued | AD-12; FR-28; edge-case rows 9, 34, 35 | Playwright with two browser contexts and `setOffline`; the shared merge specification's vectors run in both the PHP and TypeScript suites |
| TC-7 | Several required checks need tools not named anywhere: PDF text-and-metadata extraction (FR-30), tagged-PDF validation (UX-DR23), automated accessibility (UX-DR22), slow-3G throttling (FR-55) | FR-30, UX-DR22, UX-DR23, FR-55 | Choose and record the tools with the test framework: a PDF text extractor, a PDF/UA checker, axe-core, Playwright network throttling plus a real device |
| TC-8 | No sample API requests or response schemas exist yet, apart from the money JSON shape and the error shape | ADR checklist 1.4; Conventions | Publish an OpenAPI description per module as endpoints are built; the Technician-safe schema (FR-11) is the first |
| TC-9 | Observability is logs plus founder alerts; there are no request metrics or traces, and error tracking is deferred | AD-20; Conventions; Deferred list | Acceptable at launch volumes; choose error tracking before launch, as already deferred |

### Strengths already in the design

- A single write path (AD-1) enforced by architecture tests: every state change is an Action, so business logic is testable without HTTP or UI.
- Every external provider sits behind an interface with a fake bound in staging and tests (AD-15).
- Transition tables in code for each entity (AD-4) give a finite set of allowed and refused moves to test.
- The architecture already requires a two-racer concurrency test and an idempotent-replay test for every NFR-10 transition.
- Pricing is one module with the addendum's vectors as named tests (AD-7).
- One visibility registry with one test suite across every Technician, Level 1 and Level 2 surface (AD-13).
- Deadlines are server-computed UTC columns (AD-5), so devices do no time arithmetic that would need testing on each.
- Staging uses synthetic data only (AD-20).

### Architecturally significant requirements

| ASR | Source | Status |
|---|---|---|
| Lock order, typed refusal, idempotency keys and outbox (the Action contract) | AD-4, NFR-10 | ACTIONABLE |
| Money as fixed-scale decimals with one rounding point; a display-only kobo module in the app | AD-6, FR-19, NFR-4 | ACTIONABLE |
| Pricing owns every formula and the price snapshot | AD-7 | ACTIONABLE |
| Deadlines as columns plus a per-minute sweep; Business Hours over the holiday calendar | AD-5, NFR-9 | ACTIONABLE |
| Sync protocol: merge by server receipt time, `change_seq` with a snapshot guard, purge on role change | AD-12, FR-28 | ACTIONABLE |
| One field-visibility registry for every outbound surface | AD-13, FR-11 | ACTIONABLE |
| Credit Note ledger and gapless document numbering | AD-9, FR-43, FR-77 | ACTIONABLE |
| A bank credit allocated at most once | AD-10, FR-41 | ACTIONABLE |
| The upload intent stamps the time that counts for the Price Lock | AD-14, FR-38 | ACTIONABLE |
| NIN encrypted per record and crypto-shredded on deletion | AD-16, NFR-3 | ACTIONABLE |
| Append-only, hash-chained audit trail | AD-21, FR-53 | ACTIONABLE |
| Two-person approval with a database-enforced difference between requester and approver | AD-23, FR-52 | ACTIONABLE |
| Managed hosting with an hourly offsite copy | AD-20, NFR-5 | FYI, verified by drill |
| Search on PostgreSQL `pg_trgm` and on the device | AD-17 | FYI |
| A single React SPA, with no Inertia or Livewire in the app | AD-19 | FYI |

### ADR quality readiness (29 criteria)

| Category | Met | Notes |
|---|---|---|
| 1. Testability and automation | 2/4 | Isolation and headless access for the app are covered; seeding (TC-4) and sample requests (TC-8) are gaps |
| 2. Test data strategy | 3/3 | Business Account scoping, synthetic data only in staging, isolated test databases |
| 3. Scalability and availability | 2/4 | Stateless web with database sessions; SLA defined (99.5%); bottlenecks unknown until Story 15.5; no circuit breakers, though provider calls run in queued listeners rather than inside requests |
| 4. Disaster recovery | 2/3 | RPO 1 h and RTO 4 h defined; offsite copy with a quarterly drill; no automated failover (single region, accepted) |
| 5. Security | 4/4 | Sanctum plus a separate staff guard with mandatory 2FA; per-record NIN encryption; secrets in the host store; validation and parameterised queries |
| 6. Monitorability | 2/4 | Structured JSON logs with a request id; settings are database rows; no tracing (a monolith, low need); no metrics endpoint |
| 7. QoS and QoE | 3/4 | Rate limits on price endpoints and codes; loading states and friendly errors specified; latency targets UNKNOWN |
| 8. Deployability | 1/3 | Migrations owned per module; zero-downtime deploys and automated rollback undefined (CI and deploy gates deferred to Epic 1) |
| **Total** | **19/29** | CONCERNS: the gaps are test enablers and operational targets, not design flaws |

### Risk register

Score is probability (1 to 3) times impact (1 to 3). Each row cites the statement it rests on.

| ID | Cat | Risk | Source | P | I | Score | Mitigation | Owner | By |
|---|---|---|---|---|---|---|---|---|---|
| R-01 | DATA | A price, VAT split or repriced amount differs by a kobo between server, app, PDF and ledger | AD-6 "Prevents: rounding drift between PHP, JavaScript and the database"; FR-19, FR-30; addendum vectors | 2 | 3 | **6** | Every addendum vector as a named Pricing test (Story 2.9); property tests over random carts asserting unit × quantity = line and lines sum to the total; architecture test forbidding money arithmetic outside Pricing; the app's money module tested against the same vectors | Backend dev | Epic 2 |
| R-02 | DATA | Two racing actions both succeed: stock oversold, an Order completed or cancelled twice, a Credit Note overspent, a code redeemed twice | NFR-10; AD-4; edge-case rows 1, 23, 40, 51, 56, 57, 58 | 2 | 3 | **6** | A two-racer concurrency test and an idempotent-replay test for every NFR-10 transition against real PostgreSQL (TC-1); lock-order assertion in the lock registry | Backend dev | Each epic from Epic 3 |
| R-03 | SEC | A money field or price-derived ordering reaches a Technician, Level 1 or Level 2 session | FR-11 "an automated test fails when any money field ... is reachable"; AD-13 "Prevents: a money field ... reaching someone only because one surface forgot" | 2 | 3 | **6** | Registry-driven suite over every API resource, sync payload, notification, export and sort or filter parameter (Stories 3.6, 5.4), extended by each epic; a check that every new API resource is registered | Backend dev | Epic 3 on |
| R-04 | BUS | The Price Lock, repricing or expiry decides wrongly at a boundary: hour 24, hour 48, 7 days, a corrected re-upload, an upload that straddles a boundary | FR-38 and founder decisions 2, 3 and 4; edge-case rows 42 to 46 | 2 | 3 | **6** | Table-driven suite over the full lock matrix with the clock controlled (TC-2), covering every edge row and the FR-38 vectors | Backend dev | Epic 8 |
| R-05 | DATA | Offline sync loses an edit, resurrects a deleted item wrongly, or skips a row whose commit landed out of order | AD-12; FR-28 "Two Technicians editing different Rooms offline lose no edits"; edge-case rows 34, 35 | 3 | 2 | **6** | Shared merge specification with vectors on both sides; a pull test that commits out of order and asserts nothing is skipped; two-context offline E2E (TC-6) | PWA and backend devs | Epic 6 |
| R-06 | SEC | Prices remain on a device after a role change or removal, or are cached for a Technician | Edge-case rows 9, 10; AD-12 "purged at the next connection" | 2 | 3 | **6** | E2E: a Manager demoted to Technician reconnects and IndexedDB holds no price field; sync payload registry test; held-edit test | PWA dev | Epic 6 |
| R-07 | SEC | A NIN reaches a log, exception report, queue or outbox payload, or survives in a backup after deletion | NFR-3; AD-16 "Prevents: NIN ... recoverable from a backup after deletion; NIN leaking into logs" | 2 | 3 | **6** | Canary-NIN test that drives every NIN path and searches logs, exceptions, queue and outbox payloads; crypto-shred test restoring a backup taken before deletion and asserting the NIN cannot be decrypted | Backend dev | Epic 4 |
| R-08 | OPS | The sweep misses or double-fires a deadline (price hold, expiry, Issue Code, tier recalculation) | AD-5; NFR-9 "alert the founder when a job misses" | 2 | 3 | **6** | Timer Actions tested with the clock just before, at and after each deadline, and run twice; heartbeat alert test; single-server behaviour with two schedulers | Backend dev | Epic 2 |
| R-09 | DATA | One bank credit confirms two Payments, or a re-imported statement duplicates a credit | AD-10; FR-41 "A bank reference can be used once"; edge-case rows 54, 55 | 2 | 3 | **6** | Database-level tests of the natural-key constraint and the allocation-sum trigger; re-import test; split test | Backend dev | Epic 8 |
| R-10 | SEC | The Proposal PDF leaks cost, markup, discount or margin in its text or metadata | FR-30 "A forbidden-field test searches the PDF's visible text and metadata" | 2 | 3 | **6** | Forbidden-field test using planted sentinel values for each forbidden figure, searched in extracted text and metadata (TC-7) | Backend dev | Epic 6 |
| R-11 | TECH | Integration tests run against a database other than PostgreSQL, so locking and constraint guarantees go untested | AD-4, AD-10, AD-21; Conventions (constraints enforced in PostgreSQL) | 2 | 3 | **6** | Story 1.1 makes PostgreSQL 17 the only test database; CI fails if the test connection is anything else | Builder | Epic 1 |
| R-12 | SEC | The audit trail can be altered if the host does not allow a least-privilege role | AD-21; architecture Open Question 1 | 2 | 2 | 4 | Spike in Story 1.6; trigger test; chain-check test that detects an edited row | Builder | Epic 1 |
| R-13 | PERF | The Catalogue list misses about 5 seconds on slow 3G on a 2 GB phone | FR-55; NFR-7 | 2 | 2 | 4 | Throttled Playwright timing in CI on every change; real-device check before launch (Story 15.5) | PWA dev | Epic 3 |
| R-14 | BUS | Business Hours arithmetic or a calendar change sets a wrong "by" time or queue target | AD-5 "a calendar change recomputes every open Business-Hours deadline"; FR-78 example "5pm Saturday is due by 9am Monday" | 2 | 2 | 4 | Unit tests over a table of start times, durations and closed days, including the FR-78 example; recompute-on-change test | Backend dev | Epic 4 |
| R-15 | TECH | Provider fakes behave differently from the real NIN, SMS, WhatsApp or scanning providers | AD-15; Deferred: providers chosen before the Identity, Notifications and Payments epics | 2 | 2 | 4 | Contract tests for each adapter against the provider's sandbox on a schedule; fakes support the same failure modes (TC-3) | Backend dev | As each provider is chosen |
| R-16 | BUS | Screens regress below WCAG 2.2 AA | UX-DR21, UX-DR22 "a new failure stops the release" | 2 | 2 | 4 | axe-core in CI on every screen and state; manual keyboard and screen-reader pass before each release | PWA dev | Epic 1 on |
| R-17 | OPS | iOS evicts saved Projects that hold unsynced changes | NFR-6; AD-12 (`persist()`, eviction warning) | 2 | 2 | 4 | Manual iOS test before launch; automated check that the warning appears when persistence is refused | PWA dev | Epic 12 |
| R-18 | OPS | A restore misses the 1-hour recovery point or the 4-hour recovery time | NFR-5; AD-20 | 1 | 3 | 3 | Quarterly drill (Story 1.2), repeated at design scale (Story 15.5) | Founder | Epic 1, then quarterly |
| R-19 | SEC | A staff member acts on a business they created, approved or vouched for, or approves their own request | FR-52; AD-23 "database check that approver differs from requester" | 1 | 3 | 3 | Constraint test; segregation tests on every gated Action | Backend dev | Epic 2 on |
| R-20 | SEC | A step-up code proven for one action is reused for another | AD-18 (`StepUpGrant`) | 1 | 3 | 3 | Grant tests for wrong action, wrong target, expiry and second use | Backend dev | Epic 3 |
| R-21 | DATA | Invoice or credit-note numbers have gaps or repeats | AD-9 "document numbers ... gapless"; FR-77 | 1 | 3 | 3 | Concurrent-issuance test asserting a contiguous sequence; rollback test | Backend dev | Epic 10 |

**High risks (score 6 or more): 11. Critical (score 9): none.**

### NFR planning

| Category | Threshold | Planned evidence |
|---|---|---|
| Security | Server-side role and Access Level enforcement (FR-11); staff MFA and 12-hour sessions; NIN never in logs (NFR-3); step-up per action | Registry suite (R-03); canary-NIN test (R-07); auth and session feature tests; Composer and npm vulnerability scans in CI |
| Performance | Catalogue list about 5 s on the test profile (FR-55); three more budgets set by measurement (Story 15.5); **API latency P95/P99 UNKNOWN** | Throttled Playwright timing in CI; k6 load test at NFR-1 volumes (Story 15.5); Lighthouse on key screens |
| Reliability | Atomic, idempotent transitions (NFR-10); upload resume (FR-55); WhatsApp to email fallback after 15 minutes (FR-51) | Concurrency and replay tests (R-02); network-interruption E2E for uploads; fault-injecting fakes (TC-3) |
| Scalability | 500 Integrators, 5,000 Products, 500 Orders a month (NFR-1); **peak concurrent users UNKNOWN** | k6 at design scale (Story 15.5) |
| Availability and recovery | 99.5% monthly; RPO 1 h; RTO 4 h (NFR-5) | Uptime monitoring (tool deferred); quarterly restore-drill record |
| Maintainability | Architecture tests for AD-1, AD-2 and the foreign-key rule; **coverage threshold UNKNOWN** | CI architecture tests; coverage report |
| Compliance | NDPA obligations (NFR-3); VAT documents reconcile (FR-80) | VAT export reconciliation test (Story 15.4); breach runbook (Story 5.12); counsel sign-off is outside testing |
| Accessibility | WCAG 2.2 AA on screens, PDFs, emails and WhatsApp | axe-core in CI; PDF/UA check; manual assistive-technology pass |

**Clarifications to raise, not guessed:**

1. API latency targets (P95, P99) for the main endpoints.
2. Expected peak concurrent users and requests per second, beyond monthly volumes.
3. A code-coverage threshold for CI.
4. Whether a penetration test is required before launch, and who runs it.
5. Whether staging may hold an anonymised, production-shaped data set for performance testing (AD-20 currently forbids production data in staging).

### Summary

Eleven high risks and no critical ones. They cluster in three places:

1. **Money correctness under concurrency and time** (R-01, R-02, R-04, R-08, R-09): real-PostgreSQL concurrency tests, a controllable clock and table-driven boundary suites.
2. **Who may see what** (R-03, R-06, R-07, R-10): the registry suite, device-store inspection, canary-NIN tests and planted-value PDF tests.
3. **Test infrastructure** (R-05, R-11, and TC-1 to TC-4): the clock, fault-injecting fakes, seeding and PostgreSQL must exist in Epic 1, or the other mitigations cannot be built.

## Step 4: Coverage plan

Levels: **Unit** (Pest for PHP, Vitest for the app), **Integration** (Laravel feature and Action tests against PostgreSQL 17, including HTTP API tests), **Component** (Livewire or React component tests), **E2E** (Playwright, for journeys and device behaviour only). Each scenario sits at the lowest level that can prove it.

### Coverage matrix

| ID | Scenario | Level | Priority | Risk link | Notes |
|---|---|---|---|---|---|
| S-01 | Addendum price vectors: plain unit, discounted unit, rounding once per unit, quantity 1,000 | Unit | P0 | R-01 | Pure Pricing functions |
| S-02 | Proposal VAT line vector: lines 10.86, total 32.58, VAT 2.28; identical totals with and without the line | Unit | P0 | R-01 | |
| S-03 | Repricing vector: 714,875.00 to 752,500.00, balance 152,500.00; only the rate changes | Unit | P0 | R-01, R-04 | |
| S-04 | Property test over random carts: unit × quantity = line, lines sum to total, floor never breached | Unit | P1 | R-01 | |
| S-05 | The app's money module reproduces the server vectors for display sums | Unit | P0 | R-01 | Vitest, same vector file |
| S-06 | Discount rules: larger wins, 10% back-order cap, refusal below the floor | Unit | P0 | R-01 | |
| S-07 | Platform Rate guards: zero refused, above 3% to approval, above 10% refused or Admin override, 48-hour staleness stops Order creation | Integration | P0 | R-08 | Clock controlled |
| S-08 | Two-racer and replay tests for every NFR-10 transition: Order creation, Payment confirmation, stock reservation, cancel against approve or expiry, Credit Note apply and refund, Vouch Code redemption | Integration | P0 | R-02, R-11 | Real PostgreSQL; must run in parallel workers |
| S-09 | Last unit confirmed by two Orders: one reserved, the other credited | Integration | P0 | R-02 | Edge-case row 23 |
| S-10 | Idempotency: same key replays the result; same key, different payload returns 422 | Integration | P0 | R-02 | API level |
| S-11 | Price Lock matrix: in time, late, straddling the boundary, corrected re-upload, tolerance, credit value time before upload | Integration | P0 | R-04 | Table-driven, clock controlled; edge-case rows 42 to 46 |
| S-12 | Expiry: 48 h with no Receipt, 48 h after the last upload or decision, 7-day cap, credit-covered Order never expires | Integration | P0 | R-04, R-08 | |
| S-13 | Order Quote fingerprint changes (rate, tier, Proposal, back-order terms, stock) return 409 with a new quote | Integration | P0 | R-02 | |
| S-14 | Statement re-import changes nothing; allocation trigger caps a line; a bank reference is used once | Integration | P0 | R-09 | Database level |
| S-15 | Confirmation makes the Order Paid, reserves stock, credits a shortfall | Integration | P0 | R-02 | |
| S-16 | Payment reversal needs a second person and returns the Order to Awaiting Payment | Integration | P1 | R-19 | |
| S-17 | Credit Note balance formula; concurrent holds never exceed the balance; restore keeps the original expiry | Integration | P0 | R-02 | Edge-case rows 57, 58 |
| S-18 | Refunds: whole balance only, recorded account only, fresh code, refused on Hold | Integration | P0 | R-20 | |
| S-19 | Invoice and credit-note numbers contiguous under concurrent issue; one invoice per Order | Integration | P1 | R-21 | |
| S-20 | Registry suite: every API resource, sync payload, notification, export and sort or filter parameter carries no money field for Technician, Level 1 and Level 2 sessions | Integration | P0 | R-03 | Grows with each epic |
| S-21 | Role matrices enforced on the server (FR-11 for businesses, FR-52 for staff) | Integration | P0 | R-03, R-19 | |
| S-22 | Proposal PDF carries none of the planted forbidden values in text or metadata | Integration | P0 | R-10 | |
| S-23 | Technician device store holds no price field; a demoted Manager's store is purged on reconnect | E2E | P0 | R-06 | Needs the browser's IndexedDB, so E2E |
| S-24 | Price endpoints are rate-limited; no bulk price listing exists | Integration | P1 | R-03 | |
| S-25 | A canary NIN never appears in logs, exception reports, queue or outbox payloads, or exports | Integration | P0 | R-07 | |
| S-26 | After the 30-day deletion, a backup taken earlier cannot decrypt the NIN | Integration | P0 | R-07 | |
| S-27 | NIN check outcomes: timeout retries without using the attempt, mismatch makes all later checks manual, duplicate hash flagged | Integration | P1 | R-15 | Fault-injecting fake |
| S-28 | Vouch Code rules: Established only, 2 active, 6 per 90 days, 48 hours, locked after 5 wrong tries | Integration | P1 | R-02 | |
| S-29 | Step-up grant: one action, one target, 10 minutes, single use | Integration | P0 | R-20 | |
| S-30 | No path reaches Level 3 without Admin approval or a direct grant | Integration | P0 | R-03 | |
| S-31 | Every timer Action before, at and after its deadline, and run twice | Integration | P0 | R-08 | |
| S-32 | Business Hours table, including 5 pm Saturday to 9 am Monday, closed days, and recompute on a calendar change | Unit | P1 | R-14 | |
| S-33 | Sweep heartbeat alert fires on a missed run; only one server runs the sweep | Integration | P1 | R-08 | |
| S-34 | Shared merge-specification vectors pass in both PHP and TypeScript | Unit | P0 | R-05 | |
| S-35 | Pull never skips a row committed out of order | Integration | P0 | R-05, R-11 | PostgreSQL snapshot behaviour |
| S-36 | Two devices offline editing different Rooms lose nothing after sync | E2E | P1 | R-05 | Two browser contexts |
| S-37 | An edit after a deletion resurrects the item and records a conflict; 30-day restore | Integration | P2 | R-05 | |
| S-38 | 14-day lock from server time; a device clock earlier than the last check-in also locks | Unit | P2 | R-05 | Vitest |
| S-39 | Eviction warning appears when storage persistence is refused | Component | P2 | R-17 | Manual iOS check also planned |
| S-40 | Audit `UPDATE` and `DELETE` rejected; chain check detects an edited row | Integration | P1 | R-12 | |
| S-41 | Architecture tests for AD-1, AD-2 and the foreign-key rule; CI fails if the test database is not PostgreSQL | Unit | P0 | R-11 | Static and configuration checks |
| S-42 | Requester cannot approve their own request; segregation refuses staff linked to the business | Integration | P0 | R-19 | |
| S-43 | Shipment transition table, required evidence, and serial count, uniqueness and format | Integration | P1 | R-02 | |
| S-44 | Completion rule; spend query excludes credited lines and replacement Shipments | Integration | P1 | R-02 | |
| S-45 | Return-window arithmetic: calendar days in WAT ending 23:59:59, judged at first Case time, snapshotted | Unit | P2 | R-14 | Epic 16 |
| S-46 | A Suspected Product blocks Payment confirmation and shipment of affected lines | Integration | P1 | R-02 | |
| S-47 | UJ-1 end to end: Project, Proposal, Order, Receipt, confirmation, approval, delivery, Completed | E2E | P0 | R-01, R-04 | The release smoke test |
| S-48 | UJ-2: sign up, NIN, Vouch Code, approval, prices appear | E2E | P1 | R-03 | |
| S-49 | UJ-3: Cart order with pick-up | E2E | P1 | R-02 | |
| S-50 | UJ-5: Technician opens a saved guide offline and sees no price | E2E | P1 | R-06 | |
| S-51 | UJ-6: Finance confirms a Receipt in the console | E2E | P1 | R-09 | One of few console E2E journeys |
| S-52 | UJ-7: return from Case to Resolved | E2E | P2 | R-02 | Priority 3 epic |
| S-53 | Receipt upload resumes after a dropped connection and keeps the time its details arrived | E2E | P0 | R-04 | |
| S-54 | Catalogue list usable in about 5 s on throttled slow 3G | E2E | P1 | R-13 | Real-device check before launch |
| S-55 | axe-core on every screen and state | E2E | P1 | R-16 | |
| S-56 | Generated PDFs pass a PDF/UA check | Integration | P2 | R-16 | |
| S-57 | k6 load at design scale on staging | Integration | P1 | R-13 | Story 15.5 |
| S-58 | Restore drill meets RPO 1 h and RTO 4 h | Integration | P1 | R-18 | Operational drill |
| S-59 | Provider adapters against provider sandboxes | Integration | P2 | R-15 | Scheduled |
| S-60 | Notifications: no amount to a Technician, WhatsApp only with opt-in, email fallback after 15 minutes | Integration | P1 | R-03 | |
| S-61 | Monthly VAT export reconciles to documents issued | Integration | P1 | R-21 | |
| S-62 | Each surface covers its listed states (UX-DR19) | Component | P2 | R-16 | Added at output; grows with each screen |

**Totals:** 62 scenarios. P0: 31. P1: 23. P2: 8. P3: none. Every risk R-01 to R-21 has at least one row.

### NFR coverage and evidence

| Category | Scenarios | Evidence for `nfr-assess` |
|---|---|---|
| Security | S-18, S-20 to S-22, S-25, S-26, S-29, S-30, S-42 | CI test reports; Composer and npm audit output |
| Performance | S-54, S-57 | Throttled timing report; k6 summary JSON |
| Reliability | S-08 to S-13, S-31, S-33, S-53, S-60 | CI concurrency and replay reports; fault-injection results |
| Scalability | S-57 | k6 at NFR-1 volumes |
| Availability and recovery | S-58 | Drill record with measured RPO and RTO |
| Maintainability | S-41 | Architecture-test report; coverage report |
| Compliance | S-25, S-26, S-61 | Test reports; breach runbook (Story 5.12) |
| Accessibility | S-55, S-56 | axe reports; PDF/UA reports; manual pass record |

**Blockers and assumptions:** API latency targets, peak concurrency and a coverage threshold are UNKNOWN (clarifications 1 to 3). Until set, performance evidence is a baseline, not a pass.

### Execution strategy

- **Every pull request (target under 15 minutes):** unit and integration suites in parallel against PostgreSQL, architecture tests, the registry suite, Vitest, and a Playwright smoke run of S-47 plus axe on changed screens.
- **Nightly:** the full E2E suite including offline two-context runs (S-36), throttled performance (S-54), full axe and PDF/UA runs, and the provider fault matrix.
- **Weekly:** k6 at design scale on staging (S-57) and provider sandbox checks (S-59).
- **Quarterly:** the restore drill (S-58).
- **Before each release:** a manual keyboard and screen-reader pass, and a manual iOS eviction check.

### Resource estimates

| Work | Estimate |
|---|---|
| Test infrastructure (PostgreSQL in CI, injectable clock, fault-injecting fakes, seeding, Playwright and Vitest setup) | ~25 to 40 hours |
| P0 scenarios | ~70 to 110 hours |
| P1 scenarios | ~50 to 80 hours |
| P2 scenarios | ~15 to 30 hours |
| **Total** | **~160 to 260 hours**, spread across the epics that build each area |

### Quality gates

- P0 pass rate 100%; P1 pass rate at least 95%.
- All eleven high risks (R-01 to R-11) mitigated before launch.
- No new axe violation on any screen.
- Coverage of at least 80% of lines in Pricing, Orders and CreditNotes; the overall threshold is to be agreed (clarification 3).
- NFR evidence identified for every category above; the PASS, CONCERNS or FAIL verdict is left to `nfr-assess` once evidence exists.

## Step 5: Outputs

- **Execution mode:** sequential (subagents not used).
- **Outputs:**
  - `_bmad-output/test-artifacts/test-design-architecture.md`
  - `_bmad-output/test-artifacts/test-design-qa.md`
  - `_bmad-output/test-artifacts/test-design/integrator-handoff.md`
- **Checklist fixes applied:** execution strategy restated as every functional test on each pull request with the 15-minute philosophy and smoke-to-P2 order; P3 effort and week range stated; residual risk recorded; ASRs added to the architecture document; mitigation plans condensed into one table; scenario counts reconciled to 62 (31 P0, 23 P1, 8 P2) after adding S-62 for per-surface state checks.
- **Checklist items not applicable:** `probability-impact.md` and `test-priorities-matrix.md` were not in the system-level required set (the priorities matrix was read for step 4); no status file exists to log into; no project team names beyond the builder; no browser sessions were opened.
- **Known deviation:** the architecture document is about 270 lines against a 150 to 200 line target, because the risk tables are complete; prose was cut instead of rows.
- **Open for the user:** five clarifications (latency, concurrency, coverage threshold, penetration test, staging data), and approval of the Not in Scope list.
