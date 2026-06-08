# DNS Interno

O CoreDNS interno foi totalmente substituido pelo stack PowerDNS consolidado em
`dns-consolidated/`. Nao use mais os servicos `internal-dns-a` e
`internal-dns-b` como caminho operacional para resolver `results.com.br` ou
`results.intranet`.

O desenho atual e:

- `pdns-auth`: PowerDNS Authoritative, com backend MariaDB via ProxySQL
- `pdns-recursor`: PowerDNS Recursor para resolucao interna, split-horizon e cache
- `dns-dnsdist`: entrada unica DNS, roteando consultas para authoritative ou recursor

## Dependencia: ProxySQL + MySQL Replication

O `pdns-auth` **depende do cluster MySQL** para armazenar zonas e registros DNS.
O cluster e composto por 3 nos com replicacao master-slave e um ProxySQL como
load balancer:

| Componente | Container | IP | Funcao |
|---|---|---|---|
| **ProxySQL** | `mysql-proxysql` | `10.10.2.99` | Load balancer SQL (r/w → master, r/o → slaves) |
| Master | `srvmysql0` | `10.10.2.79` | Leitura e escrita |
| Slave 1 | `srvmysql1` | `10.10.2.89` | Leitura (read-only) |
| Slave 2 | `srvmysql2` | `10.10.2.49` | Leitura (read-only) |

> **IMPORTANTE**: O ProxySQL **deve estar ativo** para que `srvmysql.results.intranet`
> resolva para `10.10.2.99` e o trafego SQL seja balanceado corretamente.
> Se o ProxySQL estiver parado, `10.10.2.99:3306` nao responde e o `pdns-auth`
> falha ao conectar.

### Ordem de inicializacao

```bash
# 1. Subir cluster MySQL (master primeiro, depois slaves)
cd /opt/results/infra
docker compose -f docker-compose.mysql-replication.yml --env-file .env.mysql-replication --project-name infra up -d srvmysql0
docker compose -f docker-compose.mysql-replication.yml --env-file .env.mysql-replication --project-name infra up -d srvmysql1 srvmysql2

# 2. Subir ProxySQL
docker compose -f docker-compose.proxysql.yml --env-file .env.mysql-replication up -d

# 3. Subir stack DNS (ja deve estar rodando)
cd dns-consolidated
docker compose -f docker-compose.yml --env-file .env up -d
```

### Fallback sem ProxySQL

Se o ProxySQL estiver indisponivel, configure `DNS_DB_HOST=10.10.2.79` no
`.env` do `dns-consolidated/` para que o `pdns-auth` conecte direto ao master.
Nesse caso o registro DNS `srvmysql.results.intranet` tambem deve apontar para
`10.10.2.79` (atualizar via `pdnsutil` ou direto no banco).

## IPs dos nameservers

Os nameservers do dominio `results.com.br` usam IPs virtuais configurados em
`eth0` no host `10.10.2.30`:

| Nameserver | IP virtual |
|------------|-----------|
| `ns1.results.com.br` | `10.10.2.1` |
| `ns2.results.com.br` | `10.10.2.20` |

IPs adicionais listados na zona (ns3, ns4) resolvem para `201.6.110.53` e sao
mantidos para compatibilidade com configuracao legada de dominios no registro.br.

O IP publico `201.6.110.53` e gerenciado pelo provedor (NAT/forward para
`10.10.2.30`). Consultas DNS externas enviadas para `201.6.110.53:53` chegam ao
`dnsdist` via docker-proxy e sao roteadas para o pool `auth` (`pdns-auth`).

Portas de diagnostico do stack atual:

- `5353`: `dnsdist` durante migracao ou validacao paralela
- `5300`: `pdns-auth` direto
- `5301`: `pdns-recursor` direto
- `53`: `dnsdist` apos o corte definitivo

## Observacao operacional

Se o servico Docker for reiniciado no host, reconecte a VPN antes de validar ou
subir novamente o stack `dns-consolidated/`.

Motivo: o `pdns-auth` depende de acesso ao MariaDB em `10.10.2.99:3306`. Sem a
VPN ativa, o container pode voltar, mas nao consegue ler as zonas do banco,
fazendo as consultas autoritativas falharem ou retornarem `SERVFAIL` pelo
recursor/dnsdist.

## Validacao

No servidor de DNS, valide pelo stack PowerDNS:

```bash
cd /opt/results/infra/dns-consolidated
NEW_DNS=127.0.0.1 NEW_PORT=5353 bash scripts/validate.sh
```

Se o corte ja foi feito para a porta padrao:

```bash
cd /opt/results/infra/dns-consolidated
NEW_DNS=127.0.0.1 NEW_PORT=53 bash scripts/validate.sh
```

Para testar diretamente o registro da VPN:

```bash
dig @127.0.0.1 -p 5353 rvpn.results.com.br A +short
dig @127.0.0.1 -p 5300 rvpn.results.com.br A +short
dig @127.0.0.1 -p 5301 rvpn.results.com.br A +short
```

`rvpn.results.com.br` deve existir no PowerDNS Authoritative. O script
`dns-consolidated/scripts/validate.sh` trata esse registro como item obrigatorio
da zona `results.com.br`.

## Legado CoreDNS

Os arquivos `dns-internal/`, `internal-dns-a`, `internal-dns-b` e
`docker-compose.dns-internal.yml` permanecem apenas como historico da fase
anterior. Nao devem ser usados para novos cortes, correcao de registros ou
publicacao DNS em producao.

Qualquer ajuste de zona deve ser feito no PowerDNS, validado pelo dnsdist e
comparado com o legado quando necessario pelos scripts em `dns-consolidated/`.
