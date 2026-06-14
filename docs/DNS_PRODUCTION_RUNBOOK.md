# DNS Production Runbook

## Objetivo

Este runbook existe para impedir regressao de DNS publico, split-horizon e
publicacao de IPs internos na internet.

Ele descreve:

- topologia real de producao
- fonte de verdade das zonas
- regras que o firewall precisa manter
- procedimento correto para publicar mudancas
- validacao obrigatoria local e externa

## Topologia real

Fluxo externo:

1. `201.6.110.53`
   Observacao: IP publico no roteador da Claro.
2. DMZ do roteador da Claro
   Encaminha para `192.168.0.2`.
3. `10.10.2.254` (`srvfw0`)
   Interfaces verificadas em `2026-06-08`:
   - `eth0 = 10.10.2.254/24`
   - `eth1 = 192.168.15.3/24`
   - `eth2 = 192.168.0.2/24`
4. Firewall faz DNAT por servico para a rede `10.10.2.0/24`.
5. DNS externo termina em `10.10.2.1:53` (ns1, VIP do dnsdist).
6. No host `10.10.2.30`, `dnsdist` recebe as consultas e encaminha para:
   - `pdns-auth` quando a consulta precisa de resposta autoritativa
   - `pdns-recursor` para recursao interna

## Stack DNS em producao

No host `10.10.2.30`:

- `pdns-auth`: `powerdns/pdns-auth-51:5.1.1`
- `pdns-recursor`: `powerdns/pdns-recursor-51:5.1.10`
- `dns-dnsdist`: `powerdns/dnsdist-19:1.9.14`

Arquitetura:

- authoritative backend: `LMDB`
- split-horizon: `Views`
- dados de zona: aplicados por API REST

## Fonte de verdade

Somente estes arquivos definem o DNS autoritativo atual:

- [dns-consolidated/zones/results.com.br.json](/opt/results/infra/dns-consolidated/zones/results.com.br.json)
- [dns-consolidated/zones/results.com.br..internal.json](/opt/results/infra/dns-consolidated/zones/results.com.br..internal.json)
- [dns-consolidated/zones/results.intranet.json](/opt/results/infra/dns-consolidated/zones/results.intranet.json)
- [dns-consolidated/zones/_networks.json](/opt/results/infra/dns-consolidated/zones/_networks.json)
- [dns-consolidated/zones/_views.json](/opt/results/infra/dns-consolidated/zones/_views.json)

Aplicacao:

- [dns-consolidated/scripts/apply-zones-api.sh](/opt/results/infra/dns-consolidated/scripts/apply-zones-api.sh)
- [dns-consolidated/scripts/apply-views-api.sh](/opt/results/infra/dns-consolidated/scripts/apply-views-api.sh)

Nao usar como fonte de verdade:

- `dns-consolidated/mariadb/init/02-zone-data.sql`
- edicao manual em banco MariaDB do DNS antigo
- alteracao ad-hoc por `pdnsutil` sem refletir nos JSONs

## Regras de publicacao

### Regra 1

`results.com.br` publico nunca pode expor `10.10.2.x`.

### Regra 2

A visao publica de `results.com.br` deve responder:

- `results.com.br -> 201.6.110.53`
- `www.results.com.br -> 201.6.110.53`
- `r-observe.results.com.br -> 201.6.110.53`
- `mx1.results.com.br -> 201.6.110.53`
- `mx2.results.com.br -> 201.6.110.53`
- `imap.results.com.br -> 201.6.110.53`
- `smtp.results.com.br -> 201.6.110.53`
- `results.com.br MX -> mx1 / mx2`
- `_dmarc.results.com.br TXT -> v=DMARC1; p=none; rua=mailto:dmarc@results.com.br`

### Regra 3

A visao interna de `results.com.br` deve responder apenas para redes privadas:

- `mx1.results.com.br -> 10.10.2.3`
- `mx2.results.com.br -> 10.10.2.23`
- `imap.results.com.br -> 10.10.2.3`
- `smtp.results.com.br -> 10.10.2.3`

O apex `results.com.br` pode continuar publico tambem para clientes internos.

### Regra 4

Split-horizon de `results.com.br` deve ser feito por:

- `_networks.json`: define as redes privadas
- `_views.json`: associa a view `internal`
- `results.com.br..internal.json`: define a variante interna

## Firewall obrigatorio

Regras verificadas no `10.10.2.254` em `2026-06-08`:

### DNAT DNS

- UDP `53` em `192.168.0.2` -> `10.10.2.1:53`
- TCP `53` em `192.168.0.2` -> `10.10.2.1:53`

Contadores observados:

- regra UDP com trafego alto
- regra TCP com trafego alto

Isso confirma uso real do firewall para DNS externo.

### DNAT mail

- TCP `25` -> `10.10.2.3:25`
- TCP `26` -> `10.10.2.3:26`
- TCP `110` -> `10.10.2.3:110`
- TCP `143` -> `10.10.2.3:143`
- TCP `465` -> `10.10.2.3:465`
- TCP `587` -> `10.10.2.3:587`
- TCP `993` -> `10.10.2.3:993`
- TCP `995` -> `10.10.2.3:995`

### DNAT web

- TCP `80` -> `10.10.2.60:80`
- TCP `443` -> `10.10.2.60:443`

### DNAT VPN

- TCP `5555` -> `10.10.2.30:5555`

## ARP esperado no firewall

Os IPs migrados para o host novo devem apontar para o mesmo MAC do `10.10.2.30`.

Validado no firewall:

- `10.10.2.1 -> e2:5d:88:81:ec:d2`
- `10.10.2.3 -> e2:5d:88:81:ec:d2`
- `10.10.2.30 -> e2:5d:88:81:ec:d2`
- `10.10.2.60 -> e2:5d:88:81:ec:d2`

Se algum IP critico responder com outro MAC, ha risco de conflito de ARP e
comportamento imprevisivel.

## Procedimento correto de mudanca

1. Editar os arquivos JSON em `dns-consolidated/zones/`.
2. Se a mudanca afetar split-horizon, revisar tambem:
   - `_networks.json`
   - `_views.json`
   - `results.com.br..internal.json`
3. Sincronizar `dns-consolidated/` para `10.10.2.30`.
4. Garantir que `rendered/` foi recriado com a `DNS_API_KEY` correta.
5. Aplicar as zonas no host:

```bash
cd /opt/results/infra/dns-consolidated
bash scripts/apply-zones-api.sh 127.0.0.1
bash scripts/apply-views-api.sh 127.0.0.1
```

6. Validar localmente.
7. Validar externamente.
8. So encerrar a mudanca quando os checks externos estiverem corretos.

## Validacao local obrigatoria

No `10.10.2.30`:

```bash
dig @127.0.0.1 -p 5300 +short results.com.br A
dig @127.0.0.1 -p 5300 +short mx1.results.com.br A
dig @127.0.0.1 -p 5300 +short mx2.results.com.br A
dig @127.0.0.1 -p 5300 +short imap.results.com.br A
dig @127.0.0.1 -p 5300 +short _dmarc.results.com.br TXT

dig @127.0.0.1 +short results.com.br A
dig @127.0.0.1 +short mx1.results.com.br A
dig @127.0.0.1 +short mx2.results.com.br A
dig @127.0.0.1 +short imap.results.com.br A
```

Esperado:

- authoritative (`5300`) deve refletir a zona aplicada
- `dnsdist` local (`53`) deve entregar a variante interna para clientes privados

## Validacao no firewall obrigatoria

No `10.10.2.254`:

```bash
iptables -t nat -L PREROUTING -n -v --line-numbers
ip neigh show | sort
```

Conferir:

- DNAT `53` -> `10.10.2.30:53`
- DNAT `25/465/587/993/...` -> `10.10.2.3`
- DNAT `80/443` -> `10.10.2.60`
- MAC dos IPs migrados apontando para o host novo

## Validacao externa obrigatoria

Sempre validar por browser e por resolvedores publicos.

Para `r-observe.results.com.br`, lembrar que DNS correto nao garante aplicacao
saudavel: depois da validacao dos resolvedores publicos, ainda e necessario
confirmar HTTP/HTTPS no endpoint publicado e o proxy de borda em `10.10.2.60`.

### Registro.br

Abrir no browser:

- painel/ferramenta de DNS do `registro.br`
- `whois` do dominio no `registro.br`

Esperado:

- delegacao correta para `ns1.results.com.br` e `ns2.results.com.br`
- nameservers sem `TIMEOUT`
- autoridade reconhecida

### MXToolbox

Abrir no browser:

- `SuperTool` para `results.com.br`
- `MX Lookup`
- `DMARC Lookup`
- `SMTP Test`/checks de mail quando aplicavel

Esperado:

- apex sem IP privado
- MX correto em `mx1` e `mx2`
- `_dmarc.results.com.br` presente
- sem apontamento de MX para nomes antigos

### Resolvedor publico

Checar tambem:

```bash
curl -s 'https://dns.google/resolve?name=results.com.br&type=A'
curl -s 'https://dns.google/resolve?name=results.com.br&type=MX'
curl -s 'https://dns.google/resolve?name=_dmarc.results.com.br&type=TXT'
curl -s 'https://dns.google/resolve?name=mx1.results.com.br&type=A'
```

## Criterio de aceite

Uma mudanca de DNS so esta concluida quando tudo abaixo for verdadeiro:

- `results.com.br` publico responde `201.6.110.53`
- MX publico responde `mx1` e `mx2`
- `mx1`, `mx2`, `imap`, `smtp` publicos respondem `201.6.110.53`
- `_dmarc.results.com.br` existe
- a view interna devolve `10.10.2.3` / `10.10.2.23` apenas para redes privadas
- firewall mantem DNAT correto
- `registro.br` nao acusa timeout de nameserver
- `MXToolbox` nao acusa IP privado/ausencia de DMARC

## Problemas que nao podem voltar

Nunca repetir:

- publicar `10.10.2.x` na zona publica de `results.com.br`
- alterar so SQL antigo e achar que mudou o DNS real
- mudar `dnsdist` para usar Views sem migrar o `pdns-auth`
- encerrar mudanca sem validar `registro.br` e `MXToolbox`
- ignorar timeout de nameserver delegado

## Pendencia atual registrada

Em `2026-06-08`, a autoridade publica em `201.6.110.53` ja responde a zona
corrigida, mas `ns1.results.com.br` (`177.68.74.176`) ainda apresentava
timeout em consulta direta e o `registro.br` ainda refletia isso.

Nao considerar o ambiente plenamente normalizado enquanto:

- `177.68.74.176` nao responder adequadamente como autoritativo
  ou
- a delegacao no `registro.br` nao for ajustada para remover/substituir esse NS
