FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
        icu-dev \
        gmp-dev \
        libzip-dev \
        oniguruma-dev \
        postgresql-dev \
        sqlite-dev \
    && docker-php-ext-install -j1 \
        bcmath \
        gmp \
        intl \
        mbstring \
        opcache \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        zip

COPY docker/php/conf.d/app.ini /usr/local/etc/php/conf.d/app.ini
COPY --from=vendor /app/vendor ./vendor
COPY . .

RUN mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache database

EXPOSE 9000

CMD ["php-fpm"]
