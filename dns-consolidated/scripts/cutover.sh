#!/usr/bin/env bash
# ─── cutover.sh ───────────────────────────────────────────────────────────────
# Executa o corte: para BIND, move dnsdist para porta 53
# EXECUTAR APENAS após validate.sh sem falhas
#
# Pré-condições:
#   1. validate.sh passou sem falhas na porta 5353
#   2. Firewall já apontando para 10.10.2.1 (ou pronto para apontar)
#   3. Janela de manutenção aprovada
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
COMPOSE_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

step() { echo -e "\n${BOLD}${CYAN}▶ $*${RESET}"; }
ok()   { echo -e "  ${GREEN}✓${RESET} $*"; }
warn() { echo -e "  ${YELLOW}!${RESET} $*"; }
die()  { echo -e "${RED}ERRO:${RESET} $*" >&2; exit 1; }

echo -e "${BOLD}${RED}══════════════════════════════════════════════════════════════${RESET}"
echo -e "${BOLD}${RED} CORTE DNS — Esta operação afeta TODA a resolução de nomes!  ${RESET}"
echo -e "${BOLD}${RED}══════════════════════════════════════════════════════════════${RESET}"
echo ""
echo "Pré-condições verificadas?"
echo "  [ ] validate.sh rodou sem falhas na :5353"
echo "  [ ] Janela de manutenção aprovada"
echo "  [ ] Rollback planejado"
echo ""
read -r -p "Digite CONFIRMAR para continuar: " confirm
[[ "$confirm" != "CONFIRMAR" ]] && { echo "Cancelado."; exit 0; }

cd "$COMPOSE_DIR"

# ── 1. Snapshot do estado atual ───────────────────────────────────────────
step "Salvando estado atual do BIND (rollback)..."
mkdir -p rollback
service named status 2>/dev/null > rollback/named-status.txt || true
cp /etc/named.conf rollback/named.conf.bak 2>/dev/null || true
ok "Estado salvo em rollback/"

# ── 2. Para BIND (libera porta 53) ───────────────────────────────────────
step "Parando BIND na porta 53..."
service named stop 2>/dev/null && ok "BIND parado" || warn "BIND já estava parado"

# Confirma que a porta 53 foi liberada
sleep 2
if ss -tlnup 2>/dev/null | grep -q ":53 "; then
  die "Porta 53 ainda em uso! Verifique: ss -tlnup | grep :53"
fi
ok "Porta 53 livre"

# ── 3. Recria dnsdist na porta 53 ────────────────────────────────────────
step "Movendo dnsdist para porta 53..."
DNS_PORT=53 docker compose \
  -f docker-compose.yml \
  --env-file .env \
  up -d dnsdist 2>&1 | tail -5
ok "dnsdist rodando na porta 53"

# ── 4. Validação pós-corte ────────────────────────────────────────────────
step "Validação pós-corte (porta 53)..."
sleep 3
NEW_PORT=53 bash "$SCRIPT_DIR/validate.sh" 2>&1 | tail -20 || {
  warn "Validação com falhas! Iniciando rollback automático..."
  bash "$SCRIPT_DIR/rollback.sh"
  exit 1
}

# ── 5. Desabilita BIND no boot ────────────────────────────────────────────
step "Desabilitando BIND no boot..."
chkconfig named off 2>/dev/null || systemctl disable named 2>/dev/null || true
ok "BIND desabilitado no boot"

echo ""
echo -e "${GREEN}${BOLD}══════════════════════════════════════════════════════════════${RESET}"
echo -e "${GREEN}${BOLD} Corte concluído! DNS rodando no novo stack PowerDNS.         ${RESET}"
echo -e "${GREEN}${BOLD}══════════════════════════════════════════════════════════════${RESET}"
echo ""
echo "Próximos passos:"
echo "  1. Ajustar firewall: 10.10.2.51 → 10.10.2.1 (externo)"
echo "  2. Monitorar por 24h: docker logs -f pdns-auth dns-dnsdist pdns-recursor"
echo "  3. Após 24h estável: descomissionar 10.10.2.51 e 10.10.2.71"
