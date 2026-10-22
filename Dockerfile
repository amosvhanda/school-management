FROM node:22-alpine AS frontend-build
WORKDIR /app
COPY package.json package-lock.json* ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi
COPY resources ./resources
COPY public ./public
COPY vite.config.* ./
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
        bash \
        curl \
        git \
        icu-dev \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
        postgresql-dev \
        supervisor \
        unzip \
        zip \
    && docker-php-ext-install \
        bcmath \
        intl \
        mbstring \
        pcntl \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        zip \
    && rm -rf /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend-build /app/public/build ./public/build

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data bootstrap/cache storage \
    && chmod -R ug+rwx bootstrap/cache storage

EXPOSE 9000

CMD ["php-fpm"]
