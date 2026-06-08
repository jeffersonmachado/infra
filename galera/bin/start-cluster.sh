#!/usr/bin/env bash
# =============================================================================
# start-cluster.sh — Sobe o MariaDB Galera Cluster de 3 nós em produção
# =============================================================================
# Uso:
#   ./bin/start-cluster.sh              # Sobe o cluster completo
#   ./bin/start-cluster.sh --status     # Apenas mostra status
#   ./bin/start-cluster.sh --bootstrap  # Apenas bootstrap do galera1
#
# Contexto:
#   Servidor: 10.10.2.30 (mexico.results.intranet)
#   Diretório: /opt/results/infra/galera/
#   Porta: 3306 (exposta no host via galera1)
# =============================================================================

set -euo pipefail
cd "$(dirname "$0")/.."

# -----------------------------------------------------------------------------
# Cores e helpers
# -----------------------------------------------------------------------------
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log()  { echo -e "${GREEN}[$(date +%H:%M:%S)]${NC} $*"; }
warn() { echo -e "${YELLOW}[$(date +%H:%M:%S)] WARN${NC} $*"; }
err()  { echo -e "${RED}[$(date +%H:%M:%S)] ERROR${NC} $*"; }

# -----------------------------------------------------------------------------
# Validações
# -----------------------------------------------------------------------------
validate() {
    log "Validando ambiente..."

    if ! docker version &>/dev/null; then
        err "Docker não disponível"; exit 1
    fi

    if ! docker compose version &>/dev/null; then
        err "Docker Compose não disponível"; exit 1
    fi

    # Verifica porta 3306
    if ss -tlnp 2>/dev/null | grep -q ':3306 ' || netstat -tlnp 2>/dev/null | grep -q ':3306 '; then
        warn "Porta 3306 já em uso. Verifique se não há outro MySQL rodando."
        ss -tlnp 2>/dev/null | grep ':3306 ' || true
    fi

    log "Ambiente validado."
}

# -----------------------------------------------------------------------------
# Bootstrap do galera1
# -----------------------------------------------------------------------------
bootstrap_galera1() {
    log "Adicionando --wsrep-new-cluster temporário..."
    sed -i '/hostname: galera1/a\    command: --wsrep-new-cluster' docker-compose.yml

    log "Iniciando galera1 (bootstrap)..."
    docker compose up -d galera1

    log "Aguardando galera1 ficar healthy..."
    local max=60
    for i in $(seq 1 $max); do
        local status
        status=$(docker inspect galera1 --format '{{.State.Health.Status}}' 2>/dev/null || echo "starting")
        case "$status" in
            healthy)
                log "galera1 HEALTHY!"
                break
                ;;
            unhealthy)
                warn "galera1 unhealthy, verificando logs..."
                docker logs galera1 --tail 20 2>&1 | tail -10
                ;;
        esac
        if [ "$i" -eq "$max" ]; then
            err "Timeout aguardando galera1 ($max tentativas)"
            docker logs galera1 --tail 30
            exit 1
        fi
        sleep 5
    done

    # Validar cluster
    local size
    size=$(docker exec galera1 mysql -u root -p'resu100dba' -sN \
        -e "SHOW STATUS LIKE 'wsrep_cluster_size';" 2>/dev/null | awk '{print $2}')
    if [ "$size" != "1" ]; then
        err "Cluster size inesperado: $size (esperado: 1)"
        exit 1
    fi
    log "Cluster bootstrap OK (size=1, Primary)"
}

# -----------------------------------------------------------------------------
# Remover bootstrap flag e subir demais nós
# -----------------------------------------------------------------------------
join_nodes() {
    log "Removendo --wsrep-new-cluster do compose..."
    sed -i '/command: --wsrep-new-cluster/d' docker-compose.yml

    log "Iniciando galera2..."
    docker compose up -d --no-recreate galera2

    log "Iniciando galera3..."
    docker compose up -d --no-recreate galera3

    log "Aguardando SST/IST completar (mariabackup pode levar alguns minutos)..."
    sleep 10
}

# -----------------------------------------------------------------------------
# Aguardar cluster completo (size=3)
# -----------------------------------------------------------------------------
wait_cluster() {
    log "Aguardando cluster size = 3..."
    local max=60
    for i in $(seq 1 $max); do
        local ready=0
        for n in galera1 galera2 galera3; do
            local s
            s=$(docker exec "$n" mysql -u root -p'resu100dba' -sN \
                -e "SHOW STATUS LIKE 'wsrep_cluster_size';" 2>/dev/null | awk '{print $2}' || echo "0")
            if [ "$s" = "3" ]; then
                ready=$((ready + 1))
            fi
        done
        if [ "$ready" -eq 3 ]; then
            log "Cluster completo: size=3 em todos os nós!"
            return 0
        fi
        if [ $((i % 6)) -eq 0 ]; then
            log "Aguardando... ($ready/3 nós com size=3)"
        fi
        sleep 10
    done
    err "Timeout aguardando cluster (size não chegou a 3)"
    show_status
    exit 1
}

# -----------------------------------------------------------------------------
# Validar replicação
# -----------------------------------------------------------------------------
validate_replication() {
    log "Validando replicação..."
    docker exec galera1 mysql -u root -p'resu100dba' -e "
        CREATE DATABASE IF NOT EXISTS replica;
        CREATE TABLE IF NOT EXISTS replica._healthcheck (id INT PRIMARY KEY, ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
        INSERT INTO replica._healthcheck (id) VALUES (1) ON DUPLICATE KEY UPDATE ts=CURRENT_TIMESTAMP;
    " 2>/dev/null

    sleep 2

    local c1 c2 c3
    c1=$(docker exec galera1 mysql -u root -p'resu100dba' -sN -e "SELECT COUNT(*) FROM replica._healthcheck;" 2>/dev/null)
    c2=$(docker exec galera2 mysql -u root -p'resu100dba' -sN -e "SELECT COUNT(*) FROM replica._healthcheck;" 2>/dev/null)
    c3=$(docker exec galera3 mysql -u root -p'resu100dba' -sN -e "SELECT COUNT(*) FROM replica._healthcheck;" 2>/dev/null)

    if [ "$c1" = "$c2" ] && [ "$c2" = "$c3" ]; then
        log "Replicação OK ($c1 registros em todos os nós)"
    else
        warn "Replicação: galera1=$c1 galera2=$c2 galera3=$c3 (pode ser timing)"
    fi
}

# -----------------------------------------------------------------------------
# Status
# -----------------------------------------------------------------------------
show_status() {
    echo ""
    echo "=============================================="
    echo "  GALERA CLUSTER STATUS"
    echo "=============================================="
    docker ps --filter name=galera --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}' 2>/dev/null || echo "Nenhum container rodando"
    echo ""

    for n in galera1 galera2 galera3; do
        echo "--- $n ---"
        docker exec "$n" mysql -u root -p'resu100dba' -e \
            "SHOW STATUS LIKE 'wsrep_cluster_size';
             SHOW STATUS LIKE 'wsrep_cluster_status';
             SHOW STATUS LIKE 'wsrep_ready';
             SHOW STATUS LIKE 'wsrep_connected';" 2>/dev/null || echo "  Indisponível"
    done
    echo "=============================================="
}

# -----------------------------------------------------------------------------
# Parada
# -----------------------------------------------------------------------------
stop_cluster() {
    log "Parando cluster..."
    docker compose down
    log "Cluster parado."
}

# -----------------------------------------------------------------------------
# Main
# -----------------------------------------------------------------------------
case "${1:-}" in
    --status)
        show_status
        ;;
    --stop)
        stop_cluster
        ;;
    --bootstrap)
        validate
        bootstrap_galera1
        log "Bootstrap concluído. Execute './bin/start-cluster.sh' para subir os demais nós."
        ;;
    *)
        validate
        bootstrap_galera1
        join_nodes
        wait_cluster
        validate_replication
        echo ""
        log "=============================================="
        log "  CLUSTER PRONTO!"
        log "  Porta: 3306"
        log "  Host:  $(hostname)"
        log "  Nós:   galera1 (172.32.0.11) ✱ PRIMARY"
        log "         galera2 (172.32.0.12)"
        log "         galera3 (172.32.0.13)"
        log "=============================================="
        ;;
esac
