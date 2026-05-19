#!/usr/bin/env bash
set -euo pipefail

ZIP_FILE="${1:-}"
if [[ -z "$ZIP_FILE" || ! -f "$ZIP_FILE" ]]; then
  echo "[validate-zip] erro: informe um zip valido" >&2
  exit 1
fi

ZIP_BASE="$(basename "$ZIP_FILE" .zip)"
MANIFEST_TXT_ENTRY="${ZIP_BASE}-manifest.txt"
MANIFEST_JSON_ENTRY="${ZIP_BASE}-manifest.json"
MIN_FILE_COUNT=500

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

ALL_ENTRIES_RAW="$TMP_DIR/all-entries-raw.txt"
ALL_ENTRIES_SORTED="$TMP_DIR/all-entries-sorted.txt"
ALL_ENTRIES_DUP="$TMP_DIR/all-entries-dup.txt"
PAYLOAD_ENTRIES_SORTED="$TMP_DIR/payload-entries-sorted.txt"
MANIFEST_EXTRACTED="$TMP_DIR/manifest-extracted.txt"
MANIFEST_SORTED="$TMP_DIR/manifest-sorted.txt"
MANIFEST_DUP="$TMP_DIR/manifest-dup.txt"

unzip -Z -1 "$ZIP_FILE" | sed 's#^\./##' > "$ALL_ENTRIES_RAW"
if [[ ! -s "$ALL_ENTRIES_RAW" ]]; then
  echo "[validate-zip] erro: zip vazio" >&2
  exit 1
fi

BAD=0
LC_ALL=C sort "$ALL_ENTRIES_RAW" > "$ALL_ENTRIES_SORTED"
LC_ALL=C sort "$ALL_ENTRIES_RAW" | uniq -d > "$ALL_ENTRIES_DUP"
if [[ -s "$ALL_ENTRIES_DUP" ]]; then
  echo "[validate-zip] entradas duplicadas no zip:" >&2
  sed -n '1,20p' "$ALL_ENTRIES_DUP" >&2
  BAD=1
fi

ENTRY_COUNT=$(wc -l < "$ALL_ENTRIES_RAW" | tr -d ' ')
if [[ "$ENTRY_COUNT" -lt "$MIN_FILE_COUNT" ]]; then
  echo "[validate-zip] zip com poucos arquivos: $ENTRY_COUNT (< $MIN_FILE_COUNT)" >&2
  BAD=1
fi

if ! grep -Fxq "$MANIFEST_TXT_ENTRY" "$ALL_ENTRIES_SORTED"; then
  echo "[validate-zip] manifesto txt ausente no zip: $MANIFEST_TXT_ENTRY" >&2
  BAD=1
fi
if ! grep -Fxq "$MANIFEST_JSON_ENTRY" "$ALL_ENTRIES_SORTED"; then
  echo "[validate-zip] manifesto json ausente no zip: $MANIFEST_JSON_ENTRY" >&2
  BAD=1
fi

grep -Fvx "$MANIFEST_TXT_ENTRY" "$ALL_ENTRIES_SORTED" | grep -Fvx "$MANIFEST_JSON_ENTRY" > "$PAYLOAD_ENTRIES_SORTED"

set +e
unzip -p "$ZIP_FILE" "$MANIFEST_TXT_ENTRY" > "$MANIFEST_EXTRACTED"
UNZIP_MANIFEST_STATUS=$?
set -e
if [[ "$UNZIP_MANIFEST_STATUS" -ne 0 ]]; then
  echo "[validate-zip] nao foi possivel extrair manifesto txt: $MANIFEST_TXT_ENTRY" >&2
  BAD=1
else
  LC_ALL=C sort "$MANIFEST_EXTRACTED" > "$MANIFEST_SORTED"
  LC_ALL=C sort "$MANIFEST_EXTRACTED" | uniq -d > "$MANIFEST_DUP"
  if [[ -s "$MANIFEST_DUP" ]]; then
    echo "[validate-zip] manifesto com entradas duplicadas:" >&2
    sed -n '1,20p' "$MANIFEST_DUP" >&2
    BAD=1
  fi

  if ! diff -u "$MANIFEST_SORTED" "$PAYLOAD_ENTRIES_SORTED" > "$TMP_DIR/manifest-diff.txt"; then
    echo "[validate-zip] manifesto diverge do conteudo real do zip" >&2
    sed -n '1,60p' "$TMP_DIR/manifest-diff.txt" >&2
    BAD=1
  fi
fi

PATH_FORBIDDEN_REGEX='(^|/)(node_modules|\.git|coverage|dumps|backups|tmp|temp|cache|\.cache)(/|$)|(^|/)(test-results|playwright-report|screenshots|traces)(/|$)|(^|/)\.env($|\.)|(^|/)\.git(ignore|attributes|modules)$|(^|/)[^/]*\.token\.env$|\.(pem|key|p12|pfx|kdbx|dump|dmp|bak|tmp|log)$'

while IFS= read -r p; do
  [[ -z "$p" ]] && continue
  if echo "$p" | grep -Eq "$PATH_FORBIDDEN_REGEX"; then
    echo "[validate-zip] proibido no pacote: $p" >&2
    BAD=1
  fi
done < "$ALL_ENTRIES_RAW"

# Validacoes obrigatorias de inclusao
REQUIRED=(
  "package.json"
  "docker-compose.observe.yml"
  "scripts/observe/validate-compose.sh"
  "r-observe/discovery/package.json"
  "r-observe/discovery/Dockerfile"
  "observe/nginx/conf.d/observe.conf"
  "observe/prometheus/prometheus.yml"
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
  "docs/r-observe-discovery/README.md"
)

for req in "${REQUIRED[@]}"; do
  if ! grep -Fxq -- "$req" "$ALL_ENTRIES_SORTED"; then
    echo "[validate-zip] ausente no pacote: $req" >&2
    BAD=1
  fi
done

if ! grep -Eq '^r-observe/discovery/' "$ALL_ENTRIES_RAW"; then
  echo "[validate-zip] pacote sem arvore r-observe/discovery" >&2
  BAD=1
fi
if ! grep -Eq '^r-observe/migrations/00[4-6]_discovery' "$ALL_ENTRIES_RAW"; then
  echo "[validate-zip] pacote sem migrations de discovery (004/005/006)" >&2
  BAD=1
fi
if ! grep -Eq '^docs/r-observe-discovery/' "$ALL_ENTRIES_RAW"; then
  echo "[validate-zip] pacote sem docs do discovery" >&2
  BAD=1
fi

SECRET_NAME_REGEX='(^|/)(id_rsa|id_dsa|id_ed25519|\.npmrc|\.docker/config\.json|\.aws/credentials)$|\.(jks|asc)$'
while IFS= read -r p; do
  [[ -z "$p" ]] && continue
  if echo "$p" | grep -Eqi "$SECRET_NAME_REGEX"; then
    echo "[validate-zip] potencial secret por nome de arquivo: $p" >&2
    BAD=1
  fi
done < "$ALL_ENTRIES_RAW"

set +e
SECRET_HITS=$(zipgrep -nE '(AKIA[0-9A-Z]{16}|-----BEGIN (RSA|EC|OPENSSH|DSA|PRIVATE) KEY-----|(^|[^A-Za-z])(password|passwd|token|api[_-]?key|secret)\s*[:=]\s*["\x27]?[A-Za-z0-9_./+=@-]{12,})' "$ZIP_FILE" 2>/dev/null)
SECRET_SCAN_STATUS=$?
set -e

if [[ "$SECRET_SCAN_STATUS" -gt 1 ]]; then
  echo "[validate-zip] erro ao executar varredura de secrets" >&2
  BAD=1
fi

if [[ "$SECRET_SCAN_STATUS" -eq 0 ]]; then
  while IFS= read -r hit; do
    [[ -z "$hit" ]] && continue
    if echo "$hit" | grep -Eqi '(example|exemplo|placeholder|sample|dummy|changeme|\{\{|<[^>]+>)'; then
      continue
    fi
    echo "[validate-zip] potencial secret em conteudo: $hit" >&2
    BAD=1
  done <<< "$SECRET_HITS"
fi

if [[ "$BAD" -ne 0 ]]; then
  echo "[validate-zip] FAIL" >&2
  exit 1
fi

echo "[validate-zip] OK"
