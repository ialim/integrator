---
title: 'Admin records Suppliers and approves them against an onboarding checklist'
type: 'feature'
created: '2026-11-19'
status: 'done'
baseline_commit: '7ba72d53172340903a6e26b3d07f93b76c4287e6'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/implementation-artifacts/epic-2-context.md'
  - '{project-root}/docs/decisions/0002-code-conventions-from-epic-2.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** There are no Suppliers, so no Product can later be bought from an approved source (FR-14), and there is no onboarding record or checklist to approve one against (FR-81).

**Approach:** Add Suppliers to Catalogue with their business details, two contacts and three agreement terms, recorded by Operations or Admin. Add an Admin-configured onboarding checklist that gates "Approve the supplier", with "Approve with a reason…" as the override. Everything is written by audited Actions, on a Suppliers list and a Supplier page in the Catalogue rail group.

## Boundaries & Constraints

**Always:**
- A new Supplier is saved Not approved. Only the legal name is required to add one. Every other field may be missing and shows "Not stated".
- The 2.2 checklist items are Business details, Contacts (sales, and returns and RMA), Returns terms, Dead-on-arrival terms and Counterfeit chargeback. Stories 2.6 and 2.9 add document and bank items to the same mechanism, so items are keyed rows and the code does not assume five.
- Business details are Clear when the legal name, CAC RC number, TIN, address, invoicing currency (USD or NGN) and payment terms are all present. The trade name is optional. Contacts are Clear when both contacts have a name, a phone and an email. A term is Clear when its text is stated. Dead on arrival also needs its window in days, a whole number of 1 or more.
- The three agreement terms are always required, enforced by the Action and by a database CHECK. Changing whether an item is required is audited with a reason. It never un-approves anyone.
- Approval re-reads the checklist and the settings under lock. "Approve the supplier" needs every required item Clear. "Approve with a reason…" is offered only while some required item needs action, and its reason is stored and shown on the Supplier. Both are audited with who and when.
- Editing an approved Supplier never un-approves it. Its checklist just shows Needs action. Every edit after creation needs a reason.
- Contact names, phones and emails are declared as Catalogue personal-data columns (AD-21), so audit records "changed".
- Only Admin approves or changes the settings. Operations and Admin record and edit. Finance and Operations can view. The server refuses every out-of-role Action.
- The Product count is read through a Catalogue Query and is 0 until Story 2.3.
- Follow ADR 0002.

**Decisions (owner, 19 November 2026):**
- All five 2.2 checklist items start as required. Admin can make Business details or Contacts optional.
- Until Story 2.20, "Needs attention" means a Supplier with any required item at Needs action. Story 2.20 adds expiring or expired papers to the same chip.
- Operations and Finance see no approve or settings buttons. Instead one line reads "Only an Admin approves Suppliers or changes the checklist." Nothing is greyed out and there is no padlock (UX-DR29).
- One spec covers the story. The owner accepted the size, about 2,650 tokens.

**Never:**
- No documents, bank details, expiry warnings, Active Supplier, stopping a Supplier or Supplier-scoped upload (Stories 2.6, 2.9, 2.16, 2.17, 2.20).
- No un-approve and no delete.
- No Supplier data on any Integrator surface or API.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Add | Operations enters a legal name only | Saved Not approved, checklist "0 of 5 clear", audited | An empty legal name is refused beside the field |
| Approve, clear | Admin, all required items Clear | Approved, with who and when | N/A |
| Approve, not clear | Admin, 2 items need action | "Approve the supplier" is shown dashed and off, with "Off because 2 required items need action: …" | A forced call is refused in words |
| Approve with a reason | Admin, items need action, reason given | Approved with a reason, which is shown on the Supplier | An empty reason is refused beside the field |
| Make a term optional | Admin marks Returns terms optional | Refused | "The three agreement terms are always required." |
| Short DOA window | 14 days | Stored as 14. A Query gives min(14, 30) | 0 or below is refused beside the field |
| Other roles | Finance approves, or Operations changes the settings | Refused, nothing saved | Capability refusal naming who can |

</frozen-after-approval>

## Code Map

- `app/Modules/Catalogue/Actions/ProductRangeAction.php` -- the model for a `SupplierAction` base: `lockActor()` l.46, `reason()` l.134, `saveWithReason()` l.146 (AuditChanges::pending, then save, then audit with reason), and `guardingName()` l.164 (unique index matched by name).
- `app/Modules/Catalogue/StatusTables/ProductRangeStatus.php` -- the model for `SupplierStatus` (not_approved → approved; moves `edit`, `approve`, `approve_with_reason`). `Shared/StatusTables/TransitionRefused`.
- `app/Modules/Catalogue/Capabilities/CatalogueCapabilities.php` + `Shared/Capabilities/CapabilityDeclarations::declare($cap, $what, CapabilityGroup ...)` -- add `catalogue.suppliers.view` (Finance, Operations), `catalogue.suppliers.record` (Operations), `catalogue.suppliers.approve` and `catalogue.onboarding.configure` (Admin only, since Admin holds all).
- `app/Modules/Shared/Audit/PersonalFields.php` `declare()` l.23. Example: `Staff/Providers/StaffServiceProvider.php:61` and `StaffMember::PERSONAL_FIELDS`.
- `app/Modules/Shared/Locks/LockOrder.php:14` -- add roots `supplier` and `onboarding_setting` after `pricing_setting`. Copy `Catalogue/Locks/LockProductRange`.
- `app/Modules/Pricing/Providers/PricingServiceProvider.php:21` -- the page-registration pattern (`Panel::configureUsing`, `ConsoleRail::PANEL`, `loadViewsFrom`). Catalogue's provider adds its pages the same way, in `ConsoleRail::CATALOGUE`.
- `app/Modules/Pricing/Filament/PricingAndRules.php`, `ManagesPricingRules.php`, `views/pricing-and-rules.blade.php` -- patterns: a hand-written `console-table`, `mayCall()`, `reasonField()`, `attempt()` (field errors and `say()`), and `history()` through `Shared` `AuditHistory`. No chip or checklist component exists yet. Build the filter chips (with counts) and the Clear / Needs action checklist as reusable Shared Filament Blade components, because "Before it can go live" (Story 2.4) reuses them.
- `database/factories/TestStateScenarios.php::all()` l.22 + `database/factories/Catalogue/ProductRangesScenario.php` -- add a `suppliers` scenario (one approved, one approved with a reason, one not approved with gaps) through ActionFactories. Add it to the CI seed line in `.github/workflows/ci.yml` and the `console.spec.ts` header.
- `tests/Architecture/ConsolePanelCapabilitiesTest.php` -- add the new pages. `ForeignKeysTest`, `UuidKeysTest` and `SingleWritePathTest` must pass.
- `_bmad-output/planning-artifacts/ux-designs/ux-integrator-2026-10-07/mockups/console/flow-uj6-step18-console-content-suppliers-zones.html` l.296-470 -- frame 3 (list: summary line, "Onboarding settings…", "Add a supplier…", chips with counts, columns Supplier, Status, Products, Checklist) and frame 4 (record sections: Business details, Contacts table, Agreement terms). Leave out Papers, Bank details and the Active Supplier switch.

## Tasks & Acceptance

**Execution:**
- [x] `app/Modules/Shared/Locks/LockOrder.php` + `app/Modules/Catalogue/Database/Migrations/` -- add a `suppliers` table (UUIDv7, the fields above, `invoice_currency` CHECK in (USD, NGN), `doa_window_days` CHECK ≥ 1, status, `approved_by` FK to staff `restrictOnDelete`, `approved_at`, `approval_reason`) and an `onboarding_checklist_items` table (key, required, CHECK that the three term keys are required), seeded by the migration. Add the lock roots.
- [x] `app/Modules/Catalogue/` -- models, `SupplierStatus`, `LockSupplier`, `LockOnboardingSetting`, Actions `AddSupplier`, `EditSupplier`, `ApproveSupplier`, `ApproveSupplierWithReason` and `SetChecklistItemRequired`, a `Suppliers` Query (list rows with progress and chip counts, one Supplier's checklist, `productCount` returning 0, `doaWindowDays`), the capabilities and personal fields, and tests for every I/O row.
- [x] `app/Modules/Shared/Filament/` + `app/Modules/Catalogue/Filament/` -- Shared chips and checklist components. A Suppliers list page with chips, the add dialog and the settings dialog. A Supplier page with its sections, the checklist, the approve routes, the edit dialog with a reason, and History.
- [x] `database/factories/` + `tests/Accessibility/console.spec.ts` + `.github/workflows/ci.yml` -- the `suppliers` scenario, and an a11y test of both pages as Admin (dialogs, the dashed button and its reason, 200% zoom) and as Operations.

**Acceptance Criteria:**
- Given an Admin on a Supplier with gaps, when a screen reader reaches the checklist, then each item is read with its status in words, and the reason beside the off button is focusable and read.
- Given the Suppliers list, when it is viewed, then each row shows the Supplier, its status in words, a Product count and "N of M clear", and the chips All, Needs attention, Approved and Not approved filter it.
- Given an Admin makes an item optional, when an approved Supplier lacks that item, then it stays approved, and a later approval no longer needs that item.
- Given the full architecture suite, when it runs, then no module imports another's internals, and every new page and Action declares its capability.

## Implementation Notes

- `SupplierStatus` declares `edit` (from Not approved) and `edit_approved` (from Approved), because a move ends in one state and an edit must never change it. `editMove()` picks the right one.
- The checklist rule per key lives in `Queries/SupplierChecklist`. A new key without a rule fails loudly (LogicException), so Stories 2.6 and 2.9 must add theirs there and extend the migration's terms CHECK only if they add a term.
- "N of M clear" counts required items only. Optional items still show, marked "(optional)".
- `Suppliers::doaWindowDays()` gives 30 when no window is stated.
- An approval locks the actor, the Supplier, then every checklist row (`LockOnboardingSetting::all()`). The settings dialog calls `SetChecklistItemRequired` once per changed item, each audited with the same reason.
- `ApproveSupplierWithReason` refuses a Supplier that is already clear, so the override is only for gaps, on the server as on screen.
- Shared Blade components: `<x-shared::filter-chips>` and `<x-shared::checklist>` (anonymous component path registered in `SharedServiceProvider`).

## Spec Change Log

## Review Triage Log

Pass 1: Blind Hunter (BH), Edge Case Hunter (EC), Verification Gap (VG).

| # | Layer | Finding | Verdict | Evidence | Route |
|---|---|---|---|---|---|
| 1 | BH, EC, VG | The settings refusals are attached to a field `required`, which the form does not have | medium | `SetChecklistItemRequired` throws on `required`, but the form's fields are `required_<key>`. The "already" refusal is reached when another Admin changed the item meanwhile. | patch |
| 2 | EC | NBSP-only values get past the blank checks | low | `trim()` leaves NBSP. A LINE value collapses to '' and hits the legal-name CHECK as an error page. A TEXT term of NBSP counts as Clear. The fix is a direct correction. | patch |
| 3 | EC | A refused member gets a 404 for an unknown id and a refusal for a real one | low | Livewire runs the component's `mount()` before the trait's `mountGuardedByCapability` (`SupportLifecycleHooks.php:34-35`), so `$this->refusal` is still null there. | patch |
| 4 | BH | The DB CHECK allows windows above the Action's 9999 cap | low | `doa_window_days >= 1` against `DAYS_MAX`. The fix is a direct correction to an unshipped migration. | patch |
| 5 | BH | `Suppliers::checklist()` is unused | low | Nothing calls it. Deletion. | patch |
| 6 | BH | The Finance refusal test calls the Action as Operations | medium | `SuppliersPagesTest.php:212` passes an Operations member. Finance's server refusal is untested. | patch |
| 7 | VG | Half a dead-on-arrival entry (terms or window alone) is untested | medium | Pre-verified: no test has only one half. | patch |
| 8 | VG | The settings dialog is tested with only one item, true to false | medium | Pre-verified: no multi-item test, no back-to-required test, no unchanged-submit test. | patch |
| 9 | VG | The "(optional)" mark is untested | low | Pre-verified: grep finds nothing. The fix is a direct test. | patch |
| 10 | BH, EC, VG | The settings save is not atomic across items | low | Each item has its own transaction. A later item fails only on a concurrent change or a capability lost mid-request. A shared transaction adds new surface. | reject |
| 11 | BH, EC | A stale settings form re-applies its own values over another Admin's change | low | Last write wins, from what the Admin's form showed. Detecting stale forms adds state. Edits work the same way. | reject |
| 12 | BH | The edit dialog sends every field back, so the last save wins | low | This is the same pattern as Staff's edit and Story 2.1. Versioning is new surface. | reject |
| 13 | EC | Approve actions hidden by a concurrent approval drop the submit without words | low | The page then re-renders showing the Supplier approved, by whom and when. Rare, and the fix adds branches. | reject |
| 14 | BH, EC | Reasons have no length cap | low | This matches every Epic 1 and Story 2.1 reason. A cap adds a guard. | reject |
| 15 | BH | Pages re-read Suppliers several times per render, with no paging | low | Launch has tens of Suppliers. Memoising or paging is new structure. | reject |
| 16 | BH | Nothing stops duplicate Suppliers | low | The intent does not ask for uniqueness, and a constraint would add an error path. Staff see the list before adding. | reject |
| 17 | BH | `approved_by`, `lower(legal_name)` and `status` have no indexes | low | Staff are never deleted, only deactivated, and the table is small. | reject |
| 18 | BH | "Nothing was changed" is shown under the reason field | low | This is the Story 2.1 pattern, and the words are still read. | reject |
| 19 | BH | Untested: Admin edits, the approval race, the reason-on-not-approved CHECK, max lengths | low | Admin holds every capability, and the edit path is the same Action. The rest are guards whose failure is loud. | reject |
| 20 | BH | The spec and sprint status disagree | false | That is the workflow's order: sprint status moves to review at step 5. | reject |
| 21 | BH | A Query imports an Action's `DETAILS`, and `ChecklistItemState::words()` is trivial | low | Both are in the same module, so AD-2 is untouched. No named harm. | reject |
| 22 | BH, EC | The seed scenario cannot recover from a half-finished run | low | The same holds for every existing scenario. CI and a11y always seed a fresh database. | reject |
| 23 | BH | The rollback test hard-codes `--step 7` | low | The pattern predates this story. | reject |
| 24 | BH | Phone validation is loose, and emails are not lowercased | low | Staff type the values. Stricter rules are new rules. | reject |
| 25 | EC | A QueryException shows an error page | low | Same as Staff and Story 2.1 (2.1 finding 19). | reject |

## Verification

**Commands:**
- `docker compose run --rm --no-deps -T app sh -c 'php artisan config:clear -q; php artisan test'` -- expected: all pass
- `vendor/bin/pint --test` and `vendor/bin/phpstan analyse --no-progress --memory-limit=1G` -- expected: clean
- `npm run lint`, `npm run typecheck`, then the Playwright suite on a freshly seeded `integrator_a11y` database -- expected: all pass
- Never run `migrate:fresh` or any seeding against the `integrator` database (the owner's dev data). Use `integrator_a11y` for the a11y suite, as above.

Pass 1 follow-up: to show finding 1's refusal, the settings dialog now compares the submitted values with those the dialog opened with (`settingsShown`) and passes the Admin's chosen value. That also settles finding 11, because a stale form now gets the "already" words beside that item instead of reverting another Admin's change.

**Verified (after review pass 1):** 561 PHP tests pass (2,210 assertions). Pint and PHPStan are clean. `npm run lint` and `npm run typecheck` pass. All 23 accessibility tests pass against a freshly seeded `integrator_a11y`.
