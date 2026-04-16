#!/bin/bash

set -e

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
    echo -e "${RED}[ERROR]${NC} $1"
}

section() {
    echo -e "\n${BLUE}=== $1 ===${NC}\n"
}

show_cmd() {
    local prefix="$1"
    local description="$2"
    local cmd="$3"
    echo -e "${YELLOW}[${prefix}]${NC} $description"
    echo -e "${YELLOW}[${prefix}]${NC} $cmd"
    echo ""
}

run_cmd() {
    local cmd="$1"
    local description="$2"
    show_cmd "COMANDO" "$description" "$cmd"

    if [ "$DRY_RUN" = "true" ]; then
        return 0
    fi

    eval "$cmd"
}

run_ssh_cmd() {
    local remote_cmd="$1"
    local description="$2"
    local full_cmd="$SSH_CMD ${REMOTE_USER}@${REMOTE_HOST} \"$remote_cmd\""

    show_cmd "COMANDO SSH" "$description" "$full_cmd"
    echo -e "${YELLOW}[COMANDO REMOTO]${NC} $remote_cmd"
    echo ""

    if [ "$DRY_RUN" = "true" ]; then
        return 0
    fi

    eval "$full_cmd" 2>&1 | grep -vE "(Warning|Permanently)" || true
    local exit_code=${PIPESTATUS[0]}
    return $exit_code
}

capture_ssh_cmd() {
    local remote_cmd="$1"
    local description="$2"
    local full_cmd="$SSH_CMD ${REMOTE_USER}@${REMOTE_HOST} \"$remote_cmd\""

    show_cmd "COMANDO SSH" "$description" "$full_cmd"
    echo -e "${YELLOW}[COMANDO REMOTO]${NC} $remote_cmd"
    echo ""

    if [ "$DRY_RUN" = "true" ]; then
        CAPTURED_OUTPUT=""
        CAPTURED_EXIT=0
        return 0
    fi

    local raw_output
    raw_output=$(eval "$full_cmd" 2>&1)
    CAPTURED_EXIT=$?
    CAPTURED_OUTPUT=$(echo "$raw_output" | grep -vE "(Warning|Permanently)" || true)

    if [ -n "$CAPTURED_OUTPUT" ]; then
        echo "$CAPTURED_OUTPUT"
    fi

    return $CAPTURED_EXIT
}

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$ROOT_DIR"

DRY_RUN=false
DEPLOY_HOST="${DEPLOY_HOST:-10.10.2.30}"
REMOTE_HOST="$DEPLOY_HOST"
REMOTE_USER="${DEPLOY_USER:-root}"
REMOTE_PORT="${DEPLOY_PORT:-22}"
REMOTE_DIR="${DEPLOY_PATH:-/opt/results/infra}"
DEPLOY_ENV_FILE="${DEPLOY_ENV_FILE:-.env}"
DEPLOY_ENV_BASENAME="$(basename "$DEPLOY_ENV_FILE")"
DEPLOY_PROJECT_NAME="${DEPLOY_PROJECT_NAME:-infra-httpd}"
USE_SSH_DIRECT="${DEPLOY_USE_SSH_DIRECT:-false}"
SSH_PASSWORD="${SSH_PASSWORD:-${DEPLOY_SSH_PASSWORD:-}}"
SSH_PASSWORD_FILE="${DEPLOY_SSH_PASSWORD_FILE:-}"
SSH_KEY_PATH="${DEPLOY_SSH_KEY:-}"

while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --host)
            REMOTE_HOST="$2"
            shift 2
            ;;
        --user)
            REMOTE_USER="$2"
            shift 2
            ;;
        --port)
            REMOTE_PORT="$2"
            shift 2
            ;;
        --dir)
            REMOTE_DIR="$2"
            shift 2
            ;;
        --password)
            SSH_PASSWORD="$2"
            shift 2
            ;;
        --password-file)
            SSH_PASSWORD_FILE="$2"
            shift 2
            ;;
        *)
            error "Argumento desconhecido: $1"
            echo "Uso: $0 [--dry-run] [--host HOST] [--user USER] [--port PORT] [--dir DIR] [--password PASSWORD] [--password-file FILE]"
            exit 1
            ;;
    esac
done

if [ ! -r "$DEPLOY_ENV_FILE" ]; then
    error "Arquivo de ambiente nao encontrado ou sem leitura: $ROOT_DIR/$DEPLOY_ENV_FILE"
    error "Crie .env a partir de .env.example antes do deploy remoto."
    exit 1
fi

if [ -n "$SSH_PASSWORD" ] && [ -n "$SSH_PASSWORD_FILE" ]; then
    error "Use apenas uma das variaveis: DEPLOY_SSH_PASSWORD/SSH_PASSWORD/SSHPASS ou DEPLOY_SSH_PASSWORD_FILE."
    exit 1
fi

SSH_OPTS="-p $REMOTE_PORT -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ConnectTimeout=30 -o ServerAliveInterval=30 -o ServerAliveCountMax=20 -o TCPKeepAlive=yes"

if [ -n "$SSH_KEY_PATH" ]; then
    SSH_OPTS="$SSH_OPTS -i $SSH_KEY_PATH"
fi

if [ "$USE_SSH_DIRECT" = "true" ]; then
    info "Modo SSH direto habilitado (sem sshpass)."
    SSH_CMD="ssh $SSH_OPTS"
    RSYNC_SSH_TRANSPORT="ssh $SSH_OPTS"
else
    if [ -z "$SSHPASS" ] && [ -n "$SSH_PASSWORD" ]; then
        export SSHPASS="$SSH_PASSWORD"
    fi

    if [ -n "$SSH_PASSWORD_FILE" ]; then
        if ! command -v sshpass >/dev/null 2>&1; then
            error "sshpass nao esta instalado. Instale com: apt-get install sshpass ou yum install sshpass"
            exit 1
        fi
        SSH_CMD="sshpass -f $SSH_PASSWORD_FILE ssh $SSH_OPTS"
        RSYNC_SSH_TRANSPORT="sshpass -f $SSH_PASSWORD_FILE ssh $SSH_OPTS"
    elif [ -n "$SSHPASS" ]; then
        if ! command -v sshpass >/dev/null 2>&1; then
            error "sshpass nao esta instalado. Instale com: apt-get install sshpass ou yum install sshpass"
            error "Ou use DEPLOY_USE_SSH_DIRECT=true para autenticacao SSH padrao."
            exit 1
        fi
        export SSHPASS
        SSH_CMD="sshpass -e ssh $SSH_OPTS"
        RSYNC_SSH_TRANSPORT="sshpass -e ssh $SSH_OPTS"
    elif [ -n "$SSH_KEY_PATH" ]; then
        SSH_CMD="ssh $SSH_OPTS"
        RSYNC_SSH_TRANSPORT="ssh $SSH_OPTS"
    else
        error "Defina SSHPASS, SSH_PASSWORD, DEPLOY_SSH_PASSWORD, DEPLOY_SSH_PASSWORD_FILE, DEPLOY_SSH_KEY ou DEPLOY_USE_SSH_DIRECT=true."
        exit 1
    fi
fi

section "Deploy HTTPD Remoto"
info "Configuração:"
echo "  Host: $REMOTE_HOST"
echo "  User: $REMOTE_USER"
echo "  Porta SSH: $REMOTE_PORT"
echo "  Diretório: $REMOTE_DIR"
echo "  Projeto compose: $DEPLOY_PROJECT_NAME"
echo "  Arquivo de ambiente: $DEPLOY_ENV_FILE"
echo "  Dry-run: $DRY_RUN"

section "Pre-check Local"
command -v ssh >/dev/null 2>&1 || { error "ssh nao encontrado"; exit 1; }
command -v rsync >/dev/null 2>&1 || { error "rsync nao encontrado"; exit 1; }
command -v npm >/dev/null 2>&1 || { error "npm nao encontrado"; exit 1; }
info "✅ Pre-check local concluido"

section "Validação de Rede e SSH"
run_cmd "ping -c 1 -W 2 $REMOTE_HOST" "Testando conectividade de rede"

capture_ssh_cmd "echo 'Conexao OK'" "Testando conexao SSH"
if [ "$DRY_RUN" != "true" ] && ! echo "$CAPTURED_OUTPUT" | grep -q "Conexao OK"; then
    error "Falha no SSH para ${REMOTE_USER}@${REMOTE_HOST}"
    exit 1
fi
info "✅ SSH remoto validado"

section "Validação de Docker Remoto"
capture_ssh_cmd "docker --version" "Verificando Docker remoto"
if [ "$DRY_RUN" != "true" ] && ! echo "$CAPTURED_OUTPUT" | grep -q "Docker version"; then
    error "Docker nao esta disponivel no servidor remoto"
    exit 1
fi

capture_ssh_cmd "docker compose version" "Verificando Docker Compose remoto"
if [ "$DRY_RUN" != "true" ] && ! echo "$CAPTURED_OUTPUT" | grep -qi "docker compose"; then
    error "docker compose nao esta disponivel no servidor remoto"
    exit 1
fi

section "Preparando Diretório Remoto"
run_ssh_cmd "mkdir -p '$REMOTE_DIR'" "Criando diretório remoto"

section "Sincronizando Arquivos"
RSYNC_CMD="rsync -az --progress --delete --exclude .git/ --exclude node_modules/ --exclude .env --exclude .env.local -e '$RSYNC_SSH_TRANSPORT' '$ROOT_DIR/' '${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_DIR}/'"
run_cmd "$RSYNC_CMD" "Sincronizando workspace para o host remoto"

section "Aplicando Docker Compose"
REMOTE_DEPLOY_CMD="set -e && mkdir -p '$REMOTE_DIR' && cd '$REMOTE_DIR' && $( [ "$DEPLOY_ENV_BASENAME" = ".env" ] && echo "true" || echo "cp '$DEPLOY_ENV_BASENAME' .env" ) && command -v docker >/dev/null && docker compose version >/dev/null && docker compose --project-name '$DEPLOY_PROJECT_NAME' config >/dev/null && docker compose --project-name '$DEPLOY_PROJECT_NAME' up -d --build && docker compose --project-name '$DEPLOY_PROJECT_NAME' ps"
run_ssh_cmd "$REMOTE_DEPLOY_CMD" "Executando docker compose remoto"

info "✅ Deploy concluido para ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_DIR}"