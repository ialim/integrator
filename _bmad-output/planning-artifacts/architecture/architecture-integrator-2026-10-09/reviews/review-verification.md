---
review: verification
lens: 'Was each committed technology decision checked against live sources (versions, existence, fit, host facts)?'
target: ARCHITECTURE-SPINE.md (Stack table; AD-4, AD-5, AD-11, AD-12, AD-16, AD-17, AD-18, AD-19, AD-20, AD-21)
reviewer: independent verification pass (not the author)
date: 2026-10-09
method: npm registry (`npm view`), Packagist p2 JSON, Laravel / Laravel Cloud / Filament / Neon docs fetched live, caniuse, WebKit sources
---

# Verification review: integrator architecture spine

## Verdict

The spine's choices are real and current, and they fit together. Every package in the Stack table exists at the major version named, and the Laravel Cloud facts it relies on check out: Growth plan, London region, Serverless Postgres 17 with point-in-time recovery, managed queues, a per-minute scheduler, S3-compatible object storage, `pg_trgm`, and a secrets store. Nothing is critical. One choice causes a tooling problem: **TypeScript 7.x** cannot be installed alongside typescript-eslint today. Three host facts are thinner than the ADs assume: the second database role for AD-21, where object storage physically lives, and iOS storage persistence. All three need a sentence or a spike, not a redesign.

## Evidence table (what was checked)

| Claim in spine | Live finding (2026-10-09) | Status |
| --- | --- | --- |
| PHP 8.3+ | Laravel 13 requires PHP 8.3–8.5 | OK |
| laravel/framework 13.x (13.35) | v13.35.0 published 2026-10-06; L13 released 2026-03-17, bug fixes to Q3 2027, security to 2028-03-17 | OK |
| filament/filament 5.x (5.10) | v5.10.1 published 2026-10-07; filament/support requires `illuminate/contracts ^11.28\|^12.0\|^13.0`; needs Tailwind 4.1+ | OK |
| laravel/sanctum 4.x | v4.3.3, supports illuminate ^11\|^12\|^13 | OK |
| PostgreSQL 17 on Laravel Cloud | Laravel Cloud FAQ: "Serverless Postgres (version 17)"; the docs also say you choose the version and the newest is the default | OK (see L-1) |
| React 19.x | 19.3.0 | OK |
| TypeScript 7.x | 7.0.2 (GA 2026-07-08); typescript-eslint 8.71.1 peer `typescript >=4.8.4 <6.1.0` | **Does not fit tooling** (H-1) |
| Vite 8.x | 8.3.4 (8.0.0 on 2026-03-12); laravel-vite-plugin 3.2.0 and @vitejs/plugin-react 6.1.2 both peer `vite ^8` | OK |
| vite-plugin-pwa 2.x | 2.0.0 published **2026-10-03** (6 days old); peer `vite ... ^8.0.0`; still Workbox 7.4.1 | OK (see L-2) |
| Dexie 4.x | 4.4.6 (2026-09-10) | OK |
| @tanstack/react-query 5.x | 5.104.1 | OK |
| @tanstack/react-router 1.x | 1.170.41; router-plugin peers Vite >=8 | OK |
| Tailwind CSS 4.x | 4.3.3; @tailwindcss/vite peers Vite ^8 | OK |
| Growth plan, EU West (London) | Growth $20/mo plus usage; London (eu-west-2) is a listed region | OK |
| Serverless Postgres with PITR (diagram: 7-day) | Neon-backed; PITR retention is set per cluster when you create it; PITR log storage is billed per GB-month | OK |
| Managed queue | Yes. On Growth: 10 per environment; SQS-style at-least-once delivery, delays capped at 15 min | OK (see M-4) |
| Scheduler every minute | Yes. `schedule:run` runs every minute on the App cluster; use `onOneServer` with several replicas | OK (see L-3) |
| Object storage, S3 API | Yes. Cloudflare R2 behind the S3 API; private buckets plus `Storage::temporaryUrl` | OK (see M-2) |
| `pg_trgm` available (AD-17) | Neon supports pg_trgm 1.6 on PG14–18 | OK |
| Second DB role, triggers (AD-21) | Neon allows `CREATE ROLE` through SQL. Laravel Cloud's "Database users" UI covers only Laravel MySQL and RDS, not Serverless Postgres | Unverified on Laravel Cloud (M-1) |
| Secret store outside the DB (AD-16) | Laravel Cloud Secrets Manager (encrypted organisation-level secrets attached to environments) | OK |
| Cost ~USD 70–110/month always on | Rough estimate below: about USD 55–105 | OK (see L-4) |
| UUIDv7 via HasUuids (AD-11) | L13 docs: HasUuids generates UUIDv7 by default | OK |
| afterCommit events (AD-4) | `ShouldDispatchAfterCommit` interface on events, documented in L13 | OK |
| Sanctum SPA cookie, same site (AD-18) | Supported; SPA and API must share the top-level domain; `statefulApi()` | OK |
| Filament separate guard + mandatory MFA (AD-18) | `->authGuard('staff')`; `->multiFactorAuthentication([AppAuthentication::make()], isRequired: true)`, plus recovery codes | OK |
| iOS lacks Background Sync (AD-12) | caniuse: unsupported in Safari and iOS Safari through 27.x | OK |
| StorageManager.persist() | Not mentioned in the spine; WebKit grants it by heuristics (Home Screen web app) | Gap (M-3) |

---

## Critical

None.

## High

### H-1. TypeScript 7.x breaks the standard lint toolchain today

- **Spine:** Stack table, `TypeScript | 7.x`.
- **Finding:** TypeScript 7.0 (the native Go compiler) went GA on 2026-07-08 and its latest release is 7.0.2. Its stable programmatic API is targeted for 7.1, which is still in nightly `7.1.0-dev` builds. typescript-eslint 8.71.1, the current latest, declares the peer `typescript: ">=4.8.4 <6.1.0"`, so npm refuses to install it alongside `typescript@7`. Other tools that import the `typescript` module (ts-morph, ts-jest, ts-loader) are in the same position. Vite itself is not affected, because it strips types with Oxc/esbuild. The impact falls on linting and on any codegen that uses the compiler API.
- **Fix:** Choose one and write it down:
  - (a) Pin `typescript` 6.0.x as the `typescript` package for tooling, and run `tsgo` / TS 7 only for `tsc --noEmit` type checking. Microsoft recommends this split while the 7.1 API is pending.
  - (b) Commit to TS 7 and use a linter that does not depend on the TS API, such as Biome or oxlint, accepting that type-aware lint rules are lost.

  Either way, change the Stack row to say which TypeScript does type checking and which serves tooling.
- **Sources:**
  - npm: `npm view typescript-eslint peerDependencies` → `typescript: '>=4.8.4 <6.1.0'` (checked 2026-10-09)
  - npm: `npm view typescript dist-tags` → latest 7.0.2, next 7.1.0-dev.20261009.1
  - https://devblogs.microsoft.com/typescript/?p=5246 (TS 7.0 announcement, 2026-07-08)
  - https://dev.to/dev_encyclopedia/why-your-typescript-7-upgrade-broke-eslint-ts-jest-and-ts-morph-385k
  - https://ecorpit.com/typescript-7-migration-readiness-eslint-astro-blockers-2026/

## Medium

### M-1. AD-21's second Postgres role is unverified on Laravel Cloud, and the fallback is weaker than the AD implies

- **Spine:** AD-21: "Where the host allows a second database role, the application's role also holds only INSERT and SELECT on it, and migrations run under a separate owner role."
- **Finding:**
  - Laravel Cloud's "Database users" feature, which creates extra users with read-only or read-write access, is documented only for Laravel MySQL and RDS, not for Serverless Postgres.
  - Neon, which provides Serverless Postgres, does allow `CREATE ROLE` through SQL from a role in `neon_superuser`, and roles created that way get no `neon_superuser` privileges. So a least-privilege application role is probably possible. It would mean overriding the injected `DB_USERNAME`/`DB_PASSWORD` for web and queue processes, while deploy-time migrations keep the injected owner user. This has not been tested on Laravel Cloud.
  - If the fallback is used (one role that owns the tables), the trigger protects only against application bugs, not against a compromised application. A table owner can `ALTER TABLE … DISABLE TRIGGER` or `DROP TRIGGER`.
  - `neon_superuser` members inherit `pg_write_all_data`, so `REVOKE UPDATE, DELETE` on the audit table has no effect on the injected default role.
- **Fix:**
  - Add a spike before the Staff epic: run `CREATE ROLE app_rw LOGIN …` on a Laravel Cloud Serverless Postgres cluster, grant it only the privileges it needs (no `pg_write_all_data`), point web and queue at it, and confirm that migrations still run as the owner.
  - State in AD-21 that under the single-role fallback, tamper evidence rests on the hash chain plus the offsite nightly dump (AD-20), not on the trigger.
- **Sources:**
  - https://laravel.com/cloud/docs/resources/databases ("Database users are supported on both RDS MySQL, RDS Postgres and Laravel MySQL clusters")
  - https://laravel.com/cloud/docs/resources/databases/postgres.md
  - https://neon.com/docs/manage/roles (CREATE ROLE through SQL; `neon_superuser` includes `pg_write_all_data`, `CREATEROLE`)

### M-2. Object storage is Cloudflare R2, so "EU West (London)" does not describe where files live

- **Spine:** AD-20 places production in "EU West London"; AD-14 puts Receipts, invoices and return photos in "private S3-compatible object storage"; the Structural Seed draws object storage inside the London box. Deferred: "NDPA cross-border transfer basis for London hosting".
- **Finding:** Laravel Cloud Object Storage is "offered in partnership with Cloudflare R2". The docs give no region, jurisdiction or location hint for buckets. Receipts and bank details are personal data, so the residency question counsel is answering covers R2's storage location as well as London. Functionally it fits: R2 is S3-compatible, private buckets plus `Storage::temporaryUrl` give signed URLs, and R2 rejects per-object ACLs, which is harmless because AD-14 uses private buckets only.
- **Fix:** Correct the Structural Seed so object storage sits outside the London box, and widen the NDPA Deferred row to "London compute/DB and Cloudflare R2 object storage (location to confirm with Laravel Cloud)". AD-20's no-lock-in rule already covers swapping to an S3 bucket in a named region if counsel requires it.
- **Sources:** https://laravel.com/cloud/docs/resources/object-storage.md

### M-3. iOS persistence: the spine relies on IndexedDB but never requests persistent storage

- **Spine:** AD-12 (local store, 14-day lock); the PRD addendum and NFR-6 require an eviction warning.
- **Finding:**
  - WebKit evicts origin data under storage pressure or after a period without user interaction, unless the origin is in persistent mode.
  - WebKit grants `navigator.storage.persist()` by heuristics, chiefly whether the site runs as a Home Screen web app.
  - Home Screen apps have their own days-of-use counter, separate from Safari's 7-day ITP cap.
  - Developers report having to call `persist()` again on each launch, and there is an open WebKit report of cookies (which Sanctum sessions use) being cleared in Home Screen apps after about 7 days.
  - The spine's statement that Background Sync is unsupported on iOS is correct: caniuse shows no support through Safari / iOS 27.x.
- **Fix:**
  - Add to AD-12 that the PWA calls `navigator.storage.persisted()` and `persist()` on every launch, and records whether persistence was granted.
  - Show the eviction warning when it was not granted.
  - Use `navigator.storage.estimate()` for the "shows the size first" check (FR-50).
  - Note that an expired session cookie must lock, not purge, unsynced local changes. EXPERIENCE.md already describes this for logout.
- **Sources:**
  - https://caniuse.com/background-sync
  - https://webkit.org/?p=14403 (Updates to Storage Policy: persistent mode and eviction criteria)
  - https://developer.apple.com/forums/thread/710157
  - https://bugs.webkit.org/show_bug.cgi?id=237350

### M-4. Managed queues deliver at least once, so queued listeners need their own idempotency

- **Spine:** AD-4.6 sends notifications, PDFs, WhatsApp and email only from queued listeners; AD-20 uses "standard Laravel queues".
- **Finding:** Laravel Cloud managed queues are SQS-style, and the docs warn: "Design jobs to tolerate at-least-once delivery". Visibility is extended in 3-minute steps, a crashed worker means a retry, and per-message delays are capped at 15 minutes. AD-5 already avoids delayed jobs, which is correct. AD-4's idempotency covers Actions, but a listener that sends a WhatsApp message or email can still double-send on redelivery.
- **Fix:** Extend AD-4.6 so that each side-effecting listener records a delivery key in the Notifications delivery log, keyed on event id plus channel, under a unique constraint, before calling the provider. PDF jobs write a new immutable file each time (AD-14), so make them check-then-write.
- **Sources:** https://laravel.com/cloud/docs/queues.md

## Low

### L-1. Postgres 17 is current but not the newest; one optional gain if 18 is chosen

The Laravel Cloud FAQ still says "Serverless Postgres (version 17)". The Postgres doc says you pick the version at creation and the newest is the default. Neon supports PG14–18 and pg_trgm on all of them. PostgreSQL 17 is supported until November 2029, so it is a safe pin. The one thing to note: PostgreSQL 18 adds a native `uuidv7()` function. Under AD-11, ids come from PHP (`HasUuids`, UUIDv7 by default) and from the device, so this is optional. The device does need a UUIDv7 generator: `crypto.randomUUID()` produces only v4. Name one, such as the `uuid` package's `v7()`, alongside Dexie.

- https://laravel.com/cloud/pricing (FAQ)
- https://laravel.com/cloud/docs/resources/databases/postgres.md
- https://neon.com/docs/extensions/pg-extensions
- https://raw.githubusercontent.com/laravel/docs/13.x/eloquent.md (HasUuids generates UUIDv7)

### L-2. vite-plugin-pwa 2.0.0 is six days old

It was published 2026-10-03. The changelog's only breaking change is allowing `@vite-pwa/assets-generator ^2`, and Vite 8 support arrived earlier, in 1.3.0 (2026-05-05). The engine underneath is still Workbox 7.4.1, which has had no release since 2026-05. The risk is low. Pinning `^2.0.0` is fine; `1.3.x` is an equal fallback.

- https://github.com/vite-pwa/vite-plugin-pwa/releases/tag/v2.0.0
- npm: `npm view vite-plugin-pwa@2.0.0 peerDependencies`

### L-3. The per-minute sweep needs `onOneServer` and a shared cache store once the app has replicas

The scheduler runs `schedule:run` every minute on every App-cluster replica. With autoscaling on Growth (up to 10 replicas), the sweep needs `->onOneServer()` and, if a run can exceed a minute, `withoutOverlapping()`. Both need a shared cache store (Valkey or the database cache driver). AD-4's locks make double runs safe, but they would double the heartbeat writes and the load. Alternatively, put the scheduler on a single-replica worker cluster.

- https://laravel.com/cloud/docs/scheduled-tasks.md
- https://raw.githubusercontent.com/laravel/docs/13.x/scheduling.md

### L-4. The cost estimate holds; everything is effectively always on, and the spending limit pauses compute

A per-minute sweep that queries Postgres prevents both the app and the database from scaling to zero. A rough London estimate from the published rates:

| Item | USD/month |
| --- | --- |
| Growth plan | 20 |
| App, Flex 1–2 GiB, capped | 14–28 |
| Serverless Postgres, always on at 0.25–0.5 CU × $0.106/CU-h | ~19–39 |
| Storage + PITR (a few GB × $0.50 + $0.25) | ~2–5 |
| Flex managed queue, idle-heavy | ~1–5 |
| Optional Valkey 250 MB | 6 |
| R2 storage | small |

The total is about **USD 55–105**, which is consistent with 70–110. Using Pro compute ($27–54) or a 1 CU database floor (~$77) would push past the top of the range. One caution: a Laravel Cloud spending limit pauses compute when reached. AD-20 mentions spend alerts only, so set alert thresholds without a hard cap in production, or a cap well above the expected spend. An older pricing page quotes $0.16/vCPU-h and $1.50/GB for Postgres; the current pricing data says $0.106 and $0.50.

- https://laravel.com/cloud/pricing
- https://laravel.com/cloud/docs/pricing.md (embedded price data: `neon.pricing.cpu.pricePerHour: 0.106`, eu-west-2 flex/pro compute)
- https://laravel.com/cloud/docs/pricing-v1 (older figures)

### L-5. AD-17's offline search index library is unnamed

`pg_trgm` is verified. The device-side typo-tolerant index ("an index it builds from its synced Catalogue") names no library, and the rest of the client stack is pinned. Either pin one (for example MiniSearch or FlexSearch) or add it to Deferred with a size budget, because it adds to the PWA bundle on low-bandwidth devices (FR-55).

### L-6. Facts verified and correct as written (no action)

- **Laravel 13:** HasUuids defaults to UUIDv7; `ShouldDispatchAfterCommit` dispatches after the outermost commit; scheduler `everyMinute()`; Sanctum SPA cookie auth requires the same top-level domain, which holds since the PWA is served by Laravel.
- **Filament 5:** `authGuard()` gives a separate staff guard; `multiFactorAuthentication([...], isRequired: true)` forces enrolment, with app-based TOTP (pragmarx/google2fa is a direct dependency), email codes and recovery codes. Filament 5 uses Livewire 4 for the console only, which does not conflict with AD-19.
- **Laravel Cloud Secrets Manager** exists for AD-16's dedicated NIN keys.
- **Laravel 13 support window:** bug fixes until Q3 2027. Laravel 14 is expected around Q1 2027, so plan an upgrade window during year one.

Sources:
- https://raw.githubusercontent.com/laravel/docs/13.x/eloquent.md
- https://raw.githubusercontent.com/laravel/docs/13.x/events.md
- https://raw.githubusercontent.com/laravel/docs/13.x/sanctum.md
- https://raw.githubusercontent.com/laravel/docs/13.x/releases.md
- https://raw.githubusercontent.com/filamentphp/filament/5.x/docs/07-users/02-multi-factor-authentication.md
- https://raw.githubusercontent.com/filamentphp/filament/5.x/docs/07-users/01-overview.md
- https://laravel.com/cloud/docs/secrets.md
- https://repo.packagist.org/p2/laravel/framework.json
- https://repo.packagist.org/p2/filament/filament.json
- https://repo.packagist.org/p2/filament/support.json
- https://repo.packagist.org/p2/laravel/sanctum.json
