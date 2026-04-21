#!/bin/sh
set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
ROOT_DIR=$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)

WEBMAIL_MAIL_ENV_FILE="${WEBMAIL_MAIL_ENV_FILE:-}"
WEBMAIL_LOGIN_SCRIPT="${WEBMAIL_LOGIN_SCRIPT:-$ROOT_DIR/scripts/test-webmail-login.sh}"
WEBMAIL_URL="${WEBMAIL_URL:-https://www.results.com.br/webmail/}"
WEBMAIL_RESOLVE_HOST="${WEBMAIL_RESOLVE_HOST:-www.results.com.br}"
WEBMAIL_RESOLVE_IP="${WEBMAIL_RESOLVE_IP:-127.0.0.1}"
JOOMLA_CONTAINER="${JOOMLA_CONTAINER:-results-joomla}"
DOVECOT_CONTAINER="${DOVECOT_CONTAINER:-results-mail-dovecot}"
LDAP_CONTAINER="${LDAP_CONTAINER:-results-mail-ldap}"
MAIL_STORAGE_HOST_ROOT="${MAIL_STORAGE_HOST_ROOT:-/var/lib/docker/volumes/infra-mail_maildata/_data}"
TEMP_PREFIX="${WEBMAIL_TEMP_PREFIX:-copilot-webmail-smoke}"

info() {
  printf '[INFO] %s\n' "$1"
}

warn() {
  printf '[WARN] %s\n' "$1"
}

error() {
  printf '[ERROR] %s\n' "$1" >&2
}

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || {
    error "comando obrigatorio ausente: $1"
    exit 1
  }
}

run_ldap_container_cmd() {
  if ! docker ps --format '{{.Names}}' | grep -Fx "$LDAP_CONTAINER" >/dev/null 2>&1; then
    error "container $LDAP_CONTAINER indisponivel para operacoes LDAP"
    exit 1
  fi

  docker exec \
    -e LDAP_TEST_URI="$1" \
    -e LDAP_BIND_DN="$LDAP_BIND_DN" \
    -e LDAP_BIND_PASSWORD="$LDAP_BIND_PASSWORD" \
    -i "$LDAP_CONTAINER" sh -lc "$2"
}

autodetect_mail_env_file() {
  if [ -n "$WEBMAIL_MAIL_ENV_FILE" ] && [ -r "$WEBMAIL_MAIL_ENV_FILE" ]; then
    return 0
  fi

  if [ -z "$WEBMAIL_MAIL_ENV_FILE" ]; then
    for candidate in "$ROOT_DIR"/.env.remote-*-mail "$ROOT_DIR/.env.mail.example"; do
      if [ -r "$candidate" ]; then
        WEBMAIL_MAIL_ENV_FILE="$candidate"
        return 0
      fi
    done
  fi

  error 'arquivo de ambiente da stack de mail nao encontrado; defina WEBMAIL_MAIL_ENV_FILE'
  exit 1
}

load_mail_env() {
  autodetect_mail_env_file
  info "Carregando credenciais operacionais de $WEBMAIL_MAIL_ENV_FILE"

  while IFS= read -r line || [ -n "$line" ]; do
    case "$line" in
      ''|'#'*)
        continue
        ;;
    esac

    key=${line%%=*}
    value=${line#*=}
    export "$key=$value"
  done < "$WEBMAIL_MAIL_ENV_FILE"
}

ensure_maildir_tree() {
  local localpart="$1"
  local mailbox_root="$MAIL_STORAGE_HOST_ROOT/$MAIL_DOMAIN/$localpart/Maildir"

  mkdir -p "$mailbox_root/cur" "$mailbox_root/new" "$mailbox_root/tmp"
  chown -R "$MAIL_UID:$MAIL_GID" "$MAIL_STORAGE_HOST_ROOT/$MAIL_DOMAIN/$localpart"
  chmod 0770 "$MAIL_STORAGE_HOST_ROOT/$MAIL_DOMAIN/$localpart" "$mailbox_root"
}

create_standard_mailboxes() {
  local login_name="$1"

  if ! docker ps --format '{{.Names}}' | grep -Fx "$DOVECOT_CONTAINER" >/dev/null 2>&1; then
    warn "container $DOVECOT_CONTAINER ausente; criacao de mailboxes padrao ignorada"
    return 0
  fi

  docker exec "$DOVECOT_CONTAINER" doveadm mailbox create -u "$login_name" INBOX >/dev/null 2>&1 || true
  docker exec "$DOVECOT_CONTAINER" doveadm mailbox create -u "$login_name" Drafts >/dev/null 2>&1 || true
  docker exec "$DOVECOT_CONTAINER" doveadm mailbox create -u "$login_name" Sent >/dev/null 2>&1 || true
  docker exec "$DOVECOT_CONTAINER" doveadm mailbox create -u "$login_name" Spam >/dev/null 2>&1 || true
  docker exec "$DOVECOT_CONTAINER" doveadm mailbox create -u "$login_name" Trash >/dev/null 2>&1 || true
}

run_dovecot_auth_test() {
  local login_name="$1"
  local password="$2"

  if ! docker ps --format '{{.Names}}' | grep -Fx "$DOVECOT_CONTAINER" >/dev/null 2>&1; then
    warn "container $DOVECOT_CONTAINER ausente; auth test do Dovecot ignorado"
    return 0
  fi

  info "Validando autenticacao IMAP de $login_name no Dovecot"
  docker exec "$DOVECOT_CONTAINER" doveadm auth test "$login_name" "$password" >/dev/null
}

run_roundcube_login_test() {
  local login_name="$1"
  local password="$2"

  info "Validando login do Roundcube com $login_name"
  WEBMAIL_URL="$WEBMAIL_URL" \
  WEBMAIL_RESOLVE_HOST="$WEBMAIL_RESOLVE_HOST" \
  WEBMAIL_RESOLVE_IP="$WEBMAIL_RESOLVE_IP" \
  WEBMAIL_USER="$login_name" \
  WEBMAIL_PASSWORD="$password" \
  sh "$WEBMAIL_LOGIN_SCRIPT"
}

run_mail_mysql_sql() {
  local sql="$1"

  if command -v mysql >/dev/null 2>&1; then
    MYSQL_PWD="$MAIL_MYSQL_PASSWORD" mysql -h "$MAIL_MYSQL_HOST" -P "$MAIL_MYSQL_PORT" -u "$MAIL_MYSQL_USER" -D "$MAIL_MYSQL_DATABASE" <<SQL
$sql
SQL
    return 0
  fi

  if ! docker ps --format '{{.Names}}' | grep -Fx "$JOOMLA_CONTAINER" >/dev/null 2>&1; then
    error "cliente mysql ausente no host e container $JOOMLA_CONTAINER indisponivel para fallback PDO"
    exit 1
  fi

  printf '%s\n' "$sql" | docker exec \
    -e MAIL_MYSQL_HOST="$MAIL_MYSQL_HOST" \
    -e MAIL_MYSQL_PORT="$MAIL_MYSQL_PORT" \
    -e MAIL_MYSQL_DATABASE="$MAIL_MYSQL_DATABASE" \
    -e MAIL_MYSQL_USER="$MAIL_MYSQL_USER" \
    -e MAIL_MYSQL_PASSWORD="$MAIL_MYSQL_PASSWORD" \
    -i "$JOOMLA_CONTAINER" php -r '
      $dsn = sprintf(
          "mysql:host=%s;port=%s;dbname=%s",
          getenv("MAIL_MYSQL_HOST"),
          getenv("MAIL_MYSQL_PORT"),
          getenv("MAIL_MYSQL_DATABASE")
      );
      $pdo = new PDO($dsn, getenv("MAIL_MYSQL_USER"), getenv("MAIL_MYSQL_PASSWORD"), [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      ]);
      $pdo->exec(stream_get_contents(STDIN));
    '
}

generate_ldap_password_hash() {
  if command -v slappasswd >/dev/null 2>&1; then
    slappasswd -s "$1"
    return 0
  fi

  if ! docker ps --format '{{.Names}}' | grep -Fx "$LDAP_CONTAINER" >/dev/null 2>&1; then
    error "slappasswd ausente no host e container $LDAP_CONTAINER indisponivel para gerar hash LDAP"
    exit 1
  fi

  docker exec "$LDAP_CONTAINER" slappasswd -s "$1"
}

resolve_ldap_uri() {
  local uri="$1"
  local scheme hostport host port ip

  scheme=${uri%%://*}
  hostport=${uri#*://}
  hostport=${hostport%%/*}
  host=${hostport%%:*}
  port=''
  if [ "$host" != "$hostport" ]; then
    port=:${hostport#*:}
  fi

  ip=$(getent hosts "$host" 2>/dev/null | awk 'NR==1 {print $1}')
  if [ -z "$ip" ] && docker ps --format '{{.Names}}' | grep -Fx "$DOVECOT_CONTAINER" >/dev/null 2>&1; then
    ip=$(docker exec "$DOVECOT_CONTAINER" sh -lc "getent hosts '$host' | awk 'NR==1 {print \$1}'" 2>/dev/null | tr -d '\r')
  fi

  if [ -z "$ip" ]; then
    warn "Nao foi possivel resolver $host; usando URI original $uri"
    printf '%s\n' "$uri"
    return 0
  fi

  printf '%s://%s%s\n' "$scheme" "$ip" "$port"
}

ldap_add_entry_primary() {
  local ldif_file="$1"

  info "Adicionando usuario temporario no LDAP $LDAP_PRIMARY_URI_RESOLVED"
  run_ldap_container_cmd "$LDAP_PRIMARY_URI_RESOLVED" 'ldapadd -x -H "$LDAP_TEST_URI" -D "$LDAP_BIND_DN" -w "$LDAP_BIND_PASSWORD"' < "$ldif_file"
}

ldap_delete_entry_primary() {
  local dn="$1"

  run_ldap_container_cmd "$LDAP_PRIMARY_URI_RESOLVED" "ldapdelete -x -H \"\$LDAP_TEST_URI\" -D \"\$LDAP_BIND_DN\" -w \"\$LDAP_BIND_PASSWORD\" '$dn'" >/dev/null 2>&1 || true
}

escape_mysql_string() {
  printf '%s' "$1" | sed "s/'/''/g"
}

cleanup() {
  set +e

  if [ "${TEMP_SQL_CREATED:-0}" = "1" ]; then
    info "Removendo login temporario SQL $TEMP_SQL_USERNAME"
    run_mail_mysql_sql "
DELETE FROM \
  $MAIL_MAILBOX_TABLE \
WHERE username = '$(escape_mysql_string "$TEMP_SQL_USERNAME")';
" >/dev/null 2>&1
  fi

  if [ "${TEMP_LDAP_CREATED:-0}" = "1" ]; then
    info "Removendo login temporario LDAP $TEMP_LDAP_DN"
    ldap_delete_entry_primary "$TEMP_LDAP_DN"
  fi

  if [ -n "${TEMP_SQL_LOCALPART:-}" ] && [ -d "$MAIL_STORAGE_HOST_ROOT/$MAIL_DOMAIN/$TEMP_SQL_LOCALPART" ]; then
    rm -rf "$MAIL_STORAGE_HOST_ROOT/$MAIL_DOMAIN/$TEMP_SQL_LOCALPART"
  fi

  if [ -n "${TEMP_LDAP_LOCALPART:-}" ] && [ -d "$MAIL_STORAGE_HOST_ROOT/$MAIL_DOMAIN/$TEMP_LDAP_LOCALPART" ]; then
    rm -rf "$MAIL_STORAGE_HOST_ROOT/$MAIL_DOMAIN/$TEMP_LDAP_LOCALPART"
  fi
}

trap cleanup EXIT INT TERM

require_cmd docker
require_cmd openssl

load_mail_env

LDAP_PRIMARY_URI=$(printf '%s' "$LDAP_URI" | cut -d',' -f1)
LDAP_URI_LIST=$(printf '%s' "$LDAP_URI" | tr ',' ' ')
LDAP_PRIMARY_URI_RESOLVED=$(resolve_ldap_uri "$LDAP_PRIMARY_URI")
LDAP_PEOPLE_BASE_DN="${LDAP_USERS_BASE_DN:-ou=people,dc=results,dc=com,dc=br}"
TEMP_TOKEN=$(date +%s)

TEMP_SQL_LOCALPART="${TEMP_PREFIX}-sql-${TEMP_TOKEN}"
TEMP_SQL_USERNAME="$TEMP_SQL_LOCALPART@$MAIL_DOMAIN"
TEMP_SQL_PASSWORD="TmpSql!${TEMP_TOKEN}"
TEMP_SQL_HASH=$(openssl passwd -6 "$TEMP_SQL_PASSWORD")

TEMP_LDAP_LOCALPART="${TEMP_PREFIX}-ldap-${TEMP_TOKEN}"
TEMP_LDAP_USERNAME="$TEMP_LDAP_LOCALPART@$MAIL_DOMAIN"
TEMP_LDAP_PASSWORD="TmpLdap!${TEMP_TOKEN}"
TEMP_LDAP_PASSWORD_HASH=$(generate_ldap_password_hash "$TEMP_LDAP_PASSWORD")
TEMP_LDAP_DN="uid=$TEMP_LDAP_LOCALPART,$LDAP_PEOPLE_BASE_DN"

info 'Validando bind administrativo no LDAP real'
run_ldap_container_cmd "$LDAP_PRIMARY_URI" 'ldapwhoami -x -H "$LDAP_TEST_URI" -D "$LDAP_BIND_DN" -w "$LDAP_BIND_PASSWORD"' >/dev/null

info "Criando login temporario SQL $TEMP_SQL_USERNAME"
run_mail_mysql_sql "
INSERT INTO $MAIL_MAILBOX_TABLE (
  username,
  password,
  name,
  home,
  maildir,
  quota,
  domain,
  create_date,
  change_date,
  active,
  passwd_expire,
  uid,
  gid,
  cod_cliente
) VALUES (
  '$(escape_mysql_string "$TEMP_SQL_USERNAME")',
  '$(escape_mysql_string "$TEMP_SQL_HASH")',
  'Copilot Webmail Smoke SQL',
  '/home/postfix/',
  '$(escape_mysql_string "$MAIL_DOMAIN/$TEMP_SQL_LOCALPART/Maildir/")',
  '100000000S',
  '$(escape_mysql_string "$MAIL_DOMAIN")',
  NOW(),
  NOW(),
  1,
  'N',
  ${MAIL_UID},
  ${MAIL_GID},
  '999999'
);
"
TEMP_SQL_CREATED=1
ensure_maildir_tree "$TEMP_SQL_LOCALPART"
create_standard_mailboxes "$TEMP_SQL_USERNAME"
run_dovecot_auth_test "$TEMP_SQL_LOCALPART" "$TEMP_SQL_PASSWORD"
run_roundcube_login_test "$TEMP_SQL_LOCALPART" "$TEMP_SQL_PASSWORD"

info "Criando login temporario LDAP $TEMP_LDAP_DN"
TEMP_LDAP_LDIF=$(mktemp)
cat <<LDIF > "$TEMP_LDAP_LDIF"
dn: $TEMP_LDAP_DN
objectClass: inetOrgPerson
objectClass: organizationalPerson
objectClass: person
objectClass: top
cn: Copilot Webmail Smoke LDAP
sn: LDAP
uid: $TEMP_LDAP_LOCALPART
mail: $TEMP_LDAP_USERNAME
userPassword: $TEMP_LDAP_PASSWORD_HASH
LDIF
TEMP_LDAP_CREATED=1
ldap_add_entry_primary "$TEMP_LDAP_LDIF"
rm -f "$TEMP_LDAP_LDIF"
ensure_maildir_tree "$TEMP_LDAP_LOCALPART"
create_standard_mailboxes "$TEMP_LDAP_USERNAME"
run_dovecot_auth_test "$TEMP_LDAP_LOCALPART" "$TEMP_LDAP_PASSWORD"
run_dovecot_auth_test "$TEMP_LDAP_USERNAME" "$TEMP_LDAP_PASSWORD"

info 'Validacao temporaria de SQL e LDAP concluida com sucesso'