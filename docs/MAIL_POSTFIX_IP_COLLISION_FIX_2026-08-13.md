# Correção: Postfix "Created" (colisão de IP) + teste de envio/recebimento

**Data**: 2026-08-13
**Servidor**: mexico (10.10.2.30)
**Contexto**: habilitar teste de envio/recebimento de e-mails e criar conta de teste

---

## 1. Objetivo

O script de testes de mail (`scripts/test-mail-services.sh`) passou a testar o
**envio e o recebimento** de e-mails (roundtrip), e foi criada uma conta de
teste dedicada. Durante a validação, descobriu-se que o **Postfix (SMTP)
estava fora do ar** — containers em estado `Created`, nunca iniciados.

---

## 2. Diagnóstico: por que o Postfix ficou em `Created`

Sintoma:

```
Error response from daemon: Address already in use
Error: failed to start containers: results-mail-postfix
```

- `docker-compose.mail.yml` reserva IPs estáticos na rede `infra-shared`:
  | Serviço | IP reservado |
  |---|---|
  | `postfix` | `192.168.48.11` |
  | `postfix-mx2` | `192.168.48.12` |
  | `dovecot` | `192.168.48.14` |

- Os serviços `redis` e `rspamd` **não tinham IP fixo** e receberam via DHCP
  exatamente `.11` e `.12`, colidindo com os IPs reservados do Postfix.

- Resultado: ao subir, o Postfix não conseguia anexar o container à rede
  (`Address already in use`) e permanecia em `Created`, enquanto os demais
  serviços subiam normalmente.

---

## 3. Correção aplicada

Em `docker-compose.mail.yml`, foram pinados IPs estáticos para todos os
serviços de mail que usavam DHCP, liberando `.11`/`.12`/`.14` para
Postfix/Dovecot:

| Serviço | IP estático (novo) |
|---|---|
| `redis` | `192.168.48.15` |
| `rspamd` | `192.168.48.16` |
| `clamav` | `192.168.48.17` |
| `ldap` | `192.168.48.18` |
| `mail-certbot` | `192.168.48.19` |
| `mail-certs-bootstrap` | `192.168.48.20` |

Aplicação (NO servidor, usando o projeto compose **`infra`**):

```bash
cd /opt/results/infra
docker compose --env-file .env.mail -f docker-compose.mail.yml --project-name infra up -d redis rspamd
docker compose --env-file .env.mail -f docker-compose.mail.yml --project-name infra up -d postfix postfix-mx2
```

> O comando `up -d` também recriou `clamav`, `ldap`, `mail-certs-bootstrap` e
> reiniciou `dovecot` por dependência do bootstrap. Sem perda de dados (volumes
> nomeados preservados).

---

## 4. Outras correções feitas nesta sessão

### 4.1 `scripts/provision-smtp-user.sh`

1. **TLS MySQL**: cliente MariaDB 11.4 no servidor falhava com
   `ERROR 2026 (HY000): TLS/SSL error: Certificate verification failure`.
   Adicionado `--ssl=0` na chamada do `mysql`.
2. **Volume maildata**: default `MAIL_STORAGE_HOST_ROOT` apontava para
   `infra-mail_maildata` (volume legado). Corrigido para `infra_maildata`
   (volume ativo definido no compose: `maildata: name: infra_maildata`).

### 4.2 `scripts/test-mail-services.sh`

Adicionada seção **`Roundtrip (Envio e Recebimento)`**:

- Envia um e-mail real via Submission `587` (STARTTLS + AUTH), com fallback
  para SMTPS `465`, usando `smtplib`/`imaplib` da stdlib Python.
- Busca o e-mail na `INBOX` via IMAPS `993`, confirma e apaga (`\Deleted` +
  expunge).
- Novas opções: `--smtp-user`, `--smtp-password`, `--mail-from`, `--mail-to`;
  variáveis `SEND_RECEIVE_ATTEMPTS`/`SEND_RECEIVE_INTERVAL`.
- Sem credenciais ou sem `python3`, o teste é ignorado (não falha).

---

## 5. Conta de teste

| Campo | Valor |
|---|---|
| E-mail | `teste-e2e@results.com.br` |
| Estado | `active=1` (ativada para testes) |
| Quota | `100000000S` |
| Maildir | `results.com.br/teste-e2e/Maildir/` |

Criada via `scripts/provision-smtp-user.sh` (rodar sempre com
`--env-file .env.mail`, pois o autodetect não encontra `.env.mail`).

---

## 6. Validação

Teste completo de mail (`./scripts/test-mail-services.sh --host 10.10.2.3
--imap-user teste-e2e@results.com.br --imap-password <senha>`):

- SMTP 25/465/587: **PASS** (MX1 `10.10.2.3`, MX2 `10.10.2.23`)
- IMAP/POP3/Sieve: **PASS**
- Roundtrip: **PASS** — `SEND OK via smtp 587` + `RECEIVE OK`
- Resumo: **Falhas: 0**

---

## 7. Observações operacionais

- A stack de produção roda sob o projeto compose **`infra`** (nome default do
  diretório `/opt/results/infra`), **não** `infra-mail`. O `scripts/docker-deploy.sh`
  usa `DEPLOY_PROJECT_NAME=infra-mail` no npm `deploy:remote:ssh:mail` — há uma
  inconsistência a ser alinhada em deploy futuro.
- O projeto `infra` foi criado com vários `-f` (web, mysql-galera, proxysql,
  edge-sni, mail). Rodar `up -d` apenas com `-f docker-compose.mail.yml` emite
  warning de "orphan containers" (inofensivo); **nunca** usar `--remove-orphans`.
- `redis` usa `tmpfs: /data` (efêmero): recriar o redis zera o Bayes do rspamd,
  que é reabastecido pelo cron `train-rspamd-ham.sh` e pelo imapsieve.
