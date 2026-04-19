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
    MAIL_PROJECT_NAME        Nome do projeto docker compose da stack mail (padrao: infra-mail)
    MAIL_VOLUME_NAME         Volume Docker que guarda o spool (padrao: <MAIL_PROJECT_NAME>_maildata)
  MAIL_UID                 UID virtual do maildir novo (padrao: 1004)
  MAIL_GID                 GID virtual do maildir novo (padrao: 1004)
    TARGET_MAIL_ROOT         Mountpoint real do spool no host novo; se omitido, resolve via docker volume inspect
    LOCAL_STAGE_ROOT         Diretorio local temporario para staging do rsync (padrao: /tmp/results-mail-sync-staging)

Notas:
    - o sync copia toda a arvore /gv do legado para o mountpoint real do volume maildata no host novo
    - o sync usa staging local por mailbox com rsync retomavel
    - se a conexao cair, basta rodar o sync novamente para continuar do ponto em que parou
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
MAIL_PROJECT_NAME="${MAIL_PROJECT_NAME:-infra-mail}"
MAIL_VOLUME_NAME="${MAIL_VOLUME_NAME:-${MAIL_PROJECT_NAME}_maildata}"
LEGACY_HOST="${LEGACY_HOST:-10.10.2.2}"
LEGACY_USER="${LEGACY_USER:-root}"
LEGACY_PORT="${LEGACY_PORT:-22}"
LEGACY_PATH="${LEGACY_PATH:-/gv/}"
MAIL_UID="${MAIL_UID:-1004}"
MAIL_GID="${MAIL_GID:-1004}"
TARGET_MAIL_ROOT="${TARGET_MAIL_ROOT:-}"
LOCAL_STAGE_ROOT="${LOCAL_STAGE_ROOT:-/tmp/results-mail-sync-staging}"

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
command -v rsync >/dev/null 2>&1 || { error "rsync nao encontrado localmente"; exit 1; }

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

SSH_OPTS="-p $REMOTE_PORT -o PreferredAuthentications=password,keyboard-interactive -o KbdInteractiveAuthentication=yes -o NumberOfPasswordPrompts=1 -o PubkeyAuthentication=no -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ConnectTimeout=15"
SSH_CMD="sshpass -e ssh $SSH_OPTS"
LEGACY_SSH_OPTS="-p $LEGACY_PORT -o PreferredAuthentications=password,keyboard-interactive -o KbdInteractiveAuthentication=yes -o NumberOfPasswordPrompts=1 -o PubkeyAuthentication=no -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ConnectTimeout=15 -o HostKeyAlgorithms=+ssh-rsa -o PubkeyAcceptedAlgorithms=+ssh-rsa"
LEGACY_SSH_CMD="sshpass -e ssh $LEGACY_SSH_OPTS"

REMOTE_BASE_CMD="$SSH_CMD ${REMOTE_USER}@${REMOTE_HOST}"
LEGACY_BASE_CMD="env SSHPASS=$(printf '%q' "$LEGACY_PASSWORD") $LEGACY_SSH_CMD ${LEGACY_USER}@${LEGACY_HOST}"

if [ -z "$TARGET_MAIL_ROOT" ]; then
    TARGET_MAIL_ROOT="$($REMOTE_BASE_CMD "docker volume inspect '$MAIL_VOLUME_NAME' --format '{{ .Mountpoint }}'" 2>/dev/null | tr -d '\r')"
fi

if [ -z "$TARGET_MAIL_ROOT" ]; then
    error "Nao foi possivel resolver o mountpoint do volume '$MAIL_VOLUME_NAME' no host novo. Defina TARGET_MAIL_ROOT manualmente."
    exit 1
fi

section "Legacy Mail Sync $ACTION"
info "Host novo: $REMOTE_HOST"
info "Host legado: $LEGACY_HOST"
info "Origem legado: $LEGACY_PATH"
info "Destino novo: $TARGET_MAIL_ROOT"
info "Staging local: $LOCAL_STAGE_ROOT"
info "Dry-run: $DRY_RUN"

case "$ACTION" in
    precheck)
        run_cmd "Validar acesso ao legado" "$LEGACY_BASE_CMD \"echo legacy-ok; test -d '$LEGACY_PATH'; du -sh '$LEGACY_PATH' 2>/dev/null || true\""
    run_cmd "Validar rsync no legado" "$LEGACY_BASE_CMD \"command -v rsync\""
    run_cmd "Validar acesso ao host novo" "$REMOTE_BASE_CMD \"mkdir -p '$TARGET_MAIL_ROOT' && ls -ld '$TARGET_MAIL_ROOT' && df -h '$TARGET_MAIL_ROOT'\""
    run_cmd "Validar rsync no host novo" "$REMOTE_BASE_CMD \"command -v rsync\""
    run_cmd "Validar staging local" "mkdir -p '$LOCAL_STAGE_ROOT' && ls -ld '$LOCAL_STAGE_ROOT' && df -h '$LOCAL_STAGE_ROOT'"
    run_cmd "Medir maior mailbox no legado" "$LEGACY_BASE_CMD \"du -sm '$LEGACY_PATH'*/* 2>/dev/null | sort -n | tail -n 5\""
    run_cmd "Validar compose mail no host novo" "$REMOTE_BASE_CMD \"cd '$REMOTE_DIR' && docker compose --env-file '$MAIL_ENV_FILE' -f docker-compose.mail.yml --project-name '$MAIL_PROJECT_NAME' ps\""
        ;;
    sync)
        warn "Este sync copia toda a arvore de Maildir do legado para o host novo."
    run_cmd "Preparar staging local" "mkdir -p '$LOCAL_STAGE_ROOT'"
    run_cmd "Preparar destino base no host novo" "$REMOTE_BASE_CMD \"mkdir -p '$TARGET_MAIL_ROOT' && chown '$MAIL_UID:$MAIL_GID' '$TARGET_MAIL_ROOT' && chmod 0770 '$TARGET_MAIL_ROOT'\""

    MAILBOX_LIST="$(env SSHPASS="$LEGACY_PASSWORD" sshpass -e ssh $LEGACY_SSH_OPTS "${LEGACY_USER}@${LEGACY_HOST}" "find '$LEGACY_PATH' -mindepth 2 -maxdepth 2 -type d -printf '%P\\n' | sort" | tr -d '\r')"

    if [ -z "$MAILBOX_LIST" ]; then
        error "Nenhuma mailbox encontrada em $LEGACY_PATH no host legado."
        exit 1
    fi

    mapfile -t MAILBOX_PATHS <<< "$MAILBOX_LIST"

    for mailbox_path in "${MAILBOX_PATHS[@]}"; do
        [ -n "$mailbox_path" ] || continue

        domain="${mailbox_path%%/*}"
        mailbox="${mailbox_path#*/}"
        local_stage_dir="$LOCAL_STAGE_ROOT/$domain/$mailbox"
        remote_domain_dir="$TARGET_MAIL_ROOT/$domain"
        remote_mailbox_dir="$remote_domain_dir/$mailbox"

        info "Sincronizando $mailbox_path"

        run_cmd "Preparar staging de $mailbox_path" "mkdir -p '$local_stage_dir'"
        run_cmd "Baixar $mailbox_path para staging local" "export SSHPASS='$LEGACY_PASSWORD' && rsync -a --delete --partial --append-verify -e \"sshpass -e ssh $LEGACY_SSH_OPTS\" '${LEGACY_USER}@${LEGACY_HOST}:${LEGACY_PATH%/}/$mailbox_path/' '$local_stage_dir/'"
        run_cmd "Preparar destino remoto de $mailbox_path" "$REMOTE_BASE_CMD \"mkdir -p '$remote_domain_dir' '$remote_mailbox_dir' && chown '$MAIL_UID:$MAIL_GID' '$remote_domain_dir' '$remote_mailbox_dir' && chmod 0770 '$remote_domain_dir' '$remote_mailbox_dir'\""
        run_cmd "Enviar $mailbox_path ao host novo" "rsync -a --delete --partial --append-verify -e \"sshpass -e ssh $SSH_OPTS\" '$local_stage_dir/' '${REMOTE_USER}@${REMOTE_HOST}:$remote_mailbox_dir/'"
        run_cmd "Ajustar ownership de $mailbox_path no host novo" "$REMOTE_BASE_CMD \"chown -R '$MAIL_UID:$MAIL_GID' '$remote_mailbox_dir'\""
        run_cmd "Limpar staging de $mailbox_path" "rm -rf '$local_stage_dir'"
    done

    run_cmd "Listar dominios sincronizados no host novo" "$REMOTE_BASE_CMD \"ls -ld '$TARGET_MAIL_ROOT' '$TARGET_MAIL_ROOT'/* 2>/dev/null || true\""
        ;;
esac

info "Concluido: $ACTION"