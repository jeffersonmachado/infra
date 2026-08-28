# Dovecot FTS Flatcurve — Indexação Full-Text Search

**Data**: 2026-06-15  
**Servidor**: 10.10.2.30 (mexico.results.intranet)  
**Container**: `results-mail-dovecot`

---

## Problema

A busca no webmail (Roundcube) estava extremamente lenta porque o Dovecot não tinha FTS (Full-Text Search) configurado. Cada busca varria **358 mil arquivos** sequencialmente no Maildir (16.5 GB).

## Diagnóstico

| Métrica | Antes |
|---------|-------|
| Emails (results.com.br) | 357.815 |
| Emails (todos domínios) | ~390.000 |
| Armazenamento | 16.5 GB |
| `dovecot.index` | 235 |
| `fts-flatcurve` | 0 |
| `mail_plugins` (imap) | `quota imap_quota imap_sieve` |
| Busca no webmail | Varredura sequencial (lento) |

## Solução

Habilitado **Dovecot FTS Flatcurve** (backend Xapian) para indexação full-text.

### Plugin compilado

- **Fonte**: `slusarz/dovecot-fts-flatcurve` v1.0.5
- **Xapian**: 1.4.18
- **Dovecot**: 2.3.21
- **SO do container**: Debian 11 (Bullseye)

### Configuração adicionada (`dovecot.conf`)

```
protocol imap {
  mail_plugins = quota imap_quota imap_sieve fts fts_flatcurve
}

plugin {
  fts = flatcurve
  fts_languages = pt en es
  fts_tokenizers = generic email-address
  fts_flatcurve_substring_search = yes
  fts_autoindex = yes
  fts_enforced = no
}
```

> **Correção 2026-08-15:** a versão original desta config incluía
> `namespace inbox { fts_enabled = yes }`, que **não é uma setting válida** do
> Dovecot 2.3.21 — `doveconf` rejeita com `Unknown setting` e o Dovecot não
> sobe. Removida do template. O FTS é ativado apenas pelo `mail_plugins` do
> protocolo imap + bloco `plugin {}`.

### Índices criados

| Domínio | Usuários |
|---------|----------|
| `results.com.br` | 44 |
| `olimpicshape.com.br` | 4 |
| `escolamaat.com.br` | 5 |
| `botecoviravolta.com.br` | 1 |
| **Total** | **54** |

## Resultado

| Métrica | Antes | Depois |
|---------|-------|--------|
| Busca no webmail | Varredura 358K arquivos | Consulta índice Xapian |
| `dovecot.index` | 235 | 322 |
| `fts-flatcurve` dirs | 0 | 53 |
| Armazenamento | 16.5 GB | 19 GB (+2.5 GB índices) |
| Tempo de busca | Minutos | < 1 segundo |

## Arquivos alterados

| Arquivo | Mudança |
|---------|---------|
| `mail/dovecot/Dockerfile` | Adicionada compilação do flatcurve durante build |
| `mail/dovecot/dovecot.conf.template` | `mail_plugins` com `fts fts_flatcurve`, config completa FTS |
| `scripts/fts-index-all.sh` | Script de indexação para todos os usuários (novo) |

## Script de indexação

Local: `/opt/results/infra/scripts/fts-index-all.sh`  
Servidor: `/root/fts-index-all.sh`

O script itera sobre todos os diretórios de usuário no Maildir e executa:
1. `doveadm fts rescan -u <user>` — agenda reindexação FTS
2. `doveadm index -u <user> '*'` — constrói os índices

Log: `/var/log/dovecot-fts-index.log` no servidor.

Execução com `nohup` para sobreviver a queda de SSH.

## Manutenção

- **Novos emails**: indexados automaticamente (`fts_autoindex = yes`)
- **Reindexar todos**: executar `/root/fts-index-all.sh`
- **Reindexar um usuário**:
  ```bash
  docker exec results-mail-dovecot doveadm \
    -o 'mail_plugins=fts fts_flatcurve' \
    -o 'plugin/fts=flatcurve' \
    -o 'plugin/fts_languages=pt en es' \
    -o 'plugin/fts_tokenizers=generic email-address' \
    fts rescan -u <usuario>@results.com.br
  docker exec results-mail-dovecot doveadm \
    -o 'mail_plugins=fts fts_flatcurve' \
    -o 'plugin/fts=flatcurve' \
    -o 'plugin/fts_languages=pt en es' \
    -o 'plugin/fts_tokenizers=generic email-address' \
    index -u <usuario>@results.com.br '*'
  ```
- **Otimizar índices**:
  ```bash
  docker exec results-mail-dovecot doveadm \
    -o 'mail_plugins=fts fts_flatcurve' \
    fts optimize -A
  ```

## Prevenção de regressão (adicionado em 2026-08-15)

O incidente de 05–15/08/2026 (busca lenta por 10 dias) passou despercebido
porque nada verificava o FTS depois do deploy. Três camadas de proteção:

1. **Healthcheck do Compose** (`docker-compose.mail.yml`, serviço `dovecot`):
   além da porta IMAP, exige `fts = flatcurve` no `doveconf -n` e
   `libxapian.so.30` no `ldconfig` — container fica `unhealthy` se o FTS
   regredir.
2. **Smoke test pós-deploy** (`scripts/docker-deploy-local-images.sh`,
   projeto `infra-mail`): roda `doveadm fts rescan` com o plugin e **aborta o
   deploy com erro** se o plugin não carregar.
3. **Bateria de testes** (`scripts/test-mail-services.sh`): novo teste
   `check_fts_search` mede um `SEARCH TEXT` via IMAPS autenticado e falha se
   passar de `FTS_MAX_SECONDS` (default 15s) — com FTS a resposta é < 2s.

## Próximo rebuild

No próximo `docker compose up --build` do stack de mail, o flatcurve será compilado automaticamente e o FTS já virá habilitado. Após o rebuild, reexecutar a indexação com `/root/fts-index-all.sh`.

> **Atenção (bug corrigido em 2026-08-15):** o `Dockerfile` purga
> `libxapian-dev` com `--auto-remove`, o que removia também o runtime
> `libxapian30` — o plugin `lib21_fts_flatcurve_plugin.so` ficava presente na
> imagem mas falhava no `dlopen` (`libxapian.so.30: cannot open shared object
> file`). O `apt-mark manual libxapian30` antes do purge resolve. Foi exatamente
> esse o estado da imagem `infra-dovecot` de 2026-08-05: FTS configurado no
> template novo, mas plugin quebrado — e, pior, o template dentro da imagem era
> antigo (sem FTS), então de 05/08 a 15/08 a busca do webmail voltou a varrer
> os Maildirs sequencialmente. Sempre validar após rebuild:
> `docker exec results-mail-dovecot doveadm -o mail_plugins="fts fts_flatcurve" -o plugin/fts=flatcurve fts rescan -u postmaster@results.com.br`
> (não pode retornar `Fatal`).
