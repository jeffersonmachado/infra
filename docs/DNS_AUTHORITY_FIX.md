# Correção: "Sem autoridade sobre o domínio" — registro.br

**Data:** 2026-06-07
**Domínio:** `results.com.br`
**DNS:** `201.6.110.53`

## Causa raiz (principal)

**Conflito de IP `10.10.2.1`** entre duas máquinas na rede `10.10.2.0/24`:

| IP | Hostname | MAC |
|----|----------|-----|
| `10.10.2.1` | `srvdns0` (10.10.2.15) — servidor DNS **antigo** | `00:16:3e:fb:d7:8c` |
| `10.10.2.1` | `srvvpn0` (10.10.2.30) — servidor **novo** (mexico) | `e2:5d:88:81:ec:d2` |

O firewall (`10.10.2.254`) encaminhava DNS para `10.10.2.1:53`. Devido ao conflito, o `srvdns0` respondia com **referrals aos root servers** (`0/13/1`), sem a flag `aa`. O dnsdist/pdns-auth no servidor novo nunca recebia as consultas externas.

### Evidência (ARP no firewall)

```
srvdns0.results.intranet (10.10.2.1)  at 00:16:3e:fb:d7:8c  ← antigo
srvvpn0.results.intranet (10.10.2.30) at e2:5d:88:81:ec:d2  ← novo
```

## Causa secundária

O **health check padrão do dnsdist** consultava `powerdns.com. A`. O `pdns-auth` não é autoritativo para esse domínio → `REFUSED` → dnsdist marcava o backend como `down` a cada 1s.

## Correções aplicadas

### 1. Firewall (`10.10.2.254`) — DNAT corrigido

**Antes:** `192.168.0.2:53 → 10.10.2.1:53` (servidor antigo)
**Depois:** `192.168.0.2:53 → 10.10.2.30:53` (servidor correto)

```bash
# UDP
iptables -t nat -R PREROUTING 5 -d 192.168.0.2 -p udp --dport 53 -j DNAT --to-destination 10.10.2.30:53
# TCP
iptables -t nat -D PREROUTING -d 192.168.0.2 -p tcp --dport 53 -j DNAT --to-destination 10.10.2.1:53
iptables -t nat -A PREROUTING -d 192.168.0.2 -p tcp --dport 53 -j DNAT --to-destination 10.10.2.30:53
```

### 2. Servidor (`10.10.2.30`) — IP `10.10.2.1` removido

```bash
ip addr del 10.10.2.1/24 dev eth0
```

### 3. dnsdist — Health check

**Arquivo:** `dns-consolidated/dnsdist/dnsdist.conf`

```lua
-- Antes
newServer({address="10.53.53.11:53", name="pdns-auth", pool="auth"})

-- Depois
newServer({address="10.53.53.11:53", name="pdns-auth", pool="auth",
           checkType="SOA", checkName="results.com.br."})
```

### 4. dnsdist — Roteamento corrigido

**Antes:** `AllRule()→recurse` como default + tabela Lua no `NetmaskGroupRule`  
**Depois:** `AllRule()→auth` como default + `newNMG()` como objeto

```lua
-- DEFAULT: auth — responde com flag AA para qualquer origem
addAction(AllRule(), PoolAction("auth"))

-- OVERRIDE: IPs privados → recurse (split-horizon + cache)
local privateNets = newNMG()
privateNets:addMask("127.0.0.0/8")
privateNets:addMask("10.0.0.0/8")
privateNets:addMask("172.16.0.0/12")
privateNets:addMask("192.168.0.0/16")
addAction(NetmaskGroupRule(privateNets), PoolAction("recurse"))
```

## Topologia (fluxo DNS final)

```
Registro.br / Internet
        │
        ▼
201.6.110.53 (roteador de borda)
        │
        ▼
10.10.2.254 (firewall — srvfw0)
  eth0: 10.10.2.254/24
  eth1: 192.168.15.3/24 (WAN)
  eth2: 192.168.0.2/24  (bridge para Docker)
        │
        │ DNAT: 192.168.0.2:53 → 10.10.2.30:53
        ▼
10.10.2.30 (servidor — srvvpn0 / mexico)
  eth0: 10.10.2.30/24
  eth0: 10.10.2.20/24 (virtual — ns2.results.com.br)
        │
        │ docker-proxy (0.0.0.0:53 → 10.53.53.13:53)
        ▼
dns-dnsdist (10.53.53.13)
  ├── DEFAULT: pool "auth" → pdns-auth (flag AA ✓)
  └── OVERRIDE (RFC1918): pool "recurse" → pdns-recursor
        │
        ▼
pdns-auth (10.53.53.11)
  Backend: MariaDB 10.10.2.99
  Zonas: results.com.br, results.intranet
```

## Regras finais do firewall

| Regra | Descrição |
|-------|-----------|
| `DNAT udp ... 192.168.0.2 dpt:53 to:10.10.2.30:53` | DNS externo UDP → novo servidor |
| `DNAT tcp ... 192.168.0.2 dpt:53 to:10.10.2.30:53` | DNS externo TCP → novo servidor |

## Arquivos modificados

| Arquivo | Alteração |
|---------|-----------|
| `dns-consolidated/dnsdist/dnsdist.conf` | Health check + roteamento (`newNMG()`, ordem) |
| `dns-consolidated/rendered/dnsdist/dnsdist.conf` | Idem (cópia no servidor) |
| Firewall `10.10.2.254` iptables | DNAT 53: `10.10.2.1` → `10.10.2.30` |
| `10.10.2.30` eth0 | IP `10.10.2.1` removido |

## Comandos úteis

```bash
# Health dos backends (sem flapping = OK)
ssh root@10.10.2.30 "docker logs dns-dnsdist --tail 20 | grep -E 'Marking|up|down'"

# Testar resposta autoritativa
ssh root@10.10.2.30 "nslookup -type=SOA results.com.br 10.10.2.30"

# Testar do firewall
ssh root@10.10.2.254 "nslookup -type=SOA results.com.br 10.10.2.30"

# Ver regras do firewall
ssh root@10.10.2.254 "iptables -t nat -L PREROUTING -n -v | grep ':53'"

# Reiniciar dnsdist
ssh root@10.10.2.30 "docker restart dns-dnsdist"

# Verificar conflito de IP (ARP)
ssh root@10.10.2.254 "arp -a | grep 10.10.2.1"
```
