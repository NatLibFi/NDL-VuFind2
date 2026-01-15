FROM php:8.3-apache

LABEL org.opencontainers.image.authors="Ronja Koistinen <ronja.koistinen@helsinki.fi>"
LABEL org.opencontainers.image.description="Local development Finna/VuFind container"
LABEL org.opencontainers.image.vendor="National Library of Finland"

ARG FINNA_DOCUMENT_ROOT=/opt/vufind2
ENV FINNA_DOCUMENT_ROOT=$FINNA_DOCUMENT_ROOT

WORKDIR $FINNA_DOCUMENT_ROOT

# install php extensions and nodejs
RUN set -e; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libfreetype-dev libjpeg62-turbo-dev libpng-dev libzip-dev libicu-dev \
        libxslt1-dev nodejs npm parallel; \
    rm -rf /var/lib/apt/lists/*; \
    install_ext() { docker-php-ext-configure $@; docker-php-ext-install $1; }; \
    install_ext intl; \
    install_ext gd --with-freetype --with-jpeg; \
    install_ext exif; \
    install_ext pdo_mysql; \
    install_ext mysqli; \
    install_ext zip; \
    install_ext sockets; \
    install_ext xsl; \
    install_ext soap;
RUN set -e; \
    pecl install xdebug; \
    docker-php-ext-enable xdebug;

COPY --from=composer:2.7 /usr/bin/composer /usr/local/bin/composer
COPY vufind-start.sh /usr/local/bin/vufind-start.sh
COPY php-localdev.ini /usr/local/etc/php/conf.d/localdev.ini

RUN set -e; \
    mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"; \
    rm /etc/apache2/sites-enabled/000-default.conf; \
    ln -s ../sites-available/vufind2.conf \
        /etc/apache2/sites-enabled/vufind2.conf; \
    ln -s -f -t /etc/apache2/mods-enabled \
        ../mods-available/rewrite.load ../mods-available/headers.load; \
    mkdir /var/log/finna; \
    chown www-data:www-data /var/log/finna

CMD ["/usr/local/bin/vufind-start.sh"]
