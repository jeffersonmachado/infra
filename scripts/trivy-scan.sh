#!/bin/bash
# ─── Trivy Vulnerability Scan ───────────────────────────────────────────────
# Escaneia todas as imagens Docker em uso e gera relatório.
# Uso: ./scripts/trivy-scan.sh
# Cron sugerido: 0 3 * * 0  /opt/results/infra/scripts/trivy-scan.sh
# ───────────────────────────────────────────────────────────────────────────
set -euo pipefail

TRIVY="${TRIVY:-/usr/local/bin/trivy}"
REPORT_DIR="${REPORT_DIR:-/opt/results/infra/dist/trivy-reports}"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
REPORT_FILE="${REPORT_DIR}/trivy-scan-${TIMESTAMP}.txt"
SUMMARY_FILE="${REPORT_DIR}/trivy-summary-${TIMESTAMP}.txt"

mkdir -p "$REPORT_DIR"

if [ ! -x "$TRIVY" ]; then
    echo "❌ Trivy não encontrado em $TRIVY" >&2
    exit 1
fi

echo "🔍 Trivy scan iniciado em $(date)" | tee "$SUMMARY_FILE"
echo "========================================" | tee -a "$SUMMARY_FILE"

# Lista imagens em uso (sem <none>)
images=$(docker images --format '{{.Repository}}:{{.Tag}}' | grep -v '<none>' | sort -u)

total=0
critical_total=0
high_total=0

for img in $images; do
    echo "  Scanning: $img ..."

    # Scan apenas vulnerabilidades (mais rápido)
    output=$("$TRIVY" image --scanners vuln --severity CRITICAL,HIGH --format table --no-progress "$img" 2>&1) || true

    # Extrai totais da linha de sumário "Total: X (HIGH: Y, CRITICAL: Z)"
    critical=$(echo "$output" | grep -oP 'CRITICAL:\s*\K\d+' | head -1 || echo "0")
    high=$(echo "$output" | grep -oP 'HIGH:\s*\K\d+' | head -1 || echo "0")
    # Garante valores numéricos válidos
    crit=$(echo "$critical" | tr -d '[:space:]')
    hi=$(echo "$high" | tr -d '[:space:]')
    [ -z "$crit" ] && crit=0
    [ -z "$hi" ] && hi=0

    if [ "$crit" -gt 0 ] || [ "$hi" -gt 0 ]; then
        echo "    ⚠️  $img → CRITICAL: $crit  HIGH: $hi" | tee -a "$SUMMARY_FILE"
        critical_total=$((critical_total + crit))
        high_total=$((high_total + hi))
    else
        echo "    ✅ $img → limpo" | tee -a "$SUMMARY_FILE"
    fi

    # Salva output completo
    echo "$output" >> "$REPORT_FILE"
    echo -e "\n---\n" >> "$REPORT_FILE"

    total=$((total + 1))
done

echo "" | tee -a "$SUMMARY_FILE"
echo "📊 Total: $total imagens | 🔴 CRITICAL: $critical_total | 🟠 HIGH: $high_total" | tee -a "$SUMMARY_FILE"
echo "📁 Relatório: $REPORT_FILE" | tee -a "$SUMMARY_FILE"
echo "📁 Sumário:   $SUMMARY_FILE" | tee -a "$SUMMARY_FILE"

# Mantém apenas últimos 10 relatórios
ls -t "${REPORT_DIR}"/trivy-scan-*.txt 2>/dev/null | tail -n +11 | xargs rm -f 2>/dev/null || true
ls -t "${REPORT_DIR}"/trivy-summary-*.txt 2>/dev/null | tail -n +11 | xargs rm -f 2>/dev/null || true

echo "✅ Scan concluído em $(date)" | tee -a "$SUMMARY_FILE"
