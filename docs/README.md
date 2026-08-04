# Infra Results

Documentacao da infraestrutura geral mantida em `/opt/results/infra`.

> **Regra**: ao identificar um IP desconhecido, SEMPRE consultar DNS reverso
> (`host <IP>`, `dig -x <IP>`) antes de supor qual servidor é. Nunca assumir.

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
- `RVPN_EDGE_SNI_443.md` — compartilhamento da porta 443 entre SoftEther e Apache via HAProxy SNI

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

## Limite de responsabilidade

A separacao do projeto nao removeu as integracoes de borda mantidas aqui.
Este repositorio ainda e responsavel por partes necessarias para publicar o
`r-observe` externamente:

- DNS publico e split-horizon de `r-observe.results.com.br`
- HAProxy SNI em `edge-sni/haproxy.cfg`
- Apache que termina TLS e aplica `ProxyPass` para subdominios
- `subdomain-sync`, quando o subdominio e servido por vhost dinamico
- `lsyncd`/`CONTENT_SOURCE_PATH`, quando houver conteudo sincronizado do projeto

Se `https://r-observe.results.com.br/` falhar, revisar primeiro:

1. resolucao DNS publica do host
2. DNAT `80/443` para `10.10.2.60`
3. vhost/redirect/proxy no Apache de borda
4. servicos e containers do projeto em `/opt/results/r-observe`

## Referencias principais

- `RESULTS_DATABASE_RECOVERY.md`
- `WEBMAIL_ENVIRONMENT_10.10.2.30.md` — Ambiente do webmail (atualizado 2026-06-11)
- `CONTAINER_IP_POLICY.md` — Politica: containers usam VIPs, nunca o IP do host (novo 2026-06-13)
- `NETWORK_CONSOLIDATION.md` — Consolidacao de redes Docker (novo 2026-06-11)
- `TROUBLESHOOTING_EMAIL.md` — Troubleshooting de email/webmail (novo 2026-06-11)
- `FILE_TO_DIRECTORY_PREVENTION.md` — Prevencao: arquivos viram diretorios (novo 2026-06-12)
- `MAIL_MIGRATION_10.10.2.2.md`
- `MAIL_DNS_CUTOVER_10.10.2.15.md`
- `MAIL_IP_CUTOVER_10.10.2.30.md`
- `MAIL_LEGACY_IP_DEANNOUNCE_CHECKLIST.md`
- `MIGRATION_MAP_10.10.2.55.md`
- `INTERNAL_DNS.md`
- `DNS_PRODUCTION_RUNBOOK.md`
- `DNS_AUTHORITY_FIX.md`
- `DNS_SPF_DMARC_BIMI_FIX_2026-06-09.md`
- `SERVER_INVENTORY.md`
- `session-2026-06-04.md` — registro historico compartilhado de uma sessao
  anterior a separacao do `r-observe`
- `session-2026-06-08-migration.md` — migracao final VMs → containers + correcoes pos-sessao
