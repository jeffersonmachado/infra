#!/bin/sh
set -eu

: "${SERVER_NAME:=results.com.br}"
: "${RESULTS_SERVER_NAME:=${SERVER_NAME}}"
: "${RESULTS_SERVER_ALIAS:=www.results.com.br}"
: "${RESULTS_ROOT_BACKEND_SCHEME:=}"
: "${RESULTS_ROOT_BACKEND_HOST:=}"
: "${RESULTS_ROOT_BACKEND_PORT:=}"
: "${RESULTS_ROOT_BACKEND_PATH:=/}"
: "${RESULTS_ROOT_BACKEND_SSL_INSECURE:=0}"
: "${COLABORACAO_SERVER_NAME:=}"
: "${OLIMPICSHAPE_SERVER_NAME:=}"
: "${PLAY_SERVER_NAME:=}"
: "${PLAY_DEV_SERVER_NAME:=}"
: "${RIPABX_SERVER_NAME:=}"
: "${RVPN_SERVER_NAME:=}"
: "${ADMIN_EMAIL:?ADMIN_EMAIL is required}"
: "${ACME_CA_URL:=https://acme-v02.api.letsencrypt.org/directory}"
: "${HTTPS_EXTERNAL_PORT:=443}"
: "${UPSTREAM_HOST_30:=10.10.2.30}"
: "${UPSTREAM_HOST_22:=10.10.2.22}"
: "${UPSTREAM_HOST_7:=10.10.2.7}"
: "${RIPABX_INTERNAL_HOST:=ripabx.results.intranet}"
: "${RVPN_UPSTREAM_HOST:=${UPSTREAM_HOST_30}}"
: "${RVPN_UPSTREAM_PORT:=10443}"

HTTPS_REDIRECT_SUFFIX=""
if [ "$HTTPS_EXTERNAL_PORT" != "443" ]; then
  HTTPS_REDIRECT_SUFFIX=":${HTTPS_EXTERNAL_PORT}"
fi

RESULTS_ROOT_PROXY_BLOCK=""
if [ -n "$RESULTS_ROOT_BACKEND_HOST" ]; then
  if [ -z "$RESULTS_ROOT_BACKEND_SCHEME" ]; then
    RESULTS_ROOT_BACKEND_SCHEME="http"
  fi

  results_root_upstream="${RESULTS_ROOT_BACKEND_SCHEME}://${RESULTS_ROOT_BACKEND_HOST}"
  if [ -n "$RESULTS_ROOT_BACKEND_PORT" ]; then
    results_root_upstream="${results_root_upstream}:${RESULTS_ROOT_BACKEND_PORT}"
  fi
  results_root_upstream="${results_root_upstream}${RESULTS_ROOT_BACKEND_PATH}"

  RESULTS_ROOT_PROXY_BLOCK="    ProxyPreserveHost On
    RequestHeader set X-Forwarded-Proto \"https\"
    RequestHeader set X-Forwarded-Host \"${RESULTS_SERVER_NAME}\""

  if [ "$RESULTS_ROOT_BACKEND_SCHEME" = "https" ]; then
    RESULTS_ROOT_PROXY_BLOCK="$RESULTS_ROOT_PROXY_BLOCK
    SSLProxyEngine On"
    if [ "$RESULTS_ROOT_BACKEND_SSL_INSECURE" = "1" ]; then
      RESULTS_ROOT_PROXY_BLOCK="$RESULTS_ROOT_PROXY_BLOCK
    SSLProxyVerify none
    SSLProxyCheckPeerName off
    SSLProxyCheckPeerCN off
    SSLProxyCheckPeerExpire off"
    fi
  fi

  RESULTS_ROOT_PROXY_BLOCK="$RESULTS_ROOT_PROXY_BLOCK
    ProxyPass        \"/\"  \"${results_root_upstream}\" nocanon
    ProxyPassReverse \"/\"  \"${results_root_upstream}\"
    ProxyPassReverseCookiePath \"/\" \"/\""
fi

mkdir -p /usr/local/apache2/md /usr/local/apache2/conf/vhosts /tmp
mkdir -p /usr/local/apache2/conf/runtime /var/run/apache-runtime
rm -f /usr/local/apache2/conf/vhosts/*.conf

APACHE_MDOMAIN_LINES="MDomain ${RESULTS_SERVER_NAME} ${RESULTS_SERVER_ALIAS}"

if [ -n "$COLABORACAO_SERVER_NAME" ]; then
  APACHE_MDOMAIN_LINES="$APACHE_MDOMAIN_LINES
MDomain ${COLABORACAO_SERVER_NAME}"
fi

if [ -n "$PLAY_SERVER_NAME" ]; then
  APACHE_MDOMAIN_LINES="$APACHE_MDOMAIN_LINES
MDomain ${PLAY_SERVER_NAME}"
fi

if [ -n "$RIPABX_SERVER_NAME" ]; then
  APACHE_MDOMAIN_LINES="$APACHE_MDOMAIN_LINES
MDomain ${RIPABX_SERVER_NAME}"
fi

if [ -n "$OLIMPICSHAPE_SERVER_NAME" ]; then
  APACHE_MDOMAIN_LINES="$APACHE_MDOMAIN_LINES
MDomain ${OLIMPICSHAPE_SERVER_NAME}"
fi

if [ -n "$PLAY_DEV_SERVER_NAME" ]; then
  APACHE_MDOMAIN_LINES="$APACHE_MDOMAIN_LINES
MDomain ${PLAY_DEV_SERVER_NAME}"
fi

if [ -n "$RVPN_SERVER_NAME" ]; then
  APACHE_MDOMAIN_LINES="$APACHE_MDOMAIN_LINES
MDomain ${RVPN_SERVER_NAME}"
fi

export SERVER_NAME RESULTS_SERVER_NAME RESULTS_SERVER_ALIAS COLABORACAO_SERVER_NAME OLIMPICSHAPE_SERVER_NAME
export PLAY_SERVER_NAME PLAY_DEV_SERVER_NAME RIPABX_SERVER_NAME RVPN_SERVER_NAME ADMIN_EMAIL ACME_CA_URL HTTPS_EXTERNAL_PORT
export HTTPS_REDIRECT_SUFFIX UPSTREAM_HOST_30 UPSTREAM_HOST_22 UPSTREAM_HOST_7 RIPABX_INTERNAL_HOST RVPN_UPSTREAM_HOST RVPN_UPSTREAM_PORT APACHE_MDOMAIN_LINES RESULTS_ROOT_PROXY_BLOCK

envsubst \
  < /usr/local/apache2/conf/httpd.conf.template \
  > /usr/local/apache2/conf/httpd.conf

templates="00-results"

if [ -n "$COLABORACAO_SERVER_NAME" ]; then
  templates="$templates 10-colaboracao"
fi

if [ -n "$OLIMPICSHAPE_SERVER_NAME" ]; then
  templates="$templates 20-olimpicshape"
fi

if [ -n "$PLAY_SERVER_NAME" ]; then
  templates="$templates 30-play"
fi

if [ -n "$PLAY_DEV_SERVER_NAME" ]; then
  templates="$templates 31-play-dev"
fi

if [ -n "$RIPABX_SERVER_NAME" ]; then
  templates="$templates 40-ripabx"
fi

if [ -n "$RVPN_SERVER_NAME" ]; then
  templates="$templates 50-rvpn"
fi

for template_name in $templates; do
  template="/usr/local/apache2/conf/vhosts-templates/${template_name}.conf.template"
  target="/usr/local/apache2/conf/vhosts/${template_name}.conf"
  envsubst < "$template" > "$target"
done

reload_watcher() {
  while true; do
    if [ -f /var/run/apache-runtime/reload.request ]; then
      rm -f /var/run/apache-runtime/reload.request
      if httpd -t >/tmp/httpd-reload-check.log 2>&1; then
        httpd -k graceful >/tmp/httpd-reload.log 2>&1 || cat /tmp/httpd-reload.log >&2
      else
        cat /tmp/httpd-reload-check.log >&2
      fi
    fi
    sleep 2
  done
}

reload_watcher &

exec "$@"