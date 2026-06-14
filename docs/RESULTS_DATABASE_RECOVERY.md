# Recuperacao de Bancos de Dados Legados

Este documento descreve o metodo generico para recuperar **qualquer banco de
dados** (MySQL/MariaDB) do ambiente legado no hypervisor `africasul`
(`10.10.2.29`) para o servidor atual `10.10.2.30` (mexico).

O dump `all.sql` (2.5 GB) encontrado no disco da VM `mysql` contem **todos os
bancos** do ambiente legado: `results`, `joomla`, `roundcubemail`, `bacula`,
`accs`, entre outros.

⚠️ **Este processo NAO é especifico do banco `results`** — o mesmo dump e o
mesmo metodo servem para recuperar qualquer banco listado abaixo.

## ⚡ Método Rápido (Recomendado): Container `mariadb-forensics`

**A partir de 2026-06-10, este é o método oficial e mais rápido.** Os dados
já estão vivos no container `mariadb-forensics` no próprio `10.10.2.30` —
**não precisa acessar o hypervisor.**

### Exemplo: restaurar o banco `bacula`

```bash
# Método 1: Script robusto (recomendado)
nohup /opt/results/forensics/restore-20260610/restore-banco.sh bacula &

# Método 2: Pipeline direto (sobrevive a quedas de SSH)
cat /opt/results/forensics/restore-20260610/bacula-dump.sql \
  | perl -pe 's/\x60bacula\x60/\x60bacula_stage\x60/g' \
  | docker exec -i srvmysql0 mysql -u root -presu100dba bacula_stage
```

> ⚡ **Descoberta 2026-06-10:** Pipelines locais com `docker exec -i`
> sobrevivem a quedas de SSH sem `nohup`/`screen`/`tmux`. O `docker exec -i`
> mantém o stdin aberto e o shell pai fica em background automático. Isso
> economiza tokens do Copilot — não precisa reexecutar comandos após
> desconexão.

**O tráfego é 100% local** — pipe em memória no mesmo host, sem VPN, sem rede.

> Para a referência completa com todos os 26 bancos disponíveis e método
> detalhado, veja a seção [Fonte de Verdade Definitiva](#-fonte-de-verdade-definitiva-container-mariadb-forensics)
> no final deste documento.

---

## Resumo Executivo (método legado via dump)

- O dump `all.sql` no hypervisor `africasul` (`10.10.2.29`) contem **todos os
  bancos legados** num unico arquivo de 2.5 GB.
- O PowerDNS atual usa **LMDB local** e **Views nativas** — **nao** deve
  voltar a usar MariaDB como fonte de verdade.
- O cluster MariaDB atual em `10.10.2.30` esta **assimetrico** e nao deve ser
  tratado como fonte confiavel para nenhum banco.
- A recuperacao de qualquer banco segue o mesmo fluxo: localizar a secao no
  dump, extrair, importar em staging, validar, promover.

## Bancos Disponiveis no Dump `all.sql`

O dump `all.sql` contem **multiplos bancos** concatenados. Para listar todos
os bancos disponiveis:

```bash
# No servidor mexico (10.10.2.30), apos copiar o dump:
grep "^-- Current Database:" /opt/results/infra/all.sql
```

Bancos identificados no dump:

| Banco | Prefixo Joomla | Tabelas | Status |
|---|---|---|---|
| `results` | `okg7s_` (109 tabelas) | 169 | ✅ Importado |
| `joomla` | — | — | ❌ Dump parcial/ausente |
| `roundcubemail` | — | — | A verificar |
| `bacula` | — | — | A verificar |
| `accs` | — | — | A verificar |

> **Descoberta critica (2026-06-09):** O banco `results` contem **109 tabelas
> com prefixo `okg7s_`** que sao os dados **originais e completos do Joomla**
> (extensoes, modulos, menus, usuarios, template config). O banco `joomla`
> atual no cluster tem apenas tabelas estruturais e esta vazio deconfiguracao.

### Exemplo: Verificar bancos e localizar secoes

```bash
# 1. Listar todos os bancos no dump
grep -n "^-- Current Database:" /opt/results/infra/all.sql

# 2. Para extrair um banco especifico (ex: roundcubemail):
#    Anote os numeros de linha de inicio e fim da secao

# 3. Extrair e importar (substitua NOME_DO_BANCO e LINHAS):
tail -n +LINHA_INICIO /opt/results/infra/all.sql | head -n NUM_LINHAS | \
  docker exec -i srvmysql0 mysql -u root -presu100dba NOME_DO_BANCO
```

---

## Estado Atual do DNS

O DNS autoritativo atual deve permanecer nesta arquitetura:

- `pdns-auth` em `powerdns/pdns-auth-51`
- backend `LMDB`
- zonas aplicadas via API REST a partir de `dns-consolidated/zones/*.json`
- split-horizon via Views nativas

Referencias:

- [dns-consolidated/docker-compose.yml](/opt/results/infra/dns-consolidated/docker-compose.yml:1)
- [dns-consolidated/pdns-auth/pdns.conf](/opt/results/infra/dns-consolidated/pdns-auth/pdns.conf:1)
- [docs/DNS_PRODUCTION_RUNBOOK.md](/opt/results/infra/docs/DNS_PRODUCTION_RUNBOOK.md:1)

Conclusao operacional:

- o MariaDB atual pode voltar a hospedar bancos legados completos
- isso **nao** deve interferir no PowerDNS, porque o PDNS atual nao usa mais
  `gmysql`

## Onde Esta o Legado

Hypervisor:

- `africasul.results.intranet`
- IP: `10.10.2.29`

Credenciais usadas na verificacao:

- usuario: `root`
- senha: `@fr!c@Sul`

Definicoes das VMs antigas:

- `/root/mysql`
- `/root/mysql2`

Discos encontrados:

- `/home/vs_files/mysql.img`
- `/home/vs_files/mysql_banco.img` -> link para `/home1/vs_files/mysql_banco.img`
- `/home/vs_files/mysql_var.img`
- `/home1/vs_files/mysql2.img`
- `/home1/vs_files/mysql2_banco.img`
- `/home1/vs_files/mysql2_var.img`

As VMs `mysql` e `mysql2` nao estao rodando hoje, mas os discos ainda existem.

## Montagem Somente Leitura Usada na Analise

Foi feita montagem somente leitura dos discos de dados:

- `/mnt/mysql_banco_ro`
- `/mnt/mysql2_banco_ro`

Essas montagens mostraram duas fontes relevantes:

### VM `mysql`

Em `/mnt/mysql_banco_ro` foram encontrados:

- `all.sql` com `2.5G`
- `results_nodata.sql` com `213K`
- `powerdns.sql`

### VM `mysql2`

Em `/mnt/mysql2_banco_ro` foram encontrados:

- `todos.sql` com `1.3G`
- `powerdns.sql`
- `bacula.sql`

## Melhor Fonte de Recuperacao

A melhor fonte logica encontrada para o banco completo `results` e:

- `/mnt/mysql_banco_ro/all.sql`

Motivos:

- e o dump mais recente e mais volumoso encontrado no legado
- esta no disco da VM `mysql`
- o mesmo disco tambem guarda `results_nodata.sql`, indicando que ali foi
  mantida uma visao completa do schema `results`

Fonte secundaria util:

- `/mnt/mysql_banco_ro/results_nodata.sql`

Uso recomendado:

- `all.sql` para recuperar **estrutura + dados**
- `results_nodata.sql` para recuperar **so a estrutura** ou comparar schema

## Evidencia de que o `results` Antigo Tinha Mail

No dump `results_nodata.sql` aparecem explicitamente as tabelas:

- `alias`
- `domain`
- `mailbox`

Tambem foram encontrados os arquivos fisicos no datadir legado:

- `/mnt/mysql_banco_ro/mysql2/results/alias.frm`
- `/mnt/mysql_banco_ro/mysql2/results/domain.frm`
- `/mnt/mysql_banco_ro/mysql2/results/mailbox.frm`
- `/mnt/mysql2_banco_ro/mysql2/results/alias.frm`
- `/mnt/mysql2_banco_ro/mysql2/results/domain.frm`
- `/mnt/mysql2_banco_ro/mysql2/results/mailbox.frm`

Isso confirma que o `results` antigo era um banco misto, contendo:

- tabelas de mail
- tabelas de DNS antigo / PowerDNS
- tabelas de aplicacao diversas

## Estado Atual do MariaDB em Producao

No host `10.10.2.30`, os tres nos atuais nao estao consistentes para o schema
`results`.

### `srvmysql0`

Tem apenas as tabelas de DNS:

- `cryptokeys`
- `domainmetadata`
- `domains`
- `records`
- `supermasters`
- `tsigkeys`

### `srvmysql1`

Tem as tabelas de DNS e restos de mail:

- `cryptokeys`
- `domain`
- `domainmetadata`
- `domains`
- `mailbox`
- `records`
- `supermasters`
- `tsigkeys`

Conteudo confirmado:

- `results.mailbox` existe
- ha `1` linha:
  - `jefferson@results.com.br`
  - `maildir = results.com.br/jefferson`

### `srvmysql2`

No estado observado, nem o database `results` estava presente.

## Leitura Tecnica do Problema Atual

O ambiente atual ficou com tres historias diferentes ao mesmo tempo:

1. O PowerDNS antigo gravava informacoes no MariaDB `results`.
2. O PowerDNS novo foi migrado para LMDB e deixou o `results` de ser fonte de
   verdade para DNS.
3. O MySQL atual preservou restos diferentes do schema `results` em nos
   diferentes.

Consequencia:

- o cluster atual nao pode ser tratado como fonte completa e confiavel do
  banco `results`
- a recuperacao correta deve partir do legado montado em `10.10.2.29`

## Estrutura Nova Desejada

Na estrutura nova, o arranjo correto deve ser:

- `pdns-auth` usa apenas LMDB
- `results` volta a existir integralmente no MariaDB para aplicacoes, mail e
  legados que ainda dependem dele
- qualquer tabela antiga de DNS dentro do `results` passa a ser **inerte**
  para o PDNS atual

Em outras palavras:

- podemos restaurar o banco `results` completo no MariaDB
- isso nao recoloca o DNS em MariaDB

## Caminho Recomendado para Recuperar o Banco Completo

### Etapa 1: staging

Restaurar primeiro em staging, por exemplo:

- `results_legacy_stage`

Objetivo:

- validar integridade do dump
- contar tabelas
- comparar contra o `results` parcial atual
- identificar conflitos de charset, engine ou nomes reservados

### Etapa 2: comparacao

Comparar:

- numero total de tabelas
- existencia de `alias`, `domain`, `mailbox`
- tabelas de Joomla/webmail/VoIP que hoje nao existem mais no cluster

### Etapa 3: promocao

Depois da validacao:

- promover o schema restaurado para `results`
- ou substituir somente o `results` atual
- ou manter `results_legacy_stage` para migracao gradual por aplicacao

### Recomendacao de seguranca

Nao fazer restauracao direta sobre o `results` atual sem:

- backup do estado atual
- janela de manutencao
- validacao do impacto em Postfix, Dovecot, Roundcube e legados web

## Metodo Generico de Recuperacao (vale para QUALQUER banco)

O processo abaixo é **identico para qualquer banco** — `results`, `joomla`,
`roundcubemail`, `bacula`, `accs`, etc.

### 1. Transferir o dump do hypervisor

```bash
# No 10.10.2.30 (mexico):
export SSHPASS="@fr!c@Sul"
sshpass -e rsync -avP --progress \
  -e 'ssh -o StrictHostKeyChecking=no -o HostKeyAlgorithms=+ssh-rsa -o PubkeyAuthentication=no' \
  root@10.10.2.29:/mnt/mysql_banco_ro/all.sql /opt/results/infra/all.sql
```

- **Tempo**: ~3m30s para 2.66 GB (~12 MB/s)
- **⚠️ Senhas com `!`**: usar `export SSHPASS="..."` + `sshpass -e` (nunca
  inline no shell, o `!` causa expansão do histórico)

### 2. Listar bancos disponiveis no dump

```bash
grep -n "^-- Current Database:" /opt/results/infra/all.sql
```

Exemplo de saida:
```
84951:-- Current Database: `results`
95403:-- Current Database: `roundcubemail`
108485:-- Current Database: `results`
```

Cada banco pode aparecer em **multiplas secoes** — anote todas as linhas.

### 3. Extrair e importar um banco especifico

```bash
# Sintaxe: tail -n +LINHA_INICIO | head -n TAMANHO_DA_SECAO
# O tamanho da secao = LINHA_FIM - LINHA_INICIO

# Exemplo: importar roundcubemail (secao das linhas 95403 a 108484)
tail -n +95403 /opt/results/infra/all.sql | head -n 13082 | \
  docker exec -i srvmysql0 mysql -u root -presu100dba roundcubemail
```

**Regras de importacao:**
- **NUNCA usar ProxySQL** (`:6033`) — o `query_timeout` mata DDL (`CREATE`,
  `DROP`, `ALTER`)
- Usar `docker exec -i srvmysql0 mysql` direto no container master
- `tail -n +LINHA | head -n N` é muito mais rapido que `sed -n 'A,Bp'` para
  arquivos grandes (~2.5 GB)
- Para bancos que ja existem, importar para um nome staging primeiro (ex:
  `roundcubemail_stage`), validar, depois promover

### 4. Validar antes de promover

```bash
# Verificar tabelas importadas
docker exec srvmysql0 mysql -u root -presu100dba -e \
  "SELECT table_name FROM information_schema.tables WHERE table_schema='NOME_DO_BANCO'"

# Contar registros
docker exec srvmysql0 mysql -u root -presu100dba -e \
  "SELECT COUNT(*) FROM NOME_DO_BANCO.tabela_critica"
```

### 5. Promover (swap atomico)

```bash
# Metodo: RENAME TABLE individual (evita downtime)
docker exec srvmysql0 mysql -u root -presu100dba -e \
  "RENAME TABLE banco_atual TO banco_backup, banco_stage TO banco_atual"
```

### 6. Montagem manual dos discos (se necessario)

Se o dump `all.sql` nao estiver acessivel, montar os discos legados:

1. Localizar a VM no hypervisor em `/root/<vm>`
2. Identificar discos em `/home/vs_files` e `/home1/vs_files`
3. Montar os discos de dados em modo somente leitura:
   ```bash
   mount -o ro,loop /home/vs_files/mysql_banco.img /mnt/mysql_banco_ro
   ```
4. Procurar por:
   - `all.sql` (dump completo)
   - `*.sql` (dumps individuais)
   - Datadir fisico com `.frm`, `.ibd`, `.MYD`, `.MYI`
5. Escolher a melhor fonte:
   - **Dump logico completo** (`all.sql`) — sempre preferivel
   - Datadir fisico — apenas se nao houver dump

## Decisao Operacional Recomendada

Para **todos os bancos legados**, a fonte oficial de recuperacao e:

- **dump principal**: `/mnt/mysql_banco_ro/all.sql` (2.5 GB, contem todos os bancos)
- **schema auxiliar**: `/mnt/mysql_banco_ro/results_nodata.sql` (apenas `results`)
- **prova fisica complementar**: `/mnt/mysql_banco_ro/mysql2/` (datadir)

Para o DNS:

- manter **somente** LMDB como fonte de verdade

## Proximo Passo Recomendado (generico)

Para qualquer banco a ser restaurado:

1. Listar bancos no dump: `grep -n "^-- Current Database:" all.sql`
2. Extrair a secao do banco desejado com `tail | head`
3. Importar para staging (ex: `banco_stage`)
4. Validar tabelas criticas e contagem de registros
5. Promover com swap atomico (RENAME TABLE)

---

## ✅ Resultado da Recuperação — 2026-06-09

O banco `results` foi recuperado com sucesso do dump legado `all.sql`
(2.5 GB) para o MariaDB em `10.10.2.30`.

### Método de transferência

Arquivo copiado do hypervisor `africasul` (10.10.2.29) para o servidor de
produção (10.10.2.30) usando **rsync com sshpass** executado **de dentro do
10.10.2.30** (conexão local Gigabit, sem passar pela máquina local):

```bash
# No 10.10.2.30 (mexico):
export SSHPASS="@fr!c@Sul"
sshpass -e rsync -avP --progress \
  -e 'ssh -o StrictHostKeyChecking=no -o HostKeyAlgorithms=+ssh-rsa -o PubkeyAuthentication=no' \
  root@10.10.2.29:/mnt/mysql_banco_ro/all.sql /opt/results/infra/all.sql
```

- **Tempo**: 3m32s para 2.66 GB (~12 MB/s)
- **⚠️ Senhas com `!`**: usar `export SSHPASS="..."` + `sshpass -e` (nunca
  inline no shell, o `!` causa expansão do histórico)

### Método de importação

A importação **NÃO** pode usar ProxySQL (:6033) porque o `query_timeout` mata
DDL (`CREATE TABLE`, `DROP TABLE`, `ALTER TABLE`). O caminho correto é via
`docker exec` direto no container master:

```bash
# Localizar seção results no dump (2 seções: linhas 84951-95403 e 108485-EOF)
grep -n "^-- Current Database:" /opt/results/infra/all.sql | grep results

# Extrair e importar (bypass do ProxySQL):
tail -n +84956 /opt/results/infra/all.sql | head -n 10448 | \
  sed 's/`results`/`results_legacy`/g' | \
  docker exec -i srvmysql0 mysql -u root -presu100dba results_legacy
```

- `tail -n +LINHA | head -n N` é muito mais rápido que `sed -n 'A,Bp'` para
  arquivos grandes
- `docker exec -i srvmysql0 mysql` conecta direto ao master sem passar pelo
  ProxySQL

### Resultado final

| Métrica | Valor |
|---|---|
| Database | `results_legacy` |
| Tabelas | **169** |
| Linhas totais | **3.344.853** |
| `domain` | 6 registros ✅ |
| `mailbox` | 108 registros ✅ |
| `alias` | 10 registros ✅ |
| `radacct` | 2.146.557 |
| `cdr` | 1.139.065 |
| `queue_log` | 50.731 |

### Próximos passos

- [x] Validar integridade: comparar schema contra `results` atual
- [x] Identificar tabelas do Joomla (`okg7s_*`) — 109 tabelas presentes
- [x] Promover `results_legacy` → `results` com swap atômico
- [ ] Limpar `results_legacy_stage` (travado por lock, requer reinício do MySQL)
- [ ] Recriar triggers do `cdr` (`cdr_before_insert`, `cdr_insert`) no schema `results`

### Notas da promoção (2026-06-09)

- Swap: `results` → `results_backup` (6 tabelas DNS), `results_legacy` → `results` (169 tabelas)
- Método: RENAME TABLE individual via `docker exec srvmysql0 mysql` (157 tabelas em ~22 min)
- Bloqueio: INSERTs pendentes no `radacct` da importação causaram rollback de ~4 min antes do último RENAME
- Triggers: `cdr_before_insert` e `cdr_insert` foram dropados antes do RENAME e precisam ser recriados

---

## ✅ Recuperação do Joomla (2026-06-09)

### Descoberta

O banco `results` restaurado contém **109 tabelas com prefixo `okg7s_`** que são
os dados **originais e completos do Joomla**:

| Recurso | Tabela | Registros |
|---|---|---|
| Extensões (plugins, templates, módulos) | `okg7s_extensions` | 163 |
| Templates (estilos) | `okg7s_template_styles` | 6 |
| Módulos (posições, atribuições) | `okg7s_modules` | 18 |
| Usuários | `okg7s_users` | 1 |
| Menus | `okg7s_menu` | — |

Template original: **`jsn_metro_free`** (JSN Metro FREE), não `purity_iii`.

### Problema no banco `joomla` atual

O banco `joomla` atual tem apenas tabelas estruturais com prefixo `se0zs_`,
**sem os dados de configuração** — o dump `all.sql` não continha o banco
`joomla` completo, mas os dados do Joomla estavam **dentro do banco
`results`** com prefixo `okg7s_`.

### Método de restauração (Joomla)

```bash
# 1. Conferir tabelas Joomla no results
docker exec srvmysql0 mysql -u root -presu100dba results -e \
  "SHOW TABLES LIKE 'okg7s_%'"

# 2. Copiar tabelas do results para o joomla (ajustando prefixo)
#    Gera comandos RENAME TABLE para cada tabela
docker exec srvmysql0 mysql -u root -presu100dba -N -e \
  "SELECT CONCAT('RENAME TABLE results.\`', table_name, '\` TO joomla.\`se0zs_', SUBSTRING(table_name, 7), '\`;')
   FROM information_schema.tables
   WHERE table_schema='results' AND table_name LIKE 'okg7s_%'" | \
  docker exec -i srvmysql0 mysql -u root -presu100dba

# 3. Ajustar configuration.php para apontar para o banco results
#    OU manter as tabelas no joomla (recomendado)
```

### Resultado esperado

- Template `jsn_metro_free` ativo com layout original
- Módulos nas posições corretas
- Menu horizontal: Empresa, Soluções, Serviços, Produtos, Loja, Suporte,
  Downloads, Treinamento, Blog, Contato
- Usuários administradores restaurados

---

## 🏆 Fonte de Verdade Definitiva: Container `mariadb-forensics`

**A partir de 2026-06-10, a fonte de verdade para qualquer recuperação de
dados pré-migração é o container `mariadb-forensics` rodando no próprio
servidor `10.10.2.30`.**

Este container substitui a necessidade de voltar ao hypervisor legado
(`10.10.2.29`) ou manipular o dump `all.sql` — os dados já estão vivos,
acessíveis e mais completos que a produção atual.

### Por que `mariadb-forensics` é a melhor fonte

| Critério | `mariadb-forensics` | Produção (`srvmysql0`) | Hypervisor (`all.sql`) |
|---|---|---|---|
| **Disponibilidade** | ✅ Up 24/7 (container) | ✅ Up | ❌ Requer montagem de disco |
| **Bancos** | **26 bancos** | 3 bancos | 5 bancos identificados |
| **`results`** | **278 tabelas** | 169 tabelas | 169 tabelas (dump único) |
| **`joomla`** | **1457 tabelas** | 269 tabelas | Dump parcial |
| **Velocidade** | ✅ Local (pipe direto) | — | ❌ ~3m30s rsync + extração |
| **Segurança** | ✅ Read-only (`innodb_force_recovery=1`) | ⚠️ Cluster vivo | ✅ Dump estático |

### Características do container

```
Container:  mariadb-forensics
Imagem:     mariadb:5.5
Porta:      0.0.0.0:13306 → 3306 (tcp)
Rede:       bridge (172.17.0.5)
Volume:     /opt/results/forensics/legacy-mysql2-20260610-work/mysql2 → /var/lib/mysql
Modo:       --innodb-force-recovery=1 --skip-grant-tables
Conexão:    docker exec mariadb-forensics mysql -u root -h 127.0.0.1
Status:     Up 13+ horas (2026-06-10)
```

### Todos os 26 bancos disponíveis

| Banco | Tabelas | Observação |
|---|---|---|
| `joomla` | 1457 | Muito mais completo que a produção (269) |
| `results` | 278 | +109 tabelas vs produção (169) |
| `peruche` | 211 | Não existe na produção |
| `icinga` | 193 | Não existe na produção |
| `zabbix` | 146 | Não existe na produção |
| `lhc` | 116 | Live Helper Chat — não existe na produção |
| `shekinah` | 107 | Não existe na produção |
| `egroupware` | 105 | Não existe na produção |
| `db_nagiosql_v32` | 97 | Não existe na produção |
| `nextcloud` | 93 | Não existe na produção |
| `icinga_web` | 77 | Não existe na produção |
| `openfire` | 64 | Não existe na produção |
| `wikidb` | 52 | Não existe na produção |
| `wiki` | 49 | Não existe na produção |
| `bacula` | 49 | Não existe na produção |
| `grafana` | 33 | Não existe na produção |
| `zm` | 18 | ZoneMinder — não existe na produção |
| `roundcubemail` | 14 | Igual à produção |
| `wordpress_studio` | 13 | Não existe na produção |
| `powerdns` | 12 | Não existe na produção |
| `accs` | 7 | Não existe na produção |
| `datamaxi` | 6 | Não existe na produção |
| `usp_censo_dasp` | 2 | Não existe na produção |
| `ncm` | 1 | Não existe na produção |

---

## 🚀 Método Mais Rápido e Seguro: Pipe Direto entre Containers

**ESTE É O MÉTODO OFICIAL para recuperar qualquer banco do
`mariadb-forensics` para a produção.**

O pipe direto via `docker exec` é **mais rápido, mais seguro e mais simples**
que qualquer alternativa (rsync, dump em arquivo, conexão TCP entre
containers).

### Por que este método

1. **Zero I/O de disco**: o dump nunca é escrito em arquivo — vai direto do
   `mysqldump` para o `mysql` via pipe na memória
2. **Sem ProxySQL**: o `docker exec` no `srvmysql0` conecta direto ao
   MariaDB, sem passar pelo ProxySQL (que mataria DDL com `query_timeout`)
3. **Sem rede**: ambos os containers estão no mesmo host, sem latência de
   rede, sem firewall, sem iptables
4. **Sem autenticação inter-container**: cada `docker exec` usa as
   credenciais locais do seu próprio container
5. **À prova de timeout SSH**: como o pipe é local, não sofre com timeout da
   sessão SSH (para bancos grandes, usar `nohup`)

### ⚠️ Por que NÃO usar conexão TCP entre containers

- `srvmysql0` está em `network_mode: host`, `mariadb-forensics` em `bridge`
  — redes diferentes, sem resolução DNS entre eles
- `10.10.2.99:3306` → ProxySQL (mata DDL, requer usuário cadastrado)
- `10.10.2.79:3306` → MariaDB direto (requer senha e `root@172.17.0.5`
  autorizado)
- Conclusão: **sempre use o pipe via `docker exec`**

### Sintaxe geral

```bash
# Restaurar banco INTEIRO (schema + dados) do forensics para produção
docker exec mariadb-forensics mysqldump -u root -h 127.0.0.1 \
  --databases NOME_DO_BANCO \
  | docker exec -i srvmysql0 mysql -u root -presu100dba

# Restaurar APENAS schema (sem dados) — para análise ou staging
docker exec mariadb-forensics mysqldump -u root -h 127.0.0.1 \
  --no-data --databases NOME_DO_BANCO \
  | docker exec -i srvmysql0 mysql -u root -presu100dba

# Restaurar para staging (nome diferente) — recomendado para validação
docker exec mariadb-forensics mysqldump -u root -h 127.0.0.1 \
  --databases NOME_DO_BANCO \
  | sed 's/`NOME_DO_BANCO`/`NOME_DO_BANCO_stage`/g' \
  | docker exec -i srvmysql0 mysql -u root -presu100dba
```

### Fluxo recomendado (passo a passo)

```bash
# 1. Listar tabelas no forensics para referência
docker exec mariadb-forensics mysql -u root -h 127.0.0.1 -e \
  "SELECT table_name FROM information_schema.tables
   WHERE table_schema='NOME_DO_BANCO' ORDER BY table_name"

# 2. Importar para staging (sempre!)
docker exec mariadb-forensics mysqldump -u root -h 127.0.0.1 \
  --databases NOME_DO_BANCO \
  | sed 's/`NOME_DO_BANCO`/`NOME_DO_BANCO_stage`/g' \
  | docker exec -i srvmysql0 mysql -u root -presu100dba

# 3. Validar no staging
docker exec srvmysql0 mysql -u root -presu100dba -e \
  "SELECT table_name FROM information_schema.tables
   WHERE table_schema='NOME_DO_BANCO_stage'"

# 4. Promover (swap atômico com RENAME TABLE)
#    Metodo: RENAME TABLE individual para evitar downtime
docker exec srvmysql0 mysql -u root -presu100dba -N -e \
  "SELECT CONCAT('RENAME TABLE \`', table_name, '\` TO \`NOME_DO_BANCO_backup\`.\`', table_name, '\`;')
   FROM information_schema.tables
   WHERE table_schema='NOME_DO_BANCO'" \
  | docker exec -i srvmysql0 mysql -u root -presu100dba
```

### Bancos grandes (com `nohup` para evitar timeout SSH)

```bash
# Para bancos > 100 MB, usar nohup para não travar na sessão SSH
nohup bash -c "
  docker exec mariadb-forensics mysqldump -u root -h 127.0.0.1 \
    --databases results \
  | docker exec -i srvmysql0 mysql -u root -presu100dba results_stage
" > /opt/results/forensics/restore-20260610/restore.log 2>&1 &

# Acompanhar:
tail -f /opt/results/forensics/restore-20260610/restore.log
```

### Exemplo real validado (2026-06-10)

```bash
# Restauração do banco 'accs' (7 tabelas) validada:
echo 'CREATE DATABASE IF NOT EXISTS accs_restore;' \
  | docker exec -i srvmysql0 mysql -u root -presu100dba

docker exec mariadb-forensics mysqldump -u root -h 127.0.0.1 \
  --no-data accs \
  | docker exec -i srvmysql0 mysql -u root -presu100dba accs_restore
# Resultado: 7 tabelas importadas com sucesso ✅
```

### Arquivos relacionados no host

```
/opt/results/forensics/
├── joomla-forensic-20260610.sql         # Dump SQL do Joomla (145 MB)
├── legacy-mysql2-20260610/              # Cópia original do datadir
├── legacy-mysql2-20260610-direct/       # Outra cópia do datadir
├── legacy-mysql2-20260610-work/         # Cópia de trabalho (montada no container)
│   └── mysql2/                          # Datadir MySQL 5.5 legado
├── restore-20260610/                    # Pasta para logs de restore
├── tmp-etc/                             # Configs temporárias
└── tmp-run/                             # Runtime temporário
```

---

## 🔥 Incidente — `results.com.br` fora do ar (HTTP 500) — 2026-06-10

### Sintoma

A partir de `2026-06-10 22:26 -03`, `https://results.com.br/` passou a
responder **HTTP 500** (página genérica `Error` do Joomla). O webmail
(`/webmail/`) continuou funcionando normalmente — sinal de que o problema era
específico de credenciais de banco do Joomla, não de rede/DNS.

### Causa raiz

O usuário MySQL `resultsdba` (usado pelo Joomla em `configuration.php`:
`host=srvmysql.results.intranet` → `10.10.2.79`, `db=joomla`,
`user=resultsdba`, `password=resu100dba`) deixou de autenticar no
`srvmysql0`:

```
Access denied for user 'resultsdba'@'172.27.0.5' (using password: YES)
```

Comparando hashes (`mysql.user.authentication_string`):

| Origem | Usuário/Host | Hash |
|---|---|---|
| `srvmysql0` produção (durante o incidente) | `resultsdba@%` e `resultsdba@10.10.2.30` | `*7B86C638EFFEB8997B3AAA4979CCADF0C264C099` |
| Esperado pela aplicação (`PASSWORD('resu100dba')`) | — | `*CE9A8D9D8AD471B113E322A07F6B8A5BFAD97397` |
| `mariadb-forensics` (legado) | `resultsdba@srvmail0`, `@srvldap3`, `@srvvpn0`, `@192.168.%` | `*7B86C638EFFEB8997B3AAA4979CCADF0C264C099` |
| `mariadb-forensics` (legado) | `resultsdba@srvmysql.results.intranet`, `@localhost`, demais hosts | `*CE9A8D9D8AD471B113E322A07F6B8A5BFAD97397` |

O hash `*7B86C638...` que apareceu em produção é uma **senha legada antiga**,
usada historicamente por `srvmail0`/`srvldap3`/`srvvpn0`/`192.168.%` — e não a
senha `resu100dba` que `configuration.php` do Joomla (e o `srvmysql` legado)
sempre usaram.

`srvmysql0` foi reiniciado às `2026-06-10 17:06:30 UTC` (`14:06 -03`) e o site
começou a falhar pouco depois. A hipótese mais provável é que, durante as
operações de recuperação descritas neste documento, alguma etapa restaurou a
tabela `mysql.user`/`mysql.global_priv` a partir dos dados legados,
sobrescrevendo as linhas `resultsdba@%` e `resultsdba@10.10.2.30` com o hash
antigo. Os scripts versionados (`scripts/restore-banco.sh`,
`scripts/restore-joomla-forensic.sh`, `scripts/restore-results-forensic.sh`)
não fazem referência a `mysql.user`/`GRANT`/`--all-databases`, então a
alteração não está rastreável a um comando específico documentado.

### Correção aplicada

```bash
docker exec srvmysql0 mysql -u root -presu100dba -e "
  ALTER USER 'resultsdba'@'%' IDENTIFIED BY 'resu100dba';
  ALTER USER 'resultsdba'@'10.10.2.30' IDENTIFIED BY 'resu100dba';
  FLUSH PRIVILEGES;
"
```

Validação:

```bash
docker exec results-joomla curl -s -o /dev/null -w 'HTTP_CODE:%{http_code}\n' http://127.0.0.1/
# HTTP_CODE:200

curl -sk -o /dev/null -w "HTTP_CODE:%{http_code}\n" https://results.com.br/
# HTTP_CODE:200
```

### Lição / prevenção

- Nenhuma operação de recuperação/restore deve tocar no schema `mysql`
  (`mysql.user`, `mysql.global_priv`, `mysql.db`, etc.) do `srvmysql0` em
  produção. Restaurar **apenas** os bancos de aplicação (`results`, `joomla`,
  `roundcubemail`, etc.) — nunca `--all-databases` nem seções
  `-- Current Database: \`mysql\`` de `all.sql`/dumps do
  `mariadb-forensics`.
- Após qualquer restore/swap em produção, validar as credenciais das
  aplicações que dependem do MariaDB (`resultsdba`, `roundcube`, ...) antes de
  encerrar a janela de manutenção:

  ```sql
  SELECT user, host, authentication_string FROM mysql.user
   WHERE user IN ('resultsdba','roundcube');
  ```

  e comparar com `PASSWORD('<senha do configuration.php / docker-compose>')`.

---

## 🔥 Incidente — Webmail do `jefferson` com INBOX vazia — 2026-06-10

### Sintoma

Login em `https://results.com.br/webmail/` com `jefferson` funcionava
normalmente, mas a INBOX aparecia **vazia** (0 mensagens), apesar do usuário
ter acessado o e-mail normalmente até `2026-06-08`.

### Causa raiz

O container `results-mail-dovecot` havia sido recriado nas horas anteriores
(`Up 11h` no momento do diagnóstico) sob o **Compose project name `infra`**
— provavelmente via `docker compose -f docker-compose.mail.yml up -d dovecot`
executado de `/opt/results/infra` **sem** `--project-name infra-mail` (o
nome do diretório, `infra`, vira o project name padrão do Compose).

Isso fez o Compose criar volumes novos, vazios, com o prefixo `infra_*`
(`infra_maildata`, `infra_dovecot-state`, `infra_mail-certs`) em vez de
reconectar aos volumes corretos da stack de mail (`infra-mail_*`, conforme
`package.json` → `deploy:remote:ssh:mail` →
`DEPLOY_PROJECT_NAME=infra-mail`).

| Volume | Project | Conteúdo |
|---|---|---|
| `infra-mail_maildata` (correto) | `infra-mail` | 15.3 GB, 43 caixas (ex.: `jefferson` ~2.7GB/40505 msgs, `laudecir` 1.1GB/35765 arquivos), modificado em `2026-06-09` |
| `infra_maildata` (em uso pelo dovecot recriado) | `infra` | 168 KB — apenas estrutura vazia (`cur/`, `new/`, índices do Dovecot criados hoje), 0 mensagens em `cur/`/`new/` |

Os demais containers da stack de mail (`results-mail-ldap`,
`results-mail-clamav`, `results-mail-rspamd`, `results-mail-certbot`,
`results-mail-redis`) continuaram corretamente sob o project `infra-mail` e
não foram afetados.

### Correção aplicada

```bash
# Remove o container criado sob o project errado (infra)
docker stop results-mail-dovecot && docker rm results-mail-dovecot

# Recria sob o project correto (infra-mail), remontando infra-mail_maildata
docker compose \
  --project-directory /opt/results/infra \
  --env-file /opt/results/infra/.env.remote-10.10.2.30-mail \
  -f /opt/results/infra/docker-compose.mail.yml \
  --project-name infra-mail \
  up -d --build --no-deps dovecot
```

### Validação

```bash
docker exec results-mail-dovecot doveadm mailbox status -u jefferson messages vsize INBOX
# INBOX messages=37806

# Login no webmail (curl) -> HTTP/2 302, Location: ?_task=mail
```

### Lição / prevenção

- **Sempre** usar `--project-name infra-mail` (ou `npm run
  deploy:remote:ssh:mail`, que já define `DEPLOY_PROJECT_NAME=infra-mail`)
  ao subir/recriar qualquer serviço de `docker-compose.mail.yml`. Nunca rodar
  `docker compose ... up -d <serviço>` sem `--project-name`/`-p` a partir de
  `/opt/results/infra` — o Compose usa o nome do diretório (`infra`) como
  project padrão e cria volumes novos e vazios com esse prefixo.
- Após qualquer recriação de container de mail, validar:
  ```bash
  docker inspect <container> --format '{{.Name}}: {{(index .Config.Labels "com.docker.compose.project")}}'
  ```
  e conferir se bate com `infra-mail`.
- **Pendência (baixa prioridade, não destrutiva ainda):** os volumes órfãos
  `infra_maildata`, `infra_dovecot-state`, `infra_mail-certs` (e dois volumes
  anônimos montados em `/etc/dovecot` e `/srv/mail` do container antigo)
  ficaram no host, vazios/sem uso. Remover apenas após confirmação explícita
  (`docker volume rm infra_maildata infra_dovecot-state infra_mail-certs`).
- **Pendência separada (não investigada):** os containers
  `results-mail-postfix`/`results-mail-postfix-mx2` (project `infra-mail`)
  não existem atualmente no host, apesar das imagens `infra-mail-postfix` e
  `infra-mail-postfix-mx2` existirem (build de ~12 dias atrás).

### Nota operacional — sessão SSH abre em `/root`

Durante a correção, comandos com `cd /opt/results/infra && docker compose
...` falharam repetidamente com `Couldn't find env file` porque o `cd`
acabava não sendo aplicado (sessão SSH abre em `/root`). A solução foi
montar o `docker compose` inteiramente com paths absolutos, sem depender de
`cd`:

```bash
docker compose \
  --project-directory /opt/results/infra \
  --env-file /opt/results/infra/.env.remote-10.10.2.30-mail \
  -f /opt/results/infra/docker-compose.mail.yml \
  --project-name infra-mail \
  up -d --build --no-deps dovecot
```

### Resultado final

Após a recriação do `results-mail-dovecot` sob `infra-mail`, o e-mail voltou
a funcionar normalmente:

- `docker exec results-mail-dovecot doveadm mailbox status -u jefferson
  messages vsize INBOX` → `INBOX messages=37806` (antes: `0`).
- Login em `https://results.com.br/webmail/` com `jefferson` retorna
  `HTTP/2 302` → `Location: ?_task=mail` (login OK, INBOX populada).
- Demais 42 caixas em `infra-mail_maildata` (15.3GB) voltaram a ficar
  acessíveis pelo mesmo container.

---

## 🔥 Incidente — Envio de e-mail (SMTP) fora do ar — 2026-06-10

### Sintoma

Mesmo após a correção da INBOX vazia, o **envio** de e-mail (pelo webmail e
recebimento de MX externo) não funcionava.

### Causa raiz

Os containers `results-mail-postfix` (mx1) e `results-mail-postfix-mx2`
(mx2) **não existiam no host** — nem rodando, nem parados. Nenhuma porta
SMTP (25/465/587) estava em escuta em `10.10.2.3`/`10.10.2.23`.

O Roundcube usa `$config['smtp_server'] = 'tls://mx1.results.com.br'`
(`joomla-site/webmail/config/config.inc.php`), ou seja, depende do
`results-mail-postfix` em `10.10.2.3:587`.

Mesmo padrão do incidente do dovecot: existiam imagens `infra-postfix` /
`infra-postfix-mx2` (buildadas ~38h antes sob o project errado `infra`), mas
os containers correspondentes haviam sido removidos, deixando a stack de
mail sem MTA algum. As imagens corretas `infra-mail-postfix` /
`infra-mail-postfix-mx2` (12 dias) já existiam.

### Correção aplicada

```bash
docker compose \
  --project-directory /opt/results/infra \
  --env-file /opt/results/infra/.env.remote-10.10.2.30-mail \
  -f /opt/results/infra/docker-compose.mail.yml \
  --project-name infra-mail \
  up -d --build postfix postfix-mx2
```

Isso também acionou a dependência `mail-certs-bootstrap` (concluiu com
sucesso, `Exited (0)`, certificados já existiam em `infra-mail_mail-certs`)
e recriou `clamav`/`rspamd` (sem mudança de imagem relevante).

### Validação

```bash
docker ps --filter "name=results-mail" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
# results-mail-postfix      Up (healthy)   10.10.2.3:25/465/587 -> ...
# results-mail-postfix-mx2  Up (healthy)   10.10.2.23:25/465/587 -> ...
# results-mail-clamav       Up (healthy)
# results-mail-rspamd       Up (healthy)
```

Teste de envio real via SMTP submission autenticado (porta 587, STARTTLS,
usuário `jefferson@results.com.br`):

```bash
printf 'Subject: Teste de envio pos-fix\r\nFrom: jefferson@results.com.br\r\nTo: jefferson@results.com.br\r\n\r\nTeste...\r\n' | \
curl -sk --url "smtp://10.10.2.3:587" --mail-from "jefferson@results.com.br" \
  --mail-rcpt "jefferson@results.com.br" --ssl-reqd \
  --user "jefferson@results.com.br:<senha>" --upload-file -
# 235 2.7.0 Authentication successful
# 250 2.0.0 Ok: queued as 2791B2E47F4
```

Mensagem entregue localmente: `INBOX messages` do jefferson subiu de
`37806` para `37808` (teste + correio real recebido durante a janela).
Logs do `results-mail-postfix` já mostravam tráfego real de entrega
externa (MX) sendo processado normalmente.

### Lição / prevenção

- Mesma causa-raiz do incidente anterior: serviços da stack de mail
  precisam **sempre** rodar sob `--project-name infra-mail` (ver
  [[feedback_compose_project_name_mail]]). Containers criados sob o project
  `infra` (errado) e depois removidos deixam a stack incompleta sem aviso
  óbvio — o sintoma só aparece para o usuário final (envio falhando).
- Após qualquer mudança na stack de mail, validar minimamente:
  ```bash
  docker compose --project-directory /opt/results/infra \
    --env-file /opt/results/infra/.env.remote-10.10.2.30-mail \
    -f /opt/results/infra/docker-compose.mail.yml \
    --project-name infra-mail ps -a
  ```
  e conferir que **todos** os 9 serviços do `docker-compose.mail.yml`
  aparecem (`postfix`, `postfix-mx2`, `dovecot`, `ldap`, `rspamd`, `clamav`,
  `redis`, `mail-certbot`, `mail-certs-bootstrap`).

---

## 🔥 Incidente — Webmail "Erro SMTP (-1): Conexão ao servidor falhou" — 2026-06-11

### Sintoma

Mesmo com `results-mail-postfix`/`results-mail-postfix-mx2` no ar (incidente
anterior corrigido), o **envio pelo Roundcube** continuava falhando. A
resposta do `?_task=mail&_action=send` trazia:

```
parent.rcmail.display_message("Erro SMTP (-1): Conexão ao servidor falhou.","error",0);
```

### Causa raiz

Os containers `secure-httpd` e `results-joomla` (project `infra`, redes
`infra_default` 172.28.0.0/16 e `infra-mail_default` 172.27.0.0/16) tinham
`/etc/resolv.conf` com:

```
nameserver 127.0.0.11
# ExtServers: [172.25.0.1]
```

`172.25.0.1` é o gateway da rede `infra-httpd_default` (172.25.0.0/16) — uma
rede a que **nenhum dos dois containers está conectado**. O valor vem do
default hardcoded `dns: ${HTTP_DOCKER_DNS:-172.25.0.1}` em
[docker-compose.yml](/opt/results/infra/docker-compose.yml:14) e
[docker-compose.yml](/opt/results/infra/docker-compose.yml:74), que só faz
sentido se o serviço rodar sob o project `infra-httpd` (onde `default` seria
`infra-httpd_default`). Como ambos rodam sob `infra`, `172.25.0.1` é
**inalcançável** — toda resolução DNS (de qualquer hostname externo,
incluindo `mx1.results.com.br`) trava até timeout.

Confirmado com query DNS UDP raw via PHP de dentro do `results-joomla`:

| Alvo | Resultado |
|---|---|
| `172.25.0.1` (configurado, rede errada) | timeout |
| `10.53.53.13` (dnsdist, direto) | timeout |
| `172.28.0.1` (gw de `infra_default`, rede real) | responde, resolve via dnsdist |
| `172.27.0.1` (gw de `infra-mail_default`, rede real) | responde, resolve via dnsdist |

Mesma causa-raiz sistêmica dos dois incidentes anteriores: containers
recriados sob o project `infra` (nome do diretório) em vez do project
esperado por cada compose file — aqui o sintoma é DNS interno quebrado em vez
de volume/MTA ausente.

### Correção aplicada

```bash
# .env.remote-10.10.2.30 (servidor) e .env.example (repo)
HTTP_DOCKER_DNS=172.28.0.1   # antes: 172.25.0.1 (gw de rede inexistente p/ estes containers)

# Recriar os dois containers para regenerar /etc/resolv.conf
docker compose --env-file /opt/results/infra/.env.remote-10.10.2.30 \
  -f /opt/results/infra/docker-compose.yml \
  --project-directory /opt/results/infra --project-name infra \
  up -d --no-deps --force-recreate joomla

docker compose --env-file /opt/results/infra/.env.remote-10.10.2.30 \
  -f /opt/results/infra/docker-compose.yml \
  --project-directory /opt/results/infra --project-name infra \
  up -d --no-deps --force-recreate apache
```

### Validação

```bash
docker exec results-joomla getent hosts mx1.results.com.br
# 10.10.2.3       mx1.results.com.br

docker exec secure-httpd getent hosts mx1.results.com.br
# 10.10.2.3       mx1.results.com.br

curl -sk -H 'Host: www.results.com.br' https://10.10.2.60:18443/webmail/   # 200
curl -sk -H 'Host: www.results.com.br' https://10.10.2.60:18443/           # 200
```

Teste real de envio pelo webmail (login `jefferson` → compose → send via
HTTP, simulando o browser): resposta
`sent_successfully("confirmation","Mensagem enviada com sucesso", ["Sent"], null)`.

Confirmado nos logs do `results-mail-postfix`:
```
postfix/submission/smtpd: client=unknown[172.27.0.1], sasl_username=jefferson@results.com.br
postfix/qmgr: from=<jefferson@results.com.br>, size=829, nrcpt=1 (queue active)
postfix/smtp: to=<jeffersonmachado1@gmail.com>, relay=gmail-smtp-in.l.google.com[...]:25,
  status=sent (250 2.0.0 OK ... gsmtp)
```

### Lição / prevenção

- Valores hardcoded como `dns: ${HTTP_DOCKER_DNS:-172.25.0.1}` em
  `docker-compose.yml` assumem um project name específico (`infra-httpd`).
  Quando o project real é `infra` (containers `secure-httpd`/`results-joomla`
  rodando sob `infra`, redes `infra_default`/`infra-mail_default`), o valor
  correto é o gateway de uma rede à qual o container está de fato conectado
  (`172.28.0.1` = gw `infra_default`).
- Após recriar `secure-httpd`/`results-joomla`, sempre validar
  `# ExtServers:` em `/etc/resolv.conf` aponta para um IP alcançável
  (`docker exec <container> getent hosts <hostname externo>` não pode travar).
- Existe uma camada de proxy adicional (`172.24.0.2`, na frente de
  `10.10.2.30:443`) que retornou 403/404 para `Host: www.results.com.br`
  durante esta investigação — **não relacionado** a esta correção (não foi
  alterado), mas fica registrado como pendência a investigar separadamente:
  o caminho real de produção valida 200 direto em
  `secure-httpd:10.10.2.60:18443`.
