#!/usr/bin/with-contenv bash
# ─── 03.5-artisan-wrapper.sh ─────────────────────────────────────────────────
# 1. Substitui artisan/lnms por wrappers que executam como librenms
# 2. Remove o mysql-schema.sql para que o artisan migrate crie TUDO do zero
#    (evita conflito: schema.sql cria tabelas, migrate falha com duplicadas)
# Roda ANTES do 04-svc-main.sh (ordem alfabetica: 03 < 03.5 < 04)
set -e

echo ">>> 03.5: Installing wrappers + removing schema.sql <<<"

# Remove schema dump para evitar conflito com artisan migrate
rm -f /opt/librenms/database/schema/mysql-schema.sql
echo ">>> schema.sql removed <<<"

# Move originais
mv /opt/librenms/artisan /opt/librenms/artisan.real
mv /opt/librenms/lnms /opt/librenms/lnms.real

# Wrapper para artisan usando chpst (embutido no s6, nao ecoa comando)
cat > /opt/librenms/artisan << 'WRAPPER'
#!/bin/sh
exec chpst -u librenms:librenms /usr/bin/php /opt/librenms/artisan.real "$@"
WRAPPER
chmod 755 /opt/librenms/artisan

# Wrapper para lnms
cat > /opt/librenms/lnms << 'WRAPPER'
#!/bin/sh
exec chpst -u librenms:librenms /usr/bin/php /opt/librenms/artisan.real "$@"
WRAPPER
chmod 755 /opt/librenms/lnms

echo ">>> Wrappers installed <<<"
