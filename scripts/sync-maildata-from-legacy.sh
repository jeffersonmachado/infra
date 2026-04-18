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
  ./scripts/sync-maildata-from-legacy.sh precheck [--dry-run]
    ./scripts/sync-maildata-from-legacy.sh sync [--dry-run]

Variaveis suportadas:
  DEPLOY_HOST              Host novo da stack de mail (padrao: 10.10.2.30)
  DEPLOY_USER              Usuario SSH do host novo (padrao: root)
  DEPLOY_PORT              Porta SSH do host novo (padrao: 22)
  DEPLOY_PATH              Diretorio remoto do projeto (padrao: /opt/results/infra)
  DEPLOY_SSH_PASSWORD      Senha SSH para acessar o host novo
  SSHPASS                  Alternativa para DEPLOY_SSH_PASSWORD
  LEGACY_HOST              Host legado de mail (padrao: 10.10.2.2)
  LEGACY_USER              Usuario SSH do host legado (padrao: root)
  LEGACY_PORT              Porta SSH do host legado (padrao: 22)
  LEGACY_PATH              Raiz do spool legado (padrao: /gv/)
    LEGACY_SSH_PASSWORD      Senha SSH para acessar o servidor legado a partir da maquina local
  LEGACY_SSHPASS           Alternativa para LEGACY_SSH_PASSWORD
  MAIL_ENV_FILE            Arquivo de ambiente da stack mail no host novo (padrao: .env.remote-10.10.2.30-mail)
  MAIL_UID                 UID virtual do maildir novo (padrao: 1004)
  MAIL_GID                 GID virtual do maildir novo (padrao: 1004)
  TARGET_MAIL_ROOT         Raiz do spool novo (padrao: /var/mail/vhosts)

Notas:
  - o sync copia toda a arvore /gv do legado para /var/mail/vhosts no host novo
    - o sync usa relay local por SSH, sem depender de sshpass instalado no host novo
EOF
}

show_cmd() {
    local title="$1"
    local cmd="$2"
    echo -e "${YELLOW}[CMD]${NC} ${title}"
    echo "$cmd"
    echo ""
}

run_cmd() {
    local title="$1"
    local cmd="$2"

    show_cmd "$title" "$cmd"

    if [ "$DRY_RUN" = "true" ]; then
        return 0
    fi

    eval "$cmd"
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
LEGACY_HOST="${LEGACY_HOST:-10.10.2.2}"
LEGACY_USER="${LEGACY_USER:-root}"
LEGACY_PORT="${LEGACY_PORT:-22}"
LEGACY_PATH="${LEGACY_PATH:-/gv/}"
MAIL_UID="${MAIL_UID:-1004}"
MAIL_GID="${MAIL_GID:-1004}"
TARGET_MAIL_ROOT="${TARGET_MAIL_ROOT:-/var/mail/vhosts}"

while [[ $# -gt 0 ]]; do
    case "$1" in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        *)
            error "Argumento desconhecido: $1"
            usage
            exit 1
            ;;
    esac
done

case "$ACTION" in
    precheck|sync)
        ;;
    *)
        error "Acao invalida: $ACTION"
        usage
        exit 1
        ;;
esac

command -v ssh >/dev/null 2>&1 || { error "ssh nao encontrado"; exit 1; }
command -v sshpass >/dev/null 2>&1 || { error "sshpass nao encontrado"; exit 1; }

if [ -n "${DEPLOY_SSH_PASSWORD:-}" ] && [ -z "${SSHPASS:-}" ]; then
    export SSHPASS="$DEPLOY_SSH_PASSWORD"
fi

if [ -z "${SSHPASS:-}" ]; then
    error "Defina DEPLOY_SSH_PASSWORD ou SSHPASS para acessar o host novo."
    exit 1
fi

LEGACY_PASSWORD="${LEGACY_SSH_PASSWORD:-${LEGACY_SSHPASS:-}}"
if [ -z "$LEGACY_PASSWORD" ]; then
    error "Defina LEGACY_SSH_PASSWORD ou LEGACY_SSHPASS para acessar o servidor legado."
    exit 1
fi

SSH_OPTS="-p $REMOTE_PORT -o PreferredAuthentications=password -o PubkeyAuthentication=no -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ConnectTimeout=15"
SSH_CMD="sshpass -e ssh $SSH_OPTS"
LEGACY_SSH_OPTS="-p $LEGACY_PORT -o PreferredAuthentications=password -o PubkeyAuthentication=no -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ConnectTimeout=15"
LEGACY_SSH_CMD="sshpass -e ssh $LEGACY_SSH_OPTS"

REMOTE_BASE_CMD="$SSH_CMD ${REMOTE_USER}@${REMOTE_HOST}"
LEGACY_BASE_CMD="SSHPASS=$(printf '%q' "$LEGACY_PASSWORD") $LEGACY_SSH_CMD ${LEGACY_USER}@${LEGACY_HOST}"

section "Legacy Mail Sync $ACTION"
info "Host novo: $REMOTE_HOST"
info "Host legado: $LEGACY_HOST"
info "Origem legado: $LEGACY_PATH"
info "Destino novo: $TARGET_MAIL_ROOT"
info "Dry-run: $DRY_RUN"

case "$ACTION" in
    precheck)
        run_cmd "Validar acesso ao legado" "$LEGACY_BASE_CMD \"echo legacy-ok; test -d '$LEGACY_PATH'; du -sh '$LEGACY_PATH' 2>/dev/null || true\""
                run_cmd "Validar acesso ao host novo" "$REMOTE_BASE_CMD \"mkdir -p '$TARGET_MAIL_ROOT' && ls -ld '$TARGET_MAIL_ROOT' && df -h '$TARGET_MAIL_ROOT'\""
                run_cmd "Validar compose mail no host novo" "$REMOTE_BASE_CMD \"cd '$REMOTE_DIR' && docker compose --env-file '$MAIL_ENV_FILE' -f docker-compose.mail.yml --project-name infra-mail ps\""
        ;;
    sync)
        warn "Este sync copia toda a arvore de Maildir do legado para o host novo."
                run_cmd "Preparar destino no host novo" "$REMOTE_BASE_CMD \"mkdir -p '$TARGET_MAIL_ROOT' '$TARGET_MAIL_ROOT/results.com.br' && chown '$MAIL_UID:$MAIL_GID' '$TARGET_MAIL_ROOT' '$TARGET_MAIL_ROOT/results.com.br' && chmod 0770 '$TARGET_MAIL_ROOT' '$TARGET_MAIL_ROOT/results.com.br'\""
                if command -v pv >/dev/null 2>&1; then
                        run_cmd "Copiar spool legado para o host novo" "$LEGACY_BASE_CMD \"cd '$LEGACY_PATH' && tar cpf - .\" | pv | $REMOTE_BASE_CMD \"tar xpf - -C '$TARGET_MAIL_ROOT'\""
                else
                        run_cmd "Copiar spool legado para o host novo" "$LEGACY_BASE_CMD \"cd '$LEGACY_PATH' && tar cpf - .\" | $REMOTE_BASE_CMD \"tar xpf - -C '$TARGET_MAIL_ROOT'\""
                fi
                run_cmd "Ajustar ownership final no host novo" "$REMOTE_BASE_CMD \"chown -R '$MAIL_UID:$MAIL_GID' '$TARGET_MAIL_ROOT' && ls -ld '$TARGET_MAIL_ROOT' '$TARGET_MAIL_ROOT'/* 2>/dev/null || true\""
        ;;
esac

info "Concluido: $ACTION"