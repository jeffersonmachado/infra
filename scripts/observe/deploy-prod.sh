#!/usr/bin/env bash
# ─── deploy-prod.sh ────────────────────────────────────────────────────────────
# Deploy da stack R-Observe no servidor de produção (10.10.2.30) com acesso
# público via r-observe.results.com.br (SSL/TLS gerenciado pelo secure-httpd).
#
# Pré-requisitos:
#   - Acesso SSH root@10.10.2.30 configurado
#   - .env.observe criado a partir de .env.observe.example no servidor
#   - MySQL em 10.10.2.99 acessível com credenciais definidas em .env (local)
#
# Uso:
#   ./scripts/observe/deploy-prod.sh
#   ./scripts/observe/deploy-prod.sh --skip-vhost   # pula criação do vhost Apache
#   ./scripts/observe/deploy-prod.sh --skip-deploy  # só registra o vhost
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

# ── Configurações de produção ──────────────────────────────────────────────────
PROD_HOST="${PROD_HOST:-10.10.2.30}"
PROD_USER="${PROD_USER:-root}"
PROD_DIR="${PROD_DIR:-/opt/results/infra}"
PUBLIC_DOMAIN="${PUBLIC_DOMAIN:-r-observe.results.com.br}"
INTERNAL_PORT="${INTERNAL_PORT:-3080}"

# MySQL onde vive a tabela apache_vhosts (lido do .env local se existir)
MYSQL_HOST="${MYSQL_HOST:-10.10.2.99}"
MYSQL_PORT="${MYSQL_PORT:-3306}"
MYSQL_DATABASE="${MYSQL_DATABASE:-results}"
MYSQL_USER="${MYSQL_USER:-results}"
MYSQL_PASSWORD="${MYSQL_PASSWORD:-}"

SKIP_VHOST=false
SKIP_DEPLOY=false

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

step() { echo -e "\n${BOLD}${CYAN}▶ $*${RESET}"; }
ok()   { echo -e "  ${GREEN}✓${RESET} $*"; }
warn() { echo -e "  ${YELLOW}!${RESET} $*"; }
die()  { echo -e "${RED}ERRO:${RESET} $*" >&2; exit 1; }

while [[ $# -gt 0 ]]; do
  case "$1" in
    --skip-vhost)  SKIP_VHOST=true;  shift ;;
    --skip-deploy) SKIP_DEPLOY=true; shift ;;
    --host)        PROD_HOST="$2";   shift 2 ;;
    --domain)      PUBLIC_DOMAIN="$2"; shift 2 ;;
    --mysql-host)  MYSQL_HOST="$2";  shift 2 ;;
    --mysql-pass)  MYSQL_PASSWORD="$2"; shift 2 ;;
    --help|-h) grep '^#' "$0" | grep -v '!/usr/bin' | sed 's/^# *//'; exit 0 ;;
    *) die "Argumento desconhecido: $1" ;;
  esac
done

# Lê variáveis do .env local se existir
if [[ -f "${REPO_ROOT}/.env" ]]; then
  # shellcheck source=/dev/null
  set -a; source "${REPO_ROOT}/.env" 2>/dev/null || true; set +a
fi

echo -e "${BOLD}── R-Observe Deploy Produção ────────────────────────────────────${RESET}"
echo -e "  Servidor:  ${CYAN}${PROD_HOST}${RESET}"
echo -e "  Domínio:   ${CYAN}https://${PUBLIC_DOMAIN}${RESET}"
echo ""

# ── 1. Registra vhost no Apache (secure-httpd via MySQL) ──────────────────────
if [[ "${SKIP_VHOST}" == "false" ]]; then
  step "Registrando vhost no secure-httpd (MySQL ${MYSQL_HOST})..."

  if [[ -z "${MYSQL_PASSWORD}" ]]; then
    warn "MYSQL_PASSWORD não definido. Tente: export MYSQL_PASSWORD=... ou use --mysql-pass"
    warn "Pulando registro de vhost — faça manualmente:"
    cat <<SQL

  mysql -h ${MYSQL_HOST} -u ${MYSQL_USER} -p ${MYSQL_DATABASE} <<'EOF'
  INSERT INTO apache_vhosts (server_name, backend_scheme, backend_host, backend_port, backend_path, ssl_insecure, enabled)
  VALUES ('${PUBLIC_DOMAIN}', 'http', '${PROD_HOST}', ${INTERNAL_PORT}, '/', 0, 1)
  ON DUPLICATE KEY UPDATE
    backend_host = VALUES(backend_host),
    backend_port = VALUES(backend_port),
    enabled      = 1,
    updated_at   = NOW();
EOF

SQL
  else
    MYSQL_PWD="${MYSQL_PASSWORD}" mysql \
      --host="${MYSQL_HOST}" \
      --port="${MYSQL_PORT}" \
      --user="${MYSQL_USER}" \
      --database="${MYSQL_DATABASE}" \
      --execute="
        INSERT INTO apache_vhosts
          (server_name, backend_scheme, backend_host, backend_port, backend_path, ssl_insecure, enabled)
        VALUES
          ('${PUBLIC_DOMAIN}', 'http', '${PROD_HOST}', ${INTERNAL_PORT}, '/', 0, 1)
        ON DUPLICATE KEY UPDATE
          backend_host = VALUES(backend_host),
          backend_port = VALUES(backend_port),
          enabled      = 1,
          updated_at   = NOW();
      " 2>/dev/null && ok "Vhost '${PUBLIC_DOMAIN}' → ${PROD_HOST}:${INTERNAL_PORT} registrado" \
      || warn "Falha ao inserir no MySQL. Veja a query acima e execute manualmente."

    echo ""
    warn "O subdomain-sync atualiza o Apache a cada 15s."
    warn "O mod_md solicitará o cert Let's Encrypt automaticamente na primeira requisição HTTPS."
  fi
fi

# ── 2. Sincroniza repositório e faz deploy no servidor ────────────────────────
if [[ "${SKIP_DEPLOY}" == "false" ]]; then
  step "Sincronizando repositório em root@${PROD_HOST}:${PROD_DIR}..."

  ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 \
    "${PROD_USER}@${PROD_HOST}" "mkdir -p ${PROD_DIR}" || die "SSH falhou para ${PROD_HOST}"

  rsync -az --delete \
    --exclude='.env*' \
    --exclude='node_modules' \
    --exclude='.git' \
    --exclude='test-results' \
    --exclude='tests/playwright-report' \
    "${REPO_ROOT}/" "${PROD_USER}@${PROD_HOST}:${PROD_DIR}/"

  ok "Repositório sincronizado em ${PROD_HOST}:${PROD_DIR}"

  step "Verificando .env.observe no servidor..."
  if ! ssh -o StrictHostKeyChecking=no "${PROD_USER}@${PROD_HOST}" \
      "test -f ${PROD_DIR}/.env.observe"; then
    die ".env.observe não encontrado em ${PROD_HOST}:${PROD_DIR}/
    Crie-o a partir do exemplo:
      scp .env.observe.example ${PROD_USER}@${PROD_HOST}:${PROD_DIR}/.env.observe
      ssh ${PROD_USER}@${PROD_HOST} 'nano ${PROD_DIR}/.env.observe'  # edite senhas"
  fi
  ok ".env.observe presente"

  step "Executando deploy completo em ${PROD_HOST}..."
  ssh -o StrictHostKeyChecking=no "${PROD_USER}@${PROD_HOST}" \
    "cd ${PROD_DIR} && bash scripts/observe/deploy.sh --skip-discover" \
    || die "Deploy falhou no servidor remoto"
fi

# ── 3. Atualiza edge-sni se HAProxy estiver neste host ────────────────────────
EDGE_HOST="${EDGE_HOST:-}"
if [[ -n "${EDGE_HOST}" ]]; then
  step "Atualizando HAProxy em ${EDGE_HOST}..."
  rsync -az "${REPO_ROOT}/edge-sni/haproxy.cfg" \
    "${PROD_USER}@${EDGE_HOST}:/opt/results/infra/edge-sni/haproxy.cfg"
  ssh -o StrictHostKeyChecking=no "${PROD_USER}@${EDGE_HOST}" \
    "cd /opt/results/infra && docker compose -f docker-compose.edge-sni.yml restart edge-sni 2>/dev/null || true"
  ok "HAProxy recarregado"
fi

# ── 4. Smoke test remoto ──────────────────────────────────────────────────────
step "Verificando serviços em ${PROD_HOST}..."
sleep 5
HTTP_STATUS=$(curl -so /dev/null -w "%{http_code}" \
  --connect-timeout 5 \
  "http://${PROD_HOST}:${INTERNAL_PORT}/observe/api/health" 2>/dev/null || echo "000")

if [[ "${HTTP_STATUS}" == "200" ]]; then
  ok "API respondendo em http://${PROD_HOST}:${INTERNAL_PORT}/observe/api/health"
else
  warn "API não respondeu (HTTP ${HTTP_STATUS}). Verifique: ssh ${PROD_USER}@${PROD_HOST} docker ps"
fi

# ── Resumo ────────────────────────────────────────────────────────────────────
echo ""
echo -e "${BOLD}──────────────────────────────────────────────────────────────────${RESET}"
echo -e "${GREEN}${BOLD}Deploy de produção concluído!${RESET}"
echo ""
echo -e "  ${CYAN}Acesso público:${RESET}    https://${PUBLIC_DOMAIN}"
echo -e "  ${CYAN}Acesso direto:${RESET}     http://${PROD_HOST}:${INTERNAL_PORT}"
echo ""
echo -e "  ${CYAN}Interfaces:${RESET}"
echo -e "    /observe/settings  → Configuração da IA"
echo -e "    /observe/ai        → Dashboard de atividade"
echo -e "    /icinga/           → IcingaWeb2"
echo -e "    /grafana/          → Grafana"
echo ""
echo -e "  ${YELLOW}Nota:${RESET} O certificado SSL é emitido automaticamente pelo mod_md"
echo -e "        na primeira requisição HTTPS. Pode levar 1-2 min."
echo ""
echo -e "  Logs:   ssh ${PROD_USER}@${PROD_HOST} 'docker compose -f ${PROD_DIR}/docker-compose.observe.yml logs -f'"
echo -e "  Status: ssh ${PROD_USER}@${PROD_HOST} 'docker ps'"
