#!/bin/sh
set -eu

mkdir -p /var/www/html/results/cache /var/www/html/results/logs /tmp
chown -R www-data:www-data /var/www/html/results/cache /var/www/html/results/logs /tmp

if [ -d /var/www/html/results/webmail ]; then
	mkdir -p /var/www/html/results/webmail/logs /var/www/html/results/webmail/temp
	chown -R www-data:www-data /var/www/html/results/webmail/logs /var/www/html/results/webmail/temp

	if [ -f /var/www/html/results/webmail/config/config.inc.php ]; then
		if [ -w /var/www/html/results/webmail/config/config.inc.php ]; then
			sed -i 's#\$config\['\''default_host'\''\] = .*#\$config['\''default_host'\''] = '\''ssl://mx1.results.com.br'\'';#' /var/www/html/results/webmail/config/config.inc.php || true
			sed -i 's#\$config\['\''smtp_server'\''\] = .*#\$config['\''smtp_server'\''] = '\''tls://mx1.results.com.br'\'';#' /var/www/html/results/webmail/config/config.inc.php || true
			sed -i 's#\$config\['\''smtp_user'\''\] = .*#\$config['\''smtp_user'\''] = '\''%u'\'';#' /var/www/html/results/webmail/config/config.inc.php || true
			grep -Fq "\$config['smtp_port']" /var/www/html/results/webmail/config/config.inc.php || \
				printf "\n\$config['smtp_port'] = 587;\n" >> /var/www/html/results/webmail/config/config.inc.php
			grep -Fq "\$config['imap_conn_options']" /var/www/html/results/webmail/config/config.inc.php || cat <<'EOF' >> /var/www/html/results/webmail/config/config.inc.php

$config['imap_conn_options'] = [
	'ssl' => [
		'verify_peer' => false,
		'verify_peer_name' => false,
		'allow_self_signed' => true,
	],
];

$config['smtp_conn_options'] = [
	'ssl' => [
		'verify_peer' => false,
		'verify_peer_name' => false,
		'allow_self_signed' => true,
	],
];
EOF
		fi
	fi
fi

if [ -f /var/www/html/results/configuration.php ]; then
	sed -i 's#public \$tmp_path = .*#public \$tmp_path = '\''/tmp'\'';#' /var/www/html/results/configuration.php || true
	sed -i 's#public \$log_path = .*#public \$log_path = '\''/var/www/html/results/logs'\'';#' /var/www/html/results/configuration.php || true
	if [ -n "${JOOMLA_DB_HOST:-}" ]; then
		sed -i "s#public \\\$host = .*#public \\\$host = '${JOOMLA_DB_HOST}';#" /var/www/html/results/configuration.php || true
	fi
	if [ -n "${JOOMLA_DB_NAME:-}" ]; then
		sed -i "s#public \\\$db = .*#public \\\$db = '${JOOMLA_DB_NAME}';#" /var/www/html/results/configuration.php || true
	fi
	if [ -n "${JOOMLA_DB_USER:-}" ]; then
		sed -i "s#public \\\$user = .*#public \\\$user = '${JOOMLA_DB_USER}';#" /var/www/html/results/configuration.php || true
	fi
	if [ -n "${JOOMLA_DB_PASSWORD:-}" ]; then
		sed -i "s#public \\\$password = .*#public \\\$password = '${JOOMLA_DB_PASSWORD}';#" /var/www/html/results/configuration.php || true
	fi
fi

exec "$@"