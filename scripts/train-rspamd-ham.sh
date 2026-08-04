#!/bin/sh
# ─── Treina rspamd com HAM dos Maildirs até atingir 200 amostras ───────────
# Executa no HOST via cron. Usa docker exec nos containers apropriados.
# Só processa se BAYES_HAM < 200.
# Ignora pastas Spam, Junk, Trash, Virus, Drafts, Sent.
# ───────────────────────────────────────────────────────────────────────────

set -e

MIN_HAM=200
BATCH_SIZE=${BATCH_SIZE:-50}
DOVECOT_CONTAINER="results-mail-dovecot"
RSPAMD_CONTAINER="results-mail-rspamd"
MAILDIR_BASE="/var/mail/vhosts"

log()  { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"; }

# ── Verificar contagem atual de HAM ──────────────────────────────────────
ham_learned=$(docker exec "$RSPAMD_CONTAINER" rspamc stat 2>/dev/null | grep 'BAYES_HAM' | grep -o 'learned: [0-9]*' | awk '{print $2}')

if [ -z "$ham_learned" ]; then
    log "ERRO: nao foi possivel obter estatisticas do rspamd"
    exit 1
fi

log "BAYES_HAM learned: $ham_learned (minimo: $MIN_HAM)"

if [ "$ham_learned" -ge "$MIN_HAM" ]; then
    log "HAM ja atingiu o minimo ($ham_learned >= $MIN_HAM). Nada a fazer."
    exit 0
fi

need=$((MIN_HAM - ham_learned))
log "Precisa de mais $need amostras de HAM. Buscando nos Maildirs..."

# ── Coletar emails HAM dos Maildirs ───────────────────────────────────────
count=0

# Gerar lista de emails em arquivo temporario (evita problemas com pipe/subshell)
FILELIST=$(mktemp)
trap "rm -f $FILELIST" EXIT

# Log persistente dos arquivos treinados (para auditoria)
TRAINED_LOG="/var/log/train-rspamd-ham-files.log"

# Obter usuarios ativos via Dovecot (user_query ja filtra active=1)
# Ou usar lista fixa via variavel de ambiente ACTIVE_USERS
log "Obtendo usuarios ativos..."
if [ -n "${ACTIVE_USERS:-}" ]; then
    # Lista explicita via variavel de ambiente (ex: ACTIVE_USERS="jefferson wedila")
    ACTIVE_USERS=$(echo "$ACTIVE_USERS" | tr ',' ' ')
else
    ACTIVE_USERS=$(docker exec "$DOVECOT_CONTAINER" doveadm user '*' 2>/dev/null | grep -v 'Error\|Fatal\|^$' || true)
fi
if [ -z "$ACTIVE_USERS" ]; then
    log "ERRO: nenhum usuario ativo encontrado"
    exit 1
fi
log "Usuarios ativos: $(echo $ACTIVE_USERS | wc -w)"

# Construir find apenas para Maildirs de usuarios ativos
> "$FILELIST"
for _username in $ACTIVE_USERS; do
    _maildir="${MAILDIR_BASE}/results.com.br/${_username}/Maildir"

    if docker exec "$DOVECOT_CONTAINER" test -d "$_maildir" 2>/dev/null; then
        docker exec "$DOVECOT_CONTAINER" find "$_maildir" \
            -path "*/cur/*" \
            ! -path "*/.Spam/*" \
            ! -path "*/.Junk/*" \
            ! -path "*/.Trash/*" \
            ! -path "*/.Virus/*" \
            ! -path "*/.Drafts/*" \
            ! -path "*/.Sent/*" \
            -type f \
            -mtime +60 2>/dev/null >> "$FILELIST"
    fi
done

total_files=$(wc -l < "$FILELIST")
log "Encontrados $total_files emails candidatos a HAM"

while read -r email; do
    [ -z "$email" ] && continue

    # Verificar se ja atingiu o minimo
    current=$(docker exec "$RSPAMD_CONTAINER" rspamc stat 2>/dev/null | grep 'BAYES_HAM' | grep -o 'learned: [0-9]*' | awk '{print $2}')
    if [ -n "$current" ] && [ "$current" -ge "$MIN_HAM" ] 2>/dev/null; then
        log "Atingiu $current amostras. Parando."
        break
    fi

    # Pular emails muito pequenos
    size=$(docker exec "$DOVECOT_CONTAINER" stat -c%s "$email" 2>/dev/null || echo 0)
    if [ "$size" -lt 200 ]; then
        continue
    fi

    # Enviar para aprendizado via rspamd container
    if docker exec -i "$DOVECOT_CONTAINER" cat "$email" 2>/dev/null | docker exec -i "$RSPAMD_CONTAINER" rspamc learn_ham >/dev/null 2>&1; then
        count=$((count + 1))

        # Marcar com flag NonJunk (igual ao botao "Nao eh lixo" do Roundcube)
        # Obtem o IMAP UID real via dovecot-uidlist
        _filename=$(basename "$email" | sed 's/:2,[A-Za-z]*$//')
        _mailbox_root=$(echo "$email" | sed 's|/cur/.*||')
        _uidlist="${_mailbox_root}/dovecot-uidlist"
        _domain=$(echo "$email" | sed -n 's|.*/vhosts/\([^/]*\)/.*|\1|p')
        _user=$(echo "$email" | sed -n 's|.*/vhosts/[^/]*/\([^/]*\)/Maildir.*|\1|p')
        _mbox="INBOX"
        _folder=$(echo "$email" | sed -n 's|.*/Maildir/\.\([^/]*\)/cur/.*|\1|p')
        [ -n "$_folder" ] && _mbox="$_folder"

        _uid=$(docker exec "$DOVECOT_CONTAINER" awk -v fn="$_filename" '$0 ~ fn {print $1; exit}' "$_uidlist" 2>/dev/null)

        if [ -n "$_uid" ]; then
            docker exec "$DOVECOT_CONTAINER" doveadm flags add -u "${_user}@${_domain}" 'NonJunk' mailbox "$_mbox" uid "$_uid" 2>/dev/null || true
        fi

        # Registrar path para auditoria
        echo "$email" >> "$TRAINED_LOG"

        if [ $((count % 25)) -eq 0 ]; then
            current=$(docker exec "$RSPAMD_CONTAINER" rspamc stat 2>/dev/null | grep 'BAYES_HAM' | grep -o 'learned: [0-9]*' | awk '{print $2}')
            log "Progresso: $count processados, $current aprendidos no total"
        fi
    fi

    if [ "$count" -ge "$BATCH_SIZE" ]; then
        log "Lote de $BATCH_SIZE concluido."
        break
    fi
done < "$FILELIST"

final=$(docker exec "$RSPAMD_CONTAINER" rspamc stat 2>/dev/null | grep 'BAYES_HAM' | grep -o 'learned: [0-9]*' | awk '{print $2}')
log "Concluido. BAYES_HAM learned: $final"
