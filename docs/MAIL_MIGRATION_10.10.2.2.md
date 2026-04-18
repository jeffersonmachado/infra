# Mapeamento de Migração do Servidor de E-mail 10.10.2.2

## Resumo do legado

O host `10.10.2.2` é o `srvmail0.results.intranet`, rodando `CentOS 6.5` com:

- `Postfix 2.6.6`
- `Courier IMAP/POP3`
- `maildrop`
- `amavisd-new`
- `spamassassin`
- `opendkim`
- `opendmarc`

Portas publicadas observadas:

- `25/tcp`
- `465/tcp`
- `587/tcp`
- `110/tcp`
- `995/tcp`
- `143/tcp`
- `993/tcp`

## Persistência atual

- contas virtuais no MySQL `10.10.2.99`, schema `results`
- tabelas principais: `mailbox`, `alias`, `domain`
- armazenamento físico em `Maildir` sob `/gv/<dominio>/<usuario>/Maildir/`
- `home` lógico no banco: `/home/postfix`
- no host legado, `/home/postfix` é symlink para `/gv`

## LDAP observado no legado

- autenticação PAM via `pam_ldap` para SMTP, IMAP e POP3
- `LDAP_URI`: `ldap://srvldap0.results.intranet,ldap://srvldap1.results.intranet,ldap://srvldap2.results.intranet,ldap://srvldap3.results.intranet`
- `LDAP_BASEDN`: `dc=results,dc=com,dc=br`
- `LDAP_BINDDN`: `cn=administrador,dc=results,dc=com,dc=br`
- a senha informada para bind foi confirmada no legado, mas permanece fora do repositório

## Estratégia da nova stack

A migração nova usa componentes mais atuais, mas preserva o modelo funcional do legado:

- `postfix` em container próprio
- `dovecot` em container próprio para IMAP/POP3/LMTP/Sieve
- `rspamd` + `redis` para antispam moderno com Bayes e rede neural persistida
- `ldap` em container separado para suporte adicional de autenticação

Compatibilidade mantida:

- leitura das tabelas `mailbox`, `alias` e `domain` no MySQL atual
- uso de `Maildir` como formato de caixa
- possibilidade de reaproveitar a árvore de diretórios existente após sync para o novo host

## Antispam por IA na stack nova

O legado usava `amavisd + spamassassin`. Na stack nova, isso foi substituído por `Rspamd` com:

- classificador Bayes em `Redis`
- rede neural (`neural`) com treinamento persistido em `Redis`
- autolearn para spam, junk e ham

Na prática, o antispam por IA fica no container `rspamd`, sem introduzir um serviço externo frágil no caminho crítico do SMTP.

## Diferenças intencionais

- `Courier` é substituído por `Dovecot`
- `maildrop` e o pipeline `amavis + spamassassin` são substituídos por `LMTP + Rspamd`
- `LDAP` entra como backend adicional em container separado, conforme requisito novo
- `mx1` e `mx2` podem rodar como containers `Postfix` distintos, compartilhando o restante da stack

## Itens ainda necessários antes do corte

1. sincronizar `/gv` do legado para o host novo
2. emitir certificados válidos para `imap.results.com.br`, `mx1.results.com.br` e `mx2.results.com.br`
3. validar esquema de senha do campo `mailbox.password` para compatibilidade no `Dovecot`
4. decidir se aliases e contas passarão a nascer no MySQL atual, no LDAP novo, ou em ambos
5. mover os IPs `10.10.2.3` e `10.10.2.23` para o host novo no momento do corte

## Sync completo do legado

O repositorio agora inclui o script [scripts/sync-maildata-from-legacy.sh](/opt/results/infra/scripts/sync-maildata-from-legacy.sh) para copiar toda a arvore `/gv/` do legado para `/var/mail/vhosts/` no host novo.

Fluxo previsto:

1. executar `precheck` para validar acesso SSH ao legado, espaco no host novo e disponibilidade do compose de mail
2. executar `sync` para puxar a arvore inteira via `rsync`
3. repetir o `sync` quantas vezes for necessario antes do corte final

Comandos:

```bash
cd /opt/results/infra
export DEPLOY_HOST=10.10.2.30
export DEPLOY_USER=root
export DEPLOY_SSH_PASSWORD='***'
export LEGACY_HOST=10.10.2.2
export LEGACY_USER=root
export LEGACY_SSH_PASSWORD='***'

./scripts/sync-maildata-from-legacy.sh precheck
./scripts/sync-maildata-from-legacy.sh sync
```

## Situação atual de certificados no host novo

Os certificados do mail passaram a ser derivados do certificado valido que o Apache do edge ja mantem via `mod_md`.

Desenho atual:

- a stack sobe primeiro um bootstrap autoassinado curto no volume `mail-certs`
- o Apache mantem o certificado SAN de `mx1.results.com.br`, `mx2.results.com.br` e `imap.results.com.br` no volume externo `infra-httpd_md-data`
- o container `mail-certbot` sincroniza esse material para `mail-certs`
- `Postfix` e `Dovecot` passam a ler esse volume diretamente e recarregam quando o certificado muda

No DNS atual de `results.com.br`, os MX publicados são `mx1.results.com.br` e `mx2.results.com.br`, ambos com prioridade `0`. Se o corte novo concentrar os dois nomes no mesmo host, o certificado definitivo precisa incluir os dois FQDNs, além de `imap.results.com.br`.

Os IPs históricos do serviço de mail continuam sendo `10.10.2.3` para `mx1` e `10.10.2.23` para o secundário. Portanto, o ambiente remoto da stack foi alinhado para usar esses IPs no corte final, e não `10.10.2.60`. Enquanto esses endereços ainda responderem em outros hosts, a configuração deve ser tratada como alvo de corte, não como bind já aplicável no host novo.

## Testes de todos os serviços

O repositório agora inclui o script [scripts/test-mail-services.sh](/opt/results/infra/scripts/test-mail-services.sh) para validar a pilha de e-mail ponta a ponta.

Cobertura prevista:

- SMTP `25`
- SMTPS `465`
- Submission `587` com `STARTTLS`
- IMAP `143`
- IMAPS `993`
- POP3 `110`
- POP3S `995`
- ManageSieve `4190`
- bind/search em LDAP
- acesso ao MySQL do backend de mail
- status do `docker compose` da stack nova, quando executado no host da stack