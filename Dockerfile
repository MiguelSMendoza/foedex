FROM php:8.4-fpm-alpine AS app_base

RUN apk add --no-cache \
        bash \
        git \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        unzip \
        mysql-client \
    && docker-php-ext-install intl pdo pdo_mysql opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM app_base AS app_dev

CMD ["php-fpm"]

FROM app_base AS app_prod

ENV APP_ENV=prod \
    COMPOSER_ALLOW_SUPERUSER=1

COPY . /var/www/html
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && php bin/console cache:clear --env=prod \
    && php bin/console cache:warmup --env=prod

CMD ["php-fpm"]
