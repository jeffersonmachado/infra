#!/bin/bash
# ─── Bootstrap ProxySQL ────────────────────────────────────────────────────
# Executar no HOST (mexico) após mysql-proxysql estar rodando.
# Configura: servers, users, query rules.
#
# Uso:
#   source .env.mysql-cluster
#   ./mysql-cluster/scripts/bootstrap-proxysql.sh
# ───────────────────────────────────────────────────────────────────────────
set -euo pipefail

ADMIN_USER="${PROXYSQL_ADMIN_USER:-admin}"
ADMIN_PASS="${PROXYSQL_ADMIN_PASSWORD:?PROXYSQL_ADMIN_PASSWORD}"
ADMIN_PORT="${PROXYSQL_ADMIN_PORT:-6032}"
MASTER_HOST="${MYSQL_MASTER_HOST:?MYSQL_MASTER_HOST}"
MASTER_PORT="${MYSQL_MASTER_PORT:-3306}"
SLAVE_HOST="${MYSQL_SLAVE_HOST:-mysql-slave}"
SLAVE_PORT="${MYSQL_SLAVE_PORT:-3306}"
APP_USER="${MYSQL_APP_USER:-resultsdba}"
APP_PASS="${MYSQL_APP_PASSWORD:?MYSQL_APP_PASSWORD}"
ROUNDCUBE_USER="${ROUNDCUBE_DB_USER:-roundcube}"
ROUNDCUBE_PASS="${ROUNDCUBE_DB_PASSWORD:-}"
REPL_USER="${MYSQL_REPL_USER:-repl}"
REPL_PASS="${MYSQL_REPL_PASSWORD:-}"

echo "[bootstrap] Conectando ao ProxySQL admin (${ADMIN_PORT})..."

MYSQL_ADMIN="docker run --rm --network mysql-cluster alpine:3.19 mysql -u ${ADMIN_USER} -p${ADMIN_PASS} -h mysql-proxysql -P 6032"

# Aguarda ProxySQL
for i in $(seq 1 20); do
    if ${MYSQL_ADMIN} -e "SELECT 1" >/dev/null 2>&1; then
        echo "[bootstrap] ProxySQL acessível."
        break
    fi
    [ "$i" -eq 20 ] && { echo "ERRO: ProxySQL não respondeu"; exit 1; }
    sleep 2
done

echo "[bootstrap] Configurando servidores backend..."

${MYSQL_ADMIN} << EOF
-- Master (hostgroup 0)
INSERT INTO mysql_servers (hostgroup_id, hostname, port, weight, max_connections, comment)
VALUES (0, '${MASTER_HOST}', ${MASTER_PORT}, 1, 100, 'Master srvmysql0');

-- Slave (hostgroup 1)
INSERT INTO mysql_servers (hostgroup_id, hostname, port, weight, max_connections, comment)
VALUES (1, '${SLAVE_HOST}', ${SLAVE_PORT}, 1, 50, 'Slave container');

LOAD MYSQL SERVERS TO RUNTIME;
SAVE MYSQL SERVERS TO DISK;
EOF

echo "[bootstrap] Configurando usuários..."

${MYSQL_ADMIN} << EOF
INSERT INTO mysql_users (username, password, default_hostgroup, active)
VALUES ('${APP_USER}', '${APP_PASS}', 0, 1);

INSERT INTO mysql_users (username, password, default_hostgroup, active)
VALUES ('${ROUNDCUBE_USER}', '${ROUNDCUBE_PASS}', 0, 1);

LOAD MYSQL USERS TO RUNTIME;
SAVE MYSQL USERS TO DISK;
EOF

echo "[bootstrap] Configurando regras de r/w split..."

${MYSQL_ADMIN} << EOF
-- SELECT FOR UPDATE → master
INSERT INTO mysql_query_rules (rule_id, active, match_pattern, destination_hostgroup, apply)
VALUES (1, 1, 'SELECT.*FOR UPDATE', 0, 1);

-- Demais SELECTs → slave
INSERT INTO mysql_query_rules (rule_id, active, match_pattern, destination_hostgroup, apply)
VALUES (10, 1, '^SELECT ', 1, 1);

-- Tudo o resto (INSERT, UPDATE, DELETE, DDL) → master
INSERT INTO mysql_query_rules (rule_id, active, match_pattern, destination_hostgroup, apply)
VALUES (100, 1, '.*', 0, 1);

LOAD MYSQL QUERY RULES TO RUNTIME;
SAVE MYSQL QUERY RULES TO DISK;
EOF

echo "[bootstrap] Verificando configuração..."

${MYSQL_ADMIN} -e "
SELECT hostgroup_id, hostname, port, status FROM stats.stats_mysql_connection_pool;
SELECT rule_id, match_pattern, destination_hostgroup FROM mysql_query_rules ORDER BY rule_id;
"

echo "[bootstrap] ProxySQL configurado com sucesso."
echo "  r/w: $(hostname -I | awk '{print $1}'):${PROXYSQL_RW_PORT:-6033}"
echo "  r/o: $(hostname -I | awk '{print $1}'):${PROXYSQL_RO_PORT:-6034}"
echo "  admin: 127.0.0.1:${ADMIN_PORT}"
