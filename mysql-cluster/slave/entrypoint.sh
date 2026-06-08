#!/bin/bash
# ─── MariaDB Slave Entrypoint ───────────────────────────────────────────────
# 1. Renderiza my.cnf com variáveis de ambiente
# 2. Inicia MariaDB normalmente
# 3. Se for primeira inicialização, configura replicação
# ───────────────────────────────────────────────────────────────────────────
set -eu

CUSTOM_CNF="/etc/mysql/conf.d/custom.cnf"
TEMPLATE="/etc/mysql/conf.d/custom.cnf.template"
INIT_FLAG="/var/lib/mysql/.replication_configured"
REPL_SCRIPT="/opt/mysql-cluster/scripts/init-replication.sh"

echo "[slave-entrypoint] Renderizando configuração..."

if [ -f "${TEMPLATE}" ]; then
    envsubst < "${TEMPLATE}" > "${CUSTOM_CNF}"
    echo "[slave-entrypoint] ${CUSTOM_CNF} gerado."
else
    echo "[slave-entrypoint] AVISO: template ${TEMPLATE} não encontrado."
fi

# Inicia o MariaDB em background para bootstrap
echo "[slave-entrypoint] Iniciando MariaDB..."
docker-entrypoint.sh mariadbd &

MARIADB_PID=$!

# Aguarda MySQL ficar pronto
echo "[slave-entrypoint] Aguardando MariaDB iniciar..."
for i in $(seq 1 60); do
    if mysqladmin ping -u root -p"${MYSQL_ROOT_PASSWORD}" --silent 2>/dev/null; then
        echo "[slave-entrypoint] MariaDB pronto."
        break
    fi
    if [ "$i" -eq 60 ]; then
        echo "[slave-entrypoint] ERRO: MariaDB não iniciou." >&2
        exit 1
    fi
    sleep 2
done

# Configura replicação apenas na primeira inicialização
if [ ! -f "${INIT_FLAG}" ] && [ -f "${REPL_SCRIPT}" ]; then
    echo "[slave-entrypoint] Primeira inicialização — configurando replicação..."
    if "${REPL_SCRIPT}"; then
        touch "${INIT_FLAG}"
        echo "[slave-entrypoint] Replicação configurada com sucesso."
    else
        echo "[slave-entrypoint] ERRO: Falha ao configurar replicação." >&2
        # Não sai — deixa o container rodando para debug
    fi
else
    echo "[slave-entrypoint] Replicação já configurada, pulando bootstrap."
fi

echo "[slave-entrypoint] Slave pronto."

# Mantém o container rodando
wait ${MARIADB_PID}
