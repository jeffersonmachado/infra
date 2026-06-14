#!/bin/sh
# restore-banco.sh — Método robusto para restaurar QUALQUER banco do mariadb-forensics
# Uso: ./restore-banco.sh <BANCO>  (ex: ./restore-banco.sh results)
# Pré-requisito: container mariadb-forensics rodando em 10.10.2.30

set -e
BANCO="$1"
STAGE="${BANCO}_forensic_stage"
DIR="/opt/results/forensics/restore-20260610"
DUMP="$DIR/${BANCO}-dump.sql"
ERR="$DIR/${BANCO}-dump.err"

if [ -z "$BANCO" ]; then
    echo "Uso: $0 <NOME_DO_BANCO>"
    echo "Ex:   $0 results"
    exit 1
fi

echo "========================================"
echo "RESTORE: $BANCO (forensics -> staging)"
echo "Data: $(date)"
echo "========================================"

# ETAPA 1: Dump do forensics para arquivo
echo "[1/4] Dump do forensics para arquivo..."
echo "  (pode demorar varios minutos, dependendo do tamanho)"
START=$(date +%s)
docker exec mariadb-forensics mysqldump \
    -u root -h 127.0.0.1 \
    --skip-lock-tables \
    --databases "$BANCO" \
    > "$DUMP" 2>"$ERR"

RC=$?
SIZE=$(ls -lh "$DUMP" | awk '{print $5}')
ELAPSED=$(($(date +%s) - START))
echo "  Dump: $SIZE em ${ELAPSED}s (exit: $RC)"

if [ $RC -ne 0 ]; then
    echo "FALHA no dump. Veja $ERR"
    cat "$ERR"
    exit $RC
fi

# ETAPA 2: Substituir nome do banco no dump (usa perl, mais confiavel que sed)
echo "[2/4] Corrigindo nome do banco no dump..."
perl -i -pe "s/\`$BANCO\`/\`${STAGE}\`/g" "$DUMP"
echo "  OK"

# ETAPA 3: Importar para staging
echo "[3/4] Importando para staging ($STAGE)..."
echo "CREATE DATABASE IF NOT EXISTS \`$STAGE\`;" \
    | docker exec -i srvmysql0 mysql -u root -presu100dba 2>>"$ERR"

START=$(date +%s)
docker exec -i srvmysql0 mysql -u root -presu100dba \
    < "$DUMP" 2>>"$ERR"

RC=$?
ELAPSED=$(($(date +%s) - START))
echo "  Import: ${ELAPSED}s (exit: $RC)"

if [ $RC -ne 0 ]; then
    echo "FALHA no import. Veja $ERR"
    tail -20 "$ERR"
    exit $RC
fi

# ETAPA 4: Validar
echo "[4/4] Validando..."
TABELAS=$(docker exec srvmysql0 mysql -u root -presu100dba -N -e \
    "SELECT COUNT(*) FROM information_schema.tables 
     WHERE table_schema='$STAGE';" 2>/dev/null)
echo "  Tabelas no staging: $TABELAS"

# Comparar com forensics
FORENSIC_TABELAS=$(docker exec mariadb-forensics mysql -u root -h 127.0.0.1 -N -e \
    "SELECT COUNT(*) FROM information_schema.tables 
     WHERE table_schema='$BANCO';" 2>/dev/null)
echo "  Tabelas no forensics: $FORENSIC_TABELAS"

if [ "$TABELAS" = "$FORENSIC_TABELAS" ]; then
    echo ""
    echo "========================================"
    echo "RESTORE CONCLUIDO COM SUCESSO"
    echo "  Banco: $BANCO"
    echo "  Staging: $STAGE"
    echo "  Tabelas: $TABELAS"
    echo ""
    echo "Para promover para producao:"
    echo "  docker exec srvmysql0 mysql -u root -presu100dba -e \\"
    echo "    \"DROP DATABASE IF EXISTS ${BANCO}_old;\""
    echo "  docker exec srvmysql0 mysql -u root -presu100dba -e \\"
    echo "    \"RENAME TABLE \`$BANCO\` TO \`${BANCO}_old\`, \`$STAGE\` TO \`$BANCO\`;\""
    echo "========================================"
else
    echo "AVISO: contagem de tabelas diferente! forensics=$FORENSIC_TABELAS staging=$TABELAS"
fi
