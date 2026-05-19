#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

mkdir -p dist
TIMING_LOG="dist/discovery-gate-timing.log"
timestamp() { date -u +%Y-%m-%dT%H:%M:%SZ; }
timelog() { printf '%s | %s\n' "$(timestamp)" "$1" | tee -a "$TIMING_LOG" >/dev/null; }

: > "$TIMING_LOG"

echo "[discovery:gate] Iniciando gate obrigatório do Discovery Engine"
echo "[discovery:gate] Log de timing: $TIMING_LOG"
timelog "discovery:gate start"

run_step() {
  local label="$1"
  shift
  local t0
  t0="$(date +%s)"
  timelog "step_start label=${label}"
  echo
  echo "=================================================="
  echo "[discovery:gate] ${label}"
  echo "=================================================="
  "$@"
  local t1
  t1="$(date +%s)"
  timelog "step_end label=${label} status=pass duration_s=$((t1 - t0))"
  echo "[discovery:gate] PASS: ${label}"
}

run_step "1) discovery:test" npm run discovery:test
run_step "2) discovery:lint" npm run discovery:lint
run_step "3) discovery:smoke" npm run discovery:smoke
run_step "4) discovery:audit" npm run discovery:audit
run_step "5) discovery:integration" npm run discovery:integration

echo
echo "[discovery:gate] OK - todos os 5 gates passaram"
timelog "discovery:gate end status=pass"
