---
title: 'Story 1.1: Scaffold the project with its rules enforced'
type: 'feature'
created: '2026-10-25'
status: 'done'
baseline_commit: '767225e2c3497382b10d5df13df707dd31485921'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/implementation-artifacts/epic-1-context.md'
  - '{project-root}/_bmad-output/planning-artifacts/architecture/architecture-integrator-2026-10-09/ARCHITECTURE-SPINE.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** The repository holds only planning documents. Every later story needs the same stack, the 13-module layout and automated checks so the single write path (AD-1) and module boundaries (AD-2) cannot quietly break.

**Approach:** Scaffold Laravel 13 with Filament 5, Sanctum 4 and a React 19 / TypeScript app built by Vite 8 from `resources/app/`. Add the `Shared` kernel pieces this story owns (UUIDv7 id helper, injectable Clock, API envelope, JSON request-id logging, provider-fake binding, Action-driven test seeding), architecture tests for AD-1, AD-2 and the foreign-key rule, and a GitHub Actions CI on PostgreSQL 17 that blocks merges on failure.

## Boundaries & Constraints

**Always:**
- The stack versions in the architecture's Stack table: PHP 8.4, Laravel 13.x, Filament 5.x, Sanctum 4.x, PostgreSQL 17, React 19, TypeScript 6.0.x, Vite 8, Tailwind 4.
- All 13 modules exist under `app/Modules/` with every directory from Consistency Conventions (empty ones hold `.gitkeep`).
- Architecture-test failures name the offending file and line.
- Tests run only on PostgreSQL 17; the suite refuses to start on any other driver or server major version (TC-1, R-11).
- Code reads time only through `Shared` Clock; an architecture test forbids `now()`, `Carbon::now()`, `time()`, `date()` and `new DateTime*()` without an argument outside `Shared` (TC-2).
- Primary keys come from the `Shared` UUIDv7 helper (AD-11).
- Factories and the seeding command create state only by calling Actions and are registered only when `APP_ENV` is `local` or `testing` (TC-4).
- Provider interfaces in any module's `Contracts/` declare their fake; `testing` and `staging` bind the fake, and an architecture test fails a `Contracts/` interface without one (AD-15).

- **Decision (owner, 25 October 2026): local development runs in Docker.** A `compose.yaml` provides a PHP 8.4 container (with `pdo_pgsql`, `intl`, `mbstring`, `zip`, `bcmath`, Composer) and `postgres:17`; Composer, Artisan and Pest run inside it. Node and npm run natively (Node 24 is installed). Nothing else is installed on Windows.
- **Decision (owner, 25 October 2026): the spec is kept whole** at about 2,500 tokens rather than split.

**Never:**
- No domain table and no domain Action, Model or screen. Only Laravel's own framework tables (cache, jobs, sessions, migrations).
- No Inertia, Livewire or Blade page beyond the SPA shell view; no bundled font files.
- No staff sign-in, console screens, PWA manifest or service worker (Stories 1.3 and 1.7), and no hosting, backups or alerts (Story 1.2).
- No `.env`-based staff-tunable setting.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Health | `GET /api/v1/health` | `200`, `{"status":"ok","server_time":"2026-10-25T09:00:00Z"}`, snake_case keys | N/A |
| API error | Unknown `/api/v1/*` route or thrown validation error | `{message, errors, code}` with `code` such as `not_found` or `validation_failed`, plus `server_time` | Never an HTML error page under `/api/v1` |
| Clock frozen | Test sets the Clock to a fixed instant, then advances 2 hours | Reads return the set instant, then +2h, with no real wait | N/A |
| Wrong test DB | Test connection is SQLite, MySQL or PostgreSQL below 17 | Suite aborts before any test with a message naming the connection | CI job fails |
| Model write outside Actions | `$model->save()` in `app/Modules/X/Http/` | Architecture test fails naming file and line | N/A |
| Cross-module import | `Orders` class imports `Catalogue\Models\*` or uses another module's table name | Architecture test fails naming file | N/A |
| FK rule | Migration adds a cross-module foreign key without `restrictOnDelete()` | Architecture test fails naming the migration | Same-module FKs are not constrained |
| Seeding in production | `APP_ENV=production`, run `php artisan app:seed-test-state` | Command is not registered | N/A |

</frozen-after-approval>

## Code Map

- Repository has no application code; only `_bmad/`, `_bmad-output/`, `.claude/`, `.vscode/settings.json`, `docs/`. Do not touch `_bmad/` or `_bmad-output/` except this spec and sprint status.
- `ARCHITECTURE-SPINE.md` §Consistency Conventions (module layout, API, logging, tests), §Stack, §Structural Seed (`routes/api.php`, `tests/Architecture/`, `tests/Flows/`), AD-1, AD-2, AD-11, AD-15.
- `_bmad-output/test-artifacts/test-design-architecture.md` — tooling: Pest (PHP), Vitest (app), Playwright and axe-core come later (Story 1.7 and the console stories).
- Remote is `https://github.com/ialim/integrator.git`, so CI is GitHub Actions.

## Tasks & Acceptance

**Execution:**
- [x] `compose.yaml`, `docker/php/Dockerfile` -- PHP 8.4 CLI image with the extensions and Composer; `postgres:17` with `integrator` and `integrator_test` databases -- the owner's Docker decision.
- [x] Repo root -- `composer create-project laravel/laravel` (13.x) into the existing folder; require `filament/filament:^5`, `laravel/sanctum:^4`; dev-require Pest with the arch plugin, Larastan, Pint, `nikic/php-parser` -- the stack baseline.
- [x] `config/database.php`, `.env.example`, `phpunit.xml` -- PostgreSQL default; database driver for cache, session, queue, locks; test DB `integrator_test` -- AD hosting decisions and TC-1.
- [x] `app/Modules/{13 modules}/{12 dirs}` + `app/Providers/ModuleServiceProvider.php` -- create layout; auto-load each module's `Database/Migrations` and routes -- layout convention.
- [x] `app/Modules/Shared/Support/Id.php` + `HasUuidV7` trait -- UUIDv7 primary keys -- AD-11.
- [x] `app/Modules/Shared/Contracts/Clock.php`, `SystemClock`, `FrozenClock` -- set and advance in `testing`/`staging` -- TC-2.
- [x] `app/Modules/Shared/Http/ApiResponse` + exception rendering in `bootstrap/app.php` + `routes/api.php` (`/api/v1/health`) -- envelope, `server_time`, error `code` -- API convention.
- [x] `app/Modules/Shared/Logging/*` + `config/logging.php` + request-id middleware -- JSON log lines with `request_id` -- logging convention.
- [x] `app/Modules/Shared/Providers/ProviderFakes` -- binds each declared fake in `testing`/`staging` -- AD-15.
- [x] `app/Console/Commands/SeedTestState.php`, `database/factories/` base -- Action-driven seeding, registered only in `local`/`testing` -- TC-4.
- [x] `tests/Architecture/*` -- AD-1 write scan (php-parser AST), AD-2 import and table scan, FK migration scan, Clock-only time, every `Contracts/` interface has a fake -- each with a fixture proving it fails -- the story's point.
- [x] `tests/Pest.php` + bootstrap guard -- abort unless PostgreSQL 17 -- TC-1.
- [x] `resources/app/` (`main.tsx`, `App.tsx`), `vite.config.ts`, `tsconfig.json`, `resources/views/app.blade.php`, Tailwind 4, ESLint, Prettier, Vitest -- minimal React 19 app built into `public/build` -- stack.
- [x] `.github/workflows/ci.yml` -- `postgres:17` service; Pint, Larastan, ESLint, Prettier, `tsc`, Vitest, Pest (unit, feature, architecture); `npm run build` -- merge gate.
- [x] `README.md` -- local setup, commands, and the branch-protection step the owner must switch on for "failure blocks the merge".

**Acceptance Criteria:**
- Given a fresh clone, when the setup steps in `README.md` are followed, then `php artisan test` and `npm run build` pass and `GET /api/v1/health` responds as in the matrix.
- Given `app/Modules/`, when listed, then it holds exactly the 13 modules, each with all 12 directories.
- Given the database after `php artisan migrate`, when its tables are listed, then only Laravel framework tables exist.
- Given each architecture rule, when its failing fixture is included, then the matching test fails and names the fixture file.
- Given a push to GitHub, when CI runs, then every job above runs and any failure fails the workflow.
- Given `APP_ENV=staging`, when the app boots, then the Clock is settable and every declared provider interface resolves to its fake.

## Design Notes

- AD-1's write check needs the AST, not a regex: flag `save|update|delete|create|forceDelete|insert|upsert|increment|decrement` method and static calls on classes extending `Illuminate\Database\Eloquent\Model`, resolved by import, outside `*/Actions/*`. Fixtures live in `tests/Architecture/Fixtures/` and are excluded from autoload and from the real scan.
- The architecture tests own their fixtures, so a broken rule is proven by a test that expects failure on its fixture.
- The staging environment itself arrives with Story 1.2; here "staging" is `APP_ENV=staging` behaviour, tested by booting the app with that environment.

## Verification

**Commands:**
- PHP commands below run as `docker compose run --rm app <command>`.
- `composer test` (Pest: unit, feature, architecture) -- expected: all pass on PostgreSQL 17.
- `vendor/bin/pint --test` and `vendor/bin/phpstan analyse` -- expected: clean.
- `npm run lint`, `npm run typecheck`, `npm test`, `npm run build` -- expected: clean, assets in `public/build`.
- `DB_CONNECTION=sqlite php artisan test` -- expected: aborts with the TC-1 message.

## Implementation Notes

- Installed versions: Laravel 13.38, Filament 5.10.3, Sanctum 4.3.3, Pest 4.7.8 (arch plugin 4.0.2), Larastan 3.13.2, nikic/php-parser 5.9, React 19.3, TypeScript 6.0.3, Vite 8.3.5, Tailwind 4, Vitest 5.
- The skeleton's `users` and `password_reset_tokens` tables and `App\Models\User` were removed (Identity owns Users); `sessions` stays, with a `uuid` `user_id`. `config/auth.php` names the future `App\Modules\Identity\Models\User`. The skeleton's `AGENTS.md`/`CLAUDE.md` (Laravel Boost install prompts) and the Bunny font were dropped.
- Filament is required but no panel is registered (Story 1.3). Livewire is present only as Filament's dependency.
- Provider fakes are declared with `#[HasFake(Fake::class)]` on the interface (`App\Modules\Shared\Providers\HasFake`). The Clock is itself a `Contracts/` interface whose fake is `FrozenClock`, so the same binding covers it.
- `FrozenClock` also moves Carbon's test time so framework timestamps agree. In staging the frozen instant lives in process memory only; a cross-request setter arrives with the sweep-tick command (TC-2's later half).
- The seeding command uses `isEnabled()`, so Artisan does not register it outside `local`/`testing`; `ActionFactory` refuses to construct there too. No scenarios are registered yet.
- Architecture scanners live in `tests/Architecture/Support/`; fixtures use the non-autoloaded `ArchFixtures\` namespace and are excluded from Pint and the classmap. `ARCH_FIXTURES=1` adds the fixtures to the real scans so each rule visibly fails naming its fixture; CI asserts this. A UUIDv7-model rule was added alongside the required ones.
- `tests/bootstrap.php` (the PHPUnit bootstrap) runs the TC-1 guard; `DB_DATABASE=integrator_test` is forced in `phpunit.xml` so a shell variable cannot point tests at the dev database.

## Spec Change Log

## Review Triage Log

Review pass 1 (blind-hunter BH, edge-case-hunter EC, verification-gap VG), 25 October 2026.

| # | Layer | Finding | Verdict | Evidence | Route |
|---|-------|---------|---------|----------|-------|
| 1 | BH, EC | `DB_URL` is not forced in `phpunit.xml`, so a shell `DB_URL` points tests (and `RefreshDatabase`) at another database past the guard | medium | `config/database.php` reads `url` before `database`; only `DB_DATABASE` has `force="true"` | patch |
| 2 | BH, EC | `.env.example` has `DB_PORT=5432` but compose publishes Postgres on host 5433, so README's host-side tools fail | medium | `compose.yaml` `5433:5432`; README says `.env` serves tools outside Docker | patch |
| 3 | BH | FK scanner misses chains inside arrow-function bodies | low | only `Stmt\Expression` is walked; migrations conventionally use statement closures, fix adds a branch | reject |
| 4 | BH | AD-1 write list misses `saveOrFail`/`updateOrFail`/`deleteOrFail`, `createMany`/`saveMany`, pivot writes (`attach`, `detach`, `sync`, `toggle`, `updateExistingPivot`) | medium | `WRITE_METHODS` lacks them; `$order->products()->attach()` resolves to the model through `modelOf` but is never flagged | patch |
| 5 | BH, EC | Clock rule misses `strtotime`, `mktime()`, `microtime`, and relative literals like `'+2 hours'`, `'tomorrow'` | medium | `FUNCTIONS` and `isNowLiteral` cover only `now`/`today`/`''` | patch |
| 6 | BH | Clock rule exempts all of `Shared`, where the outbox and audit sealer will live | medium | real risk, but the frozen block says "outside `Shared`"; the fix edits this spec | reject (raise with owner) |
| 7 | BH, EC | UUIDv7 rule reports models that inherit `HasUuidV7`; migrations with `id()` unchecked | low | no base model exists; spec asks for the helper on models only; fix adds parent walking | reject |
| 8 | BH, VG | CI fixture step cannot tell the FK rule's failure from AD-2's (both use `Boundaries`) | medium | grep on `Fixtures/Boundaries/` is satisfied by `ModuleBoundariesTest` alone | patch |
| 9 | BH, EC | `TableOwners` gives a table created by two modules to whichever sorts first | false | PostgreSQL refuses the second `CREATE TABLE` of the same name, so migrations fail first | reject |
| 10 | BH | AD-2 scans only `app/Modules`; `routes/`, `app/Console`, `database/` and Shared-to-domain imports unchecked | low | module code is the target; routes delegate to module `Http/`; fix adds a non-module mode | reject |
| 11 | BH, VG | `AddServerTime` adds nothing to top-level JSON lists | low | no list endpoint exists yet; wrapping or a header is an API-shape choice for the first list endpoint | reject (raise with owner) |
| 12 | BH, EC | Unmapped 4xx statuses get "Something went wrong on our side." | low | `MESSAGES[$status] ?? 'Something went wrong on our side.'`; direct one-line correction | patch |
| 13 | VG | Error envelope untested for 401, 403, 405, 419, 429 and header passthrough | medium | pre-verified gap: no test names those codes or `Allow`/`Retry-After` | patch |
| 14 | BH | Factories and seeding command ship in the production autoload | false | spec requires them registered only in `local`/`testing`, which holds; moving to `autoload-dev` would fatal the command's `isEnabled()` in a `--no-dev` build | reject |
| 15 | BH | Spec says `in-review`, sprint status says `in-progress` | false | the workflow syncs sprint status at its next step | reject |
| 16 | BH | `tsconfig.json` includes `eslint.config.js` without `allowJs` | low | dead include; direct deletion | patch |
| 17 | BH | `phpstan.neon` ignores `trait.unused` globally | low | could hide a real unused trait later; direct scoping to the `HasUuidV7` path | patch |
| 18 | BH | `RequestId` claims queued jobs carry the id; untested | low | hidden Context does propagate; adding a queue test is more than a correction | reject |
| 19 | BH, EC | Web route regex serves the shell at `/api` and refuses `/consoles` | low | `^(?!api/|console|up$)`; direct regex correction | patch |
| 20 | EC | `HttpResponseException` becomes a 500 under `/api/v1` | medium | render callbacks run before Laravel's own `HttpResponseException` branch, and it is not an `HttpExceptionInterface` | patch |
| 21 | EC | `ProviderFakes::bind` runs in `SharedServiceProvider`, before later module providers, so a module binding its real adapter overrides the fake in staging | medium | `ModuleServiceProvider` registers Shared first; a later `register()` rebinds the interface | patch |
| 22 | EC | `ProviderFakes` globs only top-level `Contracts/*.php` | low | nested Contracts folders are unlikely; fix needs a recursive walk | reject |
| 23 | EC | File namespace differing from its path is skipped | false | PSR-4 autoloading requires the match; a mismatched interface cannot load at all | reject |
| 24 | EC | `AddServerTime` drops `server_time` on `{}` and turns nested `{}` into `[]` | low | `getData(true)` decodes objects to arrays; direct switch to object decoding | patch |
| 25 | EC | `Id::isValid` rejects uppercase UUIDv7 | low | regex has no `i` flag; direct correction | patch |
| 26 | EC | AD-1 exempts any path containing `/Actions/`, including `Filament/.../Actions/` | medium | `str_contains($file, '/Actions/')`; Filament action classes are the console's write path | patch |
| 27 | EC | `static::create()` inside a model not flagged | low | `static`/`self` not resolved; fix adds a branch | reject |
| 28 | EC | Model from `foreach` not tracked | low | type inference is per assignment; fix adds tracking | reject |
| 29 | EC | `date('Y-m-d', null)` not flagged | low | rare; fix adds a branch | reject |
| 30 | EC | Parse error does not name the file | low | phpstan and the app itself fail first; fix is a new guard | reject |
| 31 | EC | FK SQL regex false positives on comments and schema-qualified names | low | raw-SQL foreign keys are rare; fix adds parsing | reject |
| 32 | EC | `Schema::create` with a constant name or `Schema::rename` unowned | low | literal names are the convention; fix adds resolution | reject |
| 33 | EC | A non-interface in `Contracts/` passes AD-15 | low | spec says provider interfaces; fix adds a rule | reject |
| 34 | EC | Same scenario name twice runs twice | low | direct `array_unique` | patch |
| 35 | EC | `FrozenClock` in staging lives in one process, so a set time does not survive across requests | medium | singleton in memory; spec's "settable" holds at boot, but TC-2 staging control needs persistence | defer |
| 36 | VG | The real-scan arguments of each architecture rule are never run against a known violation | medium | pre-verified gap: a mistyped namespace or widened exempt would pass CI | patch |
