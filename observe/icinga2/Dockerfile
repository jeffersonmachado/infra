FROM icinga/icinga2:2.14

COPY entrypoint.sh /observe-entrypoint.sh
COPY conf.d/ /etc/icinga2/conf.d/
COPY r-observe-notify.sh /usr/lib/r-observe/notify.sh

RUN chmod +x /observe-entrypoint.sh /usr/lib/r-observe/notify.sh

ENTRYPOINT ["/observe-entrypoint.sh"]
