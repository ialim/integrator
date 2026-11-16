---
title: 'One shared axe helper, and axe scans for the console states the check skips'
type: 'chore'
created: '2026-11-16'
status: 'done'
route: 'oneshot'
review_loop_iteration: 0
context: []
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** `tests/Accessibility/app.spec.ts` and `console.spec.ts` each define their own `WCAG` tags, `expectNoViolations` and `expectNoSidewaysScroll`. The two copies have already drifted: only the console's waits for animations to finish before axe runs (retro duplication map). The CI accessibility check also never runs axe on some console states: the resend and reset dialogs on the Staff page, the session-warning banner and its "Stay signed in" dialog, and the phone notice, which is checked only for presence. That falls short of UX-DR22's "every screen" (retro finding, action A3).

**Approach:** Move the WCAG tags and the two helpers into one shared module in `tests/Accessibility`. Keep the console's version, with the animation wait, and the app's optional "where" label for the sideways-scroll message. Both spec files import it. Then add axe scans: open and cancel the resend dialog (invited row) and the reset dialog (Operations row) in the Staff test; scan the phone notice at phone width in the Today test; and drive the browser clock to the last 5 minutes of the session so the warning banner shows, scan it, open "Stay signed in" and scan the dialog.

</frozen-after-approval>

## Implementation Notes

- **`tests/Accessibility/checks.ts` (new).** It holds the WCAG tags, `expectNoViolations` (the console's version, which waits for finite animations, now with a 15-second limit) and `expectNoSidewaysScroll` (the `where` label is now required, not optional as the Intent says: review finding 8 found that the optional default hid which page failed). `app.spec.ts` and `console.spec.ts` import it and lose their own copies. The app shell scans now also wait for animations. This is the behaviour change the merge brings.
- **Staff test.** It opens the resend dialog on the invited row and the reset dialog on the Operations row, checks that only the irreversible button is red, scans each dialog, and closes it without acting, so the seeded accounts stay as later tests need them.
- **Today test.** It scans the page at phone width with the phone notice showing. Then it installs Playwright's clock, reloads, and moves the clock 2 minutes into the warning. It works that time out from the page's `data-ends-at`, `data-server-time` and `data-warning-seconds`, so neither a slow runner nor a changed warning length breaks it. It scans the banner (and checks its `alert` role) at desktop size and at 200% zoom. It opens "Stay signed in", scans the dialog, closes it with "Not now", and checks that focus returns to the button. The server's session is never touched.
- **Surprise.** The banner's text has a line break after "3 min,", so the text pattern matches `\s+`.
- **Comments.** `playwright.config.ts` and the `console.spec.ts` header now list the newly scanned states.

## Review Triage Log

Blind Hunter, one pass (the oneshot route runs one review layer):

| # | Finding | Verdict | Evidence | Route |
|---|---|---|---|---|
| 1 | The fixed jump of 11 h 57 min ignores the time since sign-in and the server's warning length | medium | Real: the jump was counted from the reload. It now comes from the page's own data attributes. | patch |
| 2 | Focus returning to "Stay signed in" after "Not now" is not checked | low | It is a one-line assertion, now added. | patch |
| 3 | Escape on the Stay signed in dialog is not tested | low | It is a behaviour test beyond this scan item. A native `<dialog>` handles Escape. | reject |
| 4 | The banner's `alert` role is not asserted | low | It is a one-line assertion, now added. | patch |
| 5 | The Stay signed in dialog's error state is never scanned | low | Showing it means sending a wrong code to the server's extend endpoint. That is more than A3 asked for. | reject |
| 6 | The new states are not checked at 200% zoom | low | The banner is now checked through `expectWorksAt200PercentZoom`. The Filament dialogs at 200% zoom are left out: no Staff dialog is checked at zoom today, and A3 asked for scans. | patch (banner) |
| 7 | The resend and reset dialogs are checked less than the deactivate dialog | low | It is a direct addition: the not-red check and `toBeHidden`. | patch |
| 8 | The sideways-scroll calls have no labels | low | `where` is now required, and every call names its page. | patch |
| 9 | The animation wait has no timeout, so it falls back to the 300-second test timeout | low | It now has a 15-second timeout. | patch |
| 10 | `WCAG` is exported but nothing imports it | low | It is now private to the module. | patch |
| 11 | The spec's notes are empty, and the console header does not list the new scans | low | The notes are written now, and the header is updated. | patch |

**Verified:** all 20 accessibility tests pass against a freshly seeded `integrator_a11y` database. `npm run lint` and `npm run typecheck` pass.
