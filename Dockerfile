ARG NODE_VERSION=15.5.0
ARG COMPOSER_VERSION=2.0.8
ARG PHP_VERSION=8.0.0
ARG ICU_VERSION=68.2
ARG APCU_VERSION=5.1.19


#####################################
##               APP               ##
#####################################
FROM php:${PHP_VERSION}-fpm as app

ARG ICU_VERSION
ARG APCU_VERSION

ENV APP_VERSION=0.0.0

WORKDIR /app

EXPOSE 80

# Install paquet requirements
RUN export PHP_CPPFLAGS="${PHP_CPPFLAGS} -std=c++11"; \
    set -ex; \
    # Install required system packages
    apt-get update; \
    apt-get install -qy --no-install-recommends \
            nginx \
            supervisor \
            libzip-dev \
            libpq-dev \
    ; \
    # Compile ICU (required by intl php extension)
    curl -L -o /tmp/icu.tar.gz https://github.com/unicode-org/icu/releases/download/release-$(echo ${ICU_VERSION} | sed s/\\./-/g)/icu4c-$(echo ${ICU_VERSION} | sed s/\\./_/g)-src.tgz; \
    tar -zxf /tmp/icu.tar.gz -C /tmp; \
    cd /tmp/icu/source; \
    ./configure --prefix=/usr/local; \
    make clean; \
    make; \
    make install; \
    #Install the PHP extensions
    docker-php-ext-configure intl; \
    docker-php-ext-configure pgsql; \
    docker-php-ext-install -j "$(nproc)" \
            intl \
            pdo \
            pdo_pgsql \
            zip \
            bcmath \
    ; \
    pecl install \
            apcu-${APCU_VERSION} \
    ; \
    docker-php-ext-enable \
            opcache \
            apcu \
    ; \
    docker-php-source delete; \
    # Clean aptitude cache and tmp directory
    apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*;

## set recommended PHP.ini settings
# See https://symfony.com/doc/current/performance.html
RUN ln -s $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini
RUN { \
        echo 'apc.enable_cli = 1'; \
        echo 'date.timezone = Europe/Paris'; \
        echo 'session.auto_start = Off'; \
        echo 'short_open_tag = Off'; \
        echo 'expose_php = off'; \
        echo 'error_log = /proc/self/fd/2'; \
        echo 'opcache.interned_strings_buffer = 16'; \
        echo 'opcache.max_accelerated_files = 20000'; \
        echo 'opcache.memory_consumption = 256'; \
        echo 'opcache.validate_timestamps = 0'; \
        echo 'realpath_cache_size = 4096K'; \
        echo 'realpath_cache_ttl = 600'; \
        echo 'opcache.preload_user = www-data'; \
        echo 'opcache.preload = /srv/app/config/preload.php'; \
        echo 'opcache.max_accelerated_files = 20011'; \
    } > $PHP_INI_DIR/conf.d/symfony.ini

# copy the Nginx config
COPY docker/nginx.conf /etc/nginx/

# copy the Supervisor config
COPY docker/supervisord.conf /etc/supervisor/conf.d/


#####################################
##       PROD VENDOR BUILDER       ##
#####################################
FROM composer:${COMPOSER_VERSION} as vendor-builder

COPY . /srv/app
WORKDIR /srv/app

RUN APP_ENV=prod composer install --ignore-platform-reqs -o -n --no-ansi --no-dev


#####################################
##       PROD ASSETS BUILDER       ##
#####################################
FROM node:${NODE_VERSION} as assets-builder

COPY --from=vendor-builder /srv/app /srv/app
WORKDIR /srv/app

RUN yarn install && yarn build && rm -R node_modules


#####################################
##             APP PROD            ##
#####################################
FROM app as app-prod

ENV APP_ENV=prod \
    APP_VERSION=0.0.0

COPY --chown=www-data --from=assets-builder /srv/app /srv/app
WORKDIR /srv/app

# copy the Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN ["chmod", "+x", "/usr/local/bin/entrypoint.sh"]

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
