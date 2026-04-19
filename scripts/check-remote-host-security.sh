#!/bin/sh
set -eu

REMOTE_HOST="${DEPLOY_HOST:-10.10.2.30}"
REMOTE_USER="${DEPLOY_USER:-root}"
REMOTE_PORT="${DEPLOY_PORT:-22}"
SSH_PASSWORD="${SSH_PASSWORD:-${DEPLOY_SSH_PASSWORD:-}}"

SSH_OPTS="-p ${REMOTE_PORT} -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ConnectTimeout=30"

if [ -z "${SSHPASS:-}" ] && [ -n "$SSH_PASSWORD" ]; then
  export SSHPASS="$SSH_PASSWORD"
fi

if [ -n "${SSHPASS:-}" ]; then
  if ! command -v sshpass >/dev/null 2>&1; then
    echo "sshpass nao encontrado" >&2
    exit 1
  fi
  SSH_CMD="sshpass -e ssh ${SSH_OPTS}"
else
  SSH_CMD="ssh ${SSH_OPTS}"
fi

remote() {
  ${SSH_CMD} "${REMOTE_USER}@${REMOTE_HOST}" "$1"
}

echo "=== FAIL2BAN ==="
remote "fail2ban-client status && echo --- && fail2ban-client status results-postfix-auth"

echo
echo "=== FIREWALL ==="
remote "iptables -L INPUT -n -v | head -n 20 && echo --- && iptables -L RESULTS-RATE-LIMIT -n -v"

echo
echo "=== POSTFIX SASL LOGS ==="
remote "docker logs --tail 80 results-mail-postfix 2>&1 | grep 'SASL PLAIN authentication failed' | tail -n 20 || true"