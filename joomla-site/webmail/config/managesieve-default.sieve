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