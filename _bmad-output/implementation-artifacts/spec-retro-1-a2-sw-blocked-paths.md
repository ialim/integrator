---
title: 'The app service worker never answers server routes outside the app'
type: 'bugfix'
created: '2026-11-16'
status: 'done'
route: 'oneshot'
review_loop_iteration: 0
context: []
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** The app's service worker covers the whole site (scope `/`) and answers navigations with the app shell. The only paths it leaves to the server are `/api`, `/console` and `/up` (`vite.config.ts:15`). Other server GET routes would be answered with the shell instead of reaching the server: `/filament/exports/…` and `/filament/imports/…` downloads, Livewire's `/livewire-<hash>/…` (including preview-file), `/storage/…` and `/sanctum/csrf-cookie`. Epic 1 does not use any of them yet, so this has not bitten (retro finding X2). Also, no test drives the console with the worker installed (retro finding X3).

**Approach:** Add `/filament`, `/livewire…`, `/storage` and `/sanctum` to the worker's list of paths it never answers. Then add one Playwright case that installs the worker by opening `/`, then signs in to the console and uses it. That case asserts that every console navigation and Livewire call reached the server, not the worker.

</frozen-after-approval>

## Implementation Notes

- **The never-answered list.** `vite.config.ts` gains `/filament`, `/livewire` (with or without the hash Livewire 4 puts in its prefix, e.g. `/livewire-f7fa8b2f/`), `/storage` and `/sanctum`. These come from `php artisan route:list --method=GET`: every GET route outside `/console` and `/api` except the `{path?}` app shell. The comment now says that any new server route outside these belongs on the list.
- **`tests/Accessibility/app.spec.ts`.** The worker test now also opens one path under each new prefix. It reads Livewire's real hashed prefix from the console's script tag. It reads each response directly, because Sanctum's 204 makes the browser abort the page load.
- **`tests/Accessibility/console.spec.ts`.** A new last test, X3, runs with the app installed. It opens `/`, waits for the worker, signs in as Operations (both steps go through Livewire), loads a refused page and Today, and asserts that no page and no Livewire POST came from the worker. It waits for the next code, since the Operations test before it used the current one.
- **`tests/Feature/AppShellTest.php`.** It pinned the old one-line list. It now checks each entry.
- **Verified:**
  - `npm run lint`, `npm run typecheck` and `npm run build` pass; the built `public/sw.js` carries the new list.
  - The three affected Playwright tests pass against the running server.
  - The `AppShell` PHP tests and Pint pass.

- **Found in review: the catch-all was hiding `/storage`.** The server's `{path?}` catch-all (`routes/web.php`) was answering `/storage/…` itself, because Laravel registers that route after it. The catch-all now skips `filament`, `livewire-<hash>`, `storage` and `sanctum` too, matching the worker's list.

## Review Triage Log

Blind Hunter, one pass (the oneshot route runs one review layer):

| # | Finding | Verdict | Evidence | Route |
|---|---|---|---|---|
| 1 | `routes/web.php`'s catch-all skips only `api\|console\|up`, so the server could serve the shell for the new paths | medium | Confirmed by the extended PHP test (finding 2). Laravel registers the local disk's `storage/{path}` route after the app's routes, so the catch-all answered `/storage/…` with the shell. This bug predates this change: no file on the local disk could be served. The catch-all now skips the same server prefixes as the worker, and both comments name the other list. | patch |
| 2 | The PHP "never serves the shell" test covers only the old three paths | low | It is a direct addition. It now covers one path under each new prefix. | patch |
| 3 | The Playwright loop checks where each answer came from, not what it was | low | Finding 2 now covers the server's side. The worker's side is what this test is for. | reject |
| 4 | The `toContain` checks in `AppShellTest` are loose | low | The Playwright test checks the real behaviour. The extra checks add little. | reject |
| 5 | The Livewire prefix lookup falls back silently to an unhashed path | low | It is a direct correction: the test now asserts the hashed prefix. | patch |
| 6 | The console test has no positive control, so it passes with no worker at all | medium | `clientsClaim: false`, and a failed registration also leaves `fromWorker` empty. The test now asserts `navigator.serviceWorker.controller`. | patch |
| 7 | The Livewire regex exists in three places | low | They are test copies of a vendor naming rule, and the app test reads the real prefix. No named divergence. | reject |
| 8 | Filament's route prefix can be configured | low | It uses the default, and nothing sets it. | reject |
| 9 | `goto().catch()` swallows every navigation error | low | `waitForResponse` still fails the test at its timeout. | reject |
| 10 | Files in `public/` (`/robots.txt`, icons, `/sw.js`) opened directly get the shell | low | Real: `navigateFallback` answers any navigation not on the list. The fix is a single entry (paths with a file extension), plus `/robots.txt` in the test. | patch |
| 11 | The test names are out of date | low | Direct rename. | patch |
