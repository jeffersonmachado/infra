#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

OUTPUT="dist/infra-release.zip"
if [[ "${1:-}" == "--output" && -n "${2:-}" ]]; then
  OUTPUT="$2"
fi

mkdir -p dist
rm -f "$OUTPUT"

OUTPUT_BASE="$(basename "$OUTPUT" .zip)"
MANIFEST_TXT="dist/${OUTPUT_BASE}-manifest.txt"
MANIFEST_JSON="dist/${OUTPUT_BASE}-manifest.json"
RUN_LOG="dist/${OUTPUT_BASE}-zip.log"

MIN_FILE_COUNT=500
ZIP_LEVEL="${ZIP_COMPRESSION_LEVEL:-3}"
TIMING_LOG="dist/zip-timing.log"

timestamp() { date -u +%Y-%m-%dT%H:%M:%SZ; }
timelog() {
  local line
  line="$(timestamp) | $1"
  printf '[zip-release] %s\n' "$1"
  printf '%s\n' "$line" >> "$TIMING_LOG"
  printf '%s\n' "$line" >> "$RUN_LOG"
}

run_with_heartbeat() {
  local label="$1"
  shift
  local t0 now elapsed hb rc

  t0="$(date +%s)"
  (
    while :; do
      sleep 15
      now="$(date +%s)"
      elapsed=$((now - t0))
      printf '[zip-release] %s... %ss\n' "$label" "$elapsed" >&2
    done
  ) &
  hb=$!

  set +e
  "$@"
  rc=$?
  set -e

  kill "$hb" >/dev/null 2>&1 || true
  wait "$hb" 2>/dev/null || true

  return "$rc"
}

mkdir -p dist
touch "$TIMING_LOG"
: > "$RUN_LOG"

is_git_repo() {
  git -C "$ROOT_DIR" rev-parse --is-inside-work-tree >/dev/null 2>&1
}

is_excluded_path() {
  local path="$1"

  case "$path" in
    .git/*|*/.git/*|.gitignore|.gitattributes|.gitmodules|*/.gitignore|*/.gitattributes|*/.gitmodules|node_modules/*|*/node_modules/*|dist/*|*/dist/*|coverage/*|*/coverage/*|test-results/*|*/test-results/*|playwright-report/*|*/playwright-report/*|screenshots/*|*/screenshots/*|traces/*|*/traces/*|tmp/*|*/tmp/*|temp/*|*/temp/*|cache/*|*/cache/*|.cache/*|*/.cache/*|dumps/*|*/dumps/*|backups/*|*/backups/*)
      return 0
      ;;
    *.dump|*.dmp|*.sql.gz|*.bak|*.tmp|*.swp|*.log)
      return 0
      ;;
  esac

  if [[ "$path" =~ (^|/)\.env($|\.) ]]; then
    return 0
  fi

  if [[ "$path" =~ (^|/)\.token\.env$ ]] || [[ "$path" =~ (^|/)[^/]+\.token\.env$ ]]; then
    return 0
  fi

  if [[ "$path" =~ \.(pem|key|p12|pfx|kdbx)$ ]]; then
    return 0
  fi

  return 1
}

collect_files() {
  local -a raw=()

  if is_git_repo; then
    mapfile -d '' -t raw < <(git -C "$ROOT_DIR" ls-files -z --cached --others --exclude-standard --)
  else
    mapfile -d '' -t raw < <(find . -type f -print0)
  fi

  local p
  for p in "${raw[@]}"; do
    p="${p#./}"
    [[ -z "$p" ]] && continue
    if is_excluded_path "$p"; then
      continue
    fi
    printf '%s\n' "$p"
  done
}

REQUIRED_INCLUDE=(
  "package.json"
  "docker-compose.observe.yml"
  "scripts/observe/validate-compose.sh"
  "r-observe/discovery/package.json"
  "r-observe/discovery/Dockerfile"
  "r-observe/discovery/src/index.js"
  "r-observe/discovery/src/engine/discovery-engine.js"
  "r-observe/discovery/src/engine/repository.js"
  "r-observe/discovery/src/scanners/active.js"
  "r-observe/discovery/src/scanners/docker-local.js"
  "r-observe/discovery/src/fingerprint/engine.js"
  "r-observe/discovery/src/topology/engine.js"
  "r-observe/discovery/src/passive/parser.js"
  "r-observe/discovery/src/exporters/prometheus-sd.js"
  "r-observe/discovery/src/security/guardrails.js"
  "r-observe/discovery/tests/unit/fingerprint.test.js"
  "r-observe/discovery/tests/unit/topology.test.js"
  "r-observe/discovery/tests/integration/prom-sd.test.js"
  "r-observe/discovery/tests/simulations/discovery-sim.test.js"
  "r-observe/migrations/004_discovery_engine.sql"
  "r-observe/migrations/005_discovery_dedupe_indexes.sql"
  "r-observe/migrations/006_discovery_policy_limits.sql"
  "observe/nginx/conf.d/observe.conf"
  "observe/prometheus/prometheus.yml"
  "docs/r-observe-discovery/README.md"
)

TMP_RAW="$(mktemp)"
TMP_SORTED="$(mktemp)"
trap 'rm -f "$TMP_RAW" "$TMP_SORTED"' EXIT

timelog "zip_start output=${OUTPUT} level=${ZIP_LEVEL}"

t0_collect=$(date +%s)
collect_files > "$TMP_RAW"
t1_collect=$(date +%s)
timelog "collect_files duration_s=$((t1_collect - t0_collect))"

for req in "${REQUIRED_INCLUDE[@]}"; do
  if [[ ! -f "$req" ]]; then
    echo "[zip-release] erro: arquivo critico ausente no workspace: $req" >&2
    exit 1
  fi
  printf '%s\n' "$req" >> "$TMP_RAW"
done

t0_sort=$(date +%s)
LC_ALL=C sort -u "$TMP_RAW" > "$TMP_SORTED"
t1_sort=$(date +%s)
timelog "sort_manifest duration_s=$((t1_sort - t0_sort))"
cp "$TMP_SORTED" "$MANIFEST_TXT"

COUNT=$(wc -l < "$MANIFEST_TXT" | tr -d ' ')
if [[ "$COUNT" -lt "$MIN_FILE_COUNT" ]]; then
  echo "[zip-release] erro: pacote com poucos arquivos ($COUNT < $MIN_FILE_COUNT)" >&2
  exit 1
fi

SHA=$(sha256sum "$MANIFEST_TXT" | awk '{print $1}')
DATE_UTC=$(date -u +%Y-%m-%dT%H:%M:%SZ)
cat > "$MANIFEST_JSON" <<EOF
{
  "generated_at": "$DATE_UTC",
  "output": "$OUTPUT",
  "file_count": $COUNT,
  "manifest_sha256": "$SHA"
}
EOF

# Inclui payload e manifesto no pacote
t0_zip=$(date +%s)
run_with_heartbeat "compactando payload (${OUTPUT_BASE})" zip -q "-${ZIP_LEVEL}" "$OUTPUT" -@ < "$MANIFEST_TXT"
run_with_heartbeat "anexando manifestos (${OUTPUT_BASE})" zip -q "-${ZIP_LEVEL}" -j "$OUTPUT" "$MANIFEST_TXT" "$MANIFEST_JSON"
t1_zip=$(date +%s)
timelog "zip_payload duration_s=$((t1_zip - t0_zip))"

# Validacao obrigatoria do conteudo
t0_validate=$(date +%s)
VALIDATE_ZIP_SKIP_CONTENT_SCAN=1 run_with_heartbeat "validando zip (${OUTPUT_BASE})" bash ./scripts/package/validate-zip.sh "$OUTPUT"
t1_validate=$(date +%s)
timelog "zip_validate duration_s=$((t1_validate - t0_validate))"

total=$((t1_validate - t0_collect))
timelog "zip_end output=${OUTPUT} file_count=${COUNT} total_s=${total}"
timelog "artifacts manifest_txt=${MANIFEST_TXT} manifest_json=${MANIFEST_JSON}"
timelog "validate_log script=./scripts/package/validate-zip.sh output=${OUTPUT}"

echo "[zip-release] OK: $OUTPUT"
echo "[zip-release] log: $RUN_LOG"
echo "[zip-release] timing: $TIMING_LOG"
