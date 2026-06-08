#!/bin/bash
# ─── ProxySQL Entrypoint Wrapper ────────────────────────────────────────────
# 1. Inicia o ProxySQL normalmente via /entrypoint.sh (imagem oficial)
# 2. Aguarda admin port (6032) ficar disponível
# 3. Injeta bootstrap.sql com envsubst
# 4. Mantém o processo rodando
# ───────────────────────────────────────────────────────────────────────────
set -eu

BOOTSTRAP_FILE="/bootstrap.sql"
ADMIN_PORT="${PROXYSQL_ADMIN_PORT:-6032}"
MAX_RETRIES=30
RETRY_INTERVAL=2

echo "[proxySQL-entrypoint] Iniciando ProxySQL..."
/entrypoint.sh proxysql -f -c /etc/proxysql.cnf &

PROXYSQL_PID=$!

echo "[proxySQL-entrypoint] Aguardando porta admin ${ADMIN_PORT}..."

for i in $(seq 1 ${MAX_RETRIES}); do
    if mysql -u "${PROXYSQL_ADMIN_USER:-admin}" -p"${PROXYSQL_ADMIN_PASSWORD}" \
       -h 127.0.0.1 -P "${ADMIN_PORT}" -e "SELECT 1" >/dev/null 2>&1; then
        echo "[proxySQL-entrypoint] ProxySQL admin port disponível (tentativa $i)"
        break
    fi
    if [ "$i" -eq "${MAX_RETRIES}" ]; then
        echo "[proxySQL-entrypoint] ERRO: ProxySQL não iniciou após $((MAX_RETRIES * RETRY_INTERVAL))s" >&2
        exit 1
    fi
    sleep "${RETRY_INTERVAL}"
done

# Substitui variáveis de ambiente no bootstrap.sql
if [ -f "${BOOTSTRAP_FILE}" ]; then
    echo "[proxySQL-entrypoint] Aplicando bootstrap.sql..."
    envsubst < "${BOOTSTRAP_FILE}" | mysql -u "${PROXYSQL_ADMIN_USER:-admin}" \
        -p"${PROXYSQL_ADMIN_PASSWORD}" -h 127.0.0.1 -P "${ADMIN_PORT}" 2>&1
    echo "[proxySQL-entrypoint] Bootstrap concluído."
else
    echo "[proxySQL-entrypoint] AVISO: ${BOOTSTRAP_FILE} não encontrado, pulando bootstrap."
fi

echo "[proxySQL-entrypoint] ProxySQL pronto."

# Mantém o container rodando com o processo principal
wait ${PROXYSQL_PID}
