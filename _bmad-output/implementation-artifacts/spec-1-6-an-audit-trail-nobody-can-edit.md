---
title: 'Story 1.6: An audit trail nobody can edit'
type: 'feature'
created: '2026-11-09'
status: 'done'
baseline_commit: 'f7b57f50f7797f16fae49e066375d6bedc8fd6ef'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/implementation-artifacts/epic-1-context.md'
  - '{project-root}/_bmad-output/implementation-artifacts/spec-1-5-each-role-sees-only-its-own-work.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** The audit trail is insert-only by convention alone: any database session can update or delete rows, nothing chains or checks them, and no screen shows them (FR-53, NFR-5, AD-21).

**Approach:** Protect the audit tables with database triggers, seal rows into a hash chain with one scheduled writer that alerts the founder when it fails or stops, give Admins an Audit trail page with a chain check and "Look up", add a read-only History panel to each staff record, record the least-privilege role decision, and extend the restore runbook.

## Boundaries & Constraints

**Always:**
- A trigger rejects `UPDATE`, `DELETE` and `TRUNCATE` on the audit rows and on the seals, for every session, including the table owner.
- Sealing never changes an audit row: each seal is a new insert-only row holding the entry's sequence, its hash and the previous seal's hash. One writer seals in sequence order, and never past an entry whose transaction is still open, so a later commit with a lower sequence cannot slip behind the chain.
- A failed sealing run, or audit rows left unsealed past the grace period, raises a founder alert.
- The chain check recomputes every hash from the stored rows and reports "intact" with the last sealed sequence, or the first sequence number where it breaks (a changed, missing or extra row).
- No audit row holds a personal value: the writer refuses or replaces any change to a column on its module's personal-data list with "changed", whoever built the changes. `acting_as` (a role) and `acting_for` (a person) can be written.
- The Audit trail page and "Look up" (by an entity or actor identifier) are Admin-only, refused by the server; the page sits in the Watch group of the rail.
- Each staff row offers History: who did what and when, newest first, names shown for staff actors, values only where the row holds them, read-only, with no Edit or Delete, readable by keyboard and screen reader.
- The restore runbook and the quarterly drill recreate the triggers before any write, and the drill proves the trigger rejects an `UPDATE`.

- **Decision (owner, 9 November 2026): least-privilege role — record the fallback.** A decision note in the repository says that on Laravel Cloud the app's role owns the tables and could disable the trigger, so tamper evidence rests on the trigger, the hash chain and the hourly offsite copy; it cites the spike findings and says to revisit if Laravel Cloud offers separate runtime database users. No runtime role is built.
- **Decision (owner, 9 November 2026): missed runs — in-app check plus external heartbeat.** The app alerts when a row stays unsealed past 30 minutes, and each successful run pings a Healthchecks.io check whose URL is a secret (unset means no ping, as with the backup heartbeat); the owner creates the check.
- **Decision (owner, 9 November 2026): the spec is kept whole** at about 1,800 tokens.

**Never:**
- No reveal of NIN or bank details (later epics), no Approvals or "acting as" prompt (Epic 2), no export of the trail.
- No editing, deleting or re-sealing of rows from any screen or command.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Tamper | Any session runs `UPDATE`, `DELETE` or `TRUNCATE` on audit rows or seals | Rejected by the trigger | Statement fails; nothing changes |
| Seal | Unsealed rows exist | One run seals them in sequence order, each linked to the previous | N/A |
| Open transaction | A lower-sequence row commits after a higher one | Neither is sealed until both are committed; chain stays in order | N/A |
| Seal fails | Sealing run throws | Founder alert names the failure | Run retried next schedule |
| Seal missed | A row is unsealed past the grace period | Founder alert | N/A |
| Check intact | Admin runs the chain check | "The chain is intact up to sequence N." | N/A |
| Check broken | A row's stored values no longer match its seal (trigger disabled in a test) | Names the first broken sequence | N/A |
| Personal field | An Action passes a staff email change with values | Row records `"changed"` | N/A |
| Wrong role | Finance or Operations opens the Audit trail page, or calls Look up | Refused in words naming who can | 403 in the console shell |
| History | Admin opens History on a staff row | Read-only list from the trail | N/A |

</frozen-after-approval>

## Code Map

- `app/Modules/Shared/Database/Migrations/2026_10_26_000000_create_audit_entries_table.php` -- `audit_entries` with `bigserial sequence`, `acting_as uuid`; new migration adds the seals table, triggers, and turns `acting_as` into a role string.
- `app/Modules/Shared/Actions/WriteAuditEntry.php` -- the only writer (insert); add the personal-field guard and the writer side of the sealing lock.
- `app/Modules/Shared/Audit/{Actor,AuditChanges,PersonalFields}.php` -- reuse; `Actor::actingAs()` takes a role.
- `app/Modules/Shared/Queries/{AuditTrail,AuditEntry}.php` -- reads for History, Look up and the page.
- `app/Modules/Shared/Alerts/FounderAlerts.php` -- `raise(kind, title, facts)`; add seal-failed and seal-overdue kinds.
- `routes/console.php` -- schedule definitions (`onOneServer`, `withoutOverlapping`); add sealing.
- `app/Modules/Staff/Capabilities/StaffCapabilities.php` -- add `audit.view` (Admin only: declare with no groups).
- `app/Modules/Staff/Filament/{ConsolePanelProvider,ConsoleRail,GuardedByCapability}.php` -- register the new page explicitly in Watch; use the trait.
- `app/Modules/Staff/Filament/StaffResource/ListStaff.php`, `views/staff-list.blade.php` -- add a History dialog per row, like the existing row actions.
- `tests/Flows/StaffDeactivationRaceTest.php:38` -- truncates `audit_entries`; must stop once `TRUNCATE` is rejected.
- `docs/operations/restore-runbook.md` -- step 7 (line 116) is the placeholder; drill notes lines 145-146; `tests/Ops/drill.sh` (CI job `drill`).
- `_bmad-output/planning-artifacts/architecture/reviews/review-verification.md:76-90` -- the role spike findings to cite.
- `tests/Architecture/`, `tests/Accessibility/console.spec.ts`, `database/factories/TestStateScenarios.php` -- rule and a11y patterns.

## Tasks & Acceptance

**Execution:**
- [x] `app/Modules/Shared/Database/Migrations/` -- seals table, triggers, `acting_as` as role -- the protection.
- [x] `app/Modules/Shared/Audit/` and `Actions/` -- sealing Action and command, chain check Query, personal-field guard -- the chain.
- [x] `routes/console.php`, `FounderAlerts` -- schedule, failure and overdue alerts -- the alerting.
- [x] `app/Modules/Staff/` -- `audit.view`, the Audit trail page (recent rows, Look up, chain check), History on staff rows -- the screens.
- [x] `docs/decisions/` -- the least-privilege fallback decision -- AD-21's recorded outcome.
- [x] `config/alerts.php`, `.env.example`, `docs/operations/{alerts,laravel-cloud}.md` -- the seal heartbeat secret and how to create its check.
- [x] `docs/operations/restore-runbook.md`, `tests/Ops/drill.sh` -- recreate triggers before writes; drill checks rejection.
- [x] Tests (Pest) -- every matrix row, including a real concurrent commit-order case.
- [x] `tests/Accessibility/console.spec.ts` -- the Audit trail page, the chain check result and History, with no axe violations.

**Acceptance Criteria:**
- Given a keyboard or screen-reader user, when they run the chain check, then the result is announced.
- Given the History dialog, when it opens, then focus moves into it, its title names the person, and it has no Edit or Delete.

## Design Notes

- Seal hash: SHA-256 over the previous seal's hash and a canonical encoding of the entry's stored columns; the first seal links to a fixed genesis value. Sequence gaps from rolled-back transactions are normal; the chain links seals, not sequence numbers.
- Commit-order safety: the writer takes a shared transaction-level advisory lock; the sealer briefly takes it exclusively to read the highest committed sequence, then seals up to it.

## Implementation Notes

- Protection: `Shared/Database/audit-protection.sql` (read by `Shared/Audit/AuditProtection`) holds one statement-level `BEFORE UPDATE OR DELETE OR TRUNCATE` trigger per table (`audit_entries_insert_only`, `audit_seals_insert_only`), `ENABLE ALWAYS`, raising "The audit trail is insert-only: ..." (SQLSTATE 42501). Statement-level, so even an `UPDATE ... WHERE false` is refused. The migration `2026_11_09_000000_seal_and_protect_audit_entries` creates `audit_seals` (uuid id, unique `sequence`, `hash`, `previous_hash`, `sealed_at`; no foreign key, so the chain check, not a key, reports a missing entry), runs the SQL and turns `acting_as` into `varchar(32)`.
- Chain: `Shared/Audit/AuditSeal` (genesis of 64 zeros; SHA-256 over the previous hash, a newline and a JSON list of the stored columns read in SQL as text, `occurred_at` as UTC microseconds, `changes::text`). `Shared/Actions/SealAuditEntries` takes `pg_advisory_xact_lock(WRITERS_LOCK)` exclusively with a 2 s `lock_timeout` to read the highest committed sequence (a timeout returns `busy`, sealing nothing), then seals in batches of 500 under `pg_advisory_xact_lock(SEALER_LOCK)`, re-reading the last seal. Only transaction-level advisory locks, so it works through Laravel Cloud's pooler. `WriteAuditEntry` takes `pg_advisory_xact_lock_shared(WRITERS_LOCK)` before each insert and replaces any change to a personal column of the subject table with "changed".
- Check: `Shared/Queries/AuditChain::check()` walks a full outer join of seals and entries up to the last seal: no seal = extra, no entry = missing, a wrong link or hash = changed. `unsealed()` feeds the overdue alert.
- Alerting: `Shared/Audit/AuditSealing` behind `php artisan audit:seal` (every 5 minutes, `onOneServer`, `withoutOverlapping`): a throw raises `audit_seal_failed` (error class only), a row unsealed for more than `alerts.audit_seal_grace_minutes` (30) raises `audit_seal_overdue`, and a completed (not busy) run GETs `AUDIT_SEAL_HEARTBEAT_URL` when set.
- Screens: `audit.view` (Admin only); `Staff/Filament/Pages/AuditTrail` at `/console/audit-trail` in Watch (latest 50 rows, Look up by subject, actor or acting-for id, chain check in a `role="status"` region); `ListStaff::historyAction()` (title "History of <name>", Close only) with `staff::audit-history`; words in `Staff/Filament/AuditWords`.
- Restore: `scripts/ops/restore.sh` reruns the SQL right after `pg_restore` and proves `UPDATE` and `DELETE ... WHERE false` are refused on both tables; `tests/Ops/drill.sh` seeds an audit row, drops the trigger before the backup, and checks the restored table refuses an `UPDATE` and keeps the row.
- Tests that tamper with the trail on purpose live outside `app/Modules` (the single-write-path rule scans module tests): `tests/Feature/Audit/{AuditProtection,AuditChain,AuditSealing}Test.php`, `tests/Flows/AuditTrailPageTest.php`, `tests/Flows/AuditSealingOrderTest.php` (the forked commit-order case).
- Tests that must commit (`StaffDeactivationRaceTest`, `AuditSealingOrderTest`) no longer truncate the audit trail; they set `RefreshDatabaseState::$migrated = false` so the next test migrates fresh. The Story 1.4 rollback test now rolls back four steps.

## Spec Change Log

## Review Triage Log

Review pass 1 (blind-hunter BH, edge-case-hunter EC, verification-gap VG), 11 November 2026.

| # | Layer | Finding | Verdict | Evidence | Route |
|---|-------|---------|---------|----------|-------|
| 1 | EC, BH | The sealer's queued exclusive wait on the writers' lock stalls every audit write up to 2s per run and can deadlock with a writer waiting on a row lock | medium | Postgres queues new shared advisory requests behind a waiting exclusive one | patch: try-lock loop |
| 2 | BH, EC, VG | Removing the newest seals (and re-sealing rewritten rows) leaves the check "intact"; nothing records where the chain ended | medium | `check()` ends at `max(sequence)` of seals | patch: chain head in ping body and log; decision note says so |
| 3 | BH | A disabled or dropped trigger goes unnoticed | medium | nothing reads `pg_trigger` | patch: each run checks, alerts |
| 4 | BH | Look up scans the whole table | low | no index leads with `subject_id`/`actor_id`; none on `acting_for` | patch: indexes |
| 5 | BH | Seal column says "Sealed" for an extra row inside the chain | low | derived from `sequence <= lastSealed` | patch |
| 6 | BH | A refused Look up keeps showing the previous identifier's rows | low | `lookedUp` unchanged on error | patch |
| 7 | BH, EC | Page, Look up and History cut at 50/200 rows silently; History can drop account creation | low | fixed limits, count shown as total | patch: say when more exist |
| 8 | BH, EC | A pre-1.6 copy restores with `audit_entries` unprotected; the drill never proves the `audit_seals` trigger | low | `audit_tables == 1` branch only warns; drill drops one trigger | patch |
| 9 | BH | Busy runs log nothing (alerts.md cites the message); failures never ping `/fail` | low | `warn()` is console output only | patch |
| 10 | VG | Look up's `acting_for` match and "acting as … for …" wording untested | low | no fixture sets them | patch |
| 11 | VG, BH | Heartbeat failure and overdue-check failure paths untested | low | heartbeat always faked OK | patch |
| 12 | VG | No test seals more than one batch | low | all tests write a few rows | patch (cheap) |
| 13 | BH, EC | The chain check walks the whole trail inside one request | low | `DB::cursor` streams; at launch volume a year of rows checks in seconds; checkpoints add a stored state | reject |
| 14 | BH | Tampering is found only when an Admin runs the check | false | the intent asks for an Admin-run check; rows 2 and 3 add the automatic signals | reject |
| 15 | BH | History is guarded by `staff.manage`, not `audit.view` | low | both Admin-only today; no holder can diverge without a registry change | reject |
| 16 | BH, EC | Rolling the migration back drops seals and nulls role `acting_as` | low | `down()` runs only in tests and local rollbacks | reject |
| 17 | BH | The personal-field guard checks top-level keys of the subject table only | false | the rule is the subject module's list; every caller passes field => change | reject |
| 18 | BH | Chain check reads counts and the walk in separate snapshots | false | the walk is bounded by the `lastSealed` read first; later seals are excluded | reject |
| 19 | BH | Spec says in-review while sprint status says in-progress | false | the workflow moves sprint status to review at presentation | reject |
| 20 | EC | Converting existing uuid `acting_as` to `varchar(32)` fails the deploy | false | no Action sets `acting_as`; only test probes write it | reject |
| 21 | EC | A role over 32 characters fails the insert | low | roles are short words; no caller passes free text | reject |

## Verification

**Commands:**
- `docker compose run --rm --no-deps -T app sh -c 'php artisan config:clear -q; php artisan test'` -- expected: all pass (not `composer test`).
- `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` -- expected: clean.
- `npm run lint`, `npm run typecheck`, `npm test`, `npm run build` -- expected: clean.
- The accessibility run (Playwright + axe) -- expected: no violations; run by the orchestrating session, never by the implementer.
