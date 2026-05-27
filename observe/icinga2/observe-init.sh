#!/bin/bash
# Executado pelo entrypoint binário original APÓS a inicialização de /data e dos symlinks.
# Substitui variáveis nos templates, valida e inicia o daemon.
set -e

CONF_DIR="/etc/icinga2/conf.d"
ENVSUBST_VARS='${ICINGA_API_USER} ${ICINGA_API_PASSWORD} ${ICINGA_REDIS_HOST} ${ICINGA_REDIS_PORT} ${TZ}'

# Copia e processa os arquivos do nosso overlay para conf.d
for f in /observe-conf.d/*; do
  name=$(basename "${f}")
  case "${name}" in
    *.conf.tpl)
      dest="${CONF_DIR}/${name%.tpl}"
      envsubst "${ENVSUBST_VARS}" < "${f}" > "${dest}"
      echo "[observe-init] Gerado: ${dest}"
      ;;
    *.conf)
      cp "${f}" "${CONF_DIR}/${name}"
      echo "[observe-init] Copiado: ${CONF_DIR}/${name}"
      ;;
  esac
done

# Usuário root opcional
if [ -n "${ICINGA_ROOT_PASSWORD:-}" ]; then
  cat >> "${CONF_DIR}/api-users.conf" <<EOF

object ApiUser "root" {
  password = "${ICINGA_ROOT_PASSWORD}"
  permissions = ["*"]
}
EOF
  echo "[observe-init] Usuário root criado."
else
  echo "[observe-init] ICINGA_ROOT_PASSWORD não definido — usuário root omitido."
fi

# A API precisa aceitar comandos e objetos criados pela R-Observe API
# (registerHost, reschedule-check e ações de remediação).
API_CONF="/etc/icinga2/features-enabled/api.conf"
if [ -f "${API_CONF}" ]; then
  sed -i 's/accept_config = false/accept_config = true/' "${API_CONF}"
  sed -i 's/accept_commands = false/accept_commands = true/' "${API_CONF}"
  echo "[observe-init] API habilitada para config e commands."
fi

# Valida antes de iniciar
icinga2 daemon -C
echo "[observe-init] Configuração validada. Iniciando daemon..."

exec icinga2 daemon
