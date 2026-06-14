#!/usr/bin/with-contenv bash
# ─── 04-svc-main.sh (custom) ────────────────────────────────────────────────
# Patch: executa artisan/lnms como usuario librenms via wrapper
set -e

echo ">>> CUSTOM 04-svc-main.sh v2 <<<"

# Cria wrappers que executam como librenms
cat > /usr/local/bin/artisan << 'WRAPPER'
#!/bin/sh
exec su -s /bin/sh librenms -c "cd /opt/librenms && php artisan $*"
WRAPPER
chmod 755 /usr/local/bin/artisan

cat > /usr/local/bin/lnms << 'WRAPPER'
#!/bin/sh
exec su -s /bin/sh librenms -c "cd /opt/librenms && php artisan $*"
WRAPPER
chmod 755 /usr/local/bin/lnms

# Garante que /usr/local/bin esta no PATH
export PATH="/usr/local/bin:$PATH"

# Executa o script original
exec bash /etc/cont-init.d/04-svc-main.sh.orig
