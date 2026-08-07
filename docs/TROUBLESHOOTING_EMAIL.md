# Troubleshooting de Email — Webmail (Roundcube)

Data: `2026-06-11`

## Guia rapido de diagnosticos

### 1. "DATABASE ERROR: CONNECTION FAILED!" (tela branca)

**Causas provaveis**:
- `ROUNDCUBE_DB_HOST` nao injetado ou errado
- `ROUNDCUBE_DB_PASSWORD` incorreta
- ProxySQL nao conhece usuario `roundcube`
- Backend MySQL `read_only`

**Diagnostico**:
```bash
# Testar conexao direta
docker exec results-joomla php -r "
\$c = mysqli_connect(getenv('ROUNDCUBE_DB_HOST'), 'roundcube', getenv('ROUNDCUBE_DB_PASSWORD'), 'roundcubemail');
echo mysqli_error(\$c) ?: 'OK';
"

# Verificar se usuario existe no ProxySQL
docker exec mysql-proxysql mysql -u admin -p'pr0xysql@dm1n2026' \
  -h 127.0.0.1 -P 6032 -e "SELECT username, default_hostgroup FROM mysql_users WHERE username='roundcube'"
```

**Solucao**:
- Garantir `ROUNDCUBE_DB_HOST=10.10.2.79` no `.env`
- Se usar ProxySQL (10.10.2.99), adicionar usuario:
  ```sql
  INSERT INTO mysql_users (username, password, default_hostgroup, active)
  VALUES ('roundcube', 'resu100roundcube', 0, 1);
  LOAD MYSQL USERS TO RUNTIME; SAVE MYSQL USERS TO DISK;
  ```

---

### 2. "Erro SMTP (-1): Conexao ao servidor falhou" (ao enviar)

**Causas provaveis**:
- DNS quebrado no container Joomla
- `ROUNDCUBE_SMTP_SERVER` apontando para hostname nao resolvivel
- Firewall bloqueando porta 587

**Diagnostico**:
```bash
# Verificar resolucao DNS
docker exec results-joomla cat /etc/resolv.conf
# Deve conter: nameserver 127.0.0.11

# Testar conectividade com Postfix
docker exec results-joomla php -r "
\$fp = @fsockopen('tls://results-mail-postfix', 587, \$errno, \$errstr, 10);
echo \$fp ? 'OK' : \"FAIL: \$errstr\";
"

# Testar do host
echo "EHLO test" | openssl s_client -connect 10.10.2.3:587 -starttls smtp -quiet 2>&1 | head -5
```

**Solucao**:
- DNS: `127.0.0.11` (Docker embedded DNS)
- `ROUNDCUBE_SMTP_SERVER=tls://results-mail-postfix`
- Verificar `smtp_conn_options` com `verify_peer=false`

---

### 3. "Erro SMTP (451): Temporary lookup failure" (ao enviar)

**Causa**: LDAP no `virtual_alias_maps` do Postfix falhando

**Diagnostico**:
```bash
# Testar alias lookup
docker exec results-mail-postfix postmap -q jefferson@results.com.br \
  ldap:/etc/postfix/ldap_virtual_alias_maps.cf

# Se der erro de bind, confirmar:
docker exec results-mail-postfix postconf virtual_alias_maps
# Se incluir ldap:, remover
```

**Solucao**:
```bash
docker exec results-mail-postfix postconf -e \
  'virtual_alias_maps = mysql:/etc/postfix/mysql_virtual_alias_maps.cf'
docker exec results-mail-postfix postfix reload
```

Template permanente: `mail/postfix/main.cf.template`

---

### 4. "Falha na autenticacao" (login Roundcube)

**Causas provaveis**:
- Dovecot nao autentica usuario
- LDAP `auth_bind` falhando
- Senha errada ou usuario nao existe

**Diagnostico**:
```bash
# Testar autenticacao Dovecot
echo "SENHA" | docker exec -i results-mail-dovecot doveadm auth test USUARIO

# Listar usuarios
docker exec results-mail-dovecot doveadm user USUARIO

# Listar mailboxes
docker exec results-mail-dovecot doveadm mailbox list -u USUARIO
```

**Solucao**:
- Verificar `dovecot.conf.template`: `passdb` com SQL antes de LDAP
- SQL: `connect = host=10.10.2.79 port=3306 dbname=results user=resultsdba password=resu100dba`
- LDAP: bind com `cn=admin,dc=results,dc=com,dc=br` / `resu1@@admin`

---

### 5. "502 Proxy Error" (login Roundcube via Apache)

**Causa**: Apache timeout (30s) expirando antes do Roundcube completar o login.
O PHP 7.2 tem problemas de compatibilidade TLS com Dovecot moderno,
causando hang na conexao SSL.

**Diagnostico**:
```bash
# Verificar timeout Apache
docker exec secure-httpd grep Timeout /usr/local/apache2/conf/httpd.conf

# Testar conexao PHP IMAP
docker exec results-joomla php -r '
\$fp = fsockopen("results-mail-dovecot", 10143, \$e, \$s, 5);
echo \$fp ? "OK: " . fgets(\$fp, 256) : "FAIL: \$s";
'

# Verificar logs Roundcube
docker exec results-joomla tail -5 /var/www/html/results/webmail/logs/errors.log
```

**Solucao**:
- `Timeout 120` e `ProxyTimeout 120` no Apache (`httpd.conf.template`)
- Listener IMAP plaintext no Dovecot na porta 10143 (`dovecot.conf.template`)
- `ROUNDCUBE_IMAP_HOST=results-mail-dovecot`, `ROUNDCUBE_IMAP_PORT=10143`
- `docker-compose.yml`: adicionar `ROUNDCUBE_IMAP_PORT` ao environment

---

### 6. Postfix bloqueando IPs legítimos no Spamhaus

**Causa**: `postscreen_access_list` usando `proxy:mysql:` (nao suportado).
O postscreen ignora a configuracao e aplica DNSBL sem whitelist.

**Diagnostico**:
```bash
# Verificar se postscreen esta ignorando a whitelist
docker logs results-mail-postfix | grep "unknown command: check_client_access"

# Testar whitelist CIDR
docker exec results-mail-postfix postmap -q '209.85.219.48' \
  cidr:/etc/postfix/client_access.cidr
```

**Solucao**:
- `postscreen_access_list = permit_mynetworks, cidr:/etc/postfix/client_access.cidr`
- `smtpd_client_restrictions = check_client_access cidr:/etc/postfix/client_access.cidr, ...`
- Arquivo `mail/postfix/client_access.cidr` com ranges Google, Microsoft, Yahoo
- Template permanente: `mail/postfix/main.cf.template`
- Dockerfile: `COPY client_access.cidr /etc/postfix/client_access.cidr`

### 5. Caixa de entrada vazia (mas emails existem)

**Causas provaveis**:
- Volume errado montado (nome de volume incorreto)
- Dovecot index corrompido
- Permissoes erradas no Maildir

**Diagnostico**:
```bash
# Contar emails no Maildir
docker exec results-mail-dovecot ls /var/mail/vhosts/results.com.br/USUARIO/Maildir/cur/ | wc -l

# Verificar se Dovecot indexa
docker exec results-mail-dovecot doveadm search -u USUARIO mailbox INBOX | wc -l

# Forcar reindex
docker exec results-mail-dovecot doveadm force-resync -u USUARIO '*'
```

**Solucao**:
- Verificar volume: `docker volume inspect infra-mail_maildata`
- Se volume errado, ajustar `docker-compose.mail.yml` com `name:` explicito
- Reindexar: `doveadm force-resync -u USUARIO '*'`

---

### 6. "500 Proxy Error - DNS lookup failure for: joomla" (webmail fora do ar)

**Causa**: `secure-httpd` faz proxy para `http://joomla/` mas o container
se chama `results-joomla` (`container_name` explícito no compose). O DNS
embutido do Docker resolve por `container_name`, não pelo nome do serviço.

**Diagnostico**:
```bash
# Verificar se o alias existe na rede
docker exec secure-httpd ping -c1 joomla
# "bad address 'joomla'" confirma o problema

# Verificar nome do container
docker ps --format '{{.Names}}' | grep joomla
# Esperado: results-joomla

# Verificar se o alias está configurado na rede
docker network inspect infra-shared --format '{{range .Containers}}{{.Name}} {{end}}' | tr ' ' '\n' | grep joomla
```

**Solucao**:
- Permanente: adicionar `aliases: [joomla]` no `docker-compose.yml`:
  ```yaml
  networks:
    default:
      aliases:
        - joomla
  ```
- Hotfix (sem rebuild):
  ```bash
  docker network disconnect infra-shared results-joomla
  docker network connect --alias joomla --alias results-joomla infra-shared results-joomla
  ```

---

### 7. Certificado SSL inválido (ERR_CERT_AUTHORITY_INVALID) após restart do Apache

**Causa**: `mod_md` fica no estado "certificate(rsa) is missing" (state=1)
após o container `secure-httpd` ser recriado. O módulo não inicia a renovação
automaticamente em todos os cenários.

**Diagnostico**:
```bash
# Verificar estado dos certificados
docker exec secure-httpd sh -c 'for d in /usr/local/apache2/md/domains/*/; do echo "$(basename $d): $(grep state $d/md.json)"; done'
# state=1 → parado, state=2 → renovando

# Verificar se está servindo fallback (autoassinado)
echo | openssl s_client -connect 201.6.110.53:443 -servername www.results.com.br 2>&1 | grep 'CN = Apache Managed Domain Fallback'
```

**Solucao**:
```bash
# Disparar renovação forçando graceful restart
docker exec secure-httpd httpd -k graceful
# Aguardar ~30s e verificar novamente o state (deve ir para 2 e depois concluir)
```

---

### 8. IMAP TLS certificate mismatch

**Causa**: Certificado TLS do Dovecot tem CN `mx1.results.com.br` mas
Roundcube conecta via hostname `results-mail-dovecot`

**Diagnostico**:
```bash
# Verificar certificado
echo | openssl s_client -connect 10.10.2.3:993 2>&1 | grep -E 'CN=|subject'
```

**Solucao**:
- No `roundcube/config.inc.php`:
  ```php
  $config['imap_conn_options'] = [
      'ssl' => [
          'verify_peer' => false,
          'verify_peer_name' => false,
          'allow_self_signed' => true,
      ],
  ];
  ```
- Mesmo para `smtp_conn_options`

---

## Comandos uteis

```bash
# Status completo do mail stack
docker ps --format '{{.Names}}\t{{.Status}}' | grep results-mail

# Logs do Postfix (ultimas 20 linhas)
docker logs results-mail-postfix --tail 20

# Logs do Dovecot
docker logs results-mail-dovecot --tail 20

# Email queue do Postfix
docker exec results-mail-postfix mailq

# Teste completo de entrega local
docker exec results-mail-postfix sendmail -bv jefferson@results.com.br

# Verificar regras iptables (portas de email)
iptables -t nat -L PREROUTING -n | grep -E ':(25|587|465|993|143)'
```
