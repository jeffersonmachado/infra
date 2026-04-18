#!/bin/bash

set -euo pipefail

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

TARGET_HOST="${TARGET_HOST:-}"
SMTP_HOST="${SMTP_HOST:-}"
IMAP_HOST="${IMAP_HOST:-}"
POP3_HOST="${POP3_HOST:-}"
SIEVE_HOST="${SIEVE_HOST:-}"
LDAP_HOST="${LDAP_HOST:-}"
LDAP_URI="${LDAP_URI:-}"
LDAP_BASE_DN="${LDAP_BASE_DN:-}"
LDAP_BIND_DN="${LDAP_BIND_DN:-}"
LDAP_BIND_PASSWORD="${LDAP_BIND_PASSWORD:-}"
MAIL_MYSQL_HOST="${MAIL_MYSQL_HOST:-}"
MAIL_MYSQL_PORT="${MAIL_MYSQL_PORT:-3306}"
MAIL_MYSQL_DATABASE="${MAIL_MYSQL_DATABASE:-}"
MAIL_MYSQL_USER="${MAIL_MYSQL_USER:-}"
MAIL_MYSQL_PASSWORD="${MAIL_MYSQL_PASSWORD:-}"
TEST_COMPOSE="${TEST_COMPOSE:-false}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.mail.yml}"
COMPOSE_ENV_FILE="${COMPOSE_ENV_FILE:-.env.mail.example}"
COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-infra-mail}"
TEST_IMAP_USER="${TEST_IMAP_USER:-}"
TEST_IMAP_PASSWORD="${TEST_IMAP_PASSWORD:-}"
TEST_POP3_USER="${TEST_POP3_USER:-}"
TEST_POP3_PASSWORD="${TEST_POP3_PASSWORD:-}"

SMTP_PORT="${SMTP_PORT:-25}"
SMTPS_PORT="${SMTPS_PORT:-465}"
SUBMISSION_PORT="${SUBMISSION_PORT:-587}"
IMAP_PORT="${IMAP_PORT:-143}"
IMAPS_PORT="${IMAPS_PORT:-993}"
POP3_PORT="${POP3_PORT:-110}"
POP3S_PORT="${POP3S_PORT:-995}"
SIEVE_PORT="${SIEVE_PORT:-4190}"

FAILURES=0
SKIPPED=0

info() {
  echo -e "${GREEN}[INFO]${NC} $1"
}

warn() {
  echo -e "${YELLOW}[WARN]${NC} $1"
}

fail() {
  echo -e "${RED}[FAIL]${NC} $1"
  FAILURES=$((FAILURES + 1))
}

pass() {
  echo -e "${GREEN}[PASS]${NC} $1"
}

section() {
  echo -e "\n${BLUE}=== $1 ===${NC}"
}

skip() {
  warn "$1"
  SKIPPED=$((SKIPPED + 1))
}

usage() {
  cat <<'EOF'
Uso:
  ./scripts/test-mail-services.sh --host HOST [opções]

Opções:
  --host HOST                 Host base para SMTP/IMAP/POP3/Sieve
  --smtp-host HOST            Override do host SMTP
  --imap-host HOST            Override do host IMAP
  --pop3-host HOST            Override do host POP3
  --sieve-host HOST           Override do host Sieve
  --ldap-host HOST            Host LDAP para teste de porta
  --ldap-uri URI              URI LDAP para ldapsearch
  --ldap-base-dn DN           Base DN LDAP
  --ldap-bind-dn DN           Bind DN LDAP
  --ldap-bind-password PASS   Senha do bind LDAP
  --mysql-host HOST           Host MySQL do backend mail
  --mysql-port PORT           Porta MySQL
  --mysql-db DB               Database MySQL
  --mysql-user USER           Usuário MySQL
  --mysql-password PASS       Senha MySQL
  --imap-user USER            Usuário para teste autenticado IMAP/IMAPS
  --imap-password PASS        Senha para teste autenticado IMAP/IMAPS
  --pop3-user USER            Usuário para teste autenticado POP3/POP3S
  --pop3-password PASS        Senha para teste autenticado POP3/POP3S
  --test-compose              Testa também docker compose local
  --compose-file FILE         Arquivo compose da stack mail
  --compose-env-file FILE     Arquivo de ambiente usado no compose
  --compose-project-name NAME Nome do projeto compose
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --host)
      TARGET_HOST="$2"
      shift 2
      ;;
    --smtp-host)
      SMTP_HOST="$2"
      shift 2
      ;;
    --imap-host)
      IMAP_HOST="$2"
      shift 2
      ;;
    --pop3-host)
      POP3_HOST="$2"
      shift 2
      ;;
    --sieve-host)
      SIEVE_HOST="$2"
      shift 2
      ;;
    --ldap-host)
      LDAP_HOST="$2"
      shift 2
      ;;
    --ldap-uri)
      LDAP_URI="$2"
      shift 2
      ;;
    --ldap-base-dn)
      LDAP_BASE_DN="$2"
      shift 2
      ;;
    --ldap-bind-dn)
      LDAP_BIND_DN="$2"
      shift 2
      ;;
    --ldap-bind-password)
      LDAP_BIND_PASSWORD="$2"
      shift 2
      ;;
    --mysql-host)
      MAIL_MYSQL_HOST="$2"
      shift 2
      ;;
    --mysql-port)
      MAIL_MYSQL_PORT="$2"
      shift 2
      ;;
    --mysql-db)
      MAIL_MYSQL_DATABASE="$2"
      shift 2
      ;;
    --mysql-user)
      MAIL_MYSQL_USER="$2"
      shift 2
      ;;
    --mysql-password)
      MAIL_MYSQL_PASSWORD="$2"
      shift 2
      ;;
    --imap-user)
      TEST_IMAP_USER="$2"
      shift 2
      ;;
    --imap-password)
      TEST_IMAP_PASSWORD="$2"
      shift 2
      ;;
    --pop3-user)
      TEST_POP3_USER="$2"
      shift 2
      ;;
    --pop3-password)
      TEST_POP3_PASSWORD="$2"
      shift 2
      ;;
    --test-compose)
      TEST_COMPOSE=true
      shift
      ;;
    --compose-file)
      COMPOSE_FILE="$2"
      shift 2
      ;;
    --compose-env-file)
      COMPOSE_ENV_FILE="$2"
      shift 2
      ;;
    --compose-project-name)
      COMPOSE_PROJECT_NAME="$2"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Argumento desconhecido: $1"
      usage
      exit 1
      ;;
  esac
done

if [[ -z "$TARGET_HOST" && -z "$SMTP_HOST" && -z "$IMAP_HOST" && -z "$POP3_HOST" ]]; then
  usage
  exit 1
fi

SMTP_HOST="${SMTP_HOST:-$TARGET_HOST}"
IMAP_HOST="${IMAP_HOST:-$TARGET_HOST}"
POP3_HOST="${POP3_HOST:-$TARGET_HOST}"
SIEVE_HOST="${SIEVE_HOST:-$IMAP_HOST}"

check_tcp() {
  local host="$1"
  local port="$2"
  local name="$3"

  if timeout 5 bash -lc "</dev/tcp/${host}/${port}" >/dev/null 2>&1; then
    pass "$name: ${host}:${port} acessível"
  else
    fail "$name: ${host}:${port} inacessível"
  fi
}

check_plain_dialog() {
  local host="$1"
  local port="$2"
  local name="$3"
  local payload="$4"
  local pattern="$5"
  local output

  output=$(timeout 8 bash -lc "exec 3<>/dev/tcp/${host}/${port}; printf '%b' \"${payload}\" >&3; timeout 3 cat <&3" 2>/dev/null | tr -d '\r' | head -n 30 || true)

  if [[ -z "$output" ]]; then
    fail "$name: falha ao dialogar com ${host}:${port}"
    return
  fi

  if echo "$output" | grep -Eq "$pattern"; then
    pass "$name: resposta compatível"
  else
    fail "$name: resposta inesperada"
    echo "$output"
  fi
}

check_tls_dialog() {
  local host="$1"
  local port="$2"
  local name="$3"
  local payload="$4"
  local pattern="$5"
  local starttls_mode="${6:-}"
  local cmd
  local output

  cmd="openssl s_client -connect ${host}:${port} -quiet"
  if [[ -n "$starttls_mode" ]]; then
    cmd="openssl s_client -starttls ${starttls_mode} -connect ${host}:${port} -quiet"
  fi

  output=$(printf '%b' "$payload" | timeout 12 bash -lc "$cmd -ign_eof" 2>/dev/null | tr -d '\r' | head -n 40 || true)

  if [[ -z "$output" ]]; then
    fail "$name: falha no handshake TLS com ${host}:${port}"
    return
  fi

  if echo "$output" | grep -Eq "$pattern"; then
    pass "$name: resposta TLS compatível"
  else
    fail "$name: resposta TLS inesperada"
    echo "$output"
  fi
}

check_ldap() {
  if [[ -n "$LDAP_URI" && -n "$LDAP_BASE_DN" && -n "$LDAP_BIND_DN" && -n "$LDAP_BIND_PASSWORD" && $(command -v ldapsearch) ]]; then
    if timeout 10 ldapsearch -x -H "$LDAP_URI" -D "$LDAP_BIND_DN" -w "$LDAP_BIND_PASSWORD" -b "$LDAP_BASE_DN" -s base dn >/dev/null 2>&1; then
      pass "LDAP bind e search base"
    else
      fail "LDAP bind/search falhou em $LDAP_URI"
    fi
  elif [[ -n "$LDAP_HOST" ]]; then
    check_tcp "$LDAP_HOST" 389 "LDAP TCP"
  else
    skip "LDAP não configurado para teste"
  fi
}

check_mysql() {
  local mysql_cmd=""
  if command -v mariadb >/dev/null 2>&1; then
    mysql_cmd="mariadb"
  elif command -v mysql >/dev/null 2>&1; then
    mysql_cmd="mysql"
  fi

  if [[ -z "$MAIL_MYSQL_HOST" || -z "$MAIL_MYSQL_DATABASE" || -z "$MAIL_MYSQL_USER" || -z "$MAIL_MYSQL_PASSWORD" ]]; then
    skip "MySQL não configurado para teste"
    return
  fi

  if [[ -z "$mysql_cmd" ]]; then
    skip "Cliente MySQL não disponível localmente"
    return
  fi

  if timeout 10 "$mysql_cmd" --connect-timeout=5 -h "$MAIL_MYSQL_HOST" -P "$MAIL_MYSQL_PORT" -u "$MAIL_MYSQL_USER" -p"$MAIL_MYSQL_PASSWORD" -D "$MAIL_MYSQL_DATABASE" -NBe "select 1; select count(*) from mailbox; select count(*) from alias; select count(*) from domain;" >/dev/null 2>&1; then
    pass "MySQL backend mail acessível"
  else
    fail "MySQL backend mail inacessível"
  fi
}

check_compose() {
  if [[ "$TEST_COMPOSE" != "true" ]]; then
    skip "Teste de docker compose não solicitado"
    return
  fi

  if ! command -v docker >/dev/null 2>&1; then
    skip "Docker não disponível para teste de compose"
    return
  fi

  if docker compose --env-file "$COMPOSE_ENV_FILE" -f "$COMPOSE_FILE" --project-name "$COMPOSE_PROJECT_NAME" ps >/tmp/mail-compose-ps.out 2>/dev/null; then
    pass "docker compose ps executou"
  else
    fail "docker compose ps falhou"
  fi
}

section "SMTP"
check_tcp "$SMTP_HOST" "$SMTP_PORT" "SMTP"
check_plain_dialog "$SMTP_HOST" "$SMTP_PORT" "SMTP banner" "EHLO test.results.local\r\nQUIT\r\n" "^220|^250"
check_tcp "$SMTP_HOST" "$SUBMISSION_PORT" "Submission"
check_tls_dialog "$SMTP_HOST" "$SUBMISSION_PORT" "Submission STARTTLS" "EHLO test.results.local\r\nQUIT\r\n" "^250|^220" "smtp"
check_tcp "$SMTP_HOST" "$SMTPS_PORT" "SMTPS"
check_tls_dialog "$SMTP_HOST" "$SMTPS_PORT" "SMTPS TLS" "EHLO test.results.local\r\nQUIT\r\n" "^250|^220"

section "IMAP"
check_tcp "$IMAP_HOST" "$IMAP_PORT" "IMAP"
check_plain_dialog "$IMAP_HOST" "$IMAP_PORT" "IMAP CAPABILITY" "a CAPABILITY\r\na LOGOUT\r\n" "CAPABILITY|OK"
check_tcp "$IMAP_HOST" "$IMAPS_PORT" "IMAPS"
check_tls_dialog "$IMAP_HOST" "$IMAPS_PORT" "IMAPS TLS" "a CAPABILITY\r\na LOGOUT\r\n" "CAPABILITY|OK"

if [[ -n "$TEST_IMAP_USER" && -n "$TEST_IMAP_PASSWORD" ]]; then
  check_tls_dialog "$IMAP_HOST" "$IMAPS_PORT" "IMAPS LOGIN" "a LOGIN ${TEST_IMAP_USER} ${TEST_IMAP_PASSWORD}\r\na LOGOUT\r\n" "a OK|Authenticated|CAPABILITY"
else
  skip "IMAPS login não configurado"
fi

section "POP3"
check_tcp "$POP3_HOST" "$POP3_PORT" "POP3"
check_plain_dialog "$POP3_HOST" "$POP3_PORT" "POP3 CAPA" "CAPA\r\nQUIT\r\n" "\+OK|CAPA"
check_tcp "$POP3_HOST" "$POP3S_PORT" "POP3S"
check_tls_dialog "$POP3_HOST" "$POP3S_PORT" "POP3S TLS" "CAPA\r\nQUIT\r\n" "\+OK|CAPA"

if [[ -n "$TEST_POP3_USER" && -n "$TEST_POP3_PASSWORD" ]]; then
  check_tls_dialog "$POP3_HOST" "$POP3S_PORT" "POP3S LOGIN" "USER ${TEST_POP3_USER}\r\nPASS ${TEST_POP3_PASSWORD}\r\nQUIT\r\n" "\+OK|-ERR"
else
  skip "POP3S login não configurado"
fi

section "Sieve"
check_tcp "$SIEVE_HOST" "$SIEVE_PORT" "ManageSieve"
check_plain_dialog "$SIEVE_HOST" "$SIEVE_PORT" "ManageSieve banner" "LOGOUT\r\n" "IMPLEMENTATION|SIEVE|OK|BYE"

section "LDAP"
check_ldap

section "MySQL"
check_mysql

section "Compose"
check_compose

section "Resumo"
echo "Falhas: $FAILURES"
echo "Ignorados: $SKIPPED"

if [[ "$FAILURES" -gt 0 ]]; then
  exit 1
fi

exit 0