#!/usr/bin/env bash
# ─── deploy.sh ────────────────────────────────────────────────────────────────
# Deploy completo do stack DNS consolidado em 10.10.2.1
# Roda em paralelo ao BIND existente (porta 5353)
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
COMPOSE_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
TARGET="${1:-10.10.2.1}"
SSH_OPT="-o StrictHostKeyChecking=no -o PreferredAuthentications=password -o HostKeyAlgorithms=+ssh-rsa"
REMOTE_DIR="/opt/results/infra/dns-consolidated"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

step() { echo -e "\n${BOLD}${CYAN}▶ $*${RESET}"; }
ok()   { echo -e "  ${GREEN}✓${RESET} $*"; }
warn() { echo -e "  ${YELLOW}!${RESET} $*"; }
die()  { echo -e "${RED}ERRO:${RESET} $*" >&2; exit 1; }

RUN="ssh $SSH_OPT root@$TARGET"

echo -e "${BOLD}── DNS Consolidado: Deploy em $TARGET ──────────────────────────────${RESET}"

# ── 1. Sincronizar arquivos ────────────────────────────────────────────────
step "Sincronizando arquivos para $TARGET:$REMOTE_DIR..."
$RUN "mkdir -p $REMOTE_DIR"
rsync -az \
  -e "ssh $SSH_OPT" \
  --exclude='.env' \
  --exclude='rendered/' \
  "$COMPOSE_DIR/" "root@$TARGET:$REMOTE_DIR/"
ok "Arquivos sincronizados"

# ── 2. Criar .env de produção ─────────────────────────────────────────────
step "Verificando .env no servidor..."
if ! $RUN "test -f $REMOTE_DIR/.env"; then
  warn ".env não encontrado — criando a partir do exemplo..."
  $RUN "cp $REMOTE_DIR/.env.example $REMOTE_DIR/.env"
  warn "EDITE $REMOTE_DIR/.env em $TARGET antes de continuar!"
  warn "  ssh root@$TARGET nano $REMOTE_DIR/.env"
  exit 1
fi
ok ".env presente"

# ── 3. Substituir placeholders nas configs ────────────────────────────────
step "Renderizando configs com credenciais do .env..."
$RUN bash << 'REMOTE'
set -euo pipefail
source /opt/results/infra/dns-consolidated/.env
CONF_DIR="/opt/results/infra/dns-consolidated"
RENDERED_DIR="$CONF_DIR/rendered"
API_KEY="${DNS_API_KEY:-}"

if [ -z "$API_KEY" ]; then
  echo "ERRO: DNS_API_KEY deve estar definido no .env" >&2
  exit 1
fi

if [ "$API_KEY" = "CHANGE_ME_gerar_com_openssl_rand_hex_32" ]; then
  echo "ERRO: edite DNS_API_KEY no .env antes do deploy" >&2
  exit 1
fi

escape_sed_replacement() {
  printf '%s' "$1" | sed 's/[\/&]/\\&/g'
}

rm -rf "$RENDERED_DIR"
mkdir -p "$RENDERED_DIR/pdns-auth" "$RENDERED_DIR/pdns-recursor" "$RENDERED_DIR/dnsdist"
cp "$CONF_DIR/pdns-auth/pdns.conf" "$RENDERED_DIR/pdns-auth/pdns.conf"
cp "$CONF_DIR/pdns-recursor/recursor.conf" "$RENDERED_DIR/pdns-recursor/recursor.conf"
cp "$CONF_DIR/dnsdist/dnsdist.conf" "$RENDERED_DIR/dnsdist/dnsdist.conf"

# pdns-auth: substituir placeholders
sed -i "s/PDNS_API_KEY_PLACEHOLDER/$(escape_sed_replacement "$API_KEY")/g" \
  "$RENDERED_DIR/pdns-auth/pdns.conf"

# pdns-recursor
sed -i "s/PDNS_API_KEY_PLACEHOLDER/$(escape_sed_replacement "$API_KEY")/g" \
  "$RENDERED_DIR/pdns-recursor/recursor.conf"

# dnsdist
sed -i \
  "s/DNSDIST_WEB_PASSWORD_PLACEHOLDER/$(escape_sed_replacement "$API_KEY")/g;
   s/DNSDIST_API_KEY_PLACEHOLDER/$(escape_sed_replacement "$API_KEY")/g" \
  "$RENDERED_DIR/dnsdist/dnsdist.conf"

if grep -R "PLACEHOLDER\|CHANGE_ME_" \
  "$RENDERED_DIR/pdns-auth/pdns.conf" \
  "$RENDERED_DIR/pdns-recursor/recursor.conf" \
  "$RENDERED_DIR/dnsdist/dnsdist.conf" >/dev/null; then
  echo "ERRO: placeholders restantes nas configs renderizadas" >&2
  exit 1
fi

echo "Configs renderizadas"
REMOTE
ok "Configs renderizadas"

# ── 4. Subir containers (porta 5353 — sem afetar BIND) ───────────────────
step "Subindo containers na porta 5353..."
$RUN bash << 'REMOTE'
cd /opt/results/infra/dns-consolidated
docker compose -f docker-compose.yml --env-file .env pull --quiet 2>/dev/null || true
docker compose -f docker-compose.yml --env-file .env up -d 2>&1
REMOTE

sleep 15
ok "Containers iniciados"

# ── 5. Verificar saúde ────────────────────────────────────────────────────
step "Verificando saúde dos containers..."
$RUN "docker ps --format '{{.Names}}\t{{.Status}}' | grep -E 'pdns|dnsdist'"

# ── 6. Teste básico na :5353 ─────────────────────────────────────────────
step "Teste básico na porta 5353..."
sleep 5
result=$(dig @$TARGET -p 5353 results.com.br A +short +time=3 2>/dev/null | head -1)
if [[ -n "$result" ]]; then
  ok "results.com.br → $result na porta 5353"
else
  warn "Sem resposta na :5353 — verificar logs: docker logs pdns-auth"
fi

echo ""
echo -e "${GREEN}${BOLD}Stack DNS rodando em $TARGET:5353 (paralelo ao BIND)${RESET}"
echo ""
echo "Observação operacional:"
echo "  - pdns-auth usa backend LMDB local (volume pdns-auth-lmdb) — não depende"
echo "    mais do MariaDB em 10.10.2.99 nem da VPN para responder zonas."
echo ""
echo "Próximos passos:"
echo "  1. Popular zonas:  bash scripts/apply-zones-api.sh $TARGET"
echo "  2. Popular views:  bash scripts/apply-views-api.sh $TARGET"
echo "  3. Rodar validate.sh: NEW_DNS=$TARGET NEW_PORT=5353 bash scripts/validate.sh"
echo "  4. Após validação completa: bash scripts/cutover.sh (para o BIND e move para :53)"
echo "  5. Ajustar firewall: 10.10.2.51 → $TARGET"
