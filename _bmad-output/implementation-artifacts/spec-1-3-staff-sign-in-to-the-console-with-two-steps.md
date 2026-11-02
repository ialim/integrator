---
title: 'Story 1.3: Staff sign in to the console with two steps'
type: 'feature'
created: '2026-10-26'
status: 'done'
baseline_commit: '9bc7229ef6bbd76e54c31e94698f6d1f4be233de'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/implementation-artifacts/epic-1-context.md'
  - '{project-root}/_bmad-output/planning-artifacts/ux-designs/ux-integrator-2026-10-07/DESIGN.md'
  - '{project-root}/_bmad-output/implementation-artifacts/spec-1-2-production-hosting-with-an-hourly-offsite-copy-and-founder-alerts.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** There is no staff console and no staff account, so nobody can reach the screens that will move money, stock and access, and nothing yet keeps the platform team separate from Integrators (FR-52, FR-71, AD-18).

**Approach:** Add the Filament console at `/console` on its own `staff` guard with mandatory two-factor sign-in and a 12-hour session, a `Staff` module with the staff account and a first-Admin console command that works through an audited `Staff` Action, and the console shell in the Ledger look (top bar, 224px rail, responsive and accessible), with tokens shared with the Integrator app.

## Boundaries & Constraints

**Always:**
- Staff authenticate only through the `staff` guard; `/api/v1` never accepts a staff session, and no Integrator account can reach `/console` (AD-18).
- No console page loads until both steps pass; a staff member without a second factor is sent to set one up first.
- The staff session ends 12 hours after sign-in; a warning appears at 11 hours 55 minutes with "Stay signed in".
- Every write goes through a `Staff` Action (AD-1), including Filament's own second-factor setup, which must call an Action rather than save the model.
- The first-Admin command creates an Admin with the founder flag only when no staff account exists, and writes an audit row naming `system` as the actor. This story adds the minimum audit write (an insert-only `audit_entries` table and an Action-side writer); Story 1.6 makes it tamper-proof.
- Colours, type, spacing and shapes come from one Ledger token file shared by the console theme and the app; light mode only; system fonts only.
- Keyboard, screen-reader and 200% zoom criteria in the story hold on the sign-in, second-step, setup and shell pages, checked by an automated accessibility test in CI.
- Times in the top bar are West Africa Time, read from the `Shared` Clock.

- **Decision (owner, 26 October 2026, PRD §8.3 question 14): staff sign in with email and password, then an authenticator-app code**, using Filament 5's built-in authenticator-app second factor with recovery codes. Until email exists (Story 1.4), a forgotten password is reset by an Admin.
- **Decision (owner, 26 October 2026): "Stay signed in" asks for the authenticator code again and then starts a fresh 12 hours**, without leaving the page.
- **Decision (owner, 26 October 2026): the spec is kept whole** at about 2,200 tokens.

**Never:**
- No staff account management screens, role scoping or rail groups beyond an empty Today page (Stories 1.4 and 1.5).
- No Integrator sign-in (Epic 3), no passkeys for staff, no email or SMS providers.
- No dark mode, no bundled fonts, no "Access denied" wording.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| First Admin | No staff exist; `php artisan staff:create-first-admin --name="Ada" --email=ada@example.com`, password entered at a hidden prompt | Admin with founder flag; audit row, actor `system`; prints how to finish setup | Weak or mismatched password refused before anything is created |
| First Admin again | A staff account exists | Command refuses, creates nothing | Non-zero exit, plain message |
| No second factor yet | Correct first step, no authenticator set | Sent to set-up page; no other console page reachable | N/A |
| Second factor | Correct first step, wrong code | Stays on the second step, error in words beside the field | Throttled after repeated failures |
| Staff on the API | Signed-in staff session calls an authenticated `/api/v1` route | `401` with the JSON error envelope | N/A |
| Integrator on the console | A non-staff session opens `/console` | Sign-in page; never a console page | N/A |
| Session warning | 11 h 55 min after sign-in | Banner: session ends in 5 minutes, with "Stay signed in" | N/A |
| Session end | 12 h after sign-in | Signed out to the sign-in page; unsaved form values kept as a draft and offered back after signing in again | N/A |
| Narrow window | Below 1024px or 400% zoom | Rail becomes a menu button; panes stack; wide tables scroll in their own box | N/A |
| Phone | Phone-width screen opens `/console` | Notice that the console is for a desktop | N/A |

</frozen-after-approval>

## Code Map

- `app/Modules/Staff/` -- empty module from Story 1.1; gets `Models/StaffMember`, `Actions/`, `Database/Migrations/`, `Filament/`, `Tests/`, and its own service provider (loaded by `ModuleServiceProvider`).
- `app/Modules/Shared/` -- kernel: add the Action base (transaction plus audit write) and the audit writer here; `Contracts/Clock` for all times; `Support/HasUuidV7` on every model.
- `app/Providers/ModuleServiceProvider.php` -- registers module providers, migrations and routes, then binds provider fakes; register the console panel provider through the Staff module.
- `config/auth.php` -- names a future `App\Modules\Identity\Models\User` for `web`; add the `staff` guard (session) and provider here; do not create the Identity model.
- `config/session.php`, `.env.example` -- database driver already; set the lifetime so it never cuts a staff session before 12 hours.
- `vendor/filament/filament/src/Auth/MultiFactor/App/` -- Filament's authenticator-app provider with recovery codes and forced setup (`requiresMultiFactorAuthentication`); its `save*` hooks on the user model must delegate to Staff Actions.
- `resources/app/app.css` -- the app's Ledger colours from Story 1.1; move them into the shared token file and import it from both.
- `tests/Architecture/*` -- every new class is scanned; comply, do not exempt.
- `.github/workflows/ci.yml` -- add the accessibility job; do not restructure.

## Tasks & Acceptance

**Execution:**
- [x] `resources/tokens/ledger.css` -- Ledger tokens from `DESIGN.md` as CSS variables; imported by the app CSS and the console theme -- one token source.
- [x] `app/Modules/Shared/Actions/Action.php`, `Shared/Audit/*`, `Shared/Database/Migrations/*_create_audit_entries_table.php` -- Action base running in a transaction and inserting an audit row (actor type and id, action, subject, field changes with personal fields marked "changed") -- audited first Admin.
- [x] `app/Modules/Staff/Database/Migrations/*_create_staff_members_table.php`, `Models/StaffMember.php` -- UUIDv7, name, unique email, password, role (`admin`, `finance`, `operations`), founder flag, active, encrypted authenticator secret and recovery codes -- staff account.
- [x] `app/Modules/Staff/Actions/CreateFirstAdmin.php`, `SetAuthenticatorSecret.php`, `SetRecoveryCodes.php` + console command `staff:create-first-admin` (name and email as options, password at a hidden prompt with confirmation, at least 12 characters, never echoed or logged) -- the only writers -- AD-1.
- [x] `config/auth.php` -- `staff` guard and provider -- AD-18.
- [x] `app/Modules/Staff/Filament/ConsolePanelProvider.php` -- panel at `/console`, `staff` guard, required authenticator-app second factor, sign-in throttling, Ledger theme, empty Today page -- two steps.
- [x] `app/Modules/Staff/Http/Middleware/EndStaffSessionAfter12Hours.php` + console banner script -- warning at 11 h 55 min, "Stay signed in" re-asking the authenticator code and starting a fresh 12 hours, sign-out at 12 h, draft save and restore of unsaved form values -- session rules.
- [x] `resources/css/filament/console/theme.css` + render hooks -- green top bar (wordmark, "Staff console", WAT clock, name and role), 224px rail on the aged surface, menu button below 1024px, phone notice, skip link, 3px focus ring -- shell.
- [x] Tests (Pest) -- every matrix row; staff session refused on an `auth:sanctum` probe under `/api/v1`; first-Admin audit row.
- [x] Playwright + axe-core check of the sign-in, second-step, setup and Today pages in CI -- accessibility criteria.

**Acceptance Criteria:**
- Given a keyboard-only user on the sign-in page, when they tab through it, then every control is reachable in reading order with a visible 3px focus ring, a skip link passes the menu, and field errors are announced in words.
- Given a screen reader on any console page built here, when it loads, then there is one top heading and every field is labelled, and the page works at 200% zoom.
- Given the axe-core job, when CI runs, then the pages above report no violations.

## Verification

**Commands:**
- `docker compose run --rm app composer test` -- expected: all pass.
- `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` -- expected: clean.
- `npm run lint`, `npm run typecheck`, `npm test`, `npm run build` -- expected: clean.
- The accessibility check (Playwright + axe) -- expected: no violations.

## Implementation Notes

- Audit kernel: `Shared/Actions/Action` (`transaction()`, `saveAndAudit()`, `audit()`), `Shared/Actions/WriteAuditEntry` (the only writer; refuses outside a transaction), `Shared/Audit/{Actor,AuditChanges,PersonalFields}`, and `Shared/Queries/AuditTrail` returning `AuditEntry` DTOs (modules may not query `audit_entries` directly, so tests read through it). The writer sits in `Actions/` because the AD-1 scanner allows writes only there. Changes are read from the dirty set before saving, so a save that changes nothing writes and audits nothing. `sequence` is a `bigserial` added by `ALTER TABLE`, beside the UUIDv7 primary key. The pgsql connection now runs in UTC.
- Filament's own writes all go through Staff Actions: `saveAppAuthenticationSecret` → `SetAuthenticatorSecret`, `saveAppAuthenticationRecoveryCodes` → `SetRecoveryCodes` (a used code is audited `staff.recovery_code_used`), and Laravel's sign-in password re-hash → `RehashPassword` through a custom `staff` user provider (which also lower-cases emails). Setting or removing an Admin's authenticator raises a founder alert after commit.
- Password rule: `Staff/Actions/StaffPassword` (12+ characters, not one repeated character, not containing the name or the email's local part), used by both the command and `CreateFirstAdmin`.
- 12-hour session: `StaffSession` stores the sign-in time at the `Login` event; `EndStaffSessionAfter12Hours` (persistent Livewire middleware) signs out with a flashed reason, a redirect for pages and a 401 with `X-Staff-Session: ended` for Livewire and JSON. `POST /console/session/extend` re-checks the code (reuse refused), only in the last 5 minutes, throttled 5 per 15 minutes, and regenerates the session id. `resources/console/session.ts` runs the clock, warning, dialog, cross-tab end-time broadcast and per-member drafts in `sessionStorage` (passwords and codes never kept). Session lifetime is now `max(720, SESSION_LIFETIME)` minutes; Sanctum's guard list is published with only `web`.
- Sign-in, failures, throttling, "Stay signed in" and the forced sign-out each write a JSON log line with the staff id only.
- Ledger look: `resources/tokens/ledger.css` is the one token file; `LedgerTokens` reads it for Filament's PHP palette. Filament registers its Inter web font for every page, so the Staff module rebinds Filament's `AssetManager` (`SystemFontsOnly`) to drop it. Copy is overridden in `lang/vendor/filament-panels/en/`. Filament's assets are published by `filament:upgrade` in `post-autoload-dump` (gitignored).
- Accessibility: `tests/Accessibility/console.spec.ts` (Playwright + axe-core, WCAG 2.2 AA tags) walks first step, forced set-up (reading the key from the dialog and computing the TOTP), Today and the second step, with reading order, 3px rings, errors tied by `aria-describedby`, 640px (200% zoom) and phone checks. The CI `accessibility` job seeds `app:seed-test-state first-admin` and clears the database cache between tests (`A11Y_RESET_LIMITS_CMD`) so the run stays under Filament's 5-a-minute sign-in limit.
- Deactivation tests use a direct row update in `tests/Support/StaffConsole::deactivate()` until Story 1.4's Action exists. Lock-registry locking is not used yet (the first-Admin race is serialised with a table lock).

- The implementation subagent stalled twice (no output for 10 minutes): once mid-edit and once after starting the accessibility server in the foreground. Its work was intact; the remaining verification and fixes were finished in the main session.
- Filament 5's built-in authenticator-app second factor (`AppAuthentication::make()->recoverable()`, required) provides set-up, recovery codes and code-reuse prevention. `StaffMember::saveAppAuthenticationSecret()` and `saveAppAuthenticationRecoveryCodes()` delegate to the `SetAuthenticatorSecret` and `SetRecoveryCodes` Actions, so Filament never saves the model (AD-1).
- `CreateFirstAdmin` takes a `SHARE ROW EXCLUSIVE` lock on `staff_members`, so two concurrent runs create one Admin.
- The 12 hours start on the `staff` guard's `Login` event (both steps passed). `EndStaffSessionAfter12Hours` signs out at 12 hours; a Livewire request gets a 401 the console script turns into a sign-out with the draft saved. "Stay signed in" posts the authenticator code to `/console/session/extend`, throttled after 5 wrong codes.
- `config/session.php` keeps the lifetime at 12 hours or more, so idleness never ends a staff session first. Epic 3 revisits it for the Integrators' 30 idle days.
- `/api/v1` refuses a staff session because Sanctum's guard list never names `staff` (`config/sanctum.php`).
- Finished after the stall: an import-order Pint fix in `StaffServiceProvider`; a 180 s process timeout in `SeedTestStateTest`, since one artisan boot takes about 15 s on the Windows bind mount; `.claude/**` added to the ESLint ignores, because a session worktree under `.claude/worktrees/` was linted as a second project; three Playwright selector fixes. The one-time-code group and its first input share the label, so the test selects by `textbox` role. The second-step field submits itself on the sixth digit (`submitOnCompletion`), so the test no longer clicks "Sign in" after filling it. "Staff console" is matched exactly, so the phone notice does not also match.
- Locally the accessibility run takes about 3 minutes against `php -S` in Docker (about 15 s per page on the bind mount). CI on Linux runs the same `accessibility` job.

## Spec Change Log

## Review Triage Log

Review pass 1 (blind-hunter BH, edge-case-hunter EC, verification-gap VG), 2 November 2026.

| # | Layer | Finding | Verdict | Evidence | Route |
|---|-------|---------|---------|----------|-------|
| 1 | BH, EC | A 12-hour sign-out on a Livewire or JSON request returns 401 without flashing the reason, so the sign-in page shows none | low | only the GET branch calls `flash()`; direct move | patch |
| 2 | BH, EC | Drafts are keyed by path only, so the next staff member on that tab is offered the previous one's values | medium | `console-draft:<path>` in `sessionStorage`, kept after sign-out | patch |
| 3 | BH, EC | "Stay signed in" in one tab leaves other tabs on the old end time, so they warn, save a draft and reload while still signed in | medium | `endsAt` updated only in the tab that extended | patch |
| 4 | BH | Using a recovery code is audited as `staff.recovery_codes_set`, the same as set-up | medium | Filament saves the shortened list through `saveAppAuthenticationRecoveryCodes()`; the test asserts two identical rows | patch |
| 5 | BH | Sign-in success and failure, throttling, extension and the forced sign-out leave no log line | medium | only model writes are audited; no listener logs auth events | patch |
| 6 | BH | The extend throttle decays after 60 s (5 guesses a minute indefinitely), accepts extensions outside the warning window and keeps the session id | medium | `RateLimiter::hit()` default decay; no window check; no `regenerate()` | patch |
| 7 | BH | "Weak password" means only under 12 characters; the rule is duplicated in the command and the Action | medium | `mb_strlen >= 12` in two places; `aaaaaaaaaaaa` passes | patch |
| 8 | BH | Anyone who learns the founder's password before first sign-in can enrol their own authenticator, unnoticed | medium | set-up re-asks only the password; no alert on `staff.authenticator_set` | patch |
| 9 | BH, VG | "refuses a deactivated staff member at the first step" only calls `canAccessPanel()` in memory | medium | the test never signs in; a signed-in deactivated session is not exercised | patch |
| 10 | BH, VG | A test title says 20:00 WAT; the assertion is correctly 21:00 WAT | low | 08:00Z + 12 h = 20:00Z = 21:00 WAT | patch |
| 11 | BH | The accessibility test checks `aria-invalid` and visible text, not that the error is tied to the field | low | no `aria-describedby` or live-region assertion; direct addition | patch |
| 12 | BH | The "Stay signed in" dialog is never in the axe run | low | it needs 11 h 55 min of browser time; a frozen browser clock would be new tooling | reject |
| 13 | BH | `AuditEntry` and `AuditTrail` drop `acting_as`, `acting_for` and `request_id` | low | the writer stores them; direct addition | patch |
| 14 | BH | Spec and sprint status disagree | false | the workflow syncs sprint status at its next step | reject |
| 15 | BH | The 12-hour sign-out invalidates the whole shared session | low | no other guard signs in today; fix touches future multi-guard design | reject |
| 16 | VG | The Livewire 401 test uses `postJson`, so `expectsJson()` hides the `X-Livewire` branch | medium | pre-verified gap | patch |
| 17 | VG | Signing in with a capitalised email is untested | medium | pre-verified gap | patch |
| 18 | VG | The password re-hash through `RehashPassword` is untested | medium | pre-verified gap | patch |
| 19 | VG | Code-reuse prevention on "Stay signed in" is untested | medium | pre-verified gap | patch |
| 20 | VG | The first-step throttle message is untested | medium | pre-verified gap | patch |
| 21 | VG | The console script's `start()` flow is never run by a test | medium | pre-verified gap | patch |
| 22 | VG | The 12-hour session lifetime floor is untested | medium | pre-verified gap | patch |
| 23 | EC | A save that changes nothing records stale or whole-row differences, and a no-op secret or codes change is audited as set or removed | low | `AuditChanges` reads `getChanges()`; the Actions save and audit unconditionally; direct dirty-check | patch |
| 24 | EC | `occurred_at` is bound without an offset into a `timestamptz`, so a non-UTC server session shifts audit times | medium | no `timezone` on the `pgsql` connection; grammar format `Y-m-d H:i:s` | patch |
| 25 | EC | The throttle message goes to the code field while the recovery-code field is shown | low | `getRateLimitedNotification()` ignores `useRecoveryCode`; direct | patch |
| 26 | EC | "Stay signed in" is offered to a member with no authenticator yet and counts as a wrong code | low | the banner renders on the set-up page; direct guard | patch |
| 27 | EC | A `code` sent as an array gives a 500 | low | `(string)` cast of an array; direct `is_string` check | patch |
| 28 | EC | The script reports "could not be reached" on a 419 or non-JSON reply | low | `response.json()` unguarded; direct | patch |
| 29 | EC | The first-Admin command before migrations shows a stack trace | low | a loud failure on a setup mistake; fix adds a guard | reject |
| 30 | EC | The accessibility run can exceed Filament's 5-per-minute sign-in limit on a fast CI runner | medium | about six `authenticate` calls across four tests from one IP | patch |
| 31 | EC | 200% zoom is checked only on Today and the second step | low | sign-in and set-up never resized; direct addition | patch |
| 32 | EC | Pest does not cover every matrix row | low | the narrow-window and draft rows are browser behaviour, covered by Playwright and Vitest | reject |
