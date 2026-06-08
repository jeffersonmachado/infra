#!/bin/bash
# ─── Deploy MariaDB Galera Cluster ─────────────────────────────────────────
# 3 nós multi-master em host network, cada um no seu IP:
#   srvmysql0 — 10.10.2.79 (bootstrap)
#   srvmysql1 — 10.10.2.89
#   srvmysql2 — 10.10.2.49
#
# Uso:
#   ./scripts/deploy-galera.sh           (primeiro deploy ou cluster íntegro)
#   ./scripts/deploy-galera.sh bootstrap (forçar bootstrap no srvmysql0)
# ───────────────────────────────────────────────────────────────────────────
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
COMPOSE_FILE="docker-compose.mysql-galera.yml"
ENV_FILE=".env.mysql-galera"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
ok()   { echo -e "${GREEN}[OK]${NC} $1"; }
step() { echo -e "\n${CYAN}═══ $1 ═══${NC}"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
fail() { echo -e "${RED}[FAIL]${NC} $1"; exit 1; }

MODE="${1:-auto}"

cd "$PROJECT_DIR"

# ── 1. Pré-requisitos ──────────────────────────────────────────────────────
step "Verificando pré-requisitos"

if [ ! -f "$ENV_FILE" ]; then
    fail "$ENV_FILE não encontrado. Crie-o com as variáveis necessárias."
fi

# Verifica IPs (necessário para host network)
for IP in 10.10.2.79 10.10.2.89 10.10.2.49; do
    if ! ip addr show 2>/dev/null | grep -q "$IP"; then
        warn "IP $IP não encontrado. Para dev local: sudo ip addr add $IP/24 dev lo"
    fi
done

if ! docker image inspect mariadb-galera:10.11 >/dev/null 2>&1; then
    warn "Imagem mariadb-galera:10.11 não encontrada. Buildando..."
    docker build -t mariadb-galera:10.11 -f mysql-cluster/galera/Dockerfile mysql-cluster/galera/
    ok "Imagem buildada"
fi

# Verifica se portas Galera já estão em uso
for PORT in 4567 4568 4444; do
    if ss -tlnp | grep -q ":$PORT "; then
        warn "Porta $PORT já está em uso no host"
    fi
done

ok "Pré-requisitos OK"

# ── 2. Bootstrap ───────────────────────────────────────────────────────────
step "Iniciando nó bootstrap (srvmysql0)"

if [ "$MODE" = "bootstrap" ] || ! docker exec srvmysql0 mysqladmin ping -u root -p"${MYSQL_ROOT_PASSWORD:-resu100dba}" 2>/dev/null; then
    # Derruba tudo (com volumes se for bootstrap)
    if [ "$MODE" = "bootstrap" ]; then
        docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" down -v 2>/dev/null || true
    else
        docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" down 2>/dev/null || true
    fi

    echo "Iniciando srvmysql0 como bootstrap..."
    GALERA_BOOTSTRAP=true docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d srvmysql0

    # Aguardar healthy (até 60s)
    echo -n "Aguardando srvmysql0 ficar healthy"
    for i in $(seq 1 30); do
        STATUS=$(docker inspect srvmysql0 --format='{{.State.Health.Status}}' 2>/dev/null || echo "unknown")
        if [ "$STATUS" = "healthy" ]; then
            echo " OK"
            break
        fi
        echo -n "."
        sleep 2
    done
    if [ "$STATUS" != "healthy" ]; then
        docker logs srvmysql0 --tail 20
        fail "srvmysql0 não atingiu healthy após 60s"
    fi

    # Criar usuário SST para mariabackup
    SST_USER="${GALERA_SST_USER:-galera}"
    SST_PASS="${GALERA_SST_PASSWORD:-galeraSST@2026}"
    docker exec srvmysql0 mysql -u root -p"${MYSQL_ROOT_PASSWORD:-resu100dba}" -e "
        CREATE USER IF NOT EXISTS '${SST_USER}'@'localhost' IDENTIFIED BY '${SST_PASS}';
        CREATE USER IF NOT EXISTS '${SST_USER}'@'%' IDENTIFIED BY '${SST_PASS}';
        GRANT ALL PRIVILEGES ON *.* TO '${SST_USER}'@'localhost' WITH GRANT OPTION;
        GRANT ALL PRIVILEGES ON *.* TO '${SST_USER}'@'%' WITH GRANT OPTION;
        FLUSH PRIVILEGES;
    " 2>/dev/null
    ok "Usuário SST '${SST_USER}' criado"
else
    ok "srvmysql0 já está rodando"
fi

# Verifica wsrep
WSREP_SIZE=$(docker exec srvmysql0 mysql -u root -p"${MYSQL_ROOT_PASSWORD:-resu100dba}" -N -e "SHOW STATUS LIKE 'wsrep_cluster_size'" 2>/dev/null | awk '{print $2}')
echo "  Cluster size: $WSREP_SIZE | State: $(docker exec srvmysql0 mysql -u root -p"${MYSQL_ROOT_PASSWORD:-resu100dba}" -N -e "SHOW STATUS LIKE 'wsrep_local_state_comment'" 2>/dev/null | awk '{print $2}')"

# ── 3. Nós secundários (sequencial para evitar conflito SST) ────────────

for NODE in srvmysql1 srvmysql2; do
    step "Iniciando $NODE"
    docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d --no-recreate "$NODE" 2>&1

    echo -n "Aguardando $NODE healthy"
    for i in $(seq 1 45); do
        STATUS=$(docker inspect "$NODE" --format='{{.State.Health.Status}}' 2>/dev/null || echo "unknown")
        if [ "$STATUS" = "healthy" ]; then echo " OK"; break; fi
        if [ "$STATUS" = "unhealthy" ]; then
            echo ""
            warn "$NODE unhealthy, verificando logs..."
            docker logs "$NODE" --tail 15 2>&1
            break
        fi
        echo -n "."; sleep 2
    done

    # Verifica wsrep
    SIZE=$(docker exec "$NODE" mysql -u root -p"${MYSQL_ROOT_PASSWORD:-resu100dba}" -N -e "SHOW STATUS LIKE 'wsrep_cluster_size'" 2>/dev/null | awk '{print $2}')
    echo "  $NODE: cluster_size=$SIZE"
done

# ── 4. Status final ────────────────────────────────────────────────────────
step "Status do Cluster"

for NODE in srvmysql0 srvmysql1 srvmysql2; do
    SIZE=$(docker exec "$NODE" mysql -u root -p"${MYSQL_ROOT_PASSWORD:-resu100dba}" -N -e "SHOW STATUS LIKE 'wsrep_cluster_size'" 2>/dev/null | awk '{print $2}')
    STATE=$(docker exec "$NODE" mysql -u root -p"${MYSQL_ROOT_PASSWORD:-resu100dba}" -N -e "SHOW STATUS LIKE 'wsrep_local_state_comment'" 2>/dev/null | awk '{print $2}')
    READY=$(docker exec "$NODE" mysql -u root -p"${MYSQL_ROOT_PASSWORD:-resu100dba}" -N -e "SHOW STATUS LIKE 'wsrep_ready'" 2>/dev/null | awk '{print $2}')
    HEALTH=$(docker inspect "$NODE" --format='{{.State.Health.Status}}' 2>/dev/null || echo "down")
    printf "  %-12s  size=%-2s  state=%-10s  ready=%-4s  health=%s\n" "$NODE" "${SIZE:-?}" "${STATE:-?}" "${READY:-?}" "$HEALTH"
done

echo ""
echo "=== Cluster Galera pronto ==="
echo "  Conexão: mysql -u root -p -h 10.10.2.79"
