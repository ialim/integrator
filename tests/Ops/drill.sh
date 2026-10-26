#!/usr/bin/env bash
# The automated restore drill. Proves the runbook end to end on a throwaway
# environment: a seeded PostgreSQL 17 database and its objects are backed up
# by scripts/ops/backup.sh, encrypted, to MinIO; scripts/ops/restore.sh then
# restores the copy into a fresh database and fresh object storage, starts
# the app on it and checks /api/v1/health; finally the app itself reads the
# restored rows and objects. Fails loudly at the first broken step.
#
# Usage: bash tests/Ops/drill.sh      (needs Docker; runs locally and in CI)
set -Eeuo pipefail

export MSYS_NO_PATHCONV=1 # Git Bash on Windows: pass container paths through untouched
cd "$(dirname "$0")/../.."

export COMPOSE_PROJECT_NAME=integrator-drill
compose=(docker compose -f compose.yaml -f tests/Ops/compose.drill.yaml)
DRILL_STARTED=$SECONDS
ROWS=250

fail() { echo "DRILL FAILED: $*" >&2; exit 1; }
trap 'echo "DRILL FAILED at line ${LINENO}." >&2' ERR
trap '"${compose[@]}" down --volumes --remove-orphans >/dev/null 2>&1 || true' EXIT

S3_KEY=integrator
S3_SECRET=integrator-local
S3_ENDPOINT=http://minio:9000
SOURCE_DB=postgresql://integrator:secret@pgsql:5432/integrator
RESTORED_DB=postgresql://integrator:secret@pgsql:5432/integrator_restored
APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"

# Every command runs in the app image, which carries pg_dump 17, age and rclone.
run() {
    "${compose[@]}" run --rm --no-deps -T \
        -e APP_ENV=production -e APP_DEBUG=false -e "APP_KEY=${APP_KEY}" -e LOG_CHANNEL=json_stderr \
        -e CACHE_STORE=database -e SESSION_DRIVER=database -e QUEUE_CONNECTION=database \
        -e RCLONE_CONFIG_MINIO_TYPE=s3 -e RCLONE_CONFIG_MINIO_PROVIDER=Other \
        -e "RCLONE_CONFIG_MINIO_ENDPOINT=${S3_ENDPOINT}" -e "RCLONE_CONFIG_MINIO_ACCESS_KEY_ID=${S3_KEY}" \
        -e "RCLONE_CONFIG_MINIO_SECRET_ACCESS_KEY=${S3_SECRET}" -e RCLONE_CONFIG_MINIO_FORCE_PATH_STYLE=true \
        "$@"
}

echo "==> Start a throwaway PostgreSQL 17 and MinIO"
"${compose[@]}" down --volumes --remove-orphans >/dev/null 2>&1 || true
"${compose[@]}" build app
"${compose[@]}" up -d --wait pgsql minio
if [[ ! -f vendor/autoload.php ]]; then
    run app composer install --no-interaction --prefer-dist --no-progress
fi

echo "==> Create the buckets: object storage, the offsite copy, and the restore target"
run app sh -c 'for b in drill-objects drill-offsite drill-offsite-fail drill-restored-objects; do rclone mkdir "MINIO:$b"; done'

echo "==> Seed the source database and its objects"
run -e "DB_URL=${SOURCE_DB}" app php artisan migrate --force --no-interaction
run app psql --no-psqlrc --set ON_ERROR_STOP=1 --quiet "${SOURCE_DB}" --command "
    CREATE TABLE drill_receipts (id integer PRIMARY KEY, reference text NOT NULL, amount_kobo bigint NOT NULL);
    INSERT INTO drill_receipts SELECT n, 'RCPT-' || lpad(n::text, 6, '0'), n * 1000 FROM generate_series(1, ${ROWS}) AS n;"
OBJECT_BODY="drill receipt $(date -u +%Y-%m-%dT%H:%M:%SZ) $RANDOM"
run app sh -c "printf '%s' '${OBJECT_BODY}' | rclone rcat MINIO:drill-objects/receipts/2026/drill-receipt.txt"

echo "==> Make a throwaway founder key pair (the private key never goes near the bucket)"
run app sh -c 'age-keygen -o /drill/founder.key 2>/dev/null'
RECIPIENT="$(run app age-keygen -y /drill/founder.key | tr -d '\r')"
[[ "${RECIPIENT}" == age1* ]] || fail "no age public key"

echo "==> A failed backup names the dump step and leaves nothing in the bucket"
if run -e BACKUP_STEP_FILE=/drill/failed-step \
    -e BACKUP_DATABASE_URL=postgresql://integrator:secret@unreachable.invalid:5432/integrator \
    -e "BACKUP_AGE_RECIPIENT=${RECIPIENT}" \
    -e "OFFSITE_S3_ENDPOINT=${S3_ENDPOINT}" -e OFFSITE_S3_BUCKET=drill-offsite-fail \
    -e "OFFSITE_S3_ACCESS_KEY_ID=${S3_KEY}" -e "OFFSITE_S3_SECRET_ACCESS_KEY=${S3_SECRET}" \
    -e "SOURCE_S3_ENDPOINT=${S3_ENDPOINT}" -e SOURCE_S3_BUCKET=drill-objects \
    -e "SOURCE_S3_ACCESS_KEY_ID=${S3_KEY}" -e "SOURCE_S3_SECRET_ACCESS_KEY=${S3_SECRET}" \
    app bash scripts/ops/backup.sh; then
    fail "backup.sh succeeded against an unreachable database"
fi
failed_step="$(run app cat /drill/failed-step | tr -d '\r')"
[[ "${failed_step}" == "dump" ]] || fail "the failed step reads '${failed_step}', not 'dump'"
failed_listing="$(run app rclone lsf --recursive --files-only MINIO:drill-offsite-fail | tr -d '\r')"
grep -qE '\.dump\.age$|\.partial$' <<< "${failed_listing}" && fail "a failed backup left a file: ${failed_listing}"

echo "==> Back up with scripts/ops/backup.sh"
run -e "BACKUP_DATABASE_URL=${SOURCE_DB}" -e "BACKUP_AGE_RECIPIENT=${RECIPIENT}" \
    -e "OFFSITE_S3_ENDPOINT=${S3_ENDPOINT}" -e OFFSITE_S3_BUCKET=drill-offsite \
    -e "OFFSITE_S3_ACCESS_KEY_ID=${S3_KEY}" -e "OFFSITE_S3_SECRET_ACCESS_KEY=${S3_SECRET}" \
    -e "SOURCE_S3_ENDPOINT=${S3_ENDPOINT}" -e SOURCE_S3_BUCKET=drill-objects \
    -e "SOURCE_S3_ACCESS_KEY_ID=${S3_KEY}" -e "SOURCE_S3_SECRET_ACCESS_KEY=${S3_SECRET}" \
    app bash scripts/ops/backup.sh

echo "==> Check the offsite bucket"
listing="$(run app rclone lsf --recursive --files-only MINIO:drill-offsite | tr -d '\r')"
echo "${listing}"
copies="$(grep -cE '^integrator-[0-9]{8}T[0-9]{6}Z\.dump\.age$' <<< "${listing}" || true)"
(( copies == 1 )) || fail "expected one integrator-YYYYMMDDTHHMMSSZ.dump.age, found ${copies}"
grep -q '\.partial$' <<< "${listing}" && fail "a partial upload was left behind"
grep -qx 'objects/receipts/2026/drill-receipt.txt' <<< "${listing}" || fail "objects were not mirrored under objects/"
COPY="$(grep -E '^integrator-.*\.dump\.age$' <<< "${listing}")"
header="$(run app sh -c "rclone cat MINIO:drill-offsite/${COPY} | head -c 21" | tr -d '\r')"
[[ "${header}" == "age-encryption.org/v1" ]] || fail "the copy is not age-encrypted"
if run app sh -c "rclone cat MINIO:drill-offsite/${COPY} | grep -aq RCPT-000001"; then
    fail "plain row data is readable in the offsite copy"
fi

echo "==> restore.sh refuses a database that already has tables"
if refusal="$(run -e "OFFSITE_S3_ENDPOINT=${S3_ENDPOINT}" -e OFFSITE_S3_BUCKET=drill-offsite \
    -e "OFFSITE_S3_ACCESS_KEY_ID=${S3_KEY}" -e "OFFSITE_S3_SECRET_ACCESS_KEY=${S3_SECRET}" \
    -e RESTORE_AGE_IDENTITY_FILE=/drill/founder.key -e "RESTORE_DATABASE_URL=${SOURCE_DB}" \
    -e RESTORE_HEALTH_URL=http://127.0.0.1:8010/api/v1/health \
    app bash scripts/ops/restore.sh 2>&1)"; then
    fail "restore.sh restored over a database with tables"
fi
grep -q 'Restore only into a new, empty database' <<< "${refusal}" || fail "unexpected refusal: ${refusal}"

echo "==> Plant an older copy with different rows, so the restore must pick the newest"
run app psql --no-psqlrc --set ON_ERROR_STOP=1 --quiet postgresql://integrator:secret@pgsql:5432/postgres \
    --command 'CREATE DATABASE integrator_older'
run app psql --no-psqlrc --set ON_ERROR_STOP=1 --quiet postgresql://integrator:secret@pgsql:5432/integrator_older --command "
    CREATE TABLE drill_receipts (id integer PRIMARY KEY, reference text NOT NULL, amount_kobo bigint NOT NULL);
    INSERT INTO drill_receipts SELECT n, 'OLD-' || n, n FROM generate_series(1, 7) AS n;"
run app sh -c "pg_dump --format=custom --no-owner --no-privileges --dbname=postgresql://integrator:secret@pgsql:5432/integrator_older \
    | age --encrypt --recipient '${RECIPIENT}' | rclone rcat MINIO:drill-offsite/integrator-20000101T000000Z.dump.age"

echo "==> Restore with scripts/ops/restore.sh into a clean database"
run app psql --no-psqlrc --set ON_ERROR_STOP=1 --quiet postgresql://integrator:secret@pgsql:5432/postgres \
    --command 'CREATE DATABASE integrator_restored'
run -e "OFFSITE_S3_ENDPOINT=${S3_ENDPOINT}" -e OFFSITE_S3_BUCKET=drill-offsite \
    -e "OFFSITE_S3_ACCESS_KEY_ID=${S3_KEY}" -e "OFFSITE_S3_SECRET_ACCESS_KEY=${S3_SECRET}" \
    -e RESTORE_AGE_IDENTITY_FILE=/drill/founder.key -e "RESTORE_DATABASE_URL=${RESTORED_DB}" \
    -e "RESTORE_OBJECTS_S3_ENDPOINT=${S3_ENDPOINT}" -e RESTORE_OBJECTS_S3_BUCKET=drill-restored-objects \
    -e "RESTORE_OBJECTS_S3_ACCESS_KEY_ID=${S3_KEY}" -e "RESTORE_OBJECTS_S3_SECRET_ACCESS_KEY=${S3_SECRET}" \
    -e 'RESTORE_APP_START_CMD=php artisan serve --no-reload --host=127.0.0.1 --port=8010' \
    -e RESTORE_HEALTH_URL=http://127.0.0.1:8010/api/v1/health -e RESTORE_HEALTH_TIMEOUT=120 \
    -e RESTORE_CHECK_TABLE=drill_receipts -e "RESTORE_EXPECT_MIN_ROWS=${ROWS}" \
    -e RESTORE_RECORD_FILE=/drill/record.md \
    -e "RESTORE_APP_CHECK_CMD=DB_URL=${RESTORED_DB} php artisan tinker --execute='echo Illuminate\\Support\\Facades\\DB::table(getenv(\"RESTORE_CHECK_TABLE\"))->count(), PHP_EOL;'" \
    app bash scripts/ops/restore.sh

echo "==> The app reads the restored rows and objects"
served="$(run -e "DB_URL=${RESTORED_DB}" \
    -e "AWS_ACCESS_KEY_ID=${S3_KEY}" -e "AWS_SECRET_ACCESS_KEY=${S3_SECRET}" -e AWS_DEFAULT_REGION=us-east-1 \
    -e AWS_BUCKET=drill-restored-objects -e "AWS_ENDPOINT=${S3_ENDPOINT}" -e AWS_USE_PATH_STYLE_ENDPOINT=true \
    app php artisan tinker --execute="
        echo 'rows=', Illuminate\Support\Facades\DB::table('drill_receipts')->count(), PHP_EOL;
        echo 'last=', Illuminate\Support\Facades\DB::table('drill_receipts')->max('reference'), PHP_EOL;
        echo 'object=', Illuminate\Support\Facades\Storage::disk('s3')->get('receipts/2026/drill-receipt.txt'), PHP_EOL;" \
    | tr -d '\r')"
echo "${served}"
grep -qx "rows=${ROWS}" <<< "${served}" || fail "the app does not see ${ROWS} restored rows"
grep -qx "last=RCPT-$(printf '%06d' "${ROWS}")" <<< "${served}" || fail "the restored rows differ"
grep -qxF "object=${OBJECT_BODY}" <<< "${served}" || fail "the app cannot read the restored object"

record="$(run app cat /drill/record.md | tr -d '\r')"
grep -qF "| ${COPY} |" <<< "${record}" || fail "the restore did not pick the newest copy ${COPY}: ${record}"
if [[ -n "${GITHUB_STEP_SUMMARY:-}" ]]; then
    {
        echo "### Restore drill"
        echo
        echo "| Started (UTC) | Copy | Restore elapsed | Result |"
        echo "| --- | --- | --- | --- |"
        echo "${record}"
    } >> "${GITHUB_STEP_SUMMARY}"
fi

echo
echo "DRILL PASSED. Restore: ${record}"
echo "Whole drill, including setup: $(( SECONDS - DRILL_STARTED )) seconds."
