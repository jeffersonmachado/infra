# Consolidacao de Redes Docker

Data: `2026-06-11`

> **Politica de IPs**: containers nunca usam `10.10.2.30`, apenas VIPs.
> Ver [CONTAINER_IP_POLICY.md](CONTAINER_IP_POLICY.md).

## Resumo

As redes Docker foram consolidadas de 24 para 18 redes, unificando os stacks
HTTP, Mail, DNS, e Observe em uma rede compartilhada `infra-shared`.

## Topologia antes (24 redes)

Cada stack tinha suas proprias redes isoladas, impedindo comunicacao direta
entre containers de stacks diferentes:

| Stack | Redes |
|-------|-------|
| HTTP (`docker-compose.yml`) | `infra_default` |
| Mail (`docker-compose.mail.yml`) | `infra-mail_default` |
| DNS | `dns-consolidated_default` |
| Edge SNI | `infra_edge-sni` |
| Observe | `infra_observe-internal`, `infra_observe-egress`, `infra_observe-public`, etc. |
| MySQL Galera | `host` network |
| ProxySQL | `host` network |
| VPN | `infra_vpn` |

Problemas:

- Joomla (`infra_default`) nao conseguia resolver `dovecot`/`postfix`
  (estavam em `infra-mail_default`)
- Apache (`infra_default`) precisava de `extra_hosts` para alcancar
  `mx1`/`imap`/`srvmysql`
- DNS `172.25.0.1` (gateway de `infra-httpd_default`) inalcancavel
  para containers em outras redes
- Cada rede tinha seu proprio gateway e subnet, exigindo configuracao
  manual de `extra_hosts`

## Topologia depois (18 redes)

Rede unificada `infra-shared` conecta todos os containers que precisam
se comunicar:

```text
infra-shared (bridge)
├── secure-httpd        (Apache, HTTP/HTTPS)
├── results-joomla      (Joomla + Roundcube)
├── joomla-lsyncd       (lsyncd Joomla)
├── httpd-lsyncd        (Apache lsyncd)
├── httpd-subdomain-sync
├── results-mail-postfix     (MX1 SMTP)
├── results-mail-postfix-mx2 (MX2 SMTP)
├── results-mail-dovecot     (IMAP/POP3/Sieve)
├── results-mail-rspamd      (Anti-spam)
├── results-mail-clamav      (Antivirus)
├── results-mail-ldap        (LDAP)
├── results-mail-certbot     (Certificados TLS)
├── results-mail-redis       (Cache Rspamd)
├── edge-sni            (HAProxy SNI)
├── dns-dnsdist         (DNS Load Balancer)
└── pdns-auth / pdns-recursor (DNS)
```

Redes removidas:
- `infra_default` → substituida por `infra-shared`
- `infra-mail_default` → substituida por `infra-shared`
- `infra_edge-sni` → substituida por `infra-shared`
- `infra_observe-internal` → unificada com `infra-shared` (se necessario)

## Configuracao

### docker-compose.yml (HTTP stack)

```yaml
networks:
  default:
    name: infra-shared
    external: true
```

### docker-compose.mail.yml (Mail stack)

```yaml
networks:
  default:
    name: infra-shared
    external: true
```

### Criacao da rede

```bash
docker network create infra-shared
```

## Beneficios

1. **DNS interno**: Containers resolvem nomes de outros containers via
   DNS embutido do Docker (`127.0.0.11`)
2. **Sem `extra_hosts`**: Nao precisa mapear `mx1`/`imap`/`srvmysql`
   manualmente
3. **Menos complexidade**: 18 redes em vez de 24
4. **Comunicacao direta**: Joomla → Dovecot, Joomla → Postfix, Apache →
   Joomla, tudo via rede compartilhada

## DNS

Com `infra-shared`, o DNS dos containers deve usar `127.0.0.11` (Docker
embedded DNS), nao gateways de redes especificas:

```yaml
# docker-compose.yml
dns:
  - 127.0.0.11
```

Ou via env:
```dotenv
HTTP_DOCKER_DNS=127.0.0.11
```

## Verificacao

```bash
# Listar containers na rede
docker network inspect infra-shared --format '{{range .Containers}}{{.Name}} {{end}}'

# Testar resolucao interna
docker exec results-joomla getent hosts results-mail-dovecot
docker exec results-joomla getent hosts results-mail-postfix

# Testar conectividade
docker exec results-joomla sh -c 'echo >/dev/tcp/results-mail-dovecot/993 && echo OK || echo FAIL'
```

## Troubleshooting

### Container nao consegue resolver hostnames internos

```bash
# Verificar se o container esta na rede
docker network inspect infra-shared | grep NOME_DO_CONTAINER

# Conectar se necessario
docker network connect infra-shared NOME_DO_CONTAINER
```

### DNS externo nao funciona

```bash
# Verificar resolv.conf
docker exec NOME_DO_CONTAINER cat /etc/resolv.conf
# Deve mostrar nameserver 127.0.0.11

# Se mostrar outro IP, corrigir com dns: no compose
```
