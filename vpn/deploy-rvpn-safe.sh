#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

REMOTE_HOST="${DEPLOY_HOST:-10.10.2.30}"
REMOTE_USER="${DEPLOY_USER:-root}"
REMOTE_PORT="${DEPLOY_PORT:-22}"
REMOTE_DIR="${DEPLOY_PATH:-/opt/results/infra}"
SSH_PASSWORD="${SSH_PASSWORD:-${DEPLOY_SSH_PASSWORD:-}}"
SSH_KEY_PATH="${DEPLOY_SSH_KEY:-}"
MODE="local"
DETACH_ONLY=0

RED="$(printf '\033[0;31m')"
GREEN="$(printf '\033[0;32m')"
YELLOW="$(printf '\033[1;33m')"
BLUE="$(printf '\033[0;34m')"
NC="$(printf '\033[0m')"

info() {
    printf '%b[INFO]%b %s\n' "${GREEN}" "${NC}" "$1"
}

warn() {
    printf '%b[WARN]%b %s\n' "${YELLOW}" "${NC}" "$1"
}

die() {
    printf '%b[ERRO]%b %s\n' "${RED}" "${NC}" "$1" >&2
    exit 1
}

usage() {
    cat <<'EOF'
Uso:
  ./vpn/deploy-rvpn-safe.sh
  ./vpn/deploy-rvpn-safe.sh --remote
  ./vpn/deploy-rvpn-safe.sh --remote --host 10.10.2.30
  ./vpn/deploy-rvpn-safe.sh --remote --detach-only

Opcoes:
  --remote         Dispara o deploy no host remoto via SSH.
  --host HOST      Sobrescreve o host remoto.
  --user USER      Sobrescreve o usuario SSH.
  --port PORT      Sobrescreve a porta SSH.
  --dir DIR        Sobrescreve o diretorio remoto.
  --detach-only    Apenas dispara o job remoto desacoplado e sai.
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --remote)
            MODE="remote"
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
        --detach-only)
            DETACH_ONLY=1
            shift
            ;;
        --help|-h)
            usage
            exit 0
            ;;
        *)
            die "Argumento desconhecido: $1"
            ;;
    esac
done

LOCAL_DEPLOY_CMD="cd '${ROOT_DIR}' && docker compose --project-name vpn -f docker-compose.vpn.yml --env-file .env.vpn up -d"
REMOTE_DEPLOY_CMD="cd '${REMOTE_DIR}' && nohup docker compose --project-name vpn -f docker-compose.vpn.yml --env-file .env.vpn up -d >/tmp/rvpn-compose.log 2>&1 </dev/null &"
REMOTE_STATUS_CMD="cd '${REMOTE_DIR}' && docker ps -a --filter name=^/rvpn$ --format 'table {{.Names}}\t{{.Status}}\t{{.Image}}' && echo --- && docker inspect rvpn --format 'restart={{.HostConfig.RestartPolicy.Name}} status={{.State.Status}} started={{.State.StartedAt}}'"

run_remote() {
    local cmd="$1"
    local ssh_opts="-p ${REMOTE_PORT} -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ConnectTimeout=15"

    if [[ -n "${SSH_KEY_PATH}" ]]; then
        ssh_opts="${ssh_opts} -i ${SSH_KEY_PATH}"
    fi

    if [[ -n "${SSH_PASSWORD}" ]]; then
        command -v sshpass >/dev/null 2>&1 || die "sshpass nao encontrado para autenticacao por senha."
        sshpass -p "${SSH_PASSWORD}" ssh ${ssh_opts} "${REMOTE_USER}@${REMOTE_HOST}" "${cmd}"
    else
        ssh ${ssh_opts} "${REMOTE_USER}@${REMOTE_HOST}" "${cmd}"
    fi
}

if [[ "${MODE}" = "local" ]]; then
    info "Executando deploy local seguro da VPN a partir de ${ROOT_DIR}."
    [[ -f "${ROOT_DIR}/docker-compose.vpn.yml" ]] || die "docker-compose.vpn.yml nao encontrado."
    [[ -f "${ROOT_DIR}/.env.vpn" ]] || die ".env.vpn nao encontrado."
    eval "${LOCAL_DEPLOY_CMD}"
    eval "${REMOTE_STATUS_CMD//${REMOTE_DIR}/${ROOT_DIR}}"
    exit 0
fi

info "Disparando deploy remoto desacoplado da VPN em ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_DIR}."
run_remote "test -f '${REMOTE_DIR}/docker-compose.vpn.yml' && test -f '${REMOTE_DIR}/.env.vpn'" || die "Arquivos obrigatorios ausentes no host remoto."
run_remote "${REMOTE_DEPLOY_CMD}"
info "Job remoto disparado. Log remoto: /tmp/rvpn-compose.log"

if [[ "${DETACH_ONLY}" -eq 1 ]]; then
    warn "Saindo sem validar o retorno do host porque --detach-only foi usado."
    exit 0
fi

sleep 5
if run_remote "${REMOTE_STATUS_CMD}"; then
    info "Status remoto consultado com sucesso."
else
    warn "Nao foi possivel validar o status remoto imediatamente. Isso pode ser esperado enquanto o host recompõe a conectividade."
    warn "Verifique depois com: ssh ${REMOTE_USER}@${REMOTE_HOST} 'cd ${REMOTE_DIR} && docker ps -a --filter name=^/rvpn$'"
fi
