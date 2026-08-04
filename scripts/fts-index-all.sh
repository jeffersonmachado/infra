#!/bin/bash
LOGFILE=/var/log/dovecot-fts-index.log
MAILROOT=/var/lib/docker/volumes/infra_maildata/_data

echo "[$(date)] === INICIANDO INDEXACAO FTS FLATCURVE ===" | tee "$LOGFILE"

TOTAL=0
for DOMAIN_DIR in "$MAILROOT"/*/; do
  DOMAIN=$(basename "$DOMAIN_DIR")
  for USER_DIR in "$DOMAIN_DIR"*/; do
    TOTAL=$((TOTAL+1))
  done
done
echo "[$(date)] Total de usuarios: $TOTAL" | tee -a "$LOGFILE"

COUNT=0
for DOMAIN_DIR in "$MAILROOT"/*/; do
  DOMAIN=$(basename "$DOMAIN_DIR")
  for USER_DIR in "$DOMAIN_DIR"*/; do
    USERNAME=$(basename "$USER_DIR")
    USER="${USERNAME}@${DOMAIN}"
    COUNT=$((COUNT+1))
    echo "[$(date)] [$COUNT/$TOTAL] Indexando $USER ..." | tee -a "$LOGFILE"
    docker exec results-mail-dovecot doveadm \
      -o mail_plugins="fts fts_flatcurve" \
      -o plugin/fts=flatcurve \
      -o plugin/fts_languages="pt en es" \
      -o plugin/fts_tokenizers="generic email-address" \
      -o namespace/inbox/fts_enabled=yes \
      fts rescan -u "$USER" 2>&1 | tee -a "$LOGFILE"
    docker exec results-mail-dovecot doveadm \
      -o mail_plugins="fts fts_flatcurve" \
      -o plugin/fts=flatcurve \
      -o plugin/fts_languages="pt en es" \
      -o plugin/fts_tokenizers="generic email-address" \
      -o namespace/inbox/fts_enabled=yes \
      index -u "$USER" "*" 2>&1 | tee -a "$LOGFILE"
  done
done

echo "[$(date)] === INDEXACAO CONCLUIDA ===" | tee -a "$LOGFILE"
