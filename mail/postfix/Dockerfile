FROM debian:12-slim

RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        ca-certificates \
        gettext-base \
        postfix \
        postfix-ldap \
        postfix-mysql \
        rsyslog \
    && rm -rf /var/lib/apt/lists/*

COPY entrypoint.sh /entrypoint.sh
COPY main.cf.template /templates/main.cf.template
COPY master.cf.template /templates/master.cf.template
COPY mysql_virtual_mailbox_maps.cf.template /templates/mysql_virtual_mailbox_maps.cf.template
COPY mysql_virtual_alias_maps.cf.template /templates/mysql_virtual_alias_maps.cf.template
COPY mysql_transport_maps.cf.template /templates/mysql_transport_maps.cf.template
COPY ldap_virtual_alias_maps.cf.template /templates/ldap_virtual_alias_maps.cf.template

RUN chmod +x /entrypoint.sh

EXPOSE 25 465 587

ENTRYPOINT ["/entrypoint.sh"]