FROM httpd:2.4-alpine

RUN apk add --no-cache gettext

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY httpd.conf.template /usr/local/apache2/conf/httpd.conf.template
COPY vhosts-templates/ /usr/local/apache2/conf/vhosts-templates/

RUN chmod 755 /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["httpd-foreground"]