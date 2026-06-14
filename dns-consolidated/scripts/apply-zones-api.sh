#!/usr/bin/env bash
# ─── apply-zones-api.sh ───────────────────────────────────────────────────────
# Cria/atualiza zonas e rrsets no pdns-auth via API REST, a partir dos
# arquivos de dados em ../zones/*.json (exceto os prefixados com "_", que são
# configuração de networks/views — ver apply-views-api.sh).
#
# Por que via API e não SQL/seed: o backend agora é LMDB (necessário para
# Views/split-horizon nativo — ver pdns-auth/pdns.conf), que não é populado
# por scripts de init SQL como o antigo gmysql/MariaDB.
#
# Uso: ./apply-zones-api.sh [host] [--dry-run] [--zone nome_da_zona]
#   host: endereço do pdns-auth (padrão: 127.0.0.1, porta da API em .env/PDNS_API_PORT)
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONF_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
ZONES_DIR="$CONF_DIR/zones"

DRY_RUN=false
ONLY_ZONE=""
HOST="127.0.0.1"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

step() { echo -e "\n${BOLD}${CYAN}▶ $*${RESET}"; }
ok()   { echo -e "  ${GREEN}✓${RESET} $*"; }
warn() { echo -e "  ${YELLOW}!${RESET} $*"; }
die()  { echo -e "${RED}ERRO:${RESET} $*" >&2; exit 1; }

while [[ $# -gt 0 ]]; do
  case "$1" in
    --dry-run) DRY_RUN=true; shift ;;
    --zone)    ONLY_ZONE="$2"; shift 2 ;;
    -*)        die "Argumento desconhecido: $1" ;;
    *)         HOST="$1"; shift ;;
  esac
done

command -v jq   >/dev/null || die "jq não encontrado — instale antes de continuar"
command -v curl >/dev/null || die "curl não encontrado — instale antes de continuar"

[[ -f "$CONF_DIR/.env" ]] && source "$CONF_DIR/.env"
API_KEY="${DNS_API_KEY:-}"
API_PORT="${PDNS_API_PORT:-9081}"
[[ -z "$API_KEY" || "$API_KEY" == CHANGE_ME_* ]] && die "DNS_API_KEY não definido/configurado em $CONF_DIR/.env"

API="http://$HOST:$API_PORT/api/v1/servers/localhost"

api_call() {
  local method="$1" path="$2" body="${3:-}"
  if $DRY_RUN; then
    echo -e "  ${YELLOW}[dry-run]${RESET} $method $path" >&2
    [[ -n "$body" ]] && echo "$body" | jq -C . >&2
    return 0
  fi
  if [[ -n "$body" ]]; then
    curl -sS -o /tmp/apply-zones-api.out -w '%{http_code}' \
      -H "X-API-Key: $API_KEY" -H 'Content-Type: application/json' \
      -X "$method" "$API$path" -d "$body"
  else
    curl -sS -o /tmp/apply-zones-api.out -w '%{http_code}' \
      -H "X-API-Key: $API_KEY" -X "$method" "$API$path"
  fi
}

# Converte o JSON de dados (records como array de strings) para o formato
# esperado pela API (rrsets com changetype REPLACE e records como objetos)
to_rrsets() {
  jq '[.rrsets[] | {
        name, type, ttl,
        changetype: "REPLACE",
        records: [.records[] | {content: ., disabled: false}]
      }]' "$1"
}

zone_exists() {
  local zone_id="$1" code
  code=$(api_call GET "/zones/$zone_id")
  [[ "$code" == "200" ]]
}

step "Aplicando zonas em $API (zonas em $ZONES_DIR)"

shopt -s nullglob
for f in "$ZONES_DIR"/*.json; do
  base="$(basename "$f")"
  [[ "$base" == _* ]] && continue
  name=$(jq -r '.name' "$f")
  zone_id="${name%.}."   # zone_id da API == nome da zona com ponto final
  [[ -n "$ONLY_ZONE" && "$ONLY_ZONE" != "$name" ]] && continue

  step "Zona: $name ($base)"
  rrsets=$(to_rrsets "$f")

  if $DRY_RUN || zone_exists "$zone_id"; then
    body=$(jq -n --argjson rrsets "$rrsets" '{rrsets: $rrsets}')
    code=$(api_call PATCH "/zones/$zone_id" "$body")
    if $DRY_RUN; then
      ok "[dry-run] PATCH preparado para $name"
    elif [[ "$code" == "204" ]]; then
      ok "rrsets atualizados ($name)"
    else
      warn "PATCH $name → HTTP $code: $(cat /tmp/apply-zones-api.out)"
    fi
  else
    kind=$(jq -r '.kind' "$f")
    nameservers=$(jq -c '.nameservers' "$f")
    body=$(jq -n --arg name "$name" --arg kind "$kind" \
                  --argjson nameservers "$nameservers" --argjson rrsets "$rrsets" \
                  '{name: $name, kind: $kind, nameservers: $nameservers, rrsets: $rrsets}')
    code=$(api_call POST "/zones" "$body")
    if [[ "$code" == "201" ]]; then
      ok "zona criada ($name)"
    else
      warn "POST $name → HTTP $code: $(cat /tmp/apply-zones-api.out)"
    fi
  fi
done

ok "Concluído"
