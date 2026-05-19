#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

mapfile -t targets < <(
  {
    find scripts -type f -name '*.sh'
    find dns-consolidated/scripts -type f -name '*.sh'
    find . -type f -path '*/docker-entrypoint.sh'
    find . -type f -name 'entrypoint.sh'
  } | sort -u
)

changed=0
for f in "${targets[@]}"; do
  if [[ ! -x "$f" ]]; then
    chmod +x "$f"
    changed=$((changed + 1))
  fi
done

echo "[fix-executable-permissions] arquivos verificados: ${#targets[@]}"
echo "[fix-executable-permissions] permissões ajustadas: $changed"
