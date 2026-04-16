#!/bin/sh
set -eu

mkdir -p /var/www/html/results/cache /var/www/html/results/logs /tmp
chown -R www-data:www-data /var/www/html/results/cache /var/www/html/results/logs /tmp

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