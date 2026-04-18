# DNS Interno para split-horizon de results.com.br

Esta stack cria dois containers CoreDNS para resolver internamente os nomes criticos de `results.com.br` e `results.intranet`.

Objetivo operacional:

- `results.com.br` e `www.results.com.br` respondem para `10.10.2.60`
- `mx1.results.com.br` e `imap.results.com.br` respondem para `10.10.2.3`
- `mx2.results.com.br` responde para `10.10.2.23`
- nomes internos criticos de `results.intranet` continuam resolvendo para os IPs privados esperados

## Execucao local na stack HTTP

Os containers `apache` e `joomla` passam a usar os resolvers internos `results-internal-dns-a` e `results-internal-dns-b` pela rede Docker `dns-internal`.

Subir ou reaplicar:

```bash
cd /opt/results/infra
docker compose up -d internal-dns-a internal-dns-b joomla apache
```

Validar dentro dos containers:

```bash
docker exec secure-httpd getent hosts imap.results.com.br mx1.results.com.br results.com.br
docker exec results-joomla getent hosts imap.results.com.br mx1.results.com.br results.com.br
```

## Publicacao futura em 10.10.2.1 e 10.10.2.20

O arquivo [docker-compose.dns-internal.yml](/opt/results/infra/docker-compose.dns-internal.yml) publica `53/tcp` e `53/udp` em `10.10.2.1` e `10.10.2.20`.

Use somente depois de retirar esses IPs dos DNS antigos, para evitar conflito de ARP e bind:

```bash
cd /opt/results/infra
docker compose -f docker-compose.yml -f docker-compose.dns-internal.yml up -d internal-dns-a internal-dns-b
```

## Observacao importante

Neste momento, `10.10.2.1` e `10.10.2.20` ainda respondem na rede e já estão ouvindo em `53/tcp`. Portanto, a publicacao direta nesses IPs deve ser tratada como etapa de corte, não como mudanca segura imediata.