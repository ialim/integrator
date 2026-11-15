---
title: 'Story 1.7: The Integrator app installs on a phone'
type: 'feature'
created: '2026-11-15'
status: 'done'
baseline_commit: '15d54c5c20c98c1d0147526850ee218fc79bc044'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/implementation-artifacts/epic-1-context.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** The Integrator app is a one-heading stub: it cannot be installed, has no pages, no update handling and no shared formatters, so every later app epic would start without its shell (FR-54, NFR-6, AD-19, UX-DR1 to 3, 21, 22, 24, 26, 30, 32).

**Approach:** Turn `resources/app/` into the installable React single-page app: a web manifest and service worker, a signed-out holding screen, the text pages on one template, the quiet update banner and the install offer, one set of formatters for app and console, an accessibility check on every app screen in CI, and a recorded cold-start baseline.

## Boundaries & Constraints

**Always:**
- Opened from the home screen on Android 9+ Chrome or iOS 16.4+ Safari, the app shows no address bar (`display: standalone`, iOS web-app meta tags, icons including a maskable one and an Apple touch icon).
- One React and TypeScript SPA served by Laravel's existing shell view and catch-all route; no Inertia, Livewire or Blade page beyond that one view. Client routing with TanStack Router.
- Ledger tokens from `resources/tokens/ledger.css` only; system font stacks; no font file in the build.
- The service worker precaches the shell's built assets and answers app navigations with the shell. It never caches or answers `/api`, `/console` or `/up`.
- The signed-out holding screen shows the wordmark and links to the text pages. Text pages share one template: Install the app (Android and iPhone steps in words), accessibility statement, not found (also for unknown paths), signed out, and service down (shown when `/api/v1/health` fails or the network is unreachable).
- A new version waits: the app never reloads by itself, and an "Update when you finish" banner appears; it applies only when the person chooses it, and keeps what they typed.
- Install is offered once, on the second visit from a browser where the app is not installed (Android: the browser's prompt; iPhone: a link to the Install page). Dismissing it ends the offer. The Install page is always reachable.
- Every format in the `EXPERIENCE.md` Formats table has one formatter, each with tests, used wherever the app or console shows that kind of value.
- At 320px wide every shell screen is one column with a 16px margin and no sideways scroll, in both orientations; it works at 200% zoom, honours larger text and reduce-motion, has landmarks, one top heading, a skip link and a visible 3px focus ring.
- CI runs the automated accessibility check on every app shell screen, as it does on the console, and a failure blocks the merge.
- The cold start to the first usable screen is measured on the test phone profile (2 GB Android, 400 kbps, 400 ms) and recorded in the repository as the baseline.

- **Decision (owner, 15 November 2026): twin formatters from one set of cases.** A TypeScript set for the app and a PHP set for the console, both tested against one shared cases file so they cannot drift. The console switches to the Formats table, so its times read like "2:05 pm" (zone named only on settings and exports).
- **Decision (owner, 15 November 2026): the baseline is emulated now, real phone later.** A script measures the built shell in Chrome with the test phone's network (400 kbps, 400 ms) and a 4x CPU slowdown; the result goes in `docs/operations/performance-baseline.md`, which also says how to add a reading from a real 2 GB phone.
- **Decision (owner, 15 November 2026): the spec is kept whole** at about 1,850 tokens.

**Never:**
- No sign-in, tabs, Catalogue or other signed-in screens (Epic 3 onwards); no Terms of use or Privacy Notice page yet (counsel wording, later epics); no browser notifications.
- No offline data store (Dexie, AD-12) or TanStack Query yet; no "Save for offline".

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| First visit | Signed out, not installed | Holding screen; no install offer | N/A |
| Second visit | Not installed, offer never dismissed | Install offered once | N/A |
| Dismissed | Person dismisses the offer | Never offered again on that browser; Install page still works | N/A |
| Installed | Opened in standalone mode | No install offer | N/A |
| New version | Service worker finds a new build while a page is open | "Update when you finish" banner; no reload until chosen | N/A |
| Unknown path | `/nothing-here` | Not found text page inside the template | HTTP 200 from the shell; page says not found |
| Service down | `/api/v1/health` fails or is unreachable | Service down text page | N/A |
| Console untouched | `/console/*`, `/api/*` with the worker installed | Served by the network, never the worker | N/A |
| Formatter edge | Negative money, this year vs another year, masked phone, look-alike letters in a code | Exactly the Formats table forms | Invalid input throws, never prints a wrong value |

</frozen-after-approval>

## Code Map

- `resources/app/{main.tsx,App.tsx,app.css,App.test.tsx,test/setup.ts}` -- the stub; `app.css` already imports the Ledger tokens and maps Tailwind names.
- `resources/tokens/ledger.css` -- the one token source (console imports it too).
- `resources/views/app.blade.php` -- the shell view; add manifest link, theme colour, Apple web-app tags, `viewport-fit=cover`.
- `routes/web.php:6-8` -- catch-all `app.shell` excluding `api`, `console`, `up`; keep it.
- `vite.config.ts` -- add vite-plugin-pwa (the laravel plugin is skipped in `test` mode; keep that).
- `package.json` -- add `vite-plugin-pwa` 2.x and `@tanstack/react-router` 1.x (AD-19 versions).
- `app/Modules/Shared/Support/{Wat,Iso8601}.php` and their console callers (`ConsoleShell`, `RefusalWords`, `ExtendStaffSessionController`, `SendStaffInvitation`, `views/{audit-history,audit-trail,staff-list}.blade.php`) -- the existing time formats.
- `_bmad-output/planning-artifacts/ux-designs/ux-integrator-2026-10-07/EXPERIENCE.md` -- Formats 303-322, voice 268-301, accessibility 449-472, Responsive & Platform 474-494, time 515-535, offline 589-608; `DESIGN.md` brand 301-310, wordmark 401, banner 409, top bar 416; mockup `mockups/system/responsive-platform-step22.html:336` (iPhone wording).
- `tests/Accessibility/console.spec.ts` (helpers at 31, 51, 55, 64, 107), `playwright.config.ts`, `.github/workflows/ci.yml:138-210` -- the accessibility pattern to extend to the app.

## Tasks & Acceptance

**Execution:**
- [x] `package.json`, `vite.config.ts`, `public/` icons -- PWA plugin, manifest, service worker rules, icons -- installable.
- [x] `resources/views/app.blade.php` -- head tags for standalone and theme -- no address bar.
- [x] `resources/app/` -- router, holding screen, text-page template and pages, update banner, install offer, service-down check -- the shell.
- [x] `resources/formats/` (TypeScript), `app/Modules/Shared/Formats/` (PHP), one shared cases file -- both sets tested against it; console callers switched -- UX-DR32.
- [x] `tests/Accessibility/app.spec.ts`, `playwright.config.ts`, `ci.yml` -- every shell screen at 320px and desktop, 200% zoom, no axe violations -- UX-DR22.
- [x] Cold-start measurement and `docs/operations/performance-baseline.md` -- NFR-6 baseline.
- [x] Tests (Vitest, Pest) -- every matrix row.

**Acceptance Criteria:**
- Given a keyboard user, when they tab through any shell screen, then the skip link comes first and every control shows the focus ring.
- Given the update banner, when it appears, then a screen reader announces it once without moving focus.

## Design Notes

- Icons are provisional: the wordmark's "i" in bold italic serif, paper on ledger green, rendered to PNG from one SVG source, until a brand mark exists (DESIGN.md:401 calls the wordmark provisional).
- Install offer state lives in `localStorage` (visit count, dismissed); a failure to read it means no offer.

## Implementation Notes

- Service worker: `vite-plugin-pwa` writes `sw.js` to `public/` (root scope) and the web manifest to `public/build/manifest.webmanifest`. It precaches the app's built JS and CSS (not the console's theme or scripts) plus `/`, whose revision hashes the asset list and `app.blade.php`, so a new build always ships a new shell. Navigations fall back to `/` except `/api`, `/console` and `/up`; there is no runtime caching. `registerType: 'prompt'`, no `skipWaiting`: the banner's "Update now" saves the typed values (`drafts.ts`) and then activates the waiting worker.
- Formats: `resources/formats/index.ts` and `app/Modules/Shared/Formats/Format.php`, both run against `resources/formats/cases.json`, and each test fails if a formatter has no case. Money, USD and percent take whole minor units (kobo, cents, basis points). `Shared/Support/Wat.php` is removed; the console's clock, session banner, refusal words, staff list, audit trail and history, the stay-signed-in reply and the invitation email now use the Formats table ("2:05 pm", "Fri 6 Nov, 10:00 am", "5 min"). Founder alerts keep their WAT and UTC times (an operations message, not a screen).
- Ledger text sizes moved from px to the same values in rem, so a larger-text setting scales them; nothing changes at the default size.
- Service down replaces the page content on any route while `/api/v1/health` fails or the browser reports offline; "Try again" and coming back online check again.
- Install offer: a visit is a browsing session (sessionStorage); the offer shows on the first qualifying visit only, stays for that visit until used or dismissed, and is hidden on the Install page itself.
- Cold-start baseline: `scripts/measure-cold-start.mjs`, recorded in `docs/operations/performance-baseline.md` (8.1 s of the shell's own time on the emulated phone, dominated by the local server sending assets uncompressed).
- Icons: `resources/icons/*.svg` rendered to `public/icons/*.png` by `scripts/render-icons.mjs`.

## Spec Change Log

## Review Triage Log

Review pass 1 (blind-hunter BH, edge-case-hunter EC, verification-gap VG, plus the orchestrator's accessibility run A11Y), 15 November 2026.

| # | Layer | Finding | Verdict | Evidence | Route |
|---|-------|---------|---------|----------|-------|
| 1 | A11Y | The iPhone `test.use` spread `devices['iPhone SE']`; Playwright refused the whole app spec while the run exited 0 | high | "Cannot use({ defaultBrowserType }) in a describe group" | patch (orchestrator) |
| 2 | BH, EC | Health check has no timeout; on slow 3G the first check and "Try again" can hang | medium | plain `fetch` with no signal | patch |
| 3 | BH, EC | Draft restore sets values React-controlled fields ignore | low | direct `.value` + `input` event | patch |
| 4 | BH, EC | Draft removed before its fields exist; malformed draft throws | low | `removeItem` before lookup | patch |
| 5 | BH | One-time codes and card fields go into the draft | medium | only passwords and `data-no-draft` skipped | patch |
| 6 | EC | Failed `apply()` leaves a stale draft | low | no cleanup path | patch |
| 7 | BH, EC | Hourly `registration.update()` rejects unhandled offline | low | `void` promise | patch |
| 8 | EC | Older health result overwrites newer; going down unmounts the route and loses typing | low | no request ordering; `Outlet` replaced | patch |
| 9 | BH, EC, VG | `markOffered` runs while the offer is hidden on `/install` | low | effect ignores `onInstallPage` | patch |
| 10 | EC | No sessionStorage makes every load a visit | low | `env.session?.` optional | patch |
| 11 | EC | `prompt()` rejection is unhandled and skips `end()` | low | no catch | patch |
| 12 | BH, EC | Twins drift on prototype keys, Unicode whitespace and letters, float measures | medium | `in`-style lookup; JS `\s`/`toUpperCase` vs PHP; `String(float)` | patch, with shared cases |
| 13 | BH, EC | Invitation email loses year and zone | medium | `dateTime` has no document form | patch: document form in both twins |
| 14 | EC | "Password set … until" throws when `invitation_expires_at` is null | low | formatter throws on null | patch: guard |
| 15 | BH | `InvalidFormatInput.php` lacks `strict_types` | low | file header | patch |
| 16 | EC | Baseline script times any h1, including service down | low | `getByRole('heading', { level: 1 })` | patch |
| 17 | BH | Larger text and reduce-motion untested | low | no font-size or reduced-motion case | patch |
| 18 | VG | Update draft save and restore unverified | low | App test checks `activate` only | patch |
| 19 | VG | `registerServiceWorker` untested | low | no test imports it | patch |
| 20 | VG | Health non-OK answer untested | low | Playwright aborts only | patch |
| 21 | VG | Offer hidden on `/install` untested | low | all cases open `/` | patch |
| 22 | VG | Console date wording on audit rows, History and staff list unasserted | low | pages render, no text asserted | patch |
| 23 | BH | Cold start 13.6 s on the emulated phone with no follow-up | medium | uncompressed local server; no compression or bundle budget recorded | defer |
| 24 | BH | Other open tabs keep the old bundle after an update | low | single bundle today; no lazy chunks to 404 | reject |
| 25 | BH | Precache exclusions are a hand-kept list of console entries | low | a missed entry only caches a hashed console file; never answers `/console` | reject |
| 26 | BH | iPad gets "phone" wording; Android browsers without the prompt get no offer | low | spec defines Android as the browser's prompt | reject |
| 27 | EC | Same-named fields and multi-selects collapse in the draft | low | no such form exists; adds keying | reject |
| 28 | EC | Founder alert emails keep 24-hour WAT | low | operations email, not among the spec's callers | reject |
| 29 | BH | Variables still named `Wat` | low | cosmetic | reject |
| 30 | BH | Test prompt sources never removed; inline `app(Clock::class)` | low | test-only and style | reject |
| 31 | BH | Spec in-review vs sprint in-progress | false | sprint moves to review at presentation | reject |
| 32 | BH, EC | Baseline script polling skew; `runs` of 0 prints NaN | low | developer script, default runs | reject |
| 33 | VG | `AppShellTest` asserts vite config text, not the built manifest | low | Playwright covers worker routing | reject |

## Verification

**Commands:**
- `docker compose run --rm --no-deps -T app sh -c 'php artisan config:clear -q; php artisan test'` -- expected: all pass (not `composer test`).
- `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` -- expected: clean.
- `npm run lint`, `npm run typecheck`, `CI=true npm test`, `npm run build` -- expected: clean.
- The accessibility run (Playwright + axe) -- expected: no violations; run by the orchestrating session, never by the implementer.
