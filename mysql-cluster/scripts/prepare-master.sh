#!/bin/bash
# ─── Prepara Master para Replicação ─────────────────────────────────────────
# Executar NO master atual (10.10.2.79) ANTES de iniciar o slave.
#
# Este script:
#   1. Verifica se binlog está ativo
#   2. Habilita binlog e server_id se necessário
#   3. Cria usuário de replicação
#   4. Gera dump para bootstrap do slave
#   5. Mostra posição do binlog para CHANGE MASTER
#
# Uso:
#   ./prepare-master.sh
#   ./prepare-master.sh --dry-run  (apenas verifica, sem alterar)
# ───────────────────────────────────────────────────────────────────────────
set -euo pipefail

DRY_RUN=false
[[ "${1:-}" == "--dry-run" ]] && DRY_RUN=true

MASTER_HOST="${MYSQL_MASTER_HOST:-10.10.2.79}"
MASTER_PORT="${MYSQL_MASTER_PORT:-3306}"
MYSQL_ROOT_PASSWORD="${MYSQL_MASTER_ROOT_PASSWORD:?Defina MYSQL_MASTER_ROOT_PASSWORD}"
REPL_USER="${MYSQL_REPL_USER:-repl}"
REPL_PASSWORD="${MYSQL_REPL_PASSWORD:?Defina MYSQL_REPL_PASSWORD}"
DUMP_DIR="${DUMP_DIR:-./dumps}"
DUMP_FILE="${DUMP_DIR}/master-dump-$(date +%Y%m%d-%H%M%S).sql.gz"

MYSQL_CMD="mysql -u root -p${MYSQL_ROOT_PASSWORD} -h ${MASTER_HOST} -P ${MASTER_PORT}"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

run_sql() {
    if [ "$DRY_RUN" = true ]; then
        echo -e "${YELLOW}[DRY-RUN]${NC} Executaria: $1"
    else
        ${MYSQL_CMD} -e "$1" 2>&1
    fi
}

echo "=== Preparação do Master para Replicação ==="
echo "Master: ${MASTER_HOST}:${MASTER_PORT}"
echo "Modo: $([ "$DRY_RUN" = true ] && echo 'DRY-RUN (sem alterações)' || echo 'REAL')"
echo ""

# 1. Verificar binlog
echo "--- Verificando binlog ---"
LOG_BIN=$(${MYSQL_CMD} -e "SHOW VARIABLES LIKE 'log_bin'" 2>/dev/null | tail -1 | awk '{print $2}')
SERVER_ID=$(${MYSQL_CMD} -e "SHOW VARIABLES LIKE 'server_id'" 2>/dev/null | tail -1 | awk '{print $2}')

echo "log_bin   = ${LOG_BIN}"
echo "server_id = ${SERVER_ID}"

if [ "${LOG_BIN}" = "OFF" ] || [ "${SERVER_ID}" = "0" ]; then
    echo ""
    echo -e "${YELLOW}[AÇÃO NECESSÁRIA]${NC} Binlog precisa ser ativado no master."
    echo ""
    echo "  Opção A — Dinâmico (sem restart, mas não sobrevive a reboot):"
    echo "    SET GLOBAL log_bin = ON;"
    echo "    SET GLOBAL server_id = 1;"
    echo ""
    echo "  Opção B — Permanente (requer restart do MariaDB):"
    echo "    Adicione ao /etc/my.cnf.d/server.cnf ou /banco/mysql2/my.cnf:"
    echo "      [mysqld]"
    echo "      server_id = 1"
    echo "      log_bin = /banco/mysql2/mysql-bin"
    echo "      binlog_format = ROW"
    echo "      expire_logs_days = 7"
    echo "    Depois: service mysql restart"
    echo ""

    if [ "$DRY_RUN" = false ]; then
        echo -n "Tentar ativação dinâmica agora? (s/N): "
        read -r answer
        if [ "${answer}" = "s" ] || [ "${answer}" = "S" ]; then
            run_sql "SET GLOBAL log_bin = ON; SET GLOBAL server_id = 1;"
            echo "Binlog ativado dinamicamente. ATENÇÃO: não sobrevive a restart."
        fi
    fi
else
    echo -e "${GREEN}[OK]${NC} Binlog já está ativo."
fi

# 2. Criar usuário de replicação
echo ""
echo "--- Usuário de replicação ---"
REPL_EXISTS=$(${MYSQL_CMD} -e "SELECT COUNT(*) FROM mysql.user WHERE User='${REPL_USER}'" 2>/dev/null | tail -1)
if [ "${REPL_EXISTS}" = "0" ]; then
    echo "Criando usuário '${REPL_USER}'..."
    run_sql "CREATE USER '${REPL_USER}'@'%' IDENTIFIED BY '${REPL_PASSWORD}';"
    run_sql "GRANT REPLICATION SLAVE, REPLICATION CLIENT ON *.* TO '${REPL_USER}'@'%';"
    run_sql "FLUSH PRIVILEGES;"
    echo -e "${GREEN}[OK]${NC} Usuário de replicação criado."
else
    echo -e "${GREEN}[OK]${NC} Usuário '${REPL_USER}' já existe."
    run_sql "GRANT REPLICATION SLAVE, REPLICATION CLIENT ON *.* TO '${REPL_USER}'@'%';"
fi

# 3. Mostrar posição do binlog
echo ""
echo "--- Posição do binlog ---"
${MYSQL_CMD} -e "SHOW MASTER STATUS;" 2>&1

# 4. Gerar dump
echo ""
echo "--- Dump para bootstrap do slave ---"
mkdir -p "${DUMP_DIR}"

echo "Gerando dump em ${DUMP_FILE}..."
if [ "$DRY_RUN" = false ]; then
    mysqldump -u root -p"${MYSQL_ROOT_PASSWORD}" \
        -h "${MASTER_HOST}" -P "${MASTER_PORT}" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --all-databases \
        --master-data=2 \
        | gzip > "${DUMP_FILE}"

    DUMP_SIZE=$(du -h "${DUMP_FILE}" | awk '{print $1}')
    echo -e "${GREEN}[OK]${NC} Dump gerado: ${DUMP_FILE} (${DUMP_SIZE})"
    echo ""
    echo "Para usar este dump no slave:"
    echo "  cp ${DUMP_FILE} /tmp/master-dump.sql.gz"
    echo "  # O init-replication.sh detectará e importará automaticamente"
else
    echo -e "${YELLOW}[DRY-RUN]${NC} Pulando geração do dump."
fi

echo ""
echo "=== Preparação concluída ==="
echo ""
echo "Próximos passos:"
echo "1. Copie o dump para o servidor do slave (se gerado)"
echo "2. Inicie o slave: docker compose -f docker-compose.mysql-cluster.yml up -d mysql-slave"
echo "3. Inicie o ProxySQL: docker compose -f docker-compose.mysql-cluster.yml up -d mysql-proxysql"
echo "4. Verifique: ./mysql-cluster/scripts/health-check.sh"
