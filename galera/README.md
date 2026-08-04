# MariaDB Galera Cluster — Produção

**Servidor:** `10.10.2.30` (mexico.results.intranet)  
**Porta:** `3306` (host network, uma por nó)  
**Imagem:** `mariadb-galera:10.11` (custom, Dockerfile em `mysql-cluster/galera/`)  
**Compose:** `docker-compose.mysql-galera.yml` + `.env.mysql-galera`  
**SST:** `mariabackup` (não bloqueante)  

> **Nota:** A stack `galera/docker-compose.yml` (containers `galera1/2/3`, rede bridge
> `172.32.0.0/16`, imagem `10.6`) é **legada** e não está em produção. A stack ativa
> é `docker-compose.mysql-galera.yml` (host network, IPs reais).

---

## Topologia

```
┌──────────────────────────────────────────────────────────────────┐
│                    host network (10.10.2.x)                      │
│                                                                  │
│  ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐    │
│  │   srvmysql0     │ │   srvmysql1     │ │   srvmysql2     │    │
│  │   10.10.2.79    │ │   10.10.2.89    │ │   10.10.2.49    │    │
│  │   server_id=1   │ │   server_id=2   │ │   server_id=3   │    │
│  │   SST port:4444 │ │   SST port:4445 │ │   SST port:4446 │    │
│  │   ✱ PRIMARY     │ │   ✱ SYNCED     │ │   ✱ SYNCED     │    │
│  └─────────────────┘ └─────────────────┘ └─────────────────┘    │
│                                                                  │
│  Volumes: galera-data-srvmysql{0,1,2}                            │
│  SST auth: galera / galeraSST@2026                               │
│  Cluster address: gcomm://10.10.2.79,10.10.2.89,10.10.2.49      │
└──────────────────────────────────────────────────────────────────┘
```

---

## Estrutura de Arquivos

```
.
├── docker-compose.mysql-galera.yml   # Stack ativa (raiz do repo)
├── .env.mysql-galera                 # Variáveis de ambiente (gitignored)
├── .env.mysql-galera.example         # Template sem segredos
├── mysql-cluster/galera/             # Dockerfile + entrypoint
│   ├── Dockerfile
│   ├── entrypoint.sh                 # Entrypoint customizado (renderiza config)
│   ├── entrypoint-ubuntu.sh
│   └── my.cnf.template
└── galera/                           # Stack legada (NÃO USAR EM PRODUÇÃO)
    ├── docker-compose.yml            # Rede bridge 172.32.0.0/16, imagem 10.6
    ├── conf/                         # Configs dos nós galera1/2/3
    ├── init/01-sst-user.sql          # Script de criação do usuário SST
    ├── bin/start-cluster.sh
    └── README.md                     # Este arquivo
```

---

## Comandos

### Subir o cluster (produção)

```bash
cd /opt/results/infra

# Bootstrap inicial (primeira vez, com datadir vazio)
GALERA_BOOTSTRAP=true docker compose -f docker-compose.mysql-galera.yml \
    --env-file .env.mysql-galera up -d srvmysql0

# Aguardar healthy, depois subir os demais
docker compose -f docker-compose.mysql-galera.yml \
    --env-file .env.mysql-galera up -d srvmysql1 srvmysql2

# Subida normal (todos os nós com datadir existente)
docker compose -f docker-compose.mysql-galera.yml \
    --env-file .env.mysql-galera up -d
```

### Ver status

```bash
docker exec srvmysql0 mysql -u root -p"${MYSQL_ROOT_PASSWORD}" \
    -e "SHOW STATUS LIKE 'wsrep%';" | grep -E 'cluster_size|cluster_status|connected|ready|local_state_comment|incoming'
```

### Verificar health dos containers

```bash
docker ps --format 'table {{.Names}}\t{{.Status}}' | grep srvmysql
```

---

## Troubleshooting

### Nó não sobe / restart em loop com erro SST

**Sintomas:** `docker ps` mostra `Restarting (139)` ou `unhealthy`, logs com:
```
WSREP_SST: [ERROR] xtrabackup_checkpoints missing, failed mariadb-backup/SST on donor
WSREP_SST: [ERROR] mariadb-backup finished with error: 1
Access denied for user 'galera'@'localhost'
```

**Causa:** O usuário `galera@localhost` não existe na tabela `mysql.user` do nó doador.
O SST usa mariadb-backup que autentica com esse usuário; sem ele, o backup falha
e o joiner nunca recebe o state transfer.

**Solução:**
```bash
# 1. Criar o usuário no nó doador (srvmysql0)
docker exec srvmysql0 mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "
  CREATE USER IF NOT EXISTS 'galera'@'localhost' IDENTIFIED BY 'galeraSST@2026';
  GRANT ALL PRIVILEGES ON *.* TO 'galera'@'localhost';
  FLUSH PRIVILEGES;
"
# Nota: GRANT ALL é necessário para mariadb-backup (requer RELOAD, PROCESS,
# LOCK TABLES, REPLICATION CLIENT, CREATE, INSERT, DROP, etc.)

# 2. Reiniciar os nós joiners
docker restart srvmysql1 srvmysql2
```

**Prevenção:** O entrypoint atual (`mysql-cluster/galera/entrypoint.sh`) **não cria**
o usuário SST automaticamente. Após o primeiro bootstrap, criar o usuário manualmente
(ver acima). Considerar adicionar um init script no compose que execute a criação
do usuário em todo restart.

### Cluster não sobe após parada total

```bash
# Bootstrap manual no srvmysql0
GALERA_BOOTSTRAP=true docker compose -f docker-compose.mysql-galera.yml \
    --env-file .env.mysql-galera up -d srvmysql0
docker compose -f docker-compose.mysql-galera.yml \
    --env-file .env.mysql-galera up -d srvmysql1 srvmysql2
```

### Nó com safe_to_bootstrap=0

```bash
docker run --rm -v galera-data-srvmysql0:/data alpine \
    sed -i 's/safe_to_bootstrap: 0/safe_to_bootstrap: 1/' /data/grastate.dat
```

### Donor travado em Donor/Desynced após SST falhar

Se o nó doador ficar preso em `Donor/Desynced` após uma falha de SST e não
voltar a `Synced`, forçar:
```bash
docker exec srvmysql0 mysql -u root -p"${MYSQL_ROOT_PASSWORD}" \
    -e "SET GLOBAL wsrep_desync=OFF;"
```
Se não resolver, restart do container doador (último recurso).

### Verificar logs

```bash
docker logs srvmysql0 --tail 50
docker logs srvmysql1 --tail 50
docker logs srvmysql2 --tail 50
```

### Acessar MySQL

```bash
docker exec -it srvmysql0 mysql -u root -p"${MYSQL_ROOT_PASSWORD}"
```

---

## Configurações Chave

| Parâmetro | Valor |
|-----------|-------|
| MariaDB | 10.11 (produção) |
| Galera | 26.4.x |
| wsrep_cluster_name | `mysql` |
| wsrep_sst_method | `mariabackup` |
| wsrep_sst_auth | `galera:galeraSST@2026` |
| Rede | `host` (IPs reais: 10.10.2.79, .89, .49) |
| SST ports | 4444, 4445, 4446 |
| binlog_format | ROW |
| auto_increment_increment | 3 |
| max_connections | 200 |
