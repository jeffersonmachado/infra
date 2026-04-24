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

validate_remote_webmail_config() {
    local validate_container="${1:-true}"
    local host_config_strict="${WEBMAIL_HOST_CONFIG_STRICT:-false}"

    if [ "$DEPLOY_PROJECT_NAME" != "infra-httpd" ]; then
        return 0
    fi

    local local_config="$ROOT_DIR/joomla-site/webmail/config/config.inc.php"
    if [ ! -r "$local_config" ]; then
        warn "Config do Roundcube nao encontrado localmente; validacao de webmail ignorada"
        return 0
    fi

    local local_md5
    local_md5=$(md5sum "$local_config" | awk '{print $1}')
    capture_ssh_cmd "md5sum '$REMOTE_DIR/joomla-site/webmail/config/config.inc.php' | cut -d ' ' -f1" "Validando checksum remoto do config do Roundcube"
    if [ "$DRY_RUN" != "true" ] && [ "$CAPTURED_OUTPUT" != "$local_md5" ]; then
        if [ "$host_config_strict" = "true" ]; then
            error "Checksum remoto do config do Roundcube diverge do repositorio"
            echo "Local:  $local_md5"
            echo "Remoto: $CAPTURED_OUTPUT"
            exit 1
        fi

        warn "Checksum do config no host remoto diverge do repositorio; validacao no container seguira"
        echo "Local:  $local_md5"
        echo "Remoto: $CAPTURED_OUTPUT"
    fi

    if [ "$validate_container" != "true" ]; then
        info "✅ Config do Roundcube validado no host remoto"
        return 0
    fi

    capture_ssh_cmd "docker exec results-joomla md5sum /var/www/html/results/webmail/config/config.inc.php | cut -d ' ' -f1" "Validando checksum montado no container Joomla"
    if [ "$DRY_RUN" != "true" ] && [ "$CAPTURED_OUTPUT" != "$local_md5" ]; then
        error "Checksum do config do Roundcube dentro do container diverge do repositorio"
        echo "Local:     $local_md5"
        echo "Container: $CAPTURED_OUTPUT"
        exit 1
    fi

    info "✅ Config do Roundcube validado no host e no container"
}

run_remote_webmail_safeguards() {
    if [ "$DEPLOY_PROJECT_NAME" != "infra-httpd" ]; then
        return 0
    fi

    local webmail_mail_env_file="${WEBMAIL_MAIL_ENV_FILE:-.env.remote-${REMOTE_HOST}-mail}"

    run_ssh_cmd "cd '$REMOTE_DIR' && sh ./scripts/normalize-roundcube-special-folders.sh" "Normalizando pastas especiais do Roundcube/Dovecot"
    run_ssh_cmd "cd '$REMOTE_DIR' && sh ./scripts/test-webmail-login.sh" "Executando smoke test da pagina de login do webmail"
    if [ "$DRY_RUN" != "true" ]; then
        capture_ssh_cmd "test -r '$REMOTE_DIR/$webmail_mail_env_file' && echo ok || true" "Verificando arquivo de ambiente do mail para teste temporario"
        if [ "$CAPTURED_OUTPUT" = "ok" ]; then
            run_ssh_cmd "cd '$REMOTE_DIR' && WEBMAIL_MAIL_ENV_FILE='$webmail_mail_env_file' sh ./scripts/test-webmail-temporary-auth.sh" "Executando validacao temporaria SQL e LDAP do webmail"
        else
            warn "Arquivo de ambiente do mail nao encontrado para validacao temporaria: $webmail_mail_env_file"
        fi
    fi

    if [ -n "${WEBMAIL_TEST_USER:-}" ] && [ -n "${WEBMAIL_TEST_PASSWORD:-}" ]; then
        run_ssh_cmd "cd '$REMOTE_DIR' && WEBMAIL_USER='${WEBMAIL_TEST_USER}' WEBMAIL_PASSWORD='${WEBMAIL_TEST_PASSWORD}' sh ./scripts/test-webmail-login.sh" "Executando smoke test autenticado do webmail"
    else
        warn "WEBMAIL_TEST_USER/WEBMAIL_TEST_PASSWORD nao definidos; smoke test autenticado foi ignorado"
    fi
}

run_local_webmail_public_gate() {
    if [ "$DEPLOY_PROJECT_NAME" != "infra-httpd" ]; then
        return 0
    fi

    if [ "${WEBMAIL_GATE_PUBLIC:-false}" != "true" ]; then
        return 0
    fi

    local gate_url="${WEBMAIL_GATE_URL:-https://www.results.com.br/webmail/}"
    local gate_host="${WEBMAIL_GATE_HOST:-www.results.com.br}"
    local gate_ip="${WEBMAIL_GATE_IP:-$REMOTE_HOST}"

    info "Executando gate publico de webmail (${gate_url})"
    WEBMAIL_URL="$gate_url" \
    WEBMAIL_RESOLVE_HOST="$gate_host" \
    WEBMAIL_RESOLVE_IP="$gate_ip" \
    WEBMAIL_USER="${WEBMAIL_TEST_USER:-}" \
    WEBMAIL_PASSWORD="${WEBMAIL_TEST_PASSWORD:-}" \
    sh "$ROOT_DIR/scripts/test-webmail-login.sh"
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
DEPLOY_SKIP_PING="${DEPLOY_SKIP_PING:-false}"
DEPLOY_ENV_FILE="${DEPLOY_ENV_FILE:-.env}"
DEPLOY_ENV_BASENAME="$(basename "$DEPLOY_ENV_FILE")"
REMOTE_ENV_FILE="$DEPLOY_ENV_BASENAME"
DEPLOY_PROJECT_NAME="${DEPLOY_PROJECT_NAME:-infra-httpd}"
DEPLOY_COMPOSE_FILE="${DEPLOY_COMPOSE_FILE:-docker-compose.yml}"
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
echo "  Arquivo compose: $DEPLOY_COMPOSE_FILE"
echo "  Arquivo de ambiente: $DEPLOY_ENV_FILE"
echo "  Dry-run: $DRY_RUN"

section "Pre-check Local"
command -v ssh >/dev/null 2>&1 || { error "ssh nao encontrado"; exit 1; }
command -v rsync >/dev/null 2>&1 || { error "rsync nao encontrado"; exit 1; }
command -v npm >/dev/null 2>&1 || { error "npm nao encontrado"; exit 1; }
info "✅ Pre-check local concluido"

section "Validação de Rede e SSH"
if [ "$DEPLOY_SKIP_PING" = "true" ]; then
    warn "Teste de ping ignorado (DEPLOY_SKIP_PING=true); validacao seguira via SSH"
else
    run_cmd "ping -c 1 -W 2 $REMOTE_HOST" "Testando conectividade de rede"
fi

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

validate_remote_webmail_config false

section "Aplicando Docker Compose"
REMOTE_DEPLOY_CMD="set -e && mkdir -p '$REMOTE_DIR' && cd '$REMOTE_DIR' && test -f '$DEPLOY_COMPOSE_FILE' && test -f '$REMOTE_ENV_FILE' && command -v docker >/dev/null && docker compose version >/dev/null && docker compose --env-file '$REMOTE_ENV_FILE' -f '$DEPLOY_COMPOSE_FILE' --project-name '$DEPLOY_PROJECT_NAME' config >/dev/null && docker compose --env-file '$REMOTE_ENV_FILE' -f '$DEPLOY_COMPOSE_FILE' --project-name '$DEPLOY_PROJECT_NAME' up -d --build && if [ '$DEPLOY_PROJECT_NAME' = 'infra-mail' ] && command -v rc-service >/dev/null 2>&1; then rc-service fail2ban restart >/dev/null 2>&1 || rc-service fail2ban start >/dev/null 2>&1 || true; fi && docker compose --env-file '$REMOTE_ENV_FILE' -f '$DEPLOY_COMPOSE_FILE' --project-name '$DEPLOY_PROJECT_NAME' ps"
run_ssh_cmd "$REMOTE_DEPLOY_CMD" "Executando docker compose remoto"

validate_remote_webmail_config
run_remote_webmail_safeguards
run_local_webmail_public_gate

info "✅ Deploy concluido para ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_DIR}"