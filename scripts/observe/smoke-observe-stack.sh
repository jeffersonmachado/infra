#!/bin/bash
# ─── R-Observe: Smoke test de incidente end-to-end ───────────────────────────
# Simula o fluxo completo: agente → api → worker → incidente → IA → timeline.
#
# Uso: ./scripts/observe/smoke-observe-stack.sh [--api-url <url>] [--token <token>]
#
# Pré-requisitos:
#   - Stack observe-core (+ observe-ai para step 5) rodando
#   - OBSERVE_INTERNAL_TOKEN definido ou passado via --token
# ─────────────────────────────────────────────────────────────────────────────
set -e

ROOT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
ENV_FILE="${ENV_FILE:-${ROOT_DIR}/.env.observe}"

# Carrega .env.observe se existir
[ -f "$ENV_FILE" ] && set -a && source "$ENV_FILE" && set +a

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
pass()    { echo -e "${GREEN}[PASS]${NC} $1"; }
fail()    { echo -e "${RED}[FAIL]${NC} $1"; FAILURES=$((FAILURES+1)); }
warn()    { echo -e "${YELLOW}[WARN]${NC} $1"; }
section() { echo -e "\n${BLUE}═══ $1 ═══${NC}"; }
step()    { echo -e "${BLUE}[STEP $1]${NC} $2"; }

FAILURES=0

# ─── Argumentos ───────────────────────────────────────────────────────────────
API_URL="${API_URL:-http://localhost:3000}"
API_BASE="${R_OBSERVE_API_BASE_PATH:-/observe/api}"
TOKEN="${OBSERVE_INTERNAL_TOKEN:-}"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --api-url) API_URL="$2"; shift 2 ;;
    --token)   TOKEN="$2";   shift 2 ;;
    *) shift ;;
  esac
done

BASE="${API_URL}${API_BASE}"
AUTH_HEADER=""
[ -n "$TOKEN" ] && AUTH_HEADER="-H 'x-internal-token: ${TOKEN}'"

smoke_curl() {
  local url="$1"; shift
  curl -sf --max-time 10 -H "Content-Type: application/json" \
       ${TOKEN:+-H "x-internal-token: ${TOKEN}"} "$@" "$url"
}

# ─── Step 1: API health ───────────────────────────────────────────────────────
section "Smoke Test de Incidente (10 steps)"
step 1 "Verificar health da API"

HEALTH=$(smoke_curl "${BASE}/health" 2>/dev/null || echo "ERRO")
if echo "$HEALTH" | grep -q '"status":"ok"'; then
  pass "API respondendo: ${BASE}/health"
else
  fail "API não responde: $HEALTH"
  echo -e "${RED}Stack não está pronta. Abortando smoke test.${NC}"
  exit 1
fi

# ─── Step 2: Status da API (DB + Redis) ──────────────────────────────────────
step 2 "Verificar status dos componentes (DB + Redis)"

STATUS=$(smoke_curl "${BASE}/status" 2>/dev/null || echo "ERRO")
if echo "$STATUS" | grep -q '"db":"ok"'; then
  pass "Banco de dados: OK"
else
  fail "Banco de dados com problema: $STATUS"
fi
if echo "$STATUS" | grep -q '"redis":"ok"'; then
  pass "Redis: OK"
else
  fail "Redis com problema: $STATUS"
fi

# ─── Step 3: Agente envia evento de container parado ─────────────────────────
step 3 "Agente detecta container parado e envia evento"

EVENT_ID="smoke-test-$(date +%s)"
EVENT_PAYLOAD="{
  \"type\": \"container.stopped\",
  \"id\": \"${EVENT_ID}\",
  \"source\": \"r-observe-agent\",
  \"host\": \"smoke-test-host\",
  \"address\": \"127.0.0.1\",
  \"severity\": \"warning\",
  \"service\": \"test-container\",
  \"container_id\": \"abc123def456\",
  \"state\": \"exited\",
  \"image\": \"alpine:latest\",
  \"message\": \"[SMOKE TEST] Container test-container está exited\"
}"

RESP=$(smoke_curl "${BASE}/events" -X POST -d "$EVENT_PAYLOAD" 2>/dev/null || echo "ERRO")
if echo "$RESP" | grep -q '"accepted":true'; then
  pass "Evento enviado: $EVENT_ID"
else
  fail "Evento rejeitado: $RESP"
fi

# ─── Step 4: Worker processa evento (aguarda) ────────────────────────────────
step 4 "Aguardando worker processar o evento (até 15s)"

MAX_WAIT=15
WAITED=0
INCIDENT_ID=""

while [ $WAITED -lt $MAX_WAIT ]; do
  INCIDENTS=$(smoke_curl "${BASE}/incidents" 2>/dev/null || echo "{}")
  # Busca incidente com source_event_id = EVENT_ID
  INCIDENT_ID=$(echo "$INCIDENTS" | python3 -c "
import sys, json
try:
    data = json.load(sys.stdin)
    for i in data.get('incidents', []):
        if i.get('source_event_id') == '${EVENT_ID}':
            print(i['id'])
            break
except: pass
" 2>/dev/null || echo "")
  if [ -n "$INCIDENT_ID" ]; then
    break
  fi
  sleep 2
  WAITED=$((WAITED+2))
done

if [ -n "$INCIDENT_ID" ]; then
  pass "Incidente criado pelo worker: $INCIDENT_ID"
else
  fail "Worker não criou incidente no tempo esperado (${MAX_WAIT}s)"
  warn "Verifique se observe-worker está rodando: docker logs r-observe-worker"
fi

# ─── Step 5: IA gerou análise ────────────────────────────────────────────────
step 5 "Verificar análise de IA no incidente"

if [ -n "$INCIDENT_ID" ]; then
  INC_DETAIL=$(smoke_curl "${BASE}/incidents/${INCIDENT_ID}" 2>/dev/null || echo "{}")
  if echo "$INC_DETAIL" | grep -q '"ai_summary"'; then
    AI_SUMMARY=$(echo "$INC_DETAIL" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('incident',{}).get('ai_summary','')[:80])" 2>/dev/null || echo "")
    if [ -n "$AI_SUMMARY" ]; then
      pass "IA gerou análise: ${AI_SUMMARY}..."
    else
      warn "Campo ai_summary vazio (IA pode estar desabilitada ou sem provider configurado)"
    fi
  else
    warn "Campo ai_summary não encontrado (IA pode estar desabilitada)"
  fi
fi

# ─── Step 6: Timeline criada ─────────────────────────────────────────────────
step 6 "Verificar timeline do incidente"

if [ -n "$INCIDENT_ID" ]; then
  INC_DETAIL=$(smoke_curl "${BASE}/incidents/${INCIDENT_ID}" 2>/dev/null || echo "{}")
  TIMELINE_COUNT=$(echo "$INC_DETAIL" | python3 -c "import sys,json; d=json.load(sys.stdin); print(len(d.get('timeline', [])))" 2>/dev/null || echo "0")
  if [ "$TIMELINE_COUNT" -gt 0 ]; then
    pass "Timeline com $TIMELINE_COUNT evento(s)"
  else
    fail "Timeline vazia"
  fi
fi

# ─── Step 7: Evidências ───────────────────────────────────────────────────────
step 7 "Verificar registro de incidente no banco"

if [ -n "$INCIDENT_ID" ]; then
  INC_DETAIL=$(smoke_curl "${BASE}/incidents/${INCIDENT_ID}" 2>/dev/null || echo "{}")
  if echo "$INC_DETAIL" | grep -q "\"id\":\"${INCIDENT_ID}\""; then
    pass "Incidente persistido no banco: $INCIDENT_ID"
  else
    fail "Incidente não encontrado no banco"
  fi
fi

# ─── Step 8: Status do incidente ─────────────────────────────────────────────
step 8 "Verificar status do incidente"

if [ -n "$INCIDENT_ID" ]; then
  INC_STATUS=$(echo "$INC_DETAIL" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('incident',{}).get('status',''))" 2>/dev/null || echo "")
  if [ "$INC_STATUS" = "open" ]; then
    pass "Incidente com status: open"
  else
    warn "Status do incidente: $INC_STATUS (esperado: open)"
  fi
fi

# ─── Step 9: Solicitar remediação ────────────────────────────────────────────
step 9 "Solicitar remediação (pending_approval)"

if [ -n "$INCIDENT_ID" ]; then
  REMEDIATION_PAYLOAD="{
    \"incident_id\": \"${INCIDENT_ID}\",
    \"action\": \"restart_container\",
    \"reason\": \"[SMOKE TEST] Container parado detectado pelo agente\"
  }"
  REM_RESP=$(smoke_curl "${BASE}/remediation/request" -X POST -d "$REMEDIATION_PAYLOAD" 2>/dev/null || echo "ERRO")
  if echo "$REM_RESP" | grep -q '"status":"pending_approval"'; then
    pass "Remediação criada com status pending_approval"
  else
    fail "Remediação falhou: $REM_RESP"
  fi
fi

# ─── Step 10: Validação final ─────────────────────────────────────────────────
step 10 "Validação final do fluxo"

if [ -n "$INCIDENT_ID" ]; then
  pass "Fluxo end-to-end concluído: agente → evento → incidente → timeline → remediação"
else
  fail "Fluxo incompleto: incidente não foi criado"
fi

# ─── Resultado ────────────────────────────────────────────────────────────────
echo ""
if [ "$FAILURES" -eq 0 ]; then
  echo -e "${GREEN}✔ Smoke test passou: ${FAILURES} falhas.${NC}"
  exit 0
else
  echo -e "${RED}✘ Smoke test com ${FAILURES} falha(s).${NC}"
  echo ""
  echo "Dicas de diagnóstico:"
  echo "  docker logs r-observe-api"
  echo "  docker logs r-observe-worker"
  echo "  docker logs r-observe-ai"
  echo "  npm run observe:logs"
  exit 1
fi
