#!/bin/bash
# ─── Health Check MySQL via ProxySQL ────────────────────────────────────────
# Uso:
#   ./health-check.sh
#   ./health-check.sh --verbose
#
# Verifica:
#   1. ProxySQL admin port (6032)
#   2. Backend servers ping status
#   3. Slave replication lag
#   4. Conexão real via ProxySQL (6033 r/w, 6034 r/o)
# ───────────────────────────────────────────────────────────────────────────
set -euo pipefail

VERBOSE=false
PROXYSQL_HOST="${PROXYSQL_HOST:-127.0.0.1}"
ADMIN_PORT="${PROXYSQL_ADMIN_PORT:-6032}"
RW_PORT="${PROXYSQL_RW_PORT:-6033}"
RO_PORT="${PROXYSQL_RO_PORT:-6034}"
ADMIN_USER="${PROXYSQL_ADMIN_USER:-admin}"
ADMIN_PASSWORD="${PROXYSQL_ADMIN_PASSWORD:-}"
APP_USER="${MYSQL_APP_USER:-resultsdba}"
APP_PASSWORD="${MYSQL_APP_PASSWORD:-}"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

pass() { echo -e "${GREEN}[OK]${NC} $1"; }
fail() { echo -e "${RED}[FAIL]${NC} $1"; exit 1; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
info() { [ "$VERBOSE" = true ] && echo "  $1"; }

[[ "${1:-}" == "--verbose" ]] && VERBOSE=true

MYSQL_ADMIN="mysql -u ${ADMIN_USER} -p${ADMIN_PASSWORD} -h ${PROXYSQL_HOST} -P ${ADMIN_PORT}"

echo "=== MySQL Cluster Health Check ==="
echo "ProxySQL: ${PROXYSQL_HOST}:${ADMIN_PORT}"
echo ""

# 1. Admin port
echo -n "ProxySQL admin port... "
if ${MYSQL_ADMIN} -e "SELECT 1" >/dev/null 2>&1; then
    pass "acessível"
else
    fail "não acessível em ${PROXYSQL_HOST}:${ADMIN_PORT}"
fi

# 2. Runtime status
echo ""
echo "--- Backend Servers ---"
${MYSQL_ADMIN} -e "
SELECT
    hostgroup_id,
    hostname,
    port,
    status,
    ConnUsed,
    ConnFree,
    Latency_us,
    Queries,
    Bytes_data_sent,
    Bytes_data_recv
FROM stats.stats_mysql_connection_pool
ORDER BY hostgroup_id, hostname;
" 2>&1 | while IFS= read -r line; do
    if echo "$line" | grep -q "ONLINE"; then
        echo -e "  ${GREEN}$line${NC}"
    elif echo "$line" | grep -q "SHUNNED\|OFFLINE"; then
        echo -e "  ${RED}$line${NC}"
    else
        echo "  $line"
    fi
done

# 3. Ping status
echo ""
echo "--- Ping Monitor ---"
${MYSQL_ADMIN} -e "
SELECT
    hostname,
    port,
    CASE ping_error
        WHEN '' THEN 'OK'
        ELSE ping_error
    END AS ping_status,
    ping_success_time_us
FROM monitor.mysql_server_ping_log
WHERE time_start_us > UNIX_TIMESTAMP(NOW() - INTERVAL 5 MINUTE) * 1000000
ORDER BY time_start_us DESC
LIMIT 5;
" 2>&1

# 4. Teste conexão r/w
echo ""
echo -n "Conexão r/w (porta ${RW_PORT})... "
RW_COUNT=$(mysql -u "${APP_USER}" -p"${APP_PASSWORD}" -h "${PROXYSQL_HOST}" -P "${RW_PORT}" -e "SELECT COUNT(*) AS cnt FROM information_schema.tables" 2>/dev/null | tail -1)
if [ -n "${RW_COUNT}" ]; then
    pass "${RW_COUNT} tabelas visíveis"
else
    warn "não foi possível conectar para r/w"
fi

# 5. Teste conexão r/o
echo -n "Conexão r/o (porta ${RO_PORT})... "
RO_COUNT=$(mysql -u "${APP_USER}" -p"${APP_PASSWORD}" -h "${PROXYSQL_HOST}" -P "${RO_PORT}" -e "SELECT COUNT(*) AS cnt FROM information_schema.tables" 2>/dev/null | tail -1)
if [ -n "${RO_COUNT}" ]; then
    pass "${RO_COUNT} tabelas visíveis"
else
    warn "não foi possível conectar para r/o (slave offline?)"
fi

echo ""
echo "Health check concluído."
