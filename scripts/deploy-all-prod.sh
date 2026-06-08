#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

DEPLOY_HOST="${DEPLOY_HOST:-10.10.2.30}"
DEPLOY_USER="${DEPLOY_USER:-root}"
DEPLOY_PATH="${DEPLOY_PATH:-/opt/results/infra}"
OBSERVE_DEPLOY_PATH="${OBSERVE_DEPLOY_PATH:-/opt/results/r-observe}"
SSH_PASSWORD="${SSH_PASSWORD:-${DEPLOY_SSH_PASSWORD:-}}"

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

section() {
    printf '\n%b== %s ==%b\n' "${BLUE}" "$1" "${NC}"
}

die() {
    printf '%b[ERRO]%b %s\n' "${RED}" "${NC}" "$1" >&2
    exit 1
}

run_remote() {
    local cmd="$1"

    if [[ -n "${SSH_PASSWORD}" ]]; then
        sshpass -p "${SSH_PASSWORD}" ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null "${DEPLOY_USER}@${DEPLOY_HOST}" "${cmd}"
    else
        ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null "${DEPLOY_USER}@${DEPLOY_HOST}" "${cmd}"
    fi
}

require_file() {
    local path="$1"
    [[ -f "${path}" ]] || die "Arquivo obrigatorio ausente: ${path}"
}

command -v ssh >/dev/null 2>&1 || die "ssh nao encontrado."
command -v sshpass >/dev/null 2>&1 || warn "sshpass nao encontrado; use chave SSH ou exporte SSH_PASSWORD/DEPLOY_SSH_PASSWORD somente se sshpass estiver instalado."

require_file ".env.remote-10.10.2.30-mail"
require_file ".env.remote-10.10.2.30-ip60"
require_file "docker-compose.mail.yml"
require_file "docker-compose.yml"
require_file "docker-compose.edge-sni.yml"
require_file "docker-compose.vpn.yml"
require_file "vpn/deploy-rvpn-safe.sh"

section "Mail"
DEPLOY_SSH_PASSWORD="${SSH_PASSWORD}" \
DEPLOY_HOST="${DEPLOY_HOST}" \
DEPLOY_USER="${DEPLOY_USER}" \
DEPLOY_PATH="${DEPLOY_PATH}" \
DEPLOY_ENV_FILE=".env.remote-10.10.2.30-mail" \
DEPLOY_PROJECT_NAME="infra-mail" \
DEPLOY_COMPOSE_FILE="docker-compose.mail.yml" \
DEPLOY_SYNC_PATHS="mail,scripts,docker-compose.mail.yml,.env.remote-10.10.2.30-mail" \
./scripts/docker-deploy.sh

section "HTTPD"
DEPLOY_SSH_PASSWORD="${SSH_PASSWORD}" \
DEPLOY_HOST="${DEPLOY_HOST}" \
DEPLOY_USER="${DEPLOY_USER}" \
DEPLOY_PATH="${DEPLOY_PATH}" \
DEPLOY_ENV_FILE=".env.remote-10.10.2.30-ip60" \
DEPLOY_PROJECT_NAME="infra-httpd" \
DEPLOY_COMPOSE_FILE="docker-compose.yml" \
DEPLOY_SYNC_PATHS="apache,joomla,lsyncd,subdomain-sync,roundcube,joomla-site,scripts,docker-compose.yml,.env.remote-10.10.2.30-ip60" \
./scripts/docker-deploy.sh

section "Edge SNI"
run_remote "cd '${DEPLOY_PATH}' && docker compose -f docker-compose.edge-sni.yml --project-name edge-sni up -d && docker compose -f docker-compose.edge-sni.yml --project-name edge-sni ps"

section "DNS"
run_remote "cd '${DEPLOY_PATH}/dns-consolidated' && docker compose -f docker-compose.yml --env-file .env up -d && docker compose -f docker-compose.yml --env-file .env ps"

section "Observe"
run_remote "cd '${OBSERVE_DEPLOY_PATH}' && docker compose -f docker-compose.observe.yml --env-file .env.observe --profile observe-core --profile observe-ai --profile observe-agent --profile observe-monitoring --profile observe-icinga --profile r-observe-proxy up -d --remove-orphans"

section "VPN"
DEPLOY_SSH_PASSWORD="${SSH_PASSWORD}" ./vpn/deploy-rvpn-safe.sh --remote --host "${DEPLOY_HOST}" --user "${DEPLOY_USER}" --dir "${DEPLOY_PATH}"

section "Checks"
run_remote "timeout 20 docker inspect rvpn edge-sni pdns-auth pdns-recursor dns-dnsdist secure-httpd results-mail-postfix results-mail-dovecot r-observe-api r-observe-icingaweb2 --format '{{.Name}} {{.State.Status}} {{if .State.Health}}{{.State.Health.Status}}{{else}}no-health{{end}}'"
run_remote "dig @127.0.0.1 -p 5300 results.com.br A +short | head -1 && echo --- && dig @127.0.0.1 -p 5301 google.com A +short | head -1"

info "Deploy completo concluido em ${DEPLOY_HOST}."
