# Prevencao: Arquivos que Viram Diretorios (Docker Bind Mount Footgun)

## Problema

Arquivos como `mail-certbot-entrypoint.sh` e `mail-certs-bootstrap.sh`
periodicamente aparecem como **diretorios vazios** no servidor, causando
falhas em containers que montam esses arquivos via bind mount.

## Causa raiz

**Docker cria um DIRETORIO quando o source de um bind mount nao existe.**

Sequencia de eventos:

1. O `docker-compose.yml` define um bind mount:
   ```yaml
   volumes:
     - ./scripts/mail-certbot-entrypoint.sh:/scripts/mail-certbot-entrypoint.sh:ro
   ```

2. Durante o deploy, o rsync ainda nao copiou o arquivo, mas o
   `docker compose up` ja tenta criar o container.

3. Docker verifica o path `./scripts/mail-certbot-entrypoint.sh` no host.
   Se o caminho **nao existe**, Docker **cria um DIRETORIO** nesse path
   (comportamento padrao do Docker para bind mounts).

4. Agora o path e um diretorio vazio no host. O rsync, quando executado
   depois, nao consegue substituir um diretorio por um arquivo (ou pior:
   o rsync com `--delete --relative` coloca o arquivo **dentro** do
   diretorio, mas o container continua montando o diretorio vazio).

5. O container que monta esse path como arquivo inicia com um diretorio
   no lugar do script → `exec format error` ou restart loop.

## Por que acontece com frequencia

- O `rsync` no script `docker-deploy.sh` usa `--delete`, mas **roda antes**
  do `docker compose up`. Porem, se o deploy falhar na primeira tentativa
  e o Docker ja criou os diretorios, o rsync da segunda tentativa pode
  nao conseguir reverter diretorios para arquivos.

- Quando o deploy do mail stack (`docker-compose.mail.yml`) e feito com
  um project name diferente, o Docker pode nao encontrar volumes/redes e
  recriar tudo, incluindo os diretorios de bind mount.

- O `mail-certs-bootstrap` container usa `restart: "no"` e cria o
  diretorio na primeira execucao. Se o bootstrap falhar, o diretorio
  permanece.

## Prevencao

### 1. Script de verificacao pre-deploy

Executar **antes** de cada deploy:

```bash
# Local
./scripts/check-file-integrity.sh

# Remoto (via SSH)
ssh root@10.10.2.30 'cd /opt/results/infra && ./scripts/check-file-integrity.sh'
```

Se encontrar diretorios onde deveriam ser arquivos, aborta com erro e
mostra instrucoes de correcao.

### 2. Corrigir manualmente

```bash
# No servidor
rm -rf /opt/results/infra/scripts/mail-certbot-entrypoint.sh
# Depois copiar o arquivo correto via scp ou rsync
```

### 3. Regra no deploy

Adicionar ao script de deploy uma verificacao previa que checa se
arquivos de bind mount sao realmente arquivos antes de executar
`docker compose up`.

### 4. Arquivos protegidos

A lista completa de arquivos que **DEVEM** ser arquivos (definida em
`scripts/check-file-integrity.sh`):

| Arquivo | Montado em |
|---------|-----------|
| `scripts/mail-certbot-entrypoint.sh` | `results-mail-certbot` |
| `scripts/mail-certs-bootstrap.sh` | `results-mail-certs-bootstrap` |
| `mail/postfix/main.cf.template` | `results-mail-postfix` |
| `mail/dovecot/dovecot.conf.template` | `results-mail-dovecot` |
| `mail/postfix/master.cf.template` | `results-mail-postfix` |
| `mail/postfix/*.cf` | `results-mail-postfix` |
| `roundcube/config.inc.php` | `results-joomla` |
| `apache/*.template` | `secure-httpd` |
| `joomla/*` | `results-joomla` |
| `lsyncd/*` | `joomla-lsyncd`, `httpd-lsyncd` |
| `edge-sni/haproxy.cfg` | `edge-sni` |

## Deteccao

```bash
# Encontrar TODOS os diretorios com nomes de arquivo no servidor
ssh root@10.10.2.30 \
  'find /opt/results/infra -type d \( -name "*.sh" -o -name "*.template" -o -name "*.php" -o -name "*.conf" -o -name "*.cfg" -o -name "*.lua" \) ! -path "*/nextcloud/*" ! -path "*/node_modules/*"'
```

## Referencias

- [Docker bind mount docs](https://docs.docker.com/storage/bind-mounts/):
  "If the source is a file and the destination doesn't exist, Docker
  creates a directory at the destination."
- Este documento: `docs/FILE_TO_DIRECTORY_PREVENTION.md`
- Script: `scripts/check-file-integrity.sh`
