# Infra Results

A documentacao principal deste repositorio foi centralizada em [docs/README.md](docs/README.md).

## Diretório Operacional

O diretório de trabalho e deploy deste repositório é sempre `/opt/results/infra`, tanto localmente quanto no servidor. Execute comandos operacionais a partir desse caminho e mantenha a configuração de runtime sincronizada nele.

## Nota Operacional

Se o servico Docker do host for reiniciado, reconecte a VPN antes de validar,
subir novamente ou depurar qualquer stack desta infraestrutura que dependa de
recursos remotos da rede `10.10.2.x`.

Na pratica, um restart do Docker pode deixar containers em pe, mas sem acesso a
backends remotos enquanto a VPN nao volta. Isso afeta especialmente stacks que
dependem de MariaDB, LDAP, DNS legado ou outros servicos fora do host local.

Para a propria stack de VPN, o container `rvpn` deve permanecer com politica de
restart `always`. Isso precisa ficar explicito no `docker-compose.vpn.yml`
porque o servico e parte do caminho de acesso operacional ao host e nao pode
depender de start manual depois de reboot ou recreate.

Documentos operacionais principais:

- [docs/README.md](docs/README.md)
- [docs/MAIL_MIGRATION_10.10.2.2.md](docs/MAIL_MIGRATION_10.10.2.2.md)
- [docs/MAIL_DNS_CUTOVER_10.10.2.15.md](docs/MAIL_DNS_CUTOVER_10.10.2.15.md)
- [docs/MAIL_IP_CUTOVER_10.10.2.30.md](docs/MAIL_IP_CUTOVER_10.10.2.30.md)
- [docs/MAIL_LEGACY_IP_DEANNOUNCE_CHECKLIST.md](docs/MAIL_LEGACY_IP_DEANNOUNCE_CHECKLIST.md)
- [docs/MIGRATION_MAP_10.10.2.55.md](docs/MIGRATION_MAP_10.10.2.55.md)

Para a stack de mail, o fluxo atual de certificados via `mod_md` do Apache esta documentado em [docs/README.md](docs/README.md) e [docs/MAIL_MIGRATION_10.10.2.2.md](docs/MAIL_MIGRATION_10.10.2.2.md).

## Nota de desenvolvimento (dev)

Adicionei ajustes para o modo de desenvolvimento local a fim de evitar erros do tipo `EMFILE` (too many open files) quando a stack `r-observe` roda múltiplos serviços com watchers:

- `r-observe/api/nodemon.json` e `r-observe/discovery/nodemon.json`: ativam `legacyWatch` (polling) para o `nodemon`.
- `r-observe/*/vite.config.js`: passei a usar `server.watch.usePolling = true` e a ignorar `node_modules` e `.git`.

Motivo: em máquinas com limite de inotify/file descriptors baixo, o watch padrão abre muitos watchers e causa `EMFILE`. O polling reduz a necessidade de watchers do kernel e estabiliza o ambiente de desenvolvimento local.

Para reverter esta alteração, remova os `nodemon.json` e restaure `vite.config.js` ao comportamento padrão de watch.
