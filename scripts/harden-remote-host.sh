#!/bin/sh
set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
ROOT_DIR=$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)

DEFAULT_HOST_SECURITY_ENV_FILE="$ROOT_DIR/.env.host-security"
HOST_SECURITY_ENV_FILE="${HOST_SECURITY_ENV_FILE:-$DEFAULT_HOST_SECURITY_ENV_FILE}"

if [ -n "$HOST_SECURITY_ENV_FILE" ]; then
  case "$HOST_SECURITY_ENV_FILE" in
    /*) HOST_SECURITY_ENV_PATH="$HOST_SECURITY_ENV_FILE" ;;
    *) HOST_SECURITY_ENV_PATH="$ROOT_DIR/$HOST_SECURITY_ENV_FILE" ;;
  esac

  if [ -f "$HOST_SECURITY_ENV_PATH" ]; then
    set -a
    . "$HOST_SECURITY_ENV_PATH"
    set +a
  fi
fi

FAIL2BAN_JAIL_DIR=/etc/fail2ban/jail.d
FAIL2BAN_FILTER_DIR=/etc/fail2ban/filter.d
FIREWALL_SCRIPT=/usr/local/sbin/results-firewall.sh
LOCALD_SCRIPT=/etc/local.d/results-firewall.start

TRUSTED_NETWORKS="${TRUSTED_NETWORKS:-127.0.0.1/8 10.10.2.0/24}"
HTTP_LIMIT_PER_MINUTE="${HTTP_LIMIT_PER_MINUTE:-120}"
SMTP_LIMIT_PER_MINUTE="${SMTP_LIMIT_PER_MINUTE:-25}"
SSH_BANTIME="${SSH_BANTIME:-1h}"
SSH_FINDTIME="${SSH_FINDTIME:-10m}"
SSH_MAXRETRY="${SSH_MAXRETRY:-6}"
POSTFIX_BANTIME="${POSTFIX_BANTIME:-1h}"
POSTFIX_FINDTIME="${POSTFIX_FINDTIME:-10m}"
POSTFIX_MAXRETRY="${POSTFIX_MAXRETRY:-6}"

install_packages() {
  apk add --no-cache fail2ban iptables ip6tables >/dev/null
}

write_fail2ban_filter() {
  mkdir -p "$FAIL2BAN_FILTER_DIR"
  cat > "$FAIL2BAN_FILTER_DIR/results-postfix-auth.conf" <<'EOF'
[Definition]
failregex = ^.*postfix/(submission/|smtps/)?smtpd\[[0-9]+\]: warning: [^[]*\[<HOST>\]: SASL [A-Z0-9_-]+ authentication failed:.*$
ignoreregex =
EOF
}

write_fail2ban_jail() {
  mkdir -p "$FAIL2BAN_JAIL_DIR"
  cat > "$FAIL2BAN_JAIL_DIR/results.local" <<EOF
[DEFAULT]
ignoreip = 127.0.0.1/8 10.10.2.0/24
banaction = iptables-multiport

[sshd]
enabled = true
backend = auto
logpath = /var/log/messages
maxretry = ${SSH_MAXRETRY}
findtime = ${SSH_FINDTIME}
bantime = ${SSH_BANTIME}

[results-postfix-auth]
enabled = true
backend = auto
filter = results-postfix-auth
logpath = /var/lib/docker/containers/*/*-json.log
maxretry = ${POSTFIX_MAXRETRY}
findtime = ${POSTFIX_FINDTIME}
bantime = ${POSTFIX_BANTIME}
port = 25,465,587
protocol = tcp
EOF
}

write_firewall_script() {
  mkdir -p /usr/local/sbin
  cat > "$FIREWALL_SCRIPT" <<EOF
#!/bin/sh
set -eu

CHAIN=RESULTS-RATE-LIMIT

ensure_chain() {
  iptables -nL "\$CHAIN" >/dev/null 2>&1 || iptables -N "\$CHAIN"
  iptables -C DOCKER-USER -j "\$CHAIN" >/dev/null 2>&1 || iptables -I DOCKER-USER 1 -j "\$CHAIN"
  iptables -C INPUT -j "\$CHAIN" >/dev/null 2>&1 || iptables -I INPUT 1 -j "\$CHAIN"
  iptables -F "\$CHAIN"
}

ensure_chain

iptables -A "\$CHAIN" -m conntrack --ctstate ESTABLISHED,RELATED -j RETURN
EOF

  for network in $TRUSTED_NETWORKS; do
    cat >> "$FIREWALL_SCRIPT" <<EOF
iptables -A "\$CHAIN" -s ${network} -j RETURN
EOF
  done

  cat >> "$FIREWALL_SCRIPT" <<EOF
iptables -A "\$CHAIN" -p tcp -m conntrack --ctstate NEW -m multiport --dports 80,443 -m recent --name results-http --rsource --update --seconds 60 --hitcount ${HTTP_LIMIT_PER_MINUTE} -j DROP
iptables -A "\$CHAIN" -p tcp -m conntrack --ctstate NEW -m multiport --dports 80,443 -m recent --name results-http --rsource --set -j RETURN
iptables -A "\$CHAIN" -p tcp -m conntrack --ctstate NEW -m multiport --dports 25,465,587 -m recent --name results-smtp --rsource --update --seconds 60 --hitcount ${SMTP_LIMIT_PER_MINUTE} -j DROP
iptables -A "\$CHAIN" -p tcp -m conntrack --ctstate NEW -m multiport --dports 25,465,587 -m recent --name results-smtp --rsource --set -j RETURN
iptables -A "\$CHAIN" -j RETURN
EOF

  chmod 755 "$FIREWALL_SCRIPT"
}

write_locald_script() {
  mkdir -p /etc/local.d
  cat > "$LOCALD_SCRIPT" <<'EOF'
#!/bin/sh
/usr/local/sbin/results-firewall.sh
EOF
  chmod 755 "$LOCALD_SCRIPT"
}

enable_services() {
  rc-update add local default >/dev/null 2>&1 || true
  rc-update add fail2ban default >/dev/null 2>&1 || true
  rc-service fail2ban restart >/dev/null 2>&1 || rc-service fail2ban start >/dev/null 2>&1
}

status() {
  echo "---FAIL2BAN---"
  fail2ban-client status 2>/dev/null || true
  echo "---RESULTS-RATE-LIMIT---"
  iptables -S RESULTS-RATE-LIMIT 2>/dev/null || true
}

apply() {
  install_packages
  write_fail2ban_filter
  write_fail2ban_jail
  write_firewall_script
  write_locald_script
  "$FIREWALL_SCRIPT"
  enable_services
  status
}

case "${1:-apply}" in
  apply)
    apply
    ;;
  status)
    status
    ;;
  *)
    echo "uso: $0 [apply|status]" >&2
    exit 1
    ;;
esac