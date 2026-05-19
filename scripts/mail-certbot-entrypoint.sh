#!/bin/sh

set -eu

CERT_DIR="${MAIL_CERT_DIR:-/certs}"
CERT_FILE="${MAIL_TLS_CERT_FILE:-$CERT_DIR/fullchain.pem}"
KEY_FILE="${MAIL_TLS_KEY_FILE:-$CERT_DIR/privkey.pem}"
CERT_VERSION_FILE="${MAIL_CERT_VERSION_FILE:-$CERT_DIR/.cert-version}"
ACME_RENEW_INTERVAL="${MAIL_ACME_RENEW_INTERVAL:-43200}"
ACME_SOURCE_BASE_DIR="${MAIL_ACME_SOURCE_BASE_DIR:-}"

mkdir -p "$CERT_DIR"

raw_domains="${MAIL_ACME_DOMAINS:-${MAIL_HOSTNAME:-} ${MAIL_MX2_HOSTNAME:-} ${MAIL_IMAP_HOSTNAME:-}}"
domains="$(printf '%s\n' "$raw_domains" | tr ', ' '\n\n' | awk 'NF && !seen[$0]++')"
primary_domain="$(printf '%s\n' "$domains" | sed -n '1p')"

if [ -z "$domains" ]; then
  echo "Nenhum dominio configurado para o sincronismo de certificados do mail" >&2
  exit 1
fi

certificate_covers_domains() {
  cert_path="$1"

  [ -s "$cert_path" ] || return 1

  san_output="$(openssl x509 -in "$cert_path" -noout -ext subjectAltName 2>/dev/null || true)"
  [ -n "$san_output" ] || return 1

  for domain in $domains; do
    printf '%s\n' "$san_output" | grep -F "DNS:$domain" >/dev/null 2>&1 || return 1
  done

  return 0
}

install_certificate_pair() {
  source_cert="$1"
  source_key="$2"

  if [ -s "$CERT_FILE" ] && [ -s "$KEY_FILE" ] \
    && cmp -s "$source_cert" "$CERT_FILE" \
    && cmp -s "$source_key" "$KEY_FILE"; then
    return 0
  fi

  cp "$source_cert" "$CERT_FILE"
  cp "$source_key" "$KEY_FILE"
  chmod 644 "$CERT_FILE"
  chmod 600 "$KEY_FILE"
  date +%s > "$CERT_VERSION_FILE"
}

sync_httpd_certificate() {
  [ -n "$ACME_SOURCE_BASE_DIR" ] || return 1
  [ -n "$primary_domain" ] || return 1

  source_dir="$ACME_SOURCE_BASE_DIR/$primary_domain"
  source_cert="$source_dir/pubcert.pem"
  source_key="$source_dir/privkey.pem"

  [ -s "$source_cert" ] || return 1
  [ -s "$source_key" ] || return 1
  certificate_covers_domains "$source_cert" || return 1

  install_certificate_pair "$source_cert" "$source_key"
}

sync_httpd_certificate || true

while :; do
  if ! sync_httpd_certificate; then
    echo "Falha ao sincronizar o certificado do Apache para a stack de mail" >&2
  fi

  sleep "$ACME_RENEW_INTERVAL"
done