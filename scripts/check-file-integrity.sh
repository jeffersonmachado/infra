#!/bin/bash
# Pre-deploy check: verifica se arquivos que deveriam ser arquivos
# nao viraram diretorios no servidor remoto (Docker bind mount footgun).
#
# Uso:
#   LOCAL:  ./scripts/check-file-integrity.sh
#   REMOTO: ./scripts/check-file-integrity.sh --remote

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

MODE="${1:-local}"
TARGET_DIR="${2:-$ROOT_DIR}"

# Arquivos que DEVEM ser arquivos (nao diretorios).
# Estes sao montados como bind mounts em containers Docker.
# Se virarem diretorios, o container nao consegue monta-los.
PROTECTED_FILES=(
    "scripts/mail-certbot-entrypoint.sh"
    "scripts/mail-certs-bootstrap.sh"
    "scripts/docker-deploy.sh"
    "scripts/deploy-all-prod.sh"
    "scripts/deploy-galera.sh"
    "mail/postfix/main.cf.template"
    "mail/dovecot/dovecot.conf.template"
    "mail/postfix/master.cf.template"
    "mail/postfix/ldap_virtual_alias_maps.cf"
    "mail/postfix/mysql_virtual_alias_maps.cf"
    "mail/postfix/mysql_virtual_mailbox_maps.cf"
    "mail/postfix/mysql_virtual_mailbox_domains.cf"
    "mail/rspamd/local.d/worker-controller.inc"
    "roundcube/config.inc.php"
    "apache/httpd.conf.template"
    "apache/Dockerfile"
    "apache/docker-entrypoint.sh"
    "joomla/Dockerfile"
    "joomla/docker-entrypoint.sh"
    "joomla/site.conf"
    "joomla/php-production.ini"
    "lsyncd/Dockerfile"
    "lsyncd/docker-entrypoint.sh"
    "lsyncd/lsyncd.conf.lua.template"
    "edge-sni/haproxy.cfg"
)

check_target() {
    local base="$1"
    local errors=0

    for relpath in "${PROTECTED_FILES[@]}"; do
        local fullpath="$base/$relpath"

        if [ -d "$fullpath" ]; then
            echo -e "${RED}[ERRO] DIRETORIO onde deveria ser ARQUIVO: $relpath${NC}"
            errors=$((errors + 1))
        elif [ -f "$fullpath" ]; then
            echo -e "${GREEN}[OK]${NC} $relpath"
        else
            echo -e "${YELLOW}[AUSENTE]${NC} $relpath"
        fi
    done

    return $errors
}

echo "=== Verificacao de Integridade de Arquivos ==="
echo "Diretorio: $TARGET_DIR"
echo ""

errors=0
check_target "$TARGET_DIR" || errors=$?

echo ""
if [ "$errors" -eq 0 ]; then
    echo -e "${GREEN}Todos os arquivos protegidos estao corretos.${NC}"
    exit 0
else
    echo -e "${RED}$errors arquivo(s) que deveriam ser arquivos viraram diretorios.${NC}"
    echo ""
    echo "CAUSA: Docker cria um DIRETORIO quando o source de um bind mount nao existe."
    echo "CORRECAO:"
    echo "  1. Remova o diretorio:  rm -rf <caminho>"
    echo "  2. Copie o arquivo:     scp <arquivo> root@10.10.2.30:<caminho>"
    echo "  3. Recrie o container:  docker compose up -d --force-recreate <servico>"
    echo ""
    echo "PREVENCAO: Execute este script antes de todo deploy."
    exit 1
fi
