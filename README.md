# Infra Results

Repositorio dedicado a infraestrutura geral da Results.

## Diretorio operacional

O diretorio de trabalho e deploy deste repositorio e `/opt/results/infra`.

## Escopo atual

- Apache e Joomla
- Stack de mail
- DNS interno e consolidado
- VPN
- MariaDB Galera, ProxySQL e replicacao
- Scripts operacionais do host

## Projeto separado

Os artefatos do `r-observe` foram separados para `/opt/results/r-observe`.
Isso inclui compose, codigo da aplicacao, stack de observabilidade, testes E2E
e documentacao especifica do produto.

Mesmo apos a separacao, a publicacao externa do `r-observe` continua com
dependencias neste repositorio:

- DNS publico de `r-observe.results.com.br`
- roteamento SNI em `edge-sni/haproxy.cfg`
- Apache/`subdomain-sync` no host `10.10.2.60`
- sincronizacao de conteudo via `CONTENT_SOURCE_PATH`

Em outras palavras: aplicacao e deploy do produto ficam em
`/opt/results/r-observe`, mas borda e exposicao publica continuam em
`/opt/results/infra`.

Documentacao operacional do observer:

- `/opt/results/r-observe/README.md`
- `/opt/results/r-observe/docs/README.md`

## Documentacao principal

- `docs/README.md` — Indice da documentacao
- `docs/WEBMAIL_ENVIRONMENT_10.10.2.30.md` — Ambiente do webmail (atualizado 2026-06-11)
- `docs/NETWORK_CONSOLIDATION.md` — Consolidacao de redes Docker (novo 2026-06-11)
- `docs/TROUBLESHOOTING_EMAIL.md` — Troubleshooting de email/webmail (novo 2026-06-11)
- `docs/FILE_TO_DIRECTORY_PREVENTION.md` — Prevencao: arquivos viram diretorios (novo 2026-06-12)
- `docs/MAIL_MIGRATION_10.10.2.2.md`
- `docs/MAIL_DNS_CUTOVER_10.10.2.15.md`
- `docs/MAIL_IP_CUTOVER_10.10.2.30.md`
- `docs/MAIL_LEGACY_IP_DEANNOUNCE_CHECKLIST.md`
- `docs/MIGRATION_MAP_10.10.2.55.md`
- `docs/session-2026-06-08-migration.md` — Migracao VMs → containers + correcoes
- `docs/DNS_PRODUCTION_RUNBOOK.md` — Runbook de DNS
- `docs/DNS_AUTHORITY_FIX.md` — Correcao de autoridade DNS
- `docs/DNS_SPF_DMARC_BIMI_FIX_2026-06-09.md` — Correcoes SPF/DMARC/BIMI
- `docs/SERVER_INVENTORY.md` — Inventario de servidores

## Memoria do repositorio

- `memories/repo/postfix-ldap-issue.md` — Nota tecnica: LDAP no Postfix
- `memories/repo/notes.md` — Notas gerais
- `memories/repo/discovery-icinga-sync.md` — Icinga sync
