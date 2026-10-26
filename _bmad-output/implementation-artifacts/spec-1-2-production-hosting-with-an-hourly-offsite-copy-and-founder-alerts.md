---
title: 'Story 1.2: Production hosting with an hourly offsite copy and founder alerts'
type: 'feature'
created: '2026-10-25'
status: 'done'
baseline_commit: '509cf53d92e045f7cbfbf8792b340c4625cc296c'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/implementation-artifacts/epic-1-context.md'
  - '{project-root}/_bmad-output/implementation-artifacts/spec-1-1-scaffold-the-project-with-its-rules-enforced.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Nothing yet runs in production, nothing copies data off the host, and nobody is told when a job or a backup fails. Receipts are evidence of payment, so NFR-5 demands a 1-hour recovery point, a 4-hour recovery time and a quarterly restore drill (AD-20).

**Approach:** Make the repository deployable to Laravel Cloud and document the exact environment settings; add an hourly job that writes an encrypted logical dump and an object-storage copy to an S3-compatible bucket at a second provider; add founder alerts for failed queue jobs, failed or missing backups and spend; and add a restore runbook whose steps are scripted and proven by an automated drill into a clean environment.

## Boundaries & Constraints

**Always:**
- Code uses only standard PostgreSQL, the S3 API and standard Laravel features; cache, locks, rate limits and sessions use the database driver (already set in Story 1.1).
- The offsite target is any S3-compatible bucket outside Laravel Cloud, configured by environment; the provider is the founder's choice.
- Dumps are encrypted before they leave the runner, to a public key; the private key is held offline by the founder and never stored with the backups or in the repository.
- Secrets live in a secret store (Laravel Cloud's environment secrets, or the runner's secret store) and never in the repository; a CI check fails on committed secrets.
- Alerts name what failed and when (WAT and UTC), and a burst of the same failure sends one alert per kind per 15 minutes.
- Alerting never depends on the queue it reports on: alerts are sent synchronously, and a failure to alert is logged, never thrown.
- The runbook states the 4-hour target, records each drill's measured time, and says the drill repeats each quarter.
- Staging never receives production data; the drill restores into a throwaway environment.

- **Decision (owner, 25 October 2026): the hourly copy runs in a scheduled GitHub Actions workflow**, outside Laravel Cloud. It installs `pg_dump` 17, `age` and `rclone`, reaches the database over TLS through its public endpoint, and reads credentials from GitHub's secret store. Late starts by GitHub's scheduler are accepted and covered by the missing-copy alert.
- **Decision (owner, 25 October 2026): alerts reach the founder by email through an SMTP provider set in the environment, plus a heartbeat monitor (such as Healthchecks.io).** The workflow pings the heartbeat on success and its failure endpoint with the failed step and time on failure; the monitor emails the founder on failure and when no success arrives within 70 minutes. App-side alerts (failed jobs) go by email.
- **Decision (owner, 25 October 2026): spend alerts are each provider's own billing alert**, set up by the founder from a checklist in the runbook; no spend code in this story.
- **Decision (owner, 25 October 2026): the spec is kept whole** at about 2,300 tokens.
- **Decision (owner, 26 October 2026): an alert storm sends the first alert at once and one follow-up stating the count when the 15-minute window closes**, so the founder hears within seconds and never gets more than one alert per kind per window. This is how the "Alert storm" row below is read.
- **Decision (owner, 26 October 2026): copy names carry seconds** (`integrator-YYYYMMDDTHHMMSSZ.dump.age`), so two runs in the same minute never overwrite each other.

**Never:**
- No Laravel Cloud-only package or API in application code.
- No real accounts, buckets or secrets created by the build; provisioning steps are written for the founder to follow.
- No audit trigger or database roles yet (Story 1.6 extends the runbook for them).
- No WhatsApp alerts (Epic 11) and no staff-facing alert screens.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Hourly copy | Hour boundary passes | One `integrator-YYYYMMDDTHHMMSSZ.dump.age` in the offsite bucket, plus object storage mirrored under `objects/` | N/A |
| Copy fails | Dump, encryption or upload exits non-zero | Founder alert "Backup failed" with the step and time; no partial file left under the final name | Retried next hour |
| Copy missing | No successful copy for 70 minutes | Founder alert "Backup missing" with the last success time | Raised by a check that does not run inside the failed job |
| Failed job | A queued job fails permanently | Founder alert naming the job class, queue and time | Throttled per kind per 15 minutes |
| Alert storm | 50 jobs fail in one minute | One alert, stating the count | N/A |
| Alert channel down | Sending the alert throws | Error logged as JSON with request id; the job failure itself is unaffected | N/A |
| Drill | Latest copy restored into a clean database | App serves `/api/v1/health` and the restored rows; elapsed time printed and recorded | Drill fails loudly if any step fails |

</frozen-after-approval>

## Code Map

- `config/database.php`, `config/cache.php`, `config/session.php`, `config/queue.php` -- database driver already set in Story 1.1; do not change.
- `config/filesystems.php` -- add an `offsite` S3 disk (endpoint, region, bucket, path-style from environment); `s3` stays for Laravel Cloud object storage.
- `config/logging.php` -- `json` and `json_stderr` channels exist; production should log to `json_stderr`.
- `app/Modules/Shared/` -- kernel home for cross-cutting operations code (it already holds `Logging/`, `Providers/`, `Support/`); founder alerts go in `Shared/Alerts/`.
- `app/Modules/Shared/Contracts/Clock.php` -- all time reads, including alert timestamps and throttling.
- `routes/console.php` -- the scheduler entry point.
- `compose.yaml` -- add a MinIO service (S3 API) so the backup and drill scripts run locally and in CI without a real provider.
- `.github/workflows/ci.yml` -- merge gate from Story 1.1; extend, do not restructure.
- `tests/Architecture/*` -- every new class is scanned (AD-1, AD-2, Clock-only time, `HasFake`); comply, do not exempt.

## Tasks & Acceptance

**Execution:**
- [x] `docs/operations/laravel-cloud.md` -- environment settings (EU West London, Serverless Postgres 17, queue, scheduler, object storage, build and deploy commands, required secrets) for the founder to apply -- AC 1.
- [x] `scripts/ops/backup.sh` -- `pg_dump --format=custom`, encrypt with `age` to the configured public key, upload under a temporary name then rename, `rclone sync` object storage to `objects/`, ping the heartbeat on success; skip the ping when no heartbeat URL is set (local and CI) -- hourly copy.
- [x] `.github/workflows/offsite-backup.yml` -- hourly `schedule` plus `workflow_dispatch`; runs `scripts/ops/backup.sh` with secrets referenced by name only; on any failure pings the heartbeat's failure endpoint with the step and time -- owner's runner decision.
- [x] `app/Modules/Shared/Alerts/FounderAlerts.php`, `FounderAlert` mailable, `config/alerts.php` -- synchronous send, 15-minute per-kind throttle on the database cache, never throws -- alert rules.
- [x] `app/Providers/AppServiceProvider.php` -- listen to `JobFailed` and send a failed-job alert -- failed jobs.
- [x] `scripts/ops/restore.sh` + `docs/operations/restore-runbook.md` -- fetch latest copy, decrypt, `pg_restore` into a clean database, restore objects, start the app, check health and a row count, print elapsed time; runbook with the 4-hour target, drill log table and quarterly schedule -- AC 3.
- [x] `compose.yaml` (MinIO), `tests/Ops/drill.sh`, CI job `drill` -- back up a seeded local database to MinIO and restore it into a fresh database, asserting health and data -- proves the runbook.
- [x] `docs/operations/spend-alerts.md` -- checklist of billing alerts to set at Laravel Cloud, the backup provider, GitHub Actions and the SMTP provider, with later providers (SMS, NIN, WhatsApp) marked for their epics -- owner's spend decision.
- [x] `docs/operations/alerts.md` -- heartbeat monitor setup (70-minute grace, founder's email) and the SMTP and founder-address secrets -- owner's alert decision.
- [x] `.github/workflows/ci.yml` -- secret scan (gitleaks) and the drill job -- secrets AC.
- [x] Tests -- alert throttle, storm count, channel failure, failed-job listener, WAT and UTC in the message -- I/O matrix.

**Acceptance Criteria:**
- Given the settings document, when the founder applies it, then production runs on Laravel Cloud EU West London with Serverless Postgres 17, queue, scheduler and object storage, and every secret it lists is entered in the host's secret store.
- Given the drill job, when CI runs, then a seeded database and its objects are backed up encrypted to MinIO, restored into a clean database, the app serves them, and the elapsed time is printed.
- Given a committed secret, when CI runs, then the secret scan fails the build.

## Verification

**Commands:**
- `docker compose run --rm app composer test` -- expected: all pass, including the alert tests.
- `bash tests/Ops/drill.sh` -- expected: restore succeeds, health check passes, elapsed time printed.
- `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` -- expected: clean.

## Implementation Notes

- MinIO no longer publishes images on Docker Hub or Quay, so `compose.yaml` uses the community build `pgsty/minio:RELEASE.2026-08-04T00-00-00Z` (same server, S3 API on 9000, console on 9001).
- The `app` image (`docker/php/Dockerfile`) now carries `postgresql-client-17` from the PostgreSQL apt repository (bookworm's 15 cannot dump a 17 server), `age` and `rclone`, so the backup, restore and drill scripts run in it locally and in CI. `league/flysystem-aws-s3-v3` was added so the `s3` and `offsite` disks work; the drill uses it to prove the app reads a restored object.
- Throttle: the first occurrence of a kind sends at once; later ones inside the 15-minute window are held and counted (with a per-job-and-queue breakdown); after the window closes one follow-up states the count, sent by `alerts:send-held` (scheduled every minute, not queued) or by the next occurrence. So the 50-job storm gives one alert in the window and one follow-up "49 more ... 50 in all". All failed jobs share the kind `failed_job`, so a storm across job classes is still one alert. Throttle state and its lock live on the database cache store (`config/alerts.php`).
- Failed-job alerts name the job class, queue, connection and exception class, never the exception message (it may hold personal data); the request id points to the log line.
- Added `php artisan alerts:test` so the founder can check the SMTP settings after deploying.
- "Backup missing" is raised by the heartbeat monitor's 70-minute grace (owner's decision), so there is no app code for it.
- `offsite-backup.yml` runs on schedule only once the repository variable `OFFSITE_BACKUP_ENABLED` is `true`, so it does not fail hourly before the founder enters the secrets; manual runs always go. The failed step is passed to the heartbeat's `/fail` body through a step file.
- The offsite credentials are documented for GitHub only, never for Laravel Cloud's app environment, so the app cannot delete its own backups. The object mirror uses `rclone sync --backup-dir objects-replaced/<time>` so a deletion in production does not destroy the only offsite copy within the hour.
- gitleaks runs from its pinned image over the full history; `.gitleaks.toml` allowlists two tooling paths with false positives (the BMad file manifest's hashes and vendored test-pattern guides). A CI step plants a generated token in a throwaway repository and asserts the scan exits 1.
- The drill also asserts the copy is age ciphertext (no plain row data), that no `.partial` file is left, and that `restore.sh` refuses a database that has tables. Local measured restore: 19 seconds (78 seconds whole drill), recorded in the runbook's drill log.
- Review fixes (26 October 2026): the copy name now carries seconds, `integrator-YYYYMMDDTHHMMSSZ.dump.age` (the frozen matrix shows `HHMMZ`; two copies in one minute overwrote each other). Throttle state is decided and saved under the lock and the email sent after it is released, so a failed send still throttles; a down throttle store gets one unthrottled send. Alerts carry the job uuid and a plain-text part; follow-ups carry the held occurrence's request id. `alerts:send-held` runs `onOneServer()->withoutOverlapping(5)`; each `alerts:test` run is its own kind. `backup.sh` refuses to mirror an empty source over a non-empty `objects/`. `restore.sh` blames `pg_restore` when `age` exits 141, records once (main shell only), treats any relation, type or user schema as "not clean", and takes `RESTORE_APP_CHECK_CMD` to compare the app's row count. The drill adds a failed-backup case and newest-copy selection; CI adds a `backup-workflow` job on ubuntu-24.04 that runs the workflow's install step and the success ping; gitleaks' planted check asserts `--exit-code 42`. The backup step has its own timeout and the notify step also runs on cancel. Docs require bucket versioning or object lock, the non-pooled database endpoint and the wake-up cost.

## Spec Change Log

## Review Triage Log

Review pass 1 (blind-hunter BH, edge-case-hunter EC, verification-gap VG), 26 October 2026.

| # | Layer | Finding | Verdict | Evidence | Route |
|---|-------|---------|---------|----------|-------|
| 1 | BH, EC | The mail send runs inside a 10 s lock and the window is saved only after a successful send: slow SMTP lets the lock expire (duplicate alerts), waiters time out after 5 s (occurrences dropped from the held count), and a down channel saves no window, so every failed job blocks on SMTP | medium | `raise()` calls `send()` inside `lock(...,10)->block(5,...)` before `save()` | patch |
| 2 | BH, EC | The shared pending list is read-modified-written under a per-kind lock only | low | only `failed_job` and `test` kinds exist; fix needs a second lock or key scheme | reject |
| 3 | BH | The failed-job alert lacks the job's uuid, which `alerts.md` tells the founder to pass to `queue:retry` | low | facts hold class, queue, connection, exception class only; direct addition | patch |
| 4 | BH | The alert's request id may not match the failed job's log lines | maybe-false | hidden Context is carried into queued jobs; whether it is still hydrated at `JobFailed` needs a worker test; low if true | reject |
| 5 | EC | The held follow-up prints the scheduler's own request id, not the failing job's | low | `sendHeld()` calls `factLines()`, which reads `RequestId::current()` in the scheduler; direct: store it in `last` | patch |
| 6 | BH | Restoring an older dump brings back today's objects mirror, unmentioned | low | `restore.sh` always copies `objects/`; direct runbook note | patch |
| 7 | BH, EC | The production restore passes on any healthy app, not proof it reads the restored database | medium | health is checked over HTTP and rows via `psql`; only the drill reads through the app | patch |
| 8 | BH | `restore.sh` blames "decrypt" when `pg_restore` fails, because `age` then exits 141 on the broken pipe | medium | `statuses[0]` is checked first; `--exit-on-error` stops reading | patch |
| 9 | BH, VG | `backup.sh` failure paths (step file, partial cleanup, no final-named copy) are never run | medium | pre-verified gap: the drill runs only the success path | patch |
| 10 | BH, EC | Two runs in the same minute give the same name and the second overwrites the first | low | minute-precision `STAMP`; `moveto` replaces; direct: seconds in the name | patch |
| 11 | BH | The setup docs omit using the direct (non-pooled) Postgres endpoint for `pg_dump`, and the cost of waking a scale-to-zero database hourly | medium | `laravel-cloud.md` says "the public connection string"; `spend-alerts.md` has no such line | patch |
| 12 | BH, VG | `.env.example` points the offsite endpoint at `127.0.0.1` (wrong inside the container), lacks the path-style variable, and the `offsite` disk is unused while the docs say the drill uses it | low | grep finds the disk only in `config/filesystems.php`; drill uses rclone | patch |
| 13 | BH | Spec and sprint status disagree | false | the workflow syncs sprint status at its next step | reject |
| 14 | BH | 35-day retention and a delete-capable offsite key mean a leaked secret or late-noticed corruption loses every copy; versioning or object lock is only "if offered" | medium | `laravel-cloud.md` lifecycle and key scope as written | patch |
| 15 | BH | Workflows holding production secrets use actions by tag, not commit SHA | low | supply-chain hardening; pinning needs SHA lookups per action | reject |
| 16 | BH | `alerts:send-held` lacks `onOneServer()` | low | `everyMinute()->withoutOverlapping()` only; direct addition | patch |
| 17 | BH | The alert email has no plain-text part | low | `content()` sets only `htmlString`; `plainText()` exists; direct | patch |
| 18 | EC | When the database (the cache store) is down, a failed-job alert is only logged | medium | store or lock failure is caught and logged before any send | patch |
| 19 | EC | `withoutOverlapping()` defaults to a 24-hour mutex, so a crashed run stops follow-ups for a day | low | Laravel default expiry 1440 minutes; direct `withoutOverlapping(5)` | patch |
| 20 | EC | A second `alerts:test` within 15 minutes is throttled and reported as a failure | low | fixed kind `test`; direct: unique kind per run | patch |
| 21 | EC | The planted-secret CI check accepts exit 1, which gitleaks also returns on a fatal error | low | `test "$status" -eq 1`; direct: `--exit-code` with a distinct value | patch |
| 22 | BH, EC | A timed-out or cancelled backup job skips the `if: failure()` notify step, and `pg_dump` has no timeout | medium | job `timeout-minutes: 45` cancels; no step-level timeout | patch |
| 23 | EC | A failed success-ping reports "Backup failed at step: heartbeat" for a backup that succeeded | low | the step name says heartbeat, and a down monitor cannot receive `/fail` either | reject |
| 24 | EC | An empty or wrong source bucket makes `rclone sync` move the whole mirror to `objects-replaced/`, deleted after 90 days | medium | `rclone sync` with `--backup-dir` and no delete guard | patch |
| 25 | EC | `set -E` runs `on_error` inside command substitutions, writing duplicate "failed" rows | low | ERR trap inherited by `$(...)`; direct `BASH_SUBSHELL` guard | patch |
| 26 | EC | The clean-target check counts tables only | low | views, sequences or types pass as clean; direct query change | patch |
| 27 | VG | Latest-copy selection is untested; the drill bucket holds one copy | medium | pre-verified gap | patch |
| 28 | VG | The backup workflow's own install step, `PG_DUMP` path and pings never run in CI | medium | pre-verified gap: the drill uses the Docker image | patch |
| 29 | VG | The `database` default of `alerts.cache_store` is unpinned; tests override it | medium | pre-verified gap: `beforeEach` sets it | patch |
