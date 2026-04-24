#!/bin/sh
set -eu

WEBMAIL_URL="${WEBMAIL_URL:-https://www.results.com.br/webmail/}"
WEBMAIL_RESOLVE_HOST="${WEBMAIL_RESOLVE_HOST:-www.results.com.br}"
WEBMAIL_RESOLVE_IP="${WEBMAIL_RESOLVE_IP:-127.0.0.1}"
WEBMAIL_USER="${WEBMAIL_USER:-}"
WEBMAIL_PASSWORD="${WEBMAIL_PASSWORD:-}"
JOOMLA_CONTAINER="${JOOMLA_CONTAINER:-results-joomla}"
ROUNDCUBE_CONFIG_PATH="${ROUNDCUBE_CONFIG_PATH:-/var/www/html/results/webmail/config/config.inc.php}"
ROUNDMAIL_ERROR_LOG="${ROUNDMAIL_ERROR_LOG:-/var/www/html/results/webmail/logs/errors.log}"

cookie_jar=$(mktemp)
login_page=$(mktemp)
login_headers=$(mktemp)
login_response=$(mktemp)
trap 'rm -f "$cookie_jar" "$login_page" "$login_headers" "$login_response"' EXIT

info() {
  printf '[INFO] %s\n' "$1"
}

warn() {
  printf '[WARN] %s\n' "$1"
}

error() {
  printf '[ERROR] %s\n' "$1" >&2
}

show_recent_roundcube_errors() {
  if ! command -v docker >/dev/null 2>&1; then
    return 0
  fi

  if ! docker ps --format '{{.Names}}' | grep -Fx "$JOOMLA_CONTAINER" >/dev/null 2>&1; then
    return 0
  fi

  warn 'Ultimas linhas do log de erros do Roundcube:'
  docker exec "$JOOMLA_CONTAINER" sh -lc "tail -n 10 '$ROUNDMAIL_ERROR_LOG' 2>/dev/null || true"
}

show_failed_login_response() {
  warn 'Headers recebidos no POST de login:'
  sed -n '1,40p' "$login_headers"
  warn 'Trecho da resposta do POST de login:'
  sed -n '1,120p' "$login_response"
}

validate_roundcube_imap_runtime() {
  if ! command -v docker >/dev/null 2>&1; then
    warn 'docker nao encontrado; validacao de runtime IMAP do Roundcube ignorada'
    return 0
  fi

  if ! docker ps --format '{{.Names}}' | grep -Fx "$JOOMLA_CONTAINER" >/dev/null 2>&1; then
    warn "container $JOOMLA_CONTAINER ausente; validacao de runtime IMAP do Roundcube ignorada"
    return 0
  fi

  info 'Validando runtime IMAP do Roundcube dentro do container'
  if ! cat <<'PHP' | docker exec -i "$JOOMLA_CONTAINER" php
<?php
$configPath = getenv('ROUNDCUBE_CONFIG_PATH') ?: '/var/www/html/results/webmail/config/config.inc.php';
$stdout = fopen('php://stdout', 'w');
$stderr = fopen('php://stderr', 'w');
if (!is_file($configPath)) {
  fwrite($stderr, "[ERROR] config do Roundcube nao encontrado: {$configPath}\n");
    exit(1);
}

$config = [];
include $configPath;

$defaultHost = $config['default_host'] ?? '';
$defaultPort = (int) ($config['default_port'] ?? 0);
if ($defaultHost === '') {
  fwrite($stderr, "[ERROR] default_host ausente no config do Roundcube\n");
    exit(1);
}

$scheme = 'tcp';
$host = $defaultHost;
if (strpos($defaultHost, 'ssl://') === 0) {
    $scheme = 'ssl';
    $host = substr($defaultHost, 6);
} elseif (strpos($defaultHost, 'tls://') === 0) {
    $scheme = 'tls';
    $host = substr($defaultHost, 6);
}

if ($defaultPort <= 0) {
    $defaultPort = $scheme === 'ssl' ? 993 : 143;
}

fwrite($stdout, "[INFO] IMAP runtime: {$defaultHost}:{$defaultPort}\n");

$errno = 0;
$errstr = '';

if ($scheme === 'ssl') {
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ]);
    $socket = @stream_socket_client("ssl://{$host}:{$defaultPort}", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) {
      fwrite($stderr, "[ERROR] Falha na conexao IMAPS com {$host}:{$defaultPort}: {$errstr} ({$errno})\n");
        exit(1);
    }

    stream_set_timeout($socket, 10);
    $banner = fgets($socket);
    fclose($socket);
    if ($banner === false || stripos($banner, 'OK') === false) {
      fwrite($stderr, "[ERROR] Banner IMAPS invalido em {$host}:{$defaultPort}\n");
        exit(1);
    }

    fwrite($stdout, '[INFO] Handshake IMAPS validado com sucesso' . PHP_EOL);
    exit(0);
}

$socket = @stream_socket_client("tcp://{$host}:{$defaultPort}", $errno, $errstr, 10);
if (!$socket) {
    fwrite($stderr, "[ERROR] Falha na conexao IMAP com {$host}:{$defaultPort}: {$errstr} ({$errno})\n");
    exit(1);
}

stream_set_timeout($socket, 10);
$banner = fgets($socket);
if ($banner === false || stripos($banner, 'OK') === false) {
    fclose($socket);
    fwrite($stderr, "[ERROR] Banner IMAP invalido em {$host}:{$defaultPort}\n");
    exit(1);
}

if ($scheme === 'tls') {
    fwrite($socket, "a001 STARTTLS\r\n");
    $starttlsResponse = '';
    while (!feof($socket)) {
        $line = fgets($socket);
        if ($line === false) {
            break;
        }
        $starttlsResponse .= $line;
        if (strpos($line, 'a001 ') === 0) {
            break;
        }
    }

    if (stripos($starttlsResponse, 'a001 OK') === false) {
        fclose($socket);
    fwrite($stderr, "[ERROR] STARTTLS recusado por {$host}:{$defaultPort}\n{$starttlsResponse}");
        exit(1);
    }

    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ]);
    stream_context_set_option($socket, $context);
    $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    if ($crypto !== true) {
        fclose($socket);
      fwrite($stderr, "[ERROR] Nao foi possivel negociar TLS apos STARTTLS em {$host}:{$defaultPort}\n");
        exit(1);
    }
}

fwrite($socket, "a002 CAPABILITY\r\n");
$capabilityResponse = '';
while (!feof($socket)) {
    $line = fgets($socket);
    if ($line === false) {
        break;
    }
    $capabilityResponse .= $line;
    if (strpos($line, 'a002 ') === 0) {
        break;
    }
}
fclose($socket);

if (stripos($capabilityResponse, 'a002 OK') === false) {
  fwrite($stderr, "[ERROR] CAPABILITY falhou em {$host}:{$defaultPort}\n{$capabilityResponse}");
    exit(1);
}

fwrite($stdout, '[INFO] Conexao IMAP/STARTTLS validada com sucesso' . PHP_EOL);
PHP
  then
    show_recent_roundcube_errors
    exit 1
  fi
}

curl_args="-ksS --resolve ${WEBMAIL_RESOLVE_HOST}:443:${WEBMAIL_RESOLVE_IP}"

validate_roundcube_imap_runtime

info 'Validando pagina de login do webmail'
curl $curl_args -D "$login_headers" -c "$cookie_jar" "$WEBMAIL_URL" -o "$login_page"

if ! grep -qi '^set-cookie: roundcube_sessid=' "$login_headers"; then
  error 'cookie de sessao roundcube_sessid nao retornado na pagina de login'
  exit 1
fi

cookie_line=$(grep -i '^set-cookie: roundcube_sessid=' "$login_headers" | head -n 1 || true)
cookie_path=$(printf '%s\n' "$cookie_line" | sed -n 's/.*[Pp]ath=\([^;]*\).*/\1/p')
if [ -n "$cookie_path" ]; then
  case "$cookie_path" in
    /|/webmail|/webmail/)
      ;;
    *)
      error "path de cookie invalido para roundcube_sessid: '$cookie_path'"
      exit 1
      ;;
  esac
else
  warn 'atributo Path do roundcube_sessid ausente; verificacao de escopo de cookie nao conclusiva'
fi

if ! grep -q 'name="_token"' "$login_page"; then
  error "token de login nao encontrado em $WEBMAIL_URL"
  exit 1
fi

token=$(tr '\n' ' ' < "$login_page" | sed -n 's/.*name="_token" value="\([^"]*\)".*/\1/p' | head -n 1)
if [ -z "$token" ]; then
  error 'nao foi possivel extrair o token de login'
  exit 1
fi

info 'Pagina de login carregou corretamente'

if [ -z "$WEBMAIL_USER" ] || [ -z "$WEBMAIL_PASSWORD" ]; then
  warn 'WEBMAIL_USER/WEBMAIL_PASSWORD nao definidos; smoke test limitado a pagina de login'
  exit 0
fi

info 'Executando login no webmail'
curl $curl_args -D "$login_headers" -b "$cookie_jar" -c "$cookie_jar" \
  --data-urlencode "_token=$token" \
  --data '_task=login' \
  --data '_action=login' \
  --data '_timezone=_default_' \
  --data '_url=' \
  --data-urlencode "_user=$WEBMAIL_USER" \
  --data-urlencode "_pass=$WEBMAIL_PASSWORD" \
  "${WEBMAIL_URL}?_task=login" -o "$login_response"

if grep -Eiq '^location: .*_task=mail|^location: .*_mbox=INBOX' "$login_headers" || grep -Eq '_task=mail|_mbox=INBOX' "$login_response"; then
  info 'Login no webmail validado com sucesso'
  exit 0
fi

show_recent_roundcube_errors
show_failed_login_response
error 'Login no webmail falhou'
exit 1