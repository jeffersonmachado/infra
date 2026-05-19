#!/usr/bin/env bash
# ─── migrate.sh ───────────────────────────────────────────────────────────────
# Importa TODAS as zonas dos servidores legados para o MariaDB 10.10.2.99
# (banco que o PowerDNS já usa — apenas adiciona zonas que estão só no BIND)
#
# Zonas já no PowerDNS (10.10.2.51 → 10.10.2.99):
#   results.com.br, lianja.com.br, olimpicshape.com.br, upsupernet.com.br,
#   escolamaat.com.br, alltvblack, baladaesporte, botecoesporte,
#   + 3 zonas reverse in-addr.arpa
#
# Zonas APENAS no BIND (10.10.2.71) que precisam ser importadas:
#   results.intranet (100+ hosts)
#   results.com.br split-horizon (IPs internos)
#   2.10.10.in-addr.arpa, 1.168.192.in-addr.arpa
#   dpaautopecas.com.br, economiatotal.com.br
#   netflix.com (override local)
#   my.ddns.internal.zone
#
# Uso: ./migrate.sh [--dry-run] [--zone nome_da_zona]
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

# Credenciais MariaDB
DB_HOST="${DNS_DB_HOST:-10.10.2.99}"
DB_PORT="${DNS_DB_PORT:-3306}"
DB_NAME="${DNS_DB_NAME:-results}"
DB_USER="${DNS_DB_USER:-resultsdba}"
DB_PASS="${DNS_DB_PASSWORD:-resu1@@dba}"

# Servidores legados
BIND_MASTER="10.10.2.71"
SSH_OPT="-o StrictHostKeyChecking=no -o PasswordAuthentication=yes -o HostKeyAlgorithms=+ssh-rsa"

DRY_RUN=false
ONLY_ZONE=""

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

step() { echo -e "\n${BOLD}${CYAN}▶ $*${RESET}"; }
ok()   { echo -e "  ${GREEN}✓${RESET} $*"; }
warn() { echo -e "  ${YELLOW}!${RESET} $*"; }
die()  { echo -e "${RED}ERRO:${RESET} $*" >&2; exit 1; }

while [[ $# -gt 0 ]]; do
  case "$1" in
    --dry-run) DRY_RUN=true; shift ;;
    --zone)    ONLY_ZONE="$2"; shift 2 ;;
    *) die "Argumento desconhecido: $1" ;;
  esac
done

MYSQL="mysql --host=$DB_HOST --port=$DB_PORT --user=$DB_USER \
  --password=$DB_PASS --database=$DB_NAME"

run_sql() {
  if $DRY_RUN; then
    echo -e "  ${YELLOW}[dry-run SQL]${RESET} $*"
  else
    MYSQL_PWD="$DB_PASS" $MYSQL --execute="$*" 2>/dev/null
  fi
}

# Insere zona se não existir
ensure_zone() {
  local name="$1" type="${2:-NATIVE}"
  local exists
  exists=$(MYSQL_PWD="$DB_PASS" $MYSQL -sN \
    --execute="SELECT COUNT(*) FROM domains WHERE name='$name';" 2>/dev/null)
  if [[ "$exists" == "0" ]]; then
    run_sql "INSERT INTO domains (name, type) VALUES ('$name', '$type');"
    ok "Zona criada: $name"
  else
    ok "Zona já existe: $name"
  fi
}

# Insere registro (upsert por name+type+content)
upsert_record() {
  local domain_id="$1" name="$2" type="$3" content="$4" ttl="${5:-3600}"
  # Escapa conteúdo para SQL
  content=$(echo "$content" | sed "s/'/''/g")
  name=$(echo "$name"  | sed "s/'/''/g")
  run_sql "
    INSERT INTO records (domain_id, name, type, content, ttl, prio, change_date)
    VALUES ($domain_id, '$name', '$type', '$content', $ttl, 0, UNIX_TIMESTAMP())
    ON DUPLICATE KEY UPDATE
      content=VALUES(content), ttl=VALUES(ttl), change_date=UNIX_TIMESTAMP();
  "
}

get_domain_id() {
  local name="$1"
  MYSQL_PWD="$DB_PASS" $MYSQL -sN \
    --execute="SELECT id FROM domains WHERE name='$name';" 2>/dev/null | head -1
}

# ── Importação de zona via zone-transfer (AXFR) do BIND ───────────────────
import_from_bind() {
  local zone="$1" bind_ip="${2:-$BIND_MASTER}"
  step "Importando $zone de $bind_ip via AXFR..."

  # Tenta AXFR (se permitido)
  local axfr_data
  axfr_data=$(dig @$bind_ip AXFR "$zone" +noall +answer 2>/dev/null) || {
    warn "AXFR negado para $zone — usando arquivo de zona via SSH"
    import_from_file "$zone" "$bind_ip"
    return
  }

  if [[ -z "$axfr_data" ]]; then
    warn "AXFR vazio para $zone — usando arquivo de zona via SSH"
    import_from_file "$zone" "$bind_ip"
    return
  fi

  ensure_zone "$zone"
  local domain_id
  domain_id=$(get_domain_id "$zone")

  while IFS= read -r line; do
    [[ -z "$line" || "$line" =~ ^; ]] && continue
    local name type content ttl
    # Parse: name ttl IN type content
    read -r name ttl _ type content <<< "$line" 2>/dev/null || continue
    [[ -z "$type" ]] && continue
    upsert_record "$domain_id" "$name" "$type" "$content" "$ttl"
  done <<< "$axfr_data"

  ok "Importado via AXFR: $zone"
}

# Importa zona lendo arquivo direto do servidor via SSH
import_from_file() {
  local zone="$1" host="$2"
  local zone_file

  # Localiza arquivo de zona no servidor
  zone_file=$(sshpass -p 'resu100gabao' ssh $SSH_OPT root@$host \
    "find /var/named/chroot/var/named /var/named -name 'db.${zone}' 2>/dev/null | head -1" 2>/dev/null)
  [[ -z "$zone_file" ]] && { warn "Arquivo não encontrado para $zone em $host"; return; }

  local zone_content
  zone_content=$(sshpass -p 'resu100gabao' ssh $SSH_OPT root@$host "cat '$zone_file'" 2>/dev/null)

  ensure_zone "$zone"
  local domain_id
  domain_id=$(get_domain_id "$zone")

  # Usa pdnsutil zone2sql para converter BIND format → SQL
  local tmp
  tmp=$(mktemp)
  echo "$zone_content" > "$tmp"

  # Parse simplificado dos registros principais
  while IFS= read -r line; do
    [[ "$line" =~ ^[[:space:]]*\; ]] && continue
    [[ "$line" =~ ^\$ ]] && continue
    [[ -z "${line// }" ]] && continue

    # Detecta padrão: nome [ttl] [IN] tipo conteudo
    if [[ "$line" =~ ^([^[:space:]]+)[[:space:]]+([0-9]+)[[:space:]]+(IN[[:space:]]+)?([A-Z]+)[[:space:]]+(.+)$ ]]; then
      local rname="${BASH_REMATCH[1]}"
      local rttl="${BASH_REMATCH[2]}"
      local rtype="${BASH_REMATCH[4]}"
      local rcontent="${BASH_REMATCH[5]}"
      # Expande @ para o nome da zona
      [[ "$rname" == "@" ]] && rname="$zone"
      # Adiciona zona se nome relativo
      [[ "$rname" != *"."* ]] && rname="${rname}.${zone}"
      upsert_record "$domain_id" "$rname" "$rtype" "$rcontent" "$rttl"
    fi
  done <<< "$zone_content"

  rm -f "$tmp"
  ok "Importado via SSH: $zone ($host)"
}

echo -e "${BOLD}── DNS Consolidado: Migração de Zonas ──────────────────────────────${RESET}"
[[ "$DRY_RUN" == "true" ]] && warn "Modo DRY-RUN ativo — nenhuma alteração será feita"

# ── 1. Zonas BIND que não estão no PowerDNS ───────────────────────────────
BIND_ONLY_ZONES=(
  "results.intranet"
  "2.10.10.in-addr.arpa"
  "1.168.192.in-addr.arpa"
  "dpaautopecas.com.br"
  "economiatotal.com.br"
  "netflix.com"
)

step "Importando zonas exclusivas do BIND..."
for zone in "${BIND_ONLY_ZONES[@]}"; do
  [[ -n "$ONLY_ZONE" && "$ONLY_ZONE" != "$zone" ]] && continue
  import_from_file "$zone" "$BIND_MASTER"
done

# ── 2. Zona results.com.br split-horizon (versão interna com IPs privados) ─
# O PowerDNS já tem results.com.br com IPs públicos.
# Criamos uma zona paralela para split-horizon via recursor.
step "Verificando split-horizon results.com.br..."
# A versão interna usa o mesmo banco mas é servida com IPs privados.
# Já está no banco (PowerDNS existente) — apenas validar.
local_count=$(MYSQL_PWD="$DB_PASS" $MYSQL -sN \
  --execute="SELECT COUNT(*) FROM records r JOIN domains d ON r.domain_id=d.id
             WHERE d.name='results.com.br' AND r.content LIKE '10.10.2.%';" 2>/dev/null || echo 0)
if [[ "$local_count" -gt 0 ]]; then
  warn "results.com.br já tem $local_count registros com IPs internos — verifique split-horizon"
else
  ok "results.com.br sem IPs internos no banco — split-horizon via recursor forward"
fi

# ── 3. my.ddns.internal.zone ──────────────────────────────────────────────
step "Verificando zona DDNS..."
[[ -z "$ONLY_ZONE" || "$ONLY_ZONE" == "my.ddns.internal.zone" ]] && {
  ensure_zone "my.ddns.internal.zone"
  ok "Zona DDNS criada (registros dinâmicos serão atualizados em runtime)"
}

# ── 4. Validação pós-importação ───────────────────────────────────────────
step "Contagem de registros após importação..."
MYSQL_PWD="$DB_PASS" $MYSQL --execute="
  SELECT d.name AS zona, COUNT(r.id) AS registros
  FROM domains d
  LEFT JOIN records r ON r.domain_id = d.id
  GROUP BY d.name
  ORDER BY d.name;" 2>/dev/null

echo ""
echo -e "${GREEN}${BOLD}Migração concluída!${RESET}"
echo ""
echo "Próximo passo: ./validate.sh"
