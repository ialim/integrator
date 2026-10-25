---
title: 'TEA Test Design → BMAD Handoff Document'
version: '1.0'
workflowType: 'testarch-test-design-handoff'
inputDocuments:
  - _bmad-output/test-artifacts/test-design-architecture.md
  - _bmad-output/test-artifacts/test-design-qa.md
  - _bmad-output/planning-artifacts/epics.md
sourceWorkflow: 'testarch-test-design'
generatedBy: 'TEA Master Test Architect'
generatedAt: '2026-10-14'
projectName: 'integrator'
---

# TEA → BMAD Integration Handoff

## Purpose

Carries the test design's quality requirements into the epics and stories. The epics already exist (`epics.md`, approved 14 October 2026), so this handoff lists what each epic and story should add or confirm, rather than feeding a fresh breakdown.

## TEA Artifacts Inventory

| Artifact | Path | BMAD integration point |
|---|---|---|
| Architecture test design | `_bmad-output/test-artifacts/test-design-architecture.md` | Story 1.1 blockers; epic quality gates |
| QA test design | `_bmad-output/test-artifacts/test-design-qa.md` | Story acceptance criteria; test priorities |
| Risk assessment | Embedded in both, R-01 to R-21 | Epic risk classification |
| Coverage strategy | Embedded in the QA design, S-01 to S-62 | Story test requirements |

## Epic-Level Integration Guidance

### Risk references

| Epic | High risks it carries |
|---|---|
| 1 Staff console and app shell | R-11 (test database), plus blockers TC-1 to TC-4 |
| 2 Catalogue and rate | R-01 (pricing drift), R-08 (timers) |
| 3 Sign up and browse | R-03 (visibility) |
| 4 Get approved | R-07 (NIN leakage) |
| 6 Projects and Proposals | R-05 (sync), R-06 (device prices), R-10 (PDF leakage) |
| 7 Ordering | R-02 (races), R-04 (lock boundaries begin) |
| 8 Payments and Credit Notes | R-02, R-04, R-09 (bank credit reuse) |
| 9 Deliveries | R-02 (completion and serials) |

### Quality gates

- **Every epic:** P0 scenarios for its area pass at 100%; the registry suite (S-20) is extended to its new surfaces; its NFR-10 transitions have two-racer and replay tests.
- **Epic 1:** cannot close until TC-1 to TC-4 exist and CI fails on a non-PostgreSQL test database.
- **Epic 2:** cannot close until every addendum vector is a named passing test.
- **Epic 8:** cannot close until the Price Lock decision table (S-11) passes in full.
- **Before launch (end of Epic 15):** R-01 to R-11 mitigated with evidence; k6 baseline recorded; restore drill passed.

## Story-Level Integration Guidance

### P0/P1 scenarios that should be acceptance criteria

| Story | Add or confirm | Scenario |
|---|---|---|
| 1.1 | PostgreSQL 17 is the only test database and CI fails otherwise; the `Shared` Clock is injectable; test-only factories and seeding exist in local and test environments only | S-41, TC-1, TC-2, TC-4 |
| 1.4 | Provider fakes accept scripted outcomes (success, failure, timeout, error), starting with email | TC-3 |
| 2.9 | Pricing and merge vectors live in shared files that the app's tests also read | S-01 to S-05 |
| 2.17 | A command runs one sweep tick on demand; each timer Action is tested before, at and after its deadline and twice | S-31, TC-2 |
| 3.6 | CI fails when a new API resource or sync payload has no registry entry | S-20 |
| 4.5 | A canary NIN never appears in logs, exception reports, queue or outbox payloads or exports; a backup taken before the 30-day deletion cannot decrypt the NIN after it | S-25, S-26 |
| 6.2 | A pull never skips a row committed out of order | S-35 |
| 6.7 | A demoted Manager's device holds no price field after reconnecting | S-23 |
| 6.11 | The forbidden-field test uses planted sentinel values | S-22 |
| 8.10 | The Price Lock rules are tested as a full decision table with the clock controlled | S-11 |

Most other P0 and P1 scenarios already match existing acceptance criteria; the stories above are where a criterion should be added or sharpened.

### Data-testid requirements

Prefer accessible roles and names (the UX requires a visible label for every control), and add `data-testid` only where a role and name are not unique:

- Money areas: `data-testid="money-area"` on any region that holds amounts, so E2E can assert its absence for Technicians.
- Clocks: `data-testid="clock-{subject}"` with the end time in a `datetime` attribute.
- Order, Shipment, Payment and Return Request status labels: `data-testid="status-{entity}"`.
- Sync state: `data-testid="changes-waiting"` and `data-testid="last-check"`.

## Risk-to-Story Mapping

| Risk ID | Category | P×I | Recommended story or epic | Test level |
|---|---|---|---|---|
| R-01 | DATA | 6 | Story 2.8, 2.9; Story 6.5 for the app | Unit |
| R-02 | DATA | 6 | Stories 7.8, 8.5, 8.8, 8.15 to 8.17, 9.10 | Integration |
| R-03 | SEC | 6 | Stories 3.6, 4.10, 5.4 | Integration |
| R-04 | BUS | 6 | Stories 7.12, 8.3, 8.10, 8.11 | Integration, E2E |
| R-05 | DATA | 6 | Stories 6.2, 12.4 | Unit, Integration, E2E |
| R-06 | SEC | 6 | Stories 6.6, 6.7 | E2E |
| R-07 | SEC | 6 | Stories 4.1, 4.5 | Integration |
| R-08 | OPS | 6 | Story 2.17, then every timer story | Integration |
| R-09 | DATA | 6 | Stories 8.4, 8.5, 8.12 | Integration |
| R-10 | SEC | 6 | Story 6.11 | Integration |
| R-11 | TECH | 6 | Story 1.1 | Unit (CI check) |
| R-12 | SEC | 4 | Story 1.6 | Integration |
| R-13 | PERF | 4 | Stories 3.6, 15.5 | E2E, Integration |
| R-14 | BUS | 4 | Stories 4.3, 16.1 | Unit |
| R-15 | TECH | 4 | Each provider interface story | Integration |
| R-16 | BUS | 4 | Story 1.7, then every screen | E2E |
| R-17 | OPS | 4 | Story 12.2 | Component |
| R-18 | OPS | 3 | Stories 1.2, 15.5 | Integration |
| R-19 | SEC | 3 | Stories 2.7, 8.5 | Integration |
| R-20 | SEC | 3 | Story 3.9 | Integration |
| R-21 | DATA | 3 | Stories 10.1, 10.2 | Integration |

## Recommended BMAD → TEA Workflow Sequence

1. **TEA Test Design** (`TD`): this document.
2. **BMAD epics and stories:** done, with the acceptance criteria above added on 25 October 2026.
3. **BMAD Sprint Planning** (`SP`): readiness check and sprint status.
4. **TEA ATDD** (`AT`): failing acceptance tests per story, starting with P0.
5. **BMAD Build** (`BD`): implement each story test-first.
6. **TEA Automate** (`TA`): expand coverage.
7. **TEA Trace** (`TR`): confirm coverage before launch.

## Phase Transition Quality Gates

| From phase | To phase | Gate criteria |
|---|---|---|
| Test Design | Epic and story update | Every high risk has a mitigation and an owner (done) |
| Epic and story update | ATDD | Stories 1.1, 1.4, 2.9, 2.17, 3.6, 4.5, 6.2, 6.7, 6.11 and 8.10 carry the criteria above (done 25 October 2026) |
| ATDD | Implementation | Failing acceptance tests exist for all P0 scenarios of the epic |
| Implementation | Test automation | All acceptance tests pass |
| Test automation | Release | Trace shows at least 80% coverage of P0 and P1 requirements, with P0 at 100% |
