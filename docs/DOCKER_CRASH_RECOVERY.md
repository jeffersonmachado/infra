# Recuperação Pós-Crash do Docker — Runbook

**Data:** 2026-08-05
**Servidor:** `10.10.2.30` (mexico.results.intranet)

## Sintomas pós-crash

- Todas as portas TCP fechadas (dockerd caiu)
- `docker ps` trava (containerd parado)
- DNS externo não resolve (dnsdist offline)
- Disco raiz (`/`) a 99%

---

## 1. Recuperar Docker e Containerd

```bash
ssh root@10.10.2.30

# 1.1 Verificar estado
service docker status
service containerd status
df -h /

# 1.2 Liberar espaço em disco (crítico: / com < 100MB trava tudo)
rm -rf /var/cache/apk/*
find /var/log -type f -name '*.gz' -delete
find /var/log -type f -name '*.1' -delete

# 1.3 Iniciar containerd (se parado)
service containerd start

# 1.4 Se docker travar no "Stopping":
killall -9 dockerd containerd
rm -rf /var/lib/docker/containers/*
service containerd restart
service docker start

# 1.5 Matar processos órfãos (docker-proxy, containerd-shim)
killall -9 docker-proxy containerd-shim-runc-v2

# ⚠️  CRÍTICO: Remover TODAS as bridges órfãs
# Após restart do Docker, bridges antigas ficam com a mesma subnet das novas.
# Isso quebra conectividade do host com os containers (DNS, HTTP, DB, tudo).
# Verificar rotas duplicadas:
ip route show | sort | uniq -d

# Remover cada bridge órfã (as que NÃO aparecem em `docker network ls`):
for br in $(ip link show type bridge | grep -oP 'br-\w+'); do
  docker network inspect ${br#br-} >/dev/null 2>&1 || ip link delete $br
done
```

---

## 2. Corrigir DNS do Host

O `/etc/resolv.conf` aponta para `10.10.2.1` (dnsdist), mas o DNS ainda não
está rodando — dependência circular.

```bash
# 2.1 Usar DNS externo temporariamente
echo 'nameserver 8.8.8.8' > /etc/resolv.conf
echo 'nameserver 1.1.1.1' >> /etc/resolv.conf

# 2.2 Atualizar daemon.json do Docker para usar DNS externo
cat > /etc/docker/daemon.json << 'EOF'
{
  "experimental": true,
  "ip6tables": true,
  "dns": ["8.8.8.8", "1.1.1.1"],
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "50m",
    "max-file": "5"
  }
}
EOF
service docker restart
```

---

## 3. Remover Bridges Órfãs (CAUSA MAIS COMUM DE DNS QUEBRADO)

Após crash, bridges Docker antigas permanecem com a mesma subnet das novas,
criando conflito de rota.

```bash
# 3.1 Listar bridges com mesma subnet
ip route show | grep 10.53.53

# Se houver DUAS entradas para 10.53.53.0/24:
# 10.53.53.0/24 dev br-366d3f8f91ed scope link  ← ativa (dns-consolidated)
# 10.53.53.0/24 dev br-d6d474556259 scope link  ← ÓRFÃ, REMOVER

# 3.2 Identificar qual bridge tem containers
docker network inspect 366d3f8f91ed | grep Containers  # ← tem containers
docker network inspect d6d474556259 | grep Containers  # ← vazia

# 3.3 Remover bridge órfã
ip link delete br-d6d474556259

# 3.4 Verificar (deve sobrar só uma rota)
ip route show | grep 10.53.53
```

---

## 4. Subir Stack DNS

```bash
cd /opt/results/infra

# Criar rede se não existir
docker network create infra-shared 2>/dev/null

# Subir DNS (usa o .env dentro de dns-consolidated/)
docker compose -f dns-consolidated/docker-compose.yml \
  --env-file dns-consolidated/.env up -d

# Verificar
docker ps --format '{{.Names}} {{.Status}}' | grep -E 'dns|pdns'
```

---

## 5. Subir MariaDB

```bash
# Usar volume de dados existente (galera-data-srvmysql0)
docker rm -f srvmysql0 srvmysql1 srvmysql2 2>/dev/null

docker run -d --name srvmysql0 \
  --restart unless-stopped \
  --network infra-shared \
  -p 10.10.2.79:3306:3306 \
  -e MARIADB_ROOT_PASSWORD=resu100dba \
  -v galera-data-srvmysql0:/var/lib/mysql \
  mariadb:10.11
```

---

## 6. Subir Stack Web (Apache + Joomla + Webmail)

```bash
cd /opt/results/infra

# 6.1 Recriar rede infra-shared com subnet correta
docker network rm infra-shared 2>/dev/null
docker network create --subnet=192.168.48.0/20 --gateway=192.168.48.1 \
  --label com.docker.compose.network=default \
  --label com.docker.compose.project=infra \
  infra-shared

# 6.2 Subir containers de suporte
docker run -d --name joomla-lsyncd --restart unless-stopped \
  --network infra-shared \
  -v /opt/results/infra/joomla-site:/sync/source:ro \
  -v infra_joomla-site-data:/sync/target \
  infra-joomla-lsyncd:latest

docker run -d --name httpd-lsyncd --restart unless-stopped \
  --network infra-shared \
  -v /opt/results/infra/joomla-site:/sync/source:ro \
  -v infra_site-data:/sync/target \
  infra-lsyncd:latest

docker run -d --name httpd-subdomain-sync --restart unless-stopped \
  --network infra-shared \
  -v infra_runtime-conf:/conf \
  -v /var/run/docker.sock:/var/run/docker.sock:ro \
  infra-subdomain-sync:latest

# 6.3 Obter IP do MySQL
MYSQL_IP=$(docker inspect srvmysql0 \
  --format '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' \
  | tr ' ' '\n' | head -1)

# 6.4 Subir Joomla
docker run -d --name results-joomla --restart unless-stopped \
  --network infra-shared --dns 8.8.8.8 \
  --add-host srvmysql.results.intranet:$MYSQL_IP \
  -e JOOMLA_DB_HOST=$MYSQL_IP \
  -e JOOMLA_DB_NAME=joomla \
  -e JOOMLA_DB_USER=resultsdba \
  -e JOOMLA_DB_PASSWORD=resu100dba \
  -e JOOMLA_CACHE_TMPFS_SIZE=256m \
  -v infra_joomla-site-data:/var/www/html/results \
  -v infra_joomla-logs:/var/www/html/results/logs \
  --tmpfs /var/www/html/results/cache:size=256m,uid=33,gid=33 \
  --health-cmd='curl -f -s -o /dev/null http://127.0.0.1/ || exit 1' \
  --health-interval=10s --health-timeout=5s --health-retries=3 \
  --health-start-period=15s \
  results-joomla:php74-event

# 6.5 Obter IP do Joomla
JOOMLA_IP=$(docker inspect results-joomla \
  --format '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' \
  | tr ' ' '\n' | head -1)

# 6.6 Subir Apache
docker run -d --name secure-httpd --restart unless-stopped \
  --network infra-shared --dns 8.8.8.8 \
  --add-host joomla:$JOOMLA_IP \
  --add-host results-joomla:$JOOMLA_IP \
  -p 10.10.2.60:80:8080 \
  -p 10.10.2.60:18443:8443 \
  -e SERVER_NAME=results.com.br \
  -e RESULTS_SERVER_NAME=results.com.br \
  -e RESULTS_SERVER_ALIAS=www.results.com.br \
  -e RESULTS_ROOT_BACKEND_SCHEME=http \
  -e RESULTS_ROOT_BACKEND_HOST=results-joomla \
  -e RESULTS_ROOT_BACKEND_PORT=80 \
  -e RESULTS_ROOT_BACKEND_PATH=/ \
  -e ADMIN_EMAIL=infra@results.com.br \
  -e ACME_CA_URL=https://acme-v02.api.letsencrypt.org/directory \
  -v infra_site-data:/usr/local/apache2/htdocs:ro \
  -v infra_md-data:/usr/local/apache2/md \
  -v infra_runtime-conf:/usr/local/apache2/conf/runtime \
  -v infra_reload-signal:/var/run/apache-runtime \
  --tmpfs /tmp \
  --health-cmd='curl -f -s -o /dev/null http://127.0.0.1:8080/ || exit 1' \
  --health-interval=15s \
  infra-apache:latest
```

---

## 7. Subir Stack de Email

```bash
cd /opt/results/infra

# 7.1 Restaurar docker-compose.mail.yml se necessário
# O git checkout pode corromper o arquivo, use o backup:
cp docker-compose.mail.yml docker-compose.mail.yml.bak

# 7.2 Garantir que a seção networks tem external: true
# (Editar manualmente se necessário)

# 7.3 Criar rede ldap_default
docker network create ldap_default 2>/dev/null

# 7.4 Subir mail
docker compose -f docker-compose.mail.yml --env-file .env.mail up -d

# 7.5 Pós-subida: reconectar containers web à rede
docker network connect infra-shared secure-httpd
docker network connect infra-shared results-joomla
docker network connect infra-shared httpd-subdomain-sync
docker network connect infra-shared httpd-lsyncd
docker network connect infra-shared joomla-lsyncd
docker network connect infra-shared srvmysql0
docker restart results-joomla secure-httpd
```

---

## 8. Verificação Final

```bash
# Verificar todos os containers
docker ps --format 'table {{.Names}}\t{{.Status}}'

# Testar DNS
dig +short results.com.br @10.10.2.30 NS

# Testar webmail (interno)
docker exec secure-httpd curl -sk https://127.0.0.1:8443/webmail/ | head -10
```

---

## Checklist Rápida

- [ ] `df -h /` — disco raiz com > 100MB livre
- [ ] `service containerd status` — started
- [ ] `service docker status` — started
- [ ] `docker ps` — responde sem travar
- [ ] `ip route show | sort | uniq -d` — sem rotas duplicadas (bridges órfãs!)
- [ ] `dig +short results.com.br @10.10.2.30 NS` — responde
- [ ] `docker ps` — dns, mysql, joomla, apache, mail todos UP

---

## 9. Corrigir Roundcube (Database Error)

### 9.1 IPs de containers mudam a cada restart — usar IPs internos

```bash
MYSQL_IP=$(docker inspect srvmysql0 \
  --format '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' \
  | awk '{print $1}')
DOVECOT_IP=$(docker inspect results-mail-dovecot \
  --format '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' \
  | awk '{print $1}')

# Recriar Joomla com add-host para MySQL, Dovecot e FQDN do certificado
docker rm -f results-joomla
docker run -d --name results-joomla --restart unless-stopped \
  --network infra-shared --dns 8.8.8.8 \
  --add-host srvmysql.results.intranet:$MYSQL_IP \
  --add-host results-mail-dovecot:$DOVECOT_IP \
  --add-host mx1.results.com.br:$DOVECOT_IP \
  --add-host imap.results.com.br:$DOVECOT_IP \
  -e JOOMLA_DB_HOST=$MYSQL_IP \
  -e JOOMLA_DB_NAME=joomla \
  -e JOOMLA_DB_USER=resultsdba \
  -e JOOMLA_DB_PASSWORD=resu100dba \
  -v infra_joomla-site-data:/var/www/html/results \
  -v infra_joomla-logs:/var/www/html/results/logs \
  --tmpfs /var/www/html/results/cache:size=256m,uid=33,gid=33 \
  --health-cmd='curl -f -s -o /dev/null http://127.0.0.1/ || exit 1' \
  --health-interval=10s --health-timeout=5s --health-retries=3 \
  --health-start-period=15s \
  results-joomla:php74-event
```

### 9.2 Configurar Roundcube

O config do Roundcube (`webmail/config/config.inc.php`) fica no volume
`infra_joomla-site-data`. O `joomla-lsyncd` **não** sobrescreve esse arquivo
(excluído via `JOOMLA_LSYNC_EXCLUDES=configuration.php,webmail`).

Modelo de config funcional (ajustar `$MYSQL_IP`):

```php
<?php
$roundcubeDbHost = '192.168.48.5';
$config['db_dsnw'] = 'mysql://roundcube:resu100roundcube@' . $roundcubeDbHost . '/roundcubemail';

// Usar TLS na porta 143 (STARTTLS) — o PHP 7.4 tem issues com SSL direto na 993
// O hostname DEVE ser mx1.results.com.br (CN do certificado Dovecot)
$config['default_host'] = 'tls://mx1.results.com.br';
$config['default_port'] = 143;

$config['smtp_server'] = 'tls://mx1.results.com.br';
$config['smtp_port'] = 587;

$config['imap_conn_options'] = [
  'ssl' => [
    'verify_peer' => false,
    'verify_peer_name' => false,
  ],
];
```

### 9.3 Corrigir MySQL host no Dovecot

O Dovecot tenta conectar no MySQL via `10.10.2.99` (IP do host). Após
recriação da rede, containers não alcançam IPs do host. Corrigir:

```bash
MYSQL_IP=$(docker inspect srvmysql0 \
  --format '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' \
  | awk '{print $1}')

docker exec results-mail-dovecot sed -i \
  "s|host=10.10.2.99|host=$MYSQL_IP|" \
  /etc/dovecot/dovecot-sql.conf.ext
```

### 9.4 Dovecot: MySQL + LDAP (2 backends de auth)

O Dovecot usa **2 backends** de autenticação (em ordem):
1. **SQL (MySQL)** — `password_query` no `dovecot-sql.conf.ext`
2. **LDAP** — `dovecot-ldap.conf.ext` → `srvldap1:389`

Configuração LDAP:
```
hosts = srvldap1:389
base = ou=people,dc=results,dc=com,dc=br
auth_bind = yes
auth_bind_userdn = uid=%n,ou=people,dc=results,dc=com,dc=br
```

**Problema conhecido:** Após recriação da rede, o Dovecot não resolve
`srvldap1`. Adicionar ao `/etc/hosts`:

```bash
LDAP_IP=$(docker inspect results-ldap \
  --format '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' \
  | awk '{print $1}')

docker exec results-mail-dovecot sh -c \
  "echo $LDAP_IP srvldap1 >> /etc/hosts"
```

**Se LDAP estiver com timeout**, desabilitar temporariamente:

```bash
# Remover passdb/userdb LDAP do dovecot.conf
docker exec results-mail-dovecot sed -i '/driver = ldap/,/}/d' /etc/dovecot/dovecot.conf
docker restart results-mail-dovecot
```

### 9.5 Senhas SHA512-CRYPT quebram após rebuild

**Problema:** Após recriar o container Dovecot, hashes `{SHA512-CRYPT}` podem
não ser reconhecidos, resultando em `auth failed (temp_fail)` mesmo com
senha correta. O `doveadm auth test` retorna `code=temp_fail`.

**Solução:** Resetar a senha usando `ENCRYPT()` do MySQL (DES crypt):

```bash
docker exec srvmysql0 mysql -u resultsdba -presu100dba -e \
  "UPDATE results.mailbox SET password=ENCRYPT('jcm@1970') WHERE username='jefferson@results.com.br';"
```

Verificar:
```bash
echo 'jcm@1970' | timeout 5 docker exec -i results-mail-dovecot \
  doveadm auth test jefferson@results.com.br
# Deve retornar: passdb: jefferson@results.com.br auth succeeded
```

---

## Notas

- **Nunca use `service docker restart`** se `docker ps` travar — o stop pode
  travar também. Prefira `killall -9 dockerd` + limpeza de estado + restart.
- **Bridges órfãs** são a causa #1 de tudo quebrar pós-crash.
  Sempre execute `ip route show | sort | uniq -d` para detectar rotas duplicadas.
- **Arquivos .env não são versionados.** O `.env.httpd` serve como backup do
  `.env` da stack web. Se `.env` não existir, copie de `.env.httpd`.
- **Imagens Docker são cacheadas** — não usar `docker compose up -d` com
  `build:` após crash, pois o compose tentará rebuildar. Prefira iniciar
  containers manualmente com `docker run` usando as imagens já existentes.
- **Conectividade entre containers:** usar IPs internos da rede Docker
  (`192.168.48.x`), não IPs do host (`10.10.2.x`). O routing do host para
  as bridges quebra quando há bridges órfãs com a mesma subnet.
- **Firewall (`srvfw0`, 10.10.2.254):** as regras de PREROUTING persistem
  entre reboots. Não precisam ser alteradas. O problema está sempre no
  servidor (`10.10.2.30`).
