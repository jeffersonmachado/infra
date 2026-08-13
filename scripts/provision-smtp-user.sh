#!/bin/sh
set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
ROOT_DIR=$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)

SMTP_USER_ACTION="${SMTP_USER_ACTION:-create}"
SMTP_USER_ENV_FILE="${SMTP_USER_ENV_FILE:-${WEBMAIL_MAIL_ENV_FILE:-}}"
SMTP_APP_USERNAME="${SMTP_APP_USERNAME:-}"
SMTP_APP_PASSWORD="${SMTP_APP_PASSWORD:-}"
SMTP_APP_PASSWORD_FILE="${SMTP_APP_PASSWORD_FILE:-}"
SMTP_APP_NAME="${SMTP_APP_NAME:-}"
SMTP_APP_QUOTA="${SMTP_APP_QUOTA:-100000000S}"
SMTP_APP_COD_CLIENTE="${SMTP_APP_COD_CLIENTE:-999999}"
SMTP_APP_HOME="${SMTP_APP_HOME:-/home/postfix/}"
SMTP_APP_VALIDATE_AUTH="${SMTP_APP_VALIDATE_AUTH:-true}"
SMTP_APP_PURGE_MAILDIR="${SMTP_APP_PURGE_MAILDIR:-false}"
JOOMLA_CONTAINER="${JOOMLA_CONTAINER:-results-joomla}"
DOVECOT_CONTAINER="${DOVECOT_CONTAINER:-results-mail-dovecot}"
MAIL_STORAGE_HOST_ROOT="${MAIL_STORAGE_HOST_ROOT:-/var/lib/docker/volumes/infra_maildata/_data}"

info() {
  printf '[INFO] %s\n' "$1"
}

warn() {
  printf '[WARN] %s\n' "$1"
}

error() {
  printf '[ERROR] %s\n' "$1" >&2
}

usage() {
  cat <<'EOF'
Uso:
  sh ./scripts/provision-smtp-user.sh [opcoes]

Acoes:
  --create                 Cria um usuario SMTP/IMAP SQL (padrao)
  --delete                 Remove o usuario SQL

Opcoes:
  --env-file ARQUIVO       Arquivo .env da stack de mail
  --username USUARIO       Email completo ou localpart do usuario
  --password SENHA         Senha do usuario
  --password-file ARQUIVO  Le a senha de um arquivo
  --name NOME              Nome descritivo no cadastro mailbox
  --quota QUOTA            Quota do mailbox (padrao: 100000000S)
  --cod-cliente COD        Cod_cliente do mailbox (padrao: 999999)
  --skip-auth-test         Nao roda doveadm auth test apos criar
  --purge-maildir          Ao deletar, remove tambem o Maildir do host
  --help                   Exibe esta ajuda

Variaveis equivalentes:
  SMTP_USER_ENV_FILE, SMTP_APP_USERNAME, SMTP_APP_PASSWORD,
  SMTP_APP_PASSWORD_FILE, SMTP_APP_NAME, SMTP_APP_QUOTA,
  SMTP_APP_COD_CLIENTE, SMTP_APP_VALIDATE_AUTH, SMTP_APP_PURGE_MAILDIR
EOF
}

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || {
    error "comando obrigatorio ausente: $1"
    exit 1
  }
}

autodetect_mail_env_file() {
  if [ -n "$SMTP_USER_ENV_FILE" ] && [ -r "$SMTP_USER_ENV_FILE" ]; then
    return 0
  fi

  if [ -z "$SMTP_USER_ENV_FILE" ]; then
    for candidate in "$ROOT_DIR"/.env.remote-*-mail "$ROOT_DIR/.env.mail.example"; do
      if [ -r "$candidate" ]; then
        SMTP_USER_ENV_FILE="$candidate"
        return 0
      fi
    done
  fi

  error 'arquivo de ambiente da stack de mail nao encontrado; defina SMTP_USER_ENV_FILE'
  exit 1
}

load_mail_env() {
  autodetect_mail_env_file
  info "Carregando configuracao de $SMTP_USER_ENV_FILE"

  while IFS= read -r line || [ -n "$line" ]; do
    case "$line" in
      ''|'#'*)
        continue
        ;;
    esac

    key=${line%%=*}
    value=${line#*=}
    export "$key=$value"
  done < "$SMTP_USER_ENV_FILE"
}

escape_mysql_string() {
  printf '%s' "$1" | sed "s/'/''/g"
}

run_mail_mysql_sql() {
  sql="$1"

  if command -v mysql >/dev/null 2>&1; then
    MYSQL_PWD="$MAIL_MYSQL_PASSWORD" mysql --ssl=0 -N -B -h "$MAIL_MYSQL_HOST" -P "$MAIL_MYSQL_PORT" -u "$MAIL_MYSQL_USER" -D "$MAIL_MYSQL_DATABASE" <<SQL
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
      $statement = stream_get_contents(STDIN);
      if (preg_match('/^\s*select\b/i', $statement)) {
          $result = $pdo->query($statement);
          foreach ($result as $row) {
              echo implode("\t", $row), PHP_EOL;
          }
      } else {
          $pdo->exec($statement);
      }
    '
}

normalize_username() {
  username="$1"

  case "$username" in
    *@*)
      printf '%s\n' "$username"
      ;;
    *)
      printf '%s@%s\n' "$username" "$MAIL_DOMAIN"
      ;;
  esac
}

localpart_from_username() {
  printf '%s\n' "${1%@*}"
}

load_password_if_needed() {
  if [ -n "$SMTP_APP_PASSWORD" ]; then
    return 0
  fi

  if [ -n "$SMTP_APP_PASSWORD_FILE" ]; then
    if [ ! -r "$SMTP_APP_PASSWORD_FILE" ]; then
      error "arquivo de senha nao pode ser lido: $SMTP_APP_PASSWORD_FILE"
      exit 1
    fi
    SMTP_APP_PASSWORD=$(tr -d '\r' < "$SMTP_APP_PASSWORD_FILE")
    export SMTP_APP_PASSWORD
  fi
}

ensure_maildir_tree() {
  localpart="$1"
  mailbox_root="$MAIL_STORAGE_HOST_ROOT/$MAIL_DOMAIN/$localpart/Maildir"

  mkdir -p "$mailbox_root/cur" "$mailbox_root/new" "$mailbox_root/tmp"
  chown -R "$MAIL_UID:$MAIL_GID" "$MAIL_STORAGE_HOST_ROOT/$MAIL_DOMAIN/$localpart"
  chmod 0770 "$MAIL_STORAGE_HOST_ROOT/$MAIL_DOMAIN/$localpart" "$mailbox_root"
}

create_standard_mailboxes() {
  login_name="$1"

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
  login_name="$1"
  password="$2"

  if [ "$SMTP_APP_VALIDATE_AUTH" != "true" ]; then
    return 0
  fi

  if ! docker ps --format '{{.Names}}' | grep -Fx "$DOVECOT_CONTAINER" >/dev/null 2>&1; then
    warn "container $DOVECOT_CONTAINER ausente; auth test do Dovecot ignorado"
    return 0
  fi

  info "Validando autenticacao no Dovecot para $login_name"
  docker exec "$DOVECOT_CONTAINER" doveadm auth test "$login_name" "$password" >/dev/null
}

mailbox_exists() {
  username_sql=$(escape_mysql_string "$1")
  result=$(run_mail_mysql_sql "SELECT COUNT(*) FROM $MAIL_MAILBOX_TABLE WHERE username = '$username_sql';" | tr -d '[:space:]')
  [ "${result:-0}" != "0" ]
}

create_user() {
  if [ -z "$SMTP_APP_USERNAME" ]; then
    error 'defina --username ou SMTP_APP_USERNAME'
    exit 1
  fi

  load_password_if_needed
  if [ -z "$SMTP_APP_PASSWORD" ]; then
    error 'defina --password, --password-file ou SMTP_APP_PASSWORD'
    exit 1
  fi

  SMTP_APP_USERNAME=$(normalize_username "$SMTP_APP_USERNAME")
  SMTP_APP_LOCALPART=$(localpart_from_username "$SMTP_APP_USERNAME")
  SMTP_APP_NAME=${SMTP_APP_NAME:-SMTP App $SMTP_APP_LOCALPART}

  if mailbox_exists "$SMTP_APP_USERNAME"; then
    error "usuario ja existe em $MAIL_MAILBOX_TABLE: $SMTP_APP_USERNAME"
    exit 1
  fi

  SMTP_APP_HASH=$(openssl passwd -6 "$SMTP_APP_PASSWORD")

  info "Criando usuario SQL $SMTP_APP_USERNAME"
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
  '$(escape_mysql_string "$SMTP_APP_USERNAME")',
  '$(escape_mysql_string "$SMTP_APP_HASH")',
  '$(escape_mysql_string "$SMTP_APP_NAME")',
  '$(escape_mysql_string "$SMTP_APP_HOME")',
  '$(escape_mysql_string "$MAIL_DOMAIN/$SMTP_APP_LOCALPART/Maildir/")',
  '$(escape_mysql_string "$SMTP_APP_QUOTA")',
  '$(escape_mysql_string "$MAIL_DOMAIN")',
  NOW(),
  NOW(),
  1,
  'N',
  ${MAIL_UID},
  ${MAIL_GID},
  '$(escape_mysql_string "$SMTP_APP_COD_CLIENTE")'
);
"

  ensure_maildir_tree "$SMTP_APP_LOCALPART"
  create_standard_mailboxes "$SMTP_APP_USERNAME"
  run_dovecot_auth_test "$SMTP_APP_LOCALPART" "$SMTP_APP_PASSWORD"
  run_dovecot_auth_test "$SMTP_APP_USERNAME" "$SMTP_APP_PASSWORD"

  info "Usuario criado com sucesso: $SMTP_APP_USERNAME"
  printf 'SMTP_HOST=%s\n' "${MAIL_HOSTNAME:-mx1.results.com.br}"
  printf 'SMTP_PORT=587\n'
  printf 'SMTP_SECURITY=STARTTLS\n'
  printf 'SMTP_USERNAME=%s\n' "$SMTP_APP_USERNAME"
}

delete_user() {
  if [ -z "$SMTP_APP_USERNAME" ]; then
    error 'defina --username ou SMTP_APP_USERNAME para remover'
    exit 1
  fi

  SMTP_APP_USERNAME=$(normalize_username "$SMTP_APP_USERNAME")
  SMTP_APP_LOCALPART=$(localpart_from_username "$SMTP_APP_USERNAME")

  if ! mailbox_exists "$SMTP_APP_USERNAME"; then
    warn "usuario nao encontrado em $MAIL_MAILBOX_TABLE: $SMTP_APP_USERNAME"
    return 0
  fi

  info "Removendo usuario SQL $SMTP_APP_USERNAME"
  run_mail_mysql_sql "DELETE FROM $MAIL_MAILBOX_TABLE WHERE username = '$(escape_mysql_string "$SMTP_APP_USERNAME")';"

  if [ "$SMTP_APP_PURGE_MAILDIR" = "true" ] && [ -d "$MAIL_STORAGE_HOST_ROOT/$MAIL_DOMAIN/$SMTP_APP_LOCALPART" ]; then
    info "Removendo Maildir de $SMTP_APP_USERNAME"
    rm -rf "$MAIL_STORAGE_HOST_ROOT/$MAIL_DOMAIN/$SMTP_APP_LOCALPART"
  fi
}

while [ $# -gt 0 ]; do
  case "$1" in
    --create)
      SMTP_USER_ACTION=create
      ;;
    --delete)
      SMTP_USER_ACTION=delete
      ;;
    --env-file)
      SMTP_USER_ENV_FILE="$2"
      shift
      ;;
    --username)
      SMTP_APP_USERNAME="$2"
      shift
      ;;
    --password)
      SMTP_APP_PASSWORD="$2"
      shift
      ;;
    --password-file)
      SMTP_APP_PASSWORD_FILE="$2"
      shift
      ;;
    --name)
      SMTP_APP_NAME="$2"
      shift
      ;;
    --quota)
      SMTP_APP_QUOTA="$2"
      shift
      ;;
    --cod-cliente)
      SMTP_APP_COD_CLIENTE="$2"
      shift
      ;;
    --skip-auth-test)
      SMTP_APP_VALIDATE_AUTH=false
      ;;
    --purge-maildir)
      SMTP_APP_PURGE_MAILDIR=true
      ;;
    --help|-h)
      usage
      exit 0
      ;;
    *)
      error "opcao invalida: $1"
      usage >&2
      exit 1
      ;;
  esac
  shift
done

require_cmd docker
require_cmd openssl
load_mail_env

case "$SMTP_USER_ACTION" in
  create)
    create_user
    ;;
  delete)
    delete_user
    ;;
  *)
    error "acao invalida: $SMTP_USER_ACTION"
    exit 1
    ;;
esac