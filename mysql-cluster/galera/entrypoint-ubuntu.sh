#!/bin/bash
# ─── Galera Entrypoint (Ubuntu) ────────────────────────────────────────────
set -eu

DATA_DIR="${DATA_DIR:-/var/lib/mysql}"
NODE_IP="${NODE_IP:-127.0.0.1}"
NODE_NAME="${NODE_NAME:-node}"
NODE_ID="${NODE_ID:-1}"
CLUSTER_NAME="${CLUSTER_NAME:-mysql}"
CLUSTER_ADDRESS="${CLUSTER_ADDRESS:-gcomm://}"
SST_USER="${SST_USER:-galera}"
SST_PASSWORD="${SST_PASSWORD:-}"
PORT="${MYSQL_PORT:-3306}"
ROOT_PASSWORD="${MARIADB_ROOT_PASSWORD:-${MYSQL_ROOT_PASSWORD:-}}"

echo "[galera] Nó: ${NODE_NAME} (${NODE_IP}:${PORT}), ID: ${NODE_ID}"

# Inicializa datadir se vazio
if [ ! -d "${DATA_DIR}/mysql" ]; then
    echo "[galera] Inicializando datadir..."
    mariadb-install-db --user=mysql --datadir="${DATA_DIR}" --auth-root-authentication-method=normal
    
    # Inicia temporário para setar senha
    mariadbd --user=mysql --datadir="${DATA_DIR}" --socket=/tmp/mysql-init.sock --skip-networking &
    PID=$!
    
    for i in $(seq 1 30); do
        if mysql -u root --socket=/tmp/mysql-init.sock -e "SELECT 1" 2>/dev/null; then
            break
        fi
        sleep 1
    done
    
    if [ -n "${ROOT_PASSWORD}" ]; then
        mysql -u root --socket=/tmp/mysql-init.sock -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '${ROOT_PASSWORD}'; FLUSH PRIVILEGES;"
    fi
    
    mysqladmin -u root --socket=/tmp/mysql-init.sock shutdown 2>/dev/null || true
    wait ${PID} 2>/dev/null || true
    echo "[galera] Datadir inicializado."
fi

# Argumentos comuns
ARGS=(
    --user=mysql
    --datadir="${DATA_DIR}"
    --port="${PORT}"
    --bind-address="${NODE_IP}"
    --wsrep-node-address="${NODE_IP}"
    --wsrep-node-name="${NODE_NAME}"
    --wsrep-cluster-name="${CLUSTER_NAME}"
    --wsrep-cluster-address="${CLUSTER_ADDRESS}"
    --wsrep-sst-auth="${SST_USER}:${SST_PASSWORD}"
    --wsrep-provider=/usr/lib/galera/libgalera_smm.so
    --wsrep_sst_method=mariabackup
    --log-bin
    --binlog-format=ROW
    --server-id="${NODE_ID}"
)

if [ "${BOOTSTRAP:-false}" = "true" ]; then
    echo "[galera] ⚡ BOOTSTRAP: iniciando novo cluster..."
    exec mariadbd --wsrep-new-cluster "${ARGS[@]}"
else
    echo "[galera] Juntando ao cluster ${CLUSTER_ADDRESS}..."
    exec mariadbd "${ARGS[@]}"
fi
