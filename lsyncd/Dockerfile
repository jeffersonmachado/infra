FROM alpine:3.20

RUN apk add --no-cache gettext lsyncd rsync openssh-client

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY lsyncd.conf.lua.template /etc/lsyncd/lsyncd.conf.lua.template

RUN chmod 755 /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]