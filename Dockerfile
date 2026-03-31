FROM php:8.4-fpm-alpine AS app_base

RUN apk add --no-cache \
        bash \
        freetype-dev \
        git \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
        unzip \
        mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd intl pdo pdo_mysql opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html

FROM node:22-alpine AS frontend_builder

WORKDIR /frontend
COPY package.json package.json
COPY package-lock.json package-lock.json
COPY vite.config.js vite.config.js
COPY frontend frontend
RUN npm ci
RUN npm run build

FROM app_base AS app_dev

CMD ["php-fpm"]

FROM app_base AS app_prod

ENV APP_ENV=prod \
    COMPOSER_ALLOW_SUPERUSER=1

COPY . /var/www/html
COPY --from=frontend_builder /frontend/public/app /var/www/html/public/app
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && php bin/console cache:clear --env=prod \
    && php bin/console cache:warmup --env=prod

CMD ["php-fpm"]
