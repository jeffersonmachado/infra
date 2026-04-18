#!/bin/bash

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

section() {
    echo -e "\n${BLUE}=== $1 ===${NC}\n"
}

usage() {
    cat <<'EOF'
Uso:
  ./scripts/mail-cutover-10.10.2.30.sh precheck [--dry-run]
  ./scripts/mail-cutover-10.10.2.30.sh cutover [--dry-run] [--interface eth0]
  ./scripts/mail-cutover-10.10.2.30.sh rollback [--dry-run] [--interface eth0]

Variaveis suportadas:
  DEPLOY_HOST              Host remoto (padrao: 10.10.2.30)
  DEPLOY_USER              Usuario SSH (padrao: root)
  DEPLOY_PORT              Porta SSH (padrao: 22)
  DEPLOY_PATH              Diretorio remoto do projeto (padrao: /opt/results/infra)
  DEPLOY_SSH_PASSWORD      Senha SSH para sshpass
  SSHPASS                  Alternativa para sshpass
  MAIL_ENV_FILE            Arquivo de ambiente remoto (padrao: .env.remote-10.10.2.30-mail)
  MAIL_COMPOSE_FILE        Compose file do mail (padrao: docker-compose.mail.yml)
  MAIL_PROJECT_NAME        Projeto compose (padrao: infra-mail)
  MAIL_PRIMARY_IP          IP principal de mail (padrao: 10.10.2.3)
  MAIL_SECONDARY_IP        IP secundario de mail (padrao: 10.10.2.23)
  MAIL_IP_PREFIX           Prefixo CIDR dos IPs (padrao: 24)
EOF
}

require_cmd() {
    command -v "$1" >/dev/null 2>&1 || {
        error "Comando obrigatorio nao encontrado: $1"
        exit 1
    }
}

show_cmd() {
    local title="$1"
    local cmd="$2"
    echo -e "${YELLOW}[CMD]${NC} ${title}"
    echo "$cmd"
    echo ""
}

run_remote() {
    local title="$1"
    local remote_cmd="$2"
  local remote_env
  local full_cmd

  remote_env="REMOTE_DIR=$(printf '%q' "$REMOTE_DIR") MAIL_ENV_FILE=$(printf '%q' "$MAIL_ENV_FILE") MAIL_COMPOSE_FILE=$(printf '%q' "$MAIL_COMPOSE_FILE") MAIL_PROJECT_NAME=$(printf '%q' "$MAIL_PROJECT_NAME") MAIL_PRIMARY_IP=$(printf '%q' "$MAIL_PRIMARY_IP") MAIL_SECONDARY_IP=$(printf '%q' "$MAIL_SECONDARY_IP") MAIL_IP_PREFIX=$(printf '%q' "$MAIL_IP_PREFIX") NETWORK_INTERFACE=$(printf '%q' "$NETWORK_INTERFACE")"
  full_cmd="$SSH_CMD ${REMOTE_USER}@${REMOTE_HOST} \"$remote_env bash -s\""

    show_cmd "$title" "$full_cmd"

    if [ "$DRY_RUN" = "true" ]; then
        return 0
    fi

  eval "$full_cmd" <<< "$remote_cmd"
}

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$ROOT_DIR"

if [ $# -lt 1 ]; then
    usage
    exit 1
fi

ACTION="$1"
shift

DRY_RUN=false
REMOTE_HOST="${DEPLOY_HOST:-10.10.2.30}"
REMOTE_USER="${DEPLOY_USER:-root}"
REMOTE_PORT="${DEPLOY_PORT:-22}"
REMOTE_DIR="${DEPLOY_PATH:-/opt/results/infra}"
MAIL_ENV_FILE="${MAIL_ENV_FILE:-.env.remote-10.10.2.30-mail}"
MAIL_COMPOSE_FILE="${MAIL_COMPOSE_FILE:-docker-compose.mail.yml}"
MAIL_PROJECT_NAME="${MAIL_PROJECT_NAME:-infra-mail}"
MAIL_PRIMARY_IP="${MAIL_PRIMARY_IP:-10.10.2.3}"
MAIL_SECONDARY_IP="${MAIL_SECONDARY_IP:-10.10.2.23}"
MAIL_IP_PREFIX="${MAIL_IP_PREFIX:-24}"
NETWORK_INTERFACE="${NETWORK_INTERFACE:-}"

while [[ $# -gt 0 ]]; do
    case "$1" in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --interface)
            NETWORK_INTERFACE="$2"
            shift 2
            ;;
        *)
            error "Argumento desconhecido: $1"
            usage
            exit 1
            ;;
    esac
done

case "$ACTION" in
    precheck|cutover|rollback)
        ;;
    *)
        error "Acao invalida: $ACTION"
        usage
        exit 1
        ;;
esac

require_cmd ssh
require_cmd sshpass

if [ -n "${DEPLOY_SSH_PASSWORD:-}" ] && [ -z "${SSHPASS:-}" ]; then
    export SSHPASS="$DEPLOY_SSH_PASSWORD"
fi

if [ -z "${SSHPASS:-}" ]; then
    error "Defina DEPLOY_SSH_PASSWORD ou SSHPASS antes de executar o script."
    exit 1
fi

SSH_OPTS="-p $REMOTE_PORT -o PreferredAuthentications=password -o PubkeyAuthentication=no -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ConnectTimeout=15"
SSH_CMD="sshpass -e ssh $SSH_OPTS"

read -r -d '' REMOTE_PRECHECK <<'EOF' || true
set -euo pipefail
cd "$REMOTE_DIR"
IFACE="${NETWORK_INTERFACE:-}"
if [ -z "$IFACE" ]; then
  IFACE="$(ip route show default 2>/dev/null | awk '/default/ {print $5; exit}')"
fi
if [ -z "$IFACE" ]; then
  echo "Nao foi possivel detectar a interface de rede" >&2
  exit 1
fi
echo "interface=$IFACE"
echo "--- ip -4 addr show dev $IFACE"
ip -4 addr show dev "$IFACE"
echo "--- compose config"
docker compose --env-file "$MAIL_ENV_FILE" -f "$MAIL_COMPOSE_FILE" config >/dev/null && echo compose-ok
echo "--- docker compose ps"
docker compose --project-name "$MAIL_PROJECT_NAME" -f "$MAIL_COMPOSE_FILE" ps || true
echo "--- listeners :25|:465|:587|:110|:995|:143|:993|:4190"
if command -v ss >/dev/null 2>&1; then
  ss -ltn '( sport = :25 or sport = :465 or sport = :587 or sport = :110 or sport = :995 or sport = :143 or sport = :993 or sport = :4190 )' || true
elif command -v netstat >/dev/null 2>&1; then
  netstat -ltn 2>/dev/null | grep -E ':(25|110|143|465|587|993|995|4190)[[:space:]]' || true
else
  echo "Nem ss nem netstat estao disponiveis no host remoto"
fi
EOF

read -r -d '' REMOTE_CUTOVER <<'EOF' || true
set -euo pipefail
cd "$REMOTE_DIR"
IFACE="${NETWORK_INTERFACE:-}"
if [ -z "$IFACE" ]; then
  IFACE="$(ip route show default 2>/dev/null | awk '/default/ {print $5; exit}')"
fi
if [ -z "$IFACE" ]; then
  echo "Nao foi possivel detectar a interface de rede" >&2
  exit 1
fi
for MAIL_IP in "$MAIL_PRIMARY_IP" "$MAIL_SECONDARY_IP"; do
  if ! ip -4 addr show dev "$IFACE" | grep -q "\b${MAIL_IP}/${MAIL_IP_PREFIX}\b"; then
    ip addr add "${MAIL_IP}/${MAIL_IP_PREFIX}" dev "$IFACE"
  fi
done
cp "$MAIL_ENV_FILE" .env
docker compose --env-file "$MAIL_ENV_FILE" -f "$MAIL_COMPOSE_FILE" config >/dev/null
docker compose --project-name "$MAIL_PROJECT_NAME" --env-file "$MAIL_ENV_FILE" -f "$MAIL_COMPOSE_FILE" up -d --build --force-recreate
echo "--- ip -4 addr show dev $IFACE"
ip -4 addr show dev "$IFACE"
echo "--- docker compose ps"
docker compose --project-name "$MAIL_PROJECT_NAME" --env-file "$MAIL_ENV_FILE" -f "$MAIL_COMPOSE_FILE" ps
EOF

read -r -d '' REMOTE_ROLLBACK <<'EOF' || true
set -euo pipefail
cd "$REMOTE_DIR"
IFACE="${NETWORK_INTERFACE:-}"
if [ -z "$IFACE" ]; then
  IFACE="$(ip route show default 2>/dev/null | awk '/default/ {print $5; exit}')"
fi
if [ -z "$IFACE" ]; then
  echo "Nao foi possivel detectar a interface de rede" >&2
  exit 1
fi
docker compose --project-name "$MAIL_PROJECT_NAME" -f "$MAIL_COMPOSE_FILE" down || true
for MAIL_IP in "$MAIL_PRIMARY_IP" "$MAIL_SECONDARY_IP"; do
  if ip -4 addr show dev "$IFACE" | grep -q "\b${MAIL_IP}/${MAIL_IP_PREFIX}\b"; then
    ip addr del "${MAIL_IP}/${MAIL_IP_PREFIX}" dev "$IFACE"
  fi
done
echo "--- ip -4 addr show dev $IFACE"
ip -4 addr show dev "$IFACE"
EOF

section "Mail Cutover $ACTION"
info "Host remoto: $REMOTE_HOST"
info "Projeto remoto: $MAIL_PROJECT_NAME"
info "Compose file: $MAIL_COMPOSE_FILE"
info "Env file: $MAIL_ENV_FILE"
info "IPs de corte: $MAIL_PRIMARY_IP/$MAIL_IP_PREFIX e $MAIL_SECONDARY_IP/$MAIL_IP_PREFIX"

case "$ACTION" in
    precheck)
        run_remote "Executar precheck remoto" "$REMOTE_PRECHECK"
        ;;
    cutover)
        warn "Execute o cutover somente apos desanunciar 10.10.2.3 e 10.10.2.23 nos hosts antigos."
        run_remote "Executar cutover remoto" "$REMOTE_CUTOVER"
        ;;
    rollback)
        run_remote "Executar rollback remoto" "$REMOTE_ROLLBACK"
        ;;
esac

info "Concluido: $ACTION"