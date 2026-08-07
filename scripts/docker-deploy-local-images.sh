#!/bin/bash
#
# docker-deploy-local-images.sh
#
# Deploy com build LOCAL das imagens Docker e transferência via save/load
# para o servidor remoto — sem build no servidor de produção.
#
# Padrão r-agent2: build local → docker save → rsync → docker load → compose up
#
# Uso:
#   ./scripts/docker-deploy-local-images.sh                           # stack web (default)
#   ./scripts/docker-deploy-local-images.sh --mail                     # stack mail
#   ./scripts/docker-deploy-local-images.sh --compose docker-compose.mail.yml --project infra-mail
#   ./scripts/docker-deploy-local-images.sh --dry-run
#   ./scripts/docker-deploy-local-images.sh --no-build                 # usar imagens já existentes
#
# Variáveis de ambiente:
#   SSH_PASSWORD             obrigatório (ou SSH_PASSWORD_FILE / SSH_KEY_PATH)
#   DEPLOY_HOST              default: 10.10.2.30
#   DEPLOY_USER              default: root
#   DEPLOY_PATH              default: /opt/results/infra
#   DEPLOY_COMPOSE_FILE      compose file a usar
#   DEPLOY_PROJECT_NAME      nome do projeto compose
#   DEPLOY_ENV_FILE          arquivo .env (default: .env ou .env.mail)

set -euo pipefail

# ── Cores ──────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

info()  { echo -e "${GREEN}[INFO]${NC}  $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC}  $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; }
section() { echo -e "\n${BLUE}=== $1 ===${NC}\n"; }

# ── Diretório raiz ─────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$ROOT_DIR"

# ── Defaults ───────────────────────────────────────────────────────
DRY_RUN=false
BUILD=true
DEPLOY_HOST="${DEPLOY_HOST:-10.10.2.30}"
REMOTE_HOST="$DEPLOY_HOST"
REMOTE_USER="${DEPLOY_USER:-root}"
REMOTE_PORT="${DEPLOY_PORT:-22}"
REMOTE_DIR="${DEPLOY_PATH:-/opt/results/infra}"
DEPLOY_COMPOSE_FILE="${DEPLOY_COMPOSE_FILE:-docker-compose.yml}"
DEPLOY_PROJECT_NAME="${DEPLOY_PROJECT_NAME:-infra-httpd}"
DEPLOY_ENV_FILE="${DEPLOY_ENV_FILE:-.env}"
DEPLOY_SKIP_PING="${DEPLOY_SKIP_PING:-false}"
SKIP_SMOKE="${SKIP_SMOKE:-false}"
FORCE="${FORCE:-false}"
SSH_PASSWORD="${SSH_PASSWORD:-${DEPLOY_SSH_PASSWORD:-}}"
SSH_PASSWORD_FILE="${DEPLOY_SSH_PASSWORD_FILE:-}"
SSH_KEY_PATH="${DEPLOY_SSH_KEY:-}"
TEMP_DIR="/tmp/infra-deploy-$$"

# ── Processa argumentos ────────────────────────────────────────────
while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN=true; shift ;;
        --no-build)
            BUILD=false; shift ;;
        --mail)
            DEPLOY_COMPOSE_FILE="docker-compose.mail.yml"
            DEPLOY_PROJECT_NAME="infra-mail"
            DEPLOY_ENV_FILE="${DEPLOY_ENV_FILE:-.env.mail}"
            shift ;;
        --compose)
            DEPLOY_COMPOSE_FILE="$2"; shift 2 ;;
        --project)
            DEPLOY_PROJECT_NAME="$2"; shift 2 ;;
        --env-file)
            DEPLOY_ENV_FILE="$2"; shift 2 ;;
        --host)
            REMOTE_HOST="$2"; shift 2 ;;
        --user)
            REMOTE_USER="$2"; shift 2 ;;
        --port)
            REMOTE_PORT="$2"; shift 2 ;;
        --dir)
            REMOTE_DIR="$2"; shift 2 ;;
        --password)
            SSH_PASSWORD="$2"; shift 2 ;;
        --password-file)
            SSH_PASSWORD_FILE="$2"; shift 2 ;;
        --skip-smoke)
            SKIP_SMOKE=true; shift ;;
        --force)
            FORCE=true; shift ;;
        *)
            error "Argumento desconhecido: $1"
            echo "Uso: $0 [--mail] [--compose FILE] [--project NAME] [--env-file FILE]"
            echo "         [--no-build] [--dry-run] [--skip-smoke] [--force]"
            echo "         [--host HOST] [--user USER] [--port PORT] [--dir DIR]"
            echo "         [--password PASSWORD] [--password-file FILE]"
            exit 1 ;;
    esac
done

# ── Validação do compose file ──────────────────────────────────────
if [ ! -r "$DEPLOY_COMPOSE_FILE" ]; then
    error "Arquivo compose nao encontrado: $ROOT_DIR/$DEPLOY_COMPOSE_FILE"
    exit 1
fi

if [ ! -r "$DEPLOY_ENV_FILE" ]; then
    error "Arquivo de ambiente nao encontrado: $ROOT_DIR/$DEPLOY_ENV_FILE"
    error "Crie $DEPLOY_ENV_FILE a partir do .example correspondente."
    exit 1
fi

# ── Configura SSH ──────────────────────────────────────────────────
SSH_OPTS="-p $REMOTE_PORT -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ConnectTimeout=30 -o ServerAliveInterval=30 -o ServerAliveCountMax=20 -o TCPKeepAlive=yes"

if [ -n "$SSH_KEY_PATH" ]; then
    SSH_OPTS="$SSH_OPTS -i $SSH_KEY_PATH"
fi

setup_ssh() {
    if [ -n "$SSH_PASSWORD" ] && [ -n "$SSH_PASSWORD_FILE" ]; then
        error "Use apenas SSH_PASSWORD ou SSH_PASSWORD_FILE, nao ambos."
        exit 1
    fi

    if [ -n "$SSH_PASSWORD_FILE" ]; then
        if [ ! -r "$SSH_PASSWORD_FILE" ]; then
            error "Arquivo de senha nao encontrado: $SSH_PASSWORD_FILE"
            exit 1
        fi
        SSH_PASSWORD="$(cat "$SSH_PASSWORD_FILE")"
    fi

    if [ -n "$SSH_PASSWORD" ]; then
        if ! command -v sshpass >/dev/null 2>&1; then
            error "sshpass nao encontrado. Instale: apt-get install sshpass"
            exit 1
        fi
        export SSHPASS="$SSH_PASSWORD"
        SSH_CMD="sshpass -e ssh $SSH_OPTS"
        RSYNC_SSH="sshpass -e ssh $SSH_OPTS"
    elif [ -n "$SSH_KEY_PATH" ]; then
        SSH_CMD="ssh $SSH_OPTS"
        RSYNC_SSH="ssh $SSH_OPTS"
    else
        error "Defina SSH_PASSWORD, SSH_PASSWORD_FILE ou SSH_KEY_PATH."
        exit 1
    fi
}

# ── Helpers ────────────────────────────────────────────────────────
run_cmd() {
    local cmd="$1"; local desc="$2"
    echo -e "${YELLOW}[CMD]${NC} $desc"
    echo -e "${YELLOW}[CMD]${NC} $cmd"
    echo ""
    if [ "$DRY_RUN" = "true" ]; then return 0; fi
    eval "$cmd"
}

run_ssh() {
    local remote_cmd="$1"; local desc="$2"
    echo -e "${YELLOW}[SSH]${NC} $desc"
    echo -e "${YELLOW}[SSH]${NC} $SSH_CMD ${REMOTE_USER}@${REMOTE_HOST} \"$remote_cmd\""
    echo ""
    if [ "$DRY_RUN" = "true" ]; then return 0; fi
    eval "$SSH_CMD ${REMOTE_USER}@${REMOTE_HOST} \"$remote_cmd\"" 2>&1 | grep -vE "(Warning|Permanently)" || true
}

capture_ssh() {
    local remote_cmd="$1"; local desc="$2"
    echo -e "${YELLOW}[SSH]${NC} $desc"
    echo ""
    if [ "$DRY_RUN" = "true" ]; then
        CAPTURED=""; return 0
    fi
    CAPTURED=$(eval "$SSH_CMD ${REMOTE_USER}@${REMOTE_HOST} \"$remote_cmd\"" 2>&1 | grep -vE "(Warning|Permanently)" || true)
    if [ -n "$CAPTURED" ]; then echo "$CAPTURED"; fi
}

# ── Extrai nomes de imagens do compose ─────────────────────────────
extract_images() {
    # Extrai serviços que têm "build:" (não apenas "image:")
    # Retorna no formato: serviço:tag (ou serviço:latest para os sem tag explícita)
    docker compose -f "$DEPLOY_COMPOSE_FILE" config 2>/dev/null \
        | python3 -c "
import sys, json
config = json.load(sys.stdin)
services = config.get('services', {})
images = []
for svc_name, svc in services.items():
    if 'build' in svc:
        img = svc.get('image', f'{svc_name}:latest')
        images.append(img)
for img in sorted(set(images)):
    print(img)
" 2>/dev/null
}

# ── Início ─────────────────────────────────────────────────────────
setup_ssh

section "Deploy com Build Local: $DEPLOY_PROJECT_NAME"
info "Configuração:"
echo "  Compose:      $DEPLOY_COMPOSE_FILE"
echo "  Projeto:      $DEPLOY_PROJECT_NAME"
echo "  Env file:     $DEPLOY_ENV_FILE"
echo "  Host:         $REMOTE_HOST"
echo "  Usuario:      $REMOTE_USER"
echo "  Diretorio:    $REMOTE_DIR"
echo "  Build local:  $BUILD"
echo "  Force:        $FORCE"
echo "  Dry-run:      $DRY_RUN"

# ── Pre-check ──────────────────────────────────────────────────────
section "Pre-check Local"
command -v ssh >/dev/null 2>&1    || { error "ssh nao encontrado"; exit 1; }
command -v rsync >/dev/null 2>&1  || { error "rsync nao encontrado"; exit 1; }
command -v docker >/dev/null 2>&1 || { error "docker nao encontrado"; exit 1; }
info "✅ Ferramentas locais OK"

# ── Conexão ────────────────────────────────────────────────────────
section "Validacao de Conexao"
if [ "$DEPLOY_SKIP_PING" != "true" ]; then
    run_cmd "ping -c 1 -W 2 $REMOTE_HOST" "Testando ping"
fi

capture_ssh "echo 'OK'" "Testando SSH"
if [ "$DRY_RUN" != "true" ] && ! echo "$CAPTURED" | grep -q "OK"; then
    error "Falha no SSH para ${REMOTE_USER}@${REMOTE_HOST}"
    exit 1
fi
info "✅ SSH OK"

capture_ssh "docker --version && docker compose version" "Verificando Docker remoto"
if [ "$DRY_RUN" != "true" ]; then
    if ! echo "$CAPTURED" | grep -q "Docker version"; then
        error "Docker nao disponivel no remoto"
        exit 1
    fi
fi
info "✅ Docker remoto OK"

# ── Rede infra-shared ──────────────────────────────────────────────
section "Validando Rede infra-shared"
capture_ssh "docker network inspect infra-shared --format='{{.Name}}' 2>/dev/null || true" \
    "Verificando rede infra-shared no remoto"
if [ "$DRY_RUN" != "true" ]; then
    if echo "$CAPTURED" | grep -q "infra-shared"; then
        info "✅ Rede infra-shared existe"
    else
        warn "Rede infra-shared nao encontrada — criando..."
        run_ssh "docker network create infra-shared 2>&1" "Criando rede infra-shared"
        info "✅ Rede infra-shared criada"
    fi
fi

# ── Build local ────────────────────────────────────────────────────
if [ "$BUILD" = "true" ]; then
    section "Build Local das Imagens"
    run_cmd "docker compose -f '$DEPLOY_COMPOSE_FILE' --env-file '$DEPLOY_ENV_FILE' build --pull" \
        "Construindo imagens localmente"
    info "✅ Build local concluido"
else
    section "Build Local (pulado via --no-build)"
    warn "Usando imagens locais ja existentes"
fi

# ── Extrai lista de imagens ────────────────────────────────────────
section "Identificando Imagens"
IMAGES=()
while IFS= read -r img; do
    [ -z "$img" ] && continue
    IMAGES+=("$img")
done < <(extract_images)

if [ ${#IMAGES[@]} -eq 0 ]; then
    error "Nenhum servico com 'build:' encontrado em $DEPLOY_COMPOSE_FILE"
    error "Este compose so usa imagens pre-construidas. Use docker-deploy.sh tradicional."
    exit 1
fi

declare -A LOCAL_IDS
info "Imagens locais (${#IMAGES[@]}):"
for img in "${IMAGES[@]}"; do
    if docker image inspect "$img" &>/dev/null 2>&1; then
        LOCAL_IDS["$img"]=$(docker image inspect "$img" --format='{{.ID}}')
        SIZE=$(docker image inspect "$img" --format='{{.Size}}' | numfmt --to=iec-i --suffix=B 2>/dev/null || echo "?")
        echo "  ✅ $img → ${LOCAL_IDS[$img]:0:12} ($SIZE)"
    else
        error "  ❌ $img nao encontrada localmente"
        error "Execute o build primeiro: docker compose -f $DEPLOY_COMPOSE_FILE build"
        exit 1
    fi
done

# ── Compara com IDs remotos (delta detection a prova de falso positivo) ─
section "Comparando com Imagens Remotas"
declare -A REMOTE_IDS
declare -A NEEDS_TRANSFER
STALE_COUNT=0
SYNCED_COUNT=0

if [ "$FORCE" = "true" ]; then
    warn "Modo --force: todas as imagens serao transferidas (delta detection ignorado)"
    for img in "${IMAGES[@]}"; do
        NEEDS_TRANSFER["$img"]=1
        STALE_COUNT=$((STALE_COUNT + 1))
    done
else

# Script remoto que retorna: EXIT_CODE\nIMAGE_ID (ou erro)
# Exit code 0 = imagem existe, ID na stdout
# Exit code 1 = imagem nao existe (No such image)
# Exit code outro = erro inesperado (docker daemon fora, etc.)
REMOTE_INSPECT_SCRIPT='
img="$1"
output=$(docker image inspect "$img" --format="{{.ID}}" 2>&1) || {
    rc=$?
    if echo "$output" | grep -q "No such image"; then
        echo "RC:1"
    else
        echo "RC:${rc}"
        echo "ERR:$output" >&2
    fi
    exit 0
}
echo "RC:0"
echo "ID:$output"
'

for img in "${IMAGES[@]}"; do
    local_id="${LOCAL_IDS[$img]}"

    remote_id=""
    remote_rc=""
    remote_err=""

    if [ "$DRY_RUN" != "true" ]; then
        # Envia script via stdin para evitar escaping de aspas
        raw=$(printf '%s' "$REMOTE_INSPECT_SCRIPT" | \
            eval "$SSH_CMD ${REMOTE_USER}@${REMOTE_HOST} \"bash -s -- '$img'\"" 2>/dev/null) || true

        # Extrai exit code e ID das linhas marcadas
        remote_rc=$(echo "$raw" | grep "^RC:" | head -1 | cut -d: -f2)
        remote_id=$(echo "$raw" | grep "^ID:" | head -1 | cut -d: -f2-)
        remote_err=$(echo "$raw" | grep "^ERR:" | head -1 | cut -d: -f2-)
    fi

    REMOTE_IDS["$img"]="$remote_id"

    if [ "$remote_rc" = "0" ] && [ -n "$remote_id" ]; then
        # Imagem existe no remoto
        if [ "$remote_id" = "$local_id" ]; then
            info "  ✅ $img → em sincronia (${local_id:0:12})"
            NEEDS_TRANSFER["$img"]=0
            SYNCED_COUNT=$((SYNCED_COUNT + 1))
        else
            info "  🔄 $img → desatualizado (local: ${local_id:0:12}, remoto: ${remote_id:0:12})"
            NEEDS_TRANSFER["$img"]=1
            STALE_COUNT=$((STALE_COUNT + 1))
        fi
    elif [ "$remote_rc" = "1" ]; then
        # Imagem nao existe — esperado, precisa transferir
        info "  📦 $img → nao existe no remoto (nova)"
        NEEDS_TRANSFER["$img"]=1
        STALE_COUNT=$((STALE_COUNT + 1))
    else
        # Erro inesperado (SSH caiu? docker daemon parou?)
        error "  ❌ $img → falha ao consultar remoto (rc=${remote_rc:-?})"
        if [ -n "$remote_err" ]; then
            error "     Detalhe: $remote_err"
        fi
        error "Abortando por seguranca — corrija o problema e tente novamente."
        exit 1
    fi
done
fi  # fim do if FORCE/else

echo ""
info "Resumo: $STALE_COUNT a transferir, $SYNCED_COUNT em sincronia, ${#IMAGES[@]} total"

# ── Save (apenas imagens desatualizadas) ───────────────────────────
mkdir -p "$TEMP_DIR"
trap "rm -rf $TEMP_DIR" EXIT

declare -A IMAGE_FILES
SAVED_COUNT=0

if [ "$STALE_COUNT" -gt 0 ]; then
    section "Salvando Imagens Desatualizadas (docker save)"

    for img in "${IMAGES[@]}"; do
        [ "${NEEDS_TRANSFER[$img]}" = "1" ] || continue

        SAVED_COUNT=$((SAVED_COUNT + 1))
        FILENAME=$(echo "$img" | tr '/:' '_')
        IMAGE_FILES["$img"]="${FILENAME}.tar"
        TAR_PATH="$TEMP_DIR/${FILENAME}.tar"

        info "[$SAVED_COUNT/$STALE_COUNT] Salvando $img → ${FILENAME}.tar"

        if [ "$DRY_RUN" = "true" ]; then continue; fi

        docker save "$img" -o "$TAR_PATH"

        SIZE=$(du -h "$TAR_PATH" | cut -f1)
        info "  ✅ Salvo ($SIZE)"
    done
fi

# ── Rsync: compose + env + imagens ─────────────────────────────────
section "Transferindo para o Servidor"

run_ssh "mkdir -p '$REMOTE_DIR'" "Criando diretorio remoto"

# 1. Compose file + .env (rsync ja faz delta por padrao)
info "Copiando compose e env (rsync delta)..."
RSYNC_BASE="rsync -avz --progress -e '$RSYNC_SSH'"
RSYNC_DEST="${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_DIR}/"

if [ "$DRY_RUN" != "true" ]; then
    eval "$RSYNC_BASE '$DEPLOY_COMPOSE_FILE' '$RSYNC_DEST'" 2>&1 | grep -vE "(Warning|Permanently)" || true
    eval "$RSYNC_BASE '$DEPLOY_ENV_FILE' '$RSYNC_DEST'" 2>&1 | grep -vE "(Warning|Permanently)" || true
fi
info "✅ Compose e env sincronizados"

# 2. Imagens (apenas as que mudaram)
if [ "$STALE_COUNT" -gt 0 ]; then
    info "Transferindo imagens desatualizadas..."
    TRANSFERRED=0
    for img in "${IMAGES[@]}"; do
        [ "${NEEDS_TRANSFER[$img]}" = "1" ] || continue

        TRANSFERRED=$((TRANSFERRED + 1))
        TAR_FILE="${IMAGE_FILES[$img]}"
        TAR_PATH="$TEMP_DIR/$TAR_FILE"
        SIZE=$(du -h "$TAR_PATH" | cut -f1)

        info "[$TRANSFERRED/$STALE_COUNT] Enviando $TAR_FILE ($SIZE)..."

        if [ "$DRY_RUN" != "true" ]; then
            eval "$RSYNC_BASE '$TAR_PATH' '$RSYNC_DEST'" 2>&1 | grep -vE "(Warning|Permanently)" || true
            info "  ✅ Enviado"
        fi
    done
else
    info "Nenhuma imagem para transferir — todas em sincronia."
fi

# ── Load no remoto (apenas imagens transferidas) ───────────────────
if [ "$STALE_COUNT" -gt 0 ]; then
    section "Carregando Imagens no Servidor"
    LOADED=0
    for img in "${IMAGES[@]}"; do
        [ "${NEEDS_TRANSFER[$img]}" = "1" ] || continue

        LOADED=$((LOADED + 1))
        TAR_FILE="${IMAGE_FILES[$img]}"

        info "[$LOADED/$STALE_COUNT] Carregando $img..."
        if [ "$DRY_RUN" != "true" ]; then
            capture_ssh "cd '$REMOTE_DIR' && docker load -i '$TAR_FILE' && rm -f '$TAR_FILE'" \
                "docker load $TAR_FILE"
            info "  ✅ Carregada"
        fi
    done
fi

# ── Compose up ─────────────────────────────────────────────────────
section "Iniciando Containers no Servidor"

run_ssh "cd '$REMOTE_DIR' && docker compose -f '$DEPLOY_COMPOSE_FILE' --env-file '$DEPLOY_ENV_FILE' down --remove-orphans 2>&1 || true" \
    "Parando containers existentes"

run_ssh "cd '$REMOTE_DIR' && docker compose -f '$DEPLOY_COMPOSE_FILE' --env-file '$DEPLOY_ENV_FILE' up -d 2>&1" \
    "Iniciando containers"

info "Aguardando inicializacao (10s)..."
if [ "$DRY_RUN" != "true" ]; then sleep 10; fi

# ── Status ─────────────────────────────────────────────────────────
section "Status dos Containers"
capture_ssh "cd '$REMOTE_DIR' && docker compose -f '$DEPLOY_COMPOSE_FILE' --env-file '$DEPLOY_ENV_FILE' ps 2>&1" \
    "docker compose ps"
echo "$CAPTURED"

# ── Smoke test ─────────────────────────────────────────────────────
if [ "$SKIP_SMOKE" != "true" ] && [ "$DRY_RUN" != "true" ]; then
    section "Smoke Tests"

    case "$DEPLOY_PROJECT_NAME" in
        infra-httpd)
            capture_ssh "curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/" \
                "Testando Apache (/)"
            info "Apache status: ${CAPTURED:-N/A}"

            capture_ssh "curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/webmail/" \
                "Testando Roundcube (/webmail/)"
            info "Webmail status: ${CAPTURED:-N/A}"
            ;;
        infra-mail)
            capture_ssh "docker exec results-mail-postfix-mx1 postfix status 2>&1 || echo 'N/A'" \
                "Verificando Postfix"
            info "Postfix: ${CAPTURED:-N/A}"

            capture_ssh "docker exec results-mail-dovecot doveadm instance list 2>&1 || echo 'N/A'" \
                "Verificando Dovecot"
            info "Dovecot: ${CAPTURED:-N/A}"
            ;;
    esac
fi

# ── Limpeza ────────────────────────────────────────────────────────
rm -rf "$TEMP_DIR"

section "Deploy Concluido"
info "Stack:   $DEPLOY_PROJECT_NAME"
info "Host:    $REMOTE_HOST"
info "Compose: $DEPLOY_COMPOSE_FILE"
echo ""
info "Para verificar logs:"
echo "  ssh ${REMOTE_USER}@${REMOTE_HOST} 'cd $REMOTE_DIR && docker compose -f $DEPLOY_COMPOSE_FILE logs --tail=50'"
