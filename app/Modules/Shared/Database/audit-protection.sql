-- The audit trail is insert-only (FR-53, AD-21, Story 1.6).
--
-- A statement trigger on each audit table rejects UPDATE, DELETE and TRUNCATE
-- for every session, including the table owner, even when the statement would
-- touch no row. ENABLE ALWAYS keeps it firing under session_replication_role
-- = replica too. The table owner could still disable or drop it: see
-- docs/decisions/0001-audit-trail-least-privilege-fallback.md.
--
-- Safe to run again. The Shared migration runs it, and the restore runbook
-- (scripts/ops/restore.sh) runs it on a restored database before any write.

CREATE OR REPLACE FUNCTION audit_reject_change() RETURNS trigger
    LANGUAGE plpgsql
AS $$
BEGIN
    RAISE EXCEPTION 'The audit trail is insert-only: % on % is refused.', TG_OP, TG_TABLE_NAME
        USING ERRCODE = 'insufficient_privilege';
END;
$$;

DROP TRIGGER IF EXISTS audit_entries_insert_only ON audit_entries;
CREATE TRIGGER audit_entries_insert_only
    BEFORE UPDATE OR DELETE OR TRUNCATE ON audit_entries
    FOR EACH STATEMENT EXECUTE FUNCTION audit_reject_change();
ALTER TABLE audit_entries ENABLE ALWAYS TRIGGER audit_entries_insert_only;

-- audit_seals: everything above this line protects audit_entries alone (restore.sh applies only that
-- part to a copy taken before the seals existed). Keep this marker line as it is.
DROP TRIGGER IF EXISTS audit_seals_insert_only ON audit_seals;
CREATE TRIGGER audit_seals_insert_only
    BEFORE UPDATE OR DELETE OR TRUNCATE ON audit_seals
    FOR EACH STATEMENT EXECUTE FUNCTION audit_reject_change();
ALTER TABLE audit_seals ENABLE ALWAYS TRIGGER audit_seals_insert_only;
