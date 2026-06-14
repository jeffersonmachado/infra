-- ─── ProxySQL Bootstrap ────────────────────────────────────────────────────
-- Executado pelo entrypoint-wrapper.sh após o ProxySQL iniciar
-- Configura: servidores backend, monitor, usuários, regras de r/w split
-- ───────────────────────────────────────────────────────────────────────────

-- ═══ Monitor user (usa resultsdba — já existe no master) ═══
UPDATE global_variables SET variable_value='${MYSQL_APP_USER}' WHERE variable_name='mysql-monitor_username';
UPDATE global_variables SET variable_value='${MYSQL_APP_PASSWORD}' WHERE variable_name='mysql-monitor_password';
UPDATE global_variables SET variable_value='2000' WHERE variable_name='mysql-monitor_connect_interval';
UPDATE global_variables SET variable_value='5000' WHERE variable_name='mysql-monitor_ping_interval';
UPDATE global_variables SET variable_value='10000' WHERE variable_name='mysql-monitor_read_only_interval';

-- Health check: usa ping (rápido e confiável, sem depender de script externo)
UPDATE global_variables SET variable_value='true' WHERE variable_name='mysql-monitor_ping_enabled';
UPDATE global_variables SET variable_value='true' WHERE variable_name='mysql-monitor_connect_enabled';
UPDATE global_variables SET variable_value='3' WHERE variable_name='mysql-monitor_ping_max_failures';
UPDATE global_variables SET variable_value='1000' WHERE variable_name='mysql-monitor_ping_timeout';

LOAD MYSQL VARIABLES TO RUNTIME;
SAVE MYSQL VARIABLES TO DISK;

-- ═══ Backend servers ═══
-- Master (leitura + escrita) — hostgroup 0
INSERT INTO mysql_servers (hostgroup_id, hostname, port, weight, max_connections, comment)
VALUES (0, '${MYSQL_MASTER_HOST}', ${MYSQL_MASTER_PORT}, 1, 100, 'Master atual - srvmysql0');

-- Slave (leitura) — hostgroup 1
INSERT INTO mysql_servers (hostgroup_id, hostname, port, weight, max_connections, comment)
VALUES (1, '${MYSQL_SLAVE_HOST}', ${MYSQL_SLAVE_PORT}, 1, 50, 'Slave container local');

LOAD MYSQL SERVERS TO RUNTIME;
SAVE MYSQL SERVERS TO DISK;

-- ═══ Usuários da aplicação ═══
-- resultsdba (rw: hostgroup 0, ro: hostgroup 1)
INSERT INTO mysql_users (username, password, default_hostgroup, active)
VALUES ('resultsdba', '${MYSQL_APP_PASSWORD}', 0, 1);

-- Monitor user (mesmo resultsdba)
INSERT INTO mysql_users (username, password, default_hostgroup, active)
VALUES ('${MYSQL_APP_USER}', '${MYSQL_APP_PASSWORD}', 0, 1);

-- Roundcube/webmail
INSERT INTO mysql_users (username, password, default_hostgroup, active)
VALUES ('${ROUNDCUBE_DB_USER}', '${ROUNDCUBE_DB_PASSWORD}', 0, 1);

LOAD MYSQL USERS TO RUNTIME;
SAVE MYSQL USERS TO DISK;

-- ═══ Regras de Query Routing (read/write split) ═══
-- SELECT ... FOR UPDATE → hostgroup 0 (master)
INSERT INTO mysql_query_rules (rule_id, active, match_pattern, destination_hostgroup, apply)
VALUES (1, 1, 'SELECT.*FOR UPDATE', 0, 1);

-- SELECT com funções de agregação → hostgroup 1 (slave, seguro)
INSERT INTO mysql_query_rules (rule_id, active, match_pattern, destination_hostgroup, apply)
VALUES (2, 1, 'SELECT.*COUNT\(|SELECT.*SUM\(|SELECT.*AVG\(|SELECT.*MAX\(|SELECT.*MIN\(', 1, 1);

-- Demais SELECTs → hostgroup 1 (slave)
INSERT INTO mysql_query_rules (rule_id, active, match_pattern, destination_hostgroup, apply)
VALUES (10, 1, '^SELECT ', 1, 1);

-- Tudo o resto (INSERT, UPDATE, DELETE, DDL, etc.) → hostgroup 0 (master)
INSERT INTO mysql_query_rules (rule_id, active, match_pattern, destination_hostgroup, apply)
VALUES (100, 1, '.*', 0, 1);

LOAD MYSQL QUERY RULES TO RUNTIME;
SAVE MYSQL QUERY RULES TO DISK;

-- ═══ Variáveis de admin ═══
UPDATE global_variables SET variable_value='${PROXYSQL_ADMIN_USER}:${PROXYSQL_ADMIN_PASSWORD}' WHERE variable_name='admin-admin_credentials';
UPDATE global_variables SET variable_value='${PROXYSQL_ADMIN_USER}:${PROXYSQL_ADMIN_PASSWORD}' WHERE variable_name='admin-stats_credentials';

LOAD ADMIN VARIABLES TO RUNTIME;
SAVE ADMIN VARIABLES TO DISK;
