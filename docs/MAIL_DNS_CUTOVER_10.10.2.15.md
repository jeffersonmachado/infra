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

Os nomes operacionais (`mx1`, `mx2`, `imap`, `smtp`) devem responder de forma
diferente conforme a origem da consulta — isso é split-horizon nativo via
**PowerDNS Views** (variante de zona `results.com.br..internal.`, ver
[dns-consolidated/zones/](../dns-consolidated/zones/)):

| Nome                   | Externo (zona pública) | Interno (variante `..internal`) |
|------------------------|------------------------|----------------------------------|
| `mx1.results.com.br`   | `201.6.110.53`         | `10.10.2.3`                      |
| `mx2.results.com.br`   | `201.6.110.53`         | `10.10.2.23`                     |
| `imap.results.com.br`  | `201.6.110.53`         | `10.10.2.3`                      |
| `smtp.results.com.br`  | `201.6.110.53`         | `10.10.2.3`                      |

- **Externo**: `201.6.110.53` é o roteador de borda, com NAT para `10.10.2.30`
  — já assim em [02-zone-data.sql](../dns-consolidated/mariadb/init/02-zone-data.sql)
  e replicado em [results.com.br.json](../dns-consolidated/zones/results.com.br.json).
- **Interno**: aponta direto para os IPs reais dos containers de mail
  (`10.10.2.3` = mx1, `10.10.2.23` = mx2), evitando hairpin via NAT — ver
  [results.com.br..internal.json](../dns-consolidated/zones/results.com.br..internal.json).

Não se escolhe um único valor de A record: o Views entrega a variante certa
automaticamente, com base no IP de origem da consulta (mapeamento em
[_networks.json](../dns-consolidated/zones/_networks.json): `10.0.0.0/8`,
`172.16.0.0/12`, `192.168.0.0/16`, `127.0.0.0/8` → view `internal`).

1. criar `mx2.results.com.br` (já presente em ambas as variantes da zona)
2. manter `mx1`, `imap`, `smtp` conforme a tabela acima (já presentes nos JSONs)
3. aplicar via API REST com [apply-zones-api.sh](../dns-consolidated/scripts/apply-zones-api.sh)
   e [apply-views-api.sh](../dns-consolidated/scripts/apply-views-api.sh) — não editar
   registros manualmente via `pdnsutil`/SQL (o backend agora é LMDB, populado via API)

Se houver necessidade de redundância de cliente IMAP em outro IP, isso deve ser decidido antes da troca de `imap.results.com.br`.

## Sequência recomendada de corte

1. parar ou desanunciar o mail legado em `10.10.2.3`
2. parar ou desanunciar o secundário legado em `10.10.2.23`
3. adicionar `10.10.2.3` e `10.10.2.23` no host novo `10.10.2.30`
4. reaplicar a stack mail com [/.env.remote-10.10.2.30-mail](/opt/results/infra/.env.remote-10.10.2.30-mail)
5. validar SMTP/Submission/SMTPS em `10.10.2.3` e `10.10.2.23`
6. validar IMAP/POP3/ManageSieve no IP definido para clientes
7. aplicar/conferir `mx1`, `mx2`, `imap` e `smtp` de `results.com.br` (ambas as
   variantes — pública e `..internal`) via `apply-zones-api.sh` /
   `apply-views-api.sh` (ver [dns-consolidated/zones/](../dns-consolidated/zones/))

## Observação importante

Nao aplicar `10.10.2.3` e `10.10.2.23` simultaneamente no host novo enquanto esses IPs ainda estiverem ativos nos servidores antigos. Isso causará conflito de ARP e comportamento imprevisível de rede.

## Execucao operacional

Para a etapa de assuncao dos IPs no host novo `10.10.2.30`, use o runbook [MAIL_IP_CUTOVER_10.10.2.30.md](MAIL_IP_CUTOVER_10.10.2.30.md) e o script [scripts/mail-cutover-10.10.2.30.sh](../scripts/mail-cutover-10.10.2.30.sh).