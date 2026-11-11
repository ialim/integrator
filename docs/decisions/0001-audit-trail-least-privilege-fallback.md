# 0001. The audit trail runs without a least-privilege database role

- **Status:** accepted
- **Date:** 9 November 2026
- **Decided by:** the owner (Story 1.6)
- **Settles:** AD-21's open question 1 (whether Laravel Cloud Serverless Postgres allows a least-privilege
  application role)

## Context

AD-21 asks for an application role that holds only `INSERT` and `SELECT` on the audit tables, with migrations
run by a separate owner role, "where the host allows it". The architecture review's spike findings
(`_bmad-output/planning-artifacts/architecture/architecture-integrator-2026-10-09/reviews/review-verification.md`,
finding M-1) found:

- Laravel Cloud's "Database users" feature is documented for Laravel MySQL and RDS, not for Serverless
  Postgres.
- Neon, under Serverless Postgres, allows `CREATE ROLE` through SQL, so a second role is probably possible,
  but it would mean overriding the injected `DB_USERNAME` and `DB_PASSWORD` for web and queue processes.
  That has not been tested on Laravel Cloud.
- The injected role belongs to `neon_superuser`, which inherits `pg_write_all_data`, so
  `REVOKE UPDATE, DELETE` on the audit tables has no effect on it.
- With one role that owns the tables, the trigger protects against application bugs, not against a
  compromised application: a table owner can `ALTER TABLE ... DISABLE TRIGGER` or `DROP TRIGGER`.

## Decision

No runtime role is built. On Laravel Cloud the app's role owns the tables and could disable the trigger, so
tamper evidence rests on three things together:

1. **The trigger** (`app/Modules/Shared/Database/audit-protection.sql`) rejects `UPDATE`, `DELETE` and
   `TRUNCATE` on `audit_entries` and `audit_seals` for every session, the owner included, which stops every
   accidental or buggy change.
2. **The hash chain** (`audit_seals`, sealed by `php artisan audit:seal`): each seal hashes the entry's
   stored values with the previous seal's hash. The chain check on the Audit trail page names the first
   changed, missing or extra row inside the sealed chain. It cannot by itself detect a removed tail (the
   newest entries deleted together with their seals, or rewritten and re-sealed): the remaining chain is
   still whole. So each completed sealing run logs the chain head (last sealed sequence and hash) as
   `audit.sealed` and sends it as the heartbeat ping's body, outside the database; compare the page's head
   with those, or with the offsite copy.
   Each run also checks that both triggers exist and are enabled, and alerts the founder ("Audit trail
   unprotected") naming the table when one is not.
3. **The hourly offsite copy** (AD-20, `docs/operations/restore-runbook.md`), outside Laravel Cloud and out
   of the app's reach: someone who disables the trigger and rewrites rows and seals together still cannot
   change the copies already taken.

## Revisit

Revisit if Laravel Cloud offers separate runtime database users for Serverless Postgres (or a tested way to
run web and queue processes under a role without `pg_write_all_data`). Then: create a role with only
`INSERT` and `SELECT` on the audit tables, point web and queue at it, keep migrations on the owner role, add
the role to the restore runbook's step 7 and the drill, and supersede this note.
