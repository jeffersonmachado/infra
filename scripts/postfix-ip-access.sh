#!/usr/bin/env bash
# ─── Postfix IP Access CLI ────────────────────────────────────────────────
# Gerencia whitelist/blacklist de IPs via banco MySQL.
# A tabela results.postfix_client_access é consultada pelo Postfix
# via proxy:mysql (postscreen_access_list + smtpd_client_restrictions).
#
# Uso:
#   ./postfix-ip-access.sh list                          Lista todos
#   ./postfix-ip-access.sh check 209.85.219.43           Verifica IP
#   ./postfix-ip-access.sh add 1.2.3.0/24 OK "Descricao" Adiciona
#   ./postfix-ip-access.sh block 5.6.7.8 "Spammer"       Bloqueia IP
#   ./postfix-ip-access.sh remove 1.2.3.0/24              Remove
#   ./postfix-ip-access.sh reload                         Recarrega Postfix
# ───────────────────────────────────────────────────────────────────────────
set -eu

MYSQL_HOST="${MYSQL_HOST:-10.10.2.79}"
MYSQL_PORT="${MYSQL_PORT:-3306}"
MYSQL_USER="${MYSQL_USER:-root}"
MYSQL_PASS="${MYSQL_PASS:-resu100dba}"
MYSQL_DB="${MYSQL_DB:-results}"
TABLE="postfix_client_access"

MYSQL_CMD="docker exec srvmysql0 mysql -u $MYSQL_USER -p$MYSQL_PASS -h $MYSQL_HOST -P $MYSQL_PORT $MYSQL_DB"

log()  { echo "  $*"; }
err()  { echo "ERRO: $*" >&2; exit 1; }

cmd_list() {
    echo "=== IP Access List ($TABLE) ==="
    $MYSQL_CMD -t -e "SELECT id, ip, action, description, created_at FROM $TABLE ORDER BY id" 2>/dev/null
}

cmd_check() {
    local ip="${1:?Informe o IP}"
    echo "Verificando $ip..."
    local result
    result=$($MYSQL_CMD -N -e "SELECT action FROM $TABLE WHERE '$ip' LIKE 
        REPLACE(REPLACE(ip, '.', '.'), '/', '/') LIMIT 1" 2>/dev/null)
    # MySQL não faz longest-prefix match nativamente, usamos Postfix para isso
    # Aqui fazemos uma busca simples
    result=$($MYSQL_CMD -N -e "SELECT CONCAT(ip, ' -> ', action, ' (', COALESCE(description,''), ')') 
        FROM $TABLE WHERE ip='$ip' LIMIT 1" 2>/dev/null)
    if [ -n "$result" ]; then
        echo "  $result"
    else
        echo "  $ip -> nao encontrado (passa pelos DNSBLs)"
    fi
}

cmd_add() {
    local ip="${1:?Informe o IP/CIDR}"
    local action="${2:-OK}"
    local desc="${3:-}"
    $MYSQL_CMD -e "INSERT INTO $TABLE (ip, action, description) VALUES ('$ip', '$action', '$desc') 
        ON DUPLICATE KEY UPDATE action='$action', description='$desc'" 2>/dev/null
    log "Adicionado: $ip -> $action ($desc)"
}

cmd_block() {
    local ip="${1:?Informe o IP/CIDR}"
    local desc="${2:-Bloqueado manualmente}"
    cmd_add "$ip" "REJECT" "$desc"
    log "ATENCAO: execute 'reload' para aplicar o bloqueio"
}

cmd_remove() {
    local ip="${1:?Informe o IP/CIDR}"
    $MYSQL_CMD -e "DELETE FROM $TABLE WHERE ip='$ip'" 2>/dev/null
    log "Removido: $ip"
}

cmd_reload() {
    log "Recarregando Postfix..."
    docker exec results-mail-postfix postfix reload 2>/dev/null || true
    docker exec results-mail-postfix-mx2 postfix reload 2>/dev/null || true
    log "Postfix recarregado"
}

cmd_search() {
    local term="${1:?Informe o termo de busca}"
    $MYSQL_CMD -t -e "SELECT id, ip, action, description, created_at FROM $TABLE 
        WHERE ip LIKE '%$term%' OR description LIKE '%$term%' ORDER BY id" 2>/dev/null
}

# ─── Main ─────────────────────────────────────────────────────────────────
case "${1:-}" in
    list|ls)       cmd_list ;;
    check|lookup)   cmd_check "${2:-}" ;;
    add|whitelist)  cmd_add "${2:-}" "${3:-OK}" "${4:-}" ;;
    block|blacklist) cmd_block "${2:-}" "${3:-}" ;;
    remove|rm|del)  cmd_remove "${2:-}" ;;
    reload)         cmd_reload ;;
    search|find)    cmd_search "${2:-}" ;;
    *)
        echo "Uso: $(basename "$0") {list|check|add|block|remove|search|reload} [args...]"
        echo ""
        echo "  list                    Lista todas as entradas"
        echo "  check <ip>              Verifica um IP"
        echo "  add <ip/cidr> [OK|REJECT] [desc]  Adiciona entrada"
        echo "  block <ip/cidr> [desc]  Bloqueia IP"
        echo "  remove <ip/cidr>        Remove entrada"
        echo "  search <termo>          Busca por IP ou descricao"
        echo "  reload                  Recarrega Postfix"
        echo ""
        echo "Exemplos:"
        echo "  $0 list"
        echo "  $0 add 203.0.113.0/24 OK 'Servidor parceiro'"
        echo "  $0 block 198.51.100.5 'Ataque brute force'"
        echo "  $0 check 209.85.219.43"
        echo "  $0 reload"
        exit 1
        ;;
esac
