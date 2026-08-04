# AGENTS.md — Infra Results

Guia para agentes de IA que trabalham neste repositório. Assume-se que o
leitor não conhece o projeto.

## Visão geral

Repositório de **infraestrutura** (não é uma aplicação): define e opera, via
Docker Compose e scripts Bash, os serviços de produção da Results
(`results.com.br`). O diretório operacional e de deploy é
`/opt/results/infra`, tanto na estação de trabalho quanto no servidor de
produção.

- **Servidor principal de produção:** `10.10.2.30` (Alpine Linux + Docker),
  acessado via SSH como `root`.
- **Sem build de aplicação:** não há `pyproject.toml`, `Cargo.toml` nem código
  compilado. O `package.json` existe apenas como catálogo de scripts npm
  (atalhos para os scripts Bash) e pela dependência de dev `@playwright/test`.
- **Linguagem dos artefatos:** documentação, comentários e mensagens de
  scripts em **português (pt-BR)**. Manter esse idioma ao editar.
- **Projeto separado:** a stack `r-observe` (aplicação, observabilidade,
  testes E2E) foi movida para `/opt/results/r-observe`. Este repositório
  mantém apenas a **borda/exposição pública** dela: DNS de
  `r-observe.results.com.br`, SNI em `edge-sni/haproxy.cfg`, vhost no Apache
  e sincronização de conteúdo via `CONTENT_SOURCE_PATH`.

## Stacks e arquivos Compose

Cada stack é um arquivo Compose independente na raiz, com seu `.env`
correspondente (nunca versionado; usar os `*.example` como base):

| Arquivo | Stack | Env |
|---|---|---|
| `docker-compose.yml` | Web: Apache (`secure-httpd`), Joomla legado + Roundcube (`results-joomla`), `subdomain-sync` (vhosts dinâmicos via MySQL), 2× `lsyncd` | `.env` (base: `.env.example`) |
| `docker-compose.mail.yml` | Mail: Postfix MX1 + MX2, Dovecot, Rspamd, ClamAV, Redis, OpenLDAP, Certbot | `.env.mail` (base: `.env.mail.example`) |
| `docker-compose.mysql-galera.yml` | MariaDB Galera 10.11, 3 nós multi-master em `network_mode: host` | `.env.mysql-galera` |
| `docker-compose.mysql-cluster.yml` | ProxySQL + slave MariaDB (substitui Piranha LVS) | `.env.mysql-cluster` |
| `docker-compose.mysql-replication.yml` | Replicação master/slave (master 10.10.2.79) | `.env.mysql-replication` |
| `docker-compose.proxysql.yml` | ProxySQL standalone na frente do Galera | — |
| `docker-compose.vpn.yml` | SoftEther VPN (`rvpn`) | `.env.vpn` |
| `docker-compose.edge-sni.yml` | HAProxy SNI de borda (bind em 10.10.2.60) | — |
| `docker-compose.dns-internal.yml` | DNS interno (CoreDNS, VIPs 10.10.2.1/10.10.2.20) | — |
| `dns-consolidated/docker-compose.yml` | DNS público: PowerDNS auth (LMDB + Views) + recursor + dnsdist | `dns-consolidated/.env` |
| `galera/docker-compose.yml` | Cluster Galera **legado** (imagem 10.6, rede bridge). Produção está em `docker-compose.mysql-galera.yml` (ver `galera/README.md`) | — |
| `librenms/docker-compose.librenms.yml` | LibreNMS (monitoramento) | `.env.librenms` |

## Organização do código

- `apache/`, `joomla/`, `lsyncd/`, `subdomain-sync/` — Dockerfiles e
  entrypoints da stack web. `joomla-site/` é o **código-fonte do site Joomla
  legado** (vendored, com `libraries/vendor/`); sincronizado ao container via
  `joomla-lsyncd`. Não tratar como código deste projeto.
- `mail/` — configs dos serviços de email: `postfix/` (templates
  `main.cf`/`master.cf` + mapas MySQL/LDAP), `dovecot/` (templates + sieve),
  `rspamd/` (`local.d`/`override.d`), `ldap/`.
- `dns-consolidated/` — zonas DNS em JSON (`zones/`), configs renderizadas em
  `rendered/` (gitignored, contém segredos) e scripts de deploy/validação
  (`scripts/apply-zones-api.sh`, `validate.sh`, `cutover.sh`, `rollback.sh`).
- `dns-internal/`, `edge-sni/`, `vpn/`, `roundcube/`, `galera/`,
  `mysql-cluster/` — configs e compose das demais stacks.
- `scripts/` — automação operacional (deploy, hardening, testes, restores,
  sincronização de maildata). Todos rodam a partir da raiz do repo.
- `docs/` — documentação operacional; índice em `docs/README.md`. Políticas
  obrigatórias: `docs/CONTAINER_IP_POLICY.md`, `docs/NETWORK_CONSOLIDATION.md`,
  `docs/FILE_TO_DIRECTORY_PREVENTION.md`.
- `test-results/`, `tests/playwright-report/` — artefatos de testes antigos
  (gitignored); os testes E2E ativos estão em `/opt/results/r-observe`.
- `dist/` — artefatos de release (zips, manifestos; gitignored).
- `.github/workflows/` — existe, mas está **vazio** (sem CI configurado).

## Comandos de build, deploy e verificação

Não há build: as imagens Docker são construídas no host remoto durante o
deploy. Os atalhos npm **válidos** (apontam para scripts que existem):

```bash
# Deploy remoto via SSH (padrão: root@10.10.2.30, path /opt/results/infra)
npm run deploy:remote:ssh:httpd          # stack web (rsync + compose up remoto)
npm run deploy:remote:ssh:httpd:dry-run  # simula sem executar
npm run deploy:remote:ssh:mail           # stack mail
npm run deploy:remote:ssh:mail:dry-run

# Testes e verificações
npm run test:mail:services               # testa SMTP/IMAP/POP3/Sieve/LDAP/MySQL
npm run webmail:test:login               # login no Roundcube
npm run check:file-integrity             # arquivos que viraram diretórios (ver abaixo)
npm run host:security:status:remote      # status de hardening do host

# Operação de mail
npm run mail:user:create / mail:user:delete
npm run postfix:ip:list|check|add|block|remove|reload
```

Subida manual de qualquer stack (local ou no servidor):

```bash
docker compose -f docker-compose.mail.yml --env-file .env.mail up -d
docker compose --env-file .env up -d          # stack web
```

**Scripts npm quebrados/obsoletos:** `dev`, `observe:*`, `discovery:*`,
`zip:*`, `release:*` referenciam `r-observe/`, `docker-compose.observe.yml` e
`scripts/{observe,discovery,package,release}/`, que foram movidos para
`/opt/results/r-observe`. Não usar aqui.

## Convenções obrigatórias

### Política de IPs (docs/CONTAINER_IP_POLICY.md — em vigor)

- Nenhum container usa o IP primário do host (`10.10.2.30`) para bind ou como
  destino. Serviços usam **VIPs** em `eth0` (10.10.2.1, .3, .20, .23, .49,
  .60, .79, .89, .99), herdadas dos IPs legados. Única exceção: VPN
  (`10.10.2.30:5555`).
- Em `ports:`, sempre bind explícito na VIP (ex:
  `"${MAIL_BIND_IP:-10.10.2.3}:25:25"`), nunca `"25:25"` genérico em
  produção.
- Comunicação entre containers: apenas via DNS interno do Docker
  (`container_name`) na rede compartilhada `infra-shared` — nunca hardcodar
  IP do host. DNS dos containers: `127.0.0.11`.
- Registros DNS e healthchecks externos apontam para VIPs.

### Estrutura dos Compose

- Todos os serviços: `restart: unless-stopped`, `security_opt:
  no-new-privileges:true`, `healthcheck` definido, `tmpfs: /tmp` quando
  aplicável.
- Volumes nomeados com prefixo `infra_` (ex: `infra_maildata`,
  `infra_site-data`).
- Rede padrão compartilhada: `infra-shared` (criar com
  `docker network create infra-shared` se ausente).
- Logging `json-file` com rotação (`max-size: 50m`) nas stacks MySQL/DNS.
- Senhas e endpoints vêm de variáveis de ambiente com defaults no compose;
  segredos reais só nos `.env` locais (gitignored).

### configuration.php do Joomla e webmail/: EXCLUÍDOS do lsyncd

O `joomla-lsyncd` sincroniza `joomla-site/` → volume `joomla-site-data`,
**exceto** `configuration.php` e `webmail/`
(`JOOMLA_LSYNC_EXCLUDES=configuration.php,webmail` no env da stack web). O
exclude existe de propósito: ambos são gitignored (contêm segredos) e, sem
ele, o `lsyncd --delete` os apagaria do volume numa estação com clone limpo.
Regras:

- **Nunca editar esses arquivos dentro do container** (`results-joomla`) —
  mas também **não adianta editar só a fonte**: por causa do exclude,
  alterações em `joomla-site/configuration.php` ou `joomla-site/webmail/`
  **não propagam automaticamente**.
- Para alterar o `configuration.php` efetivo, editar a fonte e copiar
  manualmente ao volume:
  `docker cp joomla-site/configuration.php results-joomla:/var/www/html/results/configuration.php`
- O `webmail/config/config.inc.php` efetivo no volume é a versão com
  `getenv()` (igual a `roundcube/config.inc.php` deste repo); a cópia em
  `joomla-site/webmail/config/config.inc.php` é **legada e não é usada**.
  Endpoints IMAP/SMTP/Sieve do Roundcube vêm das envs `ROUNDCUBE_*` do
  serviço `joomla` no `docker-compose.yml` (nomes de container, ex:
  `ssl://results-mail-dovecot`), nunca de IPs ou `extra_hosts`.

### Footgun: arquivos que viram diretórios

Docker cria um **diretório** quando o source de um bind mount não existe no
host. Isso já quebrou deploys (ex: `mail-certbot-entrypoint.sh`). Antes de
cada deploy, rodar `./scripts/check-file-integrity.sh` (ou
`npm run check:file-integrity`). Detalhes e lista de arquivos protegidos em
`docs/FILE_TO_DIRECTORY_PREVENTION.md`.

### Estilo de scripts Bash

- `set -e` / `set -euo pipefail`, saída colorida com funções
  `info/warn/error/section`, suporte a `--dry-run` nos de deploy.
- Variáveis de ambiente com defaults via `${VAR:-default}`; caminhos
  resolvidos a partir da raiz do repo (`ROOT_DIR="$(cd "$(dirname
  "${BASH_SOURCE[0]}")/.." && pwd)"`).
- Acesso remoto: `ssh root@${DEPLOY_HOST:-10.10.2.30}` com
  `-o StrictHostKeyChecking=no`; alguns scripts aceitam `SSHPASS`/
  `SSH_PASSWORD`.

## Testes

Não há suíte de testes unitários nem CI neste repositório. A verificação é
**operacional**, via scripts:

- `scripts/test-mail-services.sh` — bateria de testes da stack de mail
  (portas, TLS, LDAP, MySQL); `npm run test:mail:services`.
- `scripts/test-webmail-login.sh`, `scripts/test-webmail-temporary-auth.sh` —
  login no webmail Roundcube.
- `scripts/check-file-integrity.sh` — integridade de bind mounts (pré-deploy).
- `scripts/validate-no-secrets.sh` — varredura de segredos (ver Segurança).
- `dns-consolidated/scripts/validate.sh` — validação de zonas/DNS.
- Healthchecks do Compose + verificações manuais documentadas nos cabeçalhos
  de cada arquivo compose e em `docs/`.
- Testes E2E (Playwright) pertencem ao projeto `/opt/results/r-observe`.

## Segurança

- **Segredos nunca versionados:** `.env`, `.env.*`, `.env.remote-*`,
  `joomla-site/configuration.php`, `joomla-site/webmail/config/config.inc.php`
  e `dns-consolidated/rendered/` estão no `.gitignore`. Apenas os
  `*.example` (sem valores reais) são commitados. Atenção: chaves DKIM em
  `mail/rspamd/*.key` são privadas e não devem ser commitadas.
- `./scripts/validate-no-secrets.sh` varre o repo por chaves privadas,
  `sshpass` com senha literal, segredos em `.env*` reais e strings de alta
  entropia; gera relatório em `dist/security-validation-report.json`. Rodar
  antes de commits/releases.
- Hardening do host: `scripts/harden-remote-host.sh` (aplica) e
  `scripts/check-remote-host-security.sh` (audita); config via
  `.env.host-security.example`.
- Containers rodam com `no-new-privileges:true`; mounts de config sensíveis
  são `:ro`.
- `scripts/check-remote-mail-campaigns.sh` e `prompts/anti-spam-management.md`
  dão o contexto de operação anti-spam (Rspamd/Postfix).
- Ao identificar um IP desconhecido na infra, **sempre** consultar DNS reverso
  (`host <IP>`, `dig -x <IP>`) antes de assumir qual servidor é (regra de
  `docs/README.md`).

## Documentação

- `README.md` — visão geral e escopo.
- `docs/README.md` — índice da documentação operacional.
- Runbooks relevantes: `docs/DNS_PRODUCTION_RUNBOOK.md`,
  `docs/TROUBLESHOOTING_EMAIL.md`, `docs/WEBMAIL_ENVIRONMENT_10.10.2.30.md`,
  `docs/SERVER_INVENTORY.md`, `docs/RESULTS_DATABASE_RECOVERY.md`,
  `galera/README.md`.
- Ao alterar comportamento, topologia ou convenções, atualizar o doc
  correspondente em `docs/` (e este `AGENTS.md`, se aplicável). Mudanças de
  comportamento operacional relevantes merecem um doc novo datado em `docs/`,
  seguindo o padrão existente.
