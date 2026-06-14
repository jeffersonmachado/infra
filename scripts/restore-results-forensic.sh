#!/bin/sh
# Restore completo do banco results do mariadb-forensics para produção
# Executar no servidor 10.10.2.30 como root
# Uso: nohup ./restore-results-forensic.sh > /tmp/results_restore.log 2>&1 &

LOG="/opt/results/forensics/restore-20260610/restore-results.log"
ERR="/opt/results/forensics/restore-20260610/restore-results.err"

echo "[$(date)] ========================================"  | tee -a "$LOG"
echo "[$(date)] RESTORE: results (forensics → staging)"  | tee -a "$LOG"
echo "[$(date)] ========================================"  | tee -a "$LOG"

echo "[$(date)] Criando database staging..."  | tee -a "$LOG"
echo 'CREATE DATABASE IF NOT EXISTS results_forensic_stage;' \
  | docker exec -i srvmysql0 mysql -u root -presu100dba 2>>"$ERR"
echo "[$(date)] Staging OK"  | tee -a "$LOG"

echo "[$(date)] Iniciando mysqldump do forensics + pipe para staging..."  | tee -a "$LOG"
echo "[$(date)] (isto pode demorar ~30-60 min para 1.8 GB com innodb_force_recovery=1)"  | tee -a "$LOG"

docker exec mariadb-forensics mysqldump \
  -u root -h 127.0.0.1 \
  --skip-lock-tables \
  --databases results \
  2>>"$ERR" \
  | sed 's/`results`/`results_forensic_stage`/g' \
  | docker exec -i srvmysql0 mysql -u root -presu100dba 2>>"$ERR"

RC=$?
echo "[$(date)] mysqldump+pipe finalizado (exit code: $RC)"  | tee -a "$LOG"

if [ $RC -eq 0 ]; then
    echo "[$(date)] Verificando tabelas importadas..."  | tee -a "$LOG"
    docker exec srvmysql0 mysql -u root -presu100dba -N -e \
      "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='results_forensic_stage';" \
      >> "$LOG" 2>>"$ERR"
    echo "[$(date)] ✅ RESTORE CONCLUÍDO COM SUCESSO"  | tee -a "$LOG"
else
    echo "[$(date)] ❌ RESTORE FALHOU (exit code: $RC) - veja $ERR"  | tee -a "$LOG"
fi
