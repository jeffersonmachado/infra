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

Documentacao operacional do observer:

- `/opt/results/r-observe/README.md`
- `/opt/results/r-observe/docs/README.md`

## Documentacao principal

- `docs/README.md`
- `docs/MAIL_MIGRATION_10.10.2.2.md`
- `docs/MAIL_DNS_CUTOVER_10.10.2.15.md`
- `docs/MAIL_IP_CUTOVER_10.10.2.30.md`
- `docs/MAIL_LEGACY_IP_DEANNOUNCE_CHECKLIST.md`
- `docs/MIGRATION_MAP_10.10.2.55.md`
