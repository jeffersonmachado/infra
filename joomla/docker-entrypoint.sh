#!/bin/sh
set -eu

mkdir -p /var/www/html/results/cache /var/www/html/results/logs /tmp
chown -R www-data:www-data /var/www/html/results/cache /var/www/html/results/logs /tmp

if [ -d /var/www/html/results/webmail ]; then
	mkdir -p /var/www/html/results/webmail/logs /var/www/html/results/webmail/temp
	chown -R www-data:www-data /var/www/html/results/webmail/logs /var/www/html/results/webmail/temp

	cat <<'EOF' > /var/www/html/results/webmail/config/managesieve-default.sieve
# Exemplo de filtro por remetente no Roundcube/ManageSieve.
#
# Este tipo de regra funciona para organizacao geral da caixa, mas nao
# sobrepoe o desvio global de mensagens marcadas com X-Spam: Yes no
# sieve_after do Dovecot.
#
# require ["fileinto"];
#
# if address :is "from" "remetente@exemplo.com" {
#   fileinto "INBOX";
#   stop;
# }
EOF
	chown www-data:www-data /var/www/html/results/webmail/config/managesieve-default.sieve
	chmod 0644 /var/www/html/results/webmail/config/managesieve-default.sieve

	# config.inc.php fica só no volume (webmail/ é excluído do lsyncd) — não regenerar aqui
fi

# configuration.php é fonte única vinda do lsyncd — não modificar
# tmp_path e log_path devem estar corretos no arquivo de origem

exec "$@"