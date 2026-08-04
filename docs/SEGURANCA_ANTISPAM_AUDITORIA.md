# Auditoria de Segurança e Antispam — Mail Stack

**Data**: 2026-06-15  
**Servidor**: 10.10.2.30 (mexico.results.intranet)

---

## Resumo

| Área | Nota | Achados | Status |
|------|------|---------|--------|
| TLS/SSL | ✅ | TLS 1.0/1.1 permitido no Postfix | **Corrigido** (>=TLSv1.2, ciphers high) |
| SPF | ✅ | `results.com.br` usa `~all` (softfail) | **Corrigido** (-all) |
| DKIM | ✅ | Sem signing ativo (só verificação) | **Corrigido** (signing ativo) |
| DMARC | ✅ | `p=quarantine` em todos os domínios | OK |
| BIMI | ✅ | Tag `a=` vazia | **Corrigido** (a=self) |
| Rspamd threshold | ✅ | Reject=15 | **Corrigido** (reject=12) |
| Firewall | ⚠️ | `iptables INPUT ACCEPT`, porta 8443 em 0.0.0.0 | Pendente (externo) |
| Antispam | ✅ | Rspamd com Bayes, Neural, ClamAV, DNSBL, greylisting | OK |
| Relay | ✅ | Fechado, apenas SASL auth e mynetworks | OK |
| Rate limit | ✅ | Configurado em todas as portas | OK |
| Portas internas | ⚠️ | 3080 (r-observe-proxy) e 8085 (lam) em 0.0.0.0 | Pendente (externo) |

---

## 1. Postfix

### TLS — ⚠️ Corrigir

| Config | Valor | Recomendação |
|--------|-------|---------------|
| `smtpd_tls_mandatory_protocols` | `>=TLSv1` | `>=TLSv1.2` |
| `smtpd_tls_mandatory_ciphers` | `medium` | `high` |
| `smtpd_tls_security_level` (porta 25) | `may` | Aceitável (MTA público precisa de compatibilidade) |

**Ação**: Adicionar `smtpd_tls_mandatory_protocols = >=TLSv1.2` e `smtpd_tls_mandatory_ciphers = high` no `main.cf.template`.

### Relay — ✅ OK

- `smtpd_relay_restrictions = permit_mynetworks, permit_sasl_authenticated, reject_unauth_destination`
- Sem relay aberto
- SASL autenticado via Dovecot

### Rate Limiting — ✅ OK

- Porta 25: 30 conexões, 60/min, 100 msgs/min
- Porta 587/465: 10 conexões, 10/min, 20 msgs/min
- `anvil_rate_time_unit = 60s`

### Outros — ✅ OK

- `disable_vrfy_command = yes`
- `smtpd_helo_required = yes`
- Postscreen com DNSBL ativo (Spamhaus, SpamCop, Barracuda)
- `smtpd_tls_auth_only = yes`

---

## 2. Dovecot

### SSL — ✅ OK

- Let's Encrypt certificates
- `ssl = required`
- Porta 993 (IMAPS) e 995 (POP3S)

### Plaintext listener — ⚠️ Atenção

- Porta 10143 (plaintext IMAP para Roundcube/PHP 7.2)
- **Exposto apenas internamente** — sem bind público
- Roundcube conecta via `imap.results.com.br:10143` (rede interna)

---

## 3. Rspamd

### Módulos ativos — ✅ OK

| Módulo | Status |
|--------|--------|
| Bayesian (Redis) | ✅ `min_learns=200`, autolearn |
| Neural Network | ✅ Redis backend, threshold 0.92 |
| ClamAV | ✅ Antivírus, `action=reject` |
| Greylisting | ✅ Score 4 |
| DNSBL/RBL | ✅ Spamhaus, SpamCop, Barracuda via Postfix |
| Rate Limiting (Rspamd) | ✅ `dynamic_rate_limit=true` |
| Allowlist local | ✅ 1 endereço (Bradesco) |
| Blocklist local | ✅ 3 domínios de marketing |
| Composite rules | ✅ 6 regras para campanhas de spam autenticado |

### Thresholds — ⚠️ Revisar

| Config | Valor | Recomendação |
|--------|-------|---------------|
| `reject` | 15 | Considerar 12 |
| `add_header` | 6 | OK |
| `greylist` | 4 | OK |
| `authenticated_outbound.reject` | 12 | OK (mais restritivo para outbound) |

Com threshold 15, alguns spams podem passar. O ideal é monitorar e baixar gradualmente.

### DKIM Signing — ❌ Ausente

- **Sem DKIM signing ativo** no Rspamd
- Domínios `olimpicshape.com.br` e `escolamaat.com.br` têm chave pública no DNS
- `results.com.br` sem DKIM público
- **Sem chave privada configurada no Rspamd** para assinar emails de saída

**Ação**: Configurar DKIM signing no Rspamd para `results.com.br`.

---

## 4. DNS — Registros de Segurança

### SPF

| Domínio | Registro | Avaliação |
|---------|----------|-----------|
| `results.com.br` | `v=spf1 mx ip4:201.6.110.53 ~all` | ⚠️ Softfail |
| `olimpicshape.com.br` | `v=spf1 a mx ip4:smtp.results.com.br -all` | ✅ Hardfail |
| `escolamaat.com.br` | `v=spf1 a mx ip4:smtp.results.com.br -all` | ✅ Hardfail |

**Ação**: Alterar `results.com.br` de `~all` para `-all`.

### DKIM

| Domínio | DNS | Signing |
|---------|-----|---------|
| `results.com.br` | ❌ Sem registro | ❌ |
| `olimpicshape.com.br` | ✅ `mail._domainkey` | ❌ Sem signing |
| `escolamaat.com.br` | ✅ `mail._domainkey` | ❌ Sem signing |

### DMARC

| Domínio | Política | Relatórios | Avaliação |
|---------|----------|------------|-----------|
| `results.com.br` | `p=quarantine` | `dmarc@results.com.br` | ✅ |
| `olimpicshape.com.br` | `p=quarantine` | `postmaster@results.com.br` | ✅ |
| `escolamaat.com.br` | `p=quarantine` | `postmaster@results.com.br` | ✅ |

### BIMI

| Domínio | Status |
|---------|--------|
| `results.com.br` | ⚠️ Configurado mas tag `a=` vazia (certificado VMC ausente) |
| Outros | ❌ Não configurado |

---

## 5. Rede e Firewall

### Portas expostas

| Porta | Bind | Serviço | Avaliação |
|-------|------|---------|-----------|
| 25 | 10.10.2.3, 10.10.2.23 | SMTP (mx1, mx2) | ✅ |
| 53 | 10.10.2.1, 10.10.2.20, 10.10.2.30 | DNS | ✅ |
| 443 | 10.10.2.30, 10.10.2.60 | HTTPS | ✅ |
| 465 | 10.10.2.3, 10.10.2.23 | SMTPS | ✅ |
| 587 | 10.10.2.3, 10.10.2.23 | Submission | ✅ |
| 993 | 10.10.2.3 | IMAPS | ✅ |
| 995 | 10.10.2.3 | POP3S | ✅ |
| 4190 | 10.10.2.3 | ManageSieve | ✅ |
| **8443** | **0.0.0.0** | Icinga2 master API | ⚠️ Restringir (ver `/docker/compose/ripabx/`) |
| 3080 | 0.0.0.0 | r-observe proxy | ⚠️ Expor só internamente |
| 8085 | 0.0.0.0 | LDAP Account Manager | ⚠️ Expor só internamente |

### Firewall

- `iptables INPUT` policy = **ACCEPT** — sem regras de firewall
- Confiar apenas nas interfaces de rede e Docker

---

## Recomendações (ordem de prioridade)

### ✅ Corrigido (2026-06-15)

1. ✅ **TLS**: `>=TLSv1.2` + `ciphers=high` — `mail/postfix/main.cf.template`
2. ✅ **SPF**: `results.com.br` `~all` → `-all` — PowerDNS
3. ✅ **DKIM signing**: configurado no Rspamd + chave pública no DNS — `mail/rspamd/local.d/dkim_signing.conf`
4. ✅ **Rspamd threshold**: `reject` 15 → 12 — `mail/rspamd/local.d/actions.conf`
5. ✅ **BIMI**: `a=` → `a=self` — PowerDNS

### 🔴 Pendente (externo ao workspace)

1. ✅ **Porta 8443** (`ripabx-icinga2-master`): `/docker/compose/ripabx/docker-compose.yml` — corrigido para `10.10.2.30:8443`
2. ✅ **Porta 8085** (`lam`): `/opt/docker/ldap/docker-compose.yml` — corrigido para `10.10.2.30:8085`
3. ⚠️ **Porta 3080** (`r-observe-proxy`): container sem compose (nginx:1.25-alpine iniciado manualmente). Recriar com `-p 10.10.2.30:3080:80` ou usar iptables.
4. **Firewall**: adicionar regras básicas de iptables

### 🟢 Desejável

5. **BIMI**: obter certificado VMC para exibição do logo em todos os provedores
6. **DMARC**: considerar `p=reject` após monitoramento dos relatórios
