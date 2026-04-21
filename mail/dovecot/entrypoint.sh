#!/bin/sh
set -eu

mkdir -p /etc/dovecot /etc/dovecot/conf.d /etc/dovecot/sieve /var/mail/vhosts /certs /var/lib/dovecot
mkdir -p "/var/mail/vhosts/${MAIL_DOMAIN}"
chown "${MAIL_UID}:${MAIL_GID}" /var/mail/vhosts "/var/mail/vhosts/${MAIL_DOMAIN}"
chmod 0770 /var/mail/vhosts "/var/mail/vhosts/${MAIL_DOMAIN}"

CERT_VERSION_FILE="${MAIL_CERT_VERSION_FILE:-/certs/.cert-version}"

LDAP_SERVER_HOSTS="$(printf '%s' "${LDAP_URI:-}" | sed 's#ldap://##g; s#,# #g')"

export MAIL_HOSTNAME MAIL_DOMAIN MAIL_UID MAIL_GID MAIL_STORAGE_BASE \
  MAIL_MYSQL_HOST MAIL_MYSQL_PORT MAIL_MYSQL_DATABASE MAIL_MYSQL_USER MAIL_MYSQL_PASSWORD \
  MAIL_MAILBOX_TABLE MAIL_TLS_CERT_FILE MAIL_TLS_KEY_FILE MAIL_ENABLE_LDAP LDAP_URI \
  LDAP_BASE_DN LDAP_BIND_DN LDAP_BIND_PASSWORD LDAP_USERS_BASE_DN LDAP_SERVER_HOSTS

envsubst < /templates/dovecot.conf.template > /etc/dovecot/dovecot.conf
envsubst < /templates/dovecot-sql.conf.ext.template > /etc/dovecot/dovecot-sql.conf.ext
envsubst < /templates/dovecot-ldap.conf.ext.template > /etc/dovecot/dovecot-ldap.conf.ext
cp /templates/global-after.sieve /etc/dovecot/sieve/global-after.sieve

wait_for_certificates() {
  while [ ! -s "$MAIL_TLS_CERT_FILE" ] || [ ! -s "$MAIL_TLS_KEY_FILE" ]; do
    sleep 2
  done
}

watch_certificate_updates() {
  last_seen=""

  while :; do
    if [ -r "$CERT_VERSION_FILE" ]; then
      current_seen="$(cat "$CERT_VERSION_FILE" 2>/dev/null || true)"
      if [ -n "$current_seen" ] && [ "$current_seen" != "$last_seen" ]; then
        if [ -n "$last_seen" ]; then
          kill -HUP "$dovecot_pid" 2>/dev/null || true
        fi
        last_seen="$current_seen"
      fi
    fi
    sleep 30
  done
}

wait_for_certificates

sievec /etc/dovecot/sieve/global-after.sieve >/dev/null 2>&1 || true

dovecot -F &
dovecot_pid=$!

watch_certificate_updates &
watcher_pid=$!

trap 'kill "$watcher_pid" 2>/dev/null || true; kill "$dovecot_pid" 2>/dev/null || true; wait "$dovecot_pid" 2>/dev/null || true' INT TERM

wait "$dovecot_pid"
status=$?
kill "$watcher_pid" 2>/dev/null || true
wait "$watcher_pid" 2>/dev/null || true
exit "$status"