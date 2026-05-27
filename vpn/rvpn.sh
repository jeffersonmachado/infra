#!/bin/bash

VPN_PASSWORD="teste"
VPN_IF="vpn_vpn1"
VPN_IP="192.168.30.11/24"
VPN_GW="192.168.30.1"
TEST_TARGET="${TEST_TARGET:-10.10.2.30}"
PING_COUNT="${PING_COUNT:-2}"
PING_TIMEOUT_SECONDS="${PING_TIMEOUT_SECONDS:-2}"
PRIMARY_ACCOUNT="${PRIMARY_ACCOUNT:-results}"
PRIMARY_SERVER="${PRIMARY_SERVER:-rvpn.results.com.br:443}"
PRIMARY_SERVER_FALLBACK="${PRIMARY_SERVER_FALLBACK:-rvpn.results.com.br:5555}"
PRIMARY_HUB="${PRIMARY_HUB:-DEFAULT}"
DIRECT_ACCOUNT="${DIRECT_ACCOUNT:-labrador}"
DIRECT_SERVER="${DIRECT_SERVER:-rvpn.results.com.br:443}"
DIRECT_SERVER_FALLBACK="${DIRECT_SERVER_FALLBACK:-rvpn.results.com.br:5555}"
DIRECT_HUB="${DIRECT_HUB:-DEFAULT}"
DIRECT_USERNAME="${DIRECT_USERNAME:-labrador}"
DIRECT_AUTH_TYPE="${DIRECT_AUTH_TYPE:-standard}"
DIRECT_PASSWORD="${DIRECT_PASSWORD:-labrador}"
DIRECT_NICNAME="${DIRECT_NICNAME:-vpn1}"
MODE="${1:-auto}"
CONNECT_WAIT_SECONDS="${CONNECT_WAIT_SECONDS:-15}"
ROUTES_TO_CONFIGURE=("10.10.2.0/24" "192.168.1.0/24")
RESTART_CLIENT_BEFORE_CONNECT="${RESTART_CLIENT_BEFORE_CONNECT:-0}"
SOFTETHER_CLIENT_SERVICE="${SOFTETHER_CLIENT_SERVICE:-softether-vpnclient.service}"
AUTO_RESTART_ON_REACHABILITY_FAILURE="${AUTO_RESTART_ON_REACHABILITY_FAILURE:-1}"
RESTART_CLIENT_NOW=0
LOG_DIR="${LOG_DIR:-/tmp/rvpn}"
DEBUG_LOG_FILE="${DEBUG_LOG_FILE:-${LOG_DIR}/rvpn-debug.log}"
CAPTURE_SUCCESS_DIAGNOSTICS="${CAPTURE_SUCCESS_DIAGNOSTICS:-1}"

set -euo pipefail

mkdir -p "${LOG_DIR}"

validate_configuration() {
    if [ "${DIRECT_AUTH_TYPE}" != "standard" ]; then
        echo "Autenticação anônima não é permitida. Use DIRECT_AUTH_TYPE=standard."
        exit 1
    fi

    if [ "${MODE}" != "primary" ] && [ -z "${DIRECT_PASSWORD}" ]; then
        echo "DIRECT_PASSWORD é obrigatório quando o modo inclui a conta direta."
        exit 1
    fi
}

log_debug() {
    local message="$1"

    printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "${message}" | tee -a "${DEBUG_LOG_FILE}"
}

resolve_route_target() {
    local target="$1"

    if [[ "${target}" =~ ^[0-9]+(\.[0-9]+){3}$ ]] || [[ "${target}" == *:* ]]; then
        printf '%s\n' "${target}"
        return 0
    fi

    getent ahostsv4 "${target}" | awk 'NR == 1 { print $1 }'
}

collect_diagnostics() {
    local reason="$1"
    local account_name="${2:-}"
    local route_prefix
    local route_target

    {
        printf '\n===== %s | %s =====\n' "$(date '+%Y-%m-%d %H:%M:%S')" "${reason}"
        if [ -n "${account_name}" ]; then
            printf 'account=%s\n' "${account_name}"
            sudo vpncmd localhost /CLIENT /PASSWORD:${VPN_PASSWORD} /CMD AccountStatusGet "${account_name}" 2>&1 || true
        fi
        printf '\n[ip addr]\n'
        ip addr show "${VPN_IF}" 2>&1 || true
        printf '\n[ip -s link]\n'
        ip -s link show "${VPN_IF}" 2>&1 || true
        printf '\n[ip route get target]\n'
        if [ -n "${TEST_TARGET}" ]; then
            route_target="$(resolve_route_target "${TEST_TARGET}")"
            if [ -n "${route_target}" ]; then
                ip route get "${route_target}" 2>&1 || true
            else
                printf 'Não foi possível resolver %s.\n' "${TEST_TARGET}"
            fi
        fi
        printf '\n[routes]\n'
        for route_prefix in "${ROUTES_TO_CONFIGURE[@]}"; do
            ip route show "${route_prefix}" 2>&1 || true
        done
        printf '\n[ip neigh]\n'
        ip neigh show dev "${VPN_IF}" 2>&1 || true
        printf '\n[systemctl status]\n'
        sudo systemctl --no-pager --full status "${SOFTETHER_CLIENT_SERVICE}" 2>&1 || true
        printf '\n[journalctl tail]\n'
        sudo journalctl -u "${SOFTETHER_CLIENT_SERVICE}" -n 40 --no-pager 2>&1 || true
    } >> "${DEBUG_LOG_FILE}"
}

get_account_status() {
    local account_name="$1"

    sudo vpncmd localhost /CLIENT /PASSWORD:${VPN_PASSWORD} /CMD AccountStatusGet "${account_name}" 2>/dev/null
}

is_account_connected() {
    local status_output="$1"

    echo "$status_output" | grep -Eq 'Connection Completed|Number of Established Sessions[[:space:]]*\|[[:space:]]*[1-9][0-9]*'
}

account_exists() {
    local account_name="$1"

    sudo vpncmd localhost /CLIENT /PASSWORD:${VPN_PASSWORD} /CMD AccountGet "${account_name}" >/dev/null 2>&1
}

disconnect_account() {
    local account_name="$1"

    if account_exists "${account_name}"; then
        sudo vpncmd localhost /CLIENT /PASSWORD:${VPN_PASSWORD} /CMD AccountDisconnect "${account_name}" >/dev/null 2>&1 || true
    fi
}

disconnect_other_accounts() {
    local selected_account="$1"
    local known_account

    for known_account in "${PRIMARY_ACCOUNT}" "${DIRECT_ACCOUNT}"; do
        if [ "${known_account}" != "${selected_account}" ]; then
            disconnect_account "${known_account}"
        fi
    done
}

cleanup_routes() {
    local route_prefix

    for route_prefix in "${ROUTES_TO_CONFIGURE[@]}"; do
        sudo ip route del "${route_prefix}" dev "${VPN_IF}" >/dev/null 2>&1 || true
    done
}

cleanup_interface_state() {
    if ! ip link show "${VPN_IF}" >/dev/null 2>&1; then
        return 0
    fi

    sudo ip link set "${VPN_IF}" down >/dev/null 2>&1 || true
    sudo ip addr flush dev "${VPN_IF}" >/dev/null 2>&1 || true
    sudo ip neigh flush dev "${VPN_IF}" >/dev/null 2>&1 || true
}

restart_softether_client_if_requested() {
    if [ "${RESTART_CLIENT_BEFORE_CONNECT}" != "1" ] && [ "${RESTART_CLIENT_NOW}" != "1" ]; then
        return 0
    fi

    log_debug "Reiniciando ${SOFTETHER_CLIENT_SERVICE} para limpar estado residual."
    sudo systemctl restart "${SOFTETHER_CLIENT_SERVICE}"
    RESTART_CLIENT_NOW=0
}

reset_connection_state() {
    local known_account

    log_debug "Limpando estado anterior da VPN..."

    for known_account in "${PRIMARY_ACCOUNT}" "${DIRECT_ACCOUNT}"; do
        disconnect_account "${known_account}"
    done

    cleanup_routes
    cleanup_interface_state
    restart_softether_client_if_requested
}

ensure_direct_account() {
    if account_exists "${DIRECT_ACCOUNT}"; then
        sudo vpncmd localhost /CLIENT /PASSWORD:${VPN_PASSWORD} /CMD AccountSet "${DIRECT_ACCOUNT}" \
            /SERVER:"${DIRECT_SERVER}" \
            /HUB:"${DIRECT_HUB}" >/dev/null
        sudo vpncmd localhost /CLIENT /PASSWORD:${VPN_PASSWORD} /CMD AccountUsernameSet "${DIRECT_ACCOUNT}" \
            /USERNAME:"${DIRECT_USERNAME}" >/dev/null

        if [ "${DIRECT_AUTH_TYPE}" = "standard" ]; then
            if [ -z "${DIRECT_PASSWORD}" ]; then
                echo "DIRECT_PASSWORD não foi definido para autenticação standard."
                exit 1
            fi

            sudo vpncmd localhost /CLIENT /PASSWORD:${VPN_PASSWORD} /CMD AccountPasswordSet "${DIRECT_ACCOUNT}" \
                /PASSWORD:"${DIRECT_PASSWORD}" \
                /TYPE:standard >/dev/null
        fi

        return 0
    fi

    echo "Criando conta direta ${DIRECT_ACCOUNT} em ${DIRECT_SERVER}..."
    sudo vpncmd localhost /CLIENT /PASSWORD:${VPN_PASSWORD} /CMD AccountCreate "${DIRECT_ACCOUNT}" \
        /SERVER:"${DIRECT_SERVER}" \
        /HUB:"${DIRECT_HUB}" \
        /USERNAME:"${DIRECT_USERNAME}" \
        /NICNAME:"${DIRECT_NICNAME}" >/dev/null

    if [ "${DIRECT_AUTH_TYPE}" = "standard" ]; then
        if [ -z "${DIRECT_PASSWORD}" ]; then
            echo "DIRECT_PASSWORD não foi definido para autenticação standard."
            exit 1
        fi

        sudo vpncmd localhost /CLIENT /PASSWORD:${VPN_PASSWORD} /CMD AccountPasswordSet "${DIRECT_ACCOUNT}" \
            /PASSWORD:"${DIRECT_PASSWORD}" \
            /TYPE:standard >/dev/null
    fi
}

ensure_primary_account() {
    if ! account_exists "${PRIMARY_ACCOUNT}"; then
        echo "Conta VPN principal ${PRIMARY_ACCOUNT} não existe no cliente SoftEther."
        return 1
    fi

    sudo vpncmd localhost /CLIENT /PASSWORD:${VPN_PASSWORD} /CMD AccountSet "${PRIMARY_ACCOUNT}" \
        /SERVER:"${PRIMARY_SERVER}" \
        /HUB:"${PRIMARY_HUB}" >/dev/null
}

set_account_endpoint() {
    local account_name="$1"
    local server_endpoint="$2"
    local hub_name="$3"

    sudo vpncmd localhost /CLIENT /PASSWORD:${VPN_PASSWORD} /CMD AccountSet "${account_name}" \
        /SERVER:"${server_endpoint}" \
        /HUB:"${hub_name}" >/dev/null
}

get_account_servers() {
    local account_name="$1"

    if [ "${account_name}" = "${PRIMARY_ACCOUNT}" ]; then
        printf '%s\n%s\n' "${PRIMARY_SERVER}" "${PRIMARY_SERVER_FALLBACK}" | awk 'NF && !seen[$0]++'
        return 0
    fi

    if [ "${account_name}" = "${DIRECT_ACCOUNT}" ]; then
        printf '%s\n%s\n' "${DIRECT_SERVER}" "${DIRECT_SERVER_FALLBACK}" | awk 'NF && !seen[$0]++'
        return 0
    fi

    return 1
}

connect_account() {
    local account_name="$1"
    local status_output
    local connect_output
    local elapsed=0

    if ! account_exists "${account_name}"; then
        echo "Conta VPN ${account_name} não existe no cliente SoftEther."
        return 1
    fi

    status_output=$(get_account_status "${account_name}")

    if is_account_connected "$status_output"; then
        echo "VPN ${account_name} já está conectada."
        return 0
    fi

    disconnect_other_accounts "${account_name}"

    echo "VPN ${account_name} não conectada. Tentando conectar..."
    connect_output=$(sudo vpncmd localhost /CLIENT /PASSWORD:${VPN_PASSWORD} /CMD AccountConnect "${account_name}" 2>&1 || true)
    echo "$connect_output"

    while [ "${elapsed}" -lt "${CONNECT_WAIT_SECONDS}" ]; do
        sleep 2
        elapsed=$((elapsed + 2))

        status_output=$(get_account_status "${account_name}")

        if is_account_connected "$status_output"; then
            echo "VPN ${account_name} conectada com sucesso."
            return 0
        fi
    done

    echo "Falha ao estabelecer a VPN ${account_name}."
    return 1
}

select_accounts() {
    case "${MODE}" in
        primary)
            ensure_primary_account
            printf '%s\n' "${PRIMARY_ACCOUNT}"
            ;;
        direct)
            ensure_direct_account
            printf '%s\n' "${DIRECT_ACCOUNT}"
            ;;
        auto)
            ensure_primary_account
            ensure_direct_account
            printf '%s\n%s\n' "${PRIMARY_ACCOUNT}" "${DIRECT_ACCOUNT}"
            ;;
        *)
            echo "Uso: $0 [auto|primary|direct]"
            exit 1
            ;;
    esac
}

configure_network() {
    echo "Configurando IP na interface ${VPN_IF}..."
    if ip link show "${VPN_IF}" >/dev/null 2>&1; then
        sudo ip link set "${VPN_IF}" up
        sudo ip addr replace "${VPN_IP}" dev "${VPN_IF}"
    else
        echo "Interface ${VPN_IF} não encontrada."
        ip link show
        exit 1
    fi

    echo "Configurando rotas..."
    local route_prefix

    for route_prefix in "${ROUTES_TO_CONFIGURE[@]}"; do
        sudo ip route replace "${route_prefix}" via "${VPN_GW}" dev "${VPN_IF}"
    done
}

validate_connectivity() {
    local account_name="$1"
    local route_target

    if [ -z "${TEST_TARGET}" ]; then
        return 0
    fi

    echo "Validando conectividade pela conta ${account_name} para ${TEST_TARGET}..."
    route_target="$(resolve_route_target "${TEST_TARGET}")"
    if [ -n "${route_target}" ]; then
        ip route get "${route_target}" || true
    else
        echo "Não foi possível resolver ${TEST_TARGET}."
    fi

    if ping -I "${VPN_IF}" -c "${PING_COUNT}" -W "${PING_TIMEOUT_SECONDS}" "${TEST_TARGET}"; then
        return 0
    fi

    echo "A conta ${account_name} estabeleceu sessão, mas não alcançou ${TEST_TARGET}."
    collect_diagnostics "reachability-failure" "${account_name}"
    return 1
}

show_final_state() {
    local route_prefix
    local route_target

    echo "Estado final:"
    ip addr show "${VPN_IF}"

    for route_prefix in "${ROUTES_TO_CONFIGURE[@]}"; do
        ip route show "${route_prefix}"
    done

    if [ -n "${TEST_TARGET}" ]; then
        echo "Teste de rota para ${TEST_TARGET}:"
        route_target="$(resolve_route_target "${TEST_TARGET}")"
        if [ -n "${route_target}" ]; then
            ip route get "${route_target}" || true
        else
            echo "Não foi possível resolver ${TEST_TARGET}."
        fi

        echo "Teste de ping:"
        ping -I "${VPN_IF}" -c 4 "${TEST_TARGET}" || true
    fi
}

attempt_connections() {
    local account_name
    local server_endpoint
    local hub_name

    CONNECTED_ACCOUNT=""
    CONNECTED_SERVER=""
    REACHABILITY_FAILURE=0

    while IFS= read -r account_name; do
        if [ "${account_name}" = "${PRIMARY_ACCOUNT}" ]; then
            hub_name="${PRIMARY_HUB}"
        else
            hub_name="${DIRECT_HUB}"
        fi

        while IFS= read -r server_endpoint; do
            echo "Tentando ${account_name} via ${server_endpoint}..."
            set_account_endpoint "${account_name}" "${server_endpoint}" "${hub_name}"

            if connect_account "${account_name}"; then
                configure_network

                if validate_connectivity "${account_name}"; then
                    CONNECTED_ACCOUNT="${account_name}"
                    CONNECTED_SERVER="${server_endpoint}"
                    return 0
                fi

                REACHABILITY_FAILURE=1
                disconnect_account "${account_name}"
                cleanup_routes
                cleanup_interface_state
            else
                disconnect_account "${account_name}"
                cleanup_routes
                cleanup_interface_state
            fi
        done < <(get_account_servers "${account_name}")
    done < <(select_accounts)

    return 1
}

echo "Verificando status das opções de conexão VPN..."
log_debug "Iniciando execução do rvpn.sh em modo ${MODE}."

validate_configuration

reset_connection_state

if ! attempt_connections; then
    if [ "${REACHABILITY_FAILURE}" -eq 1 ] && [ -n "${TEST_TARGET}" ] && [ "${AUTO_RESTART_ON_REACHABILITY_FAILURE}" = "1" ]; then
        echo "Falha de conectividade detectada. Reiniciando o cliente SoftEther e tentando novamente..."
        collect_diagnostics "before-automatic-restart"
        RESTART_CLIENT_NOW=1
        reset_connection_state
        attempt_connections || true
    fi
fi

if [ -z "${CONNECTED_ACCOUNT}" ]; then
    collect_diagnostics "final-connection-failure"
    if [ "${REACHABILITY_FAILURE}" -eq 1 ] && [ -n "${TEST_TARGET}" ]; then
        echo "Nenhuma opção de conexão VPN alcançou ${TEST_TARGET}."
    else
        echo "Nenhuma opção de conexão VPN conseguiu estabelecer sessão."
    fi
    exit 1
fi

echo "Usando a conexão ${CONNECTED_ACCOUNT}."
if [ -n "${CONNECTED_SERVER}" ]; then
    echo "Servidor ativo: ${CONNECTED_SERVER}."
    log_debug "Conexão ativa selecionada: ${CONNECTED_ACCOUNT} via ${CONNECTED_SERVER}."
else
    log_debug "Conexão ativa selecionada: ${CONNECTED_ACCOUNT}."
fi
if [ "${CAPTURE_SUCCESS_DIAGNOSTICS}" = "1" ]; then
    collect_diagnostics "success" "${CONNECTED_ACCOUNT}"
fi
show_final_state
