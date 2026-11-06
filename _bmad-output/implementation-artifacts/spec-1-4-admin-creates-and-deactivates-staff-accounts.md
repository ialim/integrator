---
title: 'Story 1.4: Admin creates and deactivates staff accounts'
type: 'feature'
created: '2026-11-03'
status: 'done'
baseline_commit: '4b1662b3964aebe3dc5fcfbdc8cb4a16caff243e'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/implementation-artifacts/epic-1-context.md'
  - '{project-root}/_bmad-output/implementation-artifacts/spec-1-3-staff-sign-in-to-the-console-with-two-steps.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Only the founder's Admin exists, created from the command line. Admins cannot give colleagues their own audited sign-in, and nothing takes access away from someone who leaves (FR-52, FR-76, edge-case row 14).

**Approach:** Add the Staff page where an Admin creates Finance, Operations and Admin accounts that receive an emailed invitation, and deactivates accounts with every session revoked at once. Build the kernel parts the Action contract needs on the way (AD-4): the lock registry with a `Lock{Root}` Action, the declared transition table, the outbox with an idempotent relay, and the email provider interface with a fault-scripting fake (AD-15, TC-3).

## Boundaries & Constraints

**Always:**
- Create and deactivate run as `Staff` Actions that lock the staff member's row first, check the move against the declared `Staff` transition table (Invited → Active → Deactivated), write the audit row naming the acting Admin, and write the outbox event, all in one transaction (AD-4).
- A move not in the table, or a lost race, raises the typed `TransitionRefused`; the screen then shows the current state and who changed it and when, never an error page.
- The last active Admin can never be deactivated; the screen says why in words.
- Deactivation revokes every session of that person at once: their next request is refused and their stored sessions are deleted.
- The invitation email is sent only by an outbox listener, through the Notifications module's `EmailProvider` interface; the listener records a delivery key (event id plus channel) under a unique constraint before calling the provider, so relaying an event twice sends one email.
- The email fake can script, per call, success, definitive failure, timeout or error; the same scripting shape is the pattern every later provider fake follows (TC-3).
- An invited person sets their password (the Story 1.3 password rule) and their authenticator before any console page loads.
- Only an Admin can open the Staff page or call its Actions; the server refuses Finance and Operations.
- The Staff page is a console table with a fixed header and a caption; J, K, Enter and / move around the list; dialogs follow the console rule (a question naming the person as title, a body listing what will happen, red only on the irreversible button); copy follows the voice rules.

- **Decision (owner, 3 November 2026): an invitation link is valid for 72 hours; an Admin can resend it, which issues a new link and voids the old one.**
- **Decision (owner, 3 November 2026): an Admin can "Reset sign-in" for a staff member** who forgot their password or lost their authenticator: it clears both, revokes every session and sends a fresh 72-hour invitation, audited; the last active Admin cannot reset themselves.
- **Decision (owner, 3 November 2026): the spec is kept whole** at about 2,300 tokens.

**Never:**
- No role-scoped rail or capability matrix beyond the Staff page's own Admin check (Story 1.5).
- No deputies, out-of-office or queue reassignment (Epic 4); no reactivation of a deactivated account.
- No WhatsApp or SMS; no Integrator email templates (Epic 11).
- No tamper-proofing of audit rows (Story 1.6).

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Create | Admin enters name, email, role | Account Invited; audit row with the Admin as actor; `StaffAccountCreated` in the outbox | Duplicate email refused beside the field |
| Invitation sent | Relay picks up `StaffAccountCreated` | One invitation email through `EmailProvider` | Provider failure leaves the event for retry; no duplicate after success |
| Relayed twice | Same event relayed again | No second email | Delivery key unique constraint |
| Accept | Invitee opens a link under 72 hours old and unused | Sets password, then authenticator; account Active; audit row | Expired or used link: says so in words, offers to ask an Admin |
| Deactivate | Admin confirms the dialog | Account Deactivated; sessions revoked; audit row | N/A |
| Last Admin | Only one active Admin; deactivate it | Refused; reason shown in words | `TransitionRefused` |
| Race | Two Admins deactivate the same account together | One succeeds; the other sees "already deactivated by <name> at <time>" | No error page |
| Wrong role | Finance or Operations opens the Staff page or calls its Actions | Refused by the server | 403 in the console's words |
| Resend | Admin resends an Invited account's invitation | New link emailed; the old link stops working | N/A |
| Reset sign-in | Admin resets an Active member's sign-in | Password and authenticator cleared, sessions revoked, fresh invitation sent, audit row | Last active Admin resetting themselves is refused in words |
| Fake scripting | Test scripts fail, timeout, error, then success | Each call behaves as scripted, in order | N/A |

</frozen-after-approval>

## Code Map

- `app/Modules/Staff/Models/StaffMember.php` -- from Story 1.3: `is_active` boolean, `canAccessPanel()`, Filament MFA hooks delegating to Actions; replace `is_active` with a `status` column (Invited, Active, Deactivated) and keep the founder Admin Active.
- `app/Modules/Staff/Actions/*` -- Story 1.3 Actions on the `Shared\Actions\Action` base (`transaction()`, `saveAndAudit()`, `audit()`); `StaffPassword` is the one password rule.
- `app/Modules/Shared/Actions/Action.php` -- comment names the lock registry, transition helper and outbox as later additions; add them here.
- `app/Modules/Shared/Audit/*`, `Shared/Queries/AuditTrail.php` -- read who changed a record and when, for the race message.
- `app/Modules/Staff/Filament/ConsolePanelProvider.php` -- register the Staff resource and the invitation routes; required authenticator set-up already blocks every other page.
- `app/Modules/Notifications/` -- empty module; owns `Contracts/EmailProvider` (with `#[HasFake]`), the SMTP adapter on Laravel's mailer (the Story 1.2 SMTP account), the fake, and the delivery-key table.
- `app/Modules/Shared/Providers/ProviderFakes.php` -- binds every declared fake in testing and staging; the new interface needs only its attribute.
- `config/queue.php` -- database queue from Story 1.1; the relay runs as a queued job on the scheduler.
- `tests/Support/StaffConsole.php` -- `deactivate()` updates the row directly; switch it to the new Action.
- `sessions` table -- the database session handler records only the default guard's user id, so staff sessions need their own record at sign-in to be deleted on deactivation.

## Tasks & Acceptance

**Execution:**
- [x] `app/Modules/Shared/Locks/*`, `Shared/StatusTables/*`, `Shared/Events/*` + migrations for `outbox_events` -- lock registry with ordered `Lock{Root}` and nested-held assertions, declared transition tables with `TransitionRefused`, outbox writer (event id, aggregate id, per-aggregate sequence) and relay job scheduled every minute -- AD-4 kernel.
- [x] `app/Modules/Staff/Locks/LockStaffMember.php`, `StatusTables/StaffAccountStatus.php`, migration (status, invitation token hash, expiry, invited by) -- staff root lock and states.
- [x] `app/Modules/Staff/Actions/CreateStaffAccount.php`, `DeactivateStaffAccount.php`, `AcceptInvitation.php`, `ResendInvitation.php`, `ResetSignIn.php` -- the writers, with the last-Admin rule and session revocation.
- [x] `app/Modules/Notifications/Contracts/EmailProvider.php`, `Providers/SmtpEmailProvider.php`, `Fakes/FakeEmailProvider.php`, `Actions/SendEmail.php`, delivery-key migration -- provider behind an interface with a scriptable fake.
- [x] `app/Modules/Staff/Events/StaffAccountCreated.php` + listener -- sends the invitation through `SendEmail`, idempotent on event id.
- [x] `app/Modules/Staff/Filament/StaffResource.php` (+ pages, invitation acceptance page) -- table, create form, deactivate dialog, race and last-Admin messages, keyboard movement, Admin-only access.
- [x] Tests (Pest) -- every matrix row; outbox written in the same transaction; two-racer deactivation; replay of the relay; fake scripting; Finance and Operations refused.
- [x] `tests/Accessibility/console.spec.ts` -- add the Staff page and the invitation page to the axe run.

**Acceptance Criteria:**
- Given the Staff page, when an Admin views it, then each row shows name and initial, role, duties and status, and a screen reader hears the caption and column headings.
- Given a deactivated staff member who was signed in, when their browser makes any request, then it is refused and they are on the sign-in page.

## Verification

**Commands:**
- `docker compose run --rm app composer test` -- expected: all pass.
- `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` -- expected: clean.
- `npm run lint`, `npm run typecheck`, `npm test`, `npm run build` -- expected: clean.
- The accessibility run (Playwright + axe) -- expected: no violations; run by the orchestrating session, never by the implementer.

## Implementation Notes

- Kernel (AD-4): `Shared/Locks/{LockOrder,LockRegistry,LockAction}` (fixed root order, ascending ids, `SELECT ... FOR UPDATE`, nested `assertLocked()`). Only the outermost Action locks, directly in its own transaction: never in a nested Action and never in a savepoint, so a savepoint rollback can't drop locks the registry still counts as held. A row counts as held only after its `FOR UPDATE` returns it. `Shared/StatusTables/{StatusTable,Transitions,TransitionRefused}`: named moves; a refusal carries the current state and the last audit entry; `/api/v1` renders it as 409 `transition_refused` with `current`. The Action base gains `move()`, `refuse()`, `emit()` and `assertLocked()`.
- Outbox: `Shared/Events/{OutboxEvent,OutboxMessage,OutboxListener,OutboxListeners,RelayOutbox}`, `Shared/Actions/{WriteOutboxEvent,RelayOutboxEvents}` and `outbox_events` (event id, per-aggregate `sequence`, `position` bigserial, `claimed_until`). The relay works one event at a time:
  - It claims the event in its own short transaction (SKIP LOCKED; re-checks `relayed_at`, `given_up_at`, `available_at` and the lease), counts the attempt, sets a 10-minute lease and commits.
  - It runs listeners outside any relay transaction. On any Throwable it explicitly rolls back every level the listener left open, plus its own savepoint when the relay itself runs inside a transaction (as in tests). That covers deadlocks and serialization failures, which Laravel's nested transactions leave unrolled.
  - It then marks the event relayed, or failed with 1, 2, 4 ... 60-minute back-off.
  - It gives up after 10 attempts, or at once on `ProviderRefused`, with a founder alert. An event whose relay keeps dying is given up once its attempts are spent.
  - The `RelayOutbox` job has `$timeout = 120`; the relay claims nothing new after 90 seconds. It is scheduled as a queued job every minute (`ShouldBeUnique`).
- TC-3: `Shared/Providers/{ProviderFailure,ProviderRefused,ProviderTimedOut,ProviderErrored,ScriptedOutcome,ScriptsOutcomes}` is the shape every fake follows; `Notifications/Fakes/FakeEmailProvider` uses it. `SmtpEmailProvider` uses the default mailer and maps 5xx to Refused, timeouts to TimedOut and everything else to Errored.
- `Notifications/Actions/SendEmail` (owner decision, 6 November 2026) returns an `EmailOutcome`:
  - It commits the delivery key (`notification_deliveries`, unique event id + channel) as `sending`, then calls the provider with no transaction open.
  - Sent: the key becomes `sent`.
  - Refused or Errored: the key is deleted and the failure rethrown.
  - Timed out, or a later attempt finds the key still `sending`: the key becomes `uncertain` and the email is never sent again.
  - `Notifications/Queries/EmailDeliveries::uncertain()` and `Shared/Queries/OutboxEvents::latestOfTypes()` feed the Staff page's note: "The invitation email may not have arrived. Send a new invitation." It shows when the member's latest invitation event was given up or its email is uncertain.
- Staff states: `status` replaces `is_active` (existing rows keep their state); moves `accept_invitation`, `activate`, `resend_invitation`, `reset_sign_in` (Active to Invited) and `deactivate` (Invited or Active to Deactivated, final).
  - Accepting sets the password, uses up the link and clears any leftover second factor. The account stays Invited until `SetAuthenticatorSecret` makes it Active.
  - The invitation's 72 hours bound the whole set-up: `canSignIn()` lets "Invited, password set" in only until the original expiry. Under the lock, `SetAuthenticatorSecret` refuses an Invited account that is not awaiting its authenticator or whose time ran out.
  - Resend to an invitee part-way through clears the password and second factor and revokes their sessions.
  - Email addresses are unique only among Invited and Active accounts (partial index `staff_members_email_current_unique` plus the Action check; owner decision, 6 November 2026). A unique violation counts as "email taken" only for that index name. Sign-in prefers the current account under an address.
  - `down()` maps Invited-with-password to active. It restores `password NOT NULL` and the plain email index only when the data allows.
- Invitation links: a 64-character token, only its SHA-256 hash stored; the token travels in the outbox payload encrypted with the app key. Accept re-checks the hash and the expiry under the lock. The listener `Staff/Events/SendStaffInvitation` skips a link that is used, replaced or expired. Resend and reset void the old link at once.
- Last-Admin rule: deactivate and reset lock the acting Admin, the member and every active Admin in one ordered call, so two Admins removing each other serialise (the forked race test proves it). The loser's refusal is shown above the table in words.
- Sessions: `staff_sessions` records each staff session id.
  - `RecordStaffSession` takes the member's lock and records nothing for a member who may no longer sign in. Sign-in, the invitation sign-in and "Stay signed in" record the new id at once (`Staff/Http/StaffSessionRecords`); the `TrackStaffSession` middleware is the backstop and signs out a member whose recording is refused.
  - Session ids rotate with the old session destroyed (`regenerate(true)`, or the handler's `destroy()` after Filament's own regenerate).
  - Signing out deletes the session's row (`ForgetStaffSession`, on the `Logout` event). `RevokeStaffSessions` deletes a member's rows from `sessions`.
  - `SignOutInactiveStaff` replaces Filament's `Authenticate` in the console, so a deactivated or reset member's next request signs them out to the sign-in page with the reason (401 + `X-Staff-Session: ended` for Livewire and JSON). It replaces `Authenticate` rather than running before it, because Laravel's middleware priority moves `Authenticate` ahead of it.
- Database errors: the `pgsql` connection masks bindings in exception messages. The exception handler maps every `QueryException` to `Shared/Http/QueryExceptionRedactor`'s copy before reporting or rendering: same class (a unique violation keeps its index name), no bindings, the database message's first line with quoted values replaced, and the stack trace without arguments.
- Staff page: `Staff/Filament/StaffResource` and `StaffResource/ListStaff`, with their own Blade table (Filament tables render no `<caption>`; rows carry no `aria-label`). Dialogs are built with `->modal()`, not `requiresConfirmation()`, which adds a red icon. Every dialog body, Add included, lists what will happen; deactivating or resetting your own account says "This is your own account; you will be signed out at once". `RefusalWords` writes the race and last-Admin messages; an actor who lost Admin rights mid-change sees words above the table, not a 403.
  - Member ids from Livewire are validated as UUIDs and lower-cased; a bad or unknown id gets a 404 in words.
  - Keyboard: `resources/console/table-keys.ts` (+ Vitest).
  - Duties come from `StaffRole::duties()` until Story 1.5's registry and Support switch.
- Tests:
  - Cross-module flows are in `tests/Flows/StaffAccountsFlowTest.php`, including the real `is_active` to `status` migration run and back, and `tests/Flows/StaffDeactivationRaceTest.php` (two forked processes racing on real committed rows, no RefreshDatabase, tables truncated after).
  - Kernel tests are in `tests/Feature/ActionKernelTest.php` (locks, lease, back-off re-check, deadlock and serialization rollback, time budget, redaction) and `OutboxOutsideTransactionTest.php`.
  - Module tests are in `Staff/Tests/*` and `Notifications/Tests/EmailProviderTest.php`. Module `Tests/` folders are scanned by the boundary and write rules, so tests that read another module's fake or write framework tables live in `tests/Flows`.
  - The accessibility run waits until no dialog is visible before each scan after a dialog closes.
- TC-4: `database/factories/Staff/StaffAccountFactory.php` and the `invited-staff` scenario (fixed test token) for the accessibility run; CI seeds `first-admin invited-staff`.

## Spec Change Log

- 6 November 2026, owner decision: an email whose outcome is unknown (provider timed out, or the process died after the provider was called) is never sent again. The delivery key is committed as `sending` before the provider call; a definite failure frees it for retry; an unknown outcome marks it `uncertain`, alerts nobody automatically and shows on the Staff page that the invitation may not have arrived, so the Admin sends a new one. Keeps Always #5 as written.
- 6 November 2026, owner decision: an email address must be unique only among Invited and Active accounts. A deactivated person who comes back gets a new account; the old one stays for the audit trail.
- Recorded interpretation of Always #1: the declared table also holds `accept_invitation`, `resend_invitation` (Invited to Invited), `reset_sign_in` (Active to Invited, the owner's reset decision) and `deactivate` from Invited. Creating an account sets the table's declared initial state; there is no row to lock or move before it exists, so Create locks only the acting Admin.

## Review Triage Log

Review pass 1 (blind-hunter BH, edge-case-hunter EC, acceptance-auditor AA), 3 November 2026; triaged 6 November 2026.

| # | Layer | Finding | Verdict | Evidence | Route |
|---|-------|---------|---------|----------|-------|
| 1 | AA, EC, BH | Delivery key is only a savepoint while the provider is called, so a timeout, crash or failed commit sends the email again; docblock says "at most once" | medium | `SendEmail` inserts and calls SMTP inside the relay's open transaction | patch (owner decision above) |
| 2 | BH, EC | A listener deadlock or serialization failure leaves the relay transaction aborted: no attempt counted, no give-up, batch skipped, oldest event blocks every run | high | nested `DB::transaction` rethrows concurrency errors without rolling back the savepoint | patch |
| 3 | BH | Relay job has no timeout and runs SMTP inside an open transaction; a killed batch loses `relayed_at` and resends | medium | `RelayOutbox` sets no `$timeout`; whole batch in one job | patch |
| 4 | BH | `relayOne()` does not re-check `available_at`, so overlapping relays skip the back-off | low | ids picked before the claim | patch |
| 5 | EC | A definitive refusal (5xx) is retried 10 times; a given-up invitation is invisible on the Staff page | medium | `ProviderRefused` handled like any failure | patch |
| 6 | EC | Listener emails links that have already expired | low | only the hash is checked | patch |
| 7 | BH | Lock registry records rows as held before `FOR UPDATE` runs and keeps them after a savepoint rollback | medium | `claim()` before the select | patch |
| 8 | EC | A deactivated person's email can never be used again, against the dialog's "Add a new account if they come back" | medium | unique index over all rows | patch (owner decision above) |
| 9 | BH | Every unique violation is reported as "email taken" | low | `str_contains($message, 'email')` matches the column list | patch |
| 10 | BH | Database errors log bound values: names, emails, password and token hashes | medium | `QueryException` message carries bindings | patch |
| 11 | BH, EC | Malformed or uppercase member id in Livewire arguments gives a 500 or a false 404 | low | uuid column; result keyed lowercase | patch |
| 12 | AA | Losing Admin in a mutual deactivation gets a 403 page, not the refusal words | low | `attempt()` catches only `TransitionRefused` | patch |
| 13 | AA, EC | Resend to an invitee mid-setup keeps their password, sessions and any authenticator; a racing authenticator save leaves an Invited account with a second factor, skipping the set-up | medium | `issueInvitation` clears only `invitation_accepted_at` | patch |
| 14 | EC | "Invited, password set" has no time limit: the password works weeks later to enrol an authenticator | medium | accept clears the expiry | patch |
| 15 | BH, EC | Session id rotation keeps the old authenticated `sessions` row untracked; sign-out never removes `staff_sessions` rows | medium | `regenerate()` without destroy | patch |
| 16 | AA | Session recorded on the next request, without the member lock, so it can land just after a revoke | low | `RecordStaffSession` takes no lock | patch |
| 17 | BH, AA | Accept does not re-check expiry under the lock | low | check only in `memberFor()` | patch |
| 18 | EC | Migration `down()` maps Invited to inactive and leaves `password` nullable | low | round trip turns Invited into Deactivated | patch |
| 19 | EC | Deactivating or resetting your own account gives no warning | low | dialog names you in the third person | patch |
| 20 | AA | Operations duties text says "(Support)" on every account | low | `StaffRole::duties()` | patch |
| 21 | AA | Add dialog body is a sentence, not the list of what will happen | low | Always #9 | patch |
| 22 | AA | `aria-label` on each row can hide its cells from screen readers | low | `staff-list.blade.php` | patch |
| 23 | AA | Migration test creates the Admin after the migration, so the `is_active` conversion is never exercised | low | test evidence | patch |
| 24 | run | Accessibility run scans at 200% zoom while the closing dialog fades out | low | waits on `.fi-modal-open`, removed before the transition ends | patch |
| 25 | AA | Create skips a declared move and locks only the Admin; extra moves contradict the literal "Invited, Active, Deactivated" | - | no prior state or row exists | recorded in Spec Change Log |
| 26 | AA | Listener reads the member's current name and email, not emit-time facts (AD-4.6) | reject | the address must be current; the hash check enforces the drop-older rule | reject |
| 27 | AA | Staff Action tests live in `tests/Flows`, not the module | reject | disclosed: module `Tests/` scans forbid another module's fake | reject |
