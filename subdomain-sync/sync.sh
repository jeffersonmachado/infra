#!/bin/sh

set -eu

MYSQL_HOST="${MYSQL_HOST:-10.10.2.99}"
MYSQL_PORT="${MYSQL_PORT:-3306}"
MYSQL_DATABASE="${MYSQL_DATABASE:-results}"
MYSQL_USER="${MYSQL_USER:-results}"
MYSQL_PASSWORD="${MYSQL_PASSWORD:-}"
MYSQL_SSL="${MYSQL_SSL:-preferred}"
TABLE_NAME="${DYNAMIC_VHOSTS_TABLE:-apache_vhosts}"
SYNC_INTERVAL="${DYNAMIC_SYNC_INTERVAL:-15}"

RUNTIME_DIR="/work/runtime"
CONTROL_DIR="/work/control"
OUTPUT_FILE="$RUNTIME_DIR/90-dynamic-generated.conf"
MARKER_FILE="$RUNTIME_DIR/.sync-ok"
RELOAD_FILE="$CONTROL_DIR/reload.request"

mkdir -p "$RUNTIME_DIR" "$CONTROL_DIR"
printf 'ok\n' > "$MARKER_FILE"

render_config() {
  tmp_file="$1"
  row_sep="$(printf '\037')"

  ssl_args=''
  case "$(printf '%s' "$MYSQL_SSL" | tr '[:upper:]' '[:lower:]')" in
    disable|disabled|off|false|0|no)
      ssl_args='--skip-ssl'
      ;;
  esac

  {
    echo '# Generated automatically by subdomain-sync'
    echo "# Source table: $TABLE_NAME"
    echo
  } > "$tmp_file"

  MYSQL_PWD="$MYSQL_PASSWORD" mariadb \
    --host="$MYSQL_HOST" \
    --port="$MYSQL_PORT" \
    --user="$MYSQL_USER" \
    --database="$MYSQL_DATABASE" \
    $ssl_args \
    --batch \
    --raw \
    --skip-column-names \
    --execute "SELECT CONCAT(COALESCE(server_name, ''), CHAR(31), COALESCE(server_alias, ''), CHAR(31), COALESCE(backend_scheme, 'http'), CHAR(31), COALESCE(backend_host, ''), CHAR(31), backend_port, CHAR(31), COALESCE(backend_path, '/'), CHAR(31), COALESCE(ssl_insecure, 0), CHAR(31), COALESCE(enabled, 1)) FROM $TABLE_NAME WHERE enabled = 1 ORDER BY server_name" |
  while IFS="$row_sep" read -r server_name server_alias backend_scheme backend_host backend_port backend_path ssl_insecure enabled; do
    if [ -z "$server_name" ]; then
      continue
    fi

    aliases="$(printf '%s' "$server_alias" | tr ',' ' ')"
    redirect_base="https://$server_name"
    upstream="${backend_scheme}://${backend_host}:${backend_port}${backend_path}"

    {
      echo "MDomain $server_name"
      echo
      echo '<VirtualHost *:8080>'
      echo "    ServerName $server_name"
      if [ -n "$aliases" ]; then
        echo "    ServerAlias $aliases"
      fi
      echo '    RewriteEngine On'
      echo '    RewriteCond %{REQUEST_URI} !^/\\.well-known/acme-challenge/'
      echo "    RewriteRule ^ ${redirect_base}%{REQUEST_URI} [R=301,L]"
      echo '</VirtualHost>'
      echo
      echo '<VirtualHost *:8443>'
      echo "    ServerName $server_name"
      if [ -n "$aliases" ]; then
        echo "    ServerAlias $aliases"
      fi
      echo '    SSLEngine on'
      echo '    SSLProtocol -all +TLSv1.2 +TLSv1.3'
      echo '    SSLCipherSuite HIGH:!aNULL:!MD5:!3DES'
      echo '    SSLHonorCipherOrder Off'
      echo '    SSLCompression Off'
      echo '    SSLSessionTickets Off'
      if [ "$backend_scheme" = 'https' ]; then
        echo '    SSLProxyEngine On'
        if [ "$ssl_insecure" = '1' ]; then
          echo '    SSLProxyVerify none'
          echo '    SSLProxyCheckPeerName off'
          echo '    SSLProxyCheckPeerCN off'
          echo '    SSLProxyCheckPeerExpire off'
        fi
      fi
      echo "    ProxyPass        /  $upstream"
      echo "    ProxyPassReverse /  $upstream"
      echo '    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"'
      echo '    Header always set X-Content-Type-Options "nosniff"'
      echo '    Header always set X-Frame-Options "SAMEORIGIN"'
      echo '    Header always set Referrer-Policy "strict-origin-when-cross-origin"'
      echo '</VirtualHost>'
      echo
    } >> "$tmp_file"
  done
}

while true; do
  tmp_file="$(mktemp)"

  if render_config "$tmp_file"; then
    if [ ! -f "$OUTPUT_FILE" ] || ! cmp -s "$tmp_file" "$OUTPUT_FILE"; then
      mv "$tmp_file" "$OUTPUT_FILE"
      date +%s > "$RELOAD_FILE"
      echo 'sync ok changed=true'
    else
      rm -f "$tmp_file"
      echo 'sync ok changed=false'
    fi
  else
    rm -f "$tmp_file"
    echo 'sync error: failed to fetch rows or render config'
  fi

  sleep "$SYNC_INTERVAL"
done