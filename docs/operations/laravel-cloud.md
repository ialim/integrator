# Production on Laravel Cloud

The settings for the founder to apply in the Laravel Cloud dashboard (AD-20). The build creates no
account, bucket or secret; every step below is done by hand, once. Work through it top to bottom and tick
each box.

The code uses only standard PostgreSQL, the S3 API and standard Laravel queues, cache, sessions and
scheduling, so the same settings work on any host that offers those.

Related: [alerts.md](alerts.md) (heartbeat and email), [restore-runbook.md](restore-runbook.md) (recovery),
[spend-alerts.md](spend-alerts.md) (billing alerts).

## 1. Organisation and application

- [ ] Laravel Cloud organisation on the **Growth** plan.
- [ ] New application from the GitHub repository `ialim/integrator`, branch `main`.
- [ ] Region **EU West (London)**. Every resource below is created in the same region.
- [ ] Environments: `production` (this document) and `staging`. Staging uses the same settings with
      `APP_ENV=staging`, its own database and bucket, and synthetic data only. **Never copy or restore
      production data into staging.**

## 2. Compute

- [ ] PHP **8.4**, Node **24**.
- [ ] Build commands, in order:

  ```sh
  composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
  npm ci
  npm run build
  ```

- [ ] Deploy command:

  ```sh
  php artisan migrate --force
  ```

- [ ] Health check path for the host's own checks: `/api/v1/health` (returns `{"status":"ok", ...}`).

## 3. Database

- [ ] Create a **Serverless Postgres 17** database in EU West (London) and attach it to `production`.
      Laravel Cloud fills in the `DB_*` variables; keep `DB_CONNECTION=pgsql`.
- [ ] Turn on the database's **public endpoint** (TLS). The hourly offsite copy runs on GitHub's runners,
      outside Laravel Cloud, and connects through it.
- [ ] Copy the **direct (non-pooled)** public connection string, not the pooled one: `pg_dump` needs a
      session of its own, which a transaction pooler does not give. Add `sslmode=require` at the end, for example
      `postgresql://USER:PASSWORD@HOST:5432/DBNAME?sslmode=require`. It goes into GitHub as the secret
      `BACKUP_DATABASE_URL` (section 7), never into the repository.
- [ ] Cost: if the database scales to zero when idle, the hourly copy wakes it 24 times a day, and each wake
      is billed as compute for the dump plus the idle time before it sleeps again. Allow for that in the
      spend alert ([spend-alerts.md](spend-alerts.md)).

## 4. Queue worker and scheduler

- [ ] Queue: a worker process running

  ```sh
  php artisan queue:work database --tries=3 --backoff=10 --max-time=3600
  ```

  The queue runs on the database driver (`QUEUE_CONNECTION=database`). A job that has used all its tries
  fails permanently and emails the founder (see [alerts.md](alerts.md)).
- [ ] Scheduler: **on** (it runs `php artisan schedule:run` every minute). The schedule sends any held
      founder alerts (`alerts:send-held`); later stories add their jobs to it.

## 5. Object storage

- [ ] Create a private **bucket** (Laravel Cloud object storage, Cloudflare R2) in the same region and
      attach it to `production` as the `s3` disk. Laravel Cloud fills in the `AWS_*` variables.
- [ ] Create a key for the backup runner: read-only on this bucket if Laravel Cloud offers one, otherwise
      the bucket's own key. Note its endpoint, bucket name, key id and secret for section 7
      (`SOURCE_S3_*`).

## 6. Environment variables (production)

Enter secrets in Laravel Cloud's environment settings, which is the host's secret store. Values marked
"injected" are filled in by Laravel Cloud when you attach the resource.

| Variable | Value | Secret |
| --- | --- | --- |
| `APP_NAME` | `integrator` | no |
| `APP_ENV` | `production` | no |
| `APP_DEBUG` | `false` | no |
| `APP_URL` | the production URL, `https://...` | no |
| `APP_KEY` | generate once with `php artisan key:generate --show`; store a copy offline with the backup key | **yes** |
| `LOG_CHANNEL` | `json_stderr` (JSON lines with the request id, collected from stderr) | no |
| `LOG_LEVEL` | `info` | no |
| `DB_CONNECTION` | `pgsql` | no |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | injected | **yes** (password) |
| `SESSION_DRIVER` | `database` | no |
| `CACHE_STORE` | `database` | no |
| `QUEUE_CONNECTION` | `database` | no |
| `FILESYSTEM_DISK` | `s3` | no |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_ENDPOINT`, `AWS_URL` | injected | **yes** (key and secret) |
| `MAIL_MAILER` | `smtp` | no |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME` | from the SMTP provider | no |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | from the SMTP provider | **yes** |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | a sending address verified with the SMTP provider; `integrator` | no |
| `FOUNDER_ALERT_EMAIL` | the founder's address for alerts | **yes** |

Do **not** enter any `OFFSITE_S3_*` value in Laravel Cloud. Only the backup runner writes the offsite
bucket, so a compromised app cannot delete the copies. (`config/filesystems.php` has an `offsite` disk for
inspecting copies by hand from the local app container, for example with `php artisan tinker`; nothing in
the app or the scripts uses it.)

## 7. Secrets for the hourly offsite copy (GitHub)

In GitHub, open **Settings**, then **Secrets and variables**, then **Actions**. Add these repository
secrets. The workflow `.github/workflows/offsite-backup.yml` refers to them by name only.

| Secret | What it is |
| --- | --- |
| `BACKUP_DATABASE_URL` | the public connection string from section 3, ending `?sslmode=require` |
| `BACKUP_AGE_RECIPIENT` | the founder's **public** age key, `age1...` (see [restore-runbook.md](restore-runbook.md), "The key pair") |
| `OFFSITE_S3_ENDPOINT`, `OFFSITE_S3_REGION`, `OFFSITE_S3_BUCKET`, `OFFSITE_S3_ACCESS_KEY_ID`, `OFFSITE_S3_SECRET_ACCESS_KEY` | the offsite bucket at a second provider, outside Laravel Cloud (founder's choice of any S3-compatible provider) |
| `OFFSITE_S3_PREFIX` | optional folder inside that bucket, such as `integrator/`; leave unset for the bucket root |
| `SOURCE_S3_ENDPOINT`, `SOURCE_S3_REGION`, `SOURCE_S3_BUCKET`, `SOURCE_S3_ACCESS_KEY_ID`, `SOURCE_S3_SECRET_ACCESS_KEY` | the Laravel Cloud bucket and key from section 5 |
| `BACKUP_HEARTBEAT_URL` | the heartbeat monitor's ping URL (see [alerts.md](alerts.md)) |

Regions default to `auto` when left unset, which suits R2 and most S3-compatible providers.

The offsite bucket:

- [ ] At a provider other than Laravel Cloud and Cloudflare, so one provider's failure cannot take both
      copies, **and one that offers bucket versioning or object lock**. Choose another provider if it does not.
- [ ] Private, with **object lock** (compliance or governance mode, 35-day retention) or at least
      **versioning** turned on. This is required: the runner's key can overwrite and delete objects, and
      without it a leaked key or a bad script run could delete every copy.
- [ ] The runner's key must not be able to delete object versions, change the lock or the versioning,
      or change lifecycle rules. With versioning only, also add a lifecycle rule that expires noncurrent
      versions after 35 days.
- [ ] Remaining risk, accepted: whoever holds the provider account's own administrator login can still
      remove versions or end a governance-mode lock (compliance mode prevents even that until the
      retention ends). Keep that login with the founder, with two-factor sign-in, and never put it in any
      secret store.
- [ ] A lifecycle rule that deletes `integrator-*.dump.age` copies after 35 days, `objects-replaced/`
      after 90 days and anything left under `incoming/` after 1 day.
- [ ] Its key can read, write and delete in this bucket only.

Then:

- [ ] Run **Offsite backup** once by hand (**Actions**, **Offsite backup**, **Run workflow**) and check
      that one `integrator-YYYYMMDDTHHMMSSZ.dump.age` and an `objects/` folder appear in the bucket.
- [ ] Add the repository **variable** (not secret) `OFFSITE_BACKUP_ENABLED` = `true`. Scheduled runs start
      only once it is set.

GitHub stops scheduled workflows in a repository with no activity for 60 days. The heartbeat monitor's
"Backup missing" alert is what tells you; re-enable the workflow under **Actions** if that happens.

## 8. Check it works

- [ ] `https://<production URL>/api/v1/health` returns `{"status":"ok","server_time":"..."}`.
- [ ] From Laravel Cloud's command runner: `php artisan alerts:test` and the founder receives
      "integrator alert: Test alert".
- [ ] The logs in Laravel Cloud show JSON lines, each with `extra.request_id`.
- [ ] The heartbeat monitor shows the first successful ping (after section 7).
- [ ] The spend alerts in [spend-alerts.md](spend-alerts.md) are set.
