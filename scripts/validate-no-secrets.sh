#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

OUT_DIR="$ROOT_DIR/dist"
mkdir -p "$OUT_DIR"
REPORT_FILE="$OUT_DIR/security-validation-report.json"

# Padrões críticos sem ambiguidade
mapfile -t matches < <(
  rg -n -i --hidden --glob '!.git/**' --glob '!node_modules/**' --glob '!test-results/**' --glob '!dist/**' --glob '!scripts/validate-no-secrets.sh' \
    'BEGIN PRIVATE KEY|s[h][h]pass' . || true
)

# Detecta segredos hardcoded em arquivos de ambiente reais (não examples)
mapfile -t env_literal_hits < <(
  rg -n --hidden --glob '.env*' --glob '!.env.example' --glob '!.env.mail.example' --glob '!.env.observe.example' --glob '!.env.host-security.example' \
    '^[A-Z0-9_]*(PASSWORD|PASSWD|TOKEN|SECRET|API_KEY)[A-Z0-9_]*=.+$' . \
  | rg -v '=(|CHANGE_ME|__REPLACE_ME__)$' || true
)

# Entropy scan simplificado (somente arquivos de código/config)
mapfile -t entropy_hits < <(
  rg -n --hidden --glob '!.git/**' --glob '!node_modules/**' --glob '!test-results/**' --glob '!dist/**' \
    --glob '!joomla-site/**' --glob '!content/**' \
    '([A-Za-z0-9+/]{40,}|[A-Fa-f0-9]{40,})' . | head -120 || true
)

if [[ ${#env_literal_hits[@]} -gt 0 ]]; then
  matches+=("${env_literal_hits[@]}")
fi

status="green"
if [[ ${#matches[@]} -gt 0 ]]; then
  status="red"
fi

{
  printf '%s\n' "${matches[@]}" > /tmp/validate-no-secrets.matches
  printf '%s\n' "${entropy_hits[@]}" > /tmp/validate-no-secrets.entropy
  python3 - <<'PY' > "$REPORT_FILE"
import json
from datetime import datetime, timezone

def read_lines(path):
    with open(path, 'r', encoding='utf-8', errors='replace') as f:
        return [ln.rstrip('\n') for ln in f if ln.rstrip('\n')]

matches = read_lines('/tmp/validate-no-secrets.matches')
entropy = read_lines('/tmp/validate-no-secrets.entropy')
status = 'red' if matches else 'green'

data = {
    'generated_at': datetime.now(timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ'),
    'status': status,
    'hard_fail_patterns': ['password=', 'passwd=', 'token=', 'secret=', 'api_key=', 'ssh+pass', 'BEGIN PRIVATE KEY'],
    'matches_count': len(matches),
    'entropy_candidates_count': len(entropy),
    'matches': matches,
    'entropy_candidates': entropy,
}
print(json.dumps(data, ensure_ascii=False, indent=2))
PY
}

if [[ "$status" != "green" ]]; then
  echo "[validate-no-secrets] FAIL: padrões críticos encontrados. Veja $REPORT_FILE" >&2
  exit 1
fi

echo "[validate-no-secrets] OK: nenhum padrão crítico encontrado. Relatório: $REPORT_FILE"
