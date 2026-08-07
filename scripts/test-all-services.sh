#!/bin/bash
# ─── Test ALL Services — results.com.br Infrastructure ────────────────────
# Valida todas as stacks: MySQL, ProxySQL, DNS, Email, Web, VPN, Edge SNI
#
# Uso:
#   ./scripts/test-all-services.sh                        # local, todos os testes
#   TARGET_HOST=10.10.2.30 ./scripts/test-all-services.sh # remoto via SSH
#   TEST_ONLY=dns ./scripts/test-all-services.sh          # apenas testes de DNS
#   TEST_ONLY=dns,email TARGET_HOST=10.10.2.30 ./scripts/test-all-services.sh
#
# TEST_ONLY: filtra por nome de função (ex: dns, email, web, mysql, vpn)
#   valores válidos: galera, proxysql, dns, email, web, vpn, edge, security,
#   docker, librenms, dns_internal, replication, rspamd, ldap, tls
#
# Requisitos: docker, mysql/mariadb-client, dig, nc, curl, openssl
# ───────────────────────────────────────────────────────────────────────────
set -euo pipefail

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# ── Configuração ───────────────────────────────────────────────────────────
TARGET_HOST="${TARGET_HOST:-}"
SSH_USER="${SSH_USER:-root}"
SSH_PASSWORD="${SSH_PASSWORD:-}"
MYSQL_ROOT_PASS="${MYSQL_ROOT_PASS:-resu100dba}"
MYSQL_APP_USER="${MYSQL_APP_USER:-resultsdba}"
MYSQL_APP_PASS="${MYSQL_APP_PASS:-resu100dba}"
PROXYSQL_ADMIN_PASS="${PROXYSQL_ADMIN_PASS:-pr0xysql@dm1n2026}"

DNS_VIP1="${DNS_VIP1:-10.10.2.1}"
DNS_VIP2="${DNS_VIP2:-10.10.2.20}"
PUBLIC_DNS_IP="${PUBLIC_DNS_IP:-201.6.110.53}"
SMTP_IP="${SMTP_IP:-10.10.2.3}"
IMAP_IP="${IMAP_IP:-10.10.2.3}"
WEB_IP="${WEB_IP:-10.10.2.60}"
VPN_IP="${VPN_IP:-10.10.2.30}"

TIMEOUT="${TIMEOUT:-10}"
TEST_ONLY="${TEST_ONLY:-}"
PASS=0
FAIL=0
SKIP=0

# ── Helpers ────────────────────────────────────────────────────────────────
remote() {
    if [ -n "$TARGET_HOST" ]; then
        if [ -n "${SSH_PASSWORD:-}" ]; then
            sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no -o ConnectTimeout=5 \
                "${SSH_USER}@${TARGET_HOST}" "$1"
        else
            ssh -o StrictHostKeyChecking=no -o ConnectTimeout=5 \
                "${SSH_USER}@${TARGET_HOST}" "$1"
        fi
    else
        eval "$1"
    fi
}

pass() { echo -e "  ${GREEN}[PASS]${NC} $1"; PASS=$((PASS + 1)); }
fail() { echo -e "  ${RED}[FAIL]${NC} $1"; FAIL=$((FAIL + 1)); }
skip() { echo -e "  ${YELLOW}[SKIP]${NC} $1"; SKIP=$((SKIP + 1)); }
info() { echo -e "  ${CYAN}[INFO]${NC} $1"; }

# Retorna 0 (true) se o teste deve rodar com base em TEST_ONLY
should_run() {
    local name="$1"
    [ -z "$TEST_ONLY" ] && return 0
    echo "$TEST_ONLY" | tr ',' '\n' | grep -qw "$name"
}

section() {
    echo ""
    echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
}

# ── 1. MySQL Galera ────────────────────────────────────────────────────────
test_mysql_galera() {
    section "1. MySQL Galera Cluster"

    local nodes="srvmysql0 srvmysql1 srvmysql2"
    local cluster_ok=true

    for node in $nodes; do
        local status
        status=$(remote "docker inspect $node --format '{{.State.Health.Status}}' 2>/dev/null")
        if [ "$status" = "healthy" ]; then
            pass "$node: $status"
        else
            fail "$node: $status (esperado: healthy)"
            cluster_ok=false
        fi
    done

    if $cluster_ok; then
        local size
        size=$(remote "docker exec srvmysql0 mariadb -u root -p'${MYSQL_ROOT_PASS}' -e \"SHOW STATUS LIKE 'wsrep_cluster_size'\" 2>/dev/null" | tail -1 | awk '{print $2}')
        if [ "$size" = "3" ]; then
            pass "wsrep_cluster_size = 3"
        else
            fail "wsrep_cluster_size = $size (esperado: 3)"
        fi

        local state
        state=$(remote "docker exec srvmysql0 mariadb -u root -p'${MYSQL_ROOT_PASS}' -e \"SHOW STATUS LIKE 'wsrep_local_state_comment'\" 2>/dev/null" | tail -1 | awk '{print $2}')
        if [ "$state" = "Synced" ]; then
            pass "wsrep_local_state = Synced"
        else
            fail "wsrep_local_state = $state (esperado: Synced)"
        fi

        # Teste de escrita
        if remote "docker exec srvmysql0 mariadb -u root -p'${MYSQL_ROOT_PASS}' -e 'CREATE DATABASE IF NOT EXISTS test_galera_write; DROP DATABASE test_galera_write' 2>/dev/null"; then
            pass "Escrita no Galera: OK"
        else
            fail "Escrita no Galera: FALHOU"
        fi
    fi
}

# ── 2. ProxySQL ────────────────────────────────────────────────────────────
test_proxysql() {
    section "2. ProxySQL"

    local status
    status=$(remote "docker inspect mysql-proxysql --format '{{.State.Health.Status}}' 2>/dev/null")
    if [ "$status" = "healthy" ]; then
        pass "mysql-proxysql: $status"
    else
        fail "mysql-proxysql: $status (esperado: healthy)"
    fi

    # Teste de conexão via ProxySQL
    if remote "mysql -u '${MYSQL_APP_USER}' -p'${MYSQL_APP_PASS}' -h 127.0.0.1 -P 6033 -e 'SELECT 1' 2>/dev/null >/dev/null"; then
        pass "Conexao via ProxySQL:6033: OK"
    else
        fail "Conexao via ProxySQL:6033: FALHOU"
    fi

    # Verificar servidores backend
    local srv_count
    srv_count=$(remote "mysql -u admin -p'${PROXYSQL_ADMIN_PASS}' -h 127.0.0.1 -P 6032 -e \"SELECT COUNT(*) FROM mysql_servers WHERE status='ONLINE'\" 2>/dev/null" | tail -1)
    if [ "$srv_count" -ge 2 ]; then
        pass "Backends ONLINE no ProxySQL: $srv_count"
    else
        fail "Backends ONLINE no ProxySQL: $srv_count (esperado: >=2)"
    fi
}

# ── 3. DNS ─────────────────────────────────────────────────────────────────
test_dns() {
    section "3. DNS (dnsdist + PowerDNS)"

    # Containers
    for svc in pdns-auth pdns-recursor dns-dnsdist; do
        local status
        status=$(remote "docker inspect $svc --format '{{.State.Status}}' 2>/dev/null")
        if [ "$status" = "running" ]; then
            pass "$svc: running"
        else
            fail "$svc: $status (esperado: running)"
        fi
    done

    # Resolução pública (results.com.br)
    for vip in "$DNS_VIP1" "$DNS_VIP2"; do
        local result
        result=$(remote "dig +short @$vip results.com.br 2>/dev/null")
        if [ -n "$result" ]; then
            pass "results.com.br @$vip -> $result"
        else
            fail "results.com.br @$vip -> (vazio)"
        fi
    done

    # Resolução MX
    local mx_result
    mx_result=$(remote "dig +short @${DNS_VIP1} mx1.results.com.br 2>/dev/null")
    if [ -n "$mx_result" ]; then
        pass "mx1.results.com.br -> $mx_result"
    else
        fail "mx1.results.com.br -> (vazio)"
    fi

    # Teste de MX record
    local mx_records
    mx_records=$(remote "dig +short @${DNS_VIP1} results.com.br MX 2>/dev/null")
    if echo "$mx_records" | grep -q "mx"; then
        pass "MX records: OK"
    else
        fail "MX records: (vazio ou invalido)"
    fi

    # Resolução recursiva (domínio externo) — via dnsdist VIPs
    # Testa se o pdns-recursor consegue encaminhar para upstream (8.8.8.8 / 1.1.1.1)
    for vip in "$DNS_VIP1" "$DNS_VIP2"; do
        local ext_result
        ext_result=$(remote "dig +short @$vip google.com 2>/dev/null")
        if [ -n "$ext_result" ]; then
            pass "google.com (externo) @$vip -> $ext_result"
        else
            fail "google.com (externo) @$vip -> (vazio — recursor pode estar off)"
        fi
    done

    # Resolução recursiva TCP (fallback quando UDP falha/bloqueia)
    local tcp_result
    tcp_result=$(remote "dig +short +tcp @${DNS_VIP1} cloudflare.com 2>/dev/null")
    if [ -n "$tcp_result" ]; then
        pass "cloudflare.com (externo TCP) @${DNS_VIP1} -> $tcp_result"
    else
        fail "cloudflare.com (externo TCP) @${DNS_VIP1} -> (vazio)"
    fi

    # Recursor diretamente (bypass dnsdist) — saúde do pdns-recursor isolado
    local recursor_result
    recursor_result=$(remote "dig +short @127.0.0.1 -p 5301 google.com 2>/dev/null")
    if [ -n "$recursor_result" ]; then
        pass "google.com @recursor (127.0.0.1:5301) -> $recursor_result"
    else
        fail "google.com @recursor (127.0.0.1:5301) -> (vazio — upstream quebrado?)"
    fi

    # Auth diretamente — saúde do pdns-auth isolado
    local auth_result
    auth_result=$(remote "dig +short @127.0.0.1 -p 5300 results.com.br 2>/dev/null")
    if [ -n "$auth_result" ]; then
        pass "results.com.br @auth (127.0.0.1:5300) -> $auth_result"
    else
        fail "results.com.br @auth (127.0.0.1:5300) -> (vazio)"
    fi

    # ── DNS externo (borda pública) ────────────────────────────────────────
    # Testa se o IP público (roteador Claro → srvfw0 → dnsdist) responde
    # consultas DNS autoritativas vindas da internet. Roda localmente
    # (não via remote) porque queremos simular o caminho WAN → DMZ → DNAT.
    local public_dns_ip="${PUBLIC_DNS_IP:-201.6.110.53}"
    info "DNS externo via ${public_dns_ip} (borda pública)"

    # UDP (primário — se quebrado, resolvedores externos fazem fallback p/ TCP)
    local ext_udp
    ext_udp=$(dig +short +notcp +time=5 +tries=1 results.com.br MX @"$public_dns_ip" 2>/dev/null)
    if echo "$ext_udp" | grep -q "mx"; then
        pass "  UDP ${public_dns_ip}:53 → $(echo "$ext_udp" | tr '\n' ' ')"
    else
        fail "  UDP ${public_dns_ip}:53 → (vazio — DMZ/UDP quebrado?)"
    fi

    # TCP (fallback — deve sempre funcionar mesmo se UDP estiver bloqueado)
    local ext_tcp
    ext_tcp=$(dig +short +tcp +time=5 +tries=1 results.com.br MX @"$public_dns_ip" 2>/dev/null)
    if echo "$ext_tcp" | grep -q "mx"; then
        pass "  TCP ${public_dns_ip}:53 → $(echo "$ext_tcp" | tr '\n' ' ')"
    else
        fail "  TCP ${public_dns_ip}:53 → (vazio — DMZ/TCP quebrado?)"
    fi

    # Resolução via resolvedores públicos externos — teste fim a fim real.
    # Cada resolver é testado via UDP (primário) e TCP (fallback) para
    # detectar bloqueios de protocolo específico em qualquer ponto do caminho.
    for resolver in "8.8.8.8:Google" "1.1.1.1:Cloudflare" "9.9.9.9:Quad9"; do
        local ip="${resolver%%:*}"
        local name="${resolver##*:}"

        local udp_result
        udp_result=$(dig +short +notcp +time=5 +tries=1 results.com.br MX @"$ip" 2>/dev/null)
        if echo "$udp_result" | grep -q "mx"; then
            pass "  ${name} UDP (${ip}) → $(echo "$udp_result" | tr '\n' ' ')"
        else
            fail "  ${name} UDP (${ip}) → (vazio)"
        fi

        local tcp_result
        tcp_result=$(dig +short +tcp +time=5 +tries=1 results.com.br MX @"$ip" 2>/dev/null)
        if echo "$tcp_result" | grep -q "mx"; then
            pass "  ${name} TCP (${ip}) → $(echo "$tcp_result" | tr '\n' ' ')"
        else
            fail "  ${name} TCP (${ip}) → (vazio)"
        fi
    done
}

# ── 4. Email Stack ─────────────────────────────────────────────────────────
test_email() {
    section "4. Email (Postfix + Dovecot + Rspamd + ClamAV + LDAP)"

    local mail_services="results-mail-postfix results-mail-postfix-mx2 results-mail-dovecot results-mail-rspamd results-mail-clamav results-ldap results-mail-redis results-mail-certbot"
    local mail_ok=true

    for svc in $mail_services; do
        local status
        status=$(remote "docker inspect $svc --format '{{.State.Health.Status}}' 2>/dev/null")
        if [ "$status" = "healthy" ]; then
            pass "$svc: healthy"
        else
            fail "$svc: $status (esperado: healthy)"
            mail_ok=false
        fi
    done

    # SMTP MX1 (porta 25)
    if remote "echo 'EHLO test' | timeout $TIMEOUT nc -w 3 ${SMTP_IP} 25 2>/dev/null | grep -q '220'"; then
        pass "SMTP MX1 (${SMTP_IP}:25): OK"
    else
        fail "SMTP MX1 (${SMTP_IP}:25): FALHOU"
    fi

    # SMTP MX2 (porta 25)
    if remote "echo 'EHLO test' | timeout $TIMEOUT nc -w 3 10.10.2.23 25 2>/dev/null | grep -q '220'"; then
        pass "SMTP MX2 (10.10.2.23:25): OK"
    else
        fail "SMTP MX2 (10.10.2.23:25): FALHOU"
    fi

    # IMAPS (porta 993) — usa wrapper bash para dar tempo ao TLS handshake
    local imaps_out
    imaps_out=$(remote "timeout 8 bash -c \"echo 'A1 LOGOUT' | openssl s_client -connect ${IMAP_IP}:993 -quiet 2>/dev/null\"" 2>/dev/null)
    if echo "$imaps_out" | grep -qE 'Dovecot|OK.*ready|Logout'; then
        pass "IMAPS (${IMAP_IP}:993): OK"
    else
        fail "IMAPS (${IMAP_IP}:993): FALHOU"
    fi

    # Submission (porta 587)
    if remote "echo 'EHLO test' | timeout $TIMEOUT nc -w 3 ${SMTP_IP} 587 2>/dev/null | grep -q '220'"; then
        pass "Submission (${SMTP_IP}:587): OK"
    else
        fail "Submission (${SMTP_IP}:587): FALHOU"
    fi

    # SMTPS (porta 465) — usa wrapper bash para dar tempo ao TLS handshake
    local smtps_out
    smtps_out=$(remote "timeout 8 bash -c \"echo 'QUIT' | openssl s_client -connect ${SMTP_IP}:465 -quiet 2>/dev/null\"" 2>/dev/null)
    if echo "$smtps_out" | grep -qE 'ESMTP|220'; then
        pass "SMTPS (${SMTP_IP}:465): OK"
    else
        fail "SMTPS (${SMTP_IP}:465): FALHOU"
    fi
}

# ── 5. Web Stack ───────────────────────────────────────────────────────────
test_web() {
    section "5. Web (Apache + Joomla + Roundcube)"

    local web_services="secure-httpd results-joomla httpd-lsyncd joomla-lsyncd httpd-subdomain-sync"
    local web_ok=true

    for svc in $web_services; do
        local status
        status=$(remote "docker inspect $svc --format '{{.State.Health.Status}}' 2>/dev/null")
        if [ "$status" = "healthy" ]; then
            pass "$svc: healthy"
        else
            fail "$svc: $status (esperado: healthy)"
            web_ok=false
        fi
    done

    # HTTPS — usa --resolve para TLS SNI no edge-sni (HAProxy)
    local http_code
    http_code=$(remote "curl -sk -o /dev/null -w '%{http_code}' --resolve www.results.com.br:443:${WEB_IP} https://www.results.com.br/ 2>/dev/null" || true)
    if [ -n "$http_code" ] && [ "$http_code" != "000" ]; then
        pass "HTTPS (www.results.com.br): HTTP $http_code"
    else
        fail "HTTPS (www.results.com.br): sem resposta"
    fi

    # Joomla admin — 200/401 = OK (comportamento correto)
    local joomla_code
    joomla_code=$(remote "curl -sk -o /dev/null -w '%{http_code}' --resolve www.results.com.br:443:${WEB_IP} https://www.results.com.br/administrator/ 2>/dev/null" || true)
    if [ "$joomla_code" = "200" ] || [ "$joomla_code" = "301" ] || [ "$joomla_code" = "302" ] || [ "$joomla_code" = "401" ]; then
        pass "Joomla admin (www.results.com.br): HTTP $joomla_code"
    else
        fail "Joomla admin (www.results.com.br): HTTP $joomla_code (esperado: 200/301/302/401)"
    fi

    # Webmail — via Apache proxy com SNI correto
    local webmail_code
    webmail_code=$(remote "curl -sk -o /dev/null -w '%{http_code}' --resolve www.results.com.br:443:${WEB_IP} https://www.results.com.br/webmail/ 2>/dev/null" || true)
    if [ "$webmail_code" = "200" ] || [ "$webmail_code" = "301" ] || [ "$webmail_code" = "302" ] || [ "$webmail_code" = "401" ]; then
        pass "Webmail Roundcube (www.results.com.br): HTTP $webmail_code"
    else
        # Fallback: testa direto no container Joomla
        webmail_code=$(remote "docker exec results-joomla curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/webmail/ 2>/dev/null" || true)
        if [ "$webmail_code" = "200" ] || [ "$webmail_code" = "301" ] || [ "$webmail_code" = "302" ]; then
            pass "Webmail Roundcube (container): HTTP $webmail_code"
        else
            fail "Webmail Roundcube: HTTP $webmail_code (esperado: 200/301/302)"
        fi
    fi
}

# ── 6. VPN ─────────────────────────────────────────────────────────────────
test_vpn() {
    section "6. VPN (SoftEther)"

    local status
    status=$(remote "docker inspect rvpn --format '{{.State.Health.Status}}' 2>/dev/null")
    if [ "$status" = "healthy" ]; then
        pass "rvpn: healthy"
    else
        fail "rvpn: $status (esperado: healthy)"
    fi

    # Porta 5555 (VPN client)
    if remote "timeout 3 nc -z ${VPN_IP} 5555 2>/dev/null"; then
        pass "VPN port 5555: ABERTA"
    else
        fail "VPN port 5555: FECHADA"
    fi

    # Porta 443 (SSL-VPN)
    if remote "timeout 3 nc -z ${VPN_IP} 443 2>/dev/null"; then
        pass "VPN port 443: ABERTA"
    else
        fail "VPN port 443: FECHADA"
    fi
}

# ── 7. Edge SNI ────────────────────────────────────────────────────────────
test_edge_sni() {
    section "7. Edge SNI (HAProxy)"

    local status
    status=$(remote "docker inspect edge-sni --format '{{.State.Health.Status}}' 2>/dev/null")
    if [ "$status" = "healthy" ]; then
        pass "edge-sni: healthy"
    else
        fail "edge-sni: $status (esperado: healthy)"
    fi
}

# ── 8. Segurança ───────────────────────────────────────────────────────────
test_security() {
    section "8. Seguranca (Firewall + Fail2ban + Kernel)"

    # Firewall
    local fw_rules
    fw_rules=$(remote "iptables -S RESULTS-RATE-LIMIT 2>/dev/null | wc -l")
    if [ "$fw_rules" -ge 5 ]; then
        pass "Firewall RESULTS-RATE-LIMIT: $fw_rules regras"
    else
        fail "Firewall RESULTS-RATE-LIMIT: $fw_rules regras (esperado: >=5)"
    fi

    # Fail2ban
    local f2b_jails
    f2b_jails=$(remote "fail2ban-client status 2>/dev/null | grep 'Number of jail'" | awk '{print $NF}')
    if [ -n "$f2b_jails" ] && [ "$f2b_jails" -ge 2 ]; then
        pass "Fail2ban: $f2b_jails jails ativas"
    else
        fail "Fail2ban: $f2b_jails jails (esperado: >=2)"
    fi

    # Kernel hardening
    local kptr
    kptr=$(remote "sysctl -n kernel.kptr_restrict 2>/dev/null")
    if [ "$kptr" = "2" ]; then
        pass "kernel.kptr_restrict = 2"
    else
        fail "kernel.kptr_restrict = $kptr (esperado: 2)"
    fi

    # SSH
    local ssh_root
    ssh_root=$(remote "grep '^PermitRootLogin' /etc/ssh/sshd_config 2>/dev/null" | awk '{print $2}')
    if [ "$ssh_root" = "prohibit-password" ] || [ "$ssh_root" = "without-password" ] || [ "$ssh_root" = "no" ]; then
        pass "SSH PermitRootLogin = $ssh_root"
    else
        fail "SSH PermitRootLogin = $ssh_root (deve ser prohibit-password)"
    fi
}

# ── 9. Docker ──────────────────────────────────────────────────────────────
test_docker() {
    section "9. Docker Health"

    local total healthy unhealthy
    total=$(remote "docker ps -q 2>/dev/null | wc -l")
    healthy=$(remote "docker ps --filter 'health=healthy' -q 2>/dev/null | wc -l")
    unhealthy=$(remote "docker ps --filter 'health=unhealthy' -q 2>/dev/null | wc -l")

    pass "Total containers: $total"
    pass "Healthy: $healthy"
    if [ "$unhealthy" -eq 0 ]; then
        pass "Unhealthy: 0"
    else
        # Exibe apenas containers unhealthy que pertencem a este projeto
        local bad_containers
        bad_containers=$(remote "docker ps --filter 'health=unhealthy' --format '{{.Names}}' 2>/dev/null | grep -v 'r-agent2-'" || true)
        if [ -z "$bad_containers" ]; then
            pass "Unhealthy: $unhealthy (apenas r-observe, ignorado)"
        else
            fail "Unhealthy: $unhealthy — $bad_containers"
            remote "docker ps --filter 'health=unhealthy' --format 'table {{.Names}}\t{{.Status}}' 2>/dev/null"
        fi
    fi
}

# ── 10. LibreNMS ───────────────────────────────────────────────────────────
test_librenms() {
    section "10. LibreNMS (Monitoramento)"

    local status
    status=$(remote "docker ps --filter 'name=librenms' --format '{{.Status}}' 2>/dev/null | head -1")
    if echo "$status" | grep -q "Up"; then
        pass "librenms: running"
    else
        skip "librenms: nao encontrado ou parado"
        return
    fi

    # Web UI
    local nms_code
    nms_code=$(remote "curl -sk -o /dev/null -w '%{http_code}' --resolve librenms.results.intranet:443:${WEB_IP} https://librenms.results.intranet/ 2>/dev/null" || true)
    if [ "$nms_code" = "200" ] || [ "$nms_code" = "302" ] || [ "$nms_code" = "401" ]; then
        pass "LibreNMS Web UI: HTTP $nms_code"
    else
        fail "LibreNMS Web UI: HTTP $nms_code (esperado: 200/302/401)"
    fi
}

# ── 11. DNS Interno (CoreDNS) ──────────────────────────────────────────────
test_dns_internal() {
    section "11. DNS Interno (CoreDNS)"

    local status
    status=$(remote "docker ps --filter 'name=coredns' --format '{{.Status}}' 2>/dev/null | head -1")
    if echo "$status" | grep -q "Up"; then
        pass "coredns: running"

        # Resolução interna
        local int_result
        int_result=$(remote "dig +short @127.0.0.1 -p 5353 srvmysql.results.intranet 2>/dev/null" || true)
        if [ -n "$int_result" ]; then
            pass "srvmysql.results.intranet @CoreDNS -> $int_result"
        else
            fail "srvmysql.results.intranet @CoreDNS -> (vazio)"
        fi
    else
        skip "coredns: nao encontrado ou parado"
    fi
}

# ── 12. MySQL Replicação ───────────────────────────────────────────────────
test_mysql_replication() {
    section "12. MySQL Replicacao (Slave)"

    local status
    status=$(remote "docker inspect mysql-slave --format '{{.State.Health.Status}}' 2>/dev/null")
    if [ "$status" = "healthy" ]; then
        pass "mysql-slave: healthy"
    else
        skip "mysql-slave: $status"
        return
    fi

    # Status de replicação
    local slave_status
    slave_status=$(remote "docker exec mysql-slave mariadb -u root -p'${MYSQL_ROOT_PASS}' -e 'SHOW SLAVE STATUS\G' 2>/dev/null" || true)
    if [ -z "$slave_status" ]; then
        skip "Replicacao slave: nao configurada (SHOW SLAVE STATUS vazio)"
    elif echo "$slave_status" | grep -qE 'Slave_IO_Running: Yes|Slave_SQL_Running: Yes'; then
        pass "Replicacao slave: ativa"
        echo "$slave_status" | grep -E 'Seconds_Behind_Master' | head -1
    else
        fail "Replicacao slave: inativa ou erro"
        echo "$slave_status" | grep -E 'Slave_IO_Running|Slave_SQL_Running|Last_Error' | head -5
    fi
}

# ── 13. Rspamd ─────────────────────────────────────────────────────────────
test_rspamd() {
    section "13. Rspamd (Anti-spam)"

    # Web UI — usa IP do container na rede Docker
    local rspamd_ip
    rspamd_ip=$(remote "docker inspect results-mail-rspamd --format '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' 2>/dev/null" || true)
    if [ -n "$rspamd_ip" ]; then
        local rspamd_code
        rspamd_code=$(remote "curl -sk -o /dev/null -w '%{http_code}' http://${rspamd_ip}:11334/ 2>/dev/null" || true)
        if [ "$rspamd_code" = "200" ] || [ "$rspamd_code" = "401" ]; then
            pass "Rspamd Web UI: HTTP $rspamd_code"
        else
            fail "Rspamd Web UI: HTTP $rspamd_code (esperado: 200)"
        fi
    else
        fail "Rspamd Web UI: IP do container nao encontrado"
    fi

    # Bayes stats (via redis)
    local bayes_ham bayes_spam
    bayes_ham=$(remote "docker exec results-mail-redis redis-cli GET BAYES_HAM_keys 2>/dev/null" || true)
    bayes_spam=$(remote "docker exec results-mail-redis redis-cli GET BAYES_SPAM_keys 2>/dev/null" || true)
    if [ -n "$bayes_ham" ] && [ "$bayes_ham" -gt 0 ] 2>/dev/null; then
        pass "Rspamd Bayes HAM: $bayes_ham"
    else
        skip "Rspamd Bayes HAM: insuficiente (<1)"
    fi
    if [ -n "$bayes_spam" ] && [ "$bayes_spam" -gt 0 ] 2>/dev/null; then
        pass "Rspamd Bayes SPAM: $bayes_spam"
    else
        skip "Rspamd Bayes SPAM: insuficiente (<1)"
    fi
}

# ── 14. LDAP Auth ──────────────────────────────────────────────────────────
test_ldap_auth() {
    section "14. LDAP (Autenticacao)"

    # Teste de bind admin (requer credenciais)
    local ldap_pass="${LDAP_ADMIN_PASSWORD:-resu1@@admin}"

    local ldap_result
    ldap_result=$(remote "docker exec results-ldap ldapsearch -x -H ldap://127.0.0.1:389 -b 'dc=results,dc=com,dc=br' -D 'cn=admin,dc=results,dc=com,dc=br' -w '${ldap_pass}' -s base dn 2>/dev/null | grep -c 'dn:'" || true)
    if [ "$ldap_result" -ge 1 ] 2>/dev/null; then
        pass "LDAP bind admin: OK"
    else
        fail "LDAP bind admin: falhou"
    fi

    # Contagem de usuários
    local user_count
    user_count=$(remote "docker exec results-ldap ldapsearch -x -H ldap://127.0.0.1:389 -b 'ou=people,dc=results,dc=com,dc=br' -D 'cn=admin,dc=results,dc=com,dc=br' -w '${ldap_pass}' '(objectClass=inetOrgPerson)' dn 2>/dev/null | grep -c 'dn:'" || true)
    if [ "$user_count" -ge 2 ] 2>/dev/null; then
        pass "LDAP usuarios: $user_count"
    else
        fail "LDAP usuarios: $user_count (esperado: >=2)"
    fi
}

# ── 15. TLS Certificados ───────────────────────────────────────────────────
test_tls_certs() {
    section "15. TLS (Certificados)"

    local domains="www.results.com.br mx1.results.com.br imap.results.com.br"
    local all_ok=true

    for domain in $domains; do
        local expiry
        expiry=$(remote "echo | timeout 5 openssl s_client -servername $domain -connect ${WEB_IP}:443 2>/dev/null | openssl x509 -noout -enddate 2>/dev/null | cut -d= -f2" || true)
        if [ -n "$expiry" ]; then
            local expiry_epoch valid_epoch now_epoch days_left
            expiry_epoch=$(date -d "$expiry" +%s 2>/dev/null || echo 0)
            now_epoch=$(date +%s)
            days_left=$(( (expiry_epoch - now_epoch) / 86400 ))
            if [ "$days_left" -gt 30 ]; then
                pass "$domain TLS: expira em $days_left dias ($expiry)"
            elif [ "$days_left" -gt 0 ]; then
                fail "$domain TLS: expira em APENAS $days_left dias ($expiry)"
            else
                fail "$domain TLS: EXPIRADO ($expiry)"
            fi
        else
            fail "$domain TLS: nao foi possivel obter certificado"
            all_ok=false
        fi
    done
}

# ── Main ───────────────────────────────────────────────────────────────────
main() {
    echo "=============================================="
    echo "  RESULTS INFRA — Teste Completo de Servicos"
    echo "  $(date)"
    if [ -n "$TARGET_HOST" ]; then
        echo "  Alvo: ${SSH_USER}@${TARGET_HOST}"
    else
        echo "  Alvo: localhost"
    fi
    echo "=============================================="

    should_run galera       && test_mysql_galera
    should_run proxysql     && test_proxysql
    should_run dns          && test_dns
    should_run email        && test_email
    should_run web          && test_web
    should_run vpn          && test_vpn
    should_run edge         && test_edge_sni
    should_run security     && test_security
    should_run docker       && test_docker
    should_run librenms     && test_librenms
    should_run dns_internal && test_dns_internal
    should_run replication  && test_mysql_replication
    should_run rspamd       && test_rspamd
    should_run ldap         && test_ldap_auth
    should_run tls          && test_tls_certs

    echo ""
    echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
    echo -e "  ${GREEN}PASS: $PASS${NC}  ${RED}FAIL: $FAIL${NC}  ${YELLOW}SKIP: $SKIP${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"

    if [ "$FAIL" -gt 0 ]; then
        echo ""
        echo -e "${RED}ALGUNS TESTES FALHARAM!${NC}"
        exit 1
    else
        echo ""
        echo -e "${GREEN}TODOS OS TESTES PASSARAM!${NC}"
    fi
}

main "$@"
