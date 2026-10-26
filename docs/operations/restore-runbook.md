# Restore runbook

How to bring production back from the hourly offsite copy (NFR-5, AD-20), and how to prove it each
quarter.

| Target | Value |
| --- | --- |
| Recovery point | **1 hour**: a copy is taken every hour; the heartbeat alerts after 70 minutes without one |
| Recovery time | **4 hours** from the decision to restore to the app serving the restored data |
| Drill | **every quarter**, in the first two weeks of January, April, July and October, into a throwaway environment; each drill's measured time goes in the [drill log](#drill-log) |

## What is copied, and where

- Every hour, `.github/workflows/offsite-backup.yml` runs [`scripts/ops/backup.sh`](../../scripts/ops/backup.sh)
  on a GitHub runner, outside Laravel Cloud.
- The database: `pg_dump --format=custom`, encrypted with `age` to the founder's public key before it
  leaves the runner, stored as `integrator-YYYYMMDDTHHMMSSZ.dump.age` (UTC) in the offsite bucket. It is
  uploaded under `incoming/` and renamed only once complete, so a copy with the final name is always whole.
- Object storage: mirrored to `objects/` in the same bucket. Objects replaced or deleted since the
  previous hour are kept under `objects-replaced/<time>/`.
- Nothing about the backups is stored in the repository except the scripts.

### The key pair

The copies can only be read with the founder's private key. Create it once, on the founder's own
computer, never on a server:

```sh
age-keygen -o integrator-backup.key
# prints: Public key: age1...
```

- The **public** key (`age1...`) goes into GitHub as `BACKUP_AGE_RECIPIENT`.
- The **private** key file is kept **offline** by the founder: on two encrypted USB drives in separate
  places, or in a password manager the founder alone controls. It is never stored with the backups, in
  Laravel Cloud, in GitHub or in the repository. Store the production `APP_KEY` beside it.
- If the private key is lost, every existing copy is unreadable: make a new pair, replace
  `BACKUP_AGE_RECIPIENT` at once, and treat older copies as gone.

## Before you start

You need:

- the private key file and the production `APP_KEY`, from offline storage;
- the offsite bucket's endpoint, bucket name and a key that can read it;
- access to Laravel Cloud for the `production` environment;
- a computer with Docker and this repository (`docker compose build app` gives an image with `pg_restore`
  17, `age` and `rclone`).

Write down the time you decide to restore. **The 4-hour clock starts then.**

## Restore production

1. **Stop writes.** Put the app into maintenance mode from Laravel Cloud's command runner
   (`php artisan down`) if it still runs, and pause the queue worker.
2. **Make a clean database.** In Laravel Cloud, create a new Serverless Postgres 17 database in
   EU West (London), with its public endpoint on. Never restore into staging, and never into a database
   that has tables; the script refuses one that does.
3. **Write the settings** into a file **outside the repository**, for example `~/restore.env`:

   ```sh
   OFFSITE_S3_ENDPOINT=https://...
   OFFSITE_S3_BUCKET=...
   OFFSITE_S3_ACCESS_KEY_ID=...
   OFFSITE_S3_SECRET_ACCESS_KEY=...
   # OFFSITE_S3_PREFIX=integrator/        if the copies live in a folder
   # RESTORE_DUMP=integrator-20261025T090712Z.dump.age   to pick a copy other than the latest
   RESTORE_AGE_IDENTITY_FILE=/key/integrator-backup.key
   RESTORE_DATABASE_URL=postgresql://USER:PASSWORD@NEW-HOST:5432/DBNAME?sslmode=require
   RESTORE_HEALTH_URL=https://<production URL>/api/v1/health
   RESTORE_CHECK_TABLE=migrations
   ```

   Only if object storage was lost too, add a new bucket as the target:

   ```sh
   RESTORE_OBJECTS_S3_ENDPOINT=...
   RESTORE_OBJECTS_S3_BUCKET=...
   RESTORE_OBJECTS_S3_ACCESS_KEY_ID=...
   RESTORE_OBJECTS_S3_SECRET_ACCESS_KEY=...
   ```

   **Restoring an older copy with `RESTORE_DUMP` still restores today's `objects/` mirror**, not the
   objects as they were when that copy was taken. Objects created since will be there (harmless: nothing
   in the older database points at them), and objects replaced or deleted since will be at their newest
   version or missing. To get the objects as of the copy's time `T`: after the script has run, take each
   `objects-replaced/<time>/` folder whose time is **later** than `T`, from the newest to the oldest, and
   copy it over the restored bucket, for example
   `rclone copy OFFSITE:<bucket>/objects-replaced/20261025T110700Z TARGET:<bucket>`. Each folder holds the
   versions that run replaced or deleted, so applying them newest first leaves the oldest one, which is
   the version as of `T`.

4. **Run the restore** from the repository folder:

   ```sh
   docker run --rm -it --env-file ~/restore.env \
     -v "$PWD:/var/www/html" -w /var/www/html \
     -v "/path/to/key-folder:/key:ro" \
     integrator-php:8.4 bash scripts/ops/restore.sh
   ```

   [`scripts/ops/restore.sh`](../../scripts/ops/restore.sh) fetches the latest copy, says how old it is
   (the recovery point), decrypts it straight into `pg_restore` (the plain dump never touches disk),
   restores in one transaction, restores objects if a target is set, and lists the rows per table.
5. **Point the app at the restored database** when the script asks: in Laravel Cloud, attach the new
   database to `production` (or set its `DB_*` values), attach the new bucket if you restored objects,
   redeploy, bring the app up (`php artisan up`) and restart the queue worker. Then press Enter.
6. **Check.** The script waits for `/api/v1/health` to answer `"status":"ok"`, prints the rows per table
   and checks that `RESTORE_CHECK_TABLE` has rows. It fails loudly at any step that goes wrong. A healthy
   app is not proof it reads the restored database, so also check the row count **through the app**:
   in Laravel Cloud's command runner run
   `php artisan tinker --execute="echo DB::table('migrations')->count(), PHP_EOL;"` and compare it with
   the `migrations` count the script printed; they must match. Where the script can reach the app's
   command line itself (the local drill), set `RESTORE_APP_CHECK_CMD` to a command that prints that
   count and the script compares it for you.
7. **Before any write** (added by Story 1.6): recreate the audit trigger and the database roles.
   Until Story 1.6 there are none.
8. **Restart the copies.** Change the GitHub secret `BACKUP_DATABASE_URL` to the new database, run
   **Offsite backup** by hand once, and check the heartbeat monitor is green.
9. **Record** the elapsed time the script printed (it counts from its own start; add the time from your
   decision to restore to its start) in the [drill log](#drill-log) as an incident, with what happened.

If the elapsed time passes 3 hours with no end in sight, say so to the people waiting on the system and
keep going: a late restore is still better than none.

## The quarterly drill

The drill proves that a real copy, the real offline key and these steps work within 4 hours. It
restores into a **throwaway** environment and never touches production or staging.

1. Note the start time.
2. Make a throwaway database: a new Serverless Postgres 17 database in a throwaway Laravel Cloud
   environment, or locally with `docker compose up -d pgsql` and
   `CREATE DATABASE integrator_drill` (use `postgresql://integrator:secret@pgsql:5432/integrator_drill`
   and `--network integrator_default` on the `docker run` below).
3. Follow steps 3 to 6 above with the **latest real copy** and the **real offline key**. For a local drill
   let the script start the app itself by adding
   `RESTORE_APP_START_CMD=php artisan serve --no-reload --host=127.0.0.1 --port=8010`,
   `RESTORE_HEALTH_URL=http://127.0.0.1:8010/api/v1/health`, `APP_KEY=<production APP_KEY>` and
   `RESTORE_APP_CHECK_CMD=DB_URL=<the throwaway database URL> php artisan tinker --execute='echo Illuminate\Support\Facades\DB::table(getenv("RESTORE_CHECK_TABLE"))->count(), PHP_EOL;'`.
4. Check a handful of recent records by eye against what you know happened before the copy.
5. Delete the throwaway database and environment, and the local copy of the key if you made one.
6. Add a row to the drill log below and commit it.

Later epics extend the drill: the NIN master and HMAC keys from their second secret store (AD-16), and
the audit trigger and roles (Story 1.6).

### The automated drill

`bash tests/Ops/drill.sh` runs the same two scripts end to end on every push (CI job `drill`): it seeds a
PostgreSQL 17 database and a bucket on MinIO, backs them up encrypted with a throwaway key, restores into
a fresh database and bucket, starts the app on them, checks `/api/v1/health`, has the app read back the
restored rows and object (`RESTORE_APP_CHECK_CMD`), and prints the elapsed time (also in the CI job
summary). It also checks that a backup against an unreachable database fails at the `dump` step and
leaves nothing in the bucket, that `restore.sh` refuses a database that is not empty, and that it picks
the newest copy when an older one is present. It proves the scripts, not the real copy or the real key,
so it does not replace the quarterly drill.

## Drill log

| Date | Who | Kind | Copy restored (recovery point) | Measured time | Result | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| 25 October 2026 | build (Story 1.2) | automated, local MinIO | synthetic copy, about 1 minute old | 19 seconds (restore script); 78 seconds whole drill | passed | Proves the scripts only. The first quarterly drill with a real copy is due in January 2027. |
| 26 October 2026 | build (Story 1.2, after review) | automated, local MinIO | synthetic copy, under 1 minute old | 38 seconds (restore script, now including the app row-count check) | passed | Proves the scripts only. |
