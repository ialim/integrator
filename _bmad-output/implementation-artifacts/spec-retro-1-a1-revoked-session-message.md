---
title: 'A revoked staff session ends with its message, not a 419'
type: 'bugfix'
created: '2026-11-15'
status: 'done'
baseline_commit: 'bc73ed706bf68ec3326d54fe9afa91c7c150676d'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/implementation-artifacts/epic-1-retro-2026-11-15.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** When an Admin deactivates, resets or re-invites a staff member, `RevokeStaffSessions` deletes that member's rows in `sessions`. The member's next request therefore arrives with an empty session. A Livewire POST fails the forgery check and Livewire shows its generic "page expired" (419) prompt. Unsaved form values are lost, and the "no longer active" message (`SignOutInactiveStaff::MESSAGE`) is never shown. A page load lands on the sign-in page with no reason given. Tests miss this because `phpunit.xml` keeps sessions in an array (retro finding X1).

**Approach:**
- When sessions are revoked, remember each revoked session id briefly.
- A console middleware that runs after `StartSession` and before `PreventRequestForgery` recognises a request on a remembered id. It ends that request the way `SignOutInactiveStaff` does: the message is flashed, a Livewire or JSON request gets a 401 with `X-Staff-Session: ended`, and a page load is redirected to sign-in.
- The console script already turns that 401 into "save the draft, reload".

## Boundaries & Constraints

**Always:**
- Use the existing message, header, log event (`staff.session_ended`, reason `session_revoked`) and response shapes. Reuse `SignOutInactiveStaff::signOut` where it is safe with no signed-in user. Otherwise extract the shared part; do not copy it.
- Forget each remembered id once it has been used, and let it expire after the longest staff session (12 hours).
- Write the remembered ids inside the revoking Action's transaction. With the database cache store they then roll back with it.
- Add at least one flow test that runs with `session.driver = database` and `cache.default = database`.

**Never:**
- No migration and no new table.
- No change to who loses access, or when. Security behaviour stays the same: the session is still destroyed.
- Do not weaken or skip `PreventRequestForgery` for any other request.
- Do not change the 12-hour end or the "Stay signed in" flow.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Livewire call after revoke | Member deactivated/reset/re-invited, open page sends a Livewire POST with its old token | 401, `X-Staff-Session: ended`, message flashed; script saves draft and reloads; sign-in page shows the message | N/A |
| Page load after revoke | Same, member opens any `/console` page | Redirect to sign-in, which shows the message once | N/A |
| Second request on the same id | After either of the above | The id is forgotten, so this is treated as any guest request | N/A |
| Unrelated session | Another member's session, or a guest with no session | Unchanged behaviour, including a 419 for a real forgery failure | N/A |
| Revoke rolled back | Revoking Action fails after remembering ids | Nothing is remembered (database cache store) | N/A |

**Owner decisions (15 November 2026):**
- A bare Livewire 419 in the console (no remembered id) is treated as an ended session: the console script saves the draft and reloads. Livewire's "page expired" prompt never shows in the console.
- The spec is kept whole at about 1,800 tokens.

</frozen-after-approval>

## Code Map

- `app/Modules/Staff/Actions/RevokeStaffSessions.php` -- deletes `sessions` rows by id from `staff_sessions`. Remember each id here, keyed to the member id (cache `put` with a 12-hour TTL), before deleting.
- `app/Modules/Staff/Http/Middleware/SignOutInactiveStaff.php` -- `MESSAGE` and `signOut()`, the response to reuse. `signOut()` calls `Filament::auth()->logout()`; check that it is safe for a guest.
- `app/Modules/Staff/Http/Middleware/EndStaffSessionAfter12Hours.php` -- `ENDED_HEADER`; a sibling middleware to model the new one on.
- `app/Modules/Staff/Filament/ConsolePanelProvider.php:92-108` -- the `->middleware([...])` list. Insert the new middleware between `StartSession`/`AuthenticateSession` and `PreventRequestForgery`. Check that `AuthenticateSession` does not act first on the empty session.
- `app/Modules/Staff/Filament/Pages/SignIn.php:38` -- reads the flashed `staff_session.ended`; no change expected.
- `resources/console/session.ts:236-245` -- the Livewire `onError` hook (401 plus header leads to `endSession()`). Also treat a bare 419 as ended (owner decision); `:397` already does this for the extend call.
- `resources/console/session.test.ts` -- the Vitest suite for the script.
- `app/Modules/Staff/Tests/StaffSessionTest.php`, `StaffPageTest.php:298` -- existing tests that check the ended header; follow their style.
- Callers of the revoke (no change): `DeactivateStaffAccount.php`, `ResendInvitation.php`, `ResetSignIn.php`.
- `phpunit.xml:38,46` -- array cache and session. Override per test; do not change globally.

## Tasks & Acceptance

**Execution:**
- [x] `app/Modules/Staff/Http/RevokedStaffSessions.php` (new, small) -- remember/recall/forget revoked ids in the cache under one key prefix -- one place owns the key and TTL
- [x] `app/Modules/Staff/Actions/RevokeStaffSessions.php` -- remember each id before deleting rows -- the record the middleware reads
- [x] `app/Modules/Staff/Http/Middleware/EndRevokedStaffSession.php` (new) -- if the request's session id is remembered: forget it, sign out via the shared response, log -- runs before the forgery check
- [x] `app/Modules/Staff/Filament/ConsolePanelProvider.php` -- register it before `PreventRequestForgery` -- ordering is the fix
- [x] `app/Modules/Staff/Tests/RevokedSessionFlowTest.php` (new) -- database session and cache; sign in, revoke through each of the three Actions, assert the matrix rows -- the regression guard X1 asked for
- [x] `resources/console/session.ts` and `session.test.ts` -- treat a Livewire 419 like the ended 401 (save draft, reload); Vitest for both cases

**Acceptance Criteria:**
- Given the database session driver, when a signed-in member is deactivated, reset or re-invited and their page sends a Livewire POST, then the response is 401 with `X-Staff-Session: ended`, and a following GET of the sign-in page shows `SignOutInactiveStaff::MESSAGE`.
- Given the same, when the member opens a console page instead, then they are redirected to sign-in, and it shows the message.
- Given the full suites, when they run, then all pass with no change to existing assertions.

## Implementation Notes

- **Two things the Code Map did not show, both found by the new flow test:**
  - **Livewire calls do not go through the panel's middleware list.** They go to Livewire's own update route (`web` group, then the panel's *persistent* middleware). So `EndRevokedStaffSession` is also registered with `->persistentMiddleware([...])`. It throws an `HttpResponseException`: Livewire ignores any non-redirect response that persistent middleware returns.
  - **Laravel sorts the auth middleware first.** `SignOutInactiveStaff` is an `AuthenticatesRequests` and moves ahead of `AuthenticateSession` and the new middleware, so it saw a guest first. `StaffServiceProvider::boot` now adds `EndRevokedStaffSession` to the middleware priority just before `AuthenticatesRequests`. The resulting console order: `StartSession`, `ShareErrorsFromSession`, `EndRevokedStaffSession`, `SignOutInactiveStaff`, `AuthenticateSession`, `PreventRequestForgery`, and so on. No other middleware changes place.
- **`PreventRequestForgery` on Livewire calls:** Laravel 13 accepts `Sec-Fetch-Site: same-origin` without a token, so a modern browser's call passes it and reaches the new middleware. A browser that fails it gets a 419; the console script now treats a bare Livewire 419 as ended (owner decision), and the reload then ends the session with its message.
- **Remembered after commit, not inside the transaction (review change):** the ids are written with `DB::afterCommit` (at once when no transaction is open), so a rolled-back revoke remembers nothing on any cache store, not only the database one. The rollback test runs on both the database and array stores.
- **Forgery check really on:** three tests bind a `PreventRequestForgery` whose unit-test bypass is off. A revoked same-origin Livewire call and a revoked `POST session/extend` with an old token both get the ended 401; a stale token on a session nobody revoked still gets a 419.
- **`signOut()` with no signed-in user** is safe: `logout()` tolerates a null user, and the `Logout` listener only forgets the (already deleted) `staff_sessions` row.
- **Flow test isolation:** each request in `RevokedSessionFlowTest` forgets the guard, the in-memory session and Livewire's per-request state, and sends only the session cookie. Otherwise the test session store keeps attributes across requests and hides the bug, as the array driver did.
- **Seen, not changed:** `TrackStaffSession` returns (rather than throws) its sign-out response, so on a Livewire call Livewire ignores it. `SignOutInactiveStaff` still catches that member on the same request, so access is not affected.

## Spec Change Log

## Review Triage Log

| # | Layer | Finding | Verdict | Evidence | Route |
|---|---|---|---|---|---|
| 1 | Blind, Edge | Revoke ids are cached inside the transaction; on a non-database cache store a rolled-back revoke leaves them, falsely ending a live session | medium | `config/cache.php:18` defaults to database and `.env.example` uses it, so production is safe today, but correctness hangs on an env value; `DB::afterCommit` makes it store-independent | patch |
| 2 | Verification gap, Edge | The flow test runs with the forgery check off (`runningUnitTests()` bypass), so the ordering the fix depends on is untested | medium | Pre-verified by the gap layer: `PreventRequestForgery.php:99,130-133`; moving the middleware behind the check still passes | patch |
| 3 | Blind | Bare-419 reload has no loop guard | low | A reload is a GET, which never gets 419; a loop needs an auto-firing Livewire POST that 419s every time (cookies blocked), where the console is unusable anyway. Guard would add state | reject |
| 4 | Blind, Edge | On the sign-in page a 419 now reloads silently instead of Livewire's prompt, losing typed values | low | Livewire's prompt also reloads on confirm, so typed values were lost before too; no draft is saved with no member (`saveDraft` returns on `member === ''`). Matches the owner decision | reject |
| 5 | Blind, Edge | Concurrent requests on one revoked id: only the first gets the reason | low | The frozen matrix says the id is used once. Others become guest requests and land on sign-in, where the flash written by the first is usually shown | reject |
| 6 | Blind | The global priority change reorders every route | false | Priority only orders middleware a route actually has; only console routes carry `EndRevokedStaffSession`. Implementer recorded the resulting order | reject |
| 7 | Blind | The log loses why the session was revoked | false | Before this fix the revoked request logged nothing; the audit trail records deactivate/reset/resend with reason | reject |
| 8 | Blind | The Action depends on an `Http` class | low | `StaffSessionRecords` and `StaffSession` already live in `Http` and are used the same way; no layering rule broken (Larastan clean); no named harm | reject |
| 9 | Blind | The 12-hour TTL may be shorter than an extended session | false | `session.lifetime` is 720 min and a staff session is at most 12 h from its last start, so any session alive at revoke ends within the TTL | reject |
| 10 | Blind | Missing tests: extend route after revoke, several sessions, double log | low | `EndRevokedStaffSession` throws, so `TrackStaffSession` never runs (no double log); the extend route gets the same 401 the script already handles (`session.ts:397`); unlikely to regress | reject |
| 11 | Blind | `remember()` repeats the caller's empty check | low | Both check `[]`; direct deletion | patch |
| 12 | Blind | The 419 comment in `session.ts` is one long line with a dated decision | low | Diff line 480; direct edit | patch |
| 13 | Blind | The spec is not in the reviewed diff | false | By design: the spec goes to the edge-case layer only | reject |
| 14 | Edge | A cache read failure 500s every console route | low | The cache store is the same database as sessions; if it fails, sessions fail first | reject |
| 15 | Edge | A cache write failure fails the whole revoke | low | Same database as the transaction; it fails together | reject |
| 16 | Edge | `pull` is not atomic, so two requests can both read the id | low | Both then sign out and flash the same message; harmless | reject |
| 17 | Edge | On Livewire updates the web group's forgery check runs before the persistent middleware, so an old browser without `Sec-Fetch-Site` gets a 419, not the 401 | low | Real (`HandleRequests.php:102`). The id is not pulled, so the script's 419 reload sends a GET, and that GET shows the message with the draft kept; same end result for the member | reject |

## Verification

**Commands:**
- `docker compose run --rm --no-deps -T app sh -c 'php artisan config:clear -q; php artisan test'` -- expected: all pass
- `docker compose run --rm --no-deps -T app sh -c 'vendor/bin/pint --test && vendor/bin/phpstan analyse --no-progress --memory-limit=1G'` -- expected: clean
- `npm run lint && npm run typecheck && CI=true npm test` -- expected: clean
