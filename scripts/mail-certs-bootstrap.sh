#!/bin/sh

set -eu

CERT_DIR="${MAIL_CERT_DIR:-/certs}"
CERT_FILE="${MAIL_TLS_CERT_FILE:-$CERT_DIR/fullchain.pem}"
KEY_FILE="${MAIL_TLS_KEY_FILE:-$CERT_DIR/privkey.pem}"
CERT_VERSION_FILE="${MAIL_CERT_VERSION_FILE:-$CERT_DIR/.cert-version}"
BOOTSTRAP_DAYS="${MAIL_BOOTSTRAP_CERT_DAYS:-7}"

mkdir -p "$CERT_DIR"

if ! command -v openssl >/dev/null 2>&1; then
  apk add --no-cache openssl >/dev/null
fi

raw_domains="${MAIL_ACME_DOMAINS:-${MAIL_HOSTNAME:-} ${MAIL_MX2_HOSTNAME:-} ${MAIL_IMAP_HOSTNAME:-}}"
domains="$(printf '%s\n' "$raw_domains" | tr ', ' '\n\n' | awk 'NF && !seen[$0]++')"
primary_domain="$(printf '%s\n' "$domains" | sed -n '1p')"

if [ -z "$primary_domain" ]; then
  echo "Nenhum dominio configurado para o bootstrap de certificados do mail" >&2
  exit 1
fi

certificate_covers_domains() {
  [ -s "$CERT_FILE" ] || return 1
  [ -s "$KEY_FILE" ] || return 1

  san_output="$(openssl x509 -in "$CERT_FILE" -noout -ext subjectAltName 2>/dev/null || true)"
  [ -n "$san_output" ] || return 1

  for domain in $domains; do
    printf '%s\n' "$san_output" | grep -F "DNS:$domain" >/dev/null 2>&1 || return 1
  done

  return 0
}

if certificate_covers_domains; then
  date +%s > "$CERT_VERSION_FILE"
  exit 0
fi

tmp_conf="$(mktemp)"
trap 'rm -f "$tmp_conf"' EXIT

{
  echo "[req]"
  echo "distinguished_name=req_dn"
  echo "x509_extensions=v3_req"
  echo "prompt=no"
  echo "[req_dn]"
  echo "CN=$primary_domain"
  echo "[v3_req]"
  echo "subjectAltName=@alt_names"
  echo "[alt_names]"
  index=1
  printf '%s\n' "$domains" | while IFS= read -r domain; do
    [ -n "$domain" ] || continue
    echo "DNS.$index=$domain"
    index=$((index + 1))
  done
} > "$tmp_conf"

openssl req -x509 -nodes -newkey rsa:2048 \
  -days "$BOOTSTRAP_DAYS" \
  -keyout "$KEY_FILE" \
  -out "$CERT_FILE" \
  -config "$tmp_conf" >/dev/null 2>&1

chmod 600 "$KEY_FILE"
chmod 644 "$CERT_FILE"
date +%s > "$CERT_VERSION_FILE"