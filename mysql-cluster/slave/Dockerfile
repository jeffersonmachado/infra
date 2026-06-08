FROM mariadb:10.11

# mysql-client para health checks manuais
RUN apt-get update \
    && apt-get install -y --no-install-recommends gettext-base \
    && rm -rf /var/lib/apt/lists/*

COPY entrypoint.sh /slave-entrypoint.sh
RUN chmod +x /slave-entrypoint.sh

ENTRYPOINT ["/slave-entrypoint.sh"]
