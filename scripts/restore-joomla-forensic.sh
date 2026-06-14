#!/bin/sh
# Restore das tabelas se0zs_* do banco joomla do mariadb-forensics para produção
# Executar no servidor 10.10.2.30 como root
# Uso: nohup ./restore-joomla-forensic.sh > /tmp/joomla_restore.log 2>&1 &

LOG="/opt/results/forensics/restore-20260610/restore-joomla.log"
ERR="/opt/results/forensics/restore-20260610/restore-joomla.err"

echo "[$(date)] ========================================"  | tee -a "$LOG"
echo "[$(date)] RESTORE: joomla se0zs_* (forensics → staging)"  | tee -a "$LOG"
echo "[$(date)] ========================================"  | tee -a "$LOG"

# Etapa 1: Criar database staging
echo "[$(date)] Criando joomla_forensic_stage..."  | tee -a "$LOG"
echo 'CREATE DATABASE IF NOT EXISTS joomla_forensic_stage;' \
  | docker exec -i srvmysql0 mysql -u root -presu100dba 2>>"$ERR"
echo "[$(date)] Staging OK"  | tee -a "$LOG"

# Etapa 2: Lista de tabelas se0zs_* a restaurar (excluindo as que já tem prefixo extra)
echo "[$(date)] Listando tabelas se0zs_* no forensics..."  | tee -a "$LOG"
TABLES=$(docker exec mariadb-forensics mysql -u root -h 127.0.0.1 -N -e \
  "SELECT table_name FROM information_schema.tables 
   WHERE table_schema='joomla' AND table_name LIKE 'se0zs_%'
   ORDER BY table_name;" 2>>"$ERR")

TABLE_COUNT=$(echo "$TABLES" | wc -l)
echo "[$(date)] $TABLE_COUNT tabelas se0zs_* encontradas no forensics"  | tee -a "$LOG"

# Etapa 3: mysqldump + pipe
echo "[$(date)] Iniciando dump + pipe..."  | tee -a "$LOG"
echo "[$(date)] (estimativa: ~15-30 min dependendo dos dados)"  | tee -a "$LOG"

docker exec mariadb-forensics mysqldump \
  -u root -h 127.0.0.1 \
  --skip-lock-tables \
  joomla $TABLES \
  2>>"$ERR" \
  | sed 's/`joomla`/`joomla_forensic_stage`/g' \
  | docker exec -i srvmysql0 mysql -u root -presu100dba 2>>"$ERR"

RC=$?
echo "[$(date)] mysqldump+pipe finalizado (exit code: $RC)"  | tee -a "$LOG"

# Etapa 4: Validar
if [ $RC -eq 0 ]; then
    IMPORTED=$(docker exec srvmysql0 mysql -u root -presu100dba -N -e \
      "SELECT COUNT(*) FROM information_schema.tables 
       WHERE table_schema='joomla_forensic_stage';" 2>>"$ERR")
    echo "[$(date)] Tabelas importadas: $IMPORTED / $TABLE_COUNT"  | tee -a "$LOG"
    
    # Comparar algumas tabelas críticas
    echo "[$(date)] Validando tabelas críticas..."  | tee -a "$LOG"
    for t in assets extensions menu modules users; do
        FORENSIC_COUNT=$(docker exec mariadb-forensics mysql -u root -h 127.0.0.1 -N -e \
          "SELECT COUNT(*) FROM joomla.se0zs_${t};" 2>/dev/null)
        STAGE_COUNT=$(docker exec srvmysql0 mysql -u root -presu100dba -N -e \
          "SELECT COUNT(*) FROM joomla_forensic_stage.se0zs_${t};" 2>/dev/null)
        echo "[$(date)]   se0zs_${t}: forensics=$FORENSIC_COUNT stage=$STAGE_COUNT"  | tee -a "$LOG"
    done
    
    echo "[$(date)] ✅ RESTORE JOOMLA CONCLUÍDO COM SUCESSO"  | tee -a "$LOG"
else
    echo "[$(date)] ❌ RESTORE JOOMLA FALHOU (exit code: $RC) - veja $ERR"  | tee -a "$LOG"
fi
