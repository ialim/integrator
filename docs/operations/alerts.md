# Founder alerts

Every alert goes to the founder by email (AD-20). There are two senders:

| Alert | Sent by | When |
| --- | --- | --- |
| **Failed job** | the app, through the SMTP provider | a queued job fails for good (all its tries used) |
| **Backup failed** | the heartbeat monitor | the hourly copy workflow fails; the email names the step and the time |
| **Backup missing** | the heartbeat monitor | no successful copy for 70 minutes; the monitor shows the last success |
| **Audit seal failed** | the app, through the SMTP provider | a run of `php artisan audit:seal` throws; the email names the error's class |
| **Audit seal overdue** | the app, through the SMTP provider | an audit row has waited more than 30 minutes to be sealed into the hash chain |
| **Audit sealing missing** | the heartbeat monitor | no completed sealing run for 35 minutes, say because the scheduler stopped; a failed run turns the check down at once |
| **Audit trail unprotected** | the app, through the SMTP provider | at the start of a sealing run, an audit table's insert-only trigger is missing or disabled; the email names the table |
| **Spend** | each provider's own billing alert | see [spend-alerts.md](spend-alerts.md) |

Each alert says what failed and when, in WAT and UTC.

## How app alerts behave

- They are sent **synchronously**, never through the queue, so a stuck queue cannot silence them.
- One alert per kind per **15 minutes**. Further occurrences inside the window are held and counted;
  when the window closes, one follow-up says how many more happened and lists them by job and queue (for
  example 50 failed jobs in one minute give one alert, then one follow-up stating the count). The
  scheduler sends follow-ups each minute with `php artisan alerts:send-held`, on one server only.
- The window is saved before the email is sent, so a failed or slow send still throttles the kind. If
  the throttle store itself is down (the database, say), the alert is sent once without throttling and the
  store failure is logged.
- If the email cannot be sent, the app logs `Founder alert not sent` as a JSON line with the request id
  and carries on; the job's own failure is recorded as usual in `failed_jobs`.
- Alerts name the job class, the job id (for `queue:retry`), queue, connection and the exception's class.
  Each email has a plain-text part beside the HTML. They never include the
  exception message, which may hold personal data; use the request id to find the full log line.

## Set up the email (SMTP)

1. Choose an SMTP provider and verify a sending address or domain with it.
2. In Laravel Cloud, `production` environment, set (secrets marked in
   [laravel-cloud.md](laravel-cloud.md), section 6):
   - `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME` (`smtps` for port 465, empty for STARTTLS
     on 587), `MAIL_USERNAME`, `MAIL_PASSWORD`;
   - `MAIL_FROM_ADDRESS` (the verified address) and `MAIL_FROM_NAME=integrator`;
   - `FOUNDER_ALERT_EMAIL`, the founder's address.
3. Redeploy, then run `php artisan alerts:test` from Laravel Cloud's command runner. The founder should
   receive "integrator alert: Test alert" within a minute. If not, the command says so; look in the logs
   for `Founder alert not sent`.

## Set up the heartbeat monitor

The hourly copy runs outside Laravel Cloud, so a monitor outside both watches it. Healthchecks.io is the
example; any monitor with a ping URL, a failure URL and a grace period works.

1. Create an account in the founder's name and a check called `integrator offsite backup`.
2. Schedule: **simple**, period **1 hour**, grace **10 minutes**. The check goes down when no success
   arrives within 70 minutes of the last one ("Backup missing").
3. Notifications: email to the founder's address (the same as `FOUNDER_ALERT_EMAIL` or another the
   founder reads). Turn on both "down" and "up" notifications.
4. Copy the check's ping URL (for example `https://hc-ping.com/<uuid>`) into the GitHub repository secret
   `BACKUP_HEARTBEAT_URL`.
   - On success the workflow pings that URL.
   - On failure it posts "Backup failed at step: ..., When: ... WAT (... UTC), Run: <link>" to
     `<URL>/fail`, so the check goes down at once and the email carries the step and time.
5. Test it: run **Offsite backup** by hand in GitHub Actions and watch the check turn green. To see a
   failure email, run `curl -fsS --data-raw "test failure" https://hc-ping.com/<uuid>/fail`, then run the
   workflow again to turn it green.

## Set up the audit sealing heartbeat

The scheduler runs `php artisan audit:seal` every 5 minutes (AD-21). The app alerts by itself when a run
fails or rows wait too long, but if the scheduler stops, nothing in the app runs to say so. Each completed
run therefore pings a second heartbeat check; a run that seals nothing because writers stayed open does
not ping (it logs `audit.seal_busy`), and a failed run GETs the check's `/fail` URL.

**The chain head.** Each completed run sends the chain head, the last sealed sequence and its hash, as
the ping's POST body (`{"sealed": 3, "last_sealed_sequence": 1042, "head_hash": "…"}`), and logs the same as
`audit.sealed`. Healthchecks.io keeps each ping's body in the check's event log. The chain check on the
Audit trail page cannot by itself see the newest rows removed together with their seals (or rewritten
and sealed again): the chain it finds is still whole, only shorter or different. To catch that, compare the
head the page reports with the latest heads in the check's event log, or with the offsite copy: a head
whose sequence went down, or whose hash changed for the same sequence, means the trail was tampered with.

1. In the same Healthchecks.io account, create a check called `integrator audit sealing`.
2. Schedule: **simple**, period **5 minutes**, grace **30 minutes**. The check goes down when no completed
   run arrives within 35 minutes ("Audit sealing missing").
3. Notifications: email to the founder, "down" and "up", as for the backup check.
4. Copy the check's ping URL into Laravel Cloud's `production` environment as the secret
   `AUDIT_SEAL_HEARTBEAT_URL` ([laravel-cloud.md](laravel-cloud.md), section 6) and redeploy. Unset means
   no ping, so a local or staging app never pings production's check.
5. Test it: run `php artisan audit:seal` from Laravel Cloud's command runner and watch the check turn green.

## When an alert arrives

- **Failed job**: open the logs in Laravel Cloud, search for the request id, fix the cause, then retry with
  `php artisan queue:retry <Job id from the email>` (`php artisan queue:failed` lists them all). A follow-up
  alert carries the request id of the most recent occurrence it counts.
- **Backup failed**: open the run link in the email. A failed `dump` step usually means the database's
  public endpoint or `BACKUP_DATABASE_URL` changed; `upload`, `rename` or `mirror object storage` point to
  the offsite or source bucket keys. Fix it and run the workflow by hand; the next hourly run retries
  anyway.
- **Backup missing**: check **Actions**: whether the workflow ran, whether `OFFSITE_BACKUP_ENABLED` is
  still `true`, and whether GitHub disabled the schedule after 60 days without activity. Run it by hand
  to confirm.
- **Audit seal failed**: search the logs for `audit.seal_failed` and the request id, fix the cause, then run
  `php artisan audit:seal` by hand; the next scheduled run retries anyway. Nothing is lost meanwhile: rows
  wait unsealed, and sealing never changes or re-seals a row.
- **Audit seal overdue** or **Audit sealing missing**: check that the scheduler is on in Laravel Cloud and
  that `audit:seal` runs (`php artisan schedule:list`). If the logs show `audit.seal_busy` ("writers stayed
  open"), look for a long-running transaction. Then run `php artisan audit:seal` by hand and check the chain on the console's
  Audit trail page.
- **Audit trail unprotected**: someone or something disabled or dropped the trigger, which only the
  table owner can do. Run `psql --set ON_ERROR_STOP=1 --file=app/Modules/Shared/Database/audit-protection.sql`
  against the database at once, find out who did it (the database logs), then check the chain on the Audit
  trail page and compare its head with the heartbeat's event log and the offsite copy.
