# Ambiente Correto do Webmail no `10.10.2.30`

Documento operacional do ambiente que deixou o webmail em funcionamento em
producao no servidor `10.10.2.30` (`mexico.results.intranet`).

Data de referencia da validacao: `2026-06-11` (atualizado).

URL validada:

- `https://results.com.br/webmail/`

Validacoes confirmadas:

- pagina de login do Roundcube carregando sem `DATABASE ERROR`
- handshake IMAPS valido a partir do container `results-joomla`
- login real no webmail validado com `jefferson / jcm@1970`
- ✅ envio de email funcional (SMTP 587 STARTTLS + AUTH)
- ✅ recebimento de email funcional (37.835 emails na inbox)
- ✅ caixa de entrada populada com emails reais

## 1. Objetivo

O webmail depende de quatro blocos trabalhando juntos:

1. edge HTTP/HTTPS publicando `/webmail/`
2. container Joomla servindo o Roundcube embutido
3. Dovecot autenticando e entregando IMAP/ManageSieve
4. MySQL fornecendo o banco `roundcubemail`

Se qualquer um desses blocos apontar para o destino errado, o sintoma muda:

- erro de banco na tela do Roundcube
- falha de resolucao DNS interna para `dovecot`/`postfix`
- cookie de sessao sem escopo correto em `/webmail/`
- login aceito no HTML, mas rejeitado no IMAP

## 2. Topologia correta

Fluxo HTTP:

```text
Internet
  -> 10.10.2.60:443
  -> secure-httpd
  -> ProxyPass /webmail/ -> http://joomla/webmail/
  -> results-joomla (alias de rede: joomla)
  -> Roundcube
```

Atencao: o container tem `container_name: results-joomla`, mas o Apache
referencia `joomla`. O alias de rede `joomla` e obrigatorio no
`docker-compose.yml` (ver `networks.default.aliases`). Sem ele, o Apache
retorna `500 Proxy Error - DNS lookup failure for: joomla`.

Fluxo IMAP/SMTP (atualizado 2026-06-11):

```text
Roundcube em results-joomla
  -> IMAPS: results-mail-dovecot:993 (via rede infra-shared)
  -> SMTP submission: results-mail-postfix:587 (via rede infra-shared)
  -> ManageSieve: results-mail-dovecot:4190 (via rede infra-shared)
```

Nota: Os containers usam a rede `infra-shared` para comunicacao direta.
O Roundcube usa `tls://` prefix com `verify_peer=false` para aceitar o
certificado auto-assinado do Dovecot/Postfix.

Fluxo banco do Roundcube:

```text
Roundcube em results-joomla
  -> MySQL roundcubemail
  -> host configurado: 10.10.2.79
```

Observacao importante:

- embora `10.10.2.99:3306` continue exposto como ProxySQL, o ambiente validado
  do webmail ficou funcional com `ROUNDCUBE_DB_HOST=10.10.2.79`
- para o webmail funcionar, o DNAT de `10.10.2.79:3306 -> 127.0.0.1:6033` nao
  pode existir no host, porque ele desviava o Roundcube para backends
  `read_only`

## 3. Containers e servicos necessarios

Estado observado no host `10.10.2.30` durante a validacao:

- `secure-httpd`: `healthy`
- `results-joomla`: `unhealthy` no healthcheck raiz do site, mas com
  `/webmail/` funcional
- `results-mail-dovecot`: ativo
- `results-mail-postfix`: `healthy`
- `results-mail-postfix-mx2`: `healthy`
- `proxysql-galera`: `healthy`
- `srvmysql0`: ativo
- `srvmysql1`: ativo
- `srvmysql2`: ativo

Observacao:

- o healthcheck do servico `joomla` usa `GET /` com `Host: www.results.com.br`
  e hoje reflete o estado do site principal, nao do webmail
- portanto, `results-joomla` pode aparecer `unhealthy` mesmo com o webmail
  operacional

## 4. Configuracao correta no stack HTTPD

Arquivo principal:

- [docker-compose.yml](/opt/results/infra/docker-compose.yml:68)

O servico `joomla` precisa receber estas variaveis:

```yaml
environment:
  JOOMLA_DB_HOST: ${JOOMLA_DB_HOST:-10.10.2.99}
  JOOMLA_DB_NAME: ${JOOMLA_DB_NAME:-joomla}
  JOOMLA_DB_USER: ${JOOMLA_DB_USER:-resultsdba}
  JOOMLA_DB_PASSWORD: ${JOOMLA_DB_PASSWORD:-}
  ROUNDCUBE_DB_HOST: ${ROUNDCUBE_DB_HOST:-}
  ROUNDCUBE_DB_PASSWORD: ${ROUNDCUBE_DB_PASSWORD:-}
  ROUNDCUBE_IMAP_HOST: ${ROUNDCUBE_IMAP_HOST:-ssl://imap.results.com.br}
  ROUNDCUBE_SMTP_SERVER: ${ROUNDCUBE_SMTP_SERVER:-tls://mx1.results.com.br}
  ROUNDCUBE_MANAGESIEVE_HOST: ${ROUNDCUBE_MANAGESIEVE_HOST:-imap.results.com.br}
```

Ponto critico:

- antes da correcao, `ROUNDCUBE_DB_HOST` nao era injetado no container
- o `config.inc.php` ja suportava essa variavel, mas o `compose` nao a passava

## 5. Configuracao correta do Apache para `/webmail/`

Arquivo:

- [apache/vhosts-templates/00-results.conf.template](/opt/results/infra/apache/vhosts-templates/00-results.conf.template:101)

Trecho essencial:

```apache
ProxyPass        "/webmail/"  "http://joomla/webmail/" nocanon
ProxyPassReverse "/webmail/"  "http://joomla/webmail/"
<Location "/webmail/">
    ProxyPassReverseCookiePath "/" "/webmail/"
</Location>
```

Isso garante:

- acesso publico via `/webmail/`
- cookie `roundcube_sessid` com escopo compativel com `/webmail/`
- isolamento do Roundcube em relacao a raiz do site

## 6. Configuracao correta do Roundcube

Arquivo:

- [roundcube/config.inc.php](/opt/results/infra/roundcube/config.inc.php:1)

Parametros relevantes:

### Banco

```php
$roundcubeDbHost = getenv('ROUNDCUBE_DB_HOST') ?: (getenv('JOOMLA_DB_HOST') ?: 'srvmysql.results.intranet');
$config['db_dsnw'] = 'mysql://roundcube:' . (getenv('ROUNDCUBE_DB_PASSWORD') ?: 'CHANGE_ME') . '@' . $roundcubeDbHost . '/roundcubemail';
```

### IMAP

```php
$config['default_host'] = getenv('ROUNDCUBE_IMAP_HOST') ?: 'ssl://imap.results.com.br';
```

### SMTP

```php
$config['smtp_server'] = getenv('ROUNDCUBE_SMTP_SERVER') ?: 'tls://mx1.results.com.br';
$config['smtp_user'] = '%u';
```

### ManageSieve

```php
$config['managesieve_host'] = getenv('ROUNDCUBE_MANAGESIEVE_HOST') ?: 'imap.results.com.br';
$config['managesieve_port'] = 4190;
$config['managesieve_usetls'] = true;
```

### Pastas padrao

```php
$config['drafts_mbox'] = 'Drafts';
$config['sent_mbox'] = 'Sent';
$config['junk_mbox'] = 'Spam';
$config['trash_mbox'] = 'Trash';
```

## 7. Variaveis corretas do ambiente IP `10.10.2.60`

Arquivo:

- [ .env.remote-10.10.2.30-ip60 ](/opt/results/infra/.env.remote-10.10.2.30-ip60:1)

Valores relevantes para o webmail validado:

```dotenv
SERVER_NAME=results.com.br
RESULTS_SERVER_NAME=results.com.br
RESULTS_SERVER_ALIAS=www.results.com.br

JOOMLA_DB_HOST=10.10.2.99
JOOMLA_DB_NAME=joomla
JOOMLA_DB_USER=resultsdba
JOOMLA_DB_PASSWORD=resu1@@dba

ROUNDCUBE_DB_HOST=10.10.2.79
ROUNDCUBE_DB_PASSWORD=resu100roundcube
ROUNDCUBE_IMAP_HOST=ssl://imap.results.com.br
ROUNDCUBE_SMTP_SERVER=tls://mx1.results.com.br
ROUNDCUBE_MANAGESIEVE_HOST=imap.results.com.br

EDGE_BIND_IP=10.10.2.60
HTTP_PORT=80
HTTPS_PORT=18443

HTTP_DOCKER_DNS=172.28.0.1
```

Valores usados atualmente (2026-06-11):

```dotenv
ROUNDCUBE_IMAP_HOST=tls://results-mail-dovecot
ROUNDCUBE_SMTP_SERVER=tls://results-mail-postfix
ROUNDCUBE_MANAGESIEVE_HOST=results-mail-dovecot
HTTP_DOCKER_DNS=127.0.0.11
```

Nota: Com a consolidacao de redes (rede unica `infra-shared`), os containers
conseguem resolver nomes de outros containers via DNS interno do Docker.
O DNS `127.0.0.11` e o resolver embutido do Docker.

Valores que nao devem voltar para este ambiente:

- `HTTP_DOCKER_DNS=172.25.0.1` (default hardcoded no `docker-compose.yml`)
- `HTTP_DOCKER_DNS=172.28.0.1` (gateway da antiga rede `infra_default`)

Motivo:

- `172.25.0.1` e o gateway de `infra-httpd_default`, rede a que
  `secure-httpd`/`results-joomla` **nao** estao conectados. Com esse
  valor, `/etc/resolv.conf` fica inalcancavel e **toda** resolucao DNS
  externa trava.
- `127.0.0.11` e o DNS embutido do Docker, sempre acessivel de qualquer
  rede bridge.
- Ver `docs/NETWORK_CONSOLIDATION.md` para detalhes da topologia atual.

## 8. Variaveis corretas do ambiente mail

Arquivo:

- [ .env.remote-10.10.2.30-mail ](/opt/results/infra/.env.remote-10.10.2.30-mail:1)

Valores importantes para o funcionamento do login:

```dotenv
MAIL_HOSTNAME=mx1.results.com.br
MAIL_MX2_HOSTNAME=mx2.results.com.br
MAIL_IMAP_HOSTNAME=imap.results.com.br
MAIL_DOMAIN=results.com.br

MAIL_MYSQL_HOST=10.10.2.99
MAIL_MYSQL_PORT=3306
MAIL_MYSQL_DATABASE=results
MAIL_MYSQL_USER=resultsdba
MAIL_MYSQL_PASSWORD=resu1@@dba

LDAP_URI=ldap://10.10.2.10,ldap://10.10.2.7
LDAP_BIND_DN=cn=administrador,dc=results,dc=com,dc=br
LDAP_BIND_PASSWORD=resu100vsza
```

## 9. MySQL correto para o Roundcube

Estado validado em `2026-06-08`:

- `srvmysql0.results.intranet`: `read_only = 0`
- `srvmysql1.results.intranet`: `read_only = 1`
- `srvmysql2.results.intranet`: `read_only = 1`

Implicacao:

- o Roundcube precisa escrever em `roundcubemail.session`
- qualquer caminho que termine em backend `read_only` causa erro

Erros tipicos quando isso quebra:

- `DATABASE ERROR: CONNECTION FAILED!`
- `ProxySQL Error: Access denied for user 'roundcube'`
- `The MariaDB server is running with the --read-only option`
- `Table 'roundcubemail.session' doesn't exist`

Sobre o ultimo erro:

- ele apareceu quando o trafego foi para um backend inconsistente ou replica
- no ambiente correto validado, o `session` existe e aceita escrita

## 10. Regras NAT corretas no host

Estado final validado no `10.10.2.30`:

```bash
iptables -t nat -S | grep 3306
-A PREROUTING -d 10.10.2.99/32 -p tcp -m tcp --dport 3306 -j DNAT --to-destination 127.0.0.1:6033
-A OUTPUT -d 10.10.2.99/32 -p tcp -m tcp --dport 3306 -j DNAT --to-destination 127.0.0.1:6033
```

Regra que precisou ser removida para o webmail funcionar:

```bash
iptables -t nat -D PREROUTING -d 10.10.2.79 -p tcp --dport 3306 -j DNAT --to-destination 127.0.0.1:6033
iptables -t nat -D OUTPUT -d 10.10.2.79 -p tcp --dport 3306 -j DNAT --to-destination 127.0.0.1:6033
```

Motivo:

- com esse DNAT ativo, `10.10.2.79` deixava de apontar para o writer real
- o trafego era redirecionado para o ProxySQL local
- o ProxySQL, naquele estado, entregava backends read-only para o Roundcube

## 11. ProxySQL necessario para compatibilidade

Mesmo com o Roundcube operando via `10.10.2.79`, o ProxySQL precisa conhecer o
usuario `roundcube` para outros fluxos e para evitar regressao.

Arquivos preparados no repositorio:

- [mysql-cluster/proxysql/bootstrap.sql](/opt/results/infra/mysql-cluster/proxysql/bootstrap.sql:36)
- [mysql-cluster/scripts/bootstrap-proxysql.sh](/opt/results/infra/mysql-cluster/scripts/bootstrap-proxysql.sh:18)

Estado aplicado no runtime durante a correcao:

```sql
INSERT INTO mysql_users (username, password, default_hostgroup, active)
VALUES ('roundcube', 'resu100roundcube', 0, 1);

LOAD MYSQL USERS TO RUNTIME;
SAVE MYSQL USERS TO DISK;
```

## 12. Dovecot necessario para login real

O login HTTP do Roundcube so fica verde se o IMAP tambem estiver correto.

Dependencias observadas:

- `results-mail-dovecot` ativo
- `imap.results.com.br:993` respondendo
- autenticacao IMAP funcionando para o usuario

Validacao util:

```bash
docker exec results-mail-dovecot doveadm user jefferson
echo "jcm@1970" | docker exec -i results-mail-dovecot doveadm auth test jefferson
```

## 13. Validacoes que devem passar

### 13.1. Teste publico de pagina

Da maquina operacional ou do proprio host:

```bash
WEBMAIL_URL=https://www.results.com.br/webmail/ \
WEBMAIL_RESOLVE_HOST=www.results.com.br \
WEBMAIL_RESOLVE_IP=10.10.2.60 \
sh ./scripts/test-webmail-login.sh
```

Resultado esperado sem credenciais:

- `Pagina de login carregou corretamente`

### 13.2. Teste autenticado real

```bash
WEBMAIL_URL=https://www.results.com.br/webmail/ \
WEBMAIL_RESOLVE_HOST=www.results.com.br \
WEBMAIL_RESOLVE_IP=10.10.2.60 \
WEBMAIL_USER=jefferson \
WEBMAIL_PASSWORD='jcm@1970' \
sh ./scripts/test-webmail-login.sh
```

Resultado esperado:

- `Login no webmail validado com sucesso`

### 13.3. Teste remoto direto no `10.10.2.30`

```bash
ssh root@10.10.2.30 "
  cd /opt/results/infra &&
  WEBMAIL_URL=https://www.results.com.br/webmail/ \
  WEBMAIL_RESOLVE_HOST=www.results.com.br \
  WEBMAIL_RESOLVE_IP=10.10.2.60 \
  WEBMAIL_USER=jefferson \
  WEBMAIL_PASSWORD='jcm@1970' \
  sh ./scripts/test-webmail-login.sh
"
```

### 13.4. Teste ampliado do ambiente

```bash
ssh root@10.10.2.30 "
  cd /opt/results/infra &&
  WEBMAIL_MAIL_ENV_FILE=.env.remote-10.10.2.30-mail \
  WEBMAIL_RESOLVE_IP=10.10.2.60 \
  sh ./scripts/test-webmail-temporary-auth.sh
"
```

Observacao:

- na validacao de `2026-06-08`, a parte SQL do teste temporario passou e o
  login Roundcube do usuario temporario SQL ficou verde
- a etapa de criacao LDAP temporaria ainda podia falhar com
  `ldap_bind: Invalid credentials (49)` no servidor LDAP que recebe `ldapadd`
- isso nao invalida o funcionamento do webmail com usuarios reais

## 14. Sintomas e causa provavel

`DATABASE ERROR: CONNECTION FAILED!`

- Roundcube sem acesso ao banco
- conferir `ROUNDCUBE_DB_HOST`
- conferir `ROUNDCUBE_DB_PASSWORD`
- conferir logs em `webmail/logs/errors.log`

`ProxySQL Error: Access denied for user 'roundcube'`

- usuario `roundcube` ausente no ProxySQL runtime
- bootstrap do ProxySQL incompleto

`read-only option`

- Roundcube caiu em replica
- DNAT ou ProxySQL entregando backend errado

`Table roundcubemail.session doesn't exist`

- backend inconsistente ou schema incompleto
- checar se o acesso foi para o banco writer correto

`php_network_getaddresses ... dovecot`

- `ROUNDCUBE_IMAP_HOST` ou `ROUNDCUBE_MANAGESIEVE_HOST` usando hostname
  interno nao resolvivel pelo container `results-joomla`

`Erro SMTP (-1): Conexao ao servidor falhou.` (ao clicar Enviar no Roundcube)

- DNS externo quebrado dentro de `results-joomla`/`secure-httpd`: `getent
  hosts mx1.results.com.br` trava/retorna vazio
- conferir `# ExtServers:` em `docker exec results-joomla cat
  /etc/resolv.conf` — precisa ser um gateway de rede a que o container esteja
  conectado (`172.28.0.1`, gw de `infra_default`); `172.25.0.1` (default do
  compose) e inalcancavel
- corrigir via `HTTP_DOCKER_DNS=172.28.0.1` em `.env.remote-10.10.2.30` +
  `docker compose ... up -d --no-deps --force-recreate joomla apache`

## 15. Arquivos de referencia

- [roundcube/config.inc.php](/opt/results/infra/roundcube/config.inc.php:1)
- [docker-compose.yml](/opt/results/infra/docker-compose.yml:68)
- [apache/vhosts-templates/00-results.conf.template](/opt/results/infra/apache/vhosts-templates/00-results.conf.template:101)
- [scripts/test-webmail-login.sh](/opt/results/infra/scripts/test-webmail-login.sh:1)
- [scripts/test-webmail-temporary-auth.sh](/opt/results/infra/scripts/test-webmail-temporary-auth.sh:1)
- [ .env.remote-10.10.2.30-ip60 ](/opt/results/infra/.env.remote-10.10.2.30-ip60:1)
- [ .env.remote-10.10.2.30-mail ](/opt/results/infra/.env.remote-10.10.2.30-mail:1)
- [mysql-cluster/proxysql/bootstrap.sql](/opt/results/infra/mysql-cluster/proxysql/bootstrap.sql:36)
- [mysql-cluster/scripts/bootstrap-proxysql.sh](/opt/results/infra/mysql-cluster/scripts/bootstrap-proxysql.sh:1)
- [docs/session-2026-06-08-migration.md](/opt/results/infra/docs/session-2026-06-08-migration.md:1)

## 16. Resumo executivo

O ambiente correto do webmail em `10.10.2.30` ficou assim:

- Apache publica `/webmail/` via `secure-httpd`
- `results-joomla` recebe `ROUNDCUBE_DB_HOST`
- Roundcube usa:
  - banco em `10.10.2.79`
  - IMAPS em `results-mail-dovecot:993` (rede `infra-shared`)
  - SMTP em `results-mail-postfix:587` (rede `infra-shared`)
  - ManageSieve em `results-mail-dovecot:4190` (rede `infra-shared`)
- o host nao pode manter DNAT de `10.10.2.79:3306` para `127.0.0.1:6033`
- ProxySQL deve conhecer `roundcube`
- Dovecot precisa autenticar usuarios reais
- DNS interno via `127.0.0.11` (Docker embedded DNS)

Com esse conjunto, o login e envio/recebimento de email do webmail
funcionam em producao.

---

## 17. Correcao SMTP 451 — Temporary Lookup Failure

### Problema (2026-06-11)

Ao enviar email pelo Roundcube, o erro:

`Erro SMTP (451): Falha ao adicionar o destinatário "jefferson@results.com.br"
(4.3.0 <jefferson@results.com.br>: Temporary lookup failure)`

### Causa raiz

O `virtual_alias_maps` do Postfix incluia LDAP:

```
virtual_alias_maps = mysql:/etc/postfix/mysql_virtual_alias_maps.cf,
                     ldap:/etc/postfix/ldap_virtual_alias_maps.cf
```

O bind LDAP falhava com `cn=administrador` (sem senha) — o servidor LDAP
recusava. Como o `virtual_alias_maps` e consultado antes do
`virtual_mailbox_maps`, a falha no LDAP bloqueava TODAS as entregas,
mesmo quando o MySQL resolvia o destinatario corretamente.

### Diagnostico

```bash
# Teste MySQL: OK
docker exec results-mail-postfix postmap -q jefferson@results.com.br \
  mysql:/etc/postfix/mysql_virtual_mailbox_maps.cf
# → results.com.br/jefferson/Maildir/

# Teste LDAP: FALHA
docker exec results-mail-postfix postmap -q jefferson@results.com.br \
  ldap:/etc/postfix/ldap_virtual_alias_maps.cf
# → postmap: warning: dict_ldap_connect: Unable to bind to server
#   ldap://ldap:389 with dn cn=administrador,dc=results,dc=com,dc=br:
#   53 (Server is unwilling to perform)
```

### Solucao

Remover LDAP do `virtual_alias_maps`:

```bash
docker exec results-mail-postfix postconf -e \
  'virtual_alias_maps = mysql:/etc/postfix/mysql_virtual_alias_maps.cf'
docker exec results-mail-postfix postfix reload
```

Template corrigido permanentemente em:
`mail/postfix/main.cf.template` (linha 14).

### Nota sobre LDAP

Os dados de usuarios no LDAP (`results-mail-ldap`) estao vazios — o bind
com `cn=admin,dc=results,dc=com,dc=br` / `resu1@@admin` funciona, mas so
existe a OU `ou=people`, sem usuarios. O backup dos dados esta em
`/opt/docker/ldap/base.ldif` (769 linhas, 14 usuarios).

Se precisar reabilitar LDAP no Postfix:
1. Importar usuarios: `ldapadd -x -H ldap://localhost -D 'cn=admin,...' -w 'resu1@@admin' -f base.ldif`
2. Corrigir `ldap_virtual_alias_maps.cf`: `bind_dn = cn=admin,...` e `bind_pw = resu1@@admin`
3. Reabilitar no `virtual_alias_maps`

---

## 18. Correcao de Volumes Docker — Duplicacao por Nome de Projeto

### Problema

Ao fazer deploy com diferentes nomes de projeto (`infra-mail`, `infra`,
`results-mail`), o Docker Compose criava volumes com nomes diferentes,
resultando em volumes vazios e perda aparente de dados.

Exemplo:
- `infra-mail_maildata` (16.5GB, dados reais)
- `infra_maildata` (vazio, criado por deploy com project `infra`)
- `results-mail_maildata` (vazio, criado por deploy com project `results-mail`)

### Solucao

Todos os volumes nos compose files usam `name:` explicito:

```yaml
volumes:
  maildata:
    name: infra-mail_maildata
  ldap-data:
    name: infra_ldap-data
  ...
```

Isso garante que o nome do volume nao depende do `project name` e
previne duplicacao em futuros deploys.

---

## 19. Referencias adicionais

- [docs/NETWORK_CONSOLIDATION.md](NETWORK_CONSOLIDATION.md) — Consolidacao de redes Docker
- [docs/TROUBLESHOOTING_EMAIL.md](TROUBLESHOOTING_EMAIL.md) — Troubleshooting de email
- [memories/repo/postfix-ldap-issue.md](/opt/results/infra/memories/repo/postfix-ldap-issue.md) — Nota tecnica LDAP
