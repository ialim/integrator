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
- Staff sessions last 12 hours with mandatory multi-factor authentication. The second factor is assumed to be an authenticator-app code until the founder decides. A warning appears 5 minutes before the session ends.
- The audit trail is append-only and hash-chained. It covers every state change to money, stock, access, identity data and pricing parameters. Entries hold identifiers and field differences, never personal values such as a NIN, name or email. They record such a field as "changed" or as a keyed hash. No application role can edit or delete entries.
- The Integrator app installs from the browser and opens without an address bar on Android 9+ Chrome and iOS 16.4+ Safari.
- Availability and recovery: 1-hour recovery point, 4-hour recovery time, a restore drill each quarter.
- When two actions race, the first valid one wins and the other gets the current state, never an error.
- Test blockers that are acceptance criteria, not optional tasks:
  - **TC-1:** PostgreSQL 17 is the only test database, and CI fails on any other connection.
  - **TC-2:** an injectable `Shared` Clock that tests and staging can set and advance. A one-tick sweep command follows later.
  - **TC-3:** every provider fake can script success, definitive failure, timeout or error per call, starting with email.
  - **TC-4:** test-only factories and a seeding command that create state through Actions. Neither exists in production.

## Technical Decisions

- **Stack:**
  - Backend: PHP 8.4, Laravel 13.x, Filament 5.x (console at `/console`), Sanctum 4.x, PostgreSQL 17.
  - App: React 19, TypeScript 6.0, Vite 8, vite-plugin-pwa 2.x, TanStack Query and Router, Tailwind 4.
  - Build: the app source lives in `resources/app/` and builds into Laravel's public assets.
- **Modules:** 13 modules under `app/Modules/`: Shared, Identity, Catalogue, Pricing, Projects, Orders, CreditNotes, Fulfilment, Documentation, Notifications, Returns, Staff, Reporting.
  - Each has `Actions/`, `Queries/`, `Locks/`, `Models/`, `Events/`, `Http/`, `Filament/`, `Contracts/`, `StatusTables/`, `Database/Migrations/`, `Database/Views/` and `Tests/`.
  - Repo-level test folders: `tests/Architecture/` and `tests/Flows/`.
- **Single write path:** only a module's Actions write its tables. Each module's base Filament Resource routes create, edit and delete through Actions, with no carve-out for plain fields.
- **Module boundaries:** modules use each other only through public Actions, Queries (which return DTOs) and Lock actions. They never import another module's models or query its tables. Cross-module foreign keys are `ON DELETE RESTRICT`. Architecture tests enforce all three rules and name the offending file.
- **Action contract:**
  1. Lock aggregate roots up front, in the fixed global order, through `Lock{Root}` actions. Nested Actions assert the lock is held and never lock on their own.
  2. Check the move against a declared transition table.
  3. Refuse with a typed `TransitionRefused`, which becomes HTTP `409` with the current resource.
  4. Require an `Idempotency-Key` for client-retryable operations.
  5. Audit inside the transaction, naming the actor (User, staff member or `system`) with optional `acting_as` and `acting_for`.
  6. Emit events to the outbox in the same transaction, with event id and a per-aggregate sequence. Listeners are idempotent. A provider-calling listener records a delivery key (event id plus channel) under a unique constraint before calling.
- **Ids:** every primary key is a UUIDv7 from the `Shared` helper. Human-facing references are separate columns.
- **Providers:** each external provider sits behind an interface owned by its module, with a production adapter and a fake. Staging and tests bind the fakes, and staging holds synthetic data only. Provider calls happen only in listeners or a dedicated Action step.
- **Authentication:** staff use a separate guard with 2FA. A staff session is refused on `/api/v1`, and a User account never gains staff access. The database session driver makes sessions revocable.
- **Audit storage:**
  - Rows are inserted with a `bigserial` sequence and no hash.
  - A single-writer scheduled sealing job chains the hashes in sequence order.
  - A database trigger rejects `UPDATE` and `DELETE`.
  - Each module keeps a list of its personal-data columns that the audit writer reads.
  - Open spike: whether Laravel Cloud Postgres allows a least-privilege application role. If it does not, record the fallback decision (hash chain plus offsite copy) in the repository.
- **Hosting:**
  - Laravel Cloud (EU West, London) with Serverless Postgres 17, managed queue, scheduler and R2 object storage.
  - The database driver backs cache, locks, rate limits and sessions.
  - Only standard PostgreSQL, the S3 API and standard Laravel features, so nothing ties the code to the host.
  - Every hour, an encrypted dump and an object-storage copy go to a separate provider.
  - The restore runbook recreates the audit trigger and database roles before any write.
  - Secrets live in the host's secret store.
  - Founder alerts cover failed jobs, a failed or missing backup, spend, a failed audit seal and (later) the sweep heartbeat.
- **API conventions:**
  - Base path `/api/v1`, with `snake_case` JSON.
  - Errors are `{message, errors}` plus a machine `code`.
  - Every response carries `server_time`, and all times are ISO 8601 UTC (stored UTC, displayed in WAT).
  - Logs are structured JSON with the request id, and never contain NIN, bank details or file contents.
- **Settings:** staff-tunable settings are database rows changed through Actions, never `.env` entries.
- **App shell:**
  - One React SPA served by Laravel, with a manifest and a service worker.
  - No Inertia, no Livewire, and no Blade page beyond the shell.
  - System fonts only, with no bundled font file.
- **CI:** runs unit, feature and architecture tests, the linters and an automated accessibility check on every screen. Any failure blocks the merge.

## UX & Interaction Patterns

- **Ledger tokens:** one source for both the console theme and the app, in light mode only.
  - Colours: ledger-green `#153D2E`, surface-paper `#FAF6EC`, surface-aged `#F0E9D6`, ink `#1A1812`, brass `#6B4E0C`, error `#8A1C14`.
  - Type: serif titles and sans body on system stacks, 12px minimum.
  - Spacing: a 4px-based scale.
  - Shapes: 2px radius on controls, and no shadows, gradients or pill shapes.
  - Icons: line icons, always paired with words.
- **Console shell:**
  - Built for 1280px: a green top bar with the "integrator" wordmark, "Staff console", the current WAT time, and the person's name and role, plus a 224px rail on the aged surface.
  - Below 1024px or at 400% zoom, the rail becomes a menu button, panes stack and wide tables scroll inside their own box.
  - A phone gets a notice that the console is for a desktop.
- **Role-aware rail:**
  - Groups: Queues, Work, Money, Catalogue, People, Rules, Watch, with Today first.
  - It shows only what the role can use, with nothing greyed out and no padlock. Admin sees every group.
  - A page outside the role is refused, and the page names who can do it, never "Access denied".
- **Console tables:** fixed header, a caption for screen readers, and J, K, Enter and / keyboard movement.
- **Dialogs:** the title is a question naming the subject, the body lists what will happen, and red is used only for the irreversible button.
- **Switches** show the word On or Off.
- **Lost races** show who did it and when.
- **Records:** each record has a read-only History panel, and money and audit records have no Edit or Delete.
- **App shell:**
  - Signed-out holding screen with the wordmark.
  - Text pages on one template: Install the app (iPhone steps in words), accessibility statement, not found, signed out, service down.
  - Install is offered once from the second visit, and is always available on the Install page.
  - A new version never reloads under the person. An "Update when you finish" banner appears instead.
  - At 320px: one column, 16px margin, no sideways scroll, and both orientations work.
  - Measure the cold-start baseline on a 2 GB Android over slow 3G.
- **Shared formatters:** one set of formatters, each with tests, for money, dates, ranges, WAT times, time left, phone numbers, references, read-aloud codes, serials, names and measures. The app and the console both use them.
- **Accessibility:** WCAG 2.2 AA.
  - A 3px focus ring, a skip link and reading-order focus.
  - Landmarks and one top heading per screen.
  - Field errors in words beside the field.
  - Works at 200% zoom, and honours larger-text and reduce-motion settings.
  - Every story's criteria state what keyboard, screen-reader and 200% zoom users can do.
- **Copy:** plain voice, sentence case, British spelling, no exclamation marks or emoji, and buttons say what they do (never Submit or OK).

## Cross-Story Dependencies

- **Story 1.1 comes first.** Every other story depends on the module layout, the architecture tests, the Clock, the UUIDv7 helper, the test seeding and CI.
- **Hosting and audit:** the Story 1.2 restore runbook must be extended by Story 1.6 to recreate the audit trigger and roles.
- **Before Story 1.6:** the least-privilege role spike must finish before the Staff audit work closes.
- **Staff stories:**
  - Story 1.3 creates the first Admin through a `Staff` Action, audited as `system`.
  - Story 1.4 adds the outbox relay, the email provider interface and its fault-injecting fake, and invitation sign-up.
  - Story 1.5 builds on the accounts and the rail from Stories 1.3 and 1.4.
- **Shared look:** Story 1.7 shares the tokens and formatters with the console from Story 1.3.
- **Later epics:**
  - Epic 2 builds the Approvals page, "acting as" and the founder's weekly review on top of this audit trail and staff model.
  - Epic 2 also builds the one-tick sweep command.
  - Epic 4 adds deputies, the holiday calendar, work items and unusual-pattern alerts.
  - Epic 3 adds Integrator sign-in on the app shell.
