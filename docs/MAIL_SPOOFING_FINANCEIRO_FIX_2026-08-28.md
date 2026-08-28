# Correção: spoofing de financeiro@results.com.br + hardening anti-spam/DNS

**Data**: 2026-08-28
**Servidor**: mexico (10.10.2.30)
**Contexto**: relato de spam "enviado" por `financeiro@results.com.br` com retornos (bounces) estranhos.

---

## 1. Diagnóstico

**Não houve comprometimento.** `financeiro@results.com.br` é um **alias de grupo**
(→ `wedila`, `paloma`, `jefferson`, `paulow`, `gislene`), não uma caixa de login.

O que de fato ocorria:

1. **Spoofing externo**: spammers (plataformas de bulk, ex. `mail.noreply2.site` /
   `85.121.x`, e faixas OVH `185.126.x`/`185.30.x`/`57.129.x`) forjam
   `financeiro@results.com.br` como remetente (*From* e envelope *MAIL FROM*).
2. **Rejeição correta**: o Rspamd/MX rejeita o spam com `554 5.7.1 Spam message rejected`.
3. **Backscatter (os "retornos estranhos")**: o MTA externo, ao receber a rejeição,
   gera um *bounce/DSN* para o remetente forjado (`financeiro@results.com.br`),
   que o nosso MX aceita e distribui aos 5 membros do grupo.

Evidências coletadas (96h):

- **0** autenticações SASL bem-sucedidas para `financeiro` (65 falhas de
  brute-force a partir de `212.20.46.167`).
- **0** mensagens `from=<financeiro@...>` enviadas para fora (relay externo).
- Filas dos 2 MX vazias.
- Brute-force amplo contra todo o domínio (635 falhas em `shawnatow`,
  255 em `jefferson`, etc.).

---

## 2. Correções aplicadas

### 2.1 Bloqueio de IPs no Postfix

Fonte da verdade: `mail/postfix/client_access.cidr` (COPY no Dockerfile). IPs
bloqueados (ação `reject`), aplicados nos 2 MX + `postfix reload`:

| IP | Motivo |
|---|---|
| `212.20.46.167` | brute-force SASL (financeiro) |
| `85.121.125.72` | `mail.noreply2.site` — relay de spoofing |
| `85.121.53.234` | `mail.noreply2.site` — relay de spoofing |

### 2.2 fail2ban habilitado

Jail `results-postfix-auth` (`/etc/fail2ban/jail.d/results.local`) estava
`enabled = false`; habilitado e fail2ban reiniciado. Lê os JSON logs do Docker
(`/var/lib/docker/containers/*/*-json.log`), bane em `maxretry=6`/`findtime=10m`,
`bantime=1h`, portas 25/465/587. Já contabiliza falhas.

### 2.3 SPF e DMARC (anti-spoofing)

Atualizados em **ambas as views** do split-horizon
(`results.com.br.` externa e `results.com.br..internal`):

| Registro | Antes | Depois |
|---|---|---|
| SPF | `v=spf1 mx ip4:201.6.110.53 ~all` | `v=spf1 mx ip4:201.6.110.53 -all` |
| DMARC | `v=DMARC1; p=quarantine; ...` | `v=DMARC1; p=reject; ...` |

SOA serial bumpado para `2026082801`.

### 2.4 Aliases de relatório/abuso criados (tabela `results.alias`)

| Endereço | Destino |
|---|---|
| `dmarc@results.com.br` | `suporte@results.com.br` |
| `abuse@results.com.br` | `suporte@results.com.br` |

`postmaster@results.com.br` já existia → `suporte@results.com.br`.

---

## 3. Validação (MXToolbox — tudo verde)

| Check | Resultado |
|---|---|
| SPF | `-all` — sintaxe válida, sem itens após ALL, lookups OK |
| DMARC | `p=reject` — "Quarantine/Reject policy enabled", sintaxe válida |
| MX | mx1/mx2 → 201.6.110.53 — record found |
| Blacklist | `201.6.110.53` — 0 listagens em 60 blacklists |

---

## 4. Observações técnicas

- **Bug pré-existente** no `dns-consolidated/scripts/apply-zones-api.sh`: para a
  zona interna (`results.com.br..internal`, nome **sem** ponto final) o script
  montava `results.com.br..internal.` (com ponto) e o PATCH não achava → POST HTTP 409.
  Corrigido: `zone_id="$name"` (usa o `.name` exato do JSON). A atualização da
  view interna de hoje foi feita via PATCH direto em `/zones/results.com.br..internal`
  antes do fix do script; doravante o script já resolve ambos os casos.
- `scripts/postfix-ip-access.sh` (tabela MySQL `postfix_client_access`) está
  **desconectado** do runtime: o Postfix usa `cidr:/etc/postfix/client_access.cidr`,
  não a tabela MySQL.
