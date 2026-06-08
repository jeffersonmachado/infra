#!/bin/bash
# ─── Inicializa Replicação no Slave ────────────────────────────────────────
# Executado pelo entrypoint do slave na primeira inicialização
#
# Pré-requisitos (devem estar no master ANTES de rodar):
#   1. Binlog ativo: log_bin=ON, server_id=1
#   2. Usuário de replicação criado:
#      CREATE USER 'repl'@'%' IDENTIFIED BY '<senha>';
#      GRANT REPLICATION SLAVE, REPLICATION CLIENT ON *.* TO 'repl'@'%';
#   3. Dump inicial copiado para /tmp/master-dump.sql.gz (opcional)
#
# Se o dump existir, importa e replica a partir da posição do dump.
# Senão, replica a partir da posição atual do master (sem dump).
# ───────────────────────────────────────────────────────────────────────────
set -eu

MASTER_HOST="${MYSQL_MASTER_HOST:?MYSQL_MASTER_HOST obrigatório}"
MASTER_PORT="${MYSQL_MASTER_PORT:-3306}"
REPL_USER="${MYSQL_REPL_USER:-repl}"
REPL_PASSWORD="${MYSQL_REPL_PASSWORD:?MYSQL_REPL_PASSWORD obrigatório}"
ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:?MYSQL_ROOT_PASSWORD obrigatório}"
DUMP_FILE="/tmp/master-dump.sql.gz"

MYSQL_CMD="mysql -u root -p${ROOT_PASSWORD}"
MYSQL_MASTER="mysql -h ${MASTER_HOST} -P ${MASTER_PORT} -u ${REPL_USER} -p${REPL_PASSWORD}"

echo "[init-replication] Verificando conectividade com master ${MASTER_HOST}:${MASTER_PORT}..."

# Testa conexão com master
for i in $(seq 1 30); do
    if ${MYSQL_MASTER} -e "SELECT 1" >/dev/null 2>&1; then
        echo "[init-replication] Conectado ao master."
        break
    fi
    if [ "$i" -eq 30 ]; then
        echo "[init-replication] ERRO: Não foi possível conectar ao master ${MASTER_HOST}:${MASTER_PORT}" >&2
        echo "[init-replication] Verifique: rede, firewall, usuário de replicação no master" >&2
        exit 1
    fi
    echo "[init-replication] Tentativa $i/30 — master não respondeu, aguardando..."
    sleep 5
done

# Verifica se o master tem binlog ativo
MASTER_LOG_FILE=$(${MYSQL_MASTER} -e "SHOW MASTER STATUS\G" 2>/dev/null | grep -E '^\s*File:' | awk '{print $2}')
MASTER_LOG_POS=$(${MYSQL_MASTER} -e "SHOW MASTER STATUS\G" 2>/dev/null | grep -E '^\s*Position:' | awk '{print $2}')

if [ -z "${MASTER_LOG_FILE}" ] || [ -z "${MASTER_LOG_POS}" ]; then
    echo "[init-replication] ERRO: Master não tem binlog ativo." >&2
    echo "[init-replication] Execute no master: SET GLOBAL log_bin=ON; SET GLOBAL server_id=1;" >&2
    echo "[init-replication] Ou configure no my.cnf e reinicie o MariaDB." >&2
    exit 1
fi

echo "[init-replication] Master binlog: ${MASTER_LOG_FILE}:${MASTER_LOG_POS}"

# Importa dump se disponível
if [ -f "${DUMP_FILE}" ]; then
    echo "[init-replication] Importando dump ${DUMP_FILE}..."
    zcat "${DUMP_FILE}" | ${MYSQL_CMD} 2>&1
    echo "[init-replication] Dump importado."
else
    echo "[init-replication] AVISO: Nenhum dump encontrado em ${DUMP_FILE}."
    echo "[init-replication] A replicação iniciará sem dados históricos."
    echo "[init-replication] Para importar dados, coloque o dump em ${DUMP_FILE} e recrie o container."
fi

# Configura e inicia replicação
echo "[init-replication] Configurando replicação..."

${MYSQL_CMD} << EOF
CHANGE MASTER TO
    MASTER_HOST='${MASTER_HOST}',
    MASTER_PORT=${MASTER_PORT},
    MASTER_USER='${REPL_USER}',
    MASTER_PASSWORD='${REPL_PASSWORD}',
    MASTER_LOG_FILE='${MASTER_LOG_FILE}',
    MASTER_LOG_POS=${MASTER_LOG_POS},
    MASTER_CONNECT_RETRY=10,
    MASTER_USE_GTID=no;

START SLAVE;
EOF

# Aguarda slave iniciar
sleep 3

# Verifica status
echo "[init-replication] Status da replicação:"
${MYSQL_CMD} -e "SHOW SLAVE STATUS\G" 2>&1 | grep -E "Slave_IO_Running|Slave_SQL_Running|Last_IO_Error|Last_SQL_Error|Seconds_Behind_Master"

SLAVE_IO=$(${MYSQL_CMD} -e "SHOW SLAVE STATUS\G" 2>/dev/null | grep -E '^\s*Slave_IO_Running:' | awk '{print $2}')
SLAVE_SQL=$(${MYSQL_CMD} -e "SHOW SLAVE STATUS\G" 2>/dev/null | grep -E '^\s*Slave_SQL_Running:' | awk '{print $2}')

if [ "${SLAVE_IO}" = "Yes" ] && [ "${SLAVE_SQL}" = "Yes" ]; then
    echo "[init-replication] SUCESSO: Replicação rodando (IO: ${SLAVE_IO}, SQL: ${SLAVE_SQL})"
else
    echo "[init-replication] ERRO: Replicação com problema (IO: ${SLAVE_IO}, SQL: ${SLAVE_SQL})"
    echo "[init-replication] Verifique SHOW SLAVE STATUS\G para detalhes."
    # Não sai com erro — deixa o container rodando
fi
