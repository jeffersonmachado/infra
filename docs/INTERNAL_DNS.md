# DNS Interno

O desenho DNS vigente em producao foi atualizado para **PowerDNS 5.1 +
LMDB + Views + dnsdist** no host `10.10.2.30` (`mexico.results.intranet`).

Este arquivo e apenas a visao curta. O runbook operacional completo esta em:

- [DNS_PRODUCTION_RUNBOOK.md](/opt/results/infra/docs/DNS_PRODUCTION_RUNBOOK.md)

## Estado atual

- `pdns-auth`: `powerdns/pdns-auth-51:5.1.1`
- backend do authoritative: `LMDB` local
- `Views`: habilitado para split-horizon nativo
- `pdns-recursor`: `powerdns/pdns-recursor-51`
- `dnsdist`: entrada unica em `:53`

## Fonte de verdade

Nao use mais MariaDB/ProxySQL como fonte de verdade do DNS autoritativo.

Os dados validos hoje sao:

- `dns-consolidated/zones/*.json`
- `dns-consolidated/zones/_networks.json`
- `dns-consolidated/zones/_views.json`
- `dns-consolidated/scripts/apply-zones-api.sh`
- `dns-consolidated/scripts/apply-views-api.sh`

O arquivo `dns-consolidated/mariadb/init/02-zone-data.sql` ficou apenas como
historico de migracao. Ele nao popula o PowerDNS 5 em producao.

## Regras de operacao

- Zonas publicas devem ser alteradas nos arquivos JSON e aplicadas via API.
- Split-horizon de `results.com.br` deve ser mantido por Views, nao por banco SQL.
- IPs `10.10.2.x` nao podem aparecer na visao publica de `results.com.br`.
- Validacao externa e obrigatoria em `registro.br` e `MXToolbox` antes de
  encerrar qualquer mudanca de DNS ou e-mail.

## Observacao importante

Em `2026-06-08`, a autoridade publica ativa em `201.6.110.53` ja passou a
responder a zona corrigida, mas `ns1.results.com.br` (`177.68.74.176`) ainda
seguia com timeout direto. Nao considerar o ambiente 100% normalizado enquanto
essa delegacao nao estiver respondendo ou for removida/ajustada no `registro.br`.
