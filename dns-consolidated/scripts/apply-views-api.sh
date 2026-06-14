#!/usr/bin/env bash
# ─── apply-views-api.sh ───────────────────────────────────────────────────────
# Configura split-horizon nativo do PowerDNS (Views) via API REST:
#   1. networks: associa sub-redes a um nome de view      (PUT /networks/{cidr})
#   2. views:    associa o nome da view a variantes de zona (POST /views/{view})
#
# Dados em ../zones/_networks.json e ../zones/_views.json. Rode
# apply-zones-api.sh primeiro — as variantes de zona (ex.: results.com.br..internal)
# precisam existir antes de serem associadas a uma view.
#
# Uso: ./apply-views-api.sh [host] [--dry-run]
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONF_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
ZONES_DIR="$CONF_DIR/zones"

DRY_RUN=false
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
NETWORKS_FILE="$ZONES_DIR/_networks.json"
VIEWS_FILE="$ZONES_DIR/_views.json"

[[ -f "$NETWORKS_FILE" ]] || die "Não encontrado: $NETWORKS_FILE"
[[ -f "$VIEWS_FILE"    ]] || die "Não encontrado: $VIEWS_FILE"

api_call() {
  local method="$1" path="$2" body="${3:-}"
  if $DRY_RUN; then
    echo -e "  ${YELLOW}[dry-run]${RESET} $method $path" >&2
    [[ -n "$body" ]] && echo "$body" | jq -C . >&2
    return 0
  fi
  if [[ -n "$body" ]]; then
    curl -sS -o /tmp/apply-views-api.out -w '%{http_code}' \
      -H "X-API-Key: $API_KEY" -H 'Content-Type: application/json' \
      -X "$method" "$API$path" -d "$body"
  else
    curl -sS -o /tmp/apply-views-api.out -w '%{http_code}' \
      -H "X-API-Key: $API_KEY" -X "$method" "$API$path"
  fi
}

# ── 1. Networks → view ───────────────────────────────────────────────────────
step "Networks ($NETWORKS_FILE)"
jq -c '.networks[]' "$NETWORKS_FILE" | while read -r entry; do
  net=$(jq -r '.network' <<<"$entry")
  view=$(jq -r '.view' <<<"$entry")
  body=$(jq -n --arg view "$view" '{view: $view}')
  # Caminho da API usa o CIDR cru (ex.: /networks/10.0.0.0/8)
  code=$(api_call PUT "/networks/$net" "$body")
  if $DRY_RUN; then
    ok "[dry-run] $net → view '$view'"
  elif [[ "$code" == "204" ]]; then
    ok "$net → view '$view'"
  else
    warn "PUT /networks/$net → HTTP $code: $(cat /tmp/apply-views-api.out)"
  fi
done

# ── 2. View → variantes de zona ──────────────────────────────────────────────
step "Views ($VIEWS_FILE)"
jq -r '.views | keys[]' "$VIEWS_FILE" | while read -r view; do
  jq -r --arg view "$view" '.views[$view][]' "$VIEWS_FILE" | while read -r zone_variant; do
    body=$(jq -n --arg name "$zone_variant" '{name: $name}')
    code=$(api_call POST "/views/$view" "$body")
    if $DRY_RUN; then
      ok "[dry-run] view '$view' += $zone_variant"
    elif [[ "$code" == "204" ]]; then
      ok "view '$view' += $zone_variant"
    else
      warn "POST /views/$view → HTTP $code: $(cat /tmp/apply-views-api.out)"
    fi
  done
done

ok "Concluído"
echo ""
echo "Verificar:"
echo "  curl -s -H \"X-API-Key: \$DNS_API_KEY\" $API/networks | jq ."
echo "  curl -s -H \"X-API-Key: \$DNS_API_KEY\" $API/views | jq ."
