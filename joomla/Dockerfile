FROM php:7.2-apache

RUN printf '%s\n' \
        'deb http://archive.debian.org/debian buster main' \
        'deb http://archive.debian.org/debian-security buster/updates main' \
        > /etc/apt/sources.list \
    && printf '%s\n' \
        'Acquire::Check-Valid-Until "false";' \
        'Acquire::AllowInsecureRepositories "true";' \
        'Acquire::AllowDowngradeToInsecureRepositories "true";' \
        > /etc/apt/apt.conf.d/99archive \
    && apt-get update -o Acquire::Check-Valid-Until=false \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        wget \
        unzip \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j"$(nproc)" gd mysqli pdo_mysql zip \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

COPY site.conf /etc/apache2/sites-available/000-default.conf
COPY docker-entrypoint.sh /usr/local/bin/joomla-entrypoint.sh
COPY php-production.ini /usr/local/etc/php/conf.d/zz-production.ini

RUN chmod 755 /usr/local/bin/joomla-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/joomla-entrypoint.sh"]
CMD ["apache2-foreground"]
