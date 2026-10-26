#!/usr/bin/env bash
# The hourly offsite copy (NFR-5, AD-20).
#
#   1. pg_dump --format=custom of the production database, over TLS;
#   2. encrypted with age to the founder's public key before it leaves the runner;
#   3. uploaded to the offsite bucket under a temporary name, then renamed to
#      integrator-YYYYMMDDTHHMMSSZ.dump.age, so no partial file ever has the final name;
#   4. object storage mirrored into the offsite bucket under objects/ (objects that
#      were replaced or deleted since the last run are kept under objects-replaced/);
#   5. the heartbeat monitor pinged on success (skipped when no URL is set).
#
# Required environment (from the runner's secret store, never the repository):
#   BACKUP_DATABASE_URL            postgresql://user:pass@host:5432/db?sslmode=require
#   BACKUP_AGE_RECIPIENT           the founder's age public key (age1...)
#   OFFSITE_S3_ENDPOINT, OFFSITE_S3_BUCKET, OFFSITE_S3_ACCESS_KEY_ID, OFFSITE_S3_SECRET_ACCESS_KEY
#   SOURCE_S3_ENDPOINT,  SOURCE_S3_BUCKET,  SOURCE_S3_ACCESS_KEY_ID,  SOURCE_S3_SECRET_ACCESS_KEY
# Optional:
#   OFFSITE_S3_REGION, SOURCE_S3_REGION   default "auto"
#   OFFSITE_S3_PREFIX              folder inside the offsite bucket, e.g. "integrator/" (default: bucket root)
#   BACKUP_HEARTBEAT_URL           success ping; unset locally and in CI
#   BACKUP_STEP_FILE               file the current step is written to, read by the workflow on failure
#   PG_DUMP                        pg_dump binary (default: pg_dump); must be version 17 or later
set -Eeuo pipefail

STEP="start"
WORK_DIR=""
REMOTE_TEMP=""

step() {
    STEP="$1"
    echo "==> ${STEP}"
    if [[ -n "${BACKUP_STEP_FILE:-}" ]]; then
        printf '%s\n' "${STEP}" > "${BACKUP_STEP_FILE}"
    fi
}

utc_now() { date -u +%Y-%m-%dT%H:%M:%SZ; }

on_error() {
    local status=$?
    echo "Backup failed at step '${STEP}' at $(utc_now) (exit ${status})." >&2
    if [[ -n "${REMOTE_TEMP}" ]]; then
        rclone deletefile "${REMOTE_TEMP}" >/dev/null 2>&1 || true
    fi
    exit "${status}"
}
trap on_error ERR

cleanup() {
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

# rclone reads remote definitions from RCLONE_CONFIG_<NAME>_<OPTION>, so no
# config file (and no credential) is ever written to disk.
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
require BACKUP_DATABASE_URL BACKUP_AGE_RECIPIENT \
    OFFSITE_S3_ENDPOINT OFFSITE_S3_BUCKET OFFSITE_S3_ACCESS_KEY_ID OFFSITE_S3_SECRET_ACCESS_KEY \
    SOURCE_S3_ENDPOINT SOURCE_S3_BUCKET SOURCE_S3_ACCESS_KEY_ID SOURCE_S3_SECRET_ACCESS_KEY

PG_DUMP="${PG_DUMP:-pg_dump}"
for tool in "${PG_DUMP}" age rclone curl; do
    command -v "${tool}" >/dev/null || { echo "Missing tool: ${tool}" >&2; false; }
done
pg_major="$("${PG_DUMP}" --version | grep -oE '[0-9]+' | head -n1)"
if (( pg_major < 17 )); then
    echo "pg_dump ${pg_major} cannot dump a PostgreSQL 17 server; install pg_dump 17." >&2
    false
fi

s3_remote OFFSITE OFFSITE
s3_remote SOURCE SOURCE

PREFIX="${OFFSITE_S3_PREFIX:-}"
if [[ -n "${PREFIX}" && "${PREFIX}" != */ ]]; then
    PREFIX="${PREFIX}/"
fi
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
NAME="integrator-${STAMP}.dump.age"
OFFSITE="OFFSITE:${OFFSITE_S3_BUCKET}/${PREFIX}"
WORK_DIR="$(mktemp -d)"
ENCRYPTED="${WORK_DIR}/${NAME}"

step "dump and encrypt"
# The plain dump goes straight from pg_dump into age; only ciphertext touches disk.
# The ERR trap is paused so the failing side of the pipe can be named below.
trap - ERR
set +e
"${PG_DUMP}" --format=custom --no-owner --no-privileges --dbname="${BACKUP_DATABASE_URL}" \
    | age --encrypt --recipient "${BACKUP_AGE_RECIPIENT}" --output "${ENCRYPTED}"
statuses=("${PIPESTATUS[@]}")
set -e
trap on_error ERR
# age is checked first: when it fails, pg_dump also fails, on the broken pipe.
if (( statuses[1] != 0 )); then
    step "encrypt"
    false
fi
if (( statuses[0] != 0 )); then
    step "dump"
    false
fi
if [[ "$(head -c 21 "${ENCRYPTED}")" != "age-encryption.org/v1" ]]; then
    step "encrypt"
    echo "The encrypted file has no age header." >&2
    false
fi
echo "Encrypted dump: ${NAME} ($(wc -c < "${ENCRYPTED}") bytes)"

step "upload"
REMOTE_TEMP="${OFFSITE}incoming/${NAME}.partial"
rclone copyto "${ENCRYPTED}" "${REMOTE_TEMP}"

step "rename"
rclone moveto "${REMOTE_TEMP}" "${OFFSITE}${NAME}"
REMOTE_TEMP=""
echo "Uploaded: ${OFFSITE}${NAME}"

step "mirror object storage"
# An empty or wrong source bucket would move the whole mirror into
# objects-replaced/; refuse instead when the source lists nothing but the mirror has objects.
if [[ -z "$(rclone lsf --recursive --files-only "SOURCE:${SOURCE_S3_BUCKET}" | head -n1)" ]] \
    && [[ -n "$(rclone lsf --recursive --files-only "${OFFSITE}objects" 2>/dev/null | head -n1)" ]]; then
    echo "The source bucket ${SOURCE_S3_BUCKET} lists no objects but the offsite mirror has some; not mirroring. Check SOURCE_S3_*." >&2
    false
fi
rclone sync "SOURCE:${SOURCE_S3_BUCKET}" "${OFFSITE}objects" \
    --backup-dir "${OFFSITE}objects-replaced/${STAMP}"

step "heartbeat"
if [[ -n "${BACKUP_HEARTBEAT_URL:-}" ]]; then
    curl -fsS -m 10 --retry 3 -o /dev/null "${BACKUP_HEARTBEAT_URL}"
else
    echo "No heartbeat URL set; skipping the ping."
fi

step "done"
echo "Backup finished at $(utc_now): ${NAME}"
