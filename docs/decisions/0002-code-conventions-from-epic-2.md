# 0002. Code conventions from Epic 2 on

- **Status:** accepted
- **Date:** 16 November 2026
- **Decided by:** the architect role, from the Epic 1 retrospective (action A6)
- **Settles:** the pattern drift recorded in
  `_bmad-output/implementation-artifacts/epic-1-retro-2026-11-15.md` (strict types, how the Clock is reached,
  `final`), Story 1.5's open seam for other modules' capabilities, and the Filament upgrade risk.

## Context

Epic 1 left five places where a later story would copy whichever pattern it saw first:

- Only 2 of 168 files under `app/` declare `strict_types` (`Shared/Formats/Format.php` and
  `InvalidFormatInput.php`).
- The Clock is injected by constructor almost everywhere. Five production places call `app(Clock::class)`.
- Most concrete classes are `final`, but `InvitationLinks`, `AuditChain` and `AppServiceProvider` are not.
  `AuditChain` lost `final` so one test could mock it.
- Only `Staff` can declare capabilities. `CapabilityRegistry` and `CapabilityGroup` live in `Staff`, which
  other modules may not import (AD-2).
- The console copies one Filament view (`sidebar/group.blade.php`, from Filament 5.10.3) and restyles it in a
  776-line `theme.css` that targets 56 distinct `.fi-*` classes. Any Filament release can break either.

## Decision

### 1. No `strict_types`

No file declares `strict_types`. This follows Laravel and its Pint preset. PHPStan at level 6 and the test
suite catch type mistakes. A file that declares it changes how its calls coerce scalars, and a rule that
holds only sometimes is worse than no rule. The two declarations in `Shared/Formats` are removed the next
time a story touches those files, with the format tests run.

### 2. The Clock comes in by constructor

A class the container builds takes `Clock` in its constructor. That covers Actions and their base classes,
Queries, listeners, jobs, commands, middleware, and Livewire components (through `boot()` or method
injection). `app(Clock::class)` is allowed only where there is no constructor to inject into: Eloquent
models, static helpers, and Filament's static render hooks. Today that is `StaffMember::canSignIn()`,
`ApiResponse::serverTime()` and `ConsoleShell::identity()`. Two container-built classes break the rule and
are fixed the next time their files change: `StaffAccountAction::issueInvitation()`, an Action base class,
and `RefusalWords::when()`. Reading the real time anywhere else is already refused by `ClockOnlyTest`
(TC-2).

### 3. `final` by default

Every concrete class is `final` unless something extends it, Eloquent models included (`StaffMember` already
is). Laravel's generated `AppServiceProvider` may stay open. A test never
removes `final` to mock a class. It swaps a fake through the container, or the class gets an interface
the test can fake. `InvitationLinks` becomes `final` when it is next touched. `AuditChain` becomes
`final` once the test in `tests/Feature/Audit/AuditSealingTest.php` that mocks it moves to a fake.

### 4. A module declares its capabilities in `Shared`

This follows the pattern `PersonalFields` and `OutboxListeners` already use. The first module after `Staff`
that adds a capability builds the seam:

- `CapabilityGroup` moves to `Shared/Capabilities`. Its cases are the PRD's role names, which every module
  may use.
- `Shared/Capabilities/CapabilityDeclarations` is a container singleton with
  `declare(string $capability, string $what, CapabilityGroup ...$groups)`. A module adds its own in its
  service provider through `callAfterResolving(CapabilityDeclarations::class, ...)`. Each capability name
  starts with the module's own prefix, such as `orders.` or `catalogue.`.
- Staff's `CapabilityRegistry` reads those declarations and stays the only place that answers "who holds
  this". `StaffCapabilities::declareIn` moves to the same route.
- The capability constants (for example `OrdersCapabilities::CONFIRM_PAYMENT`) live in the declaring module.
  The `#[NeedsCapability]` declarations and the existing architecture rules apply unchanged.

### 5. A Filament upgrade has a checklist

Before merging any change to the `filament/filament` version in `composer.lock`:

1. Diff `vendor/filament/filament/resources/views/components/sidebar/group.blade.php` against the copy in
   `app/Modules/Staff/Filament/views/vendor/filament-panels/components/sidebar/group.blade.php`. Carry over
   Filament's changes, keep only our `aria-labelledby` change, and update the version named in the copy's
   header comment.
2. Check that every `.fi-*` class used in `resources/css/filament/console/theme.css` still exists in
   Filament's built CSS. Each missing one is a rule that silently stopped applying.
3. Run `npm run build`, then the full Playwright accessibility suite against a freshly seeded
   `integrator_a11y` database. It scans every console page and dialog, and checks the 3px focus ring and
   200% zoom.
4. Open the Today, Staff and Audit trail pages at 1280px and at phone width, and compare them by eye with the
   previous release. Look for fonts, rail spacing, table headers and dialog buttons.
5. Record the new version and anything that changed in the pull request.

## Consequences

- Epic 2 starts from one written pattern for each of the five points. Code review checks new code against
  this note.
- Points 1 to 3 change no code now. The existing exceptions are listed so they get fixed when their files are
  next touched, not in a sweep.
- Point 4 costs one move of `CapabilityGroup` and one new `Shared` class, made by the first module that needs
  it.
