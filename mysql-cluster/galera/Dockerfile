FROM mariadb:10.11

# ─── MariaDB 10.11 + Galera 4 ──────────────────────────────────────────────
# Imagem base oficial + galera-4 + mariadb-backup para SST

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        galera-4 \
        mariadb-backup \
        gettext-base \
    && rm -rf /var/lib/apt/lists/*

# Verifica suporte wsrep
RUN mariadbd --help --verbose 2>/dev/null | grep -q wsrep \
    && echo "✅ Galera wsrep suportado" \
    || (echo "❌ Galera NÃO suportado" && exit 1)

# Expõe portas Galera (3306 mysql, 4567 galera, 4568 IST, 4444 SST)
EXPOSE 3306 4567 4568 4444
