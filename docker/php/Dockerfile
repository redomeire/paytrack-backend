# ================= STAGE 1: BUILDER =================
FROM php:8.3-fpm-alpine AS builder

# Install semua dependensi OS dan PHP yang dibutuhkan untuk build
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    oniguruma-dev \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    icu-dev

# Install ekstensi PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Install ekstensi Redis
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Copy Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Salin sisa kode aplikasi
COPY . .

# ================= STAGE 2: PRODUCTION =================
FROM php:8.3-fpm-alpine

RUN apk add --no-cache oniguruma libzip libpng libjpeg-turbo libwebp libxml2 icu \
    # Tambahkan dependensi build SEMENTARA untuk mengompilasi redis
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    # Install ekstensi PHP
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache \
    # Kompilasi dan install redis
    && pecl install redis \
    # Aktifkan redis
    && docker-php-ext-enable redis \
    # Hapus semua dependensi build yang tidak lagi dibutuhkan
    && apk del .build-deps

WORKDIR /var/www/html

# Salin HANYA hasil akhir (kode + vendor) dari panggung "builder".
COPY --from=builder /var/www/html .

# Buat direktori dan atur izin
RUN mkdir -p \
    storage/logs \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && find /var/www/html/storage -type f -exec chmod 664 {} \; \
    && find /var/w ww/html/storage -type d -exec chmod 775 {} \; \
    && find /var/www/html/bootstrap/cache -type f -exec chmod 664 {} \; \
    && find /var/www/html/bootstrap/cache -type d -exec chmod 775 {} \;

# Salin konfigurasi PHP kustom Anda
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

# Expose port dan jalankan server
EXPOSE 9000
CMD ["php-fpm"]

