# 0003. `/api/v1/health` checks the database and starts no session

- **Status:** accepted
- **Date:** 17 November 2026
- **Decided by:** the owner (Epic 1 retrospective, action A7)
- **Settles:** finding X5 of `_bmad-output/implementation-artifacts/epic-1-retro-2026-11-15.md`

## Context

`/api/v1/health` answered `{"status":"ok"}` without touching the database. When the app called it,
Sanctum's stateful middleware started a database session, so the check depended on the database by
accident, and every check wrote a `sessions` row. Called from curl, the host or `restore.sh`, the same
check said nothing about the database.

## Decision

`/api/v1/health` runs `select 1` and answers `503` with `"code":"database_unavailable"` when the database
does not answer. The route leaves Sanctum's `EnsureFrontendRequestsAreStateful`, so it starts no session
and writes nothing. The app's "not available" page, the host's health check and `restore.sh` now get the
same answer. `/up` stays Laravel's plain "the app answers" check.

## Consequences

- A database outage shows the app's "not available" page, and the host sees it as unhealthy.
- A restore's health step passes only once the app reaches its database.
- Health checks no longer fill the `sessions` table.
- Tests: `tests/Feature/ApiEnvelopeTest.php` covers the 503 answer, and checks that a check from the app's
  own origin writes no session.
