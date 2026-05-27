#!/usr/bin/env bash

set -euo pipefail

CONTAINER_NAME="${CONTAINER_NAME:-rvpn}"
LOG_FILE="${LOG_FILE:-/var/log/ensure-rvpn-running.log}"
TIMESTAMP="$(date '+%Y-%m-%d %H:%M:%S')"

mkdir -p "$(dirname "${LOG_FILE}")"

log() {
    printf '[%s] %s\n' "${TIMESTAMP}" "$1" >> "${LOG_FILE}"
}

container_exists() {
    docker inspect "${CONTAINER_NAME}" >/dev/null 2>&1
}

container_running() {
    [ "$(docker inspect --format '{{.State.Status}}' "${CONTAINER_NAME}" 2>/dev/null || true)" = "running" ]
}

if ! command -v docker >/dev/null 2>&1; then
    log "docker nao encontrado; verificacao abortada."
    exit 1
fi

if ! container_exists; then
    log "container ${CONTAINER_NAME} nao existe; nenhuma acao executada."
    exit 1
fi

if container_running; then
    exit 0
fi

log "container ${CONTAINER_NAME} estava parado; executando docker start."
if docker start "${CONTAINER_NAME}" >/dev/null 2>&1; then
    log "container ${CONTAINER_NAME} iniciado com sucesso."
    exit 0
fi

log "falha ao iniciar container ${CONTAINER_NAME}."
exit 1
