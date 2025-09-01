# ================= STAGE 1: BUILDER =================
# Panggung ini kita sebut "builder". Isinya lengkap dengan semua alat
# yang dibutuhkan untuk membangun aplikasi Anda (git, composer, -dev packages).
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

WORKDIR /var/w ww/html

# Salin file dependensi dan install (langkah ini di-cache untuk kecepatan build)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Salin sisa kode aplikasi
COPY . .

# ================= STAGE 2: PRODUCTION =================
# Panggung ini adalah image final yang akan kita gunakan.
# Kita mulai dari awal dengan image yang bersih dan super minimalis.
FROM php:8.3-fpm-alpine

# Install HANYA ekstensi PHP dan library yang dibutuhkan saat runtime.
# Kita tidak menyertakan -dev packages, git, curl, dll.
RUN apk add --no-cache libzip libpng libjpeg-turbo libwebp libxml2 icu \
    # Tambahkan dependensi build SEMENTARA untuk mengompilasi redis
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    # Install ekstensi PHP dari source
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache \
    # Kompilasi dan install redis
    && pecl install redis \
    # Aktifkan redis
    && docker-php-ext-enable redis \
    # Hapus semua dependensi build yang tidak lagi dibutuhkan untuk menjaga image tetap kecil
    && apk del .build-deps

WORKDIR /var/www/html

# Perintah Kunci: Salin HANYA hasil akhir (kode aplikasi + folder vendor)
# dari panggung "builder". Semua alat build berat akan ditinggalkan.
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
    && find /var/www/html/storage -type d -exec chmod 775 {} \; \
    && find /var/www/html/bootstrap/cache -type f -exec chmod 664 {} \; \
    && find /var/www/html/bootstrap/cache -type d -exec chmod 775 {} \;

# Salin konfigurasi PHP kustom Anda
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

# Expose port dan jalankan server
EXPOSE 9000
CMD ["php-fpm"]

