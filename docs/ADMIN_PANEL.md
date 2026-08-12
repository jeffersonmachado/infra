# Painel Administrativo — admin.results.com.br

Criado em 2026-08-11. Atualizado em 2026-08-12 (migração para Python/Flask).

## Visão geral

Painel web unificado em **Python/Flask** para administração da infraestrutura.
Um único container (`admin-panel`) substitui os 4 containers anteriores
(PowerDNS-Admin, VHosts Manager, phpLDAPadmin, Admin Dashboard).

Acesso: `https://admin.results.com.br/`

## Funcionalidades

| Rota | Funcionalidade | Fonte de dados |
|---|---|---|
| `/` | Dashboard com cards baseado nas flags de acesso | — |
| `/dns` | Listar zonas DNS, ver/adicionar/remover registros | API REST do pdns-auth |
| `/vhosts` | CRUD de virtual hosts Apache | MySQL (`apache_vhosts`) |
| `/ldap` | Listar/criar/editar/remover usuários e grupos | OpenLDAP |

## Flags de acesso (memberOf)

Cada usuário vê apenas as funcionalidades dos grupos a que pertence:

- `cn=dns-admins,ou=groups,...` → acesso ao DNS
- `cn=vhost-admins,ou=groups,...` → acesso aos VHosts
- `cn=ldap-admins,ou=groups,...` → acesso ao LDAP

## Container

| Container | Imagem | Função | Porta |
|---|---|---|---|
| `admin-panel` | `infra-admin-panel:latest` (python:3.12-alpine) | Flask + gunicorn | 5000 |
| `secure-httpd` | `infra-apache:latest` | Proxy reverso + SSL (mod_md) | 8080/8443 |

## Apache vhost

Proxy simples — sem path routing, sem mod_substitute:

```apache
ProxyPass / http://admin-panel:5000/
ProxyPassReverse / http://admin-panel:5000/
```

## Variáveis de ambiente

```
ADMIN_SERVER_NAME=admin.results.com.br
FLASK_SECRET_KEY=<gerada>
DNS_API_KEY=<mesma do dns-consolidated>
PDNS_API_URL=http://pdns-auth:8081
LDAP_URI=ldap://results-ldap:389
LDAP_BASE_DN=dc=results,dc=com,dc=br
LDAP_BIND_DN=cn=admin,dc=results,dc=com,dc=br
LDAP_BIND_PASSWORD=<senha>
ADMIN_GROUP_DNS=cn=dns-admins,ou=groups,dc=results,dc=com,dc=br
ADMIN_GROUP_VHOSTS=cn=vhost-admins,ou=groups,dc=results,dc=com,dc=br
ADMIN_GROUP_LDAP=cn=ldap-admins,ou=groups,dc=results,dc=com,dc=br
```

## Deploy

```bash
# Sincronizar
sshpass -p "$SSH_PASSWORD" rsync -az -e "ssh -o StrictHostKeyChecking=no" \
    admin-panel/ apache/ docker-compose.yml .env.example \
    root@10.10.2.30:/opt/results/infra/

# No servidor
cd /opt/results/infra

# Remover containers/volumes antigos
docker compose down pdns-admin vhosts-manager admin-dashboard ldap-admin
docker volume rm infra_pdns-admin-data 2>/dev/null || true

# Atualizar .env com as novas variáveis
vim .env   # ou: cat >> .env << 'EOF' ...

# Criar grupo ldap-admins (se não existir)
docker exec results-ldap ldapadd -x -H ldap://127.0.0.1:389 \
  -D "cn=admin,dc=results,dc=com,dc=br" -w "$LDAP_BIND_PASSWORD" << 'EOF'
dn: cn=ldap-admins,ou=groups,dc=results,dc=com,dc=br
objectClass: groupOfNames
cn: ldap-admins
member: cn=admin,dc=results,dc=com,dc=br
EOF

# Build e deploy
docker compose build --no-cache admin-panel apache
docker compose up -d --force-recreate admin-panel apache
docker compose up -d --remove-orphans
```

## Arquivos

| Arquivo | Descrição |
|---|---|
| `admin-panel/Dockerfile` | Python 3.12 + Flask + ldap3 + pymysql + gunicorn |
| `admin-panel/app.py` | Aplicação Flask (~300 linhas) |
| `admin-panel/templates/` | Templates Jinja2 (login, dashboard, dns, vhosts, ldap) |
| `apache/vhosts-templates/80-admin.conf.template` | Proxy reverso simples |
| `docker-compose.yml` | Serviço `admin-panel` |
