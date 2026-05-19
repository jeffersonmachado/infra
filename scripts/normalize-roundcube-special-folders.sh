#!/bin/sh
set -eu

JOOMLA_CONTAINER="${JOOMLA_CONTAINER:-results-joomla}"
DOVECOT_CONTAINER="${DOVECOT_CONTAINER:-results-mail-dovecot}"
MAIL_ROOT="${MAIL_ROOT:-/var/lib/docker/volumes/infra-mail_maildata/_data}"
ROUNDCUBE_DB_DSN="${ROUNDCUBE_DB_DSN:-mysql:host=srvmysql.results.intranet;dbname=roundcubemail}"
ROUNDCUBE_DB_USER="${ROUNDCUBE_DB_USER:-roundcube}"
ROUNDCUBE_DB_PASSWORD="${ROUNDCUBE_DB_PASSWORD:-resu100roundcube}"

info() {
  printf '[INFO] %s\n' "$1"
}

warn() {
  printf '[WARN] %s\n' "$1"
}

if ! command -v docker >/dev/null 2>&1; then
  warn 'docker nao encontrado; normalizacao ignorada'
  exit 0
fi

if docker ps --format '{{.Names}}' | grep -Fx "$JOOMLA_CONTAINER" >/dev/null 2>&1; then
  info 'Normalizando preferencias do Roundcube no banco'
  cat <<'PHP' | docker exec -e ROUNDCUBE_DB_DSN="$ROUNDCUBE_DB_DSN" -e ROUNDCUBE_DB_USER="$ROUNDCUBE_DB_USER" -e ROUNDCUBE_DB_PASSWORD="$ROUNDCUBE_DB_PASSWORD" -i "$JOOMLA_CONTAINER" php >/tmp/normalize-roundcube-special-folders.log
<?php
$pdo = new PDO(getenv('ROUNDCUBE_DB_DSN'), getenv('ROUNDCUBE_DB_USER'), getenv('ROUNDCUBE_DB_PASSWORD'), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$map = [
    'INBOX.Drafts' => 'Drafts',
    'INBOX.Sent' => 'Sent',
    'INBOX.Spam' => 'Spam',
    'INBOX.Junk' => 'Spam',
    'INBOX.Trash' => 'Trash',
    'INBOX.Templates' => 'Templates',
    'INBOX.Archive' => 'Archive',
    'Junk' => 'Spam',
];

$normalize = function ($value) use (&$normalize, $map) {
    if (is_array($value)) {
        foreach ($value as $key => $item) {
            $value[$key] = $normalize($item);
        }
        return $value;
    }

    if (is_string($value) && isset($map[$value])) {
        return $map[$value];
    }

    return $value;
};

$select = $pdo->query('SELECT user_id, preferences FROM users');
$update = $pdo->prepare('UPDATE users SET preferences = ? WHERE user_id = ?');
$changed = 0;

while ($row = $select->fetch(PDO::FETCH_ASSOC)) {
    if (!is_string($row['preferences']) || $row['preferences'] === '') {
        continue;
    }

    $prefs = @unserialize($row['preferences']);
    if (!is_array($prefs)) {
        continue;
    }

    $normalized = $normalize($prefs);

    if (isset($normalized['junk_mbox'])) {
        $normalized['junk_mbox'] = 'Spam';
    }

    if (isset($normalized['default_folders']) && is_array($normalized['default_folders'])) {
        $normalized['default_folders'] = array_values(array_unique(array_map(function ($folder) {
            if ($folder === 'Junk') {
                return 'Spam';
            }
            return $folder;
        }, $normalized['default_folders'])));
    }

    $normalized['namespace_fixed'] = false;

    if ($normalized !== $prefs) {
        $update->execute([serialize($normalized), $row['user_id']]);
        $changed++;
    }
}

echo 'UPDATED=', $changed, PHP_EOL;
PHP
  cat /tmp/normalize-roundcube-special-folders.log
  rm -f /tmp/normalize-roundcube-special-folders.log
else
  warn "container $JOOMLA_CONTAINER ausente; preferencias do Roundcube nao normalizadas"
fi

if docker ps --format '{{.Names}}' | grep -Fx "$DOVECOT_CONTAINER" >/dev/null 2>&1 && [ -d "$MAIL_ROOT" ]; then
  info 'Normalizando subscriptions do Dovecot'
  find "$MAIL_ROOT" -mindepth 3 -maxdepth 3 -type d -name Maildir | while read -r maildir; do
    user_dir=$(dirname "$maildir")
    user_name=$(basename "$user_dir")
    domain_name=$(basename "$(dirname "$user_dir")")
    mailbox="$user_name@$domain_name"

    docker exec "$DOVECOT_CONTAINER" doveadm mailbox subscribe -u "$mailbox" Sent >/dev/null 2>&1 || true
    docker exec "$DOVECOT_CONTAINER" doveadm mailbox subscribe -u "$mailbox" Spam >/dev/null 2>&1 || true
    docker exec "$DOVECOT_CONTAINER" doveadm mailbox subscribe -u "$mailbox" Trash >/dev/null 2>&1 || true
    docker exec "$DOVECOT_CONTAINER" doveadm mailbox subscribe -u "$mailbox" Drafts >/dev/null 2>&1 || true
    docker exec "$DOVECOT_CONTAINER" doveadm mailbox unsubscribe -u "$mailbox" INBOX.Sent >/dev/null 2>&1 || true
    docker exec "$DOVECOT_CONTAINER" doveadm mailbox unsubscribe -u "$mailbox" INBOX.Spam >/dev/null 2>&1 || true
    docker exec "$DOVECOT_CONTAINER" doveadm mailbox unsubscribe -u "$mailbox" INBOX.Junk >/dev/null 2>&1 || true
    docker exec "$DOVECOT_CONTAINER" doveadm mailbox unsubscribe -u "$mailbox" INBOX.Trash >/dev/null 2>&1 || true
    docker exec "$DOVECOT_CONTAINER" doveadm mailbox unsubscribe -u "$mailbox" INBOX.Drafts >/dev/null 2>&1 || true
  done
else
  warn "container $DOVECOT_CONTAINER ausente ou MAIL_ROOT indisponivel; subscriptions nao normalizadas"
fi

info 'Normalizacao concluida'