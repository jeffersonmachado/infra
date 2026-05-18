#!/usr/bin/env bash
# ─── rollback.sh ──────────────────────────────────────────────────────────────
# Reverte o corte: para dnsdist na :53, reinicia BIND
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
COMPOSE_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

echo "ROLLBACK: parando dnsdist na :53..."
cd "$COMPOSE_DIR"
DNS_PORT=53 docker compose -f docker-compose.yml --env-file .env \
  stop dnsdist 2>/dev/null || true

echo "ROLLBACK: reiniciando BIND..."
service named start 2>/dev/null && echo "BIND reiniciado" || echo "BIND já rodando"

sleep 2
echo "ROLLBACK: verificando porta 53..."
ss -tlnup | grep ":53" && echo "Porta 53 OK (BIND)" || echo "AVISO: porta 53 vazia"

echo ""
echo "ROLLBACK CONCLUÍDO. DNS de volta ao BIND."
echo "Retorne o dnsdist para a porta 5353 com: DNS_PORT=5353 docker compose up -d dnsdist"
