#!/usr/bin/env bash

set -euo pipefail

TEST_HOSTNAME="${TEST_HOSTNAME:-rvpn.results.com.br}"
VPN_COMPOSE_FILE="${VPN_COMPOSE_FILE:-/opt/results/infra/docker-compose.vpn.yml}"
VPN_ENV_FILE="${VPN_ENV_FILE:-/opt/results/infra/.env.vpn}"
EDGE_COMPOSE_FILE="${EDGE_COMPOSE_FILE:-/opt/results/infra/docker-compose.edge-sni.yml}"
EDGE_CONFIG_FILE="${EDGE_CONFIG_FILE:-/opt/results/infra/edge-sni/haproxy.cfg}"
RVPN_CONTAINER="${RVPN_CONTAINER:-rvpn}"
EDGE_CONTAINER="${EDGE_CONTAINER:-edge-sni}"
RVPN_VOLUME_NAME="${RVPN_VOLUME_NAME:-vpn_softetherdata}"
RVPN_SERVER_PASSWORD="${RVPN_SERVER_PASSWORD:-}"
REPORT_DIR="${REPORT_DIR:-/tmp/rvpn-server-diag}"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
REPORT_FILE="${REPORT_DIR}/report-${TIMESTAMP}.log"

mkdir -p "${REPORT_DIR}"

RED="$(printf '\033[0;31m')"
GREEN="$(printf '\033[0;32m')"
YELLOW="$(printf '\033[1;33m')"
BLUE="$(printf '\033[0;34m')"
NC="$(printf '\033[0m')"

PASS_COUNT=0
WARN_COUNT=0
FAIL_COUNT=0

have_cmd() {
    command -v "$1" >/dev/null 2>&1
}

section() {
    printf '\n%s== %s ==%s\n' "${BLUE}" "$1" "${NC}" | tee -a "${REPORT_FILE}"
}

line() {
    printf '%s\n' "$1" | tee -a "${REPORT_FILE}"
}

pass() {
    PASS_COUNT=$((PASS_COUNT + 1))
    printf '%b[PASS]%b %s\n' "${GREEN}" "${NC}" "$1" | tee -a "${REPORT_FILE}"
}

warn() {
    WARN_COUNT=$((WARN_COUNT + 1))
    printf '%b[WARN]%b %s\n' "${YELLOW}" "${NC}" "$1" | tee -a "${REPORT_FILE}"
}

fail() {
    FAIL_COUNT=$((FAIL_COUNT + 1))
    printf '%b[FAIL]%b %s\n' "${RED}" "${NC}" "$1" | tee -a "${REPORT_FILE}"
}

run() {
    local description="$1"
    shift

    section "${description}"
    printf '+ %s\n' "$*" | tee -a "${REPORT_FILE}"
    "$@" 2>&1 | tee -a "${REPORT_FILE}" || true
}

docker_exec() {
    docker exec "${RVPN_CONTAINER}" sh -lc "$1"
}

softether_server_cmd() {
    local inner_cmd="$1"

    if [ -z "${RVPN_SERVER_PASSWORD}" ]; then
        warn "RVPN_SERVER_PASSWORD nao definido; pulando consultas administrativas do SoftEther."
        return 1
    fi

    docker_exec "if command -v vpncmd >/dev/null 2>&1; then vpncmd localhost /SERVER /PASSWORD:${RVPN_SERVER_PASSWORD} /CMD ${inner_cmd}; else /usr/local/vpnserver/vpncmd localhost /SERVER /PASSWORD:${RVPN_SERVER_PASSWORD} /CMD ${inner_cmd}; fi"
}

check_file() {
    local path="$1"
    if [ -f "${path}" ]; then
        pass "Arquivo presente: ${path}"
    else
        fail "Arquivo ausente: ${path}"
    fi
}

check_container_running() {
    local name="$1"
    local status

    status="$(docker inspect --format '{{.State.Status}}' "${name}" 2>/dev/null || true)"
    if [ "${status}" = "running" ]; then
        pass "Container ${name} esta running."
    elif [ -n "${status}" ]; then
        fail "Container ${name} nao esta running (status=${status})."
    else
        fail "Container ${name} nao existe."
    fi
}

check_container_health() {
    local name="$1"
    local health

    health="$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}no-healthcheck{{end}}' "${name}" 2>/dev/null || true)"
    case "${health}" in
        healthy)
            pass "Healthcheck do container ${name} esta healthy."
            ;;
        no-healthcheck)
            warn "Container ${name} nao define healthcheck."
            ;;
        *)
            fail "Healthcheck do container ${name} esta ${health}."
            ;;
    esac
}

check_listener() {
    local label="$1"
    local pattern="$2"

    if ss -ltnup 2>/dev/null | grep -Eq "${pattern}"; then
        pass "Listener encontrado para ${label}."
    else
        fail "Listener ausente para ${label}."
    fi
}

check_tcp_probe() {
    local host="$1"
    local port="$2"

    if timeout 5 bash -lc "cat < /dev/null > /dev/tcp/${host}/${port}" >/dev/null 2>&1; then
        pass "TCP conectou em ${host}:${port}."
    else
        fail "TCP nao conectou em ${host}:${port}."
    fi
}

suggestions() {
    section "Orientacao"

    if [ "${FAIL_COUNT}" -eq 0 ]; then
        line "Nenhuma falha objetiva foi detectada neste host."
        line "Se o cliente ainda nao conecta, valide credenciais/hub no SoftEther e o caminho externo/NAT a partir de fora do host."
        return 0
    fi

    line "Priorize os itens abaixo conforme as falhas acima:"
    line "1. Se o container rvpn nao estiver running/healthy:"
    line "   - rode: docker compose --project-name vpn -f ${VPN_COMPOSE_FILE} --env-file ${VPN_ENV_FILE} up -d"
    line "   - depois confira: docker logs --tail 100 ${RVPN_CONTAINER}"
    line "2. Se os listeners 10.10.2.30:443/5555/992 nao existirem:"
    line "   - valide VPN_BIND_IP em ${VPN_ENV_FILE}"
    line "   - valide publish em ${VPN_COMPOSE_FILE}"
    line "   - confira se o host realmente tem o IP 10.10.2.30 com: ip addr"
    line "3. Se o volume ${RVPN_VOLUME_NAME} nao estiver montado no rvpn:"
    line "   - o SoftEther pode ter subido sem a configuracao real"
    line "   - valide o volume em: docker inspect ${RVPN_CONTAINER}"
    line "4. Se ${TEST_HOSTNAME}:5555 falhar, mas 443 responder:"
    line "   - falta publish/NAT/firewall para 5555"
    line "   - valide regra externa e firewall do host"
    line "5. Se 443 responder em TCP mas os logs/sessoes do SoftEther nao mostrarem conexao:"
    line "   - valide se rvpn.results.com.br esta realmente chegando ao SoftEther"
    line "   - revise o edge-sni em ${EDGE_CONFIG_FILE}"
    line "   - confirme que rvpn.results.com.br nao esta indo para Apache/HTTPS comum"
    line "6. Se o host tiver iptables/fail2ban agressivo:"
    line "   - confira counters na chain RESULTS-RATE-LIMIT"
    line "   - revise bans ativos no fail2ban"
    line "7. Se tudo acima estiver certo:"
    line "   - valide usuarios/hub/sessoes com a senha admin do SoftEther via RVPN_SERVER_PASSWORD"
}

section "Contexto"
line "Relatorio: ${REPORT_FILE}"
line "Hostname de teste: ${TEST_HOSTNAME}"
line "Container VPN: ${RVPN_CONTAINER}"
line "Container edge: ${EDGE_CONTAINER}"

section "Pre-check"

if [ "$(id -u)" -ne 0 ]; then
    warn "Rodando sem root. Alguns comandos (iptables, fail2ban) podem sair incompletos."
else
    pass "Script esta rodando como root."
fi

for required in docker ss getent; do
    if have_cmd "${required}"; then
        pass "Comando disponivel: ${required}"
    else
        fail "Comando ausente: ${required}"
    fi
done

check_file "${VPN_COMPOSE_FILE}"
check_file "${VPN_ENV_FILE}"
check_file "${EDGE_COMPOSE_FILE}"
check_file "${EDGE_CONFIG_FILE}"

run "DNS do hostname publico" getent ahostsv4 "${TEST_HOSTNAME}"
run "IPs do host" ip addr
run "Rotas do host" ip route

section "Containers"
check_container_running "${RVPN_CONTAINER}"
check_container_health "${RVPN_CONTAINER}"
check_container_running "${EDGE_CONTAINER}"
check_container_health "${EDGE_CONTAINER}"

run "Docker ps resumido" docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'
run "Compose VPN renderizado" docker compose --project-name vpn -f "${VPN_COMPOSE_FILE}" --env-file "${VPN_ENV_FILE}" config
run "Docker inspect rvpn" docker inspect "${RVPN_CONTAINER}"
run "Docker inspect edge-sni" docker inspect "${EDGE_CONTAINER}"
run "Volume esperado do SoftEther" docker volume inspect "${RVPN_VOLUME_NAME}"
run "Mapeamento de portas do rvpn" docker port "${RVPN_CONTAINER}"

section "Listeners locais"
run "Sockets publicados no host" ss -ltnup
check_listener "rvpn 10.10.2.30:443" '10\.10\.2\.30:443'
check_listener "rvpn 10.10.2.30:5555" '10\.10\.2\.30:5555'
check_listener "rvpn 10.10.2.30:992" '10\.10\.2\.30:992'
check_listener "edge-sni 10.10.2.60:443" '10\.10\.2\.60:443'

section "Probes locais"
check_tcp_probe "10.10.2.30" 443
check_tcp_probe "10.10.2.30" 5555
check_tcp_probe "10.10.2.30" 992

section "Probes publicos"
run "Probe publico 443" timeout 8 bash -lc "cat < /dev/null > /dev/tcp/${TEST_HOSTNAME}/443"
run "Probe publico 5555" timeout 8 bash -lc "cat < /dev/null > /dev/tcp/${TEST_HOSTNAME}/5555"
run "Probe publico 992" timeout 8 bash -lc "cat < /dev/null > /dev/tcp/${TEST_HOSTNAME}/992"
run "TLS observavel no hostname publico" openssl s_client -connect "${TEST_HOSTNAME}:443" -servername "${TEST_HOSTNAME}" </dev/null

section "Config de borda"
run "Trecho relevante do edge-sni" sed -n '1,220p' "${EDGE_CONFIG_FILE}"
run "Env da VPN" sed -n '1,120p' "${VPN_ENV_FILE}"

section "Logs"
run "Logs recentes do rvpn" docker logs --tail 120 "${RVPN_CONTAINER}"
run "Logs recentes do edge-sni" docker logs --tail 120 "${EDGE_CONTAINER}"

section "Host firewall"
if have_cmd iptables; then
    run "iptables INPUT" iptables -L INPUT -n -v
    run "iptables RESULTS-RATE-LIMIT" iptables -L RESULTS-RATE-LIMIT -n -v
else
    warn "iptables nao disponivel; pulando firewall."
fi

if have_cmd fail2ban-client; then
    run "fail2ban status" fail2ban-client status
else
    warn "fail2ban-client nao disponivel; pulando fail2ban."
fi

section "SoftEther server"
if [ -n "${RVPN_SERVER_PASSWORD}" ]; then
    run "SoftEther listener list" softether_server_cmd "ListenerList"
    run "SoftEther session list" softether_server_cmd "SessionList DEFAULT"
    run "SoftEther user list" softether_server_cmd "UserList DEFAULT"
else
    warn "Sem RVPN_SERVER_PASSWORD; pulando ListenerList/SessionList/UserList."
fi

section "Resumo"
line "Pass: ${PASS_COUNT}"
line "Warn: ${WARN_COUNT}"
line "Fail: ${FAIL_COUNT}"

suggestions

line ""
line "Relatorio salvo em: ${REPORT_FILE}"
