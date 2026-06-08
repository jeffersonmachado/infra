#!/bin/bash
# ─── Init Slave ────────────────────────────────────────────────────────────
# Executado na primeira inicialização do slave
# Configura e inicia replicação a partir do master
# ───────────────────────────────────────────────────────────────────────────
set -eu

echo "[init-slave] Aguardando master ${MASTER_HOST}:${MASTER_PORT}..."

for i in $(seq 1 120); do
    if mysqladmin ping -h "${MASTER_HOST}" -P "${MASTER_PORT}" -u root -p"${MYSQL_ROOT_PASSWORD}" --silent 2>/dev/null; then
        echo "[init-slave] Master acessível."
        break
    fi
    [ "$i" -eq 120 ] && { echo "[init-slave] ERRO: master não respondeu"; exit 1; }
    sleep 2
done

# Obtém posição do binlog do master
MASTER_LOG_FILE=$(mysql -h "${MASTER_HOST}" -P "${MASTER_PORT}" -u root -p"${MYSQL_ROOT_PASSWORD}" -e "SHOW MASTER STATUS\G" 2>/dev/null | grep -E '^\s*File:' | awk '{print $2}')
MASTER_LOG_POS=$(mysql -h "${MASTER_HOST}" -P "${MASTER_PORT}" -u root -p"${MYSQL_ROOT_PASSWORD}" -e "SHOW MASTER STATUS\G" 2>/dev/null | grep -E '^\s*Position:' | awk '{print $2}')

if [ -z "${MASTER_LOG_FILE}" ]; then
    echo "[init-slave] ERRO: binlog não encontrado no master"
    exit 1
fi

echo "[init-slave] Master binlog: ${MASTER_LOG_FILE}:${MASTER_LOG_POS}"

# Configura replicação (sem dump - espera-se que o dump já tenha sido importado ou
# que o slave esteja vazio e sincronize do zero)
mysql -u root -p"${MYSQL_ROOT_PASSWORD}" << EOF
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

sleep 3

SLAVE_IO=$(mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "SHOW SLAVE STATUS\G" 2>/dev/null | grep -E '^\s*Slave_IO_Running:' | awk '{print $2}')
SLAVE_SQL=$(mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "SHOW SLAVE STATUS\G" 2>/dev/null | grep -E '^\s*Slave_SQL_Running:' | awk '{print $2}')

if [ "${SLAVE_IO}" = "Yes" ] && [ "${SLAVE_SQL}" = "Yes" ]; then
    echo "[init-slave] ✅ Replicação iniciada (IO: ${SLAVE_IO}, SQL: ${SLAVE_SQL})"
else
    echo "[init-slave] ⚠️ Replicação com problema (IO: ${SLAVE_IO}, SQL: ${SLAVE_SQL})"
    echo "[init-slave] Execute: SHOW SLAVE STATUS\G para detalhes"
fi
