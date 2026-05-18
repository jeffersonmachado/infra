FROM icinga/icinga2:2.14

# Instala dependências como root
USER root
RUN apt-get update -qq \
 && apt-get install -y --no-install-recommends gettext-base \
 && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /observe-conf.d /usr/lib/r-observe

COPY conf.d/            /observe-conf.d/
COPY --chmod=755 r-observe-notify.sh  /usr/lib/r-observe/notify.sh
COPY --chmod=755 observe-init.sh      /observe-init.sh

# Restaura usuário original da imagem base (icinga, uid=5665)
# para que o entrypoint binário funcione com as permissões corretas
USER icinga

CMD ["/observe-init.sh"]
