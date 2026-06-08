# MariaDB Galera Cluster — Produção

**Servidor:** `10.10.2.30` (mexico.results.intranet)  
**Porta:** `3306` (exposta via galera1)  
**Imagem:** `mariadb-galera:10.6` (custom, base Ubuntu 22.04)  
**SST:** `mariabackup` (não bloqueante)  

---

## Topologia

```
┌──────────────────────────────────────────────────────┐
│          galera-cluster (172.32.0.0/16)              │
│                                                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐           │
│  │ galera1  │  │ galera2  │  │ galera3  │           │
│  │ .11      │  │ .12      │  │ .13      │           │
│  │ server=1 │  │ server=2 │  │ server=3 │           │
│  │ ✱ PRIMARY│  │ ✱ SYNCED │  │ ✱ SYNCED │           │
│  │ :3306→   │  │          │  │          │           │
│  │  host    │  │          │  │          │           │
│  └──────────┘  └──────────┘  └──────────┘           │
│                                                      │
│  Volumes: galera-prod_galera{1,2,3}-data             │
│  SST auth: galera / galeraSST@2026                   │
└──────────────────────────────────────────────────────┘
```

---

## Estrutura de Arquivos

```
galera/
├── docker-compose.yml       # Definição dos 3 serviços
├── conf/
│   ├── galera1.cnf          # Configuração nó 1 (172.32.0.11, server_id=1)
│   ├── galera2.cnf          # Configuração nó 2 (172.32.0.12, server_id=2)
│   └── galera3.cnf          # Configuração nó 3 (172.32.0.13, server_id=3)
├── init/
│   └── 01-sst-user.sql      # Criação do usuário SST (mariabackup)
├── bin/
│   └── start-cluster.sh     # Script de subida automatizada
└── README.md                # Este arquivo
```

---

## Comandos

### Subir o cluster

```bash
cd /opt/results/infra/galera
./bin/start-cluster.sh
```

O script automaticamente:
1. Valida ambiente (Docker, portas)
2. Adiciona `--wsrep-new-cluster` ao galera1
3. Sobe galera1 e aguarda healthy
4. Remove o flag de bootstrap
5. Sobe galera2 e galera3 com `--no-recreate`
6. Aguarda cluster atingir size=3
7. Valida replicação

### Ver status

```bash
./bin/start-cluster.sh --status
```

### Parar o cluster

```bash
./bin/start-cluster.sh --stop
# ou
docker compose down
```

### Apenas bootstrap (caso queira subir os demais manualmente)

```bash
./bin/start-cluster.sh --bootstrap
```

---

## ⚠️ Procedimentos de Emergência

### Cluster não sobe após parada total

Se todos os containers foram parados (`docker compose down`), é necessário bootstrap:

```bash
# 1) Adicionar bootstrap temporário
sed -i '/hostname: galera1/a\    command: --wsrep-new-cluster' docker-compose.yml

# 2) Subir galera1
docker compose up -d galera1

# 3) Aguardar healthy, depois remover o bootstrap
sed -i '/command: --wsrep-new-cluster/d' docker-compose.yml

# 4) Subir os demais
docker compose up -d --no-recreate galera2 galera3
```

### Nó com safe_to_bootstrap=0

Se o galera1 não sobe com erro "It may not be safe to bootstrap", editar o grastate.dat:

```bash
docker run --rm -v galera-prod_galera1-data:/data alpine \
    sed -i 's/safe_to_bootstrap: 0/safe_to_bootstrap: 1/' /data/grastate.dat
```

### Verificar logs

```bash
docker logs galera1 --tail 50
docker logs galera2 --tail 50
docker logs galera3 --tail 50
```

### Acessar MySQL

```bash
docker exec -it galera1 mysql -u root -p'resu100dba'
```

---

## Dados das Aplicações

### Bancos de dados no ambiente

| Container | Imagem | Status | Porta | Volume | Dados |
|-----------|--------|--------|-------|--------|-------|
| **galera1/2/3** | mariadb-galera:10.6 | **UP** (cluster) | 3306 | `galera-prod_galera{1,2,3}-data` | Cluster operacional |
| srvmysql0 | mariadb-galera:10.6 | STOPPED | - | `galera-data-srvmysql0` | Sem dados de app (vazio) |
| egroupware-db | mariadb:10.6 | UP (standalone) | 3306 (interno) | `egroupware_db` | EGroupware |
| ripabx-mariadb | mariadb:11.7 | STOPPED | *3306* | `/docker/ripabx/mariadb/data` | RIPABX (conflito porta) |

### ⚠️ ripabx-mariadb

O `ripabx-mariadb` está parado porque a porta 3306 está agora alocada ao cluster Galera.  
Dados preservados em `/docker/ripabx/mariadb/data` (bind mount).  
Para restaurar, é necessário migrar os dados para o cluster Galera ou usar outra porta.

### ⚠️ egroupware-db

Continua rodando como standalone na porta 3306 interna (rede bridge `egroupware_default`).  
Não conflita com o Galera pois usa rede separada.  
Volume: `egroupware_db`.

---

## Configurações Chave

| Parâmetro | Valor |
|-----------|-------|
| MariaDB | 10.6.27 |
| Galera | 26.4.27 |
| wsrep_cluster_name | `mysql` |
| wsrep_sst_method | `mariabackup` |
| wsrep_sst_auth | `galera:galeraSST@2026` |
| innodb_buffer_pool_size | 256M |
| binlog_format | ROW |
| auto_increment_increment | 3 |
| max_connections | 200 |
