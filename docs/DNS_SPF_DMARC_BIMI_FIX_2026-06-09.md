# Correção DNS: SPF, DMARC e BIMI — results.com.br

**Data**: 2026-06-09
**Servidor**: 10.10.2.30 (mexico.results.intranet)
**Ferramenta de validação**: https://mxtoolbox.com/SuperTool.aspx

---

## Diagnóstico inicial

Ao rodar `mx:results.com.br` no MXToolbox, foram encontrados os seguintes problemas:

| Registro | Status inicial | Problema |
|---|---|---|
| MX | OK | mx1 (10), mx2 (20) resolvendo corretamente |
| SPF | **AUSENTE** | Nenhum registro TXT `v=spf1` |
| DMARC | `p=none` | Política de monitoramento apenas, sem enforcement |
| BIMI | **AUSENTE** | Sem registro `default._bimi` |

---

## Ações realizadas

### 1. Criação do registro SPF

**Valor**: `v=spf1 mx ip4:201.6.110.53 ~all`

**Comando**:
```bash
# Zona externa (results.com.br)
docker exec pdns-auth sh -c 'pdnsutil replace-rrset results.com.br results.com.br TXT 300 "\"v=spf1 mx ip4:201.6.110.53 ~all\""'

# Zona interna (results.com.br..internal) — view para clientes internos
docker exec pdns-auth sh -c 'pdnsutil replace-rrset results.com.br..internal results.com.br TXT 300 "\"v=spf1 mx ip4:201.6.110.53 ~all\""'
```

**Explicação**:
- `mx`: autoriza os IPs dos servidores MX (mx1, mx2)
- `ip4:201.6.110.53`: autoriza explicitamente o IP público de borda
- `~all`: softfail para qualquer outro IP (recomenda rejeição, mas não bloqueia)

**Validação MXToolbox (spf:results.com.br)**: 12/12 checks OK
- SPF Record Published ✅
- SPF Syntax Check: The record is valid ✅
- SPF Multiple Records: Less than two records found ✅
- Sem loops, sem PTR, sem lookups excessivos ✅

---

### 2. Ajuste da política DMARC

**Antes**: `v=DMARC1; p=none; rua=mailto:dmarc@results.com.br`
**Depois**: `v=DMARC1; p=quarantine; rua=mailto:dmarc@results.com.br`

**Comando**:
```bash
# Zona externa
docker exec pdns-auth sh -c 'pdnsutil replace-rrset results.com.br _dmarc.results.com.br TXT 300 "\"v=DMARC1; p=quarantine; rua=mailto:dmarc@results.com.br\""'

# Zona interna
docker exec pdns-auth sh -c 'pdnsutil replace-rrset results.com.br..internal _dmarc.results.com.br TXT 300 "\"v=DMARC1; p=quarantine; rua=mailto:dmarc@results.com.br\""'
```

**Explicação**:
- `p=quarantine`: e-mails que falham DMARC vão para quarentena/spam
- `p=quarantine` é pré-requisito para BIMI funcionar
- Relatórios continuam sendo enviados para `dmarc@results.com.br`

**Validação MXToolbox**: "DMARC Quarantine/Reject policy enabled" ✅

---

### 3. Criação do registro BIMI

**Valor**: `v=BIMI1; l=https://results.com.br/bimi-logo.svg; a=`

**Logo SVG** criado em:
```
/var/lib/docker/volumes/infra-httpd_site-data/_data/bimi-logo.svg
```
(volume `infra-httpd_site-data`, montado em `/usr/local/apache2/htdocs` no container `secure-httpd`)

Acessível via: `https://results.com.br/bimi-logo.svg`

**Comando DNS**:
```bash
# Zona externa
docker exec pdns-auth sh -c 'pdnsutil add-record results.com.br default._bimi.results.com.br TXT 300 "\"v=BIMI1; l=https://results.com.br/bimi-logo.svg; a=\""'

# Zona interna
docker exec pdns-auth sh -c 'pdnsutil add-record results.com.br..internal default._bimi.results.com.br TXT 300 "\"v=BIMI1; l=https://results.com.br/bimi-logo.svg; a=\""'
```

**Explicação**:
- `v=BIMI1`: versão do protocolo BIMI
- `l=...`: URL do logo em SVG (HTTPS obrigatório, SVG Tiny 1.2/PS)
- `a=`: campo opcional para URL do VMC (Verified Mark Certificate) — deixado vazio

**Validação MXToolbox**: "BIMI Brand Logo found" ✅

---

### 4. Reconexão da VPN

Durante o processo, a conexão VPN caiu. Foi restaurada com:

```bash
sudo bash /opt/results/infra/vpn/rvpn.sh
```

---

## Arquivos alterados

| Arquivo | Alteração |
|---|---|
| `dns-consolidated/zones/results.com.br.json` | +SPF, +BIMI, DMARC `p=quarantine` |
| `dns-consolidated/zones/results.com.br..internal.json` | +SPF, +BIMI, DMARC `p=quarantine` |
| `/var/lib/docker/volumes/infra-httpd_site-data/_data/bimi-logo.svg` | Logo BIMI criado |

---

## Resultado final — MXToolbox 100% verde

MX lookup (`mx:results.com.br`):

| Teste | Resultado |
|---|---|
| DNS Record Published | Status Ok — DNS Record found |
| DMARC Record Published | Status Ok — DMARC Record found |
| DMARC Policy | Status Ok — DMARC Quarantine/Reject policy enabled |
| BIMI Record Published | Status Ok — BIMI Brand Logo found |

SPF lookup (`spf:results.com.br`): 12/12 checks aprovados.

---

## Zona final (results.com.br, visão externa)

```
results.com.br.          300 IN A      201.6.110.53
results.com.br.          300 IN NS     ns1.results.com.br.
results.com.br.          300 IN NS     ns2.results.com.br.
results.com.br.          300 IN MX     10 mx1.results.com.br.
results.com.br.          300 IN MX     20 mx2.results.com.br.
results.com.br.          300 IN TXT    "v=spf1 mx ip4:201.6.110.53 ~all"
default._bimi.results.com.br. 300 IN TXT "v=BIMI1; l=https://results.com.br/bimi-logo.svg; a="
_dmarc.results.com.br.   300 IN TXT    "v=DMARC1; p=quarantine; rua=mailto:dmarc@results.com.br"
mx1.results.com.br.      300 IN A      201.6.110.53
mx2.results.com.br.      300 IN A      201.6.110.53
ns1.results.com.br.      300 IN A      177.68.74.176
ns2.results.com.br.      300 IN A      201.6.110.53
www.results.com.br.      300 IN A      201.6.110.53
r-observe.results.com.br. 300 IN A     201.6.110.53
imap.results.com.br.     300 IN A      201.6.110.53
smtp.results.com.br.     300 IN A      201.6.110.53
```

---

## Pendências conhecidas (não bloqueantes)

| Item | Descrição |
|---|---|
| IP único para MX | mx1 e mx2 resolvem para o mesmo IP público (201.6.110.53) — sem redundância real |
| PTR do mx2 | Apenas mx1.results.com.br. tem PTR reverso configurado |
| VMC (BIMI) | Certificado VMC não configurado — necessário para Gmail exibir o logo |
