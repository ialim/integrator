# integrator

One Laravel 13 application: the `/api/v1` JSON API, the Filament staff console (from Story 1.3) and the
Integrator app, a React 19 single-page app built from `resources/app/` into `public/build`.

The architecture lives in
`_bmad-output/planning-artifacts/architecture/architecture-integrator-2026-10-09/ARCHITECTURE-SPINE.md`.

## Stack

PHP 8.4, Laravel 13, Filament 5, Sanctum 4, PostgreSQL 17; React 19, TypeScript 6.0, Vite 8, Tailwind 4.
Tests: Pest (PHP) and Vitest (app).

## Local setup

Local development runs PHP and PostgreSQL in Docker. Composer, Artisan and Pest run inside the `app`
container; Node 24 and npm run on the host.

You need Docker Desktop and Node 24.

```sh
git clone https://github.com/ialim/integrator.git
cd integrator
cp .env.example .env

docker compose build
docker compose up -d pgsql
docker compose run --rm app composer install
docker compose run --rm app php artisan key:generate
docker compose run --rm app php artisan migrate

npm ci
npm run build

docker compose up -d app        # serves http://localhost:8000
```

`compose.yaml` points the app container at the `pgsql` service on port 5432, so `.env` keeps
`DB_HOST=127.0.0.1` and `DB_PORT=5433` for tools run outside Docker (PostgreSQL is published on host port 5433). The first start of the `pgsql`
volume creates both `integrator` and `integrator_test`. If you created the volume before that script
existed, run `docker compose down -v` once.

Check it works:

```sh
curl http://localhost:8000/api/v1/health
# {"status":"ok","server_time":"2026-10-25T09:00:00Z"}
```

## Commands

PHP commands run as `docker compose run --rm app <command>`.

| What | Command |
| --- | --- |
| All PHP tests (unit, feature, architecture, module, flow) | `composer test` |
| One suite | `php artisan test --testsuite=Architecture` |
| Code style | `vendor/bin/pint --test` (fix with `vendor/bin/pint`) |
| Static analysis | `vendor/bin/phpstan analyse` |
| Create test state through Actions (local and testing only) | `php artisan app:seed-test-state` |
| App lint (ESLint and Prettier) | `npm run lint` |
| App type check | `npm run typecheck` |
| App tests | `npm test` |
| App build into `public/build` | `npm run build` |
| App dev server with hot reload | `npm run dev` |

## Rules the build enforces

- **Tests run only on PostgreSQL 17 (TC-1).** `tests/bootstrap.php` aborts before any test on another
  driver or server version, naming the connection. `DB_CONNECTION=sqlite php artisan test` shows it.
- **Architecture tests** in `tests/Architecture/` fail with the offending file and line:
  - AD-1: model and query-builder writes only inside a module's `Actions/`;
  - AD-2: a module uses another only through its `Actions`, `Queries` and `Locks`, and never names its tables;
  - foreign keys between modules are `restrictOnDelete()`;
  - time is read only through the `Shared` Clock, never `now()`, `Carbon::now()`, `time()`, `date()` or `new DateTime()`;
  - every interface in a module's `Contracts/` declares its fake with `#[HasFake(...)]`;
  - every model uses `HasUuidV7`.

  Each rule has a fixture in `tests/Architecture/Fixtures/` that breaks it. To watch every rule fail and
  name its fixture: `ARCH_FIXTURES=1 php artisan test --testsuite=Architecture`.
- **Testing and staging bind fakes.** With `APP_ENV` `testing` or `staging`, every declared provider fake
  is bound, including the settable `FrozenClock`.
- **Test seeding only through Actions (TC-4).** Factories extend `Database\Factories\ActionFactory`, and the
  seeding command exists only when `APP_ENV` is `local` or `testing`.

## Modules

`app/Modules/` holds 13 modules: Shared, Identity, Catalogue, Pricing, Projects, Orders, CreditNotes,
Fulfilment, Documentation, Notifications, Returns, Staff, Reporting. Each has `Actions/`, `Queries/`, `Locks/`,
`Models/`, `Events/`, `Http/`, `Filament/`, `Contracts/`, `StatusTables/`, `Database/Migrations/`,
`Database/Views/` and `Tests/`. `ModuleServiceProvider` loads each module's migrations, its
`Http/routes.php` under `/api/v1`, and its `Providers/{Module}ServiceProvider` when present.

## CI and the merge gate

`.github/workflows/ci.yml` runs on every push and pull request: Pint, Larastan, Pest (unit, feature,
architecture, module and flow suites) on a PostgreSQL 17 service, the database-guard and fixture checks,
ESLint, Prettier, `tsc`, Vitest and `npm run build`. Any failing step fails the workflow.

For a failure to block the merge, the repository owner must switch on branch protection once:

1. On GitHub, open **Settings**, then **Branches** (or **Rules**, then **Rulesets**).
2. Add a rule for `main`.
3. Turn on **Require status checks to pass before merging** and **Require branches to be up to date**.
4. Add the three checks: `PHP lint (Pint, Larastan)`,
   `PHP tests (unit, feature, architecture) on PostgreSQL 17` and `App (ESLint, Prettier, tsc, Vitest, build)`.
   They appear in the list after the workflow has run once.
5. Save.
