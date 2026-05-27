# DNS Interno

O CoreDNS interno foi totalmente substituido pelo stack PowerDNS consolidado em
`dns-consolidated/`. Nao use mais os servicos `internal-dns-a` e
`internal-dns-b` como caminho operacional para resolver `results.com.br` ou
`results.intranet`.

O desenho atual e:

- `pdns-auth`: PowerDNS Authoritative, com backend MariaDB em `10.10.2.99`
- `pdns-recursor`: PowerDNS Recursor para resolucao interna, split-horizon e cache
- `dns-dnsdist`: entrada unica DNS, roteando consultas para authoritative ou recursor

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
