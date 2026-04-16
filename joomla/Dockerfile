FROM php:7.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        wget \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd mysqli pdo_mysql zip \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

COPY site.conf /etc/apache2/sites-available/000-default.conf
COPY docker-entrypoint.sh /usr/local/bin/joomla-entrypoint.sh
COPY php-production.ini /usr/local/etc/php/conf.d/zz-production.ini

RUN chmod 755 /usr/local/bin/joomla-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/joomla-entrypoint.sh"]
CMD ["apache2-foreground"]