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
  ./scripts/sync-maildata-to-new-host.sh precheck [--dry-run]
  ./scripts/sync-maildata-to-new-host.sh sync [--dry-run]

Variaveis suportadas:
  DEPLOY_HOST              Host novo da stack de mail (padrao: 10.10.2.30)
  DEPLOY_USER              Usuario SSH do host novo (padrao: root)
  DEPLOY_PORT              Porta SSH do host novo (padrao: 22)
  DEPLOY_PATH              Diretorio remoto do projeto (padrao: /opt/results/infra)
    DEPLOY_SSH_AUTH_MODE     Modo de autenticacao SSH no host novo: auto, password ou key (padrao: auto)
  DEPLOY_SSH_PASSWORD      Senha SSH para acessar o host novo
  SSHPASS                  Alternativa para DEPLOY_SSH_PASSWORD
  LEGACY_PATH              Raiz local do spool legado (padrao: /gv/)
  MAIL_ENV_FILE            Arquivo de ambiente da stack mail no host novo (padrao: .env.remote-10.10.2.30-mail)
  MAIL_PROJECT_NAME        Nome do projeto docker compose da stack mail (padrao: infra-mail)
  MAIL_VOLUME_NAME         Volume Docker que guarda o spool (padrao: <MAIL_PROJECT_NAME>_maildata)
  MAIL_UID                 UID virtual do maildir novo (padrao: 1004)
  MAIL_GID                 GID virtual do maildir novo (padrao: 1004)
  TARGET_MAIL_ROOT         Mountpoint real do spool no host novo; se omitido, resolve via docker volume inspect
  START_FROM_MAILBOX       Retoma o sync a partir de domain/mailbox especifico da lista ordenada

Notas:
  - este script foi feito para rodar no servidor legado
  - o sync envia cada mailbox diretamente para o host novo via rsync sobre SSH
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
REMOTE_AUTH_MODE="${DEPLOY_SSH_AUTH_MODE:-auto}"
MAIL_ENV_FILE="${MAIL_ENV_FILE:-.env.remote-10.10.2.30-mail}"
MAIL_PROJECT_NAME="${MAIL_PROJECT_NAME:-infra-mail}"
MAIL_VOLUME_NAME="${MAIL_VOLUME_NAME:-${MAIL_PROJECT_NAME}_maildata}"
LEGACY_PATH="${LEGACY_PATH:-/gv/}"
MAIL_UID="${MAIL_UID:-1004}"
MAIL_GID="${MAIL_GID:-1004}"
TARGET_MAIL_ROOT="${TARGET_MAIL_ROOT:-}"
START_FROM_MAILBOX="${START_FROM_MAILBOX:-}"

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
command -v rsync >/dev/null 2>&1 || { error "rsync nao encontrado"; exit 1; }

if [ -n "${DEPLOY_SSH_PASSWORD:-}" ] && [ -z "${SSHPASS:-}" ]; then
    export SSHPASS="$DEPLOY_SSH_PASSWORD"
fi

case "$REMOTE_AUTH_MODE" in
    auto|password|key)
        ;;
    *)
        error "DEPLOY_SSH_AUTH_MODE invalido: $REMOTE_AUTH_MODE"
        exit 1
        ;;
esac

HAS_SSHPASS=false
if command -v sshpass >/dev/null 2>&1; then
    HAS_SSHPASS=true
fi

SSH_COMMON_OPTS="-p $REMOTE_PORT -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ConnectTimeout=15"
SSH_PASSWORD_OPTS="$SSH_COMMON_OPTS -o PreferredAuthentications=password,keyboard-interactive -o KbdInteractiveAuthentication=yes -o NumberOfPasswordPrompts=1 -o PubkeyAuthentication=no"
SSH_KEY_OPTS="$SSH_COMMON_OPTS -o PreferredAuthentications=publickey -o PasswordAuthentication=no -o BatchMode=yes"

if [ "$REMOTE_AUTH_MODE" = "auto" ]; then
    if [ "$HAS_SSHPASS" = "true" ] && [ -n "${SSHPASS:-}" ]; then
        REMOTE_AUTH_MODE="password"
    else
        REMOTE_AUTH_MODE="key"
    fi
fi

if [ "$REMOTE_AUTH_MODE" = "password" ]; then
    if [ "$HAS_SSHPASS" != "true" ]; then
        error "sshpass nao encontrado para autenticacao por senha. Use DEPLOY_SSH_AUTH_MODE=key com chave SSH, ou instale sshpass."
        exit 1
    fi

    if [ -z "${SSHPASS:-}" ]; then
        error "Defina DEPLOY_SSH_PASSWORD ou SSHPASS para autenticacao por senha no host novo."
        exit 1
    fi

    SSH_OPTS="$SSH_PASSWORD_OPTS"
    SSH_CMD="sshpass -e ssh $SSH_OPTS"
    RSYNC_RSH="sshpass -e ssh $SSH_OPTS"
else
    SSH_OPTS="$SSH_KEY_OPTS"
    SSH_CMD="ssh $SSH_OPTS"
    RSYNC_RSH="ssh $SSH_OPTS"
    warn "Usando autenticacao por chave SSH para $REMOTE_USER@$REMOTE_HOST"
fi

REMOTE_BASE_CMD="$SSH_CMD ${REMOTE_USER}@${REMOTE_HOST}"

if [ "$REMOTE_AUTH_MODE" = "key" ] && ! $REMOTE_BASE_CMD "true" >/dev/null 2>&1; then
    error "Falha na autenticacao por chave para $REMOTE_USER@$REMOTE_HOST. Instale a chave publica no host novo antes de rodar o sync."
    exit 1
fi

if [ ! -d "$LEGACY_PATH" ]; then
    error "Diretorio legado inexistente: $LEGACY_PATH"
    exit 1
fi

if [ -z "$TARGET_MAIL_ROOT" ]; then
    TARGET_MAIL_ROOT="$($REMOTE_BASE_CMD "docker volume inspect '$MAIL_VOLUME_NAME' --format '{{ .Mountpoint }}'" 2>/dev/null | tr -d '\r')"
fi

if [ -z "$TARGET_MAIL_ROOT" ]; then
    error "Nao foi possivel resolver o mountpoint do volume '$MAIL_VOLUME_NAME' no host novo. Defina TARGET_MAIL_ROOT manualmente."
    exit 1
fi

section "Legacy Mail Sync $ACTION"
info "Executando no legado: $(hostname -f 2>/dev/null || hostname)"
info "Host novo: $REMOTE_HOST"
info "Origem legado: $LEGACY_PATH"
info "Destino novo: $TARGET_MAIL_ROOT"
info "Dry-run: $DRY_RUN"

case "$ACTION" in
    precheck)
        run_cmd "Validar spool local" "test -d '$LEGACY_PATH' && du -sh '$LEGACY_PATH' 2>/dev/null || true"
        run_cmd "Medir maiores mailboxes locais" "find '$LEGACY_PATH' -mindepth 2 -maxdepth 2 -type d -print0 | xargs -0 du -sm 2>/dev/null | sort -n | tail -n 5"
        run_cmd "Validar acesso ao host novo" "$REMOTE_BASE_CMD \"mkdir -p '$TARGET_MAIL_ROOT' && ls -ld '$TARGET_MAIL_ROOT' && df -h '$TARGET_MAIL_ROOT'\""
        run_cmd "Validar rsync no host novo" "$REMOTE_BASE_CMD \"command -v rsync\""
        run_cmd "Validar compose mail no host novo" "$REMOTE_BASE_CMD \"cd '$REMOTE_DIR' && docker compose --env-file '$MAIL_ENV_FILE' -f docker-compose.mail.yml --project-name '$MAIL_PROJECT_NAME' ps\""
        ;;
    sync)
        warn "Este sync envia toda a arvore de Maildir local para o host novo, mailbox por mailbox."
        run_cmd "Preparar destino base no host novo" "$REMOTE_BASE_CMD \"mkdir -p '$TARGET_MAIL_ROOT' && chown '$MAIL_UID:$MAIL_GID' '$TARGET_MAIL_ROOT' && chmod 0770 '$TARGET_MAIL_ROOT'\""

        MAILBOX_LIST="$(find "$LEGACY_PATH" -mindepth 2 -maxdepth 2 -type d -printf '%P\n' | sort)"

        if [ -z "$MAILBOX_LIST" ]; then
            error "Nenhuma mailbox encontrada em $LEGACY_PATH."
            exit 1
        fi

        mapfile -t MAILBOX_PATHS <<< "$MAILBOX_LIST"

        resume_started=true
        if [ -n "$START_FROM_MAILBOX" ]; then
            info "Retomando a partir de $START_FROM_MAILBOX"
            resume_started=false
        fi

        for mailbox_path in "${MAILBOX_PATHS[@]}"; do
            [ -n "$mailbox_path" ] || continue

            if [ "$resume_started" = "false" ]; then
                if [ "$mailbox_path" = "$START_FROM_MAILBOX" ]; then
                    resume_started=true
                else
                    continue
                fi
            fi

            domain="${mailbox_path%%/*}"
            mailbox="${mailbox_path#*/}"
            local_mailbox_dir="${LEGACY_PATH%/}/$mailbox_path"
            remote_domain_dir="$TARGET_MAIL_ROOT/$domain"
            remote_mailbox_dir="$remote_domain_dir/$mailbox"

            info "Sincronizando $mailbox_path"

            run_cmd "Preparar destino remoto de $mailbox_path" "$REMOTE_BASE_CMD \"mkdir -p '$remote_domain_dir' '$remote_mailbox_dir' && chown '$MAIL_UID:$MAIL_GID' '$remote_domain_dir' '$remote_mailbox_dir' && chmod 0770 '$remote_domain_dir' '$remote_mailbox_dir'\""
            run_cmd "Enviar $mailbox_path ao host novo" "rsync -a --delete --partial --append-verify -e \"$RSYNC_RSH\" '$local_mailbox_dir/' '${REMOTE_USER}@${REMOTE_HOST}:$remote_mailbox_dir/'"
            run_cmd "Ajustar ownership de $mailbox_path no host novo" "$REMOTE_BASE_CMD \"chown -R '$MAIL_UID:$MAIL_GID' '$remote_mailbox_dir'\""
        done

        if [ -n "$START_FROM_MAILBOX" ] && [ "$resume_started" = "false" ]; then
            error "Mailbox inicial nao encontrada na lista ordenada: $START_FROM_MAILBOX"
            exit 1
        fi

        run_cmd "Listar dominios sincronizados no host novo" "$REMOTE_BASE_CMD \"ls -ld '$TARGET_MAIL_ROOT' '$TARGET_MAIL_ROOT'/* 2>/dev/null || true\""
        ;;
esac

info "Concluido: $ACTION"