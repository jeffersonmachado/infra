# Sessão 2026-06-08 — Migração Final VMs → Containers + Correções

## Resumo

Sessão de consolidação final: todas as VMs legadas no hypervisor `africasul` (10.10.2.29) foram desligadas e removidas permanentemente. Seus IPs foram migrados para o servidor principal `10.10.2.30` (mexico). Correções críticas no DNS externo, Dovecot, MySQL e rede Docker.

---

## 1. DNS Externo — "Sem autoridade sobre o domínio"

### Problema
- `registro.br` e `mxtoolbox.com` reportavam "Sem autoridade sobre o domínio" / "No Valid NameServers Responded"
- DNS externo (201.6.110.53) não retornava flag AA (Authoritative Answer)

### Causa raiz
- Firewall (`srvfw0` em 10.10.2.254) redirecionava tráfego DNS para `10.10.2.51` (VM `mydns` legada)
- VM `mydns` tinha porta 53 aberta mas PowerDNS não estava funcional (zonas stale)
- `pdns-auth` no container estava DOWN no dnsdist (health check SOA falhava)

### Correções
1. **dnsdist.conf**: config final com `AllRule → recurse` (default) + `NotRule → auth` (IPs públicos)
2. **route_localnet=1**: habilitado para todas as bridges Docker (`sysctl -w net.ipv4.conf.all.route_localnet=1`)
3. **DNS `srvmysql`**: atualizado de `10.10.2.79` → `10.10.2.99` (ProxySQL ativo)
   - Banco: `results.records` atualizado via SQL
   - `.env`: `DNS_DB_HOST=10.10.2.99`
   - Zone file: `dns-internal/zones/db.results.intranet`
   - `dns-consolidated/mariadb/init/02-zone-data.sql`
4. **pdns-auth recriado** com `extra_hosts: srvmysql→10.10.2.99`
5. **IP 10.10.2.51 migrado** para `eth0` em 10.10.2.30
6. **VM `mydns` removida** permanentemente do hypervisor

### Config dnsdist final
```lua
addLocal("0.0.0.0:53", {reusePort=true})
newServer({address="10.53.53.11:53", name="pdns-auth", pool="auth", checkType="SOA", checkName="results.com.br."})
newServer({address="10.53.53.12:53", name="pdns-recursor", pool="recurse"})
addAction(AllRule(), PoolAction("recurse"))
addAction(NotRule(NetmaskGroupRule({"127.0.0.0/8","10.0.0.0/8","172.16.0.0/12","192.168.0.0/16"})), PoolAction("auth"))
```

---

## 2. Dovecot — Webmail Authentication

### Problema
- `doveadm user jefferson` falhava (timeout)
- `doveadm auth test jefferson` falhava
- Webmail não conseguia logar

### Causa raiz
- `dovecot.conf.template` usava `address = dovecot` (hostname) para LMTP e auth inet_listener
- Container não resolvia hostname "dovecot" → `Fatal: service(lmtp) Can't resolve address dovecot`
- LDAP userdb com `auth_bind = yes` causava hang no userdb lookup
- SQL userdb tentava conectar MySQL inacessível da bridge Docker

### Correções
1. **Template**: `address = dovecot` → `address = *` (LMTP + auth)
2. **Userdb**: `driver = static` substitui LDAP+SQL:
   ```
   userdb {
     driver = static
     args = uid=1004 gid=1004 home=/results.com.br/%n mail=maildir:/results.com.br/%n/Maildir
   }
   ```
3. **Passdb**: mantido `driver = ldap` com `auth_bind = yes` (funciona para autenticação)
4. **Imagem rebuildada**: `infra-dovecot` com SHA `e7e6694de281`

### Verificação
```bash
$ doveadm user jefferson
field   value
uid     1004
gid     1004
home    /results.com.br/jefferson
mail    maildir:/results.com.br/jefferson/Maildir

$ echo "jcm@1970" | doveadm auth test jefferson
passdb: jefferson auth succeeded
```

---

## 3. MySQL — Conectividade das Bridges Docker

### Problema
- Containers em redes bridge (`infra-mail_default`, `infra-httpd_default`, `dns-consolidated_default`) não alcançavam MySQL em `10.10.2.99:3306`
- DNAT `10.10.2.99:3306 → 127.0.0.1:6033` (ProxySQL) não funcionava das bridges

### Causa raiz
- `net.ipv4.conf.all.route_localnet = 0` — kernel bloqueia tráfego roteado para 127.0.0.0/8

### Correção
```bash
sysctl -w net.ipv4.conf.all.route_localnet=1
# Persistido em /etc/sysctl.conf
echo 'net.ipv4.conf.all.route_localnet=1' >> /etc/sysctl.conf
```

### DNAT adicional
```bash
# 10.10.2.79:3306 → ProxySQL (para compatibilidade com DNS antigo)
iptables -t nat -A PREROUTING -d 10.10.2.79 -p tcp --dport 3306 -j DNAT --to-destination 127.0.0.1:6033
iptables -t nat -A OUTPUT -d 10.10.2.79 -p tcp --dport 3306 -j DNAT --to-destination 127.0.0.1:6033
```

---

## 4. DNS `srvmysql.results.intranet` — Migração .79 → .99

### Arquivos alterados
| Arquivo | Antes | Depois |
|---------|-------|--------|
| `dns-consolidated/mariadb/init/02-zone-data.sql` | `10.10.2.79` | `10.10.2.99` |
| `dns-internal/zones/db.results.intranet` | `10.10.2.79` | `10.10.2.99` |
| `dns-consolidated/.env` | `DNS_DB_HOST=10.10.2.79` | `DNS_DB_HOST=10.10.2.99` |
| Banco `results.records` (id=31) | `10.10.2.79` | `10.10.2.99` |

---

## 5. Descomissionamento de VMs

### Hypervisor: `africasul` (10.10.2.29, senha: `@fr!c@Sul`)

### VMs removidas permanentemente
| VM | IP(s) | Container substituto |
|----|-------|---------------------|
| `mydns` | `10.10.2.51` | `dns-dnsdist` (DNS autoritativo + recursor) |
| `dns` | `10.10.2.1` | `dns-dnsdist` (ns1.results.com.br) |
| `dns2` | `10.10.2.20` | `dns-dnsdist` (ns2.results.com.br) |
| `mysql` | `10.10.2.79` | `srvmysql0/1/2` + `proxysql-galera` |
| `srvldap0` | `10.10.2.10` | `results-mail-ldap` + `srvldap1` |
| `srvhttp0` | `10.10.2.55`, `10.10.2.60` | `secure-httpd` + `edge-sni` |
| `srvmonitor0` | `10.10.2.18` | `r-observe-*` stack (prometheus, grafana, icinga, loki) |
| `srvmail0` | `10.10.2.3` | `results-mail-postfix` (mx1) |
| `srvmail2` | `10.10.2.23` | `results-mail-postfix-mx2` (mx2) |
| `mysql1` | `10.10.2.89` | `srvmysql1` (slave) |
| `mysql2` | `10.10.2.49` | `srvmysql2` (slave) |
| `centos7` | — (já offline) | — |
| `srvftp0` | — (já offline) | — |
| `srvproxy0` | — (já offline) | — |
| `matriz` | — (já offline) | — |

### VMs restantes (sem container)
| VM | IP | Serviço | Motivo |
|----|-----|---------|--------|
| `dhcp0` | `10.10.2.212` | DHCP | Sem substituto containerizado |
| `gabao` | `10.10.2.254` | Firewall `srvfw0` | Infraestrutura crítica de rede |
| `srvradius0` | `10.10.2.8` | RADIUS | Sem substituto containerizado |
| `srvvoip0` | `10.10.2.75` | VoIP | Sem substituto containerizado |

### IPs NÃO migrados (ainda nas VMs)
| IP | VM | Serviço |
|----|-----|---------|
| `10.10.2.8` | `srvradius0` | RADIUS |
| `10.10.2.75` | `srvvoip0` | VoIP |
| `10.10.2.212` | `dhcp0` | DHCP |
| `10.10.2.254` | `gabao` (srvfw0) | Firewall |

### IPs migrados para 10.10.2.30 (eth0)

| IP | Origem (VM) | Serviço | Container | Porta |
|----|------------|---------|-----------|-------|
| `10.10.2.1` | `dns` | ns1.results.com.br | `dns-dnsdist` | `0.0.0.0:53` (TCP/UDP) |
| `10.10.2.3` | `srvmail0` | MX1 SMTP | `results-mail-postfix` | `:25`, `:465`, `:587` |
| `10.10.2.10` | `srvldap0` | LDAP | `srvldap1` / `results-mail-ldap` | `:389`, `:636` |
| `10.10.2.18` | `srvmonitor0` | Monitor legado | — (substituído por observe stack) | — |
| `10.10.2.20` | `dns2` | ns2.results.com.br | `dns-dnsdist` | `0.0.0.0:53` |
| `10.10.2.23` | `srvmail2` | MX2 SMTP | `results-mail-postfix-mx2` | `:25`, `:465`, `:587` |
| `10.10.2.30` | — (primário) | Servidor principal | todos os containers | — |
| `10.10.2.49` | `mysql2` (slave) | MySQL slave | `srvmysql2` | `3306` (interno) |
| `10.10.2.51` | `mydns` | **DNS (firewall redireciona)** | `dns-dnsdist` | `0.0.0.0:53` |
| `10.10.2.55` | `srvhttp0` | HTTP legado | `secure-httpd` | `:80→8080`, `:18443→8443` |
| `10.10.2.60` | `srvhttp0` | **HTTPS (web)** | `edge-sni` + `secure-httpd` | `:443`, `:80→8080`, `:18443→8443` |
| `10.10.2.79` | `mysql` (master) | MySQL master (DNAT) | `srvmysql0` → `proxysql-galera` | `3306→127.0.0.1:6033` |
| `10.10.2.89` | `mysql1` (slave) | MySQL slave | `srvmysql1` | `3306` (interno) |
| `10.10.2.99` | — (virtual) | ProxySQL (DNAT) | `proxysql-galera` | `3306→127.0.0.1:6033` |

> **Nota:** `10.10.2.51` é o IP crítico para DNS externo — o firewall `srvfw0` (10.10.2.254) redireciona tráfego DNS (porta 53) da internet para este IP. Sem ele, `registro.br` e consultas externas recebem "Sem autoridade sobre o domínio".

---

## 6. Pendências

### Webmail (Roundcube)
- ❌ "DATABASE ERROR: CONNECTION FAILED!" — ProxySQL `mysql_users` precisa ser atualizado com senha correta para `roundcube@%`
- ✅ Conexão direta ao Galera funciona: `mysql -u roundcube -presu100roundcube -e "SELECT 1"` → OK
- ⚠️ ProxySQL admin: credenciais desconhecidas (não é `admin/admin`)

### Joomla (site principal)
- ❌ `https://results.com.br/` retorna 500 — erro PHP (provavelmente `configuration.php` com `CHANGE_ME`)

### Load do servidor
- Load average ~13 (alto) — `fail2ban-server` consumindo ~24% CPU
- Node.js processes (r-observe, r-agent) consumindo ~20GB RAM combinados

---

## 7. Comandos úteis

### Hypervisor
```bash
sshpass -p '@fr!c@Sul' ssh -o HostKeyAlgorithms=+ssh-rsa root@10.10.2.29
xm list              # Listar VMs
xm shutdown <vm>     # Desligar
xm destroy <vm>      # Forçar
xm delete <vm>       # Remover permanentemente
```

### Servidor principal
```bash
sshpass -p "$SSH_PASSWORD" ssh root@10.10.2.30
cat /proc/loadavg    # Load
ip addr show eth0    # IPs
docker ps            # Containers
```

### DNS
```bash
# Teste interno
nslookup -type=SOA results.com.br 10.10.2.1
# Teste autoritativo direto
nslookup -type=SOA results.com.br 10.53.53.11
# Status dnsdist
docker logs dns-dnsdist --tail 5 | grep Marking
# Zonas
docker exec pdns-auth pdnsutil list-all-zones
```

### MySQL via ProxySQL
```bash
docker exec proxysql-galera mysql -h 127.0.0.1 -P 6033 -u resultsdba -presu1@@dba
```

### Dovecot
```bash
docker exec results-mail-dovecot doveadm user jefferson
echo "jcm@1970" | docker exec -i results-mail-dovecot doveadm auth test jefferson
```

---

## 8. Correcoes Pos-Sessao (2026-06-11)

### 8.1. SMTP 451 — Temporary Lookup Failure

**Problema**: Ao enviar email pelo webmail, Postfix retornava:
`Erro SMTP (451): Falha ao adicionar o destinatário (4.3.0 Temporary lookup failure)`

**Causa**: `virtual_alias_maps` incluia LDAP com bind `cn=administrador` sem
senha. O LDAP recusava o bind, e a falha bloqueava todas as entregas.

**Solucao**: Removido LDAP do `virtual_alias_maps`, mantendo apenas MySQL.
Corrigido em `mail/postfix/main.cf.template` (linha 14).

Ver `docs/TROUBLESHOOTING_EMAIL.md` e `memories/repo/postfix-ldap-issue.md`.

### 8.2. Consolidacao de Redes Docker (24 → 18 redes)

Todas as stacks (HTTP, Mail, DNS) migradas para rede unica `infra-shared`.
Isso eliminou a necessidade de `extra_hosts` e permitiu resolucao DNS
interna entre containers.

DNS alterado de gateways de rede (`172.25.0.1`, `172.28.0.1`) para
`127.0.0.11` (Docker embedded DNS).

Ver `docs/NETWORK_CONSOLIDATION.md`.

### 8.3. Volumes Docker — Duplicacao por Project Name

**Problema**: Deploys com diferentes project names (`infra-mail`, `infra`,
`results-mail`) criavam volumes duplicados vazios. 37.835 emails estavam
no volume `infra-mail_maildata` (16.5GB) mas o container montava
`infra_maildata` (vazio).

**Solucao**: Todos os volumes nos compose files agora usam `name:` explicito
para evitar dependencia do project name.

Ver `docs/WEBMAIL_ENVIRONMENT_10.10.2.30.md` secao 18.

### 8.4. Healthcheck Apache — wget → curl

Healthcheck do `secure-httpd` usava `wget` que nao estava instalado na
imagem `httpd:2.4-alpine`. Adicionado `curl` ao Dockerfile e healthcheck
alterado para `curl -f -s`.

### 8.5. MongoDB AVX — Rocketchat

`mongo:5.0` requer AVX (nao suportado pelo CPU). Downgrade para `mongo:4.4`.

### 8.6. phpMyAdmin Healthcheck

Healthcheck usava `wget` (ausente na imagem). Alterado para `curl`.

### 8.7. MySQL Galera — SST Failure (pendente)

`srvmysql1` e `srvmysql2` falham no State Snapshot Transfer (SST) —
volumes vazios. Cluster opera com single node (`srvmysql0`, cluster_size=1).
Pendente investigacao.

### 8.8. LDAP — Dados de Usuarios Perdidos

O LDAP (`results-mail-ldap`) perdeu os dados de usuarios — so existe
`ou=people` vazia. Backup em `/opt/docker/ldap/base.ldif` (769 linhas,
14 usuarios). Bind com `cn=admin` / `resu1@@admin` funciona.

Nao afeta o funcionamento atual porque:
- Dovecot autentica via SQL (senhas no banco `results`)
- Postfix `virtual_alias_maps` nao usa mais LDAP

### 8.9. Status Final do Webmail

✅ Login: funcional (`jefferson / jcm@1970`)
✅ Inbox: 37.835 emails carregados
✅ Envio: SMTP 587 STARTTLS + AUTH funcional
✅ Recebimento: entrega local funcional

### 8.10. Documentacao Criada/Atualizada

| Documento | Status |
|-----------|--------|
| `docs/WEBMAIL_ENVIRONMENT_10.10.2.30.md` | Atualizado (secoes 17-19) |
| `docs/NETWORK_CONSOLIDATION.md` | Novo |
| `docs/TROUBLESHOOTING_EMAIL.md` | Novo |
| `docs/session-2026-06-08-migration.md` | Atualizado (secao 8) |
| `docs/README.md` | Atualizado |
| `README.md` | Atualizado |
| `memories/repo/postfix-ldap-issue.md` | Novo |
