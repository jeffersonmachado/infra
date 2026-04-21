#!/bin/sh
set -eu

REMOTE_HOST="${DEPLOY_HOST:-10.10.2.30}"
REMOTE_USER="${DEPLOY_USER:-root}"
REMOTE_PORT="${DEPLOY_PORT:-22}"
SSH_PASSWORD="${SSH_PASSWORD:-${DEPLOY_SSH_PASSWORD:-}}"
RSPAMD_LOG_WINDOW="${RSPAMD_LOG_WINDOW:-24h}"
RSPAMD_LOG_TAIL="${RSPAMD_LOG_TAIL:-200}"

SSH_OPTS="-p ${REMOTE_PORT} -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ConnectTimeout=30 -o LogLevel=ERROR"

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
remote "fail2ban-client status | grep -E 'Number of jail|Jail list' && echo --- && fail2ban-client status results-postfix-auth | grep -E 'Status for the jail|Currently failed|Total failed|Currently banned|Total banned|Banned IP list'"

echo
echo "=== FIREWALL ==="
remote "iptables -L INPUT -n -v | head -n 20 && echo --- && iptables -L RESULTS-RATE-LIMIT -n -v"

echo
echo "=== POSTFIX SASL LOGS ==="
remote "logs=\$(docker logs --tail 80 results-mail-postfix 2>&1 | grep 'SASL PLAIN authentication failed' | tail -n 20 || true); if [ -n \"\$logs\" ]; then printf '%s\n' \"\$logs\"; else echo 'nenhuma falha SASL recente'; fi"

echo
echo "=== RSPAMD CAMPAIGN COMPOSITES ==="
remote "logs=\$(docker logs --since '${RSPAMD_LOG_WINDOW}' --tail '${RSPAMD_LOG_TAIL}' results-mail-rspamd 2>&1 | grep 'LOCAL_AUTH_SPAM_CAMPAIGN' || true); if [ -n \"\$logs\" ]; then printf '%s\n' \"\$logs\"; else echo 'nenhum disparo recente das composites locais'; fi"

echo
echo "=== POSTFIX REJECTIONS ==="
remote "logs=\$(docker logs --since '${RSPAMD_LOG_WINDOW}' --tail '${RSPAMD_LOG_TAIL}' results-mail-postfix 2>&1 | grep -Ei 'milter-reject|reject:|blocked using LOCAL_AUTH_SPAM_CAMPAIGN|LOCAL_AUTH_SPAM_CAMPAIGN' || true); if [ -n \"\$logs\" ]; then printf '%s\n' \"\$logs\"; else echo 'nenhuma rejeicao recente relacionada a campanhas'; fi"