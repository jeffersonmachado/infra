# Inventário de Servidores — results.com.br

**Data:** 2026-06-07
**Hypervisor:** `africasul.results.intranet` (10.10.2.29, Xen)
**Firewall:** `srvfw0` (10.10.2.254)

---

## Servidor Novo (ativo) — `srvvpn0` / mexico

| IP Principal | MAC | SO | Serviços |
|-------------|-----|-----|----------|
| `10.10.2.30` | `e2:5d:88:81:ec:d2` | Alpine Linux + Docker | DNS, Web, Email, VPN, Observabilidade, MariaDB |

### IPs secundários em eth0

| IP | Serviço | Container |
|----|---------|-----------|
| `10.10.2.1` | ns1 (DNS) | `dns-dnsdist` → `pdns-auth` |
| `10.10.2.3` | mx1 / IMAP / SMTP | `results-mail-postfix`, `results-mail-dovecot` |
| `10.10.2.20` | ns2 (DNS) | `dns-dnsdist` → `pdns-auth` |
| `10.10.2.23` | mx2 (SMTP) | `results-mail-postfix-mx2` |
| `10.10.2.30` | Principal / VPN | `rvpn` (SoftEther) |
| `10.10.2.49` | MariaDB cluster | `srvmysql0/1/2`, `proxysql-galera` |
| `10.10.2.60` | Web (Apache) | `secure-httpd`, `edge-sni` |
| `10.10.2.79` | MariaDB | `srvmysql0/1/2` (bind) |
| `10.10.2.89` | MariaDB | `srvmysql0/1/2` (bind) |
| `10.10.2.99` | MariaDB (antigo piranha0) | `srvmysql0/1/2` (bind) |

### dnsdist — Configuração final

```lua
-- Backends
newServer({address="10.53.53.11:53", name="pdns-auth",     pool="auth",
           checkType="SOA", checkName="results.com.br."})
newServer({address="10.53.53.12:53", name="pdns-recursor", pool="recurse"})

-- DEFAULT: auth (flag AA para qualquer origem)
addAction(AllRule(), PoolAction("auth"))

-- OVERRIDE: IPs privados → recurse (cache + split-horizon)
local privateNets = newNMG()
privateNets:addMask("127.0.0.0/8")
privateNets:addMask("10.0.0.0/8")
privateNets:addMask("172.16.0.0/12")
privateNets:addMask("192.168.0.0/16")
addAction(NetmaskGroupRule(privateNets), PoolAction("recurse"))
```

---

## VMs Desativadas no Hypervisor `africasul` (10.10.2.29)

| VM | MAC | IPs | Serviço | Método |
|----|-----|-----|---------|--------|
| `dns` / `srvdns0` | `00:16:3e:fb:d7:8c` | 10.10.2.1, 10.10.2.15, 10.10.2.20 | DNS BIND | `shutdown -h` + `xm delete` |
| `dns2` | `00:16:3e:ae:4d:39` | ? | DNS secundário | `xm delete` |
| `mydns` | `00:16:3e:94:e2:45` | ? | DNS antigo | `xm delete` |
| `srvmail0` | `00:16:3e:51:84:42` | 10.10.2.2, 10.10.2.3, 10.10.2.23 | Email | `xm delete` |
| `mysql2` | `00:16:3e:96:0d:18` | 10.10.2.49 | MariaDB | `xm delete` |
| `piranha0` | `00:16:3e:c3:bc:25` | 10.10.2.99 | Balanceador | `xm delete` |

---

## VMs Ativas no Hypervisor (sem conflito)

| VM | MAC | IPs | Serviço |
|----|-----|-----|---------|
| `gabao` | `00:16:3e:c8:8f:54` / `:35:8d:b1` / `:31:b0:ae` | 10.10.2.254, 192.168.15.3, 192.168.0.2 | **Firewall srvfw0** |
| `dhcp0` | `00:16:3e:15:08:05` / `:2d:75:51` | ? | DHCP |
| `mysql` | `00:16:3e:3c:39:8b` | ? | MariaDB |
| `srvhttp0` | `00:16:3e:b3:dc:a4` | 10.10.2.61 | Web |
| `srvldap0` | `00:16:3e:fa:53:0e` | ? | LDAP |
| `srvmonitor0` | `00:16:3e:9f:b6:af` / `:5e:18:d5` | 10.10.2.18 | Monitoramento |
| `srvradius0` | `00:16:3e:0c:37:d9` / `:0a:bd:b3` / `:a6:1b:a2` | 10.10.2.8, 10.10.2.88 | Radius |
| `srvvoip0` | `00:16:3e:cd:ab:03` / `:10:31:81` | ? | VoIP |

### VMs Desligadas (sem conflito)

`centos7`, `matriz`, `srvftp0`, `srvproxy0`

---

## Firewall — Regras PREROUTING (`10.10.2.254`)

| Linha | Proto | Porta | Origem | → Destino | Serviço |
|-------|-------|-------|--------|-----------|---------|
| #5 | UDP | 53 | 192.168.0.2 | 10.10.2.30:53 | DNS |
| #7 | TCP | 53 | 192.168.0.2 | 10.10.2.30:53 | DNS TCP |
| — | TCP | 25,465,587 | 192.168.0.2 | 10.10.2.3 | SMTP |
| — | TCP | 110,143,993,995 | 192.168.0.2 | 10.10.2.3 | IMAP/POP3 |
| — | TCP | 80 | 192.168.0.2 | 10.10.2.60 | HTTP |
| — | TCP | 443 | 192.168.0.2 | 10.10.2.60 | HTTPS |
| #31 | TCP | 5555 | 192.168.0.2 | 10.10.2.30 | VPN |

---

## ARP Final (todos IPs no servidor novo)

```
10.10.2.1  (ns1)  → e2:5d:88:81:ec:d2 (10.10.2.30)
10.10.2.3  (mx1)  → e2:5d:88:81:ec:d2 (10.10.2.30)
10.10.2.20 (ns2)  → e2:5d:88:81:ec:d2 (10.10.2.30)
10.10.2.23 (mx2)  → e2:5d:88:81:ec:d2 (10.10.2.30)
10.10.2.30 (vpn)  → e2:5d:88:81:ec:d2 (10.10.2.30)
10.10.2.49 (mdb)  → e2:5d:88:81:ec:d2 (10.10.2.30)
10.10.2.60 (web)  → e2:5d:88:81:ec:d2 (10.10.2.30)
10.10.2.99 (mdb)  → e2:5d:88:81:ec:d2 (10.10.2.30)
```

---

## Comandos úteis

```bash
# Ver ARP (conflitos)
ssh srvfw0 "ip neigh show | sort"

# Listar VMs no hypervisor
ssh africasul "xm list"

# Health dnsdist
ssh 10.10.2.30 "docker logs dns-dnsdist --tail 5 | grep -E 'Marking|up|down'"

# Regras do firewall
ssh srvfw0 "iptables -t nat -L PREROUTING -n -v --line-numbers | grep -E 'dpt:53|10.10.2.30'"

# IPs do servidor novo
ssh 10.10.2.30 "ip addr show eth0 | grep inet"
```
