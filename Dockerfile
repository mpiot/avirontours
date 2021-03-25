ARG NODE_VERSION=14.16.0
ARG COMPOSER_VERSION=2.0.11
ARG PHP_VERSION=8.0.3
ARG ICU_VERSION=68.2
ARG APCU_VERSION=5.1.20


#####################################
##               APP               ##
#####################################
FROM php:${PHP_VERSION}-fpm-alpine as app

ARG ICU_VERSION
ARG APCU_VERSION

WORKDIR /srv/app
EXPOSE 80

# persistent / runtime deps
RUN apk add --no-cache \
        nginx \
        supervisor \
		acl \
		fcgi \
		file \
		gettext \
	;

RUN set -eux; \
	apk add --no-cache --virtual .build-deps \
		$PHPIZE_DEPS \
		icu-dev \
		libzip-dev \
		postgresql-dev \
		zlib-dev \
	; \
	\
	docker-php-ext-configure zip; \
    docker-php-ext-configure intl; \
    docker-php-ext-configure pgsql; \
	docker-php-ext-install -j$(nproc) \
            intl \
            pdo \
            pdo_pgsql \
            zip \
            bcmath \
	; \
	pecl install \
		apcu-${APCU_VERSION} \
	; \
	pecl clear-cache; \
	docker-php-ext-enable \
		apcu \
		opcache \
	; \
    docker-php-source delete; \
	\
	runDeps="$( \
		scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
			| tr ',' '\n' \
			| sort -u \
			| awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
	)"; \
	apk add --no-cache --virtual .api-phpexts-rundeps $runDeps; \
	\
	apk del .build-deps

## set recommended PHP.ini settings
# See https://symfony.com/doc/current/performance.html
RUN ln -s $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini
COPY docker/php/conf.d/symfony.ini $PHP_INI_DIR/conf.d/symfony.ini

# set php-fpm conf
#COPY docker/php/php-fpm.d/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf

# copy the Nginx config
COPY docker/nginx/nginx.conf /etc/nginx/

# copy the Supervisor config
COPY docker/supervisord.conf /etc/supervisord.conf


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

# healthcheck
COPY docker/php/healthcheck.sh /usr/local/bin/healthcheck
RUN chmod +x /usr/local/bin/healthcheck

HEALTHCHECK --interval=10s --timeout=3s --retries=3 CMD ["healthcheck"]

# entrypoint
COPY --from=vendor-builder /usr/bin/composer /usr/bin/composer
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
