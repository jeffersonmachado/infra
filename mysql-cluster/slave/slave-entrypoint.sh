#!/bin/bash
# ─── Slave Init Script ─────────────────────────────────────────────────────
# Executado pelo mariadb:10.11 na primeira inicialização
# Renderiza my.cnf customizado e tenta iniciar replicação
# ───────────────────────────────────────────────────────────────────────────
set -eu

echo "[slave-init] Configurando slave..."

# Renderiza my.cnf customizado com envsubst
if [ -f /etc/mysql/conf.d/custom.cnf.template ]; then
    # Instala gettext para envsubst se necessário
    apt-get update -qq && apt-get install -y -qq gettext-base 2>/dev/null || true
    envsubst < /etc/mysql/conf.d/custom.cnf.template > /etc/mysql/conf.d/custom.cnf
    echo "[slave-init] my.cnf renderizado."
fi

# Tenta configurar replicação se o init-replication.sh existir
if [ -f /opt/mysql-cluster/scripts/init-replication.sh ]; then
    echo "[slave-init] Tentando configurar replicação..."
    # Não falha se replicação não funcionar (master pode não ter binlog ainda)
    /opt/mysql-cluster/scripts/init-replication.sh || echo "[slave-init] AVISO: replicação não configurada (master sem binlog?)"
fi

echo "[slave-init] Concluído."
