#!/bin/sh
set -eu

mkdir -p /etc/postfix /var/spool/postfix /var/mail/vhosts /certs

CERT_VERSION_FILE="${MAIL_CERT_VERSION_FILE:-/certs/.cert-version}"

LDAP_SERVER_HOSTS="$(printf '%s' "${LDAP_URI:-}" | sed 's#ldap://##g; s#,# #g')"

export MAIL_HOSTNAME MAIL_DOMAIN MAIL_RELAY_NETWORKS MAIL_MESSAGE_SIZE_LIMIT \
  MAIL_MYSQL_HOST MAIL_MYSQL_PORT MAIL_MYSQL_DATABASE MAIL_MYSQL_USER MAIL_MYSQL_PASSWORD \
  MAIL_MAILBOX_TABLE MAIL_ALIAS_TABLE MAIL_DOMAIN_TABLE MAIL_TLS_CERT_FILE MAIL_TLS_KEY_FILE \
  MAIL_ENABLE_LDAP LDAP_URI LDAP_BASE_DN LDAP_BIND_DN LDAP_BIND_PASSWORD LDAP_USERS_BASE_DN \
  LDAP_GROUPS_BASE_DN LDAP_SERVER_HOSTS RSPAMD_MILTER_HOST RSPAMD_MILTER_PORT

envsubst < /templates/main.cf.template > /etc/postfix/main.cf
envsubst < /templates/master.cf.template > /etc/postfix/master.cf
envsubst < /templates/mysql_virtual_mailbox_maps.cf.template > /etc/postfix/mysql_virtual_mailbox_maps.cf
envsubst < /templates/mysql_virtual_alias_maps.cf.template > /etc/postfix/mysql_virtual_alias_maps.cf
envsubst < /templates/mysql_transport_maps.cf.template > /etc/postfix/mysql_transport_maps.cf

if [ "${MAIL_ENABLE_LDAP:-true}" = "true" ]; then
  envsubst < /templates/ldap_virtual_alias_maps.cf.template > /etc/postfix/ldap_virtual_alias_maps.cf
else
  : > /etc/postfix/ldap_virtual_alias_maps.cf
fi

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
          postfix reload || true
        fi
        last_seen="$current_seen"
      fi
    fi
    sleep 30
  done
}

wait_for_certificates

postfix start-fg &
postfix_pid=$!

watch_certificate_updates &
watcher_pid=$!

trap 'kill "$watcher_pid" 2>/dev/null || true; postfix stop || true; wait "$postfix_pid" 2>/dev/null || true' INT TERM

wait "$postfix_pid"
status=$?
kill "$watcher_pid" 2>/dev/null || true
wait "$watcher_pid" 2>/dev/null || true
exit "$status"