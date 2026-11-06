---
title: 'Story 1.5: Each role sees only its own work'
type: 'feature'
created: '2026-11-06'
status: 'done'
baseline_commit: '9363f16728a069ceb221933cf9aaae786d2637aa'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/implementation-artifacts/epic-1-context.md'
  - '{project-root}/_bmad-output/implementation-artifacts/spec-1-4-admin-creates-and-deactivates-staff-accounts.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** The console has no capability matrix. The Staff page guards itself with a one-off Admin check, every other page is open to any role, and Operations accounts have no way to gain or lose Support duties (FR-52, UX-DR29, UX-DR38).

**Approach:** Add one capability registry in the `Staff` module that maps each capability to Finance, Operations, Admin or the Support duties group. Every console resource, page and Action declares the capability it needs, and an architecture test names any that does not. The server checks the registry on every page load and every Action. The rail shows only what the role can use, a refused page names who can, and an Admin switches Support duties on or off per Operations account from the Staff page, audited.

## Boundaries & Constraints

**Always:**
- The registry is the single source: page access, rail visibility, Action checks and the Staff page's Duties column all read it. Admin holds every capability.
- Support duties are their own group in the registry, held by an Operations account only while its switch is On, so a later Support role can take the group over.
- Every concrete `Action` and every console resource or page declares either the capability it needs or, for Actions with no staff actor (sign-in, invitation, listeners, system jobs), that it needs none and why. A missing or unknown declaration fails an architecture test that names the file, with a fixture and a planted-file check like the existing rules, and a CI step that proves the rule fails on its fixture.
- The server enforces every rule: a page the role cannot use is refused on load, and an Action called by a staff member without the capability is refused, whatever the screen offered.
- The rail is grouped Queues, Work, Money, Catalogue, People, Rules, Watch, with Today first. A role sees only its own groups and links; a group with nothing the role can use is not shown; nothing is greyed out and there is no padlock. The current place is marked visually and with `aria-current`, and each group is announced as a labelled list.
- A refused page uses "shown, and who can": it names the role that can and an active person who holds it, never "Access denied", "Forbidden" or a lock icon, and keeps the console shell.
- The Support duties switch shows the word On or Off beside its track, is announced with that word, takes effect on that person's next request with no release, and writes an audit row naming the Admin and the from/to value.
- Switching runs through a `Staff` Action that locks the member, refuses accounts that are not Operations or are deactivated, and audits in the same transaction (AD-4).

- **Decision (owner, 6 November 2026): the spec is kept whole** at about 1,950 tokens.

**Never:**
- No Approvals page, "acting as" or second-approver flow (Epic 2); no deputies or out-of-office (Epic 4).
- No Support desk or other screens behind the capabilities; later epics add them and their capabilities.
- No capability stored per person beyond the Support duties switch; no role editing.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Finance rail | Finance member signs in | Rail shows Queues with Today only; no People group | N/A |
| Admin rail | Admin signs in | Every group that has a page, including People with Staff | N/A |
| Outside link | Operations opens `/console/staff` | Refused on load; page says the Staff page is for Admins and names an active Admin | 403 inside the console shell |
| Action refused | Finance calls a Staff Action directly | Refused by the server in words naming who can | No change, no audit row |
| Switch on | Admin turns Support duties On for an Operations account | Member gains the Support group on their next request; Duties reads "Goods and Support"; audit row | N/A |
| Switch off | Admin turns it Off | Member loses the Support group on their next request; audit row | N/A |
| Wrong account | Switch for a Finance, Admin or deactivated account | Not offered on screen; the Action refuses in words | `TransitionRefused` or authorization refusal |
| Undeclared | A new Action or console page declares no capability | Architecture test fails naming the file | N/A |

</frozen-after-approval>

## Code Map

- `app/Modules/Staff/Models/StaffRole.php` -- `duties()` is fixed per-role text from Story 1.4; replace with words derived from the registry ("Money", "Goods", "Goods and Support", "Everything", as in the step-16 mockup).
- `app/Modules/Staff/Models/StaffMember.php` -- add `support_duties` (boolean, default false) and a cast; `isActiveAdmin()` stays.
- `app/Modules/Staff/Actions/StaffAccountAction.php` -- `assertActiveAdmin()` is the one-off check to replace with the registry check; keep the last-Admin and locking logic untouched.
- `app/Modules/Staff/Filament/StaffResource.php` (`canAccess()`), `StaffResource/ListStaff.php` (`authorizeResourceAccess()`, `REFUSED`, `attempt()`), `views/staff-list.blade.php` -- move to the declared capability, add the switch in the Duties cell of Operations rows, show refusals in words above the table as Story 1.4 does.
- `app/Modules/Staff/Filament/Pages/Today.php` -- open to every role; declare it. `SignIn.php` and `AcceptInvitationPage.php` declare that they need none.
- `app/Modules/Staff/Filament/ConsolePanelProvider.php` -- register the ordered navigation groups; Filament hides items whose `canAccess()` is false and drops empty groups.
- `vendor/filament/filament/resources/views/components/sidebar/group.blade.php` -- already renders a `<ul>` per group with `aria-label` and `aria-current="page"`; verify in the accessibility run before overriding anything.
- `app/Modules/Staff/Providers/StaffServiceProvider.php` -- declares Staff's personal fields and outbox listeners through `callAfterResolving`; register Staff's capabilities the same way so other modules can add theirs later.
- `app/Modules/Shared/` -- declaration attributes belong here so any module can use them without importing `Staff`; keep them out of `Shared/Contracts/`, which the provider-fake rule scans.
- `tests/Architecture/Support/{RealScans,SourceFiles,ClassIndex,Violation}.php`, `tests/Architecture/ProviderFakesTest.php`, `tests/Architecture/Fixtures/` -- the pattern for a new rule: real scan, fixture, planted file, `Violation::report()`.
- `.github/workflows/ci.yml` -- the "Each architecture rule fails on its fixture" step lists rule names; add the new one.
- `app/Modules/Staff/Tests/StaffPageTest.php`, `tests/Flows/StaffAccountsFlowTest.php` -- Story 1.4's wrong-role tests expect `ListStaff::REFUSED`; update them to the new words.
- `tests/Accessibility/console.spec.ts` -- signs in as the founder; the `invited-staff` scenario seeds an invitee. Add a seeded Operations member to check its rail and refused page.

## Tasks & Acceptance

**Execution:**
- [x] `app/Modules/Shared/Capabilities/` -- declaration attributes (needs a capability; needs none, with a reason) -- one declaration shape for every module.
- [x] `app/Modules/Staff/Capabilities/` -- the registry: capabilities, their holders (Finance, Operations, Admin, Support duties), `allows()`, the words naming who can, and the active people who hold it; the Staff capabilities (`console.today`, `staff.manage`, `support.desk`) -- the single source of FR-52.
- [x] `app/Modules/Staff/Database/Migrations/` -- add `support_duties` -- the switch's state.
- [x] `app/Modules/Staff/Actions/SetSupportDuties.php` -- lock, check, audit -- the switch's write path.
- [x] `app/Modules/Staff/Actions/*`, `app/Modules/Notifications/Actions/SendEmail.php` -- declare each Action; Staff Actions with an acting member check the registry -- server enforcement.
- [x] `app/Modules/Staff/Filament/*` -- declarations, `canAccess()` from the registry, the refused page, the ordered rail groups, the switch and Duties words.
- [x] `tests/Architecture/` and `.github/workflows/ci.yml` -- the declaration rule with fixture, planted file and CI step.
- [x] Tests (Pest) -- every matrix row; registry holders per role and with the switch On and Off.
- [x] `tests/Accessibility/console.spec.ts` and a seeded Operations scenario -- the rail per role, `aria-current`, groups as lists, the refused page and the switch, with no axe violations.

**Acceptance Criteria:**
- Given a keyboard or screen-reader user, when they move through the rail, then the current place is marked and announced and each group is announced as a list.
- Given the switch, when it is focused, then a screen reader announces "Support duties" with the word On or Off.

## Design Notes

- Existing and new Operations accounts start with Support duties Off (least privilege); an Admin turns them on.
- A refused page names one active holder by name (alphabetically first), e.g. "The Staff page is for Admins. Ask Ada Founder."

## Implementation Notes

- Declarations: `Shared/Capabilities/{NeedsCapability,NeedsNoCapability}` (class attributes; the reason is required). The rule covers every concrete `Shared\Actions\Action`, every concrete class in a module's `Actions/` folder with a public `handle()` (Lock actions aside; the kernel's `RelayOutboxEvents`, `WriteAuditEntry`, `WriteOutboxEvent` declare none, with reasons), and every Filament `Resource` and page. Staff-account Actions and `StaffResource` need `staff.manage`; `Today` needs `console.today`; sign-in, invitation, session and second-factor Actions and pages, `RevokeStaffSessions` (nested) and `Notifications/SendEmail` (listeners) declare none.
- The declaration is the check (review row 1):
  - Actions: `Shared/Actions/Action::authorizeCapability($actor)` reads the class's own `#[NeedsCapability]` and asks `Shared/Capabilities/CapabilityCheck` (an interface, so the kernel never imports `Staff`), bound by Staff to `RegistryCapabilityCheck`. `StaffAccountAction::lockActorAndMember()` and `CreateStaffAccount` call it on the actor read under lock. The rule fails an Action that declares a capability but never calls it (in its own body or a parsed base class).
  - Pages and resources: `Staff/Filament/GuardedByCapability` gives `canAccess()` from the declaration and, on load, the in-shell "shown, and who can" refusal (`staff::refused-page`, sent as 403 by `RefusedPageStatus`); every later Livewire call gets a 403 in the same words. A resource's pages declare nothing of their own and take the resource's capability through the trait. The rule fails a declaring page or resource without the trait, a resource page with its own declaration, and a resource page without the trait.
  - `tests/Architecture/ConsolePanelCapabilitiesTest.php` walks what the console panel serves (every `filament.console.*` route to a page, and every registered resource), exempting Filament's own MFA set-up page by an explicit list.
- Registry: `Staff/Capabilities/CapabilityRegistry` (singleton; `StaffServiceProvider` declares `StaffCapabilities`). Groups are `CapabilityGroup` (Finance, Operations, SupportDuties, Admin); Admin holds every capability; `console.today` is also held by the Support duties group. `allows()` needs an account that can sign in; `authorize()` refuses an actor whose own account is not Active with "Your account is not active, so nothing was changed.", otherwise a role refusal naming who can and the alphabetically first active holder other than the actor. Unknown capabilities throw. Other modules cannot reach the registry yet (AD-2): the first one to add a capability needs a public way in, such as a declaration list in `Shared`.
- Duties words come from the groups: Money, Goods, Goods and Support, Everything. A deactivated account never counts the Support duties group, and deactivation clears `support_duties` in its own audit row; reset and resend keep it.
- `SetSupportDuties` returns `SupportDutiesSet` (member, changed). When nothing changed it writes and logs nothing, and the Staff page says "Support duties were already On for ...: <Admin> switched them On at <time>. Nothing was changed." from the audit trail.
- Rail: `ConsoleRail` registers Queues, Work, Money, Catalogue, People, Rules, Watch (not collapsible); Today is in Queues, Staff in People. `Staff/Filament/views/vendor/filament-panels/components/sidebar/group.blade.php` is Filament 5.10.3's file with only `aria-labelledby` on each group's list; re-compare on a Filament upgrade. Group labels use `--ledger-ink-secondary` for contrast.
- Switch: a `button role="switch"` with `aria-checked`, named "Support duties for <person>: On/Off" (the person part visually hidden), never disabled while the request runs (`aria-busy` instead) so keyboard focus stays on it.
- Architecture rule: `CapabilityDeclarationsTest` with `Support/CapabilityDeclarationScanner`, fixtures in `Fixtures/Capabilities` (Orders fixtures name capabilities by string; passing guarded fixtures sit in a Staff fixture module) and planted files; reported as "FR-52, every console page and Action declares its capability" in the CI fixture step.
- TC-4: the `active-operations` scenario (Chika Operations, known test password and authenticator secret, Support duties Off); CI seeds `first-admin invited-staff active-operations`.
- The Story 1.4 migration round-trip test rolls back three steps now that `support_duties` is the newest migration.

## Spec Change Log

## Review Triage Log

Review pass 1 (blind-hunter BH, edge-case-hunter EC, acceptance-auditor AA, accessibility run A11Y), 6 November 2026.

| # | Layer | Finding | Verdict | Evidence | Route |
|---|-------|---------|---------|----------|-------|
| 1 | BH, EC, AA | A `#[NeedsCapability]` declaration enforces nothing by itself: a page without the `canAccess()` override, or an Action outside `StaffAccountAction`, is open to every role while the rule passes; `ListStaff`'s own declaration is ignored at runtime | high | Filament's default `canAccess()` is true; only `StaffAccountAction` calls `authorizeActor()` | patch |
| 2 | AA, EC | Only `ListStaff` renders the in-shell "shown, and who can" refusal; any other refused page gets Filament's bare 403 ("Forbidden") | medium | refusal wired by hand in `ListStaff::mountCanAuthorizeResourceAccess` | patch |
| 3 | EC | The rule scans only files under `app/Modules`, not the pages and resources registered on the panel | medium | `RealScans` source roots | patch |
| 4 | A11Y | Rail group headings fail contrast | high | Filament `text-gray-500` on the aged surface; `--ledger-ink-secondary` gives 8:1 | patch |
| 5 | BH | Every switch is named "Support duties On/Off" with no person | medium | name from the button text only | patch |
| 6 | BH | `wire:loading.attr="disabled"` drops keyboard focus from the switch mid-request | medium | disabled controls lose focus | patch |
| 7 | BH, EC | A switch call that changes nothing still logs a change and tells the second Admin "now On" | low | `Log::info` after a skipped save | patch |
| 8 | EC | `support_duties` survives deactivation: a deactivated row reads "Goods and Support" with no way to switch it off | low | `groupsOf()` ignores status | patch: deactivation clears it in the same audit row; reset and resend keep it (same person, the Admin's choice stands) |
| 9 | EC | `console.today` is not held by the Support duties group, so a later Support role would be refused the first page | low | `StaffCapabilities::declareIn` | patch |
| 10 | AA, EC | Refusal blames the role when the real reason is the actor's account status; the switch's refusal says "Adding, changing and deactivating staff accounts" | low | `actionRefusal()` | patch |
| 11 | AA | Classes in `Actions/` that do not extend the Action base (the relay, audit and outbox writers) are outside the rule, narrower than "system jobs" | low | scanner scoped to `Shared\Actions\Action` | patch |
| 12 | AA | No fixture proves an undeclared resource is reported | low | `OrderResource` is only a passing case | patch |
| 13 | AA | The Orders fixture imports `Staff\Capabilities`, modelling the import AD-2 forbids; registry docblock says other modules declare from their providers | low | fixture line 8; registry line 18 | patch |
| 14 | AA | Audit from/to tests compare with `toEqual` | low | `0`/`1` would pass | patch |
| 15 | BH | A refused member could call `getMembers()` and read staff details | false | Filament's `hydrateCanAuthorizeResourceAccess` re-checks on every Livewire request and answers 403; `StaffPageTest` covers it | reject |
| 16 | AA | "Next request" for the switch is proved through `allows()`, not a real request | false | no Support screen exists yet; the server reads the registry per request | reject |
| 17 | EC | Anonymous Action classes are skipped by the scanner | reject | none exist; a declared rule for named classes is enough | reject |

## Verification

**Commands:**
- `docker compose run --rm --no-deps -T app sh -c 'php artisan config:clear -q; php artisan test'` -- expected: all pass (not `composer test`, whose process timeout kills the run).
- `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` -- expected: clean.
- `npm run lint`, `npm run typecheck`, `npm test`, `npm run build` -- expected: clean.
- The accessibility run (Playwright + axe) -- expected: no violations; run by the orchestrating session, never by the implementer.
