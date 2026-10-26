# Spend alerts

Spend alerts are each provider's own billing alert (owner's decision of 25 October 2026); there is no spend
code in the app. The founder sets each one up from this checklist, sending to the founder's email, and
ticks it here.

Set each threshold at about 1.5 times the expected monthly bill, plus a second, higher one where the
provider allows it, so a runaway cost is caught within days rather than at month end.

## Now

- [ ] **Laravel Cloud**: in the organisation's billing or usage settings, set a spending limit or usage
      alert for the Growth plan, covering compute, Serverless Postgres, the bucket and data transfer.
      Include the database's wake-ups: if it scales to zero, the hourly offsite copy wakes it 24 times a day,
      and each wake is billed as compute for the dump plus the idle time before it sleeps again.
- [ ] **Offsite backup provider** (the founder's S3-compatible provider): set a billing or budget alert on
      the account. Versioning or object lock keeps old versions for their retention, so storage is roughly
      35 days of copies plus replaced objects; check the bucket's lifecycle rules are in place (see
      [laravel-cloud.md](laravel-cloud.md), section 7) so stored copies cannot grow without end.
- [ ] **GitHub Actions**: in the account's **Billing and plans**, set a spending limit for Actions (USD 0
      keeps it to the included minutes) and turn on the usage email. The hourly backup and CI use the
      minutes.
- [ ] **SMTP provider**: set the usage or quota alert, so a failed-job storm or a misconfigured sender
      cannot run up the bill or exhaust the sending quota.
- [ ] **Heartbeat monitor**: on a free plan there is no spend; if on a paid plan, set its billing alert.

## Later, with the epic that brings the provider

- [ ] **SMS provider** for one-time codes: Epic 3.
- [ ] **NIN verification provider**: Epic 4.
- [ ] **WhatsApp** (Meta business messaging): Epic 11.

Each of those epics adds its line here when the provider is contracted.
