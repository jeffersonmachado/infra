# Apache seguro em Docker

Esta stack replica o comportamento HTTP publicamente observável do host `10.10.2.55` com uma base atualizada e endurecida:

- Apache `2.4` atual em container.
- TLS com Let's Encrypt via `mod_md`.
- Redirecionamento automático de HTTP para HTTPS.
- Edge multi-vhost com reverse proxy por domínio e por path.
- `TRACE` desabilitado.
- `server-status` restrito a acesso local.
- Conteúdo sincronizado por `lsyncd` para um volume dedicado.
- Persistência separada para certificados ACME.

## O que foi identificado no host 10.10.2.55

- `Apache/2.4.6 (CentOS)` com `OpenSSL/1.0.2k-fips` e `PHP/7.2.34`.
- Porta `80/tcp` aberta.
- Porta `443/tcp` indisponível.
- Página raiz respondendo `200 OK` com conteúdo estático mínimo.
- Diretório `/icons/` com indexação pública.
- Métodos `GET`, `HEAD`, `POST`, `OPTIONS` e `TRACE` expostos.

## Estrutura

- `docker-compose.yml`: sobe Apache e `lsyncd`.
- `docker-compose.mail.yml`: sobe a nova stack de e-mail em containers separados.
- `subdomain-sync/`: sincroniza subdomínios dinâmicos via MySQL para arquivos de vhost runtime.
- `apache/`: imagem customizada com `mod_md`, TLS, hardening e templates de vhost.
- `joomla/`: backend PHP/Apache preparado para hospedar o Joomla legado de `results.com.br`.
- `lsyncd/`: sincroniza o conteúdo da origem para o volume servido.
- `content/`: origem padrão inicial para conteúdo estático.
- `joomla-site/`: cópia sincronizada dos arquivos do Joomla legado.
- `mail/`: imagens e templates da nova stack de e-mail.

## Versionamento seguro

O repositório foi preparado para não publicar segredos locais por padrão.

- `.env` e arquivos `.env.*` ficam ignorados, com exceção de `.env.example`.
- `joomla-site/configuration.php` fica ignorado porque contém credenciais e segredos do Joomla legado.

Fluxo recomendado:

1. mantenha valores reais apenas em arquivos `.env` locais ou no host remoto;
2. use `.env.example` como contrato de variáveis obrigatórias;
3. se precisar versionar configuração adicional do Joomla, faça isso fora de `configuration.php` ou com placeholders sem segredo.

Para validar rapidamente antes de um commit:

```bash
git status --short
git check-ignore -v .env .env.remote-10.10.2.30 .env.remote-10.10.2.30-ip60 joomla-site/configuration.php
```

## Migração do servidor de e-mail 10.10.2.2

O repositório também passou a conter uma stack própria para migrar o host de e-mail `10.10.2.2` seguindo o mesmo padrão operacional adotado no HTTP: versionado, containerizado, com deploy remoto por SSH e componentes separados.

Desenho atual da stack nova:

- `postfix`: SMTP, Submission e SMTPS
- `postfix-mx2`: segunda instância SMTP para `mx2.results.com.br`
- `dovecot`: IMAP, POP3, LMTP e Sieve
- `rspamd` + `redis`: antispam moderno com classificador Bayes e rede neural
- `clamav`: antivírus de anexos integrado ao fluxo do `Rspamd`
- `ldap`: suporte adicional de autenticação em container separado

Compatibilidade com o legado:

- lê `mailbox`, `alias` e `domain` do MySQL remoto
- preserva `Maildir` como formato de armazenamento
- aceita manter o diretório de caixas sincronizado no host novo
- já nasce com defaults alinhados ao LDAP observado no legado (`dc=results,dc=com,dc=br`), mantendo a senha fora do Git

Arquivo de ambiente base:

- `.env.mail.example`

Notas operacionais:

- as credenciais MySQL da stack de mail seguem o mesmo padrão do banco `results`
- a senha de bind LDAP foi confirmada no servidor legado, mas deve ser preenchida apenas no arquivo de ambiente remoto
- o antispam por IA usa o módulo `neural` nativo do `Rspamd`, com aprendizado persistido em `Redis`
- mensagens classificadas pelo `Rspamd` com ação `add header` agora são entregues pelo LMTP com headers padrão (`X-Spam`/`X-Spam-Status`) e passam por um Sieve global `sieve_after` do `Dovecot` que faz `fileinto "Spam"`; nos testes desta stack, filtros pessoais do Roundcube/ManageSieve nao conseguiram sobrepor esse desvio global, mesmo com `stop`
- o Roundcube agora expõe o plugin `managesieve` conectado a `imap.results.com.br:4190`, com um script inicial de exemplo em [joomla-site/webmail/config/managesieve-default.sieve](/opt/results/infra/joomla-site/webmail/config/managesieve-default.sieve); ele serve para filtros pessoais de organizacao, mas nao para whitelists que precisem vencer o `sieve_after` global de Spam
- whitelists que precisem vencer a marcacao global devem ser mantidas no `Rspamd`, em [mail/rspamd/local.d/local_from_allowlist.map](/opt/results/infra/mail/rspamd/local.d/local_from_allowlist.map) para remetentes exatos e [mail/rspamd/local.d/local_from_domain_allowlist.map](/opt/results/infra/mail/rspamd/local.d/local_from_domain_allowlist.map) para dominios; esses mapas sao aplicados por [mail/rspamd/local.d/multimap.conf](/opt/results/infra/mail/rspamd/local.d/multimap.conf) com score `-20.0`, preservando ainda outras verificacoes como antivirus
- a allowlist inicial desta stack inclui `pesquisa@bradescosegurospesquisa.com.br`, para evitar que a campanha legitima do Bradesco/Medallia seja empurrada para `Spam` pelo endurecimento local
- campanhas recorrentes que escapem ao score heurístico podem ser bloqueadas localmente pelo `Rspamd` em [mail/rspamd/local.d/composites.conf](/opt/results/infra/mail/rspamd/local.d/composites.conf), combinando símbolos que já apareceram no log real da campanha para forçar rejeição nas próximas ocorrências; além da composite principal e da fallback com `PHP mailer`, `FORGED_SENDER`, `FROM_NEQ_ENVFROM`, autenticação parcial SPF ou DKIM e `DMARC_NA`, há cobertura específica para remetentes em `RBL_SEM` com `RCVD_COUNT_ZERO`, para campanhas com `DBL_SPAM`, envelope VERP divergente e preheader invisível, para blasts autenticados com `Reply-To` fora do domínio de origem, `List-Unsubscribe` e cabeçalhos de ESP como `X-AntiAbuse`, `X-Authenticated-Sender` e `X-Get-Message-Sender-Via`, e para golpes no formato "Google Calendar" com cobrança falsa, `MISSING_TO`, `Reply-To`, corpo base64 e host de `From` sem `A` ou `MX`; nessa última cobertura, a composite `LOCAL_CALENDAR_ABUSE_BILLING` soma `8.0` pontos tanto quando a mensagem chega com `R_DKIM_ALLOW` quanto quando um replay local cair em `R_DKIM_REJECT`, e a `LOCAL_CALENDAR_ABUSE_BILLING_FALLBACK` acrescenta `4.5` pontos com base no fingerprint estável do exemplar real (`MISSING_TO`, `Reply-To` igual ao `From`, corpo base64, partes invisíveis, `FROM_EQ_ENVFROM` e ausência de `X-Mailer/User-Agent`), para manter o caso acima de `add header` mesmo sem apoio de `DBL_SPAM` ou `URIBL_BLACK`; quando os dois últimos cabeçalhos de ESP não forem reconhecidos pelo parser, a fallback `LOCAL_AUTH_SPAM_CAMPAIGN_REPLYTO_TRACKING_FALLBACK` mantém a rejeição com base nos demais sinais estáveis da campanha
- para uma postura mais agressiva sem rejeitar no SMTP, a composite `LOCAL_AUTH_MARKETING_AGGRESSIVE` soma `8.0` pontos em campanhas autenticadas com `List-Unsubscribe`, envelope VERP divergente do `From`, histórico de entrega anterior (`PREVIOUSLY_DELIVERED`) e autenticação SPF ou DKIM; na prática, esse perfil passa a cair em `add header` e ser movido para `Spam` pelo Sieve global, com risco maior de falso positivo em newsletters legítimas
- quando o webmail legado gravou preferencias de pastas especiais como `INBOX.Sent`, `INBOX.Spam`, `INBOX.Trash` e `INBOX.Drafts`, normalize essas preferencias e as subscriptions IMAP com [scripts/normalize-roundcube-special-folders.sh](/opt/results/infra/scripts/normalize-roundcube-special-folders.sh); na stack atual do Dovecot, as mailboxes corretas sao `Sent`, `Spam`, `Trash` e `Drafts`
- o Postfix aplica limites básicos por cliente para reduzir abuso e rajadas de conexões
- o Postfix também pode aplicar `postscreen` com DNSBL no SMTP público do `mx1`/`mx2`

Proteção de host (fora dos containers):

- o script [scripts/harden-remote-host.sh](/opt/results/infra/scripts/harden-remote-host.sh) instala `fail2ban` no host Alpine remoto
- o script cria um jail para `sshd` e outro para falhas SASL do Postfix a partir dos logs JSON do Docker
- o mesmo script cria regras idempotentes na chain `RESULTS-RATE-LIMIT`, ligada em `INPUT` e `DOCKER-USER`, para limitar rajadas por IP em `80/443` e `25/465/587`
- como este host usa `docker-proxy` para portas publicadas, o gancho em `INPUT` e necessario para que o rate limit atue de fato sobre `80/443` e `25/465/587`
- os limites padrao atuais do host sao `120` conexoes novas/minuto por IP em `80/443` e `25` conexoes novas/minuto por IP em `25/465/587`, com override por `HTTP_LIMIT_PER_MINUTE` e `SMTP_LIMIT_PER_MINUTE`
- para ajuste sem editar script, use um arquivo de ambiente como [/.env.host-security.example](/opt/results/infra/.env.host-security.example) e, neste host, o operacional [/.env.remote-10.10.2.30-host-security](/opt/results/infra/.env.remote-10.10.2.30-host-security)

Documento de mapeamento do legado:

- [MAIL_MIGRATION_10.10.2.2.md](MAIL_MIGRATION_10.10.2.2.md)
- [MAIL_DNS_CUTOVER_10.10.2.15.md](MAIL_DNS_CUTOVER_10.10.2.15.md)

Deploy remoto da stack de e-mail:

```bash
cd /opt/results/infra
export DEPLOY_ENV_FILE=.env.remote-10.10.2.30-mail
export DEPLOY_HOST=10.10.2.30
export DEPLOY_USER=root
export DEPLOY_PATH=/opt/results/infra

npm run deploy:remote:ssh:mail
```

No deploy da stack de mail, o script reinicia o `fail2ban` no host remoto depois do `docker compose up -d --build`. Isso evita que o jail `results-postfix-auth` continue preso ao JSON log do container antigo depois de um recreate do `postfix`.

Verificacao operacional no host `10.10.2.30`:

```bash
cd /opt/results/infra
export SSHPASS='***'
npm run host:security:status:remote
```

O script usado por esse comando e [scripts/check-remote-host-security.sh](/opt/results/infra/scripts/check-remote-host-security.sh).

Esse healthcheck unico agora tambem inclui:

- disparos de composites locais com prefixo `LOCAL_AUTH_SPAM_CAMPAIGN` nos logs do `results-mail-rspamd`
- rejeicoes correlatas nos logs do `results-mail-postfix`

Para verificar disparos das composites locais do `Rspamd` e rejeicoes correlatas no `Postfix`:

```bash
cd /opt/results/infra
export SSHPASS='***'
npm run mail:campaigns:status:remote
```

O script usado por esse comando e [scripts/check-remote-mail-campaigns.sh](/opt/results/infra/scripts/check-remote-mail-campaigns.sh). Ele busca por qualquer composite local com prefixo `LOCAL_AUTH_SPAM_CAMPAIGN` nos logs do `results-mail-rspamd` e por rejeicoes correlatas no `results-mail-postfix`. Se precisar ampliar a janela, sobrescreva `RSPAMD_LOG_WINDOW` e `RSPAMD_LOG_TAIL`.

Para reaplicar o hardening com o arquivo operacional deste host:

```bash
cd /opt/results/infra
export SSHPASS='***'
npm run host:harden:remote
```

Por padrao, esse comando remoto carrega [/.env.remote-10.10.2.30-host-security](/opt/results/infra/.env.remote-10.10.2.30-host-security). Se precisar apontar outro arquivo, sobrescreva `HOST_SECURITY_ENV_FILE`.

```bash
# fail2ban
ssh root@10.10.2.30 'fail2ban-client status && echo --- && fail2ban-client status results-postfix-auth'

# regras e contadores do rate limit
ssh root@10.10.2.30 'iptables -L INPUT -n -v | head -n 20 && echo --- && iptables -L RESULTS-RATE-LIMIT -n -v'

# ultimas falhas SASL visiveis para o jail do Postfix
ssh root@10.10.2.30 'docker logs --tail 80 results-mail-postfix 2>&1 | grep "SASL PLAIN authentication failed" | tail -n 20'

# regravar firewall/fail2ban do host, se necessario
ssh root@10.10.2.30 'cd /opt/results/infra && ./scripts/harden-remote-host.sh apply'
```

Leitura rapida esperada:

- `fail2ban-client status results-postfix-auth` deve mostrar contadores em `Currently failed` ou `Total failed` quando houver abuso SMTP e listar IP banido em `Banned IP list` quando o limiar for atingido
- `iptables -L RESULTS-RATE-LIMIT -n -v` deve acumular pacotes em `DROP` para `25,465,587` e `80,443` durante burst externo acima do limiar
- como o host usa `docker-proxy`, os contadores relevantes para portas publicadas aparecem primeiro em `INPUT`, nao apenas em `DOCKER-USER`

Sincronizacao completa do spool legado (`/gv`) para a stack nova:

```bash
cd /opt/results/infra
export DEPLOY_HOST=10.10.2.30
export DEPLOY_USER=root
export DEPLOY_PATH=/opt/results/infra
export DEPLOY_SSH_PASSWORD='***'
export LEGACY_HOST=10.10.2.2
export LEGACY_USER=root
export LEGACY_SSH_PASSWORD='***'

# valida conectividade, espaco e acesso ao spool legado
npm run sync:maildata:legacy:precheck

# copia toda a arvore /gv/ do legado para o volume Docker maildata no host novo
npm run sync:maildata:legacy
```

Notas do sync legado:

- a origem padrao e `/gv/` no host legado `10.10.2.2`
- o destino padrao e o mountpoint real do volume Docker `infra-mail_maildata` no host novo
- o sync resolve esse mountpoint via `docker volume inspect`, mas aceita `TARGET_MAIL_ROOT` para override manual
- o sync usa `rsync` com staging local por mailbox em `/tmp/results-mail-sync-staging`
- se a conexao cair, execute o mesmo comando novamente para retomar a partir do que ja foi baixado/enviado
- use `./scripts/sync-maildata-from-legacy.sh sync --dry-run` para simular antes

Comandos manuais no host remoto devem sempre usar o arquivo de ambiente especifico de cada stack.

Exemplos:

```bash
# HTTP
docker compose --env-file .env.remote-10.10.2.30-ip60 -f docker-compose.yml --project-name infra-httpd ps

# mail
docker compose --env-file .env.remote-10.10.2.30-mail -f docker-compose.mail.yml --project-name infra-mail ps
```

Nao use um `.env` compartilhado no host para alternar entre HTTP e mail. O deploy remoto agora preserva os arquivos `.env.remote-*` e executa cada `docker compose` com `--env-file`, evitando que o ultimo deploy sobrescreva o contexto operacional da outra stack.

No corte final do mail, os binds esperados são:

- `mx1` em `10.10.2.3`
- `mx2` em `10.10.2.23`

Assim, a stack mantém dois containers `Postfix` distintos sem conflito de portas, reproduzindo os IPs históricos do serviço de e-mail.

Enquanto `10.10.2.3` e `10.10.2.23` ainda estiverem ativos em outros hosts, esses binds não devem ser aplicados no `10.10.2.30`, porque haverá conflito de ARP e de publicação de portas. O rebind desses IPs para o host novo é etapa de corte.

A stack de mail agora reaproveita o certificado Let's Encrypt que o Apache ja mantem em `mod_md`, sem um segundo fluxo `http-01` separado.

Desenho atual dos certificados do mail:

- `mail-certs-bootstrap`: gera um certificado autoassinado curto apenas para bootstrap seguro da stack
- `secure-httpd`: mantem o certificado SAN valido em `infra-httpd_md-data`
- `mail-certbot`: sincroniza `pubcert.pem` e `privkey.pem` de `mx1.results.com.br` para o volume `mail-certs`
- `postfix`, `postfix-mx2` e `dovecot`: consomem o volume `mail-certs` e recarregam quando o `.cert-version` muda

Variaveis relevantes:

- `MAIL_IMAP_HOSTNAME`
- `MAIL_ACME_DOMAINS`
- `MAIL_ACME_RENEW_INTERVAL`
- `HTTPD_MD_VOLUME`

Requisito operacional para o certificado do mail:

- o Apache do edge precisa continuar emitindo e renovando o certificado SAN de `mx1.results.com.br`, `mx2.results.com.br` e `imap.results.com.br`
- o volume externo `infra-httpd_md-data` precisa estar disponivel para a stack de mail

Testes dos serviços da stack de e-mail:

```bash
cd /opt/results/infra

# smoke tests de rede/protocolo no host alvo
./scripts/test-mail-services.sh --host 10.10.2.30 \
	--ldap-uri ldap://srvldap0.results.intranet \
	--ldap-base-dn 'dc=results,dc=com,dc=br' \
	--ldap-bind-dn 'cn=administrador,dc=results,dc=com,dc=br' \
	--ldap-bind-password '***' \
	--mysql-host 10.10.2.99 \
	--mysql-db results \
	--mysql-user resultsdba \
	--mysql-password '***'

# mesma validação com checagem adicional do docker compose local
TEST_COMPOSE=true ./scripts/test-mail-services.sh --host 127.0.0.1
```

Cobertura do script:

- SMTP `25`
- Submission `587` com `STARTTLS`
- SMTPS `465`
- IMAP `143`
- IMAPS `993`
- POP3 `110`
- POP3S `995`
- ManageSieve `4190`
- LDAP por `ldapsearch` ou TCP
- MySQL do backend `results`
- `docker compose ps` quando solicitado

## Vhosts iniciais modelados

Esta primeira versão já sobe o edge com os domínios e rotas mais importantes do corte inicial:

- `results.com.br` e `www.results.com.br`
- `colaboracao.results.com.br`
- `olimpicshape.com.br`
- `play.results.com.br`
- `play-dev.results.com.br`
- `ripabx.results.com.br`
- `rvpn.results.com.br` opcional

As rotas de `results.com.br` já incluem:

- `/n8n`
- `/r-agent2`
- `/aprende-ai`
- `/oly-fit-balance`
- `/oly-fit-balance-admin`
- `/suporte`
- `/egroupware`
- `/colaboracao`
- `/barcode`
- `/valid`

Domínios opcionais como `colaboracao.results.com.br`, `play.results.com.br`, `olimpicshape.com.br`, `play-dev.results.com.br` e `rvpn.results.com.br` podem ser temporariamente excluídos da emissão ACME deixando suas variáveis `*_SERVER_NAME` vazias no arquivo `.env` remoto.

Da mesma forma, `rvpn.results.com.br` pode ser ativado quando necessário com `RVPN_SERVER_NAME=rvpn.results.com.br`, apontando para o backend HTTPS do SoftEther via `RVPN_UPSTREAM_HOST` e `RVPN_UPSTREAM_PORT`.

## Subdomínios dinâmicos via MySQL

Esta stack agora suporta criação de subdomínios sem reiniciar container e sem reiniciar o Apache.

Desenho:

- um sidecar `subdomain-sync` lê a tabela MySQL em `10.10.2.99`;
- ele gera um arquivo `90-dynamic-generated.conf` em `conf/runtime/`;
- quando detecta mudança, ele cria um sinal de reload;
- o container Apache recebe esse sinal e executa `httpd -k graceful`.

Com isso, novos subdomínios entram no Apache sem restart do container e sem derrubar conexões ativas.

Variáveis usadas:

- `MYSQL_HOST`
- `MYSQL_PORT`
- `MYSQL_DATABASE`
- `MYSQL_USER`
- `MYSQL_PASSWORD`
- `MYSQL_SSL`
- `DYNAMIC_VHOSTS_TABLE`
- `DYNAMIC_SYNC_INTERVAL`

Se o MySQL remoto estiver com certificado TLS expirado ou inválido, defina `MYSQL_SSL=disable` no `.env` remoto para forçar o cliente a usar conexão sem SSL. Para bancos com cadeia válida, mantenha `MYSQL_SSL=preferred`.

Tabela padrão esperada no MySQL:

- `apache_vhosts`

Arquivo de schema de exemplo:

- `subdomain-sync/schema.sql`

Fluxo operacional:

1. inserir ou atualizar o subdomínio no MySQL;
2. aguardar o `subdomain-sync` sincronizar;
3. o Apache recarrega com `graceful`;
4. o novo subdomínio passa a responder sem reinício.

## Preparação no host 10.10.2.30

1. Garanta que o DNS do domínio público aponte para `10.10.2.30`.
2. Libere `80/tcp` e `443/tcp` até o host Docker.
3. Copie `.env.example` para `.env` e ajuste os valores.
4. Se o conteúdo vier de outro diretório do host, ajuste `CONTENT_SOURCE_PATH`.

Exemplo de `.env`:

```env
SERVER_NAME=results.com.br
RESULTS_SERVER_NAME=results.com.br
RESULTS_SERVER_ALIAS=www.results.com.br
COLABORACAO_SERVER_NAME=colaboracao.results.com.br
OLIMPICSHAPE_SERVER_NAME=olimpicshape.com.br
PLAY_SERVER_NAME=play.results.com.br
PLAY_DEV_SERVER_NAME=play-dev.results.com.br
RIPABX_SERVER_NAME=ripabx.results.com.br
ADMIN_EMAIL=infra@results.com.br
CONTENT_SOURCE_PATH=/srv/httpd-content
HTTP_PORT=80
HTTPS_PORT=443
ACME_CA_URL=https://acme-v02.api.letsencrypt.org/directory
UPSTREAM_HOST_30=10.10.2.30
UPSTREAM_HOST_22=10.10.2.22
UPSTREAM_HOST_7=10.10.2.7
RIPABX_INTERNAL_HOST=ripabx.results.intranet
LSYNC_DELAY=5
LSYNC_DELETE=true
```

O arquivo `SERVER_NAME` foi mantido por compatibilidade, mas o desenho atual usa os nomes específicos de cada vhost.

## Backend Joomla do domínio principal

O legado serve `results.com.br` a partir de um Joomla em `/var/www/html/results`. Para reproduzir isso no ambiente novo, a stack agora suporta um backend dedicado `joomla`.

Variáveis relevantes:

- `JOOMLA_SOURCE_PATH`
- `JOOMLA_LSYNC_DELAY`
- `JOOMLA_LSYNC_DELETE`
- `JOOMLA_DB_HOST`
- `JOOMLA_DB_NAME`
- `JOOMLA_DB_USER`
- `JOOMLA_DB_PASSWORD`
- `RESULTS_ROOT_BACKEND_SCHEME`
- `RESULTS_ROOT_BACKEND_HOST`
- `RESULTS_ROOT_BACKEND_PORT`
- `RESULTS_ROOT_BACKEND_PATH`
- `RESULTS_ROOT_BACKEND_SSL_INSECURE`

Quando `RESULTS_ROOT_BACKEND_HOST` estiver preenchido, a raiz `/` do vhost `results.com.br` passa a ser encaminhada para esse backend, preservando as rotas por path já existentes.

Os arquivos do Joomla não precisam mais ser montados diretamente no container PHP. A stack agora usa um `joomla-lsyncd` dedicado para sincronizar `JOOMLA_SOURCE_PATH` para um volume Docker consumido pelo serviço `joomla`, no mesmo estilo operacional do legado.

Para performance sem comprometer persistência, o backend Joomla usa apenas `cache` em memória (`tmpfs`) e mantém `logs` em volume persistente. O tamanho do `tmpfs` de cache pode ser ajustado com `JOOMLA_CACHE_TMPFS_SIZE`.

Para manter `./joomla-site` atualizado no host `10.10.2.30`, a origem correta é o `lsyncd` do servidor `10.10.2.7`, que já é o autoritativo do Joomla legado.

Arquivo de exemplo para o servidor de origem:

[lsyncd/lsyncd_joomla_from_10.10.2.7.conf.lua.example](/opt/results/infra/lsyncd/lsyncd_joomla_from_10.10.2.7.conf.lua.example)

Fluxo recomendado:

1. no `10.10.2.7`, adicionar o `10.10.2.30` como novo destino do `lsyncd` para `/opt/results/infra/joomla-site/`;
2. no `10.10.2.30`, manter `JOOMLA_SOURCE_PATH=./joomla-site`;
3. deixar o serviço `joomla-lsyncd` da stack copiar esse diretório para o volume interno do container Joomla.

Para isso funcionar de forma não interativa, o `10.10.2.7` precisa ter acesso SSH por chave ao `10.10.2.30`.

No host `10.10.2.30`, a porta `443` já está ocupada pelo container de VPN (`rvpn`). Nesse cenário, use um arquivo dedicado como `.env.remote-10.10.2.30` com `HTTPS_PORT=4443` para conseguir publicar o Apache sem desligar a VPN. A limitação é que o acesso HTTPS ficará em porta não padrão até a porta `443` ser liberada.

## Melhor forma de usar o IP 10.10.2.60 neste cenário

O servidor legado usa `10.10.2.60` como IP secundário no mesmo `eth0`. No host novo `10.10.2.30`, a melhor estratégia é repetir esse desenho:

- adicionar `10.10.2.60/24` como IP secundário em `eth0`;
- manter o `rvpn` preso em `10.10.2.30`;
- publicar o edge HTTP/HTTPS em `10.10.2.60`.

Isso é melhor do que `macvlan` ou outra rede Docker dedicada porque:

- replica o padrão já usado no legado;
- simplifica troubleshooting e roteamento;
- permite separar `443` por IP sem mudar a topologia L2/L3.

### Host Alpine: persistência do IP secundário

No host `10.10.2.30`, o sistema é Alpine e usa `/etc/network/interfaces`.

Teste imediato, sem reinício:

```bash
ip addr add 10.10.2.60/24 dev eth0
```

Persistência recomendada em `/etc/network/interfaces`:

```conf
auto lo
iface lo inet loopback

auto eth0
iface eth0 inet static
		address 10.10.2.30
		netmask 255.255.255.0
		gateway 10.10.2.254
		up ip addr add 10.10.2.60/24 dev eth0 || true
		down ip addr del 10.10.2.60/24 dev eth0 || true
```

### rvpn: bind explícito em 10.10.2.30

Hoje o `rvpn` vem de `/root/vpn/docker-compose.yml` e publica `443` em `0.0.0.0`, o que bloqueia todos os IPs do host. O correto é mudar para bind explícito em `10.10.2.30`:

```yaml
ports:
	- "10.10.2.30:443:443/tcp"
	- "10.10.2.30:992:992/tcp"
	- "10.10.2.30:1194:1194/udp"
	- "10.10.2.30:5555:5555/tcp"
	- "10.10.2.30:500:500/udp"
	- "10.10.2.30:4500:4500/udp"
	- "10.10.2.30:1701:1701/udp"
```

### Edge HTTP/HTTPS: bind explícito em 10.10.2.60

A stack deste repositório já aceita bind por IP via `EDGE_BIND_IP`.

Para o corte final em `10.10.2.60`, use o arquivo `.env.remote-10.10.2.30-ip60`:

```env
EDGE_BIND_IP=10.10.2.60
HTTP_PORT=80
HTTPS_PORT=443
```

Com isso, o Apache publicará:

- `10.10.2.60:80 -> 8080`
- `10.10.2.60:443 -> 8443`

### Ordem segura de execução

1. adicionar `10.10.2.60` no host `10.10.2.30` sem reiniciar a rede;
2. alterar o `rvpn` para bind em `10.10.2.30` e recriar apenas esse stack;
3. subir esta stack com `.env.remote-10.10.2.30-ip60`;
4. validar com `curl --resolve dominio:443:10.10.2.60 ...`;
5. só então ajustar DNS público.

## Subida

```bash
docker compose up -d --build
```

Os templates de domínio ficam em `apache/vhosts-templates/` e são renderizados pelo entrypoint para `conf/vhosts/` dentro do container.

## Deploy remoto via npm + SSH

Se você quiser um fluxo no estilo `npm run deploy:remote:ssh:httpd`, agora o workspace já suporta isso por um script Bash no padrão operacional do `r-agent2`.

1. Crie `.env` a partir de `.env.example`.
2. Exporte as variáveis de conexão SSH.
3. Rode o script de deploy.

Exemplo:

```bash
cd /opt/results/infra
cp .env.example .env
export DEPLOY_USER=root
export DEPLOY_HOST=10.10.2.30
export DEPLOY_PATH=/opt/results/infra
export DEPLOY_ENV_FILE=.env.remote-10.10.2.30-ip60
npm run deploy:remote:ssh:httpd
```

Variáveis suportadas pelo deploy remoto:

- `DEPLOY_USER`: usuário SSH remoto. Obrigatória.
- `DEPLOY_HOST`: host remoto. Padrão `10.10.2.30`.
- `DEPLOY_PORT`: porta SSH. Padrão `22`.
- `DEPLOY_PATH`: diretório remoto onde a stack será sincronizada. Padrão `/opt/results/infra`.
- `DEPLOY_SSH_KEY`: caminho para chave privada SSH opcional.
- `DEPLOY_SSH_PASSWORD`: senha SSH opcional para uso com `sshpass`.
- `DEPLOY_SSH_PASSWORD_FILE`: arquivo com a senha SSH para uso com `sshpass`.
- `SSH_PASSWORD`: alias compatível com o padrão do `r-agent2`.
- `SSHPASS`: variável preferida pelo `sshpass -e`, também compatível com o padrão do `r-agent2`.
- `DEPLOY_USE_SSH_DIRECT`: se `true`, usa `ssh` direto sem `sshpass`.
- `DEPLOY_ENV_FILE`: arquivo de ambiente local a ser enviado junto no deploy. Padrão `.env`.
- `DEPLOY_PROJECT_NAME`: nome do projeto do `docker compose` no host remoto. Padrão `infra-httpd`.

Para validar sem executar o deploy remoto:

```bash
npm run deploy:remote:ssh:httpd:dry-run
```

O script efetivo usado pelo `npm` é [scripts/docker-deploy.sh](/opt/results/infra/scripts/docker-deploy.sh).

Quando `DEPLOY_PROJECT_NAME=infra-mail`, esse script tambem reinicia o `fail2ban` no host remoto ao final do deploy para que os jails que leem `/var/lib/docker/containers/*/*-json.log` acompanhem o ID atual dos containers recriados.

Quando `DEPLOY_PROJECT_NAME=infra-httpd`, esse script tambem endurece o fluxo de webmail:

- valida o checksum de [joomla-site/webmail/config/config.inc.php](/opt/results/infra/joomla-site/webmail/config/config.inc.php) no host remoto antes do `docker compose`
- valida o mesmo checksum no bind mount dentro do container `results-joomla` depois do recreate
- executa [scripts/normalize-roundcube-special-folders.sh](/opt/results/infra/scripts/normalize-roundcube-special-folders.sh) para alinhar preferencias do Roundcube e subscriptions do Dovecot com `Sent`/`Spam`/`Trash`/`Drafts`
- executa [scripts/test-webmail-login.sh](/opt/results/infra/scripts/test-webmail-login.sh) para validar o runtime IMAP/TLS efetivo do Roundcube dentro do container `results-joomla`, confirmar cookie/token da pagina de login e, se `WEBMAIL_TEST_USER` e `WEBMAIL_TEST_PASSWORD` estiverem definidos no ambiente de deploy, tambem executar login autenticado
- se o arquivo de ambiente da stack de mail estiver disponivel no host remoto, executa [scripts/test-webmail-temporary-auth.sh](/opt/results/infra/scripts/test-webmail-temporary-auth.sh), que cria um login temporario no MySQL e outro no LDAP real, cria o Maildir de ambos, valida o login SQL via Roundcube, valida o login LDAP via `doveadm auth test` com `localpart` e email completo, e remove tudo no `cleanup`

Para executar essa validacao manualmente no host remoto, depois de sincronizar o repositório:

```bash
cd /opt/results/infra
WEBMAIL_MAIL_ENV_FILE=.env.remote-10.10.2.30-mail sh ./scripts/test-webmail-temporary-auth.sh
```

Para um smoke test mais rapido, sem criar usuarios temporarios:

```bash
cd /opt/results/infra
sh ./scripts/test-webmail-login.sh
```

O script remoto também aceita `DEPLOY_ENV_FILE=.env.example`; nesse caso ele copia esse arquivo para `.env` no host remoto antes de executar o `docker compose`.

Se você preferir autenticação por senha, o deploy usa `sshpass -e` no mesmo padrão do `r-agent2`, aceitando `SSHPASS`, `SSH_PASSWORD` ou `DEPLOY_SSH_PASSWORD`.

## Validação

```bash
docker compose ps
curl -I http://127.0.0.1
curl -k -I https://127.0.0.1
docker compose logs -f apache
```

Se você estiver validando localmente com portas alternativas, prefira testar com `Host` compatível com `SERVER_NAME`, por exemplo:

```bash
curl -I -H 'Host: site.seudominio.com.br' http://127.0.0.1:8088/
curl -k -I -H 'Host: site.seudominio.com.br' https://127.0.0.1:8448/
```

## Migração inicial do conteúdo do servidor antigo

Se você tiver acesso SSH ao host antigo, faça uma carga inicial antes de ligar o sincronismo contínuo do seu fluxo atual:

```bash
rsync -avz --delete root@10.10.2.55:/var/www/html/ /srv/httpd-content/
```

Se o seu `lsync` atual já alimenta um diretório local no host `10.10.2.30`, basta apontar `CONTENT_SOURCE_PATH` para esse diretório e manter o container `lsyncd` desta stack como sincronizador interno para o volume do Apache.

## Diferenças de segurança em relação ao host antigo

- Remove Apache/PHP/OpenSSL legados do CentOS.
- Ativa HTTPS automaticamente com renovação de certificado.
- Desabilita `TRACE`.
- Reduz vazamento de banner com `ServerTokens Prod`.
- Adiciona cabeçalhos de segurança.
- Restringe `server-status`.
- Reduz a configuração duplicada do legado para templates versionados por vhost.
- Mantém WebSocket e reverse proxy no edge novo sem reusar os módulos legados desnecessários.

## Limitações conhecidas

- A emissão do Let's Encrypt só funciona quando `SERVER_NAME` resolve publicamente para `10.10.2.30`.
- Esta stack não replica `PHP 7.2`, porque nada no levantamento público indicou aplicação PHP ativa em produção. Se houver conteúdo PHP real fora da superfície observável, será melhor adicionar `php-fpm` separadamente em vez de reproduzir a versão obsoleta.