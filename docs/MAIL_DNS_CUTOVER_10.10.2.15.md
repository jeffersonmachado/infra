# Corte DNS do Mail em 10.10.2.15

## Estado confirmado no DNS autoritativo

Consultas feitas diretamente no servidor DNS `10.10.2.15` retornaram:

- `results.com.br MX 10 srvmail0.results.com.br`
- `results.com.br MX 20 srvmail1.results.com.br`
- `srvmail0.results.com.br A 10.10.2.2`
- `srvmail1.results.com.br A 10.10.2.23`
- `results.com.br A 10.10.2.60`
- `mx1.results.com.br A 10.10.2.3`
- `imap.results.com.br A 10.10.2.3`
- `mx2.results.com.br`: inexistente no DNS autoritativo
- `webmail.results.com.br`: inexistente no DNS autoritativo

## Implicação para o corte

Se o corte do mail deve manter os IPs históricos do serviço, ou seja:

- `mx1` em `10.10.2.3`
- `mx2` em `10.10.2.23`

então os registros MX principais de `results.com.br` já estão estruturalmente corretos.

O que muda no corte não é o nome MX, e sim a posse desses IPs pelo host novo.

## O que não precisa mudar no DNS

Enquanto a estratégia for preservar `10.10.2.3` e `10.10.2.23` como IPs de mail:

- `results.com.br MX 10 srvmail0.results.com.br`
- `results.com.br MX 20 srvmail1.results.com.br`
- `srvmail0.results.com.br A 10.10.2.3`
- `srvmail1.results.com.br A 10.10.2.23`

podem permanecer como estão.

## O que ainda precisa ser ajustado no DNS

Para alinhar os nomes operacionais e de cliente com a nova stack:

1. criar `mx2.results.com.br A 10.10.2.23`
2. manter `mx1.results.com.br A 10.10.2.3`
3. alterar `imap.results.com.br A` para o IP que servirá IMAP no corte

Opção mais consistente com o desenho atual:

- `mx1.results.com.br A 10.10.2.3`
- `mx2.results.com.br A 10.10.2.23`
- `imap.results.com.br A 10.10.2.3`

Se houver necessidade de redundância de cliente IMAP em outro IP, isso deve ser decidido antes da troca de `imap.results.com.br`.

## Sequência recomendada de corte

1. parar ou desanunciar o mail legado em `10.10.2.3`
2. parar ou desanunciar o secundário legado em `10.10.2.23`
3. adicionar `10.10.2.3` e `10.10.2.23` no host novo `10.10.2.30`
4. reaplicar a stack mail com [/.env.remote-10.10.2.30-mail](/opt/results/infra/.env.remote-10.10.2.30-mail)
5. validar SMTP/Submission/SMTPS em `10.10.2.3` e `10.10.2.23`
6. validar IMAP/POP3/ManageSieve no IP definido para clientes
7. ajustar `mx1.results.com.br`, `mx2.results.com.br` e `imap.results.com.br` no DNS autoritativo

## Observação importante

Nao aplicar `10.10.2.3` e `10.10.2.23` simultaneamente no host novo enquanto esses IPs ainda estiverem ativos nos servidores antigos. Isso causará conflito de ARP e comportamento imprevisível de rede.

## Execucao operacional

Para a etapa de assuncao dos IPs no host novo `10.10.2.30`, use o runbook [MAIL_IP_CUTOVER_10.10.2.30.md](MAIL_IP_CUTOVER_10.10.2.30.md) e o script [scripts/mail-cutover-10.10.2.30.sh](../scripts/mail-cutover-10.10.2.30.sh).