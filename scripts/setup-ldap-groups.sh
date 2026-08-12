#!/usr/bin/env bash
# ─── setup-ldap-groups.sh ────────────────────────────────────────────────────
# Cria os grupos LDAP para acesso às interfaces admin:
#   cn=dns-admins,ou=groups,dc=results,dc=com,dc=br
#   cn=vhost-admins,ou=groups,dc=results,dc=com,dc=br
#
# Uso: ./setup-ldap-groups.sh [usuário1,usuário2,...]
#   Se usuários forem passados, adiciona-os como membros dos grupos.
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

LDAP_CONTAINER="${LDAP_CONTAINER:-results-ldap}"
LDAP_BASE="${LDAP_BASE:-dc=results,dc=com,dc=br}"
LDAP_ADMIN="${LDAP_ADMIN:-cn=administrador,dc=results,dc=com,dc=br}"
LDAP_PASSWORD="${LDAP_ADMIN_PASSWORD:-}"
USERS="${1:-}"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

step() { echo -e "\n${BOLD}${CYAN}▶ $*${RESET}"; }
ok()   { echo -e "  ${GREEN}✓${RESET} $*"; }
warn() { echo -e "  ${YELLOW}!${RESET} $*"; }
die()  { echo -e "${RED}ERRO:${RESET} $*" >&2; exit 1; }

ldap_cmd() {
    docker exec "$LDAP_CONTAINER" sh -c "$*"
}

step "Verificando container LDAP..."
docker inspect "$LDAP_CONTAINER" >/dev/null 2>&1 || die "Container $LDAP_CONTAINER não encontrado"
ok "Container $LDAP_CONTAINER OK"

step "Criando grupo dns-admins..."
ldap_cmd "cat > /tmp/dns-admins.ldif << 'EOF'
dn: cn=dns-admins,ou=groups,${LDAP_BASE}
objectClass: groupOfNames
cn: dns-admins
member: ${LDAP_ADMIN}
EOF
ldapadd -x -H ldap://127.0.0.1:389 \
  -D '${LDAP_ADMIN}' -w '${LDAP_PASSWORD}' \
  -f /tmp/dns-admins.ldif 2>&1 || echo '(já existe?)'"
ok "Grupo dns-admins criado/verificado"

step "Criando grupo vhost-admins..."
ldap_cmd "cat > /tmp/vhost-admins.ldif << 'EOF'
dn: cn=vhost-admins,ou=groups,${LDAP_BASE}
objectClass: groupOfNames
cn: vhost-admins
member: ${LDAP_ADMIN}
EOF
ldapadd -x -H ldap://127.0.0.1:389 \
  -D '${LDAP_ADMIN}' -w '${LDAP_PASSWORD}' \
  -f /tmp/vhost-admins.ldif 2>&1 || echo '(já existe?)'"
ok "Grupo vhost-admins criado/verificado"

if [ -n "$USERS" ]; then
    step "Adicionando usuários aos grupos..."
    IFS=',' read -ra USER_ARRAY <<< "$USERS"
    for user in "${USER_ARRAY[@]}"; do
        user="$(echo "$user" | xargs)"
        user_dn="uid=${user},ou=people,${LDAP_BASE}"

        if ldap_cmd "ldapsearch -x -H ldap://127.0.0.1:389 -D '${LDAP_ADMIN}' -w '${LDAP_PASSWORD}' -b '${user_dn}' -s base dn 2>/dev/null | grep -q '^dn:'"; then
            for grp in dns-admins vhost-admins; do
                ldap_cmd "cat > /tmp/add-member.ldif << 'EOF'
dn: cn=${grp},ou=groups,${LDAP_BASE}
changetype: modify
add: member
member: ${user_dn}
EOF
ldapmodify -x -H ldap://127.0.0.1:389 \
  -D '${LDAP_ADMIN}' -w '${LDAP_PASSWORD}' \
  -f /tmp/add-member.ldif 2>&1 || echo '(já é membro?)'"
                ok "  $user → $grp"
            done
        else
            warn "  Usuário '$user' não encontrado no LDAP — pulando"
        fi
    done
fi

echo -e "\n${GREEN}${BOLD}✔ Grupos LDAP configurados.${RESET}"
echo "  dns-admins:   gerencia zonas DNS (PowerDNS-Admin)"
echo "  vhost-admins: gerencia virtual hosts (VHosts Manager)"
echo ""
echo "  Para adicionar usuários:"
echo "    ./scripts/setup-ldap-groups.sh usuario1,usuario2"