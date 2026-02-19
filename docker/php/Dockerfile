#Build
FROM composer:2 AS builder

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-progress \
    --optimize-autoloader

COPY . .

RUN composer dump-autoload \
    --optimize \
    --classmap-authoritative

#Runtime
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    libpng \
    libxml2 \
    zip \
    oniguruma \
    freetype \
    libjpeg-turbo \
    libwebp \
    icu

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

WORKDIR /var/www/html

COPY --from=builder /app /var/www/html

RUN rm -rf bootstrap/cache/*.php

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

RUN rm -rf \
    /var/www/html/tests \
    /var/www/html/storage/logs/* \
    /var/www/html/node_modules

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

EXPOSE 9000
CMD ["php-fpm"]