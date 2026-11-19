---
title: 'Admin sets up Product Ranges and their markup'
type: 'feature'
created: '2026-11-18'
status: 'done'
baseline_commit: 'd747ffedde0eb1644bd37fc649d84ccced4704d0'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/implementation-artifacts/epic-2-context.md'
  - '{project-root}/docs/decisions/0002-code-conventions-from-epic-2.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Nothing can be priced yet. No Product Range exists to hold a Platform Markup, and no margin floor exists to stop a price falling below a safe margin (FR-13, edge-case row 20). Catalogue and Pricing are also empty modules that cannot yet add a console page, a capability or a History view without importing Staff, which AD-2 forbids.

**Approach:** Add Product Ranges to Catalogue (name, compliance flag, active or retired) and each range's markup and the single margin floor to Pricing. Everything is written by Actions, audited, and edited with a required reason, on a "Pricing and rules" console page in the Rules group. First, open three kernel seams that every later Epic 2 module needs. A module declares capabilities in `Shared` (ADR 0002 point 4). It guards a console page through a `Shared` contract. It registers its own console pages, and shows a History from the audit trail.

## Boundaries & Constraints

**Always:**
- A new range starts at 25% markup. The floor starts at 8%, inserted once by the migration that creates its table. After that only an Action changes either.
- Percentages are `numeric(7,4)`. A markup below zero is refused by the Action in a sentence, and again by a database CHECK.
- Create, rename, markup change, compliance change, floor change and retire are each audited. Every change after creation carries a required reason, which is stored in the audit row's `changes` and shown in History.
- Only an Admin may write. The server refuses every other role on every Action, whatever the screen shows.
- A range with Products cannot be retired. The refusal says how many Products must be moved first. Until Story 2.3 that count is always 0, read through a Catalogue Query.
- Catalogue and Pricing reach Staff and each other only through public Actions, Queries and Lock actions. `CapabilityRegistry` stays the only resolver of who holds a capability.
- Follow ADR 0002: no `strict_types`, the Clock by constructor, `final` classes.

**Decisions (owner, 19 November 2026):**
- Finance and Operations both open the page read-only. Their rail shows it, and their writes are refused.
- Non-Admins see the same table and floor with no edit, add or retire buttons, under the line "Set by an Admin. Only an Admin can change these." No control is shown disabled (DESIGN.md).
- A retired range stays in the table, labelled Retired, with its History. It takes no new Products and has no further edits.
- One spec covers the kernel seams and the story (owner accepted the size, about 2,400 tokens).

**Never:**
- No preview of moved prices, no floor-breach list and no per-Product own markup: no Products exist yet (Stories 2.3 and 2.12).
- No other "Pricing and rules" sections (VAT, return windows and the rest). Only "Markups and the floor".
- No price arithmetic and no Money type.
- No reactivation of a retired range.
- Staff must not list Catalogue or Pricing classes in its panel.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Create | Admin enters a name and a compliance flag | Range saved Active at 25%, audited | Duplicate active name is refused in words |
| Change markup | Admin enters 30 and a reason | Saved, and History shows old, new and the reason | Empty reason: field error in words |
| Negative markup | Admin enters -1 | Nothing saved | "A markup cannot be below zero." |
| Change floor | Admin enters 10 and a reason | Floor saved and audited | Below zero refused in words |
| Retire with Products | Range holds N Products | Refused | "Move its N Products to another range first." |
| Other roles | Finance or Operations calls any write Action | Refused, nothing saved | Capability refusal naming who can |

</frozen-after-approval>

## Code Map

- `app/Modules/Shared/Capabilities/` -- has `NeedsCapability`, `NeedsNoCapability` and `CapabilityCheck`. Add `CapabilityGroup` here, moved from Staff without `ofRole()`, which becomes a private match in `CapabilityRegistry` (Shared may not import `StaffRole`). Add a `CapabilityDeclarations` singleton with `declare()`.
- `app/Modules/Staff/Capabilities/CapabilityRegistry.php` -- `declare()` l.40. It reads `CapabilityDeclarations` lazily, so names are complete before `has()` and `names()` (used by `RealScans::capabilities()` and `consolePanelProblems()`). `StaffCapabilities::declareIn` moves to `CapabilityDeclarations`.
- `app/Modules/Staff/Filament/GuardedByCapability.php` and `ConsoleAccess` -- the page guard. Its behaviour moves behind a `Shared` contract (`Shared/Filament/GuardedByCapability` trait plus a `ConsolePageGuard` interface bound by Staff). `CapabilityDeclarationScanner` (l.9, 120, 154, 217) then names the Shared trait.
- `app/Modules/Staff/Filament/ConsolePanelProvider.php` l.76-79 -- explicit pages and resources. A module adds its own through `Panel::configureUsing()` in its provider, filtered to the `console` id, and appends rather than replaces. `ConsoleRail::RULES` is at `ConsoleRail.php` l.25.
- `app/Modules/Staff/Filament/StaffResource/ListStaff.php` -- patterns to copy: `historyAction()` l.257, `attempt()` l.287, `say()` l.304, and reason fields l.161. `AuditWords` and `staff::audit-history` are Staff-only, so a shared History view moves to Shared Filament. `AuditTrail::historyOf()` is already in Shared.
- `app/Modules/Shared/Actions/Action.php` -- `transaction`, `saveAndAudit`, `audit`, `move`, `refuse` and `authorizeCapability` (l.50-159). `Staff/Actions/SetSupportDuties.php` is the closest model. Locks: `Shared/Locks/LockAction.php`, plus new roots in `LockOrder::ROOTS` l.14.
- `app/Modules/Staff/StatusTables/StaffAccountStatus.php` -- the model for `ProductRangeStatus` (active → retired).
- `app/Providers/ModuleServiceProvider.php` -- registers `Catalogue/Providers/CatalogueServiceProvider` and `Pricing/Providers/PricingServiceProvider` once they exist.
- `database/factories/TestStateScenarios.php`, `app/Console/Commands/SeedTestState.php` -- add `product-ranges` and `active-finance` scenarios through ActionFactories. Also add them to the CI seed line in `.github/workflows/ci.yml` l.187 and the `console.spec.ts` header.
- `tests/Architecture/*` -- `ModuleBoundariesTest`, `CapabilityDeclarationsTest`, `ConsolePanelCapabilitiesTest` (add the new page at l.184), `ForeignKeysTest` (Pricing → Catalogue `restrictOnDelete`), `UuidKeysTest`, `SingleWritePathTest`.
- `_bmad-output/planning-artifacts/ux-designs/ux-integrator-2026-10-07/mockups/console/flow-uj6-step16-console-admin-settings.html` l.245-322 -- the page's look: h1, subtitle, caption, columns, the edit panel and "Last three changes".

## Tasks & Acceptance

**Execution:**
- [x] `app/Modules/Shared/Capabilities/` + `Staff/Capabilities/*` + `Staff/Tests/CapabilitiesTest.php` -- move `CapabilityGroup`, add `CapabilityDeclarations`, and wire the registry to it. Behaviour does not change, and the existing tests must pass unchanged apart from their imports.
- [x] `app/Modules/Shared/Filament/` + `Staff/Filament/*` + `tests/Architecture/Support/CapabilityDeclarationScanner.php` -- add the Shared page-guard contract and trait (Staff binds it), and a Shared History view and its words. Staff's pages move to them.
- [x] `app/Modules/Catalogue/` -- provider, `product_ranges` migration (unique active name), `ProductRange` model, `ProductRangeStatus`, `LockProductRange`, Actions `CreateProductRange`, `RenameProductRange`, `SetRangeCompliance` and `RetireProductRange`, a Query for ranges and their Product count, the `catalogue.ranges.manage` capability, and tests.
- [x] `app/Modules/Pricing/` -- provider, `range_markups` table (FK `restrictOnDelete`, CHECK ≥ 0), `pricing_settings` table seeded with an 8% floor, Actions `SetRangeMarkup` and `SetMarginFloor`, a Query for markups and the floor, the `pricing.rules.manage` and `pricing.rules.view` capabilities, the "Pricing and rules" page, and tests. `CreateProductRange` calls `SetRangeMarkup` at 25% in the same transaction.
- [x] `database/factories/` + `tests/Accessibility/console.spec.ts` + `.github/workflows/ci.yml` -- add the scenarios, and an a11y test for the page as Admin (dialogs, 200% zoom) and as Operations.

**Acceptance Criteria:**
- Given a fresh install, when the floor is read, then it is 8%, and only `SetMarginFloor` changes it.
- Given an Admin, when they open Rules → Pricing and rules, then they see a table captioned "Platform Markup by Product Range", the floor, and History for each range and for the floor.
- Given the full architecture suite, when it runs, then no module imports another's internals, and every new page and Action declares its capability.

## Design Notes

The markup belongs to Pricing (AD-7) and the range to Catalogue. `CreateProductRange` is a Catalogue Action that calls Pricing's public `SetRangeMarkup` inside its own transaction. Locks are taken in `LockOrder` root order, so a range and its markup are never written apart.

## Verification

**Commands:**
- `docker compose run --rm --no-deps -T app sh -c 'php artisan config:clear -q; php artisan test'` -- expected: all pass
- `vendor/bin/pint --test` and `vendor/bin/phpstan analyse --no-progress --memory-limit=1G` -- expected: clean
- `npm run lint`, `npm run typecheck`, then the Playwright suite on a freshly seeded `integrator_a11y` database -- expected: all pass

## Implementation Notes

- **Review pass 1 deviation, noted.** The first task said the Staff capability tests "must pass unchanged apart from their imports". They also changed: the registry-names check compares Staff's own four, because Catalogue and Pricing now declare too, and Finance's and Admin's rails gain Rules, by the owner's decision.

## Review Triage Log

Pass 1: Blind Hunter (BH), Edge Case Hunter (EC), Verification Gap (VG).

| # | Layer | Finding | Verdict | Evidence | Route |
|---|---|---|---|---|---|
| 1 | BH, EC, VG | `guardingName` reports any unique violation as "name taken", worded with the merely trimmed name | medium | It catches every `UniqueConstraintViolationException`, including `range_markups.product_range_id`. Staff's `CreateStaffAccount` matches its own index by name. | patch |
| 2 | VG | The guard's race path is never run by a test | medium | Pre-verified: the only tests reach the pre-check or a raw insert. | patch (with 1) |
| 3 | EC, VG | `Percentage::parse` saves "--5" as 5% | medium | `ltrim($value, '-')` strips every dash after the single-dash check. | patch |
| 4 | EC | "2 5" is saved as 25% | low | `str_replace(' ', '', ...)` removes inner spaces. The fix is a direct correction. | patch |
| 5 | BH | Unicode minus, non-breaking space, ".5" and "27,5" fall to the generic message | low | Real, but rare, and the fix adds parsing branches. | reject |
| 6 | VG | Rename's taken-name, case-only and identical-name rules are untested | medium | Pre-verified: no test covers the `exceptId` branch or the same-name refusal. | patch |
| 7 | VG | Same-value refusals for markup, floor and compliance are untested | medium | Pre-verified: grep finds no test for them, and the dialogs open pre-filled. | patch |
| 8 | VG, EC | The retire-with-Products branch is tested only as a string helper | medium | `productCount()` is 0 until Story 2.3, so the branch cannot run. | defer |
| 9 | BH | The lock contract for retire versus adding Products is not written down | low | Nothing tells Story 2.3 to take `LockProductRange`. The fix is a docblock sentence. | patch |
| 10 | BH | `RangeCompliance::options()` lists cases by hand | low | A new case would drop out of the form. The fix uses `cases()`. | patch |
| 11 | BH, EC | Create needs two capabilities, but the button checks one | low | Only Admin holds either today, so nobody meets it. The fix adds a check path. | reject |
| 12 | BH | `SetRangeMarkup` skips the reason when no markup row exists | false | Every range gets its row in the same transaction as its creation, so a standalone call never finds none. The nested call receives the caller's locked actor. | reject |
| 13 | BH | Nothing warns when a markup is below the floor | low | The frozen intent excludes floor-breach checks until Products exist (Stories 2.3 and 2.12). | reject (out of scope by intent) |
| 14 | BH | A retired range's markup is refused beside the field | low | The page shows a retired range only its History, so the dialog cannot be opened for one. | reject |
| 15 | BH, EC | History lines name records by UUID | false | The History panel (`shared::audit-history`) never prints `record()`. Only the Audit trail page does, as it does for any non-staff subject. | reject |
| 16 | BH | The starting floor has no audit row, and the empty History text hardcodes "8%" | false | The empty text shows only while the floor has never changed, so it is still 8% then. The migration seed is the documented starting state. | reject |
| 17 | BH | `attempt()` aborts 404 on a missing range | low | Ranges are never deleted, so this is not met in use. | reject |
| 18 | EC | A missing floor row aborts with the range's 404 words | false | The migration inserts the row, and nothing deletes it. | reject |
| 19 | EC | A QueryException shows an error page, not words | low | This matches Staff's pages. A generic catch is new surface. | reject |
| 20 | BH | Dialog errors are not listed at the top of the form | medium | Epic 1's dialogs have no error summary either, so this predates the story. | defer |
| 21 | BH | `ActiveFinanceScenario` is unused by the a11y suite and cannot be re-run | low | The spec asks for a11y as Admin and Operations, and Feature tests use the scenario. Like `ActiveOperationsScenario`, it is not idempotent. | reject |
| 22 | BH | The spec says in-review while sprint status says in-progress | false | That is the workflow's own order: sprint status moves to review at step 5. | reject |
| 23 | EC | `CapabilitiesTest` changed beyond its imports, against the task's wording | low | Real deviation. The names check now covers Staff's own four, because other modules declare too, and the rail gains Rules by the owner's decision. The fix would edit this spec. | reject (see notes) |

**Verified (after review pass 1):** 516 PHP tests pass (1,905 assertions). Pint and PHPStan are clean. `npm run lint` and `npm run typecheck` pass. All 21 accessibility tests pass against a freshly seeded `integrator_a11y`: 17 in a first run that ran out of time, and the 10 console tests in a rerun.
