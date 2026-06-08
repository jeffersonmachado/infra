FROM proxysql/proxysql:2.7.1

USER root

# mysql-client para health checks e bootstrap
RUN apt-get update \
    && apt-get install -y --no-install-recommends default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Script de bootstrap que configura servers, users e regras
COPY bootstrap.sql /bootstrap.sql
COPY entrypoint-wrapper.sh /entrypoint-wrapper.sh
RUN chmod +x /entrypoint-wrapper.sh

USER proxysql
ENTRYPOINT ["/entrypoint-wrapper.sh"]
