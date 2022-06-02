ARG COMPOSER_VERSION=2.2
ARG NODE_IMAGE=16.13-alpine
ARG PHP_VERSION=8.1
ARG PHP_VARIANT=alpine
ARG NGINX_VERSION=1.21

#
# PHP Dependencies
#
FROM composer:${COMPOSER_VERSION} AS vendor
ARG DEVELOPMENT_MODE=1

COPY composer.json composer.json
COPY composer.lock composer.lock

#set -e: exits if a command fails
#set -u: errors if an variable is referenced before being set
#set -x: shows the commands that get run

RUN set -eux; \
    export COMPOSER_INSTALL_FLAGS=${COMPOSER_INSTALL_FLAGS:---no-interaction --no-plugins --no-scripts --ignore-platform-reqs --prefer-dist}; \
    if [ "$DEVELOPMENT_MODE" != "1" ]; then \
      export COMPOSER_INSTALL_FLAGS="${COMPOSER_INSTALL_FLAGS} --optimize-autoloader --no-dev"; \
    fi; \
    composer install ${COMPOSER_INSTALL_FLAGS}

#
# Frontend
#
FROM node:${NODE_IMAGE} AS frontend
ARG DEVELOPMENT_MODE=1

WORKDIR /app

COPY package.json package-lock.json webpack.mix.js ./

RUN set -eux; \
    if [ "$DEVELOPMENT_MODE" != "1" ]; then \
      npm ci; \
    else \
      npm install; \
    fi

COPY resources/js ./resources/js
COPY resources/scss ./resources/scss

RUN set -eux; \
    if [ "$DEVELOPMENT_MODE" != "1" ]; then \
      npm run production; \
    else \
      npm run development; \
    fi

#
# Nginx
#
FROM nginx:${NGINX_VERSION}-alpine AS nginx

WORKDIR /app

COPY --from=frontend /app/public/js/ ./public/js/
COPY --from=frontend /app/public/css/ ./public/css/
COPY --from=frontend /app/public/fonts/ ./public/fonts/
COPY --from=frontend /app/public/mix-manifest.json ./public/mix-manifest.json

COPY config/docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY public ./public

#
# Application
#
FROM php:${PHP_VERSION}-fpm-${PHP_VARIANT} AS php-fpm
ARG DEVELOPMENT_MODE=1

WORKDIR /app

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN set -eux; \
    export PHP_FPM_INSTALL_EXTENSIONS=${PHP_FPM_INSTALL_EXTENSIONS:-bcmath bz2 gd intl pdo_mysql zip redis}; \
    if [ "$DEVELOPMENT_MODE" == "1" ]; then \
      export PHP_FPM_INSTALL_EXTENSIONS="${PHP_FPM_INSTALL_EXTENSIONS} xdebug"; \
    else \
      export PHP_FPM_INSTALL_EXTENSIONS="${PHP_FPM_INSTALL_EXTENSIONS} opcache"; \
    fi; \
    install-php-extensions ${PHP_FPM_INSTALL_EXTENSIONS};

# Copy frontend build
COPY --from=frontend /app/public/js/ ./public/js/
COPY --from=frontend /app/public/css/ ./public/css/
COPY --from=frontend /app/public/fonts/ ./public/fonts/
COPY --from=frontend /app/public/mix-manifest.json ./public/mix-manifest.json

# Copy composer dependencies
COPY --from=vendor /app/vendor/ ./vendor/
COPY . .

RUN chown -R www-data:www-data ./ \
    && find ./ -type f -exec chmod 644 {} \; \
    && find ./ -type d -exec chmod 755 {} \; \
    && chmod 744 ./artisan \
    && chmod 775 -R ./storage ./bootstrap/cache

CMD ["php-fpm"]

FROM php:${PHP_VERSION}-cli-${PHP_VARIANT} AS php-cli
ARG DEVELOPMENT_MODE=1

WORKDIR /app

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN set -eux; \
    export PHP_CLI_INSTALL_EXTENSIONS=${PHP_CLI_INSTALL_EXTENSIONS:-bcmath bz2 gd intl pdo_mysql zip redis pcntl}; \
    if [ "$DEVELOPMENT_MODE" == "1" ]; then \
      export PHP_CLI_INSTALL_EXTENSIONS="${PHP_CLI_INSTALL_EXTENSIONS} xdebug"; \
    else \
      export PHP_CLI_INSTALL_EXTENSIONS="${PHP_CLI_INSTALL_EXTENSIONS} opcache"; \
    fi; \
    install-php-extensions ${PHP_CLI_INSTALL_EXTENSIONS};

# Copy composer dependencies
COPY --from=vendor /app/vendor/ ./vendor/
COPY . .

RUN set -eux; \
    { \
        echo 'memory_limit = 512M'; \
    } > /usr/local/etc/php/conf.d/memlimit.ini

RUN chown -R www-data:www-data ./ \
    && find ./ -type f -exec chmod 644 {} \; \
    && find ./ -type d -exec chmod 755 {} \; \
    && chmod 744 ./artisan ./config/docker/php/cli-entrypoint.sh \
    && chmod 775 -R ./storage ./bootstrap/cache

STOPSIGNAL SIGTERM
ENTRYPOINT ["/app/config/docker/php/cli-entrypoint.sh"]
CMD []
