# Corte Operacional dos IPs de Mail no 10.10.2.30

Este runbook executa o corte final da stack de mail para os IPs historicos do servico:

- `10.10.2.3` para o MX principal e servicos de cliente
- `10.10.2.23` para o MX secundario

O objetivo aqui nao e redesenhar a stack. A stack ja esta pronta. O foco e assumir os IPs no host novo `10.10.2.30` com uma sequencia curta, repetivel e com rollback simples.

## Pre-condicoes

- os hosts antigos que ainda anunciam `10.10.2.3` e `10.10.2.23` precisam estar desligados ou com esses IPs removidos
- o repositorio remoto em `10.10.2.30:/opt/results/infra` precisa estar atualizado
- o arquivo `/.env.remote-10.10.2.30-mail` precisa permanecer com:
  - `MAIL_BIND_IP=10.10.2.3`
  - `MAIL_MX2_BIND_IP=10.10.2.23`
- o operador precisa ter acesso SSH como `root` ao `10.10.2.30`

Se ainda faltar a etapa de retirar os IPs do legado, use antes o checklist [MAIL_LEGACY_IP_DEANNOUNCE_CHECKLIST.md](MAIL_LEGACY_IP_DEANNOUNCE_CHECKLIST.md).

## Script de apoio

O script operacional deste corte e [scripts/mail-cutover-10.10.2.30.sh](/opt/results/infra/scripts/mail-cutover-10.10.2.30.sh).

Ele suporta tres modos:

- `precheck`: valida compose, interface e listeners atuais
- `cutover`: adiciona os IPs no host novo e reaplica a stack mail
- `rollback`: derruba a stack mail e remove os IPs do host novo

## Sequencia recomendada

1. Validar se os IPs antigos realmente sairam de rede.
2. Executar precheck remoto.
3. Executar o cutover.
4. Rodar a bateria de testes contra `10.10.2.3`.
5. Rodar a bateria de testes contra `10.10.2.23`.
6. Ajustar os nomes auxiliares no DNS autoritativo.

## Comandos exatos

### 1. Precheck

```bash
cd /opt/results/infra
DEPLOY_SSH_PASSWORD='resu100gabao' ./scripts/mail-cutover-10.10.2.30.sh precheck
```

Se a interface nao for detectada automaticamente, repetir com `--interface`:

```bash
cd /opt/results/infra
DEPLOY_SSH_PASSWORD='resu100gabao' ./scripts/mail-cutover-10.10.2.30.sh precheck --interface eth0
```

### 2. Cutover

```bash
cd /opt/results/infra
DEPLOY_SSH_PASSWORD='resu100gabao' ./scripts/mail-cutover-10.10.2.30.sh cutover
```

O script faz isso no host remoto:

- detecta a interface default
- adiciona `10.10.2.3/24`
- adiciona `10.10.2.23/24`
- valida o compose com [docker-compose.mail.yml](/opt/results/infra/docker-compose.mail.yml)
- reaplica o projeto `infra-mail`
- mostra `ip addr` e `docker compose ps`

### 3. Validacao funcional

Executar a suite contra o IP principal:

```bash
cd /opt/results/infra
LDAP_BIND_PASSWORD='resu100vsza' MAIL_MYSQL_PASSWORD='resu1@@dba' ./scripts/test-mail-services.sh --host 10.10.2.3 --ldap-uri ldap://srvldap0.results.intranet --ldap-base-dn 'dc=results,dc=com,dc=br' --ldap-bind-dn 'cn=administrador,dc=results,dc=com,dc=br' --ldap-bind-password 'resu100vsza' --mysql-host 10.10.2.99 --mysql-db results --mysql-user resultsdba --mysql-password 'resu1@@dba'
```

Executar a suite contra o IP secundario para validar SMTP:

```bash
cd /opt/results/infra
LDAP_BIND_PASSWORD='resu100vsza' MAIL_MYSQL_PASSWORD='resu1@@dba' ./scripts/test-mail-services.sh --host 10.10.2.23 --ldap-uri ldap://srvldap0.results.intranet --ldap-base-dn 'dc=results,dc=com,dc=br' --ldap-bind-dn 'cn=administrador,dc=results,dc=com,dc=br' --ldap-bind-password 'resu100vsza' --mysql-host 10.10.2.99 --mysql-db results --mysql-user resultsdba --mysql-password 'resu1@@dba'
```

### 4. Ajuste final no DNS autoritativo

Depois de o cutover estar validado, alinhar no DNS `10.10.2.15`:

- `mx1.results.com.br A 10.10.2.3`
- `mx2.results.com.br A 10.10.2.23`
- `imap.results.com.br A 10.10.2.3`

O contexto detalhado de DNS esta em [MAIL_DNS_CUTOVER_10.10.2.15.md](MAIL_DNS_CUTOVER_10.10.2.15.md).

## Rollback

Se houver falha no cutover, remover os IPs do host novo e derrubar a stack de mail:

```bash
cd /opt/results/infra
DEPLOY_SSH_PASSWORD='resu100gabao' ./scripts/mail-cutover-10.10.2.30.sh rollback
```

Depois disso, reativar os IPs antigos nos hosts legados apenas se o retorno deles fizer parte da janela aprovada.

## Observacao importante

Nao execute o `cutover` enquanto `10.10.2.3` e `10.10.2.23` ainda responderem na rede a partir dos servidores antigos. Isso continua sendo o principal risco operacional do procedimento.