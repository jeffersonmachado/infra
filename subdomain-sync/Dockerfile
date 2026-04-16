FROM mariadb:11.4

WORKDIR /app

COPY sync.sh /app/sync.sh

RUN chmod 755 /app/sync.sh

CMD ["/app/sync.sh"]