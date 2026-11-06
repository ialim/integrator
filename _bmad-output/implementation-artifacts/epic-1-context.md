# Epic 1 Context: Staff console, roles, audit trail and the app shell

<!-- Compiled from planning artifacts. Edit freely. Regenerate with compile-epic-context if planning docs change. -->

## Goal

Lay the foundation every later epic builds on. The project is scaffolded as a modular monolith with its structural rules checked automatically. Production runs with hourly offsite backups and alerts to the founder. Admin, Finance and Operations sign in to a separate console with two steps and see only their own work. Every change writes to an audit trail nobody can edit, and the installable Integrator app shell opens on a low-end phone. This epic also builds the shared kernel parts (Action base, lock registry, transition helper, outbox, audit, UUIDv7, Clock) and the test infrastructure. The epic closes only when a real staff Action exercises each kernel part and the four test blockers (below) exist.

## Stories

- Story 1.1: Scaffold the project with its rules enforced
- Story 1.2: Production hosting with an hourly offsite copy and founder alerts
- Story 1.3: Staff sign in to the console with two steps
- Story 1.4: Admin creates and deactivates staff accounts
- Story 1.5: Each role sees only its own work
- Story 1.6: An audit trail nobody can edit
- Story 1.7: The Integrator app installs on a phone

## Requirements & Constraints

- Staff roles are Finance, Operations and Admin, with capabilities from one matrix. Support duties are a separate permission group held by Operations, switched on or off without a release. The last active Admin can never be deactivated. Deactivation revokes every session at once.
- Staff sessions last 12 hours with mandatory two-step sign-in. A warning appears 5 minutes before the session ends, offering "Stay signed in".
- The audit trail is append-only and hash-chained. It covers every state change to money, stock, access, identity data, pricing parameters, Cases and Return Requests, and every read of a NIN or bank detail (with a reason code). Entries hold identifiers and field differences, never personal values such as a NIN, name or email; such a field is recorded as "changed" or as a keyed hash. No application role can edit or delete entries.
- The Integrator app installs from the browser and opens without an address bar on Android 9+ Chrome and iOS 16.4+ Safari.
- Availability and recovery: 1-hour recovery point, 4-hour recovery time, a restore drill each quarter.
- When two actions race, the first valid one wins and the other gets the current state, never an error.
- Test blockers that are acceptance criteria, not optional tasks:
  - **TC-1:** PostgreSQL 17 is the only test database, and CI fails on any other connection.
  - **TC-2:** an injectable `Shared` Clock that tests and staging can set and advance. A one-tick sweep command follows in Epic 2.
  - **TC-3:** every provider fake can script success, definitive failure, timeout or error per call, starting with email.
  - **TC-4:** test-only factories and a seeding command that create state through Actions. Neither exists in production.

## Technical Decisions

- **Decided by the owner during this epic (treat as settled):**
  - Staff sign in with email and password, then an authenticator-app code (TOTP). This settles the open staff sign-in method question.
  - "Stay signed in" re-asks the authenticator code and starts a fresh 12-hour session; it never silently extends the old one.
  - Local development runs in Docker.
  - Founder alerts go by email, plus an external heartbeat monitor that alerts when expected pings stop.
  - The hourly offsite copy (encrypted database dump and object-storage copy) runs in GitHub Actions, outside Laravel Cloud.
- **Stack:**
  - Backend: PHP 8.4, Laravel 13.x, Filament 5.x (console at `/console`), Sanctum 4.x, PostgreSQL 17.
  - App: React 19, TypeScript 6.0, Vite 8, vite-plugin-pwa 2.x, TanStack Query and Router, Tailwind 4. Source in `resources/app/`, built into Laravel's public assets.
- **Modules:** 13 under `app/Modules/` (Shared, Identity, Catalogue, Pricing, Projects, Orders, CreditNotes, Fulfilment, Documentation, Notifications, Returns, Staff, Reporting), each with `Actions/`, `Queries/`, `Locks/`, `Models/`, `Events/`, `Http/`, `Filament/`, `Contracts/`, `StatusTables/`, `Database/Migrations/`, `Database/Views/`, `Tests/`. Repo-level `tests/Architecture/` and `tests/Flows/`.
- **Single write path:** only a module's Actions write its tables. Each module registers its own Filament resources, and every console create, edit and delete goes through an Action, with no carve-out for plain fields.
- **Module boundaries:** modules use each other only through public Actions, Queries (returning DTOs) and Lock actions; never another module's models or tables. Cross-module foreign keys are `ON DELETE RESTRICT`. Architecture tests enforce all three and name the offending file.
- **Action contract:**
  1. Lock aggregate roots up front, in the fixed global order, through `Lock{Root}` actions. Nested Actions assert the lock is held and never lock themselves.
  2. Check the move against a declared transition table.
  3. Refuse with a typed `TransitionRefused`, which becomes HTTP `409` with the current resource.
  4. Require an `Idempotency-Key` for client-retryable operations (same key, different payload returns `422`).
  5. Audit inside the transaction, naming the actor (User, staff member or `system`) with optional `acting_as` and `acting_for`.
  6. Emit events to the outbox in the same transaction, with event id and per-aggregate sequence. Listeners are idempotent; a provider-calling listener records a delivery key (event id plus channel) under a unique constraint before calling.
- **Ids:** every primary key is a UUIDv7 from the `Shared` helper. Human-facing references are separate columns.
- **Providers:** each sits behind a module-owned interface with a production adapter and a fake. Staging and tests bind the fakes; staging holds synthetic data only. Provider calls happen only in listeners or a dedicated Action step.
- **Authentication:** staff use a separate guard with mandatory 2FA. A staff session is refused on `/api/v1`, a User account never gains staff access, and a staff account never uses the app. The database session driver makes sessions revocable.
- **Audit storage:** rows inserted in the Action's transaction with a `bigserial` sequence and no hash; a single-writer scheduled sealing job chains hashes in sequence order; a database trigger rejects `UPDATE` and `DELETE`; each module keeps a list of its personal-data columns that the audit writer reads. Every read of a NIN or bank detail goes through an audited Action. Open spike: whether Laravel Cloud Postgres allows a least-privilege application role; if not, record the fallback (hash chain plus offsite copy) in the repository.
- **Hosting:** Laravel Cloud (EU West, London) with Serverless Postgres 17, managed queue, scheduler and R2 object storage. Database driver for cache, locks, rate limits and sessions. Only standard PostgreSQL, the S3 API and standard Laravel features. Secrets in the host's secret store. The restore runbook recreates the audit trigger and database roles before any write. Founder alerts cover failed jobs, a failed or missing backup, spend, a failed audit seal, outbox backlog and (later) the sweep heartbeat.
- **API conventions:** base `/api/v1`, `snake_case` JSON, errors `{message, errors}` plus a machine `code`, every response carries `server_time`, times ISO 8601 UTC (stored UTC, displayed WAT). Logs are structured JSON with the request id and never contain NIN, bank details or file contents.
- **Settings:** staff-tunable settings are database rows changed through Actions, never `.env` entries.
- **App shell:** one React SPA served by Laravel with a manifest and service worker; no Inertia, Livewire or Blade page beyond the shell; system fonts only.
- **CI:** unit, feature and architecture tests, linters and an automated accessibility check on every screen; any failure blocks the merge.

## UX & Interaction Patterns

- **Ledger tokens:** one source for the console theme and the app, light mode only. Key colours: ledger-green `#153D2E`, surface-paper `#FAF6EC`, surface-aged `#F0E9D6`, ink `#1A1812`, brass `#6B4E0C`, error `#8A1C14`. Serif titles and sans body on system stacks, 12px floor; 4px-based spacing; 2px radius on controls; no shadows, gradients or pills; line icons always paired with words.
- **Console shell:** built for 1280px: green top bar with the "integrator" wordmark, "Staff console", current WAT time, and the person's name and role, plus a 224px rail on the aged surface. Below 1024px or at 400% zoom the rail becomes a menu button, panes stack and wide tables scroll in their own box. A phone gets a notice that the console is for a desktop.
- **Role-aware rail:** groups Queues, Work, Money, Catalogue, People, Rules, Watch, with Today first. Shows only what the role can use, nothing greyed out, no padlock; Admin sees every group. A page outside the role is refused and names who can do it, never "Access denied".
- **Sign-in:** code entry is six boxes over one real input so paste and auto-fill work.
- **Console tables:** fixed header, screen-reader caption, J, K, Enter and / keyboard movement.
- **Dialogs:** title is a question naming the subject, body lists what will happen, red only for the irreversible button. Switches show the word On or Off. Lost races show who did it and when.
- **Records:** a read-only History panel on each record; money and audit records have no Edit or Delete.
- **App shell:** signed-out holding screen with the wordmark; text pages on one template (Install the app with iPhone steps in words, accessibility statement, not found, signed out, service down). Install offered once from the second visit, always on the Install page. A new version never reloads under the person; an "Update when you finish" banner appears. At 320px: one column, 16px margin, no sideways scroll, both orientations. Record the cold-start baseline on a 2 GB Android over slow 3G.
- **Shared formatters:** one tested set for money, dates, ranges, WAT times, time left, phone numbers, references, read-aloud codes, serials, names and measures, used by app and console.
- **Accessibility:** WCAG 2.2 AA: 3px focus ring, skip link, reading-order focus, landmarks and one top heading per screen, field errors in words beside the field, works at 200% zoom, honours larger-text and reduce-motion. Every story's criteria state what keyboard, screen-reader and 200% zoom users can do.
- **Copy:** plain voice, sentence case, British spelling, no exclamation marks or emoji; buttons say what they do (never Submit or OK).

## Cross-Story Dependencies

- **Story 1.1 comes first:** every other story needs the module layout, architecture tests, Clock, UUIDv7 helper, test seeding and CI.
- **Hosting and audit:** Story 1.6 extends the Story 1.2 restore runbook to recreate the audit trigger and roles. The least-privilege role spike must finish before the Staff audit work closes.
- **Staff stories:** Story 1.3 creates the first Admin (founder flag) through a `Staff` Action, audited as `system`. Story 1.4 adds the outbox relay, the email provider interface and its fault-injecting fake, and invitation sign-up (invitees set up password and authenticator before any console page). Story 1.5 builds on the accounts and rail from 1.3 and 1.4.
- **Shared look:** Story 1.7 shares the tokens and formatters with the console from Story 1.3.
- **Later epics:**
  - Epic 2 builds the Approvals page, "acting as", the founder's weekly review and the one-tick sweep command on top of this audit trail and staff model.
  - Epic 2's Supplier onboarding adds Supplier capabilities to the Story 1.5 registry and Supplier personal-data columns (and audited bank-detail reveals) to the Story 1.6 audit rules; nothing in Epic 1 changes for it. Before Story 2.9 ships, the Supplier bank-detail key joins the host secret store, the escrow store and the Story 1.2 restore drill.
  - Epic 3 adds Integrator sign-in on the app shell. Epic 4 adds deputies, the holiday calendar, work items and unusual-pattern alerts.
