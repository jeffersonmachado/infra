FROM php:7.4-fpm

# Instalar Apache + extensoes PHP
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        apache2 \
        libfreetype-dev \
        libjpeg-dev \
        libpng-dev \
        libzip-dev \
        wget \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd mysqli pdo_mysql zip \
    && a2enmod proxy_fcgi setenvif rewrite headers expires \
    && a2dismod mpm_prefork \
    && a2enmod mpm_event \
    && rm -rf /var/lib/apt/lists/*

# Apache → php-fpm via proxy_fcgi
RUN printf '%s\n' \
        '<FilesMatch "\\.php$">' \
        '    SetHandler "proxy:fcgi://127.0.0.1:9000"' \
        '</FilesMatch>' \
        > /etc/apache2/conf-available/php-fpm.conf \
    && a2enconf php-fpm

COPY site.conf /etc/apache2/sites-available/000-default.conf
COPY docker-entrypoint.sh /usr/local/bin/joomla-entrypoint.sh
COPY php-production.ini /usr/local/etc/php/conf.d/zz-production.ini

# php-fpm pool config
RUN printf '%s\n' \
        '[www]' \
        'user = www-data' \
        'group = www-data' \
        'listen = 127.0.0.1:9000' \
        'pm = dynamic' \
        'pm.max_children = 50' \
        'pm.start_servers = 10' \
        'pm.min_spare_servers = 5' \
        'pm.max_spare_servers = 20' \
        'pm.max_requests = 10000' \
        > /usr/local/etc/php-fpm.d/zz-docker.conf

RUN chmod 755 /usr/local/bin/joomla-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/joomla-entrypoint.sh"]
CMD ["apachectl", "-D", "FOREGROUND"]
