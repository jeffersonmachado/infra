# Politica de IPs para Containers — Uso Exclusivo de VIPs

**Data:** 2026-06-13
**Status:** Em vigor

## Regra fundamental

> **Nenhum container deve utilizar o IP primario do host Docker (`10.10.2.30`) como endereco de bind ou destino de comunicacao. Todo servico exposto deve usar uma VIP (Virtual IP) configurada em `eth0` do host, preferencialmente com o mesmo IP que o servico legado ocupava antes da migracao.**

## Motivacao

1. **Separacao de responsabilidades**: o IP `10.10.2.30` pertence ao host (Alpine Linux + Docker daemon). Servicos individuais devem ter identidade de rede propria, nao depender do IP de gerencia do host.

2. **Mobilidade futura**: se um container precisar ser movido para outro host Docker, o IP de servico (VIP) pode ser transferido junto, sem reconfigurar DNS, firewall ou clientes. Se o servico estiver atrelado ao `10.10.2.30`, essa mobilidade e impossivel.

3. **Compatibilidade com legado**: o firewall (`srvfw0`, 10.10.2.254) e o DNS externo ja apontam para os IPs legados. Manter esses mesmos IPs como VIPs no host novo elimina a necessidade de reconfigurar dezenas de regras de firewall e registros DNS.

4. **Evitar acoplamento acidental**: se um container usa `10.10.2.30` internamente e esse IP muda (ex: migracao de datacenter), todas as referencias internas quebram. Com VIPs, apenas a configuracao de rede do host precisa ser ajustada.

## Tabela de VIPs ativas

| VIP | Servico | Container(es) | IP legado |
|-----|---------|---------------|-----------|
| `10.10.2.1` | DNS ns1 | `dns-dnsdist` | `srvdns0` (VM Xen) |
| `10.10.2.3` | MX1 / IMAP / SMTP | `results-mail-postfix`, `results-mail-dovecot` | `srvmail0` (VM Xen) |
| `10.10.2.20` | DNS ns2 | `dns-dnsdist` | `srvdns0` (VM Xen) |
| `10.10.2.23` | MX2 SMTP | `results-mail-postfix-mx2` | `srvmail0` (VM Xen) |
| `10.10.2.49` | MariaDB node | `srvmysql2` | `mysql2` (VM Xen) |
| `10.10.2.60` | Web HTTP/HTTPS | `secure-httpd`, `edge-sni` | `srvhttp0` (VM Xen) |
| `10.10.2.79` | MariaDB node | `srvmysql0` | legado |
| `10.10.2.89` | MariaDB node | `srvmysql1` | legado |
| `10.10.2.99` | ProxySQL / MariaDB | `mysql-proxysql` | `piranha0` (VM Xen) |

## Como aplicar

### 1. Port bind nos containers

Sempre especificar o IP da VIP no `ports:` do compose, nunca `0.0.0.0` generico para servicos de producao:

```yaml
# Correto — bind explicito na VIP do servico
ports:
  - "${MAIL_BIND_IP:-10.10.2.3}:25:25"
  - "${MAIL_BIND_IP:-10.10.2.3}:587:587"

# Incorreto — bind em 0.0.0.0 expoe o servico em TODOS os IPs do host,
# incluindo 10.10.2.30, 10.10.2.1, 10.10.2.20, etc.
ports:
  - "25:25"
```

### 2. Referencias internas entre containers

Containers na rede `infra-shared` comunicam-se **exclusivamente via DNS interno do Docker** (`container_name` ou service name), nunca via IP do host:

```yaml
# Correto — usar hostname interno
environment:
  ROUNDCUBE_IMAP_HOST: "ssl://results-mail-dovecot"
  ROUNDCUBE_SMTP_SERVER: "tls://results-mail-postfix"

# Incorreto — hardcodar IP do host
environment:
  ROUNDCUBE_IMAP_HOST: "ssl://10.10.2.30"
```

### 3. Firewall e DNAT

As regras de PREROUTING no firewall (`srvfw0`, 10.10.2.254) ja redirecionam trafego externo para as VIPs. Nao criar regras que apontem para `10.10.2.30` como destino de servico.

### 4. DNS

Registros DNS (tanto internos quanto externos) devem apontar para as VIPs, nunca para `10.10.2.30`:

```
; Correto
mx1.results.com.br.     A   10.10.2.3
mx2.results.com.br.     A   10.10.2.23
ns1.results.com.br.     A   10.10.2.1
ns2.results.com.br.     A   10.10.2.20

; Incorreto
mx1.results.com.br.     A   10.10.2.30
```

### 5. healthchecks e monitoramento

Healthchecks que testam conectividade externa devem usar a VIP do servico, nao `127.0.0.1` nem `10.10.2.30`:

```yaml
# Correto — testa no IP de servico (VIP)
healthcheck:
  test: ["CMD-SHELL", "nc -z 10.10.2.3 25 || exit 1"]
```

## Excecoes validas

- **VPN (SoftEther)**: `10.10.2.30:5555` — o servidor VPN esta diretamente no host, nao em container com IP proprio. E aceitavel porque e um servico de infraestrutura de acesso, nao um servico de aplicacao.
- **Portas de debug/API internas**: `127.0.0.1:5300`, `127.0.0.1:9081`, etc. — estas sao isoladas no loopback e nao expostas externamente.
- **`network_mode: host`**: containers que usam `network_mode: host` (ex: `srvmysql0`, `mysql-proxysql`) precisam de IPs proprios na interface do host. Neste caso, o IP do container e a VIP atribuida a ele (ex: `10.10.2.79`, `10.10.2.99`), nunca `10.10.2.30`.

## Verificacao

```bash
# Listar todos os binds de porta e seus IPs
docker ps --format '{{.Names}} {{.Ports}}' | grep -v '127.0.0.1'

# Nenhum container de aplicacao deve aparecer com 10.10.2.30
# Exemplo de saida esperada:
# results-mail-postfix  10.10.2.3:25->25/tcp, 10.10.2.3:587->587/tcp
# secure-httpd          10.10.2.60:443->8443/tcp, 10.10.2.60:80->8080/tcp
# dns-dnsdist           10.10.2.1:53->53/udp, 10.10.2.20:53->53/udp

# Verificar se algum container de aplicacao tem bind em 10.10.2.30
docker ps --format '{{.Names}} {{.Ports}}' | grep '10.10.2.30'
# Esperado: apenas rvpn (VPN, porta 5555) e nenhum outro container
```

## Violacoes conhecidas

Nenhuma. Todos os containers estao em conformidade com a politica.
