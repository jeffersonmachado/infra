# Infra Results

Documentacao da infraestrutura geral mantida em `/opt/results/infra`.

## Stacks

### Banco de dados
- MariaDB Galera Cluster
- ProxySQL
- Replicacao auxiliar

### DNS
- PowerDNS + dnsdist
- DNS interno

### Email
- Postfix
- Dovecot
- Rspamd
- LDAP auxiliar

### Web
- Apache
- Joomla legado

### VPN
- SoftEther/rvpn e scripts operacionais

## Projeto separado

Toda a stack `r-observe` foi movida para `/opt/results/r-observe`, incluindo:

- `docker-compose.observe*.yml`
- `.env.observe.example`
- `observe/`
- `r-observe/`
- `scripts/observe/`
- `scripts/discovery/`
- testes E2E e dashboards estaticos
- documentacao de `docs/observe/` e `docs/r-observe-discovery/`

Pontos de entrada da documentacao separada:

- `/opt/results/r-observe/README.md`
- `/opt/results/r-observe/docs/README.md`

## Referencias principais

- `MAIL_MIGRATION_10.10.2.2.md`
- `MAIL_DNS_CUTOVER_10.10.2.15.md`
- `MAIL_IP_CUTOVER_10.10.2.30.md`
- `MAIL_LEGACY_IP_DEANNOUNCE_CHECKLIST.md`
- `MIGRATION_MAP_10.10.2.55.md`
- `INTERNAL_DNS.md`
- `session-2026-06-04.md` — registro historico compartilhado de uma sessao
  anterior a separacao do `r-observe`
