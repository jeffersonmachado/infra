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