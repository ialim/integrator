#!/usr/bin/env bash
# Restore from the offsite copy (NFR-5, AD-20). See docs/operations/restore-runbook.md.
#
#   1. fetch the latest integrator-*.dump.age (or RESTORE_DUMP) from the offsite bucket;
#   2. decrypt it with the founder's private age key;
#   3. pg_restore it into a clean database (the script refuses a database with tables);
#   4. restore object storage from the offsite objects/ folder (when a target is set);
#   5. start the app (when RESTORE_APP_START_CMD is set) or wait for it to be pointed here;
#   6. check /api/v1/health and a row count;
#   7. print, and optionally record, the elapsed time against the 4-hour target.
#
# Required environment:
#   OFFSITE_S3_ENDPOINT, OFFSITE_S3_BUCKET, OFFSITE_S3_ACCESS_KEY_ID, OFFSITE_S3_SECRET_ACCESS_KEY
#   RESTORE_AGE_IDENTITY_FILE      path to the founder's private age key, brought from offline storage
#   RESTORE_DATABASE_URL           postgresql://... of a NEW, empty database (never staging)
#   RESTORE_HEALTH_URL             e.g. https://app.example/api/v1/health
# Optional:
#   OFFSITE_S3_REGION (default auto), OFFSITE_S3_PREFIX (default: bucket root)
#   RESTORE_DUMP                   a specific copy name instead of the latest
#   RESTORE_OBJECTS_S3_ENDPOINT, RESTORE_OBJECTS_S3_BUCKET, RESTORE_OBJECTS_S3_ACCESS_KEY_ID,
#   RESTORE_OBJECTS_S3_SECRET_ACCESS_KEY, RESTORE_OBJECTS_S3_REGION
#                                  where objects are restored to; unset skips step 4
#   RESTORE_APP_START_CMD          command that serves the app against the restored database;
#                                  it runs in the background with DB_URL set to RESTORE_DATABASE_URL
#   RESTORE_HEALTH_TIMEOUT         seconds to wait for the health check (default 900)
#   RESTORE_CHECK_TABLE            table whose rows must be present (default migrations)
#   RESTORE_EXPECT_MIN_ROWS        minimum rows in that table (default 1)
#   RESTORE_APP_CHECK_CMD          command that reads the row count of $RESTORE_CHECK_TABLE through the
#                                  app and prints it on its last line; it must equal the psql count
#   RESTORE_RECORD_FILE            a Markdown file the result row is appended to
#   PG_RESTORE, PSQL               binaries (default pg_restore, psql); version 17 or later
set -Eeuo pipefail

STARTED_AT="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
SECONDS=0
STEP="start"
WORK_DIR=""
APP_PID=""
DUMP_NAME="(none)"

step() {
    STEP="$1"
    echo "==> [$(elapsed)] ${STEP}"
}

elapsed() { printf '%dh %02dm %02ds' $((SECONDS / 3600)) $((SECONDS % 3600 / 60)) $((SECONDS % 60)); }

record() {
    local result="$1"
    echo "Elapsed: $(elapsed) (${SECONDS} seconds) against the 4-hour target. Result: ${result}."
    if [[ -n "${RESTORE_RECORD_FILE:-}" ]]; then
        printf '| %s | %s | %s (%s s) | %s |\n' "${STARTED_AT}" "${DUMP_NAME}" "$(elapsed)" "${SECONDS}" "${result}" \
            >> "${RESTORE_RECORD_FILE}"
    fi
}

on_error() {
    local status=$?
    # set -E also runs this trap inside command substitutions; only the main shell reports and records.
    if (( BASH_SUBSHELL > 0 )); then
        exit "${status}"
    fi
    echo "RESTORE FAILED at step '${STEP}' (exit ${status})." >&2
    record "failed at ${STEP}"
    exit "${status}"
}
trap on_error ERR

cleanup() {
    if [[ -n "${APP_PID}" ]]; then
        kill -- "-${APP_PID}" >/dev/null 2>&1 || kill "${APP_PID}" >/dev/null 2>&1 || true
    fi
    if [[ -n "${WORK_DIR}" && -d "${WORK_DIR}" ]]; then
        rm -rf "${WORK_DIR}"
    fi
}
trap cleanup EXIT

require() {
    local missing=0 name
    for name in "$@"; do
        if [[ -z "${!name:-}" ]]; then
            echo "Missing required setting: ${name}" >&2
            missing=1
        fi
    done
    return "${missing}"
}

s3_remote() {
    local name="$1" prefix="$2"
    local endpoint="${prefix}_S3_ENDPOINT" key="${prefix}_S3_ACCESS_KEY_ID" secret="${prefix}_S3_SECRET_ACCESS_KEY" region="${prefix}_S3_REGION"
    export "RCLONE_CONFIG_${name}_TYPE=s3"
    export "RCLONE_CONFIG_${name}_PROVIDER=Other"
    export "RCLONE_CONFIG_${name}_ENDPOINT=${!endpoint}"
    export "RCLONE_CONFIG_${name}_ACCESS_KEY_ID=${!key}"
    export "RCLONE_CONFIG_${name}_SECRET_ACCESS_KEY=${!secret}"
    export "RCLONE_CONFIG_${name}_REGION=${!region:-auto}"
    export "RCLONE_CONFIG_${name}_FORCE_PATH_STYLE=true"
    export "RCLONE_CONFIG_${name}_NO_CHECK_BUCKET=true"
}

step "check settings and tools"
require OFFSITE_S3_ENDPOINT OFFSITE_S3_BUCKET OFFSITE_S3_ACCESS_KEY_ID OFFSITE_S3_SECRET_ACCESS_KEY \
    RESTORE_AGE_IDENTITY_FILE RESTORE_DATABASE_URL RESTORE_HEALTH_URL
[[ -r "${RESTORE_AGE_IDENTITY_FILE}" ]] || { echo "Cannot read the age identity file." >&2; false; }

PG_RESTORE="${PG_RESTORE:-pg_restore}"
PSQL="${PSQL:-psql}"
for tool in "${PG_RESTORE}" "${PSQL}" age rclone curl; do
    command -v "${tool}" >/dev/null || { echo "Missing tool: ${tool}" >&2; false; }
done
pg_major="$("${PG_RESTORE}" --version | grep -oE '[0-9]+' | head -n1)"
(( pg_major >= 17 )) || { echo "pg_restore ${pg_major} is older than the dump; install 17." >&2; false; }

s3_remote OFFSITE OFFSITE
PREFIX="${OFFSITE_S3_PREFIX:-}"
if [[ -n "${PREFIX}" && "${PREFIX}" != */ ]]; then
    PREFIX="${PREFIX}/"
fi
OFFSITE="OFFSITE:${OFFSITE_S3_BUCKET}/${PREFIX}"

sql() { "${PSQL}" --no-psqlrc --quiet --tuples-only --no-align --set ON_ERROR_STOP=1 --dbname="${RESTORE_DATABASE_URL}" --command "$1"; }

step "check the target database is clean"
# Any relation (table, view, sequence, index...), type or user schema outside the system schemas counts.
existing="$(sql "WITH user_ns AS (
        SELECT oid, nspname FROM pg_namespace
        WHERE nspname NOT IN ('pg_catalog', 'information_schema') AND nspname NOT LIKE 'pg\_toast%' AND nspname NOT LIKE 'pg\_temp%'
    )
    SELECT (SELECT count(*) FROM pg_class WHERE relnamespace IN (SELECT oid FROM user_ns))
         + (SELECT count(*) FROM pg_type WHERE typnamespace IN (SELECT oid FROM user_ns))
         + (SELECT count(*) FROM user_ns WHERE nspname <> 'public')")"
if (( existing != 0 )); then
    echo "The target database already has ${existing} relations, types or schemas. Restore only into a new, empty database." >&2
    false
fi

step "fetch the copy"
if [[ -n "${RESTORE_DUMP:-}" ]]; then
    DUMP_NAME="${RESTORE_DUMP}"
else
    DUMP_NAME="$(rclone lsf --files-only --include 'integrator-*.dump.age' "${OFFSITE}" | sort | tail -n1)"
fi
[[ -n "${DUMP_NAME}" ]] || { echo "No integrator-*.dump.age copy found in ${OFFSITE}" >&2; false; }
stamp="${DUMP_NAME#integrator-}"
stamp="${stamp%.dump.age}"
if taken="$(date -u -d "${stamp:0:4}-${stamp:4:2}-${stamp:6:2}T${stamp:9:2}:${stamp:11:2}:${stamp:13:2}Z" +%s 2>/dev/null)"; then
    echo "Copy: ${DUMP_NAME}, taken $(( ($(date -u +%s) - taken) / 60 )) minutes before now (recovery point)."
fi
WORK_DIR="$(mktemp -d)"
rclone copyto "${OFFSITE}${DUMP_NAME}" "${WORK_DIR}/${DUMP_NAME}"

step "decrypt and restore the database"
# Decrypted bytes go straight into pg_restore; the plain dump never touches disk.
# The ERR trap is paused so the failing side of the pipe can be named below.
trap - ERR
set +e
age --decrypt --identity "${RESTORE_AGE_IDENTITY_FILE}" "${WORK_DIR}/${DUMP_NAME}" \
    | "${PG_RESTORE}" --no-owner --no-privileges --exit-on-error --single-transaction --dbname="${RESTORE_DATABASE_URL}"
statuses=("${PIPESTATUS[@]}")
set -e
trap on_error ERR
# age exits 141 (SIGPIPE) when pg_restore stops reading, so pg_restore is blamed first then.
if (( statuses[0] != 0 && statuses[0] != 141 )); then
    step "decrypt"
    false
fi
if (( statuses[1] != 0 )); then
    step "pg_restore"
    false
fi
if (( statuses[0] != 0 )); then
    step "decrypt"
    false
fi

step "restore object storage"
if [[ -n "${RESTORE_OBJECTS_S3_BUCKET:-}" ]]; then
    require RESTORE_OBJECTS_S3_ENDPOINT RESTORE_OBJECTS_S3_ACCESS_KEY_ID RESTORE_OBJECTS_S3_SECRET_ACCESS_KEY
    s3_remote TARGET RESTORE_OBJECTS
    rclone copy "${OFFSITE}objects" "TARGET:${RESTORE_OBJECTS_S3_BUCKET}"
    echo "Objects restored: $(rclone lsf --recursive --files-only "TARGET:${RESTORE_OBJECTS_S3_BUCKET}" | wc -l)"
else
    echo "No RESTORE_OBJECTS_S3_BUCKET set; object storage left as it is."
fi

step "start the app"
if [[ -n "${RESTORE_APP_START_CMD:-}" ]]; then
    DB_URL="${RESTORE_DATABASE_URL}" setsid bash -c "${RESTORE_APP_START_CMD}" > "${WORK_DIR}/app.log" 2>&1 &
    APP_PID=$!
elif [[ -t 0 ]]; then
    read -r -p "Point the app at the restored database and deploy it, then press Enter. " _
else
    echo "Waiting for the app at ${RESTORE_HEALTH_URL} to be pointed at the restored database."
fi

step "check health"
deadline=$(( SECONDS + ${RESTORE_HEALTH_TIMEOUT:-900} ))
until body="$(curl -fsS -m 10 "${RESTORE_HEALTH_URL}" 2>/dev/null)" && [[ "${body}" == *'"status":"ok"'* ]]; do
    if (( SECONDS >= deadline )); then
        echo "The health check did not pass in time. Last response: ${body:-none}" >&2
        if [[ -n "${APP_PID}" ]]; then cat "${WORK_DIR}/app.log" >&2 || true; fi
        false
    fi
    sleep 2
done
echo "Health: ${body}"

step "check the restored rows"
echo "Rows per table:"
for table in $(sql "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename"); do
    echo "  ${table}: $(sql "SELECT count(*) FROM public.\"${table}\"")"
done
check_table="${RESTORE_CHECK_TABLE:-migrations}"
[[ "${check_table}" =~ ^[a-z_][a-z0-9_]*$ ]] || { echo "Bad RESTORE_CHECK_TABLE: ${check_table}" >&2; false; }
rows="$(sql "SELECT count(*) FROM public.\"${check_table}\"")"
if (( rows < ${RESTORE_EXPECT_MIN_ROWS:-1} )); then
    echo "${check_table} has ${rows} rows; expected at least ${RESTORE_EXPECT_MIN_ROWS:-1}." >&2
    false
fi

step "check the app reads the restored database"
if [[ -n "${RESTORE_APP_CHECK_CMD:-}" ]]; then
    # The command reads the row count of RESTORE_CHECK_TABLE through the app and prints it last.
    app_rows="$(RESTORE_CHECK_TABLE="${check_table}" bash -c "${RESTORE_APP_CHECK_CMD}" | tr -d '
' | tail -n1 | tr -cd '0-9')"
    if [[ "${app_rows}" != "${rows}" ]]; then
        echo "The app reads ${app_rows:-nothing} rows in ${check_table}; the restored database has ${rows}." >&2
        false
    fi
    echo "The app reads ${app_rows} rows in ${check_table}, as restored."
else
    echo "No RESTORE_APP_CHECK_CMD set; check the app's row count by hand (runbook step 6)."
fi

step "done"
echo "Restored ${DUMP_NAME}: ${check_table} has ${rows} rows and the app is healthy."
record "passed"
