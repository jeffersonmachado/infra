#!/bin/bash
# ─── Galera Entrypoint ─────────────────────────────────────────────────────
# 1. Renderiza galera.cnf com variáveis de ambiente
# 2. Se BOOTSTRAP=true, inicia com --wsrep-new-cluster
# 3. Senão, inicia normalmente (junta ao cluster)
# ───────────────────────────────────────────────────────────────────────────
set -eu

NODE_IP="${NODE_IP:-127.0.0.1}"
NODE_NAME="${NODE_NAME:-node}"
NODE_ID="${NODE_ID:-1}"
CLUSTER_NAME="${CLUSTER_NAME:-mysql}"
CLUSTER_ADDRESS="${CLUSTER_ADDRESS:-gcomm://}"
SST_USER="${SST_USER:-galera}"
SST_PASSWORD="${SST_PASSWORD:-}"
PORT="${MYSQL_PORT:-3306}"

echo "[galera] Nó: ${NODE_NAME} (${NODE_IP}:${PORT}), ID: ${NODE_ID}"

if [ -f /etc/mysql/conf.d/galera.cnf.template ]; then
    apt-get update -qq && apt-get install -y -qq gettext-base 2>/dev/null || true
    envsubst < /etc/mysql/conf.d/galera.cnf.template > /etc/mysql/conf.d/galera.cnf
    echo "[galera] Config renderizada."
fi

if [ "${BOOTSTRAP:-false}" = "true" ]; then
    echo "[galera] BOOTSTRAP: iniciando novo cluster..."
    exec docker-entrypoint.sh mariadbd \
        --wsrep-new-cluster \
        --port="${PORT}" \
        --bind-address="${NODE_IP}" \
        --wsrep-node-address="${NODE_IP}" \
        --wsrep-node-name="${NODE_NAME}" \
        --wsrep-cluster-name="${CLUSTER_NAME}" \
        --wsrep-cluster-address="${CLUSTER_ADDRESS}" \
        --wsrep-sst-auth="${SST_USER}:${SST_PASSWORD}"
else
    echo "[galera] Juntando ao cluster ${CLUSTER_ADDRESS}..."
    exec docker-entrypoint.sh mariadbd \
        --port="${PORT}" \
        --bind-address="${NODE_IP}" \
        --wsrep-node-address="${NODE_IP}" \
        --wsrep-node-name="${NODE_NAME}" \
        --wsrep-cluster-name="${CLUSTER_NAME}" \
        --wsrep-cluster-address="${CLUSTER_ADDRESS}" \
        --wsrep-sst-auth="${SST_USER}:${SST_PASSWORD}"
fi
