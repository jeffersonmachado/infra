#!/usr/bin/env bash
# ─── validate.sh ──────────────────────────────────────────────────────────────
# Valida resolução DNS em TODOS os domínios antes do corte
# Compara resposta do novo stack (porta 5353) vs legado (porta 53)
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

NEW_DNS="${NEW_DNS:-127.0.0.1}"
NEW_PORT="${NEW_PORT:-5353}"
OLD_DNS_EXT="${OLD_DNS_EXT:-10.10.2.51}"   # PowerDNS externo legado
OLD_DNS_INT="${OLD_DNS_INT:-10.10.2.1}"    # BIND interno legado

PASS=0; FAIL=0; WARN_COUNT=0

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RESET='\033[0m'
ok()   { ((PASS++));  echo -e "  ${GREEN}✓${RESET} $*"; }
fail() { ((FAIL++));  echo -e "  ${RED}✗${RESET} $*"; }
warn() { ((WARN_COUNT++)); echo -e "  ${YELLOW}!${RESET} $*"; }

resolve() { dig @$1 -p $2 "$3" "$4" +short +time=3 +tries=1 2>/dev/null | head -1; }
resolve_new() { resolve "$NEW_DNS" "$NEW_PORT" "$1" "${2:-A}"; }
resolve_old() { resolve "$OLD_DNS_EXT" 53 "$1" "${2:-A}"; }
resolve_int() { resolve "$OLD_DNS_INT" 53 "$1" "${2:-A}"; }

check() {
  local desc="$1" qname="$2" qtype="${3:-A}" expected="${4:-}"
  local got
  got=$(resolve_new "$qname" "$qtype")
  if [[ -z "$got" ]]; then
    fail "$desc: $qname $qtype → SEM RESPOSTA"
  elif [[ -n "$expected" && "$got" != *"$expected"* ]]; then
    fail "$desc: $qname $qtype → '$got' (esperado: '$expected')"
  else
    ok "$desc: $qname $qtype → $got"
  fi
}

compare() {
  local desc="$1" qname="$2" qtype="${3:-A}"
  local new old
  new=$(resolve_new "$qname" "$qtype")
  old=$(resolve_old "$qname" "$qtype")
  if [[ "$new" == "$old" ]]; then
    ok "$desc: $qname $qtype → $new (=legado)"
  elif [[ -z "$new" ]]; then
    fail "$desc: $qname $qtype → SEM RESPOSTA (legado: $old)"
  else
    warn "$desc: $qname $qtype → novo=$new legado=$old (divergência aceitável?)"
  fi
}

echo "════════════════════════════════════════════════════════════════"
echo " Validação DNS: novo stack ($NEW_DNS:$NEW_PORT)"
echo "════════════════════════════════════════════════════════════════"

echo ""
echo "── 1. Domínios Públicos (results.com.br) ────────────────────────"
compare "Apex"          "results.com.br"           A
compare "www"           "www.results.com.br"        A
compare "MX"            "results.com.br"            MX
compare "SPF"           "results.com.br"            TXT
compare "DKIM"          "mail._domainkey.results.com.br" TXT
compare "DMARC"         "_dmarc.results.com.br"    TXT
compare "r-observe"     "r-observe.results.com.br"  A
compare "rvpn"          "rvpn.results.com.br"       A
compare "mx1"           "mx1.results.com.br"        A
compare "mx2"           "mx2.results.com.br"        A
compare "stun"          "stun.results.com.br"       A
compare "stun-ripabx"   "stun.ripabx.results.com.br" A
compare "rchat"         "rchat.results.com.br"      A
compare "rdialog"       "rdialog.results.com.br"    A

echo ""
echo "── 2. Domínios Hospedados ───────────────────────────────────────"
compare "lianja"        "lianja.com.br"             A
compare "lianja-www"    "www.lianja.com.br"         A
compare "olimpicshape"  "olimpicshape.com.br"       A
compare "upsupernet"    "upsupernet.com.br"         A
compare "escolamaat"    "escolamaat.com.br"         A
compare "dpaautopecas"  "dpaautopecas.com.br"       A
compare "alltvblack"    "alltvblack"                A
compare "baladaesporte" "baladaesporte"             A
compare "botecoesporte" "botecoesporte"             A

echo ""
echo "── 3. DKIM/SPF/DMARC dos domínios hospedados ───────────────────"
for dom in lianja.com.br olimpicshape.com.br upsupernet.com.br escolamaat.com.br; do
  check "SPF $dom"  "$dom"                   TXT "v=spf1"
  check "DKIM $dom" "mail._domainkey.$dom"   TXT "v=DKIM1"
  check "DMARC $dom" "_dmarc.$dom"           TXT "v=DMARC1"
done

echo ""
echo "── 4. Zona Interna results.intranet ────────────────────────────"
check "ns1"         "ns1.results.intranet"      A "10.10.2"
check "srvmysql"    "srvmysql.results.intranet"  A "10.10.2.99"
check "srvhttp"     "srvhttp.results.intranet"   A "10.10.2.60"
check "srvvoz"      "srvvoz.results.intranet"    A "10.10.2.240"
check "ripabx"      "ripabx.results.intranet"    A  # CNAME → srvvoz
check "mexico"      "mexico.results.intranet"    A "10.10.2.30"
check "srvmydns0"   "srvmydns0.results.intranet" A "10.10.2.51"
check "zambia"      "zambia.results.intranet"    A "10.10.2.2"
check "noruega"     "noruega.results.intranet"   A "10.10.2.23"

echo ""
echo "── 5. Reverse DNS ──────────────────────────────────────────────"
check "PTR mx1 público"  "14.15.51.187.in-addr.arpa"  PTR "mx1.results"
check "PTR 10.10.2.60"   "60.2.10.10.in-addr.arpa"    PTR "srvhttp"
check "PTR 10.10.2.240"  "240.2.10.10.in-addr.arpa"   PTR "srvvoz"
check "PTR 10.10.2.99"   "99.2.10.10.in-addr.arpa"    PTR "srvmysql"

echo ""
echo "── 6. Override netflix.com (split-horizon local) ───────────────"
# Internamente deve resolver para 10.10.2.60 (bloqueio local)
# Externamente não deve existir no novo stack
local_netflix=$(resolve_new "netflix.com" A)
if [[ "$local_netflix" == "10.10.2.60" ]]; then
  ok "netflix.com override: $local_netflix (correto)"
elif [[ -z "$local_netflix" ]]; then
  warn "netflix.com: sem resposta (verificar se está no MariaDB)"
else
  warn "netflix.com: $local_netflix (esperado 10.10.2.60)"
fi

echo ""
echo "── 7. Recursão interna (não-autoritativo) ──────────────────────"
check "Recursão google.com" "google.com"   A
check "Recursão cloudflare" "one.one.one.one" A "1.1.1.1"

echo ""
echo "── 8. Split-horizon: results.com.br interno vs externo ─────────"
int_apex=$(resolve_int "results.com.br" A)
new_apex=$(resolve_new "results.com.br" A)
echo "  Interno (BIND):  results.com.br → $int_apex"
echo "  Novo stack:      results.com.br → $new_apex"
if [[ "$new_apex" == "$int_apex" ]] || [[ "$new_apex" == "10.10.2"* ]]; then
  ok "Split-horizon interno: $new_apex"
elif [[ "$new_apex" == "201.6.110.53" ]]; then
  warn "Split-horizon: retornando IP público para clientes internos — verificar forward-zones.conf"
fi

echo ""
echo "── 9. Zonas DDNS ────────────────────────────────────────────────"
# Só verifica que a zona existe (SOA)
check "DDNS zone SOA" "my.ddns.internal.zone" SOA

echo ""
echo "════════════════════════════════════════════════════════════════"
echo -e " Resultado: ${GREEN}${PASS} OK${RESET} | ${RED}${FAIL} FALHAS${RESET} | ${YELLOW}${WARN_COUNT} AVISOS${RESET}"
echo "════════════════════════════════════════════════════════════════"

if [[ $FAIL -gt 0 ]]; then
  echo ""
  echo -e "${RED}Corrija as falhas antes de executar o corte!${RESET}"
  exit 1
else
  echo ""
  echo -e "${GREEN}Pronto para o corte. Execute: ./cutover.sh${RESET}"
fi
