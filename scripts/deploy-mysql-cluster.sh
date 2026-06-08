#!/bin/bash
# ─── Deploy MySQL Cluster ──────────────────────────────────────────────────
# Faz deploy do ProxySQL + Slave no servidor remoto (mexico)
#
# Uso:
#   ./scripts/deploy-mysql-cluster.sh
#   ./scripts/deploy-mysql-cluster.sh 10.10.2.30  (target customizado)
# ───────────────────────────────────────────────────────────────────────────
set -euo pipefail

TARGET="${1:-10.10.2.30}"
REMOTE_DIR="/opt/results/infra"
SSH_OPTS="-o StrictHostKeyChecking=no"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

ok()   { echo -e "${GREEN}[OK]${NC} $1"; }
step() { echo -e "\n${CYAN}═══ $1 ═══${NC}"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
fail() { echo -e "${RED}[FAIL]${NC} $1"; exit 1; }

SSH_RUN() { sshpass -p "${SSH_PASSWORD:?SSH_PASSWORD obrigatória}" ssh ${SSH_OPTS} root@"${TARGET}" "$@"; }

echo "=== Deploy MySQL Cluster ==="
echo "Target: ${TARGET}"
echo ""

# ── 1. Verificar pré-requisitos ────────────────────────────────────────────
step "Verificando pré-requisitos"

if [ ! -f ".env.mysql-cluster" ]; then
    warn ".env.mysql-cluster não encontrado. Copiando de .env.mysql-cluster.example..."
    cp .env.mysql-cluster.example .env.mysql-cluster
    fail "Edite .env.mysql-cluster com as senhas e IPs reais antes de continuar."
fi

# Carrega env para validação
source <(grep -v '^#' .env.mysql-cluster | grep -v '^$')

if [ "${PROXYSQL_ADMIN_PASSWORD:-}" = "CHANGE_ME_ADMIN_PASSWORD" ]; then
    fail "PROXYSQL_ADMIN_PASSWORD ainda está com valor padrão em .env.mysql-cluster"
fi
if [ "${MYSQL_ROOT_PASSWORD:-}" = "CHANGE_ME_ROOT_PASSWORD" ]; then
    fail "MYSQL_ROOT_PASSWORD ainda está com valor padrão em .env.mysql-cluster"
fi
if [ "${MYSQL_REPL_PASSWORD:-}" = "CHANGE_ME_REPL_PASSWORD" ]; then
    fail "MYSQL_REPL_PASSWORD ainda está com valor padrão em .env.mysql-cluster"
fi

ok "Variáveis de ambiente OK"

# ── 2. Sincronizar arquivos ────────────────────────────────────────────────
step "Sincronizando arquivos para ${TARGET}"

sshpass -p "${SSH_PASSWORD}" rsync -avz --delete \
    -e "ssh ${SSH_OPTS}" \
    ./mysql-cluster/ \
    ./docker-compose.mysql-cluster.yml \
    ./.env.mysql-cluster \
    "root@${TARGET}:${REMOTE_DIR}/" \
    2>&1 | tail -5

ok "Arquivos sincronizados"

# ── 3. Build e deploy ──────────────────────────────────────────────────────
step "Build e deploy dos containers"

SSH_RUN "cd ${REMOTE_DIR} && docker compose -f docker-compose.mysql-cluster.yml --env-file .env.mysql-cluster build --pull 2>&1"
SSH_RUN "cd ${REMOTE_DIR} && docker compose -f docker-compose.mysql-cluster.yml --env-file .env.mysql-cluster up -d 2>&1"

ok "Containers iniciados"

# ── 4. Aguardar health ─────────────────────────────────────────────────────
step "Aguardando containers ficarem saudáveis (até 60s)"

for i in $(seq 1 30); do
    PROXYSQL_STATUS=$(SSH_RUN "docker inspect --format='{{.State.Health.Status}}' mysql-proxysql 2>/dev/null" || echo "unknown")
    SLAVE_STATUS=$(SSH_RUN "docker inspect --format='{{.State.Health.Status}}' mysql-slave 2>/dev/null" || echo "unknown")
    
    echo "  ProxySQL: ${PROXYSQL_STATUS} | Slave: ${SLAVE_STATUS}"
    
    if [ "${PROXYSQL_STATUS}" = "healthy" ] && [ "${SLAVE_STATUS}" = "healthy" ]; then
        ok "Todos os containers saudáveis"
        break
    fi
    if [ "$i" -eq 30 ]; then
        warn "Timeout aguardando containers. Verifique os logs:"
        echo "  docker logs mysql-proxysql"
        echo "  docker logs mysql-slave"
    fi
    sleep 2
done

# ── 5. Smoke test ──────────────────────────────────────────────────────────
step "Smoke test"

echo "Testando conexão r/w (porta ${PROXYSQL_RW_PORT:-6033})..."
SSH_RUN "docker exec mysql-proxysql mysql -u resultsdba -p'${MYSQL_APP_PASSWORD:-}' -h 127.0.0.1 -P 6033 -e 'SELECT VERSION(), @@server_id, @@read_only;' 2>&1" || warn "Falha no teste r/w"

echo "Testando conexão r/o (porta ${PROXYSQL_RO_PORT:-6034})..."
SSH_RUN "docker exec mysql-proxysql mysql -u resultsdba -p'${MYSQL_APP_PASSWORD:-}' -h 127.0.0.1 -P 6034 -e 'SELECT VERSION(), @@server_id, @@read_only;' 2>&1" || warn "Falha no teste r/o (slave pode ainda estar sincronizando)"

# ── 6. Status da replicação ────────────────────────────────────────────────
step "Status da replicação"

SSH_RUN "docker exec mysql-slave mysql -u root -p'${MYSQL_ROOT_PASSWORD}' -e 'SHOW SLAVE STATUS\G'" 2>&1 | grep -E "Slave_IO_Running|Slave_SQL_Running|Seconds_Behind_Master|Last_Error"

echo ""
echo "=== Deploy concluído ==="
echo ""
echo "Conexões disponíveis:"
echo "  r/w (master):  ${TARGET}:${PROXYSQL_RW_PORT:-6033}"
echo "  r/o (slave):   ${TARGET}:${PROXYSQL_RO_PORT:-6034}"
echo "  admin:         127.0.0.1:${PROXYSQL_ADMIN_PORT:-6032}"
echo ""
echo "Para migrar aplicações, altere MYSQL_HOST de 10.10.2.99:3306 para:"
echo "  ${TARGET}:${PROXYSQL_RW_PORT:-6033}"
