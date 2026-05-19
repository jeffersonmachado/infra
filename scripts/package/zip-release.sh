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

MIN_FILE_COUNT=500

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

collect_files > "$TMP_RAW"

for req in "${REQUIRED_INCLUDE[@]}"; do
  if [[ ! -f "$req" ]]; then
    echo "[zip-release] erro: arquivo critico ausente no workspace: $req" >&2
    exit 1
  fi
  printf '%s\n' "$req" >> "$TMP_RAW"
done

LC_ALL=C sort -u "$TMP_RAW" > "$TMP_SORTED"
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
zip -q -9 "$OUTPUT" -@ < "$MANIFEST_TXT"
zip -q -9 -j "$OUTPUT" "$MANIFEST_TXT" "$MANIFEST_JSON"

# Validacao obrigatoria do conteudo
bash ./scripts/package/validate-zip.sh "$OUTPUT"

echo "[zip-release] OK: $OUTPUT"
